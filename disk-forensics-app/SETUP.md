# Disk Image Analyzer — Setup Guide

## Project Structure

```
disk-forensics-app/
├── backend/            # FastAPI (Python)
│   ├── main.py
│   ├── requirements.txt
│   ├── models/         # Pydantic schemas
│   ├── routers/        # API route handlers
│   └── services/       # Core forensics logic
└── frontend/           # React + TypeScript (Vite)
    ├── src/
    │   ├── App.tsx
    │   ├── api/        # API client
    │   └── components/ # UI components
    └── package.json
```

---

## 1. Backend Setup

```bash
cd backend

# Create a virtual environment
python -m venv venv
source venv/bin/activate      # Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Start the server
python main.py
# → Runs at http://localhost:8000
# → Swagger docs at http://localhost:8000/api/docs
```

### Optional: Install pytsk3 (The Sleuth Kit)
For full NTFS/ext4/HFS+ support and deleted file recovery:
```bash
# macOS
brew install sleuthkit
pip install pytsk3

# Ubuntu/Debian
sudo apt install libtsk-dev
pip install pytsk3

# Windows
# Download pre-built wheels from: https://github.com/py4n6/pytsk/releases
pip install pytsk3-<version>-win.whl
```

Without pytsk3, the app falls back to a built-in raw FAT32 parser.

---

## 2. Frontend Setup

```bash
cd frontend

npm install
npm run dev
# → Runs at http://localhost:3000
```

---

## 3. API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST   | /api/upload | Upload a disk image |
| GET    | /api/filesystem/{id} | Parse the filesystem |
| GET    | /api/filesystem/{id}/tree | Browse files at a path |
| GET    | /api/recovery/{id}/deleted | List deleted files |
| GET    | /api/recovery/{id}/recover/{inode} | Download recovered file |
| GET    | /api/timeline/{id} | Get MAC time events |
| GET    | /api/hash/{id} | Get image hashes |
| POST   | /api/hash/{id}/verify | Verify hash integrity |
| GET    | /api/health | Health check |

---

## 4. Supported Image Formats

| Format | Extension | Notes |
|--------|-----------|-------|
| Raw DD | .dd, .raw | Standard raw disk dump |
| IMG    | .img      | Common raw format |
| ISO    | .iso      | CD/DVD images |
| Binary | .bin      | Raw binary dumps |
| E01    | .e01      | EnCase (requires pytsk3) |

---

## 5. Creating a Test Image

```bash
# Create a small FAT32 test image (Linux/macOS)
dd if=/dev/zero of=test.img bs=1M count=16
mkfs.fat -F 32 test.img

# Mount and add files
sudo mount test.img /mnt/test
echo "Secret document" | sudo tee /mnt/test/secret.txt
sudo mkdir /mnt/test/photos
sudo umount /mnt/test

# Now delete a file to test recovery
sudo mount test.img /mnt/test
sudo rm /mnt/test/secret.txt
sudo umount /mnt/test

# Upload test.img to the analyzer
```

---

## 6. Tech Stack

- **Backend**: Python 3.11+, FastAPI, Pydantic v2, uvicorn
- **Forensics**: pytsk3 (The Sleuth Kit) / built-in FAT32 parser
- **Frontend**: React 18, TypeScript, Vite
- **Styling**: Pure CSS (no UI library dependency)
