<?php

namespace App\Services\Import\Types;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\Import\Concerns\ParsesValues;
use App\Services\Import\ImportType;
use App\Services\Import\RowError;
use Illuminate\Support\Str;

class StaffImport implements ImportType
{
    use ParsesValues;

    private ?array $roleSlugs = null;
    private ?array $departments = null;

    public function key(): string { return 'staff'; }

    public function label(): string { return 'Staff'; }

    public function description(): string
    {
        return 'Create staff accounts. Each gets a random password and signs in for the first time with "Forgot password". Existing emails are skipped.';
    }

    public function columns(): array
    {
        return [
            'name'          => ['required' => true,  'help' => 'Full name',                                   'example' => 'Priya Raman'],
            'email'         => ['required' => true,  'help' => 'Work email; must not already exist',          'example' => 'priya@example.com'],
            'role'          => ['required' => false, 'help' => 'Role slug, e.g. staff or pm; default staff',  'example' => 'staff'],
            'department'    => ['required' => false, 'help' => 'Department name (must exist)',                'example' => 'Engineering'],
            'phone'         => ['required' => false, 'help' => 'Optional',                                    'example' => '+91 98765 43210'],
            'manager_email' => ['required' => false, 'help' => 'Email of their manager (must exist)',         'example' => 'lead@example.com'],
        ];
    }

    public function validateRow(array $row, array &$seen): array
    {
        $name  = $this->required($row, 'name');
        $email = strtolower($this->required($row, 'email'));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RowError("\"{$email}\" is not a valid email address.");
        }
        $this->once($seen, $email, "The email {$email}");

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw new RowError("{$email} already has an account.");
        }

        $role = ($row['role'] ?? '') === '' ? 'staff' : strtolower($row['role']);
        if (!in_array($role, $this->roleSlugs(), true)) {
            throw new RowError("Role \"{$row['role']}\" does not exist. Use one of: " . implode(', ', $this->roleSlugs()) . '.');
        }

        $departmentId = null;
        if (($row['department'] ?? '') !== '') {
            $departmentId = $this->departments()[mb_strtolower($row['department'])]
                ?? throw new RowError("Department \"{$row['department']}\" does not exist. Import departments first.");
        }

        $managerId = ($row['manager_email'] ?? '') !== ''
            ? $this->userByEmail($row['manager_email'], 'manager_email')->id
            : null;

        return [
            'name'          => mb_substr($name, 0, 255),
            'email'         => $email,
            'role'          => $role,
            'department_id' => $departmentId,
            'phone'         => ($row['phone'] ?? '') !== '' ? mb_substr($row['phone'], 0, 20) : null,
            'reporting_to'  => $managerId,
        ];
    }

    public function import(array $clean, int $actorId): string
    {
        // Checked again here in case someone created the account after the preview.
        if (User::whereRaw('LOWER(email) = ?', [$clean['email']])->exists()) {
            return 'skipped';
        }

        User::create($clean + [
            // Never a shared default password; the person sets their own via "Forgot password".
            'password'  => Str::password(32),
            'is_active' => true,
        ]);

        return 'created';
    }

    private function roleSlugs(): array
    {
        return $this->roleSlugs ??= array_values(array_unique(array_merge(
            ['admin', 'staff', 'pm'],
            Role::where('is_active', true)->pluck('slug')->all()
        )));
    }

    private function departments(): array
    {
        return $this->departments ??= Department::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id])
            ->all();
    }
}
