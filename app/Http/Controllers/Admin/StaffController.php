<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index()
    {
        $this->authorize('view-staff');

        $staff = \App\Models\User::where('role', '!=', 'admin')->get();
        return view('admin.staff.index', compact('staff'));
    }

    public function create()
    {
        $this->authorize('create-staff');

        $roles = \App\Models\Role::where('is_active', true)->get();
        $departments = \App\Models\Department::where('is_active', true)->get();
        return view('admin.staff.create', compact('roles', 'departments'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $this->authorize('create-staff');

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
        $this->authorize('edit-staff');

        $roles = \App\Models\Role::where('is_active', true)->get();
        $departments = \App\Models\Department::where('is_active', true)->get();
        return view('admin.staff.edit', compact('staff', 'roles', 'departments'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\User $staff)
    {
        $this->authorize('edit-staff');

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
        $this->authorize('delete-staff');

        $staff->delete();
        return redirect()->route('admin.staff.index')->with('success', 'Staff deleted successfully.');
    }

    public function importForm()
    {
        $this->authorize('create-staff');
        $roles = \App\Models\Role::where('is_active', true)->get();
        $departments = \App\Models\Department::where('is_active', true)->get();
        return view('admin.staff.import', compact('roles', 'departments'));
    }

    public function import(\Illuminate\Http\Request $request)
    {
        $this->authorize('create-staff');

        $request->validate([
            'csv_file'      => 'required|file|mimes:csv,txt|max:2048',
            'default_role'  => 'required|exists:roles,slug',
            'default_dept'  => 'required|exists:departments,id',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle); // skip header row

        // Normalize headers
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $created = 0;
        $skipped = [];
        $rowNum  = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $data = array_combine($headers, array_pad($row, count($headers), null));

            $email = trim($data['email'] ?? '');
            $name  = trim($data['name'] ?? '');

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped[] = "Row {$rowNum}: invalid email.";
                continue;
            }
            if (!$name) {
                $skipped[] = "Row {$rowNum}: missing name.";
                continue;
            }
            if (\App\Models\User::where('email', $email)->exists()) {
                $skipped[] = "Row {$rowNum}: {$email} already exists.";
                continue;
            }

            $password = trim($data['password'] ?? '') ?: 'Password@123';
            $role     = trim($data['role'] ?? '') ?: $request->default_role;
            $deptId   = trim($data['department_id'] ?? '') ?: $request->default_dept;

            \App\Models\User::create([
                'name'          => $name,
                'email'         => $email,
                'password'      => bcrypt($password),
                'role'          => $role,
                'department_id' => $deptId,
                'phone'         => trim($data['phone'] ?? ''),
            ]);
            $created++;
        }
        fclose($handle);

        $msg = "{$created} staff imported successfully.";
        if ($skipped) {
            $msg .= ' Skipped: ' . implode('; ', $skipped);
        }

        return redirect()->route('admin.staff.index')->with('success', $msg);
    }
}
