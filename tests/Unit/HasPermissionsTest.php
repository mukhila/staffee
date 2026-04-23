<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasPermissionsTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeRole(string $slug, array $permissionSlugs = []): Role
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);

        foreach ($permissionSlugs as $ps) {
            $perm = Permission::firstOrCreate(
                ['slug' => $ps],
                ['name' => ucwords(str_replace('-', ' ', $ps)), 'category' => 'test']
            );
            $role->permissions()->attach($perm->id);
        }

        return $role;
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    // ─── isAdmin / isSuperAdmin ───────────────────────────────────────────────

    public function test_admin_is_identified_correctly(): void
    {
        $admin = $this->makeUser('admin');
        $staff = $this->makeUser('staff');

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertFalse($staff->isAdmin());
        $this->assertFalse($staff->isSuperAdmin());
    }

    // ─── hasPermission ────────────────────────────────────────────────────────

    public function test_admin_has_every_permission_without_db_assignment(): void
    {
        $admin = $this->makeUser('admin');
        // Admin bypass is in Gate::before; hasPermission() checks isAdmin() first
        $this->assertTrue($admin->hasPermission('create-staff'));
        $this->assertTrue($admin->hasPermission('some-made-up-permission'));
    }

    public function test_staff_has_only_assigned_permissions(): void
    {
        $this->makeRole('staff', ['view-tasks', 'submit-leave']);
        $user = $this->makeUser('staff');

        $this->assertTrue($user->hasPermission('view-tasks'));
        $this->assertTrue($user->hasPermission('submit-leave'));
        $this->assertFalse($user->hasPermission('create-staff'));
        $this->assertFalse($user->hasPermission('delete-task'));
    }

    public function test_user_with_no_role_entry_has_no_permissions(): void
    {
        // 'orphan' role has no Role row and no permissions
        $user = $this->makeUser('orphan');
        $this->assertFalse($user->hasPermission('view-tasks'));
    }

    // ─── hasAllPermissions ────────────────────────────────────────────────────

    public function test_has_all_permissions_requires_every_slug(): void
    {
        $this->makeRole('pm', ['view-projects', 'create-project', 'edit-project']);
        $pm = $this->makeUser('pm');

        $this->assertTrue($pm->hasAllPermissions(['view-projects', 'create-project']));
        $this->assertFalse($pm->hasAllPermissions(['view-projects', 'delete-staff']));
    }

    // ─── hasAnyPermission ─────────────────────────────────────────────────────

    public function test_has_any_permission_passes_with_one_match(): void
    {
        $this->makeRole('staff', ['submit-leave']);
        $user = $this->makeUser('staff');

        $this->assertTrue($user->hasAnyPermission(['submit-leave', 'approve-leave']));
        $this->assertFalse($user->hasAnyPermission(['approve-leave', 'manage-leave']));
    }

    // ─── whereHasPermission scope ─────────────────────────────────────────────

    public function test_scope_where_has_permission_returns_correct_users(): void
    {
        $this->makeRole('pm', ['view-projects']);
        $this->makeRole('staff', ['submit-leave']);

        $admin  = $this->makeUser('admin');
        $pm     = $this->makeUser('pm');
        $staff  = $this->makeUser('staff');

        $results = User::whereHasPermission('view-projects')->pluck('id');

        $this->assertContains($admin->id, $results);  // admin always included
        $this->assertContains($pm->id, $results);
        $this->assertNotContains($staff->id, $results);
    }

    // ─── Role convenience methods ─────────────────────────────────────────────

    public function test_is_pm_and_is_staff_helpers(): void
    {
        $pm    = $this->makeUser('pm');
        $staff = $this->makeUser('staff');
        $admin = $this->makeUser('admin');

        $this->assertTrue($pm->isPm());
        $this->assertFalse($pm->isStaff());
        $this->assertTrue($staff->isStaff());
        $this->assertFalse($staff->isPm());
        $this->assertFalse($admin->isPm());
    }

    public function test_has_any_role(): void
    {
        $pm = $this->makeUser('pm');
        $this->assertTrue($pm->hasAnyRole(['pm', 'admin']));
        $this->assertFalse($pm->hasAnyRole(['staff', 'admin']));
    }

    // ─── Cache ────────────────────────────────────────────────────────────────

    public function test_permission_cache_is_consistent_after_role_change(): void
    {
        $role = $this->makeRole('tester', ['view-tasks']);
        $user = $this->makeUser('tester');

        $this->assertTrue($user->hasPermission('view-tasks'));

        // Simulate admin adding a new permission to the role
        $newPerm = Permission::create(['slug' => 'create-task', 'name' => 'Create Task', 'category' => 'test']);
        $role->permissions()->attach($newPerm->id);
        $user->flushPermissionCache();

        $this->assertTrue($user->hasPermission('create-task'));
    }
}
