<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_store_creates_role_from_validated_fields_only(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name'        => 'Auditor',
            'slug'        => 'auditor',
            'description' => 'Read-only finance access',
            'is_active'   => '1',
            'id'          => 9999,
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role = Role::where('slug', 'auditor')->firstOrFail();
        $this->assertNotSame(9999, $role->id);
        $this->assertTrue((bool) $role->is_active);
        $this->assertNull($role->department_id);
    }

    public function test_store_rejects_unknown_department_and_permission(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.roles.store'), [
            'name'          => 'Auditor',
            'slug'          => 'auditor',
            'department_id' => 9999,
            'permissions'   => [9999],
        ]);

        $response->assertSessionHasErrors(['department_id', 'permissions.0']);
        $this->assertSame(0, Role::where('slug', 'auditor')->count());
    }

    public function test_update_changes_fields_and_clears_active_when_unchecked(): void
    {
        $role = Role::create(['name' => 'Auditor', 'slug' => 'auditor', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->put(route('admin.roles.update', $role), [
            'name' => 'Senior Auditor',
            'slug' => 'auditor',
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        $this->assertSame('Senior Auditor', $role->name);
        $this->assertFalse((bool) $role->is_active);
    }

    public function test_role_show_route_no_longer_exists(): void
    {
        $role = Role::create(['name' => 'Auditor', 'slug' => 'auditor']);

        $this->actingAs($this->admin)
            ->get('/admin/roles/' . $role->id)
            ->assertStatus(405);
    }
}
