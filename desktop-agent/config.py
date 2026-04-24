"""
Configuration management for the desktop monitoring agent.
Loads from .env and provides centralized config access.
"""

import os
from pathlib import Path
from dotenv import load_dotenv

# Load environment variables
env_path = Path(__file__).parent / '.env'
load_dotenv(dotenv_path=env_path)

class Config:
    """Main configuration class"""

    # API Configuration
    BACKEND_URL = os.getenv('BACKEND_URL', 'http://localhost')
    API_TOKEN = os.getenv('API_TOKEN', '')

    # Monitoring Configuration
    SCREENSHOT_INTERVAL = int(os.getenv('SCREENSHOT_INTERVAL', '300'))
    IDLE_THRESHOLD = int(os.getenv('IDLE_THRESHOLD', '300'))
    SYNC_INTERVAL = int(os.getenv('SYNC_INTERVAL', '60'))

    # Screenshot Configuration
    BLUR_SCREENSHOTS = os.getenv('BLUR_SCREENSHOTS', 'false').lower() == 'true'
    COMPRESS_QUALITY = int(os.getenv('COMPRESS_QUALITY', '80'))
    MAX_SCREENSHOT_SIZE = int(os.getenv('MAX_SCREENSHOT_SIZE', '500000'))  # 500 KB

    # Offline Configuration
    OFFLINE_QUEUE_MAX = int(os.getenv('OFFLINE_QUEUE_MAX', '500'))
    LOCAL_STORAGE_PATH = Path.home() / '.monitoring_agent'

    # Consent Configuration
    AUTO_CONSENT = os.getenv('AUTO_CONSENT', 'false').lower() == 'true'
    CONSENT_FILE = LOCAL_STORAGE_PATH / 'consent.json'

    # Debug
    DEBUG = os.getenv('DEBUG', 'false').lower() == 'true'
    LOG_FILE = LOCAL_STORAGE_PATH / 'agent.log'

    @classmethod
    def validate(cls):
        """Validate required configuration"""
        if not cls.BACKEND_URL:
            raise ValueError("BACKEND_URL is required")
        if not cls.API_TOKEN:
            raise ValueError("API_TOKEN is required")

    @classmethod
    def ensure_directories(cls):
        """Create necessary directories"""
        cls.LOCAL_STORAGE_PATH.mkdir(parents=True, exist_ok=True)
