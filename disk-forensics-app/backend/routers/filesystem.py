from fastapi import APIRouter
from typing import List
from models.schemas import FileEntry, FileSystemInfo
from services.disk_analyzer import analyze_image
from routers.upload import get_image_path

router = APIRouter(prefix="/api/filesystem", tags=["filesystem"])

# Cache analysis results to avoid re-parsing on every request
_cache: dict[str, dict] = {}


def _get_analysis(image_id: str) -> dict:
    if image_id not in _cache:
        path = get_image_path(image_id)
        files, deleted, timeline, info = analyze_image(path)
        _cache[image_id] = {
            "files": files,
            "deleted": deleted,
            "timeline": timeline,
            "info": info,
        }
    return _cache[image_id]


@router.get("/{image_id}", response_model=FileSystemInfo)
async def get_filesystem(image_id: str):
    """Parse the filesystem of an uploaded disk image."""
    data = _get_analysis(image_id)
    info = data["info"]
    return FileSystemInfo(
        image_id=image_id,
        fs_type=info["fs_type"],
        block_size=info["block_size"],
        total_blocks=info["total_blocks"],
        total_size=info["total_size"],
        files=data["files"],
    )


@router.get("/{image_id}/tree", response_model=List[FileEntry])
async def get_file_tree(image_id: str, path: str = ""):
    """Get files at a specific directory path."""
    data = _get_analysis(image_id)
    all_files = data["files"]
    if path:
        filtered = [f for f in all_files if f.path.startswith(path + "/") or f.path == path]
    else:
        filtered = all_files
    return filtered
