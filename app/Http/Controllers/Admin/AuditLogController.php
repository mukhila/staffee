<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'user_id' => 'nullable|integer',
            'event'   => 'nullable|string|max:40',
            'type'    => 'nullable|string|max:255',
            'from'    => 'nullable|date',
            'to'      => 'nullable|date|after_or_equal:from',
            'q'       => 'nullable|string|max:100',
        ]);

        $logs = AuditLog::with('user:id,name')
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['event'] ?? null, fn ($q, $event) => $q->where('event', $event))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('auditable_type', $type))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('description', 'like', "%{$term}%")
                        ->orWhere('ip_address', 'like', "%{$term}%")
                        ->orWhere('auditable_id', is_numeric($term) ? (int) $term : -1);
                });
            })
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);

        $types = AuditLog::query()
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->mapWithKeys(fn ($type) => [$type => Str::headline(class_basename($type))]);

        $events = AuditLog::EVENT_LABELS;

        return view('admin.audit-logs.index', compact('logs', 'users', 'types', 'events', 'filters'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user:id,name,email');

        // Everything else that happened to the same record, for context.
        $history = $auditLog->auditable_type
            ? AuditLog::with('user:id,name')
                ->where('auditable_type', $auditLog->auditable_type)
                ->where('auditable_id', $auditLog->auditable_id)
                ->orderByDesc('id')
                ->limit(25)
                ->get()
            : collect();

        return view('admin.audit-logs.show', compact('auditLog', 'history'));
    }
}
