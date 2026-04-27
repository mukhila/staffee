"""
Disk Image Analyzer Service
Uses pytsk3 (The Sleuth Kit Python bindings) for filesystem parsing.
Falls back to a basic raw parser if pytsk3 is unavailable.
"""
import os
import struct
import hashlib
from datetime import datetime, timezone
from typing import List, Optional, Tuple
from models.schemas import FileEntry, DeletedFile, TimelineEvent

# Try importing pytsk3 (The Sleuth Kit bindings)
try:
    import pytsk3
    TSK_AVAILABLE = True
except ImportError:
    TSK_AVAILABLE = False


# ─── Timestamp helpers ────────────────────────────────────────────────────────

def _ts(unix_ts: Optional[int]) -> Optional[str]:
    """Convert a Unix timestamp to ISO-8601 string."""
    if not unix_ts:
        return None
    try:
        return datetime.fromtimestamp(unix_ts, tz=timezone.utc).isoformat()
    except (OSError, OverflowError, ValueError):
        return None


def _ext(name: str) -> Optional[str]:
    _, dot_ext = os.path.splitext(name)
    return dot_ext.lstrip(".").lower() if dot_ext else None


# ─── TSK-based analyzer (primary) ─────────────────────────────────────────────

class TSKAnalyzer:
    """Filesystem analysis using pytsk3 / The Sleuth Kit."""

    def __init__(self, image_path: str):
        self.image_path = image_path
        self.img_info = pytsk3.Img_Info(image_path)
        # Try opening directly as a filesystem first, then fall back to partition scan
        try:
            self.fs_info = pytsk3.FS_Info(self.img_info)
        except Exception:
            # Find first valid partition
            volume = pytsk3.Volume_Info(self.img_info)
            for part in volume:
                if part.len > 2048:
                    try:
                        self.fs_info = pytsk3.FS_Info(
                            self.img_info, offset=part.start * 512
                        )
                        break
                    except Exception:
                        continue

    def fs_type(self) -> str:
        type_map = {
            pytsk3.TSK_FS_TYPE_FAT12: "FAT12",
            pytsk3.TSK_FS_TYPE_FAT16: "FAT16",
            pytsk3.TSK_FS_TYPE_FAT32: "FAT32",
            pytsk3.TSK_FS_TYPE_NTFS: "NTFS",
            pytsk3.TSK_FS_TYPE_EXT2: "ext2",
            pytsk3.TSK_FS_TYPE_EXT3: "ext3",
            pytsk3.TSK_FS_TYPE_EXT4: "ext4",
            pytsk3.TSK_FS_TYPE_ISO9660: "ISO9660",
            pytsk3.TSK_FS_TYPE_HFS: "HFS+",
        }
        return type_map.get(self.fs_info.info.ftype, "Unknown")

    def _process_directory(
        self, directory, path: str, files: List[FileEntry],
        deleted: List[DeletedFile], timeline: List[TimelineEvent],
        depth: int = 0
    ):
        if depth > 20:
            return
        for entry in directory:
            try:
                name = entry.info.name.name.decode("utf-8", errors="replace")
                if name in (".", ".."):
                    continue
                meta = entry.info.meta
                is_dir = (
                    meta and
                    meta.type == pytsk3.TSK_FS_META_TYPE_DIR
                )
                is_del = (
                    entry.info.name.flags &
                    pytsk3.TSK_FS_NAME_FLAG_UNALLOC
                ) != 0
                size = meta.size if meta else 0
                inode = meta.addr if meta else None
                created = _ts(meta.crtime if meta else None)
                modified = _ts(meta.mtime if meta else None)
                accessed = _ts(meta.atime if meta else None)
                file_path = f"{path}/{name}"

                fe = FileEntry(
                    name=name,
                    path=file_path,
                    size=size,
                    is_directory=is_dir,
                    is_deleted=is_del,
                    created=created,
                    modified=modified,
                    accessed=accessed,
                    inode=inode,
                    extension=_ext(name),
                )
                files.append(fe)

                if is_del and not is_dir:
                    deleted.append(DeletedFile(
                        name=name,
                        path=file_path,
                        size=size,
                        inode=inode,
                        modified=modified,
                        extension=_ext(name),
                        recoverable=size > 0,
                    ))

                # Timeline events
                for ts, ev in [(created, "created"), (modified, "modified"), (accessed, "accessed")]:
                    if ts:
                        timeline.append(TimelineEvent(
                            timestamp=ts,
                            filename=name,
                            path=file_path,
                            event_type=ev,
                            size=size,
                            is_deleted=is_del,
                        ))

                # Recurse into directories
                if is_dir and not is_del:
                    try:
                        sub_dir = entry.as_directory()
                        self._process_directory(
                            sub_dir, file_path, files, deleted, timeline, depth + 1
                        )
                    except Exception:
                        pass
            except Exception:
                continue

    def analyze(self) -> Tuple[List[FileEntry], List[DeletedFile], List[TimelineEvent], dict]:
        files, deleted, timeline = [], [], []
        root = self.fs_info.open_dir("/")
        self._process_directory(root, "", files, deleted, timeline)
        info = {
            "fs_type": self.fs_type(),
            "block_size": self.fs_info.info.block_size,
            "total_blocks": self.fs_info.info.block_count,
            "total_size": self.fs_info.info.block_size * self.fs_info.info.block_count,
        }
        return files, deleted, timeline, info


# ─── Raw FAT32 parser (fallback) ──────────────────────────────────────────────

class RawFAT32Parser:
    """
    Minimal FAT32 parser used when pytsk3 is not installed.
    Parses the BPB, reads the root cluster, and walks directory entries.
    """

    ATTR_READ_ONLY  = 0x01
    ATTR_HIDDEN     = 0x02
    ATTR_SYSTEM     = 0x04
    ATTR_VOLUME_ID  = 0x08
    ATTR_DIRECTORY  = 0x10
    ATTR_ARCHIVE    = 0x20
    ATTR_LFN        = 0x0F

    def __init__(self, image_path: str):
        self.image_path = image_path
        self.f = open(image_path, "rb")
        self._parse_bpb()

    def _parse_bpb(self):
        self.f.seek(0)
        bpb = self.f.read(512)
        self.bytes_per_sector   = struct.unpack_from("<H", bpb, 11)[0]
        self.sectors_per_cluster= bpb[13]
        self.reserved_sectors   = struct.unpack_from("<H", bpb, 14)[0]
        self.num_fats           = bpb[16]
        self.total_sectors      = struct.unpack_from("<I", bpb, 32)[0]
        self.sectors_per_fat    = struct.unpack_from("<I", bpb, 36)[0]
        self.root_cluster       = struct.unpack_from("<I", bpb, 44)[0]
        self.volume_label       = bpb[71:82].decode("ascii", errors="replace").strip()

        self.cluster_size = self.bytes_per_sector * self.sectors_per_cluster
        self.fat_start    = self.reserved_sectors * self.bytes_per_sector
        self.data_start   = (
            self.reserved_sectors +
            self.num_fats * self.sectors_per_fat
        ) * self.bytes_per_sector

    def _cluster_offset(self, cluster: int) -> int:
        return self.data_start + (cluster - 2) * self.cluster_size

    def _read_cluster(self, cluster: int) -> bytes:
        self.f.seek(self._cluster_offset(cluster))
        return self.f.read(self.cluster_size)

    def _next_cluster(self, cluster: int) -> Optional[int]:
        fat_offset = self.fat_start + cluster * 4
        self.f.seek(fat_offset)
        val = struct.unpack("<I", self.f.read(4))[0] & 0x0FFFFFFF
        return None if val >= 0x0FFFFFF8 else val

    def _fat32_date(self, date_val: int, time_val: int) -> Optional[str]:
        if date_val == 0:
            return None
        day   = date_val & 0x1F
        month = (date_val >> 5) & 0x0F
        year  = ((date_val >> 9) & 0x7F) + 1980
        hour  = (time_val >> 11) & 0x1F
        minute= (time_val >> 5) & 0x3F
        second= (time_val & 0x1F) * 2
        try:
            dt = datetime(year, month, day, hour, minute, second, tzinfo=timezone.utc)
            return dt.isoformat()
        except ValueError:
            return None

    def _read_dir_entries(self, cluster: int, path: str,
                          files: list, deleted: list, timeline: list, depth: int = 0):
        if depth > 15 or cluster < 2 or cluster >= 0x0FFFFFF8:
            return
        lfn_parts = {}
        while cluster and cluster < 0x0FFFFFF8:
            data = self._read_cluster(cluster)
            for i in range(0, len(data), 32):
                entry = data[i:i+32]
                if len(entry) < 32:
                    break
                first_byte = entry[0]
                if first_byte == 0x00:
                    return
                if first_byte == 0xE5:
                    # Deleted entry — try to recover name
                    attr = entry[11]
                    if attr == self.ATTR_LFN or attr & self.ATTR_VOLUME_ID:
                        continue
                    raw_name = entry[0:8].decode("ascii", errors="replace").strip()
                    raw_ext  = entry[8:11].decode("ascii", errors="replace").strip()
                    name = (raw_name + ("." + raw_ext if raw_ext else "")).replace("\x00", "?")
                    size = struct.unpack_from("<I", entry, 28)[0]
                    wdate = struct.unpack_from("<H", entry, 24)[0]
                    wtime = struct.unpack_from("<H", entry, 22)[0]
                    modified = self._fat32_date(wdate, wtime)
                    ext = raw_ext.lower() if raw_ext else None
                    file_path = f"{path}/{name}"
                    deleted.append(DeletedFile(
                        name=name, path=file_path, size=size,
                        modified=modified, extension=ext, recoverable=size > 0,
                    ))
                    continue

                attr = entry[11]
                if attr == self.ATTR_LFN:
                    continue
                if attr & self.ATTR_VOLUME_ID:
                    continue

                raw_name = entry[1:8].decode("ascii", errors="replace").strip() if first_byte else entry[0:8].decode("ascii", errors="replace").strip()
                # Re-read properly
                raw_name = entry[0:8].decode("ascii", errors="replace").strip()
                raw_ext  = entry[8:11].decode("ascii", errors="replace").strip()
                if not raw_name:
                    continue
                name = raw_name + ("." + raw_ext if raw_ext else "")
                if name in (".", ".."):
                    continue

                is_dir = bool(attr & self.ATTR_DIRECTORY)
                size   = struct.unpack_from("<I", entry, 28)[0]
                cdate  = struct.unpack_from("<H", entry, 16)[0]
                ctime  = struct.unpack_from("<H", entry, 14)[0]
                wdate  = struct.unpack_from("<H", entry, 24)[0]
                wtime  = struct.unpack_from("<H", entry, 22)[0]
                adate  = struct.unpack_from("<H", entry, 18)[0]
                created  = self._fat32_date(cdate, ctime)
                modified = self._fat32_date(wdate, wtime)
                accessed = self._fat32_date(adate, 0)

                hi = struct.unpack_from("<H", entry, 20)[0]
                lo = struct.unpack_from("<H", entry, 26)[0]
                child_cluster = (hi << 16) | lo
                file_path = f"{path}/{name}"
                ext = raw_ext.lower() if raw_ext else None

                fe = FileEntry(
                    name=name, path=file_path, size=size,
                    is_directory=is_dir, is_deleted=False,
                    created=created, modified=modified, accessed=accessed,
                    extension=ext,
                )
                files.append(fe)

                for ts, ev in [(created, "created"), (modified, "modified"), (accessed, "accessed")]:
                    if ts:
                        timeline.append(TimelineEvent(
                            timestamp=ts, filename=name, path=file_path,
                            event_type=ev, size=size, is_deleted=False,
                        ))

                if is_dir and child_cluster >= 2:
                    self._read_dir_entries(child_cluster, file_path, files, deleted, timeline, depth + 1)

            cluster = self._next_cluster(cluster)

    def analyze(self) -> Tuple[List[FileEntry], List[DeletedFile], List[TimelineEvent], dict]:
        files, deleted, timeline = [], [], []
        self._read_dir_entries(self.root_cluster, "", files, deleted, timeline)
        total_size = self.total_sectors * self.bytes_per_sector
        info = {
            "fs_type": "FAT32",
            "block_size": self.cluster_size,
            "total_blocks": self.total_sectors // self.sectors_per_cluster,
            "total_size": total_size,
        }
        self.f.close()
        return files, deleted, timeline, info


# ─── Public interface ──────────────────────────────────────────────────────────

def analyze_image(image_path: str) -> Tuple[List[FileEntry], List[DeletedFile], List[TimelineEvent], dict]:
    """
    Analyze a disk image file.
    Uses pytsk3 (TSK) if available, otherwise falls back to raw FAT32 parser.
    """
    if TSK_AVAILABLE:
        analyzer = TSKAnalyzer(image_path)
    else:
        analyzer = RawFAT32Parser(image_path)
    return analyzer.analyze()


def recover_file(image_path: str, inode: int, output_path: str) -> bool:
    """Extract a file from the image by inode number (TSK only)."""
    if not TSK_AVAILABLE:
        return False
    try:
        img = pytsk3.Img_Info(image_path)
        fs  = pytsk3.FS_Info(img)
        f   = fs.open_meta(inode=inode)
        size = f.info.meta.size
        data = f.read_random(0, size)
        with open(output_path, "wb") as out:
            out.write(data)
        return True
    except Exception:
        return False
