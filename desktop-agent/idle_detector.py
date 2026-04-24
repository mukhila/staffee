"""
Idle time detection using Windows API.
Detects when user hasn't interacted with keyboard/mouse.
"""

import ctypes
import time
from datetime import datetime
from utils import logger


class IdleDetector:
    """Detects idle time on Windows"""

    class LASTINPUTINFO(ctypes.Structure):
        _fields_ = [("cbSize", ctypes.c_uint), ("dwTime", ctypes.c_ulong)]

    def __init__(self, idle_threshold: int = 300):
        self.idle_threshold = idle_threshold
        self._idle_since: float | None = None
        self._idle_start_iso: str | None = None

    def get_idle_seconds(self) -> int:
        """Return seconds since last user input (Windows only)."""
        try:
            lii = self.LASTINPUTINFO()
            lii.cbSize = ctypes.sizeof(self.LASTINPUTINFO)
            if ctypes.windll.user32.GetLastInputInfo(ctypes.byref(lii)):
                millis = ctypes.windll.kernel32.GetTickCount() - lii.dwTime
                return max(0, millis // 1000)
            return 0
        except Exception as e:
            logger.error(f"Idle time detection failed: {e}")
            return 0

    # Backwards-compatible alias used by main.py
    def get_idle_time(self) -> int:
        return self.get_idle_seconds()

    def is_idle(self) -> bool:
        return self.get_idle_seconds() >= self.idle_threshold

    def get_active_time(self, check_interval: int = 60) -> int:
        return max(0, check_interval - min(self.get_idle_seconds(), check_interval))

    def tick(self) -> dict | None:
        """
        Call on a short loop (every few seconds).
        Returns a completed idle-period dict when idle ends, else None.
        Dict keys: idle_start (ISO str), idle_end (ISO str), duration_seconds (int).
        """
        idle_secs = self.get_idle_seconds()
        now = time.time()

        if idle_secs >= self.idle_threshold:
            if self._idle_since is None:
                self._idle_since = now - idle_secs
                self._idle_start_iso = datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ')
                logger.debug(f"Idle started (idle for {idle_secs}s)")
            return None

        if self._idle_since is not None:
            duration = int(now - self._idle_since)
            result = {
                'idle_start': self._idle_start_iso,
                'idle_end': datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ'),
                'duration_seconds': duration,
            }
            logger.info(f"Idle ended after {duration}s")
            self._idle_since = None
            self._idle_start_iso = None
            return result

        return None

    @property
    def currently_idle(self) -> bool:
        return self._idle_since is not None
