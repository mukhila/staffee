import requests
from typing import Any, Dict, Optional
import config
from utils import logger

_BASE = f"{config.BACKEND_URL}/api/agent"
_HEADERS = {"Authorization": f"Bearer {config.API_TOKEN}", "Accept": "application/json"}
_TIMEOUT = 15


def _post(path: str, data: Dict[str, Any] = None, files: Dict = None) -> Optional[Dict]:
    url = f"{_BASE}/{path.lstrip('/')}"
    try:
        if files:
            resp = requests.post(url, headers=_HEADERS, data=data, files=files, timeout=_TIMEOUT)
        else:
            resp = requests.post(url, headers=_HEADERS, json=data or {}, timeout=_TIMEOUT)
        resp.raise_for_status()
        return resp.json()
    except requests.RequestException as e:
        logger.warning("POST %s failed: %s", path, e)
        return None


def _get(path: str) -> Optional[Dict]:
    url = f"{_BASE}/{path.lstrip('/')}"
    try:
        resp = requests.get(url, headers=_HEADERS, timeout=_TIMEOUT)
        resp.raise_for_status()
        return resp.json()
    except requests.RequestException as e:
        logger.warning("GET %s failed: %s", path, e)
        return None


def session_start() -> Optional[Dict]:
    return _post("session/start")


def session_end(session_id: int) -> None:
    _post("session/end", {"session_id": session_id})


def heartbeat(session_id: int, active_window_title: str = "") -> None:
    _post("heartbeat", {"session_id": session_id, "active_window_title": active_window_title})


def get_config() -> Optional[Dict]:
    return _get("config")


def send_screenshot(session_id: int, captured_at: str, image_path: str, active_window_title: str = "") -> Optional[Dict]:
    try:
        with open(image_path, "rb") as f:
            return _post(
                "screenshot",
                data={"session_id": session_id, "captured_at": captured_at, "active_window_title": active_window_title},
                files={"file": (image_path.split("/")[-1], f, "image/jpeg")},
            )
    except OSError as e:
        logger.warning("Could not open screenshot %s: %s", image_path, e)
        return None


def send_activity(
    session_id: int,
    recorded_at: str,
    keyboard_events: int,
    mouse_events: int,
    mouse_distance_px: int,
    is_active: bool,
    duration_seconds: int = 60,
    active_app_name: str = "",
    active_window_title: str = "",
) -> Optional[Dict]:
    return _post("activity", {
        "session_id": session_id,
        "recorded_at": recorded_at,
        "duration_seconds": duration_seconds,
        "keyboard_events": keyboard_events,
        "mouse_events": mouse_events,
        "mouse_distance_px": mouse_distance_px,
        "is_active": is_active,
        "active_app_name": active_app_name or None,
        "active_window_title": active_window_title or None,
    })


def send_idle(session_id: int, idle_start: str, idle_end: str, duration_seconds: int) -> Optional[Dict]:
    return _post("idle", {
        "session_id": session_id,
        "idle_start": idle_start,
        "idle_end": idle_end,
        "duration_seconds": duration_seconds,
    })
