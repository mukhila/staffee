from pydantic import BaseModel
from typing import Optional, List
from datetime import datetime


class FileEntry(BaseModel):
    name: str
    path: str
    size: int
    is_directory: bool
    is_deleted: bool
    created: Optional[str] = None
    modified: Optional[str] = None
    accessed: Optional[str] = None
    inode: Optional[int] = None
    extension: Optional[str] = None


class FileSystemInfo(BaseModel):
    image_id: str
    fs_type: str
    block_size: int
    total_blocks: int
    total_size: int
    files: List[FileEntry]


class DeletedFile(BaseModel):
    name: str
    path: str
    size: int
    inode: Optional[int] = None
    modified: Optional[str] = None
    extension: Optional[str] = None
    recoverable: bool = True


class TimelineEvent(BaseModel):
    timestamp: str
    filename: str
    path: str
    event_type: str   # created, modified, accessed
    size: int
    is_deleted: bool


class HashResult(BaseModel):
    image_id: str
    filename: str
    md5: str
    sha1: str
    sha256: str
    file_size: int
    computed_at: str


class UploadResponse(BaseModel):
    image_id: str
    filename: str
    file_size: int
    md5: str
    sha256: str
    message: str


class ErrorResponse(BaseModel):
    detail: str
