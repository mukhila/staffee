import ctypes
import ctypes.wintypes
import time
from typing import Optional
from utils import now_iso, logger


class LASTINPUTINFO(ctypes.Structure):
    _fields_ = [("cbSize", ctypes.wintypes.UINT), ("dwTime", ctypes.wintypes.DWORD)]


def get_idle_seconds() -> float:
    lii = LASTINPUTINFO()
    lii.cbSize = ctypes.sizeof(LASTINPUTINFO)
    if ctypes.windll.user32.GetLastInputInfo(ctypes.byref(lii)):
        millis = ctypes.windll.kernel32.GetTickCount() - lii.dwTime
        return max(0, millis / 1000.0)
    return 0.0


class IdleDetector:
    def __init__(self, threshold_seconds: int):
        self.threshold = threshold_seconds
        self._idle_since: Optional[float] = None
        self._idle_start_iso: Optional[str] = None

    def tick(self) -> Optional[dict]:
        idle_secs = get_idle_seconds()
        now = time.time()

        if idle_secs >= self.threshold:
            if self._idle_since is None:
                self._idle_since = now - idle_secs
                self._idle_start_iso = now_iso()
                logger.debug("Idle started (idle for %.0fs)", idle_secs)
            return None

        if self._idle_since is not None:
            duration = int(now - self._idle_since)
            idle_end_iso = now_iso()
            result = {
                "idle_start": self._idle_start_iso,
                "idle_end": idle_end_iso,
                "duration_seconds": duration,
            }
            logger.info("Idle ended after %ds", duration)
            self._idle_since = None
            self._idle_start_iso = None
            return result

        return None

    @property
    def is_idle(self) -> bool:
        return self._idle_since is not None
