import threading
import pystray
from PIL import Image, ImageDraw
from utils import logger


def _make_icon(color: str) -> Image.Image:
    img = Image.new("RGB", (64, 64), color=color)
    draw = ImageDraw.Draw(img)
    draw.ellipse((8, 8, 56, 56), fill="white")
    return img


class TrayIcon:
    def __init__(self, on_pause, on_resume, on_exit):
        self._on_pause = on_pause
        self._on_resume = on_resume
        self._on_exit = on_exit
        self._paused = False
        self._icon: pystray.Icon | None = None
        self._thread: threading.Thread | None = None

    def _build_menu(self) -> pystray.Menu:
        if self._paused:
            toggle_item = pystray.MenuItem("Resume Monitoring", self._resume)
        else:
            toggle_item = pystray.MenuItem("Pause Monitoring", self._pause)
        return pystray.Menu(toggle_item, pystray.MenuItem("Exit", self._exit))

    def _pause(self, icon, item) -> None:
        self._paused = True
        self._on_pause()
        icon.icon = _make_icon("#f59e0b")
        icon.menu = self._build_menu()
        logger.info("Monitoring paused via tray")

    def _resume(self, icon, item) -> None:
        self._paused = False
        self._on_resume()
        icon.icon = _make_icon("#22c55e")
        icon.menu = self._build_menu()
        logger.info("Monitoring resumed via tray")

    def _exit(self, icon, item) -> None:
        logger.info("Exit requested via tray")
        icon.stop()
        self._on_exit()

    def start(self) -> None:
        self._icon = pystray.Icon(
            "Staffee Agent",
            _make_icon("#22c55e"),
            "Staffee Monitoring",
            self._build_menu(),
        )
        self._thread = threading.Thread(target=self._icon.run, daemon=True)
        self._thread.start()

    def stop(self) -> None:
        if self._icon:
            self._icon.stop()
