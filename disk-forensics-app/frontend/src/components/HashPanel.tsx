import { useState, useEffect } from "react";
import { api, HashResult, UploadResponse } from "../api/client";

interface Props {
  imageId: string;
  uploadInfo: UploadResponse;
}

export default function HashPanel({ imageId, uploadInfo }: Props) {
  const [hashes, setHashes] = useState<HashResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [verifyInput, setVerifyInput] = useState("");
  const [verifyAlgo, setVerifyAlgo] = useState("sha256");
  const [verifyResult, setVerifyResult] = useState<{ match: boolean; message: string } | null>(null);
  const [verifying, setVerifying] = useState(false);
  const [copied, setCopied] = useState<string | null>(null);

  useEffect(() => {
    api.getHashes(imageId)
      .then(setHashes)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [imageId]);

  const copy = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    setCopied(label);
    setTimeout(() => setCopied(null), 2000);
  };

  const handleVerify = async () => {
    if (!verifyInput.trim()) return;
    setVerifying(true);
    setVerifyResult(null);
    try {
      const res = await api.verifyHash(imageId, verifyInput.trim(), verifyAlgo) as any;
      setVerifyResult({ match: res.match, message: res.message });
    } catch (e: any) {
      setVerifyResult({ match: false, message: e.message });
    } finally {
      setVerifying(false);
    }
  };

  function formatSize(bytes: number): string {
    if (bytes === 0) return "0 B";
    const units = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${(bytes / 1024 ** i).toFixed(2)} ${units[i]}`;
  }

  if (loading) return <div className="panel-loading">Computing hashes…</div>;
  if (error) return <div className="panel-error">Error: {error}</div>;
  if (!hashes) return null;

  const hashRows = [
    { label: "MD5", value: hashes.md5 },
    { label: "SHA-1", value: hashes.sha1 },
    { label: "SHA-256", value: hashes.sha256 },
  ];

  return (
    <div className="panel">
      <div className="hash-file-info">
        <div className="hash-file-detail">
          <span className="hash-label">Filename</span>
          <span className="hash-value-text">{uploadInfo.filename}</span>
        </div>
        <div className="hash-file-detail">
          <span className="hash-label">File Size</span>
          <span className="hash-value-text">{formatSize(hashes.file_size)}</span>
        </div>
        <div className="hash-file-detail">
          <span className="hash-label">Computed At</span>
          <span className="hash-value-text">{new Date(hashes.computed_at).toLocaleString()}</span>
        </div>
      </div>

      <div className="hash-grid">
        {hashRows.map(({ label, value }) => (
          <div key={label} className="hash-card">
            <div className="hash-card-label">{label}</div>
            <div className="hash-card-value">
              <code>{value}</code>
              <button
                className="copy-btn"
                onClick={() => copy(value, label)}
                title="Copy to clipboard"
              >
                {copied === label ? "✓ Copied" : "📋"}
              </button>
            </div>
          </div>
        ))}
      </div>

      <div className="verify-section">
        <h3 className="verify-title">🔐 Verify Integrity</h3>
        <p className="verify-desc">
          Paste a known hash to verify the evidence file hasn't been tampered with.
        </p>
        <div className="verify-row">
          <select
            value={verifyAlgo}
            onChange={(e) => setVerifyAlgo(e.target.value)}
            className="verify-algo"
          >
            <option value="md5">MD5</option>
            <option value="sha1">SHA-1</option>
            <option value="sha256">SHA-256</option>
          </select>
          <input
            className="verify-input"
            placeholder="Paste expected hash here…"
            value={verifyInput}
            onChange={(e) => setVerifyInput(e.target.value)}
          />
          <button
            className="verify-btn"
            onClick={handleVerify}
            disabled={verifying || !verifyInput.trim()}
          >
            {verifying ? "Checking…" : "Verify"}
          </button>
        </div>
        {verifyResult && (
          <div className={`verify-result ${verifyResult.match ? "match" : "mismatch"}`}>
            {verifyResult.match ? "✅" : "❌"} {verifyResult.message}
          </div>
        )}
      </div>

      <div className="chain-of-custody">
        <h3>📋 Chain of Custody Report</h3>
        <pre className="coc-block">
{`DISK IMAGE EVIDENCE RECORD
==========================
Filename  : ${uploadInfo.filename}
File Size : ${formatSize(hashes.file_size)}
Acquired  : ${new Date(hashes.computed_at).toUTCString()}

HASH VALUES
-----------
MD5    : ${hashes.md5}
SHA-1  : ${hashes.sha1}
SHA-256: ${hashes.sha256}

Examiner : ____________________
Signature: ____________________
Date     : ____________________`}
        </pre>
        <button
          className="copy-coc-btn"
          onClick={() =>
            copy(
              `DISK IMAGE EVIDENCE RECORD\n==========================\nFilename  : ${uploadInfo.filename}\nFile Size : ${formatSize(hashes.file_size)}\nAcquired  : ${new Date(hashes.computed_at).toUTCString()}\n\nHASH VALUES\n-----------\nMD5    : ${hashes.md5}\nSHA-1  : ${hashes.sha1}\nSHA-256: ${hashes.sha256}`,
              "COC"
            )
          }
        >
          {copied === "COC" ? "✓ Copied!" : "📋 Copy Report"}
        </button>
      </div>
    </div>
  );
}
