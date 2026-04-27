import { useState, useEffect } from "react";
import { api, TimelineEvent } from "../api/client";

interface Props {
  imageId: string;
}

const EVENT_COLORS = {
  created: "#27ae60",
  modified: "#e67e22",
  accessed: "#2980b9",
};

const EVENT_ICONS = {
  created: "✨",
  modified: "✏️",
  accessed: "👁",
};

function formatSize(bytes: number): string {
  if (bytes === 0) return "0 B";
  const units = ["B", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / 1024 ** i).toFixed(1)} ${units[i]}`;
}

function groupByDay(events: TimelineEvent[]): Record<string, TimelineEvent[]> {
  const groups: Record<string, TimelineEvent[]> = {};
  for (const e of events) {
    const day = e.timestamp.split("T")[0];
    if (!groups[day]) groups[day] = [];
    groups[day].push(e);
  }
  return groups;
}

export default function Timeline({ imageId }: Props) {
  const [events, setEvents] = useState<TimelineEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<string>("all");
  const [includeDeleted, setIncludeDeleted] = useState(true);
  const [search, setSearch] = useState("");

  useEffect(() => {
    setLoading(true);
    api.getTimeline(imageId, {
      event_type: filter === "all" ? undefined : filter,
      include_deleted: includeDeleted,
      limit: 500,
    })
      .then(setEvents)
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false));
  }, [imageId, filter, includeDeleted]);

  const filtered = search
    ? events.filter((e) =>
        e.filename.toLowerCase().includes(search.toLowerCase()) ||
        e.path.toLowerCase().includes(search.toLowerCase())
      )
    : events;

  const grouped = groupByDay(filtered);
  const days = Object.keys(grouped).sort().reverse();

  return (
    <div className="panel">
      <div className="timeline-controls">
        <div className="filter-tabs">
          {["all", "created", "modified", "accessed"].map((t) => (
            <button
              key={t}
              className={`filter-tab ${filter === t ? "active" : ""}`}
              style={filter === t && t !== "all" ? { borderColor: EVENT_COLORS[t as keyof typeof EVENT_COLORS] } : {}}
              onClick={() => setFilter(t)}
            >
              {t === "all" ? "All Events" : `${EVENT_ICONS[t as keyof typeof EVENT_ICONS]} ${t.charAt(0).toUpperCase() + t.slice(1)}`}
            </button>
          ))}
        </div>
        <label className="toggle-label">
          <input
            type="checkbox"
            checked={includeDeleted}
            onChange={(e) => setIncludeDeleted(e.target.checked)}
          />
          Show deleted files
        </label>
      </div>

      <input
        className="search-box"
        placeholder="🔍 Search filename or path…"
        value={search}
        onChange={(e) => setSearch(e.target.value)}
      />

      {loading && <div className="panel-loading">Building timeline…</div>}
      {error && <div className="panel-error">Error: {error}</div>}

      {!loading && filtered.length === 0 && (
        <p className="empty">No events found.</p>
      )}

      {!loading && (
        <div className="timeline-summary">
          <span>Showing <strong>{filtered.length}</strong> events across <strong>{days.length}</strong> days</span>
        </div>
      )}

      <div className="timeline-body">
        {days.map((day) => (
          <div key={day} className="timeline-day">
            <div className="day-header">
              📅 {new Date(day + "T12:00:00Z").toLocaleDateString(undefined, {
                weekday: "long", year: "numeric", month: "long", day: "numeric"
              })}
              <span className="day-count">{grouped[day].length} events</span>
            </div>
            <div className="day-events">
              {grouped[day].map((e, i) => (
                <div
                  key={i}
                  className={`timeline-event ${e.is_deleted ? "event-deleted" : ""}`}
                  style={{ borderLeftColor: EVENT_COLORS[e.event_type] }}
                >
                  <div className="event-time">
                    {new Date(e.timestamp).toLocaleTimeString()}
                  </div>
                  <div className="event-icon">{EVENT_ICONS[e.event_type]}</div>
                  <div className="event-details">
                    <div className="event-filename">
                      {e.filename}
                      {e.is_deleted && <span className="deleted-badge">DELETED</span>}
                    </div>
                    <div className="event-path">{e.path}</div>
                  </div>
                  <div className="event-meta">
                    <span
                      className="event-type-badge"
                      style={{ background: EVENT_COLORS[e.event_type] }}
                    >
                      {e.event_type}
                    </span>
                    <span className="event-size">{formatSize(e.size)}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
