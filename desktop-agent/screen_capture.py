"""
Screenshot capture module using mss for high performance.
Includes compression and blur functionality.
"""

import mss
import io
from pathlib import Path
from datetime import datetime
from PIL import Image
from config import Config
from utils import logger, hash_screenshot, compress_image, blur_image


class ScreenCapture:
    """Handles screenshot capture and processing"""

    def __init__(self):
        self.sct = mss.mss()
        self.last_screenshot_hash = None
        self.screenshots_dir = Config.LOCAL_STORAGE_PATH / 'screenshots'
        self.screenshots_dir.mkdir(parents=True, exist_ok=True)

    def capture(self) -> dict:
        """
        Capture primary monitor.

        Returns dict with keys:
            success, image_data (bytes), hash, timestamp, dimensions, duplicate, file_path
        """
        try:
            monitor = self.sct.monitors[1]
            raw = self.sct.grab(monitor)
            img = Image.frombytes('RGB', raw.size, raw.rgb)

            timestamp = datetime.utcnow().strftime('%Y%m%d_%H%M%S')
            temp_path = self.screenshots_dir / f"screenshot_{timestamp}.png"
            img.save(temp_path)

            with open(temp_path, 'rb') as f:
                image_data = f.read()

            image_hash = hash_screenshot(image_data)
            is_duplicate = image_hash == self.last_screenshot_hash
            self.last_screenshot_hash = image_hash

            if len(image_data) > Config.MAX_SCREENSHOT_SIZE:
                logger.warning("Screenshot exceeds max size, compressing")
                image_data = compress_image(temp_path, quality=70)
                if not image_data:
                    return {'success': False, 'error': 'Compression failed'}

            if Config.BLUR_SCREENSHOTS:
                blurred_path = blur_image(temp_path)
                with open(blurred_path, 'rb') as f:
                    image_data = f.read()

            return {
                'success': True,
                'image_data': image_data,
                'hash': image_hash,
                'timestamp': datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ'),
                'dimensions': (img.width, img.height),
                'duplicate': is_duplicate,
                'file_path': str(temp_path),
            }

        except Exception as e:
            logger.error(f"Screenshot capture failed: {e}")
            return {'success': False, 'error': str(e)}

    def cleanup_old_screenshots(self, days: int = 7):
        """Remove local screenshot files older than `days` days."""
        try:
            from datetime import timedelta, datetime as dt
            cutoff = dt.utcnow() - timedelta(days=days)
            for file in self.screenshots_dir.glob('*.png'):
                if dt.fromtimestamp(file.stat().st_mtime) < cutoff:
                    file.unlink()
                    logger.debug(f"Deleted old screenshot: {file.name}")
        except Exception as e:
            logger.error(f"Screenshot cleanup failed: {e}")
