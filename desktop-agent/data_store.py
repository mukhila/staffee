import sqlite3
import json
import os
from typing import List, Dict, Any

DB_PATH = os.path.join(os.path.dirname(__file__), "queue.db")


def _conn() -> sqlite3.Connection:
    conn = sqlite3.connect(DB_PATH, check_same_thread=False)
    conn.row_factory = sqlite3.Row
    return conn


def init_db() -> None:
    with _conn() as conn:
        conn.execute("""
            CREATE TABLE IF NOT EXISTS activity_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payload TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
        """)
        conn.execute("""
            CREATE TABLE IF NOT EXISTS screenshot_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                path TEXT NOT NULL,
                payload TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
        """)
        conn.execute("""
            CREATE TABLE IF NOT EXISTS idle_queue (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payload TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
        """)


def enqueue_activity(payload: Dict[str, Any]) -> None:
    from utils import now_iso
    with _conn() as conn:
        conn.execute(
            "INSERT INTO activity_queue (payload, created_at) VALUES (?, ?)",
            (json.dumps(payload), now_iso()),
        )


def enqueue_screenshot(path: str, payload: Dict[str, Any]) -> None:
    from utils import now_iso
    with _conn() as conn:
        conn.execute(
            "INSERT INTO screenshot_queue (path, payload, created_at) VALUES (?, ?, ?)",
            (path, json.dumps(payload), now_iso()),
        )


def enqueue_idle(payload: Dict[str, Any]) -> None:
    from utils import now_iso
    with _conn() as conn:
        conn.execute(
            "INSERT INTO idle_queue (payload, created_at) VALUES (?, ?)",
            (json.dumps(payload), now_iso()),
        )


def flush_activity_queue() -> List[Dict[str, Any]]:
    with _conn() as conn:
        rows = conn.execute("SELECT * FROM activity_queue ORDER BY id").fetchall()
        if rows:
            conn.execute("DELETE FROM activity_queue WHERE id <= ?", (rows[-1]["id"],))
        return [{"id": r["id"], "payload": json.loads(r["payload"])} for r in rows]


def flush_screenshot_queue() -> List[Dict[str, Any]]:
    with _conn() as conn:
        rows = conn.execute("SELECT * FROM screenshot_queue ORDER BY id").fetchall()
        if rows:
            conn.execute("DELETE FROM screenshot_queue WHERE id <= ?", (rows[-1]["id"],))
        return [{"id": r["id"], "path": r["path"], "payload": json.loads(r["payload"])} for r in rows]


def flush_idle_queue() -> List[Dict[str, Any]]:
    with _conn() as conn:
        rows = conn.execute("SELECT * FROM idle_queue ORDER BY id").fetchall()
        if rows:
            conn.execute("DELETE FROM idle_queue WHERE id <= ?", (rows[-1]["id"],))
        return [{"id": r["id"], "payload": json.loads(r["payload"])} for r in rows]
