import { useState, useCallback } from "react";
import { api, UploadResponse } from "../api/client";

interface Props {
  onUploaded: (resp: UploadResponse) => void;
}

export default function UploadZone({ onUploaded }: Props) {
  const [dragging, setDragging] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleFile = useCallback(async (file: File) => {
    setError(null);
    setUploading(true);
    try {
      const resp = await api.uploadImage(file);
      onUploaded(resp);
    } catch (e: any) {
      setError(e.message || "Upload failed");
    } finally {
      setUploading(false);
    }
  }, [onUploaded]);

  const onDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setDragging(false);
    const file = e.dataTransfer.files[0];
    if (file) handleFile(file);
  }, [handleFile]);

  const onInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) handleFile(file);
  };

  return (
    <div className="upload-zone-wrapper">
      <label
        className={`upload-zone ${dragging ? "drag-over" : ""} ${uploading ? "uploading" : ""}`}
        onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
      >
        <input
          type="file"
          accept=".dd,.img,.raw,.iso,.bin,.e01"
          style={{ display: "none" }}
          onChange={onInputChange}
          disabled={uploading}
        />
        <div className="upload-icon">💽</div>
        {uploading ? (
          <>
            <p className="upload-title">Uploading & hashing…</p>
            <div className="spinner" />
          </>
        ) : (
          <>
            <p className="upload-title">Drop a disk image here</p>
            <p className="upload-sub">or click to browse</p>
            <p className="upload-formats">.dd  ·  .img  ·  .raw  ·  .iso  ·  .bin  ·  .e01</p>
          </>
        )}
      </label>
      {error && <p className="upload-error">⚠ {error}</p>}
    </div>
  );
}
