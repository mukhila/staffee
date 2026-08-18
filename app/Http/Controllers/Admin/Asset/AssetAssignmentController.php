<?php

namespace App\Http\Controllers\Admin\Asset;

use App\Http\Controllers\Controller;
use App\Models\Asset\AssetAssignment;
use App\Services\Asset\AssetService;
use Illuminate\Http\Request;

class AssetAssignmentController extends Controller
{
    public function __construct(private AssetService $assetService) {}

    public function returnAsset(Request $request, AssetAssignment $assignment)
    {
        $validated = $request->validate([
            'return_condition' => 'required|in:good,damaged,lost',
            'return_notes'     => 'nullable|string',
        ]);

        try {
            $this->assetService->returnAsset(
                $assignment,
                $validated['return_condition'],
                $validated['return_notes'] ?? null,
                auth()->id()
            );
            return redirect()->route('admin.assets.show', $assignment->asset_id)
                ->with('success', 'Asset returned successfully.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
