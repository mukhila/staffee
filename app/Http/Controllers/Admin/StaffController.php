<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $staff = \App\Models\User::where('role', '!=', 'admin')->get();
        return view('admin.staff.index', compact('staff'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = \App\Models\Role::where('is_active', true)->get();
        $departments = \App\Models\Department::where('is_active', true)->get();
        return view('admin.staff.create', compact('roles', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|exists:roles,slug',
            'department_id' => 'required|exists:departments,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'reporting_to' => 'nullable|exists:users,id',
        ]);

        \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
            'department_id' => $request->department_id,
            'phone' => $request->phone,
            'address' => $request->address,
            'reporting_to' => $request->reporting_to,
        ]);

        return redirect()->route('admin.staff.index')->with('success', 'Staff created successfully.');
    }

    public function edit(\App\Models\User $staff)
    {
        $roles = \App\Models\Role::where('is_active', true)->get();
        $departments = \App\Models\Department::where('is_active', true)->get();
        return view('admin.staff.edit', compact('staff', 'roles', 'departments'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\User $staff)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $staff->id,
            'role' => 'required|exists:roles,slug',
            'department_id' => 'required|exists:departments,id',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'reporting_to' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'department_id' => $request->department_id,
            'phone' => $request->phone,
            'address' => $request->address,
            'reporting_to' => $request->reporting_to,
            'is_active' => $request->has('is_active') ? $request->is_active : 0,
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $data['password'] = bcrypt($request->password);
        }

        $staff->update($data);

        return redirect()->route('admin.staff.index')->with('success', 'Staff updated successfully.');
    }

    public function destroy(\App\Models\User $staff)
    {
        $staff->delete();
        return redirect()->route('admin.staff.index')->with('success', 'Staff deleted successfully.');
    }
}
