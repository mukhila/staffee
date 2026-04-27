const BASE_URL = "http://localhost:8000/api";

export interface UploadResponse {
  image_id: string;
  filename: string;
  file_size: number;
  md5: string;
  sha256: string;
  message: string;
}

export interface FileEntry {
  name: string;
  path: string;
  size: number;
  is_directory: boolean;
  is_deleted: boolean;
  created: string | null;
  modified: string | null;
  accessed: string | null;
  inode: number | null;
  extension: string | null;
}

export interface FileSystemInfo {
  image_id: string;
  fs_type: string;
  block_size: number;
  total_blocks: number;
  total_size: number;
  files: FileEntry[];
}

export interface DeletedFile {
  name: string;
  path: string;
  size: number;
  inode: number | null;
  modified: string | null;
  extension: string | null;
  recoverable: boolean;
}

export interface TimelineEvent {
  timestamp: string;
  filename: string;
  path: string;
  event_type: "created" | "modified" | "accessed";
  size: number;
  is_deleted: boolean;
}

export interface HashResult {
  image_id: string;
  filename: string;
  md5: string;
  sha1: string;
  sha256: string;
  file_size: number;
  computed_at: string;
}

async function request<T>(path: string, options?: RequestInit): Promise<T> {
  const res = await fetch(`${BASE_URL}${path}`, options);
  if (!res.ok) {
    const err = await res.json().catch(() => ({ detail: res.statusText }));
    throw new Error(err.detail || "Request failed");
  }
  return res.json();
}

export const api = {
  uploadImage: async (file: File): Promise<UploadResponse> => {
    const fd = new FormData();
    fd.append("file", file);
    return request<UploadResponse>("/upload", { method: "POST", body: fd });
  },

  getFilesystem: (imageId: string): Promise<FileSystemInfo> =>
    request<FileSystemInfo>(`/filesystem/${imageId}`),

  getDeletedFiles: (imageId: string): Promise<DeletedFile[]> =>
    request<DeletedFile[]>(`/recovery/${imageId}/deleted`),

  getTimeline: (
    imageId: string,
    params?: { event_type?: string; include_deleted?: boolean; limit?: number }
  ): Promise<TimelineEvent[]> => {
    const qs = new URLSearchParams();
    if (params?.event_type) qs.set("event_type", params.event_type);
    if (params?.include_deleted !== undefined)
      qs.set("include_deleted", String(params.include_deleted));
    if (params?.limit) qs.set("limit", String(params.limit));
    return request<TimelineEvent[]>(`/timeline/${imageId}?${qs}`);
  },

  getHashes: (imageId: string): Promise<HashResult> =>
    request<HashResult>(`/hash/${imageId}`),

  verifyHash: (imageId: string, expectedHash: string, algorithm: string) =>
    request(`/hash/${imageId}/verify?expected_hash=${expectedHash}&algorithm=${algorithm}`, {
      method: "POST",
    }),

  getRecoverUrl: (imageId: string, inode: number) =>
    `${BASE_URL}/recovery/${imageId}/recover/${inode}`,
};
