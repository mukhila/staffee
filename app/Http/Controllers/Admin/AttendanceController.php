<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attendances = \App\Models\Attendance::with('user')->latest()->get();
        return view('admin.attendances.index', compact('attendances'));
    }

    public function edit(\App\Models\Attendance $attendance)
    {
        return view('admin.attendances.edit', compact('attendance'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Attendance $attendance)
    {
        $request->validate([
            'check_in' => 'nullable|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s',
            'status' => 'required|string',
        ]);

        $attendance->update($request->all());

        return redirect()->route('admin.attendances.index')->with('success', 'Attendance updated successfully.');
    }

}
