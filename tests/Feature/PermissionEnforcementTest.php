<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function makeRole(string $slug, array $permSlugs = []): Role
    {
        $role = Role::create(['name' => ucfirst($slug), 'slug' => $slug, 'is_active' => true]);
        foreach ($permSlugs as $ps) {
            $perm = Permission::firstOrCreate(
                ['slug' => $ps],
                ['name' => ucwords(str_replace('-', ' ', $ps)), 'category' => 'test']
            );
            $role->permissions()->attach($perm->id);
        }
        return $role;
    }

    private function makeUser(string $roleSlug): User
    {
        return User::factory()->create(['role' => $roleSlug]);
    }

    // ─── CheckPermission middleware ───────────────────────────────────────────

    public function test_permission_middleware_allows_user_with_permission(): void
    {
        $this->makeRole('pm', ['view-projects']);
        $pm = $this->makeUser('pm');

        // Register a test route that uses the permission middleware
        \Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'permission:view-projects'])
            ->get('/test-permission-route', fn () => response('OK'));

        $this->actingAs($pm)
            ->get('/test-permission-route')
            ->assertStatus(200)
            ->assertSee('OK');
    }

    public function test_permission_middleware_blocks_user_without_permission(): void
    {
        $this->makeRole('staff', ['submit-leave']); // does NOT have view-projects
        $staff = $this->makeUser('staff');

        \Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'permission:view-projects'])
            ->get('/test-blocked-route', fn () => response('OK'));

        $this->actingAs($staff)
            ->get('/test-blocked-route')
            ->assertStatus(403);
    }

    public function test_permission_middleware_always_passes_for_admin(): void
    {
        $admin = $this->makeUser('admin');

        \Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'permission:delete-staff'])
            ->get('/test-admin-route', fn () => response('OK'));

        $this->actingAs($admin)
            ->get('/test-admin-route')
            ->assertStatus(200);
    }

    public function test_permission_middleware_redirects_unauthenticated_users(): void
    {
        \Illuminate\Support\Facades\Route::middleware(['web', 'auth', 'permission:view-projects'])
            ->get('/test-guest-route', fn () => response('OK'));

        $this->get('/test-guest-route')
            ->assertRedirect('/login');
    }

    // ─── StaffController authorization ───────────────────────────────────────

    public function test_admin_can_access_staff_index(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('admin.staff.index'))
            ->assertStatus(200);
    }

    public function test_staff_user_is_blocked_by_role_middleware_on_admin_routes(): void
    {
        $this->makeRole('staff');
        $staff = $this->makeUser('staff');

        // Admin routes are guarded by role:admin — staff gets 403 from CheckRole
        $this->actingAs($staff)
            ->get(route('admin.staff.index'))
            ->assertStatus(403);
    }

    // ─── Gate / authorize() in controllers ───────────────────────────────────

    public function test_authorize_blocks_user_missing_create_staff_permission(): void
    {
        // Craft a role that passes the role:admin check but lacks create-staff.
        // We simulate this by testing the Gate directly (the role:admin middleware
        // is the outer guard; authorize() is the inner guard for future PM access).
        $this->makeRole('pm', ['view-staff']); // view but NOT create
        $pm = $this->makeUser('pm');

        $this->assertFalse($pm->can('create-staff'));
    }

    public function test_gate_allows_when_permission_present(): void
    {
        $this->makeRole('pm', ['create-staff']);
        $pm = $this->makeUser('pm');

        $this->assertTrue($pm->can('create-staff'));
    }

    public function test_admin_gate_always_returns_true_via_before_hook(): void
    {
        $admin = $this->makeUser('admin');

        // Admin bypasses every gate — even permissions that were never seeded.
        $this->assertTrue($admin->can('create-staff'));
        $this->assertTrue($admin->can('non-existent-permission'));
    }

    // ─── PermissionService ────────────────────────────────────────────────────

    public function test_permission_service_reports_role_permission_correctly(): void
    {
        $this->makeRole('pm', ['view-projects', 'create-project']);

        $service = app(\App\Services\PermissionService::class);

        $this->assertTrue($service->roleHasPermission('pm', 'view-projects'));
        $this->assertFalse($service->roleHasPermission('pm', 'delete-staff'));
        $this->assertTrue($service->roleHasPermission('admin', 'anything')); // admin bypass
    }

    public function test_permission_service_matrix_data_structure(): void
    {
        $role = $this->makeRole('pm', ['view-projects']);

        $service = app(\App\Services\PermissionService::class);
        $matrix  = $service->matrixData();

        $perm = Permission::where('slug', 'view-projects')->first();
        $this->assertArrayHasKey($role->id, $matrix);
        $this->assertArrayHasKey($perm->id, $matrix[$role->id]);
    }

    // ─── whereHasPermission scope ─────────────────────────────────────────────

    public function test_where_has_permission_scope_in_query(): void
    {
        $this->makeRole('pm', ['view-projects']);
        $this->makeRole('staff', ['submit-leave']);

        $admin = $this->makeUser('admin');
        $pm    = $this->makeUser('pm');
        $staff = $this->makeUser('staff');

        $ids = User::whereHasPermission('view-projects')->pluck('id');

        $this->assertContains($admin->id, $ids);
        $this->assertContains($pm->id, $ids);
        $this->assertNotContains($staff->id, $ids);
    }

    // ─── Role inheritance / defaults ─────────────────────────────────────────

    public function test_all_users_sharing_a_role_inherit_the_same_permissions(): void
    {
        $this->makeRole('staff', ['submit-leave', 'view-tasks']);

        $staff1 = $this->makeUser('staff');
        $staff2 = $this->makeUser('staff');

        // Both users share the cached role_permissions:staff entry
        $this->assertEquals(
            $staff1->cachedPermissionSlugs(),
            $staff2->cachedPermissionSlugs()
        );
        $this->assertTrue($staff1->hasPermission('submit-leave'));
        $this->assertTrue($staff2->hasPermission('view-tasks'));
        $this->assertFalse($staff1->hasPermission('create-staff'));
    }
}
