import os
from dotenv import load_dotenv

load_dotenv()

BACKEND_URL = os.getenv("BACKEND_URL", "").rstrip("/")
API_TOKEN = os.getenv("API_TOKEN", "")
SCREENSHOT_INTERVAL = int(os.getenv("SCREENSHOT_INTERVAL", "300"))
SYNC_INTERVAL = int(os.getenv("SYNC_INTERVAL", "60"))
IDLE_THRESHOLD = int(os.getenv("IDLE_THRESHOLD", "300"))
WORKING_HOURS_ONLY = os.getenv("WORKING_HOURS_ONLY", "false").lower() == "true"
WORK_START = os.getenv("WORK_START", "09:00")
WORK_END = os.getenv("WORK_END", "18:00")

if not BACKEND_URL:
    raise RuntimeError("BACKEND_URL is not set in .env")
if not API_TOKEN:
    raise RuntimeError("API_TOKEN is not set in .env")
