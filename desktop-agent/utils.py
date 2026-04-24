"""
Utility functions for the monitoring agent.
Includes logging, image helpers, and JSON I/O.
"""

import logging
import json
import hashlib
from pathlib import Path
from datetime import datetime
from config import Config


def setup_logging():
    """Configure logging for the agent"""
    Config.ensure_directories()
    log_format = '%(asctime)s - %(name)s - %(levelname)s - %(message)s'

    file_handler = logging.FileHandler(Config.LOG_FILE)
    file_handler.setLevel(logging.DEBUG)
    file_handler.setFormatter(logging.Formatter(log_format))

    console_handler = logging.StreamHandler()
    console_handler.setLevel(logging.DEBUG if Config.DEBUG else logging.INFO)
    console_handler.setFormatter(logging.Formatter(log_format))

    root_logger = logging.getLogger()
    root_logger.setLevel(logging.DEBUG)
    root_logger.addHandler(file_handler)
    root_logger.addHandler(console_handler)

    return logging.getLogger(__name__)


logger = setup_logging()


def get_timestamp() -> str:
    """Get current UTC timestamp in ISO 8601 format"""
    return datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ')


def hash_screenshot(image_data: bytes) -> str:
    """Generate SHA-256 hash of screenshot for deduplication"""
    return hashlib.sha256(image_data).hexdigest()


def compress_image(image_path: Path, quality: int = 80) -> bytes:
    """Compress image and return JPEG bytes"""
    try:
        from PIL import Image
        import io

        img = Image.open(image_path)

        if img.mode in ('RGBA', 'LA', 'P'):
            rgb_img = Image.new('RGB', img.size, (255, 255, 255))
            rgb_img.paste(img, mask=img.split()[-1] if img.mode == 'RGBA' else None)
            img = rgb_img

        buffer = io.BytesIO()
        img.save(buffer, format='JPEG', quality=quality, optimize=True)
        buffer.seek(0)
        return buffer.getvalue()
    except Exception as e:
        logger.error(f"Image compression failed: {e}")
        return None


def blur_image(image_path: Path, blur_strength: int = 15) -> Path:
    """Blur screenshot and save next to original"""
    try:
        from PIL import Image, ImageFilter

        img = Image.open(image_path)
        blurred = img.filter(ImageFilter.GaussianBlur(radius=blur_strength))
        blurred_path = image_path.parent / f"{image_path.stem}_blurred.png"
        blurred.save(blurred_path)
        return blurred_path
    except Exception as e:
        logger.error(f"Image blur failed: {e}")
        return image_path


def save_json(data: dict, file_path: Path):
    """Save data as JSON file"""
    try:
        file_path.parent.mkdir(parents=True, exist_ok=True)
        with open(file_path, 'w') as f:
            json.dump(data, f, indent=2)
    except Exception as e:
        logger.error(f"Failed to save JSON: {e}")


def load_json(file_path: Path) -> dict:
    """Load data from JSON file"""
    try:
        if file_path.exists():
            with open(file_path, 'r') as f:
                return json.load(f)
    except Exception as e:
        logger.error(f"Failed to load JSON: {e}")
    return {}
