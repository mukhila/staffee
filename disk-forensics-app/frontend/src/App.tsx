import { useState } from "react";
import { UploadResponse } from "./api/client";
import UploadZone from "./components/UploadZone";
import FileTree from "./components/FileTree";
import DeletedFiles from "./components/DeletedFiles";
import Timeline from "./components/Timeline";
import HashPanel from "./components/HashPanel";
import "./App.css";

type Tab = "filesystem" | "deleted" | "timeline" | "hash";

const TABS: { id: Tab; label: string; icon: string }[] = [
  { id: "filesystem", label: "File System", icon: "🗂" },
  { id: "deleted", label: "Deleted Files", icon: "🗑" },
  { id: "timeline", label: "Timeline", icon: "📅" },
  { id: "hash", label: "Hash & Integrity", icon: "🔐" },
];

export default function App() {
  const [uploadInfo, setUploadInfo] = useState<UploadResponse | null>(null);
  const [activeTab, setActiveTab] = useState<Tab>("filesystem");

  return (
    <div className="app">
      <header className="header">
        <div className="header-left">
          <span className="logo">🔬</span>
          <div>
            <h1 className="app-title">Disk Image Analyzer</h1>
            <p className="app-subtitle">Digital Forensics Tool</p>
          </div>
        </div>
        {uploadInfo && (
          <div className="header-file-info">
            <span className="header-filename">💽 {uploadInfo.filename}</span>
            <button className="new-image-btn" onClick={() => setUploadInfo(null)}>
              + New Image
            </button>
          </div>
        )}
      </header>

      <main className="main">
        {!uploadInfo ? (
          <div className="welcome">
            <div className="welcome-card">
              <h2>Welcome to Disk Image Analyzer</h2>
              <p>Upload a raw disk image to begin forensic analysis.</p>
              <ul className="feature-list">
                <li>🗂 Browse the full file system tree</li>
                <li>🗑 Detect and recover deleted files</li>
                <li>📅 Visualize MAC time activity timeline</li>
                <li>🔐 Verify evidence integrity via hash</li>
              </ul>
              <UploadZone onUploaded={(resp) => { setUploadInfo(resp); setActiveTab("filesystem"); }} />
            </div>
          </div>
        ) : (
          <>
            <div className="upload-info-bar">
              <div className="info-item">
                <span className="info-label">Image ID</span>
                <code className="info-val">{uploadInfo.image_id.slice(0, 8)}…</code>
              </div>
              <div className="info-item">
                <span className="info-label">MD5</span>
                <code className="info-val">{uploadInfo.md5.slice(0, 16)}…</code>
              </div>
              <div className="info-item">
                <span className="info-label">SHA-256</span>
                <code className="info-val">{uploadInfo.sha256.slice(0, 16)}…</code>
              </div>
            </div>

            <div className="tab-bar">
              {TABS.map((tab) => (
                <button
                  key={tab.id}
                  className={`tab-btn ${activeTab === tab.id ? "tab-active" : ""}`}
                  onClick={() => setActiveTab(tab.id)}
                >
                  {tab.icon} {tab.label}
                </button>
              ))}
            </div>

            <div className="tab-content">
              {activeTab === "filesystem" && <FileTree imageId={uploadInfo.image_id} />}
              {activeTab === "deleted"    && <DeletedFiles imageId={uploadInfo.image_id} />}
              {activeTab === "timeline"   && <Timeline imageId={uploadInfo.image_id} />}
              {activeTab === "hash"       && <HashPanel imageId={uploadInfo.image_id} uploadInfo={uploadInfo} />}
            </div>
          </>
        )}
      </main>
    </div>
  );
}
