"""
API client for backend communication.
All endpoints target /api/agent/* — authentication is via Bearer token only.
"""

import requests
import json
import time
from datetime import datetime
from pathlib import Path
from config import Config
from utils import logger


class APIClient:
    """Backend API communication"""

    def __init__(self):
        self.base_url = Config.BACKEND_URL.rstrip('/')
        self.token = Config.API_TOKEN
        self.timeout = 10
        self.max_retries = 3
        self.retry_delay = 2

    def _headers(self, content_type: str = 'application/json') -> dict:
        h = {
            'Authorization': f'Bearer {self.token}',
            'Accept': 'application/json',
        }
        if content_type:
            h['Content-Type'] = content_type
        return h

    def _request(self, method: str, path: str, data: dict = None, files: dict = None) -> tuple:
        """
        Make HTTP request with retry logic.
        Returns (success: bool, response_dict or None).
        """
        url = f"{self.base_url}/api/agent/{path.lstrip('/')}"

        for attempt in range(self.max_retries):
            try:
                if method == 'GET':
                    resp = requests.get(url, headers=self._headers(), params=data, timeout=self.timeout)
                elif files:
                    resp = requests.post(
                        url,
                        headers=self._headers(content_type=None),
                        data=data or {},
                        files=files,
                        timeout=self.timeout,
                    )
                else:
                    resp = requests.post(url, headers=self._headers(), json=data or {}, timeout=self.timeout)

                if resp.status_code in (200, 201, 202):
                    return True, resp.json() if resp.text else {}
                if resp.status_code == 401:
                    logger.error("Authentication failed — check API_TOKEN")
                    return False, None
                if resp.status_code >= 500:
                    logger.warning(f"Server error {resp.status_code}, retrying…")
                    time.sleep(self.retry_delay)
                    continue

                logger.error(f"Request failed {resp.status_code}: {resp.text[:200]}")
                return False, None

            except requests.Timeout:
                logger.warning(f"Timeout (attempt {attempt + 1}/{self.max_retries})")
            except requests.ConnectionError:
                logger.warning(f"Connection error (attempt {attempt + 1}/{self.max_retries})")
            except Exception as e:
                logger.error(f"Request error: {e}")
                return False, None

            if attempt < self.max_retries - 1:
                time.sleep(self.retry_delay)

        return False, None

    # ── Session lifecycle ─────────────────────────────────────────────────────

    def session_start(self, hostname: str = '', os_info: str = '', agent_version: str = '1.0') -> dict | None:
        """POST /api/agent/session/start — returns {'session_id': int, 'config': dict} or None."""
        success, resp = self._request('POST', 'session/start', {
            'hostname': hostname,
            'os_info': os_info,
            'agent_version': agent_version,
        })
        if success and resp and 'session_id' in resp:
            return resp
        logger.error("Failed to start session")
        return None

    def session_end(self, session_id: int) -> bool:
        success, _ = self._request('POST', 'session/end', {'session_id': session_id})
        return success

    def heartbeat(self, session_id: int, active_window_title: str = '') -> bool:
        success, _ = self._request('POST', 'heartbeat', {
            'session_id': session_id,
            'active_window_title': active_window_title,
        })
        return success

    # ── Config ────────────────────────────────────────────────────────────────

    def get_config(self) -> dict:
        """GET /api/agent/config — returns remote monitoring settings or {}."""
        success, resp = self._request('GET', 'config')
        return resp if success and resp else {}

    # ── Activity ──────────────────────────────────────────────────────────────

    def send_activity(self, activity_data: dict) -> bool:
        """
        POST /api/agent/activity
        Required keys: session_id, recorded_at, keyboard_events, mouse_events,
                       mouse_distance_px, is_active
        Optional: duration_seconds, active_app_name, active_window_title
        """
        payload = {
            'session_id':          activity_data['session_id'],
            'recorded_at':         activity_data.get('recorded_at', datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ')),
            'duration_seconds':    activity_data.get('duration_seconds', 60),
            'keyboard_events':     activity_data.get('keyboard_events', 0),
            'mouse_events':        activity_data.get('mouse_events', 0),
            'mouse_distance_px':   activity_data.get('mouse_distance_px', 0),
            'is_active':           activity_data.get('is_active', True),
            'active_app_name':     activity_data.get('active_app_name') or None,
            'active_window_title': activity_data.get('active_window_title') or None,
        }
        success, _ = self._request('POST', 'activity', payload)
        if not success:
            logger.warning("Failed to send activity")
        return success

    # ── Screenshot ────────────────────────────────────────────────────────────

    def send_screenshot(self, screenshot_data: dict, image_data: bytes) -> bool:
        """
        POST /api/agent/screenshot  (multipart/form-data)
        Fields: session_id, captured_at, file (binary), active_window_title
        """
        try:
            form = {
                'session_id':          str(screenshot_data['session_id']),
                'captured_at':         screenshot_data.get('captured_at', datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ')),
                'active_window_title': screenshot_data.get('active_window_title', ''),
            }
            files = {'file': ('screenshot.jpg', image_data, 'image/jpeg')}
            success, _ = self._request('POST', 'screenshot', form, files)
            if not success:
                logger.warning("Failed to send screenshot")
            return success
        except Exception as e:
            logger.error(f"Screenshot upload error: {e}")
            return False

    # ── Idle ──────────────────────────────────────────────────────────────────

    def send_idle(self, idle_data: dict) -> bool:
        """
        POST /api/agent/idle
        Required keys: session_id, idle_start, idle_end, duration_seconds
        """
        success, _ = self._request('POST', 'idle', {
            'session_id':       idle_data['session_id'],
            'idle_start':       idle_data['idle_start'],
            'idle_end':         idle_data['idle_end'],
            'duration_seconds': idle_data['duration_seconds'],
        })
        if not success:
            logger.warning("Failed to send idle period")
        return success
