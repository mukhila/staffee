import signal
import sys
import time
import threading
from typing import Optional

import config
import api_client
import data_store
import screen_capture
import window_tracker
from idle_detector import IdleDetector
from tray_icon import TrayIcon
from utils import logger, now_iso, is_within_working_hours


HEARTBEAT_INTERVAL = 30
ACTIVITY_FLUSH_INTERVAL = 60


class MonitoringAgent:
    def __init__(self):
        self._session_id: Optional[int] = None
        self._remote_config: dict = {}
        self._paused = False
        self._running = False

        self._keyboard_events = 0
        self._mouse_events = 0
        self._mouse_distance_px = 0
        self._lock = threading.Lock()

        self._idle_detector: Optional[IdleDetector] = None
        self._tray = TrayIcon(
            on_pause=self._pause,
            on_resume=self._resume,
            on_exit=self._stop,
        )

    # ── Lifecycle ─────────────────────────────────────────────────────────────

    def start(self) -> None:
        data_store.init_db()
        logger.info("Starting Staffee monitoring agent…")

        self._remote_config = api_client.get_config() or {}
        threshold = self._remote_config.get("idle_threshold_seconds", config.IDLE_THRESHOLD)
        self._idle_detector = IdleDetector(threshold)

        result = api_client.session_start()
        if not result or "session_id" not in result:
            logger.error("Could not start session — check API_TOKEN and BACKEND_URL")
            sys.exit(1)

        self._session_id = result["session_id"]
        logger.info("Session started: id=%d", self._session_id)

        self._running = True
        self._tray.start()

        self._run_loops()

    def _stop(self) -> None:
        self._running = False
        if self._session_id:
            api_client.session_end(self._session_id)
        self._tray.stop()
        logger.info("Agent stopped")
        sys.exit(0)

    def _pause(self) -> None:
        self._paused = True

    def _resume(self) -> None:
        self._paused = False

    # ── Main loops ────────────────────────────────────────────────────────────

    def _run_loops(self) -> None:
        threads = [
            threading.Thread(target=self._heartbeat_loop, daemon=True),
            threading.Thread(target=self._screenshot_loop, daemon=True),
            threading.Thread(target=self._activity_flush_loop, daemon=True),
            threading.Thread(target=self._idle_loop, daemon=True),
            threading.Thread(target=self._upload_loop, daemon=True),
        ]
        for t in threads:
            t.start()

        try:
            while self._running:
                time.sleep(1)
        except KeyboardInterrupt:
            self._stop()

    def _heartbeat_loop(self) -> None:
        while self._running:
            time.sleep(HEARTBEAT_INTERVAL)
            if self._paused or not self._session_id:
                continue
            title = window_tracker.get_active_window_title()
            api_client.heartbeat(self._session_id, title)

    def _screenshot_loop(self) -> None:
        interval = self._remote_config.get(
            "screenshot_interval_seconds", config.SCREENSHOT_INTERVAL
        )
        while self._running:
            time.sleep(interval)
            if self._paused or not self._session_id:
                continue
            if config.WORKING_HOURS_ONLY and not is_within_working_hours(config.WORK_START, config.WORK_END):
                continue
            if not self._remote_config.get("screenshot_enabled", True):
                continue

            title = window_tracker.get_active_window_title()
            path = screen_capture.capture_screenshot()
            if path:
                data_store.enqueue_screenshot(path, {
                    "session_id": self._session_id,
                    "captured_at": now_iso(),
                    "active_window_title": title,
                })

    def _activity_flush_loop(self) -> None:
        while self._running:
            time.sleep(ACTIVITY_FLUSH_INTERVAL)
            if not self._session_id:
                continue

            with self._lock:
                kb = self._keyboard_events
                ms = self._mouse_events
                dist = self._mouse_distance_px
                self._keyboard_events = 0
                self._mouse_events = 0
                self._mouse_distance_px = 0

            is_active = (kb + ms) > 0 and not self._idle_detector.is_idle
            data_store.enqueue_activity({
                "session_id": self._session_id,
                "recorded_at": now_iso(),
                "duration_seconds": ACTIVITY_FLUSH_INTERVAL,
                "keyboard_events": kb,
                "mouse_events": ms,
                "mouse_distance_px": dist,
                "is_active": is_active,
                "active_app_name": window_tracker.get_active_process_name(),
                "active_window_title": window_tracker.get_active_window_title(),
            })

    def _idle_loop(self) -> None:
        while self._running:
            time.sleep(5)
            if self._paused or not self._idle_detector or not self._session_id:
                continue
            completed = self._idle_detector.tick()
            if completed:
                data_store.enqueue_idle({
                    "session_id": self._session_id,
                    **completed,
                })

    def _upload_loop(self) -> None:
        while self._running:
            time.sleep(config.SYNC_INTERVAL)
            self._flush_activity()
            self._flush_screenshots()
            self._flush_idle()

    # ── Flush helpers ─────────────────────────────────────────────────────────

    def _flush_activity(self) -> None:
        for item in data_store.flush_activity_queue():
            p = item["payload"]
            api_client.send_activity(
                p["session_id"], p["recorded_at"],
                p["keyboard_events"], p["mouse_events"],
                p["mouse_distance_px"], p["is_active"],
                duration_seconds=p.get("duration_seconds", 60),
                active_app_name=p.get("active_app_name", ""),
                active_window_title=p.get("active_window_title", ""),
            )

    def _flush_screenshots(self) -> None:
        for item in data_store.flush_screenshot_queue():
            p = item["payload"]
            result = api_client.send_screenshot(
                p["session_id"], p["captured_at"], item["path"], p.get("active_window_title", "")
            )
            if result:
                screen_capture.cleanup_screenshot(item["path"])
            else:
                data_store.enqueue_screenshot(item["path"], p)

    def _flush_idle(self) -> None:
        for item in data_store.flush_idle_queue():
            p = item["payload"]
            api_client.send_idle(
                p["session_id"], p["idle_start"], p["idle_end"], p["duration_seconds"]
            )


def _handle_signal(sig, frame) -> None:
    logger.info("Signal %d received — shutting down", sig)
    sys.exit(0)


if __name__ == "__main__":
    signal.signal(signal.SIGINT, _handle_signal)
    signal.signal(signal.SIGTERM, _handle_signal)
    MonitoringAgent().start()
