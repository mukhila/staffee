import os
import mss
import mss.tools
from PIL import Image, ImageFilter
from datetime import datetime
from utils import logger

CAPTURE_DIR = os.path.join(os.path.dirname(__file__), "captures")
os.makedirs(CAPTURE_DIR, exist_ok=True)


def capture_screenshot(blur: bool = False, quality: int = 60) -> str:
    timestamp = datetime.utcnow().strftime("%Y%m%d_%H%M%S")
    path = os.path.join(CAPTURE_DIR, f"screen_{timestamp}.jpg")

    try:
        with mss.mss() as sct:
            monitor = sct.monitors[1]
            raw = sct.grab(monitor)
            img = Image.frombytes("RGB", raw.size, raw.bgra, "raw", "BGRX")

        if blur:
            img = img.filter(ImageFilter.GaussianBlur(radius=4))

        img.save(path, "JPEG", quality=quality, optimize=True)
        logger.debug("Screenshot saved: %s", path)
        return path
    except Exception as e:
        logger.warning("Screenshot failed: %s", e)
        return ""


def cleanup_screenshot(path: str) -> None:
    try:
        if path and os.path.exists(path):
            os.remove(path)
    except OSError:
        pass
