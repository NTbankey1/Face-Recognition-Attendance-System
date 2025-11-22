import argparse
import json
import os
from pathlib import Path
from typing import List

import cv2
import numpy as np
from tqdm import tqdm

try:
    import albumentations as A  # type: ignore
except ImportError:
    A = None

DEFAULT_WIDTH = 640
DEFAULT_HEIGHT = 480
AUG_COUNT = 4


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Normalize and augment face images for the Face Recognition Attendance System")
    parser.add_argument("input", type=Path, help="Directory containing per-student folders with images")
    parser.add_argument("output", type=Path, help="Directory to write processed images")
    parser.add_argument("--augment", action="store_true", help="Enable light augmentation using Albumentations if installed")
    parser.add_argument("--overwrite", action="store_true", help="Overwrite output directory if it exists")
    parser.add_argument("--summary", action="store_true", help="Print JSON summary of processed files")
    return parser.parse_args()


def ensure_dir(path: Path, overwrite: bool = False) -> None:
    if path.exists():
        if overwrite:
            for item in path.glob("**/*"):
                if item.is_file():
                    item.unlink()
        else:
            raise FileExistsError(f"Output directory {path} already exists; use --overwrite to clear")
    path.mkdir(parents=True, exist_ok=True)


def hist_equalize_color(image: np.ndarray) -> np.ndarray:
    if image.ndim != 3 or image.shape[2] != 3:
        return image
    ycrcb = cv2.cvtColor(image, cv2.COLOR_BGR2YCrCb)
    y, cr, cb = cv2.split(ycrcb)
    y_eq = cv2.equalizeHist(y)
    ycrcb = cv2.merge((y_eq, cr, cb))
    return cv2.cvtColor(ycrcb, cv2.COLOR_YCrCb2BGR)


def build_augmentations() -> List[A.Compose]:
    if not A:
        return []
    transforms = []
    transforms.append(A.Compose([
        A.Rotate(limit=5, border_mode=cv2.BORDER_REFLECT_101, p=1.0)
    ]))
    transforms.append(A.Compose([
        A.Rotate(limit=-5, border_mode=cv2.BORDER_REFLECT_101, p=1.0)
    ]))
    transforms.append(A.Compose([
        A.RandomBrightnessContrast(brightness_limit=0.1, contrast_limit=0.08, p=1.0)
    ]))
    transforms.append(A.Compose([
        A.RandomGamma(gamma_limit=(90, 110), p=1.0)
    ]))
    return transforms


def process_image(image_path: Path, output_path: Path, augment: bool = False, aug_transforms: List[A.Compose] = None) -> List[Path]:
    image = cv2.imread(str(image_path))
    if image is None:
        return []
    resized = cv2.resize(image, (DEFAULT_WIDTH, DEFAULT_HEIGHT), interpolation=cv2.INTER_AREA)
    normalized = hist_equalize_color(resized)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    cv2.imwrite(str(output_path), normalized, [cv2.IMWRITE_JPEG_QUALITY, 90])
    generated = [output_path]
    if augment and aug_transforms:
        for idx, transform in enumerate(aug_transforms, start=1):
            augmented = transform(image=normalized)["image"]
            aug_path = output_path.with_name(f"{output_path.stem}_aug{idx}.jpg")
            cv2.imwrite(str(aug_path), augmented, [cv2.IMWRITE_JPEG_QUALITY, 90])
            generated.append(aug_path)
    return generated


def main() -> None:
    args = parse_args()
    input_dir: Path = args.input
    output_dir: Path = args.output
    if not input_dir.exists() or not input_dir.is_dir():
        raise FileNotFoundError(f"Input directory {input_dir} not found")
    ensure_dir(output_dir, overwrite=args.overwrite)
    aug_transforms = build_augmentations() if args.augment else []
    summary = {}
    student_dirs = [p for p in input_dir.iterdir() if p.is_dir()]
    for student_dir in tqdm(student_dirs, desc="Students"):
        student_id = student_dir.name
        images = list(student_dir.glob("*.jpg")) + list(student_dir.glob("*.jpeg")) + list(student_dir.glob("*.png"))
        processed_paths = []
        for img_path in images:
            rel_name = img_path.stem
            out_path = output_dir / student_id / f"{rel_name}.jpg"
            processed_paths.extend(process_image(img_path, out_path, augment=args.augment, aug_transforms=aug_transforms))
        summary[student_id] = {
            "source_images": len(images),
            "generated_files": [str(p.relative_to(output_dir)) for p in processed_paths],
        }
    if args.summary:
        print(json.dumps(summary, indent=2))


if __name__ == "__main__":
    main()
