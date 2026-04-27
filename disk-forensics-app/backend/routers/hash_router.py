from fastapi import APIRouter, HTTPException
from models.schemas import HashResult
from services.hash_service import compute_hashes, verify_hash
from routers.upload import get_image_path

router = APIRouter(prefix="/api/hash", tags=["hash"])


@router.get("/{image_id}", response_model=HashResult)
async def get_image_hash(image_id: str):
    """Compute and return MD5, SHA1, SHA256 hashes for the disk image."""
    path = get_image_path(image_id)
    hashes = compute_hashes(path)
    # Retrieve original filename from path
    filename = path.split("/")[-1].split("\\")[-1]
    return HashResult(
        image_id=image_id,
        filename=filename,
        md5=hashes["md5"],
        sha1=hashes["sha1"],
        sha256=hashes["sha256"],
        file_size=hashes["file_size"],
        computed_at=hashes["computed_at"],
    )


@router.post("/{image_id}/verify")
async def verify_image_hash(image_id: str, expected_hash: str, algorithm: str = "sha256"):
    """Verify a disk image's integrity against a known hash."""
    path = get_image_path(image_id)
    if algorithm.lower() not in ("md5", "sha1", "sha256"):
        raise HTTPException(status_code=400, detail="Algorithm must be md5, sha1, or sha256.")
    match = verify_hash(path, expected_hash, algorithm)
    return {
        "image_id": image_id,
        "algorithm": algorithm,
        "expected": expected_hash,
        "match": match,
        "message": "Hash verified ✓" if match else "Hash mismatch — image may be corrupted or tampered.",
    }
