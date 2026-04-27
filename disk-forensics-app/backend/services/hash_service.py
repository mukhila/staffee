import hashlib
import os
from datetime import datetime, timezone


def compute_hashes(file_path: str) -> dict:
    """Compute MD5, SHA1, and SHA256 hashes for a file."""
    md5 = hashlib.md5()
    sha1 = hashlib.sha1()
    sha256 = hashlib.sha256()

    with open(file_path, "rb") as f:
        for chunk in iter(lambda: f.read(8192), b""):
            md5.update(chunk)
            sha1.update(chunk)
            sha256.update(chunk)

    return {
        "md5": md5.hexdigest(),
        "sha1": sha1.hexdigest(),
        "sha256": sha256.hexdigest(),
        "file_size": os.path.getsize(file_path),
        "computed_at": datetime.now(timezone.utc).isoformat(),
    }


def verify_hash(file_path: str, expected_hash: str, algorithm: str = "sha256") -> bool:
    """Verify a file against an expected hash."""
    algorithms = {
        "md5": hashlib.md5,
        "sha1": hashlib.sha1,
        "sha256": hashlib.sha256,
    }
    h = algorithms.get(algorithm.lower(), hashlib.sha256)()
    with open(file_path, "rb") as f:
        for chunk in iter(lambda: f.read(8192), b""):
            h.update(chunk)
    return h.hexdigest().lower() == expected_hash.lower()
