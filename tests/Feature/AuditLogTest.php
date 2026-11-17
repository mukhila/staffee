<?php

namespace Tests\Feature;

use App\Listeners\NotifyHROfTermination;
use App\Listeners\NotifyManagerOfResignation;
use App\Listeners\SendPromotionNotification;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'password' => 'secret-password']);
        $this->staff = User::factory()->create(['role' => 'staff', 'password' => 'secret-password']);
    }

    private function lastFor(string $event, ?object $subject = null): ?AuditLog
    {
        return AuditLog::where('event', $event)
            ->when($subject, fn ($q) => $q->where('auditable_type', $subject->getMorphClass())->where('auditable_id', $subject->getKey()))
            ->latest('id')
            ->first();
    }

    // ── Model changes ─────────────────────────────────────────────────────────

    public function test_create_update_delete_are_recorded_with_actor(): void
    {
        $this->actingAs($this->admin);

        $role = Role::create(['name' => 'Auditor', 'slug' => 'auditor', 'is_active' => true]);
        $created = $this->lastFor('created', $role);
        $this->assertNotNull($created);
        $this->assertSame($this->admin->id, $created->user_id);
        $this->assertSame('Auditor', $created->new_values['name']);

        $role->update(['name' => 'Senior Auditor']);
        $updated = $this->lastFor('updated', $role);
        $this->assertSame(['name' => 'Auditor'], $updated->old_values);
        $this->assertSame(['name' => 'Senior Auditor'], $updated->new_values);

        $role->delete();
        $deleted = $this->lastFor('deleted', $role);
        $this->assertSame('Senior Auditor', $deleted->old_values['name']);
    }

    public function test_saving_without_real_changes_records_nothing(): void
    {
        $role = Role::create(['name' => 'Auditor', 'slug' => 'auditor', 'is_active' => true]);
        $before = AuditLog::count();

        $role->touch();          // timestamps only
        $role->update(['name' => 'Auditor']); // same value

        $this->assertSame($before, AuditLog::count());
    }

    public function test_passwords_and_tokens_are_never_stored(): void
    {
        $user = User::factory()->create(['password' => 'plain-text-secret']);
        $user->update(['password' => 'another-secret', 'agent_token' => 'tok_123']);

        $json = AuditLog::where('auditable_id', $user->id)
            ->where('auditable_type', $user->getMorphClass())
            ->get()
            ->map(fn ($log) => json_encode([$log->old_values, $log->new_values]))
            ->implode(' ');

        $this->assertStringNotContainsString('plain-text-secret', $json);
        $this->assertStringNotContainsString('another-secret', $json);
        $this->assertStringNotContainsString('tok_123', $json);
        $this->assertStringContainsString('[hidden]', $json);
    }

    public function test_secret_settings_are_redacted_but_others_are_kept(): void
    {
        Setting::create(['key' => 'smtp_password', 'value' => 'mail-secret']);
        Setting::create(['key' => 'company_name', 'value' => 'Acme']);

        $secret = AuditLog::where('auditable_type', (new Setting)->getMorphClass())->get()
            ->first(fn ($log) => ($log->new_values['key'] ?? null) === 'smtp_password');
        $plain = AuditLog::where('auditable_type', (new Setting)->getMorphClass())->get()
            ->first(fn ($log) => ($log->new_values['key'] ?? null) === 'company_name');

        $this->assertSame('[hidden]', $secret->new_values['value']);
        $this->assertSame('Acme', $plain->new_values['value']);
    }

    // ── Authentication events ─────────────────────────────────────────────────

    public function test_successful_and_failed_sign_ins_are_recorded(): void
    {
        $this->post('/login', ['email' => $this->staff->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $failed = $this->lastFor('login_failed');
        $this->assertSame($this->staff->id, $failed->user_id);
        $this->assertSame(['email' => $this->staff->email], $failed->new_values);
        $this->assertStringNotContainsString('wrong', json_encode($failed->toArray()));

        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'x']);
        $this->assertNull($this->lastFor('login_failed')->user_id);

        $this->post('/login', ['email' => $this->staff->email, 'password' => 'secret-password'])->assertRedirect();
        $this->assertNotNull($this->lastFor('login', $this->staff));

        $this->post('/logout');
        $this->assertNotNull($this->lastFor('logout', $this->staff));
    }

    // ── Viewer ────────────────────────────────────────────────────────────────

    public function test_admin_can_browse_filter_and_open_entries(): void
    {
        $this->actingAs($this->admin);
        $role = Role::create(['name' => 'Auditor', 'slug' => 'auditor', 'is_active' => true]);
        $role->update(['name' => 'Chief Auditor']);
        $entry = $this->lastFor('updated', $role);

        $this->get(route('admin.audit-logs.index'))->assertOk()->assertSee('Audit Log')->assertSee('Role');
        $this->get(route('admin.audit-logs.index', ['event' => 'updated', 'type' => $role->getMorphClass()]))
            ->assertOk()->assertSee('Changed Name');
        $this->get(route('admin.audit-logs.show', $entry))
            ->assertOk()->assertSee('Chief Auditor')->assertSee('History of this record');
    }

    public function test_non_admins_cannot_see_the_audit_log(): void
    {
        $entry = AuditLog::firstOrFail();

        $this->actingAs($this->staff)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($this->staff)->get(route('admin.audit-logs.show', $entry))->assertForbidden();
    }

    // ── Listener registration (regression) ────────────────────────────────────

    public function test_hr_listeners_are_registered_once(): void
    {
        $count = fn (string $event, string $listener) => collect(Event::getRawListeners()[$event] ?? [])
            ->filter(fn ($l) => is_string($l) && str_starts_with($l, $listener))
            ->count();

        $this->assertSame(1, $count(\App\Events\EmployeePromoted::class, SendPromotionNotification::class));
        $this->assertSame(1, $count(\App\Events\ResignationSubmitted::class, NotifyManagerOfResignation::class));
        $this->assertSame(1, $count(\App\Events\TerminationInitiated::class, NotifyHROfTermination::class));
    }
}
