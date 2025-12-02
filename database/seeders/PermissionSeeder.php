<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            'Staff',
            'Departments',
            'Roles',
            'Projects',
            'Tasks',
            'Bugs',
            'Test Cases',
            'DSR',
            'Attendance',
            'Settings'
        ];

        $actions = ['view', 'create', 'edit', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $slug = strtolower(str_replace(' ', '_', $module)) . '.' . $action;
                $name = ucfirst($action) . ' ' . $module;
                
                Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name]
                );
            }
        }


        // Assign all permissions to Admin role
        $adminRole = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Administrator with full access', 'is_active' => true]
        );

        $allPermissions = Permission::all();
        $adminRole->permissions()->sync($allPermissions);
    }
}
