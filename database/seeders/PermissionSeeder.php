<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class PermissionSeeder extends Seeder
{
    /**
     * All permissions.
     * Format: slug => [name, category, description]
     */
    private array $permissions = [
        // Staff Management
        'view-staff'             => ['View Staff',            'staff_management',  'View staff list and profiles'],
        'create-staff'           => ['Create Staff',          'staff_management',  'Add new employees'],
        'edit-staff'             => ['Edit Staff',            'staff_management',  'Edit employee details'],
        'delete-staff'           => ['Delete Staff',          'staff_management',  'Remove employees from the system'],
        'bulk-import-staff'      => ['Bulk Import Staff',     'staff_management',  'Import employees via CSV'],

        // Project Management
        'view-projects'          => ['View Projects',         'project_management','View all projects'],
        'create-project'         => ['Create Project',        'project_management','Create new projects'],
        'edit-project'           => ['Edit Project',          'project_management','Modify project details and team'],
        'archive-project'        => ['Archive Project',       'project_management','Close or archive projects'],

        // Task Management
        'view-tasks'             => ['View Tasks',            'task_management',   'View all tasks'],
        'create-task'            => ['Create Task',           'task_management',   'Create tasks within projects'],
        'assign-task'            => ['Assign Task',           'task_management',   'Assign tasks to team members'],
        'update-task'            => ['Update Task',           'task_management',   'Update task status and details'],
        'delete-task'            => ['Delete Task',           'task_management',   'Delete tasks'],

        // Leave Management
        'submit-leave'           => ['Submit Leave',          'leave_management',  'Apply for leave requests'],
        'approve-leave'          => ['Approve Leave',         'leave_management',  'Approve or reject leave requests'],
        'manage-leave'           => ['Manage Leave',          'leave_management',  'View and manage all leave records'],

        // Attendance
        'view-attendance'        => ['View Attendance',       'attendance',        'View own and team attendance'],
        'manage-attendance'      => ['Manage Attendance',     'attendance',        'Edit attendance records'],
        'override-attendance'    => ['Override Attendance',   'attendance',        'Override attendance status for any employee'],

        // Reports
        'view-reports'           => ['View Reports',          'reports',           'Access reports and analytics dashboard'],
        'export-reports'         => ['Export Reports',        'reports',           'Export reports to CSV or PDF'],
        'view-payroll-reports'   => ['View Payroll Reports',  'reports',           'Access payroll and salary data'],

        // Admin Settings
        'manage-system-settings' => ['Manage System Settings','admin_settings',    'Change company-wide application settings'],
        'view-audit-logs'        => ['View Audit Logs',       'admin_settings',    'Access system audit and activity logs'],

        // Employee Monitoring
        'view-monitoring'        => ['View Monitoring',       'monitoring',        'Access the live monitoring dashboard and activity logs'],
        'view-screenshots'       => ['View Screenshots',      'monitoring',        'View captured employee screenshots'],
        'manage-monitoring'      => ['Manage Monitoring',     'monitoring',        'Configure monitoring settings and manage agent tokens'],
    ];

    /**
     * Default permission slugs assigned to each non-admin role.
     * Admin gets everything via Gate::before() — no DB assignment needed.
     */
    private array $roleDefaults = [
        'pm' => [
            'view-staff',
            'view-projects', 'create-project', 'edit-project', 'archive-project',
            'view-tasks', 'create-task', 'assign-task', 'update-task', 'delete-task',
            'submit-leave', 'approve-leave',
            'view-attendance',
            'view-reports', 'export-reports',
        ],
        'staff' => [
            'view-tasks', 'update-task',
            'submit-leave',
            'view-attendance',
        ],
    ];

    public function run(): void
    {
        // 1. Upsert permissions
        foreach ($this->permissions as $slug => [$name, $category, $description]) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                compact('name', 'category', 'description')
            );
        }
        $this->command->info('✓ Seeded ' . count($this->permissions) . ' permissions.');

        // 2. Ensure base roles exist
        foreach (['pm' => 'Project Manager', 'staff' => 'Staff'] as $slug => $name) {
            Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'is_active' => true]
            );
        }

        // 3. Assign default permissions and flush cache
        foreach ($this->roleDefaults as $roleSlug => $slugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (!$role) {
                continue;
            }
            $ids = Permission::whereIn('slug', $slugs)->pluck('id')->toArray();
            $role->permissions()->sync($ids);
            Cache::forget("role_permissions:{$roleSlug}");
            $this->command->info("✓ Assigned " . count($ids) . " permissions → {$roleSlug}");
        }
    }
}
