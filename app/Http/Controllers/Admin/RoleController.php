<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('category')->orderBy('name')->get()
            ->groupBy('category');
        $categories  = Permission::categories();
        return view('admin.roles.create', compact('permissions', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:roles',
            'slug'        => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        if (empty($data['department_id'])) {
            $data['department_id'] = null;
        }

        $role = Role::create($data);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
            Cache::forget("role_permissions:{$role->slug}");
        }

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissions    = Permission::orderBy('category')->orderBy('name')->get()->groupBy('category');
        $categories     = Permission::categories();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'categories', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:roles,name,' . $role->id,
            'slug'        => 'required|string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string',
        ]);

        $data              = $request->all();
        $data['is_active'] = $request->has('is_active');
        if (empty($data['department_id'])) {
            $data['department_id'] = null;
        }

        $role->update($data);

        $role->permissions()->sync($request->input('permissions', []));
        Cache::forget("role_permissions:{$role->slug}");

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        Cache::forget("role_permissions:{$role->slug}");
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }

    // ─── Permission Matrix ────────────────────────────────────────────────────

    public function matrix()
    {
        $roles       = Role::with('permissions')->get();
        $permissions = Permission::orderBy('category')->orderBy('name')->get()->groupBy('category');
        $categories  = Permission::categories();

        // Build a quick-lookup: [role_id][permission_id] => bool
        $matrix = [];
        foreach ($roles as $role) {
            $matrix[$role->id] = $role->permissions->pluck('id')->flip()->toArray();
        }

        return view('admin.roles.matrix', compact('roles', 'permissions', 'categories', 'matrix'));
    }

    public function updateMatrix(Request $request)
    {
        // payload: permissions[role_id][] = permission_id
        $payload = $request->input('permissions', []);
        $roles   = Role::all();

        foreach ($roles as $role) {
            $permIds = $payload[$role->id] ?? [];
            $role->permissions()->sync($permIds);
            Cache::forget("role_permissions:{$role->slug}");
        }

        return redirect()->route('admin.roles.matrix')
            ->with('success', 'Permission matrix updated successfully.');
    }

    // ─── API ─────────────────────────────────────────────────────────────────

    public function getRolesByDepartment(Request $request)
    {
        $roles = Role::where('department_id', $request->query('department_id'))
            ->where('is_active', true)
            ->get();
        return response()->json($roles);
    }
}
