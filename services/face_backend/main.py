import base64
import logging
import os
import threading
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Tuple

import cv2
import numpy as np
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from insightface.app import FaceAnalysis

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
logger = logging.getLogger(__name__)

FACE_LABELS_DIR = Path(os.getenv("FACE_LABELS_DIR", "/app/resources/labels_runtime"))
MIN_SCORE = float(os.getenv("FACE_LOGIN_MIN_SCORE", "0.55"))
SIM_THRESHOLD = float(os.getenv("FACE_SIM_THRESHOLD", "0.3"))
MIN_CONFIDENCE = float(os.getenv("FACE_MIN_CONFIDENCE", "0.2"))
MIN_BOX = float(os.getenv("FACE_MIN_BOX", "10"))

app = FastAPI(title="Face Recognition Backend", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"]
)


class MatchItem(BaseModel):
    label: str
    score: float
    bbox: List[int] = Field(default_factory=list)


class MatchResponse(BaseModel):
    matches: List[MatchItem] = Field(default_factory=list)


class MatchRequest(BaseModel):
    image: str
    width: Optional[int] = None
    height: Optional[int] = None


face_lock = threading.Lock()
embeddings_lock = threading.Lock()
face_app: Optional[FaceAnalysis] = None
embeddings: Dict[str, np.ndarray] = {}
embedding_cache: Dict[str, Tuple[float, np.ndarray]] = {}
last_reload: Optional[datetime] = None


def _normalise(vec: np.ndarray) -> np.ndarray:
    norm = np.linalg.norm(vec)
    if norm == 0:
        return vec
    return vec / norm


def _load_face_app() -> FaceAnalysis:
    global face_app
    with face_lock:
        if face_app is None:
            logger.info("Initialising InsightFace (this may download models on first run)...")
            analyser = FaceAnalysis(name="buffalo_l", providers=["CPUExecutionProvider"])
            analyser.prepare(ctx_id=0, det_thresh=MIN_CONFIDENCE, det_size=(640, 640))
            face_app = analyser
        return face_app


def _decode_image(payload: str) -> np.ndarray:
    if "," in payload:
        payload = payload.split(",", 1)[1]
    try:
        binary = base64.b64decode(payload)
    except (ValueError, TypeError) as exc:
        raise HTTPException(status_code=400, detail="Invalid base64 payload") from exc
    image = np.frombuffer(binary, dtype=np.uint8)
    frame = cv2.imdecode(image, cv2.IMREAD_COLOR)
    if frame is None:
        raise HTTPException(status_code=400, detail="Unable to decode image")
    return frame


def _extract_faces(image: np.ndarray):
    analyser = _load_face_app()
    return analyser.get(image)


def _scan_embeddings() -> Dict[str, np.ndarray]:
    global embedding_cache
    analyser = _load_face_app()
    store: Dict[str, List[np.ndarray]] = {}
    new_cache: Dict[str, Tuple[float, np.ndarray]] = {}
    if not FACE_LABELS_DIR.exists():
        logger.warning("Labels directory %s does not exist", FACE_LABELS_DIR)
        return {}

    for label_dir in sorted(FACE_LABELS_DIR.iterdir()):
        if not label_dir.is_dir():
            continue
        label = label_dir.name.strip()
        if not label:
            continue
        vectors: List[np.ndarray] = []
        for image_path in sorted(label_dir.iterdir()):
            if image_path.suffix.lower() not in {".jpg", ".jpeg", ".png", ".bmp"}:
                continue
            cache_key = str(image_path.resolve())
            try:
                mtime = image_path.stat().st_mtime
            except FileNotFoundError:
                continue
            cached = embedding_cache.get(cache_key)
            if cached and cached[0] == mtime:
                vectors.append(cached[1])
                new_cache[cache_key] = cached
                continue

            data = image_path.read_bytes()
            frame = cv2.imdecode(np.frombuffer(data, dtype=np.uint8), cv2.IMREAD_COLOR)
            if frame is None:
                logger.warning("Failed to decode %s", image_path)
                continue
            faces = analyser.get(frame)
            if not faces:
                logger.warning("No face detected in %s", image_path)
                continue
            # Use the face with highest detection score
            best_face = max(faces, key=lambda f: float(getattr(f, "det_score", 0.0)))
            bbox = best_face.bbox.astype(int)
            if (bbox[2] - bbox[0]) < MIN_BOX or (bbox[3] - bbox[1]) < MIN_BOX:
                logger.debug("Skipping %s due to small bbox", image_path)
                continue
            embedding = getattr(best_face, "embedding", None)
            if embedding is None:
                logger.warning("Face embedding missing for %s", image_path)
                continue
            vector = _normalise(np.asarray(embedding, dtype=np.float32))
            vectors.append(vector)
            new_cache[cache_key] = (mtime, vector)
        if vectors:
            store[label] = np.vstack(vectors)
            logger.info("Loaded %d embeddings for label %s", len(vectors), label)
        else:
            logger.warning("No usable embeddings for label %s", label)
    embedding_cache = new_cache
    return {label: np.asarray(vecs, dtype=np.float32) for label, vecs in store.items()}


def reload_embeddings() -> Dict[str, np.ndarray]:
    global embeddings, last_reload
    with embeddings_lock:
        embeddings = _scan_embeddings()
        last_reload = datetime.utcnow()
    logger.info("Embeddings reloaded. Labels: %d", len(embeddings))
    return embeddings


@app.on_event("startup")
def startup_event() -> None:
    _load_face_app()
    reload_embeddings()


@app.get("/health")
def health() -> Dict[str, object]:
    return {
        "status": "ok",
        "labels": len(embeddings),
        "last_reload": last_reload.isoformat() if last_reload else None
    }


@app.post("/reload")
def reload_endpoint() -> Dict[str, object]:
    reload_embeddings()
    return {"status": "reloaded", "labels": len(embeddings)}


def _best_match(vec: np.ndarray) -> MatchItem:
    best_label = "unknown"
    best_score = 0.0
    with embeddings_lock:
        for label, vectors in embeddings.items():
            if vectors.size == 0:
                continue
            sims = vectors @ vec
            score = float(np.max(sims))
            if score > best_score:
                best_score = score
                best_label = label
    distance = 1.0 - best_score
    if best_score >= MIN_SCORE and distance <= SIM_THRESHOLD:
        return MatchItem(label=best_label, score=best_score, bbox=[])
    return MatchItem(label="unknown", score=best_score, bbox=[])


@app.post("/match", response_model=MatchResponse)
def match(request: MatchRequest) -> MatchResponse:
    if not request.image:
        raise HTTPException(status_code=400, detail="Missing image payload")
    frame = _decode_image(request.image)
    faces = _extract_faces(frame)
    matches: List[MatchItem] = []
    for face in faces:
        det_score = float(getattr(face, "det_score", 0.0))
        if det_score < MIN_CONFIDENCE:
            continue
        bbox = face.bbox.astype(int)
        width = bbox[2] - bbox[0]
        height = bbox[3] - bbox[1]
        if width < MIN_BOX or height < MIN_BOX:
            continue
        embedding = getattr(face, "embedding", None)
        if embedding is None:
            continue
        vec = _normalise(np.asarray(embedding, dtype=np.float32))
        match_item = _best_match(vec)
        match_item.bbox = [int(bbox[0]), int(bbox[1]), int(bbox[2]), int(bbox[3])]
        match_item.score = float(match_item.score)
        matches.append(match_item)
    return MatchResponse(matches=matches)
