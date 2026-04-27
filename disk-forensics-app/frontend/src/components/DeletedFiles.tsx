import { useState, useEffect } from "react";
import { api, DeletedFile } from "../api/client";

interface Props {
  imageId: string;
}

function formatSize(bytes: number): string {
  if (bytes === 0) return "0 B";
  const units = ["B", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / 1024 ** i).toFixed(1)} ${units[i]}`;
}

const EXT_COLORS: Record<string, string> = {
  jpg: "#e74c3c", jpeg: "#e74c3c", png: "#e74c3c", gif: "#e74c3c",
  pdf: "#c0392b",
  doc: "#2980b9", docx: "#2980b9",
  xls: "#27ae60", xlsx: "#27ae60",
  mp4: "#8e44ad", avi: "#8e44ad", mkv: "#8e44ad",
  mp3: "#f39c12", wav: "#f39c12",
  zip: "#7f8c8d", rar: "#7f8c8d",
  exe: "#e67e22", dll: "#e67e22",
  txt: "#34495e", log: "#34495e",
};

export default function DeletedFiles({ imageId }: Props) {
  const [files, setFiles] = useState<DeletedFile[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState("");
  const [recovering, setRecovering] = useState<number | null>(null);

  useEffect(() => {
    api.getDeletedFiles(imageId)
      .then(setFiles)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [imageId]);

  const handleRecover = async (file: DeletedFile) => {
    if (!file.inode) return;
    setRecovering(file.inode);
    const url = api.getRecoverUrl(imageId, file.inode);
    const link = document.createElement("a");
    link.href = url;
    link.download = file.name;
    link.click();
    setTimeout(() => setRecovering(null), 1500);
  };

  if (loading) return <div className="panel-loading">Scanning for deleted files…</div>;
  if (error) return <div className="panel-error">Error: {error}</div>;

  const filtered = filter
    ? files.filter((f) =>
        f.name.toLowerCase().includes(filter.toLowerCase()) ||
        (f.extension || "").includes(filter.toLowerCase())
      )
    : files;

  // Group by extension
  const extGroups = filtered.reduce<Record<string, DeletedFile[]>>((acc, f) => {
    const ext = f.extension || "unknown";
    if (!acc[ext]) acc[ext] = [];
    acc[ext].push(f);
    return acc;
  }, {});

  return (
    <div className="panel">
      <div className="recovery-stats">
        <div className="stat-card">
          <div className="stat-num">{files.length}</div>
          <div className="stat-label">Deleted Files</div>
        </div>
        <div className="stat-card">
          <div className="stat-num">{files.filter((f) => f.recoverable).length}</div>
          <div className="stat-label">Recoverable</div>
        </div>
        <div className="stat-card">
          <div className="stat-num">
            {formatSize(files.reduce((a, f) => a + f.size, 0))}
          </div>
          <div className="stat-label">Total Size</div>
        </div>
      </div>

      <div className="ext-chips">
        {Object.entries(extGroups).map(([ext, items]) => (
          <span
            key={ext}
            className="ext-chip"
            style={{ background: EXT_COLORS[ext] || "#95a5a6" }}
            onClick={() => setFilter(ext === filter ? "" : ext)}
          >
            .{ext} ({items.length})
          </span>
        ))}
      </div>

      <input
        className="search-box"
        placeholder="🔍 Filter deleted files…"
        value={filter}
        onChange={(e) => setFilter(e.target.value)}
      />

      {filtered.length === 0 && (
        <p className="empty">No deleted files found{filter ? " matching your filter" : ""}.</p>
      )}

      <div className="deleted-table">
        <div className="deleted-header">
          <span>Name</span>
          <span>Extension</span>
          <span>Size</span>
          <span>Modified</span>
          <span>Action</span>
        </div>
        {filtered.map((f, i) => (
          <div key={i} className="deleted-row-item">
            <span className="del-name" title={f.path}>
              🗑 {f.name}
            </span>
            <span>
              <span
                className="ext-badge"
                style={{ background: EXT_COLORS[f.extension || ""] || "#95a5a6" }}
              >
                {f.extension || "?"}
              </span>
            </span>
            <span>{formatSize(f.size)}</span>
            <span>{f.modified ? new Date(f.modified).toLocaleString() : "—"}</span>
            <span>
              {f.recoverable && f.inode ? (
                <button
                  className="recover-btn"
                  onClick={() => handleRecover(f)}
                  disabled={recovering === f.inode}
                >
                  {recovering === f.inode ? "↓ Downloading…" : "⬇ Recover"}
                </button>
              ) : (
                <span className="not-recoverable">Not recoverable</span>
              )}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}
