import os
from fastapi import APIRouter
from fastapi.responses import FileResponse
from typing import List
from models.schemas import DeletedFile
from services.disk_analyzer import analyze_image, recover_file
from routers.upload import get_image_path
from routers.filesystem import _get_analysis

router = APIRouter(prefix="/api/recovery", tags=["recovery"])

RECOVERY_DIR = os.path.join(os.path.dirname(__file__), "..", "recovered")


@router.get("/{image_id}/deleted", response_model=List[DeletedFile])
async def list_deleted_files(image_id: str):
    """List all deleted/unallocated files found in the disk image."""
    data = _get_analysis(image_id)
    return data["deleted"]


@router.get("/{image_id}/recover/{inode}")
async def recover_deleted_file(image_id: str, inode: int):
    """
    Attempt to recover a deleted file by its inode number.
    Requires pytsk3 to be installed.
    """
    image_path = get_image_path(image_id)
    os.makedirs(RECOVERY_DIR, exist_ok=True)
    output_path = os.path.join(RECOVERY_DIR, f"{image_id}_inode_{inode}.bin")

    success = recover_file(image_path, inode, output_path)
    if not success:
        return {
            "success": False,
            "message": "Recovery failed. pytsk3 must be installed for file recovery.",
        }

    return FileResponse(
        path=output_path,
        filename=f"recovered_inode_{inode}.bin",
        media_type="application/octet-stream",
    )
