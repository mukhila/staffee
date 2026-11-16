<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Leave\LeaveApprovalDelegation;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Leave\LeaveApprovalService;
use App\Services\Leave\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LeaveDelegationAndClashTest extends TestCase
{
    use RefreshDatabase;

    private LeaveApprovalService $approvals;
    private LeaveRequestService $requests;
    private Department $engineering;
    private Department $sales;
    private User $engManager;
    private User $salesManager;
    private User $engineer;
    private LeaveType $type;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 10:00:00');

        $this->approvals = app(LeaveApprovalService::class);
        $this->requests  = app(LeaveRequestService::class);

        $role = Role::create(['name' => 'PM', 'slug' => 'pm', 'is_active' => true]);
        $perm = Permission::firstOrCreate(
            ['slug' => 'approve-leave'],
            ['name' => 'Approve Leave', 'category' => 'leave_management']
        );
        $role->permissions()->attach($perm->id);

        $this->engineering  = Department::create(['name' => 'Engineering', 'is_active' => true]);
        $this->sales        = Department::create(['name' => 'Sales', 'is_active' => true]);
        $this->engManager   = User::factory()->create(['role' => 'pm', 'department_id' => $this->engineering->id]);
        $this->salesManager = User::factory()->create(['role' => 'pm', 'department_id' => $this->sales->id]);
        $this->engineer     = User::factory()->create(['role' => 'staff', 'department_id' => $this->engineering->id]);
        $this->type         = LeaveType::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function leaveFor(User $user, string $from, string $to, string $status = 'pending'): LeaveRequest
    {
        return LeaveRequest::create([
            'user_id'       => $user->id,
            'leave_type_id' => $this->type->id,
            'type'          => $this->type->code,
            'from_date'     => $from,
            'to_date'       => $to,
            'days'          => 1,
            'reason'        => 'Test',
            'status'        => $status,
        ]);
    }

    // ── Delegation ────────────────────────────────────────────────────────────

    public function test_manager_cannot_act_on_other_department_without_delegation(): void
    {
        $leave = $this->leaveFor($this->engineer, '2026-11-20', '2026-11-20');

        $this->assertFalse($this->approvals->canAct($leave, $this->salesManager));
        $this->assertCount(0, $this->approvals->getPendingForApprover($this->salesManager));
    }

    public function test_active_delegation_lets_delegate_see_and_act(): void
    {
        $leave = $this->leaveFor($this->engineer, '2026-11-20', '2026-11-20');

        $this->approvals->delegate(
            $this->engManager, $this->salesManager, Carbon::today(), Carbon::today()->addDays(5), 'On leave'
        );

        $this->assertTrue($this->approvals->canAct($leave, $this->salesManager));
        $this->assertTrue($this->approvals->getPendingForApprover($this->salesManager)->contains('id', $leave->id));
        $this->assertSame($this->salesManager->id, $this->approvals->activeDelegateFor($this->engManager)?->id);
    }

    public function test_delegation_outside_its_dates_has_no_effect(): void
    {
        $leave = $this->leaveFor($this->engineer, '2026-11-20', '2026-11-20');

        $this->approvals->delegate(
            $this->engManager, $this->salesManager, Carbon::today()->addDays(10), Carbon::today()->addDays(12)
        );

        $this->assertFalse($this->approvals->canAct($leave, $this->salesManager));
        $this->assertNull($this->approvals->activeDelegateFor($this->engManager));
    }

    public function test_cannot_delegate_to_self(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->approvals->delegate($this->engManager, $this->engManager, Carbon::today(), Carbon::today());
    }

    public function test_cannot_delegate_to_someone_without_approve_permission(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->approvals->delegate($this->engManager, $this->engineer, Carbon::today(), Carbon::today());
    }

    public function test_cannot_create_overlapping_delegations(): void
    {
        $this->approvals->delegate($this->engManager, $this->salesManager, Carbon::today(), Carbon::today()->addDays(5));

        $this->expectException(InvalidArgumentException::class);
        $this->approvals->delegate(
            $this->engManager, $this->salesManager, Carbon::today()->addDays(3), Carbon::today()->addDays(8)
        );
    }

    public function test_delegation_http_flow(): void
    {
        $this->actingAs($this->engManager)
            ->post(route('admin.leaves.delegations.store'), [
                'delegate_id' => $this->salesManager->id,
                'from_date'   => '2026-11-16',
                'to_date'     => '2026-11-20',
                'reason'      => 'Conference',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $delegation = LeaveApprovalDelegation::firstOrFail();

        $this->actingAs($this->engManager)->get(route('admin.leaves.delegations.index'))
            ->assertOk()->assertSee('Conference');

        // Another manager cannot remove it; the owner can.
        $this->actingAs($this->salesManager)
            ->delete(route('admin.leaves.delegations.destroy', $delegation))
            ->assertForbidden();
        $this->actingAs($this->engManager)
            ->delete(route('admin.leaves.delegations.destroy', $delegation))
            ->assertRedirect();

        $this->assertSame(0, LeaveApprovalDelegation::count());
    }

    public function test_staff_cannot_open_delegation_page(): void
    {
        $this->actingAs($this->engineer)->get(route('admin.leaves.delegations.index'))->assertForbidden();
    }

    // ── Team clashes ──────────────────────────────────────────────────────────

    public function test_team_clashes_lists_overlapping_leave_in_same_department(): void
    {
        $teammate = User::factory()->create(['role' => 'staff', 'department_id' => $this->engineering->id]);
        $outsider = User::factory()->create(['role' => 'staff', 'department_id' => $this->sales->id]);

        $overlapping = $this->leaveFor($teammate, '2026-11-19', '2026-11-21', 'approved');
        $this->leaveFor($teammate, '2026-12-01', '2026-12-02', 'approved');   // different dates
        $this->leaveFor($outsider, '2026-11-20', '2026-11-20', 'approved');   // other department
        $rejected = User::factory()->create(['role' => 'staff', 'department_id' => $this->engineering->id]);
        $this->leaveFor($rejected, '2026-11-20', '2026-11-20', 'rejected');   // not active

        $clashes = $this->requests->teamClashes(
            $this->engineer, Carbon::parse('2026-11-20'), Carbon::parse('2026-11-20')
        );

        $this->assertCount(1, $clashes);
        $this->assertSame($overlapping->id, $clashes->first()->id);
    }

    public function test_team_clashes_excludes_own_request_and_handles_no_department(): void
    {
        $own = $this->leaveFor($this->engineer, '2026-11-20', '2026-11-20');
        $loner = User::factory()->create(['role' => 'staff', 'department_id' => null]);

        $this->assertCount(0, $this->requests->teamClashes(
            $this->engineer, Carbon::parse('2026-11-20'), Carbon::parse('2026-11-20'), $own->id
        ));
        $this->assertCount(0, $this->requests->teamClashes(
            $loner, Carbon::parse('2026-11-20'), Carbon::parse('2026-11-20')
        ));
    }

    public function test_admin_leave_page_shows_clash_warning(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $teammate = User::factory()->create([
            'role' => 'staff', 'department_id' => $this->engineering->id, 'name' => 'Clashing Teammate',
        ]);
        $this->leaveFor($teammate, '2026-11-20', '2026-11-20', 'approved');
        $leave = $this->leaveFor($this->engineer, '2026-11-20', '2026-11-20');

        $this->actingAs($admin)->get(route('admin.leaves.show', $leave))
            ->assertOk()
            ->assertSee('Team clash')
            ->assertSee('Clashing Teammate');
    }

    public function test_staff_team_calendar_route_is_reachable(): void
    {
        $this->actingAs($this->engineer)->get(route('staff.leaves.team-calendar'))->assertOk();
    }
}
