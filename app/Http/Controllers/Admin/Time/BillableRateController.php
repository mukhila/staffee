<?php

namespace App\Http\Controllers\Admin\Time;

use App\Http\Controllers\Controller;
use App\Models\BillableRate;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class BillableRateController extends Controller
{
    public function index(Request $request)
    {
        $rates = BillableRate::with(['user', 'project', 'creator'])
            ->when($request->type, fn ($q) => $q->where('rate_type', $request->type))
            ->orderByDesc('effective_from')
            ->paginate(30)
            ->withQueryString();

        return view('admin.time.rates.index', compact('rates'));
    }

    public function create()
    {
        $users    = User::active()->excludeAdmin()->orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        return view('admin.time.rates.create', compact('users', 'projects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'rate_type'      => 'required|in:global,user,project,user_project',
            'user_id'        => 'nullable|required_if:rate_type,user|required_if:rate_type,user_project|exists:users,id',
            'project_id'     => 'nullable|required_if:rate_type,project|required_if:rate_type,user_project|exists:projects,id',
            'hourly_rate'    => 'required|numeric|min:0|max:9999.99',
            'currency'       => 'required|string|size:3',
            'effective_from' => 'required|date',
            'effective_to'   => 'nullable|date|after_or_equal:effective_from',
            'notes'          => 'nullable|string|max:500',
        ]);

        $data['created_by'] = auth()->id();

        // Close any open rate of same type/user/project on the start date
        $this->closeExistingRate($data);

        BillableRate::create($data);
        return redirect()->route('admin.time.rates.index')->with('success', 'Billable rate created.');
    }

    public function destroy(BillableRate $rate)
    {
        $rate->delete();
        return back()->with('success', 'Rate deleted.');
    }

    private function closeExistingRate(array $data): void
    {
        BillableRate::where('rate_type', $data['rate_type'])
            ->where('user_id', $data['user_id'] ?? null)
            ->where('project_id', $data['project_id'] ?? null)
            ->whereNull('effective_to')
            ->update(['effective_to' => (new \Carbon\Carbon($data['effective_from']))->subDay()->toDateString()]);
    }
}
