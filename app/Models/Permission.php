<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'description'];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public static function categories(): array
    {
        return [
            'staff_management' => 'Staff Management',
            'project_management' => 'Project Management',
            'task_management' => 'Task Management',
            'leave_management' => 'Leave Management',
            'attendance' => 'Attendance',
            'reports' => 'Reports & Analytics',
            'admin_settings' => 'Admin Settings',
        ];
    }
}
