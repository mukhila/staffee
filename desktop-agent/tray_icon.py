"""
System tray icon and UI control.
Manages start/pause/exit functionality.
"""

import threading
from utils import logger


class TrayIcon:
    """System tray icon control"""

    def __init__(self, on_start=None, on_pause=None, on_exit=None):
        self.on_start = on_start
        self.on_pause = on_pause
        self.on_exit = on_exit
        self.is_running = False
        self.icon = None

    def setup(self) -> bool:
        """Setup system tray icon. Returns False if pystray unavailable."""
        try:
            import pystray
            from PIL import Image, ImageDraw

            icon_image = self._create_icon_image()

            menu_items = [
                pystray.MenuItem('Start Monitoring', self._on_start, default=True),
                pystray.MenuItem('Pause Monitoring', self._on_pause),
                pystray.Menu.SEPARATOR,
                pystray.MenuItem('Exit', self._on_exit),
            ]

            self.icon = pystray.Icon(
                'Employee Monitoring',
                icon_image,
                menu=pystray.Menu(*menu_items),
                title='Staffee Monitoring Agent',
            )

            logger.info("Tray icon setup complete")
            return True

        except Exception as e:
            logger.error(f"Tray icon setup failed: {e}")
            return False

    def run(self):
        """Run tray icon event loop (blocking)."""
        try:
            self.icon.run()
        except Exception as e:
            logger.error(f"Tray icon error: {e}")

    def run_async(self) -> threading.Thread:
        """Run tray icon in a daemon thread."""
        thread = threading.Thread(target=self.run, daemon=True)
        thread.start()
        return thread

    def stop(self):
        if self.icon:
            try:
                self.icon.stop()
            except Exception:
                pass

    def _create_icon_image(self):
        try:
            from PIL import Image, ImageDraw
            size = (64, 64)
            image = Image.new('RGB', size, color='white')
            draw = ImageDraw.Draw(image)
            margin = 8
            draw.ellipse(
                [(margin, margin), (size[0] - margin, size[1] - margin)],
                fill='#22c55e',
                outline='#15803d',
                width=2,
            )
            return image
        except Exception as e:
            logger.error(f"Icon creation failed: {e}")
            return None

    def _on_start(self, icon, item):
        logger.info("Start monitoring clicked")
        self.is_running = True
        if self.on_start:
            self.on_start()

    def _on_pause(self, icon, item):
        logger.info("Pause monitoring clicked")
        self.is_running = False
        if self.on_pause:
            self.on_pause()

    def _on_exit(self, icon, item):
        logger.info("Exit clicked")
        self.icon.stop()
        if self.on_exit:
            self.on_exit()
