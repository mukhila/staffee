"""
Main entry point for the Staffee Employee Monitoring Agent.
Orchestrates all components and runs the monitoring loop.
"""

import os
import platform
import signal
import sys
import threading
import time
from datetime import datetime

from config import Config
from utils import logger, setup_logging
from consent_manager import ConsentManager
from screen_capture import ScreenCapture
from idle_detector import IdleDetector
from window_tracker import WindowTracker
from api_client import APIClient
from data_store import DataStore
from tray_icon import TrayIcon

HEARTBEAT_INTERVAL = 30   # seconds between heartbeat calls
ACTIVITY_WINDOW    = 60   # seconds per activity record
IDLE_TICK          = 5    # seconds between idle checks


class MonitoringAgent:
    """Main monitoring agent orchestrator"""

    def __init__(self):
        Config.ensure_directories()
        logger.info("=" * 60)
        logger.info("Staffee Monitoring Agent Starting")
        logger.info("=" * 60)

        self.consent_manager  = ConsentManager()
        self.screen_capture   = ScreenCapture()
        self.idle_detector    = IdleDetector(idle_threshold=Config.IDLE_THRESHOLD)
        self.window_tracker   = WindowTracker()
        self.api_client       = APIClient()
        self.data_store       = DataStore()
        self.tray_icon        = TrayIcon(
            on_start=self.start_monitoring,
            on_pause=self.pause_monitoring,
            on_exit=self.stop_monitoring,
        )

        self._session_id: int | None = None
        self._is_running    = False
        self._is_monitoring = False

        # Per-window activity counters (incremented by pynput hooks if available)
        self._kb_events   = 0
        self._ms_events   = 0
        self._ms_distance = 0
        self._counter_lock = threading.Lock()

    # ── Initialization ────────────────────────────────────────────────────────

    def initialize(self) -> bool:
        try:
            Config.validate()
        except ValueError as e:
            logger.error(f"Config error: {e}")
            return False

        if not self.consent_manager.request_consent_if_needed():
            logger.error("Monitoring aborted — no consent")
            return False

        if not self.tray_icon.setup():
            logger.warning("Tray icon unavailable, continuing headless")

        # Fetch remote config and apply overrides
        remote = self.api_client.get_config()
        if remote:
            self._apply_remote_config(remote)

        # Start session
        result = self.api_client.session_start(
            hostname=platform.node(),
            os_info=f"{platform.system()} {platform.release()}",
            agent_version='1.0',
        )
        if not result:
            logger.error("Could not start monitoring session — check API_TOKEN / BACKEND_URL")
            return False

        self._session_id = result['session_id']
        # Remote may return updated config alongside session
        if 'config' in result:
            self._apply_remote_config(result['config'])

        logger.info(f"Session started: id={self._session_id}")
        return True

    def _apply_remote_config(self, cfg: dict):
        if 'screenshot_interval_seconds' in cfg:
            Config.SCREENSHOT_INTERVAL = int(cfg['screenshot_interval_seconds'])
        if 'idle_threshold_seconds' in cfg:
            Config.IDLE_THRESHOLD = int(cfg['idle_threshold_seconds'])
            self.idle_detector.idle_threshold = Config.IDLE_THRESHOLD
        if not cfg.get('screenshot_enabled', True):
            Config.SCREENSHOT_INTERVAL = 0  # 0 → skip screenshots

    # ── Public controls ───────────────────────────────────────────────────────

    def start_monitoring(self):
        self._is_monitoring = True
        logger.info("Monitoring started")

    def pause_monitoring(self):
        self._is_monitoring = False
        logger.info("Monitoring paused")

    def stop_monitoring(self):
        logger.info("Stopping agent…")
        self._is_running = False
        self._is_monitoring = False
        self._sync_pending_data()
        if self._session_id:
            self.api_client.session_end(self._session_id)
        self.tray_icon.stop()
        logger.info("Agent stopped")
        sys.exit(0)

    def handle_signal(self, signum, frame):
        logger.info(f"Signal {signum} received")
        self.stop_monitoring()

    # ── Main loop ─────────────────────────────────────────────────────────────

    def run(self):
        if not self.initialize():
            return

        self._is_running    = True
        self._is_monitoring = True

        self.tray_icon.run_async()
        self._start_input_hooks()

        logger.info("Monitoring loop running…")

        threads = [
            threading.Thread(target=self._heartbeat_loop,   daemon=True),
            threading.Thread(target=self._screenshot_loop,  daemon=True),
            threading.Thread(target=self._activity_loop,    daemon=True),
            threading.Thread(target=self._idle_loop,        daemon=True),
            threading.Thread(target=self._upload_loop,      daemon=True),
        ]
        for t in threads:
            t.start()

        try:
            while self._is_running:
                time.sleep(1)
        except KeyboardInterrupt:
            self.stop_monitoring()

    # ── Background threads ────────────────────────────────────────────────────

    def _heartbeat_loop(self):
        while self._is_running:
            time.sleep(HEARTBEAT_INTERVAL)
            if not self._session_id:
                continue
            title = self.window_tracker.get_active_window().get('title', '')
            self.api_client.heartbeat(self._session_id, title)

    def _screenshot_loop(self):
        while self._is_running:
            interval = Config.SCREENSHOT_INTERVAL or 300
            time.sleep(interval)
            if not self._is_monitoring or not self._session_id:
                continue
            if Config.SCREENSHOT_INTERVAL == 0:
                continue

            window = self.window_tracker.get_active_window()
            result = self.screen_capture.capture()
            if result.get('success') and not result.get('duplicate'):
                self.data_store.add_screenshot({
                    'session_id':          self._session_id,
                    'file_path':           result['file_path'],
                    'captured_at':         result['timestamp'],
                    'active_window_title': window.get('title', ''),
                })

    def _activity_loop(self):
        while self._is_running:
            time.sleep(ACTIVITY_WINDOW)
            if not self._session_id:
                continue

            with self._counter_lock:
                kb   = self._kb_events;   self._kb_events   = 0
                ms   = self._ms_events;   self._ms_events   = 0
                dist = self._ms_distance; self._ms_distance = 0

            window    = self.window_tracker.get_active_window()
            is_active = (kb + ms) > 0 and not self.idle_detector.currently_idle

            self.data_store.add_activity({
                'session_id':          self._session_id,
                'recorded_at':         datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ'),
                'duration_seconds':    ACTIVITY_WINDOW,
                'keyboard_events':     kb,
                'mouse_events':        ms,
                'mouse_distance_px':   dist,
                'is_active':           is_active,
                'active_app_name':     window.get('app_name', ''),
                'active_window_title': window.get('title', ''),
            })

    def _idle_loop(self):
        while self._is_running:
            time.sleep(IDLE_TICK)
            if not self._session_id:
                continue
            completed = self.idle_detector.tick()
            if completed:
                self.data_store.add_idle({
                    'session_id': self._session_id,
                    **completed,
                })

    def _upload_loop(self):
        while self._is_running:
            time.sleep(Config.SYNC_INTERVAL)
            self._sync_pending_data()
            # Hourly cleanup
            if int(time.time()) % 3600 < Config.SYNC_INTERVAL:
                self.data_store.cleanup_old_data()
                self.screen_capture.cleanup_old_screenshots()

    # ── Sync ──────────────────────────────────────────────────────────────────

    def _sync_pending_data(self):
        self._upload_activities()
        self._upload_screenshots()
        self._upload_idles()

    def _upload_activities(self):
        pending = self.data_store.get_pending_activities(limit=100)
        for item in pending:
            if self.api_client.send_activity(item):
                self.data_store.mark_activity_uploaded(item['id'])
        if pending:
            logger.info(f"Synced {len(pending)} activity records")

    def _upload_screenshots(self):
        pending = self.data_store.get_pending_screenshots(limit=50)
        for item in pending:
            try:
                with open(item['file_path'], 'rb') as f:
                    image_data = f.read()
            except OSError:
                self.data_store.mark_screenshot_uploaded(item['id'])  # file gone, discard
                continue

            if self.api_client.send_screenshot(item, image_data):
                self.data_store.mark_screenshot_uploaded(item['id'])
                try:
                    os.remove(item['file_path'])
                except OSError:
                    pass
        if pending:
            logger.info(f"Synced {len(pending)} screenshots")

    def _upload_idles(self):
        pending = self.data_store.get_pending_idles(limit=100)
        for item in pending:
            if self.api_client.send_idle(item):
                self.data_store.mark_idle_uploaded(item['id'])
        if pending:
            logger.info(f"Synced {len(pending)} idle periods")

    # ── Input hooks ───────────────────────────────────────────────────────────

    def _start_input_hooks(self):
        """Wire pynput listeners to increment event counters."""
        try:
            from pynput import keyboard, mouse

            def on_key_press(key):
                with self._counter_lock:
                    self._kb_events += 1

            def on_move(x, y):
                with self._counter_lock:
                    self._ms_events += 1

            def on_click(x, y, button, pressed):
                if pressed:
                    with self._counter_lock:
                        self._ms_events += 1

            keyboard.Listener(on_press=on_key_press, daemon=True).start()
            mouse.Listener(on_move=on_move, on_click=on_click, daemon=True).start()
            logger.info("Input hooks active")
        except Exception as e:
            logger.warning(f"Input hooks unavailable: {e}")


def main():
    agent = MonitoringAgent()
    signal.signal(signal.SIGINT,  agent.handle_signal)
    signal.signal(signal.SIGTERM, agent.handle_signal)
    agent.run()


if __name__ == '__main__':
    main()
