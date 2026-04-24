"""
Consent management for employee monitoring.
Handles consent UI and stores consent status locally.
"""

from datetime import datetime
from config import Config
from utils import logger, save_json, load_json


class ConsentManager:
    """Manages user consent for monitoring"""

    CONSENT_VERSION = "1.0"

    def __init__(self):
        self.consent_file = Config.CONSENT_FILE
        self.consent_data = self._load_consent()

    def _load_consent(self) -> dict:
        return load_json(self.consent_file)

    def has_consent(self) -> bool:
        return self.consent_data.get('consent_given', False)

    def show_consent_dialog(self) -> bool:
        try:
            import tkinter as tk
            from tkinter import messagebox

            root = tk.Tk()
            root.withdraw()

            message = (
                "EMPLOYEE MONITORING SYSTEM\n\n"
                "This application will monitor your computer activity including:\n"
                "  • Screenshots (configurable intervals)\n"
                "  • Active application windows\n"
                "  • Idle time detection\n"
                "  • Keyboard/mouse activity counts\n\n"
                "Your data will be securely transmitted and stored.\n"
                "You can pause monitoring at any time via the system tray.\n\n"
                "Do you consent to this monitoring?"
            )

            result = messagebox.askyesno("Consent Required", message)
            root.destroy()
            return result
        except Exception as e:
            logger.error(f"Consent dialog failed: {e}")
            return False

    def set_consent(self, consent_given: bool):
        self.consent_data = {
            'consent_given': consent_given,
            'consent_date': datetime.utcnow().isoformat(),
            'consent_version': self.CONSENT_VERSION,
        }
        save_json(self.consent_data, self.consent_file)
        logger.info(f"Consent set to: {consent_given}")

    def request_consent_if_needed(self) -> bool:
        """Show consent dialog if not yet given; return True if consent granted."""
        if self.has_consent():
            return True

        if Config.AUTO_CONSENT:
            self.set_consent(True)
            logger.warning("Auto consent enabled — consent assumed")
            return True

        logger.info("Requesting user consent")
        if self.show_consent_dialog():
            self.set_consent(True)
            return True

        logger.info("User declined consent")
        return False
