import { useState, useEffect } from "react";
import { api, FileEntry, FileSystemInfo } from "../api/client";

interface Props {
  imageId: string;
}

const FILE_ICONS: Record<string, string> = {
  pdf: "📄", jpg: "🖼", jpeg: "🖼", png: "🖼", gif: "🖼",
  mp4: "🎬", avi: "🎬", mkv: "🎬", mov: "🎬",
  mp3: "🎵", wav: "🎵", flac: "🎵",
  zip: "🗜", rar: "🗜", "7z": "🗜", tar: "🗜", gz: "🗜",
  exe: "⚙", dll: "⚙", sys: "⚙",
  txt: "📝", log: "📝", md: "📝",
  doc: "📃", docx: "📃", xls: "📊", xlsx: "📊", ppt: "📋", pptx: "📋",
  sql: "🗃", db: "🗃", sqlite: "🗃",
  js: "🟨", ts: "🔷", py: "🐍", html: "🌐", css: "🎨",
};

function fileIcon(entry: FileEntry): string {
  if (entry.is_directory) return "📁";
  return FILE_ICONS[entry.extension || ""] || "📄";
}

function formatSize(bytes: number): string {
  if (bytes === 0) return "0 B";
  const units = ["B", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / 1024 ** i).toFixed(1)} ${units[i]}`;
}

function buildTree(files: FileEntry[]): Record<string, FileEntry[]> {
  const tree: Record<string, FileEntry[]> = { "": [] };
  for (const f of files) {
    const parts = f.path.split("/").filter(Boolean);
    const parentParts = parts.slice(0, -1);
    const parentKey = parentParts.length ? "/" + parentParts.join("/") : "";
    if (!tree[parentKey]) tree[parentKey] = [];
    tree[parentKey].push(f);
  }
  return tree;
}

interface TreeNodeProps {
  entry: FileEntry;
  tree: Record<string, FileEntry[]>;
  depth: number;
}

function TreeNode({ entry, tree, depth }: TreeNodeProps) {
  const [open, setOpen] = useState(depth === 0);
  const children = entry.is_directory ? (tree[entry.path] || []) : [];
  const hasChildren = children.length > 0;

  return (
    <div className="tree-node" style={{ paddingLeft: depth * 16 }}>
      <div
        className={`tree-row ${entry.is_deleted ? "deleted-row" : ""}`}
        onClick={() => entry.is_directory && setOpen(!open)}
        style={{ cursor: entry.is_directory ? "pointer" : "default" }}
      >
        <span className="tree-toggle">
          {entry.is_directory && hasChildren ? (open ? "▾" : "▸") : " "}
        </span>
        <span className="tree-icon">{fileIcon(entry)}</span>
        <span className="tree-name" title={entry.path}>
          {entry.name}
          {entry.is_deleted && <span className="deleted-badge">DELETED</span>}
        </span>
        <span className="tree-size">{formatSize(entry.size)}</span>
        <span className="tree-date">{entry.modified ? new Date(entry.modified).toLocaleDateString() : "—"}</span>
      </div>
      {open && entry.is_directory && (
        <div>
          {children.map((child) => (
            <TreeNode key={child.path} entry={child} tree={tree} depth={depth + 1} />
          ))}
        </div>
      )}
    </div>
  );
}

export default function FileTree({ imageId }: Props) {
  const [fsInfo, setFsInfo] = useState<FileSystemInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [search, setSearch] = useState("");

  useEffect(() => {
    api.getFilesystem(imageId)
      .then(setFsInfo)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [imageId]);

  if (loading) return <div className="panel-loading">Parsing filesystem…</div>;
  if (error) return <div className="panel-error">Error: {error}</div>;
  if (!fsInfo) return null;

  const filteredFiles = search
    ? fsInfo.files.filter((f) => f.name.toLowerCase().includes(search.toLowerCase()))
    : fsInfo.files;

  const tree = buildTree(fsInfo.files);
  const roots = tree[""] || [];

  return (
    <div className="panel">
      <div className="fs-info-bar">
        <span>🗂 <strong>{fsInfo.fs_type}</strong></span>
        <span>📦 {formatSize(fsInfo.total_size)}</span>
        <span>📄 {fsInfo.files.length} entries</span>
        <span>🔴 {fsInfo.files.filter(f => f.is_deleted).length} deleted</span>
      </div>
      <input
        className="search-box"
        placeholder="🔍 Search files…"
        value={search}
        onChange={(e) => setSearch(e.target.value)}
      />
      {search ? (
        <div className="search-results">
          {filteredFiles.length === 0 && <p className="empty">No files found.</p>}
          {filteredFiles.map((f) => (
            <div key={f.path} className={`search-row ${f.is_deleted ? "deleted-row" : ""}`}>
              <span>{fileIcon(f)}</span>
              <span className="tree-name">{f.path}</span>
              {f.is_deleted && <span className="deleted-badge">DELETED</span>}
              <span className="tree-size">{formatSize(f.size)}</span>
            </div>
          ))}
        </div>
      ) : (
        <div className="tree-container">
          {roots.map((entry) => (
            <TreeNode key={entry.path} entry={entry} tree={tree} depth={0} />
          ))}
        </div>
      )}
    </div>
  );
}
