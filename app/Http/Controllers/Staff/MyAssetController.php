<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Asset\AssetAssignment;

class MyAssetController extends Controller
{
    public function index()
    {
        $assignments = AssetAssignment::with('asset')
            ->where('user_id', auth()->id())
            ->where('is_current', true)
            ->latest('assigned_at')
            ->get();

        return view('staff.my-assets.index', compact('assignments'));
    }
}
