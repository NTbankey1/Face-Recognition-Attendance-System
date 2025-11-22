import argparse
import base64
import json
from pathlib import Path
from typing import Any, Dict

import requests

DEFAULT_SERVICE_URL = "http://127.0.0.1:8001"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Send a face image to the /match endpoint for quick evaluation")
    parser.add_argument("image", type=Path, help="Path to the image to test")
    parser.add_argument("--service", type=str, default=DEFAULT_SERVICE_URL, help="Face service base URL (default: %(default)s)")
    parser.add_argument("--pretty", action="store_true", help="Pretty-print JSON output")
    return parser.parse_args()


def encode_image(image_path: Path) -> str:
    with image_path.open("rb") as f:
        data = f.read()
    encoded = base64.b64encode(data).decode("utf-8")
    return f"data:image/jpeg;base64,{encoded}"


def call_match(service_url: str, payload: Dict[str, Any]) -> Dict[str, Any]:
    url = service_url.rstrip("/") + "/match"
    response = requests.post(url, json=payload, timeout=30)
    response.raise_for_status()
    return response.json()


def main() -> None:
    args = parse_args()
    if not args.image.exists():
        raise FileNotFoundError(f"Image {args.image} not found")
    payload = {
        "image": encode_image(args.image)
    }
    data = call_match(args.service, payload)
    if args.pretty:
        print(json.dumps(data, indent=2, ensure_ascii=False))
    else:
        print(data)


if __name__ == "__main__":
    main()
