<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset\Asset;
use App\Models\User;
use App\Services\Asset\AssetService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(private AssetService $assetService) {}

    public function index(Request $request)
    {
        $query = Asset::with('currentAssignment.user')->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assets = $query->paginate(20)->withQueryString();

        return view('admin.assets.index', compact('assets'));
    }

    public function create()
    {
        return view('admin.assets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_tag'     => 'required|string|max:50|unique:assets,asset_tag',
            'name'          => 'required|string|max:150',
            'category'      => 'required|in:laptop,desktop,phone,tablet,monitor,peripheral,vehicle,furniture,software_license,other',
            'brand'         => 'nullable|string|max:100',
            'model_number'  => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:150',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date',
            'location'      => 'nullable|string|max:150',
            'notes'         => 'nullable|string',
        ]);

        Asset::create($validated);

        return redirect()->route('admin.assets.index')->with('success', 'Asset created successfully.');
    }

    public function show(Asset $asset)
    {
        $asset->load(['assignments.user', 'assignments.assignedBy', 'currentAssignment.user']);
        $users = User::orderBy('name')->get();

        return view('admin.assets.show', compact('asset', 'users'));
    }

    public function edit(Asset $asset)
    {
        return view('admin.assets.edit', compact('asset'));
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'asset_tag'     => 'required|string|max:50|unique:assets,asset_tag,' . $asset->id,
            'name'          => 'required|string|max:150',
            'category'      => 'required|in:laptop,desktop,phone,tablet,monitor,peripheral,vehicle,furniture,software_license,other',
            'brand'         => 'nullable|string|max:100',
            'model_number'  => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:150',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date',
            'status'        => 'required|in:available,assigned,in_repair,retired,lost',
            'location'      => 'nullable|string|max:150',
            'notes'         => 'nullable|string',
        ]);

        $asset->update($validated);

        return redirect()->route('admin.assets.show', $asset)->with('success', 'Asset updated successfully.');
    }

    public function assign(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($validated['user_id']);

        try {
            $this->assetService->assign($asset, $user, auth()->id());
            return redirect()->route('admin.assets.show', $asset)->with('success', 'Asset assigned successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function repair(Request $request, Asset $asset)
    {
        try {
            $this->assetService->sendForRepair($asset, auth()->id());
            return redirect()->route('admin.assets.show', $asset)->with('success', 'Asset sent for repair.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
