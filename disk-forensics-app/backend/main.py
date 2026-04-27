from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
import os

from routers import upload, filesystem, recovery, timeline, hash_router

app = FastAPI(
    title="Disk Image Analyzer",
    description="A forensic tool to parse disk images, recover deleted files, and visualize file timelines.",
    version="1.0.0",
    docs_url="/api/docs",
    redoc_url="/api/redoc",
)

# ── CORS (allow React dev server) ──────────────────────────────────────────────
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:3000", "http://localhost:5173"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Routers ────────────────────────────────────────────────────────────────────
app.include_router(upload.router)
app.include_router(filesystem.router)
app.include_router(recovery.router)
app.include_router(timeline.router)
app.include_router(hash_router.router)


@app.get("/api/health")
async def health():
    try:
        import pytsk3
        tsk_status = "available"
    except ImportError:
        tsk_status = "not installed (fallback FAT32 parser active)"

    return {
        "status": "ok",
        "pytsk3": tsk_status,
        "message": "Disk Image Analyzer API is running.",
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)
