<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Leave\CompOffCredit;
use App\Services\Leave\CompOffService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CompOffController extends Controller
{
    public function __construct(private readonly CompOffService $service) {}

    public function index()
    {
        $credits = CompOffCredit::with('reviewer:id,name')
            ->where('user_id', auth()->id())
            ->orderByDesc('worked_date')
            ->paginate(20);

        return view('staff.leaves.comp-off', compact('credits'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'worked_date' => 'required|date|before_or_equal:today',
            'reason'      => 'required|string|max:500',
        ]);

        try {
            $this->service->request($request->user(), $data['worked_date'], $data['reason']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('staff.comp-off.index')->with('success', 'Comp-off claim submitted.');
    }
}
