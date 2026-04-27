from fastapi import APIRouter, Query
from typing import List, Optional
from models.schemas import TimelineEvent
from routers.filesystem import _get_analysis

router = APIRouter(prefix="/api/timeline", tags=["timeline"])


@router.get("/{image_id}", response_model=List[TimelineEvent])
async def get_timeline(
    image_id: str,
    event_type: Optional[str] = Query(None, description="Filter by: created, modified, accessed"),
    include_deleted: bool = Query(True, description="Include deleted file events"),
    limit: int = Query(500, ge=1, le=5000),
):
    """
    Return filesystem events sorted by timestamp (newest first).
    Useful for reconstructing user/system activity.
    """
    data = _get_analysis(image_id)
    events: List[TimelineEvent] = data["timeline"]

    if event_type:
        events = [e for e in events if e.event_type == event_type]

    if not include_deleted:
        events = [e for e in events if not e.is_deleted]

    # Sort by timestamp descending
    events_sorted = sorted(events, key=lambda e: e.timestamp, reverse=True)

    return events_sorted[:limit]
