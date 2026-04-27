import os
import uuid
import shutil
from fastapi import APIRouter, File, UploadFile, HTTPException
from models.schemas import UploadResponse
from services.hash_service import compute_hashes

router = APIRouter(prefix="/api/upload", tags=["upload"])

UPLOAD_DIR = os.path.join(os.path.dirname(__file__), "..", "uploads")
ALLOWED_EXTENSIONS = {".dd", ".img", ".raw", ".iso", ".bin", ".e01"}

# In-memory registry: image_id → file path
image_registry: dict[str, str] = {}


@router.post("", response_model=UploadResponse)
async def upload_image(file: UploadFile = File(...)):
    """Upload a disk image file (.dd, .img, .raw, etc.)"""
    _, ext = os.path.splitext(file.filename or "")
    if ext.lower() not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=400,
            detail=f"Unsupported file type '{ext}'. Allowed: {', '.join(ALLOWED_EXTENSIONS)}"
        )

    image_id = str(uuid.uuid4())
    dest_path = os.path.join(UPLOAD_DIR, f"{image_id}{ext}")
    os.makedirs(UPLOAD_DIR, exist_ok=True)

    with open(dest_path, "wb") as out:
        shutil.copyfileobj(file.file, out)

    hashes = compute_hashes(dest_path)
    image_registry[image_id] = dest_path

    return UploadResponse(
        image_id=image_id,
        filename=file.filename or "unknown",
        file_size=hashes["file_size"],
        md5=hashes["md5"],
        sha256=hashes["sha256"],
        message="Image uploaded successfully. Use the image_id for analysis.",
    )


def get_image_path(image_id: str) -> str:
    """Resolve image_id to a file path, or raise 404."""
    path = image_registry.get(image_id)
    if not path or not os.path.exists(path):
        raise HTTPException(status_code=404, detail=f"Image '{image_id}' not found.")
    return path
