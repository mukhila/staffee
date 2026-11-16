<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave\CompOffCredit;
use App\Services\Leave\CompOffService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CompOffController extends Controller
{
    public function __construct(private readonly CompOffService $service) {}

    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $credits = CompOffCredit::with(['user:id,name,department_id', 'user.department:id,name', 'reviewer:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.leaves.comp-off', compact('credits', 'status'));
    }

    public function approve(Request $request, CompOffCredit $credit)
    {
        $data = $request->validate(['review_notes' => 'nullable|string|max:500']);

        try {
            $this->service->approve($credit, $request->user(), $data['review_notes'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Comp-off approved and credited to the leave balance.');
    }

    public function reject(Request $request, CompOffCredit $credit)
    {
        $data = $request->validate(['review_notes' => 'required|string|max:500']);

        try {
            $this->service->reject($credit, $request->user(), $data['review_notes']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Comp-off claim rejected.');
    }
}
