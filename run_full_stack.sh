#!/usr/bin/env bash

# One-touch bootstrap for the Face Recognition Attendance System via Docker Compose.
# Usage: ./run_full_stack.sh

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
WEIGHTS_DIR="$PROJECT_ROOT/services/face_backend/weights"
WEIGHTS_FILE="$WEIGHTS_DIR/yolov8n-face.pt"
WEIGHTS_URL="https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8n.pt"
LABELS_SRC="$PROJECT_ROOT/resources/labels"
LABELS_DST="$PROJECT_ROOT/resources/labels_runtime"

print_step() {
  echo
  echo "=== $1 ==="
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "[ERROR] Required command '$1' not found. Please install it and rerun." >&2
    exit 1
  fi
}

print_step "Checking prerequisites"
require_cmd docker

if docker compose version >/dev/null 2>&1; then
  COMPOSE_CMD=(docker compose)
elif docker-compose version >/dev/null 2>&1; then
  COMPOSE_CMD=(docker-compose)
else
  echo "[ERROR] Docker Compose is not available. Install docker compose plugin or docker-compose binary." >&2
  exit 1
fi

if ! command -v curl >/dev/null 2>&1 && ! command -v wget >/dev/null 2>&1; then
  echo "[ERROR] Neither curl nor wget is available for downloading weights." >&2
  exit 1
fi

print_step "Ensuring YOLO weights are present"
mkdir -p "$WEIGHTS_DIR"
if [ ! -s "$WEIGHTS_FILE" ]; then
  echo "Downloading weights to $WEIGHTS_FILE"
  TMP_FILE="${WEIGHTS_FILE}.tmp"
  if command -v curl >/dev/null 2>&1; then
    curl -L "$WEIGHTS_URL" -o "$TMP_FILE"
  else
    wget -O "$TMP_FILE" "$WEIGHTS_URL"
  fi
  mv "$TMP_FILE" "$WEIGHTS_FILE"
else
  echo "Weights already exist, skipping download"
fi

print_step "Syncing runtime labels"
mkdir -p "$LABELS_DST"
if command -v rsync >/dev/null 2>&1; then
  rsync -a --delete "$LABELS_SRC"/ "$LABELS_DST"/
else
  # Fallback without rsync: clear destination then copy
  find "$LABELS_DST" -mindepth 1 -maxdepth 1 -exec rm -rf {} +
  cp -a "$LABELS_SRC"/. "$LABELS_DST"/
fi

echo "Labels ready: $(find "$LABELS_DST" -mindepth 1 -maxdepth 1 -type d | wc -l) identities"

print_step "Building and starting Docker stack"
"${COMPOSE_CMD[@]}" up -d --build

print_step "Stack status"
"${COMPOSE_CMD[@]}" ps

echo
echo "The system is now running. Access points:"
echo "  Web UI:        http://localhost:8080"
echo "  Face backend:  http://localhost:8001/health"
echo "  phpMyAdmin:    http://localhost:8081"
echo
