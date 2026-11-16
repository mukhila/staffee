<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRegularization;
use App\Services\Shift\RegularizationService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AttendanceRegularizationController extends Controller
{
    public function __construct(private readonly RegularizationService $service) {}

    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $requests = AttendanceRegularization::with(['user:id,name,department_id', 'user.department:id,name', 'reviewer:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.attendances.regularizations', compact('requests', 'status'));
    }

    public function approve(Request $request, AttendanceRegularization $regularization)
    {
        $data = $request->validate(['review_notes' => 'nullable|string|max:500']);

        try {
            $this->service->approve($regularization, $request->user(), $data['review_notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Request approved and attendance updated.');
    }

    public function reject(Request $request, AttendanceRegularization $regularization)
    {
        $data = $request->validate(['review_notes' => 'required|string|max:500']);

        try {
            $this->service->reject($regularization, $request->user(), $data['review_notes']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Request rejected.');
    }
}
