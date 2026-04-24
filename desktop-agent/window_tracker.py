"""
Active window and application tracking using Windows API.
Tracks current foreground window and running processes.
"""

import ctypes
import psutil
from pathlib import Path
from datetime import datetime
from utils import logger


class WindowTracker:
    """Tracks active windows and applications"""

    PRODUCTIVE_APPS = {
        'chrome', 'firefox', 'msedge', 'iexplore',
        'code', 'visualstudio', 'notepad++', 'sublime_text',
        'excel', 'winword', 'powerpnt',
        'slack', 'teams', 'thunderbird', 'outlook',
        'pycharm', 'intellij', 'rider',
        'postman', 'docker', 'git',
        'figma', 'photoshop', 'illustrator',
    }

    def __init__(self):
        self.last_window = None

    def get_active_window(self) -> dict:
        """
        Return currently active window details.

        Keys: title, app_name, executable, timestamp, is_productive
        """
        try:
            hwnd = ctypes.windll.user32.GetForegroundWindow()

            length = ctypes.windll.user32.GetWindowTextLengthW(hwnd)
            buf = ctypes.create_unicode_buffer(length + 1)
            ctypes.windll.user32.GetWindowTextW(hwnd, buf, length + 1)
            title = buf.value

            pid = ctypes.c_ulong()
            ctypes.windll.user32.GetWindowThreadProcessId(hwnd, ctypes.byref(pid))

            app_name = 'Unknown'
            executable = 'Unknown'
            try:
                proc = psutil.Process(pid.value)
                executable = proc.name()
                app_name = Path(executable).stem.lower()
            except Exception:
                pass

            return {
                'title': title,
                'app_name': app_name,
                'executable': executable,
                'timestamp': datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ'),
                'is_productive': self._is_productive_app(app_name),
            }

        except Exception as e:
            logger.error(f"Failed to get active window: {e}")
            return {
                'title': '',
                'app_name': 'unknown',
                'executable': 'unknown',
                'timestamp': datetime.utcnow().strftime('%Y-%m-%dT%H:%M:%SZ'),
                'is_productive': False,
            }

    def _is_productive_app(self, app_name: str) -> bool:
        app_lower = app_name.lower()
        return any(p in app_lower for p in self.PRODUCTIVE_APPS)

    def get_running_processes(self) -> list:
        try:
            processes = []
            for proc in psutil.process_iter(['pid', 'name']):
                try:
                    processes.append({'name': proc.info['name'], 'pid': proc.info['pid']})
                except Exception:
                    pass
            return processes
        except Exception as e:
            logger.error(f"Failed to get processes: {e}")
            return []
