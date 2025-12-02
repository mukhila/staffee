<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = \App\Models\Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = \App\Models\Permission::all();
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        if (empty($data['department_id'])) {
            $data['department_id'] = null;
        }

        $role = \App\Models\Role::create($data);
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(\App\Models\Role $role)
    {
        $permissions = \App\Models\Permission::all();
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'slug' => 'required|string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        if (empty($data['department_id'])) {
            $data['department_id'] = null;
        }

        $role->update($data);
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        } else {
            $role->permissions()->detach();
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(\App\Models\Role $role)
    {
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }

    public function getRolesByDepartment(\Illuminate\Http\Request $request)
    {
        $departmentId = $request->query('department_id');
        $roles = \App\Models\Role::where('department_id', $departmentId)->where('is_active', true)->get();
        return response()->json($roles);
    }

}
