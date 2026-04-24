"""
Local data storage and queue management.
Handles offline storage and sync with retry logic.
"""

import sqlite3
from pathlib import Path
from datetime import datetime, timedelta
from config import Config
from utils import logger


class DataStore:
    """Local data storage with SQLite"""

    def __init__(self):
        self.db_path = Config.LOCAL_STORAGE_PATH / 'activity.db'
        self.init_database()

    def _conn(self) -> sqlite3.Connection:
        conn = sqlite3.connect(self.db_path, check_same_thread=False)
        conn.row_factory = sqlite3.Row
        return conn

    def init_database(self):
        with self._conn() as conn:
            conn.execute('''
                CREATE TABLE IF NOT EXISTS activity_queue (
                    id              INTEGER PRIMARY KEY AUTOINCREMENT,
                    session_id      INTEGER NOT NULL,
                    recorded_at     TEXT    NOT NULL,
                    duration_seconds INTEGER DEFAULT 60,
                    keyboard_events INTEGER DEFAULT 0,
                    mouse_events    INTEGER DEFAULT 0,
                    mouse_distance_px INTEGER DEFAULT 0,
                    is_active       BOOLEAN DEFAULT 1,
                    active_app_name TEXT,
                    active_window_title TEXT,
                    uploaded        BOOLEAN DEFAULT 0,
                    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ''')
            conn.execute('''
                CREATE TABLE IF NOT EXISTS screenshot_queue (
                    id              INTEGER PRIMARY KEY AUTOINCREMENT,
                    session_id      INTEGER NOT NULL,
                    file_path       TEXT    NOT NULL,
                    captured_at     TEXT    NOT NULL,
                    active_window_title TEXT,
                    uploaded        BOOLEAN DEFAULT 0,
                    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ''')
            conn.execute('''
                CREATE TABLE IF NOT EXISTS idle_queue (
                    id              INTEGER PRIMARY KEY AUTOINCREMENT,
                    session_id      INTEGER NOT NULL,
                    idle_start      TEXT    NOT NULL,
                    idle_end        TEXT    NOT NULL,
                    duration_seconds INTEGER NOT NULL,
                    uploaded        BOOLEAN DEFAULT 0,
                    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ''')
        logger.info("Database initialized")

    # ── Activity ─────────────────────────────────────────────────────────────

    def add_activity(self, data: dict) -> bool:
        try:
            with self._conn() as conn:
                conn.execute('''
                    INSERT INTO activity_queue
                    (session_id, recorded_at, duration_seconds, keyboard_events,
                     mouse_events, mouse_distance_px, is_active, active_app_name, active_window_title)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ''', (
                    data['session_id'],
                    data.get('recorded_at', datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ')),
                    data.get('duration_seconds', 60),
                    data.get('keyboard_events', 0),
                    data.get('mouse_events', 0),
                    data.get('mouse_distance_px', 0),
                    int(data.get('is_active', True)),
                    data.get('active_app_name'),
                    data.get('active_window_title'),
                ))
            return True
        except Exception as e:
            logger.error(f"Failed to add activity: {e}")
            return False

    def get_pending_activities(self, limit: int = 100) -> list:
        try:
            with self._conn() as conn:
                rows = conn.execute(
                    'SELECT * FROM activity_queue WHERE uploaded = 0 LIMIT ?', (limit,)
                ).fetchall()
            return [dict(r) for r in rows]
        except Exception as e:
            logger.error(f"Failed to get pending activities: {e}")
            return []

    def mark_activity_uploaded(self, activity_id: int) -> bool:
        try:
            with self._conn() as conn:
                conn.execute('UPDATE activity_queue SET uploaded = 1 WHERE id = ?', (activity_id,))
            return True
        except Exception as e:
            logger.error(f"Failed to mark activity uploaded: {e}")
            return False

    # ── Screenshots ───────────────────────────────────────────────────────────

    def add_screenshot(self, data: dict) -> bool:
        try:
            with self._conn() as conn:
                conn.execute('''
                    INSERT INTO screenshot_queue
                    (session_id, file_path, captured_at, active_window_title)
                    VALUES (?, ?, ?, ?)
                ''', (
                    data['session_id'],
                    data['file_path'],
                    data.get('captured_at', datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ')),
                    data.get('active_window_title'),
                ))
            return True
        except Exception as e:
            logger.error(f"Failed to add screenshot: {e}")
            return False

    def get_pending_screenshots(self, limit: int = 50) -> list:
        try:
            with self._conn() as conn:
                rows = conn.execute(
                    'SELECT * FROM screenshot_queue WHERE uploaded = 0 LIMIT ?', (limit,)
                ).fetchall()
            return [dict(r) for r in rows]
        except Exception as e:
            logger.error(f"Failed to get pending screenshots: {e}")
            return []

    def mark_screenshot_uploaded(self, screenshot_id: int) -> bool:
        try:
            with self._conn() as conn:
                conn.execute('UPDATE screenshot_queue SET uploaded = 1 WHERE id = ?', (screenshot_id,))
            return True
        except Exception as e:
            logger.error(f"Failed to mark screenshot uploaded: {e}")
            return False

    # ── Idle periods ──────────────────────────────────────────────────────────

    def add_idle(self, data: dict) -> bool:
        try:
            with self._conn() as conn:
                conn.execute('''
                    INSERT INTO idle_queue (session_id, idle_start, idle_end, duration_seconds)
                    VALUES (?, ?, ?, ?)
                ''', (
                    data['session_id'],
                    data['idle_start'],
                    data['idle_end'],
                    data['duration_seconds'],
                ))
            return True
        except Exception as e:
            logger.error(f"Failed to add idle: {e}")
            return False

    def get_pending_idles(self, limit: int = 100) -> list:
        try:
            with self._conn() as conn:
                rows = conn.execute(
                    'SELECT * FROM idle_queue WHERE uploaded = 0 LIMIT ?', (limit,)
                ).fetchall()
            return [dict(r) for r in rows]
        except Exception as e:
            logger.error(f"Failed to get pending idles: {e}")
            return []

    def mark_idle_uploaded(self, idle_id: int) -> bool:
        try:
            with self._conn() as conn:
                conn.execute('UPDATE idle_queue SET uploaded = 1 WHERE id = ?', (idle_id,))
            return True
        except Exception as e:
            logger.error(f"Failed to mark idle uploaded: {e}")
            return False

    # ── Cleanup ───────────────────────────────────────────────────────────────

    def cleanup_old_data(self, days: int = 7):
        """Remove uploaded records older than `days` days."""
        try:
            cutoff = (datetime.utcnow() - timedelta(days=days)).isoformat()
            with self._conn() as conn:
                conn.execute(
                    "DELETE FROM activity_queue WHERE uploaded = 1 AND created_at < ?", (cutoff,)
                )
                conn.execute(
                    "DELETE FROM screenshot_queue WHERE uploaded = 1 AND created_at < ?", (cutoff,)
                )
                conn.execute(
                    "DELETE FROM idle_queue WHERE uploaded = 1 AND created_at < ?", (cutoff,)
                )
            logger.info("Old data cleaned up")
        except Exception as e:
            logger.error(f"Cleanup failed: {e}")
