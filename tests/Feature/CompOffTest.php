<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Leave\LeaveBalance;
use App\Models\Shift\ShiftHoliday;
use App\Models\User;
use App\Services\Leave\CompOffService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CompOffTest extends TestCase
{
    use RefreshDatabase;

    private const SATURDAY = '2026-11-14';
    private const FRIDAY   = '2026-11-13';

    private CompOffService $service;
    private User $staff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 10:00:00');
        $this->service = app(CompOffService::class);
        $this->staff   = User::factory()->create(['role' => 'staff']);
        $this->admin   = User::factory()->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function attend(string $date, ?int $workedMinutes = 480): Attendance
    {
        return Attendance::create([
            'user_id'        => $this->staff->id,
            'date'           => $date,
            'check_in'       => '09:00:00',
            'check_out'      => '17:00:00',
            'status'         => 'present',
            'worked_minutes' => $workedMinutes,
        ]);
    }

    public function test_claim_for_weekend_with_attendance_is_accepted(): void
    {
        $this->attend(self::SATURDAY);

        $credit = $this->service->request($this->staff, self::SATURDAY, 'Release support');

        $this->assertSame('pending', $credit->status);
        $this->assertSame(0, bccomp((string) $credit->days, '1.0', 1));
    }

    public function test_short_day_earns_half_a_day(): void
    {
        $this->attend(self::SATURDAY, 200);

        $credit = $this->service->request($this->staff, self::SATURDAY, 'Short fix');

        $this->assertSame(0, bccomp((string) $credit->days, '0.5', 1));
    }

    public function test_claim_for_ordinary_working_day_is_refused(): void
    {
        $this->attend(self::FRIDAY);

        $this->expectException(InvalidArgumentException::class);
        $this->service->request($this->staff, self::FRIDAY, 'Normal day');
    }

    public function test_claim_for_public_holiday_on_weekday_is_accepted(): void
    {
        ShiftHoliday::create([
            'name' => 'Founders Day', 'date' => self::FRIDAY, 'holiday_type' => 'public',
            'is_recurring' => false, 'is_active' => true,
        ]);
        $this->attend(self::FRIDAY);

        $credit = $this->service->request($this->staff, self::FRIDAY, 'Holiday cover');

        $this->assertSame('pending', $credit->status);
    }

    public function test_claim_without_attendance_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->request($this->staff, self::SATURDAY, 'Did not check in');
    }

    public function test_duplicate_claim_is_refused(): void
    {
        $this->attend(self::SATURDAY);
        $this->service->request($this->staff, self::SATURDAY, 'First');

        $this->expectException(InvalidArgumentException::class);
        $this->service->request($this->staff, self::SATURDAY, 'Second');
    }

    public function test_approval_credits_comp_off_leave_balance(): void
    {
        $this->attend(self::SATURDAY);
        $credit = $this->service->request($this->staff, self::SATURDAY, 'Release support');

        $this->service->approve($credit, $this->admin);

        $balance = LeaveBalance::where('user_id', $this->staff->id)
            ->where('leave_type_id', $this->service->leaveType()->id)
            ->where('year', 2026)
            ->firstOrFail();

        $this->assertSame(0, bccomp((string) $balance->accrued_days, '1.0', 1));
        $this->assertSame('approved', $credit->fresh()->status);
    }

    public function test_rejection_does_not_credit_balance(): void
    {
        $this->attend(self::SATURDAY);
        $credit = $this->service->request($this->staff, self::SATURDAY, 'Release support');

        $this->service->reject($credit, $this->admin, 'Not pre-approved');

        $this->assertSame('rejected', $credit->fresh()->status);
        $this->assertSame(0, LeaveBalance::where('user_id', $this->staff->id)->count());
    }

    public function test_claim_cannot_be_approved_twice(): void
    {
        $this->attend(self::SATURDAY);
        $credit = $this->service->request($this->staff, self::SATURDAY, 'Release support');
        $this->service->approve($credit, $this->admin);

        $this->expectException(InvalidArgumentException::class);
        $this->service->approve($credit->fresh(), $this->admin);
    }

    public function test_http_flow_and_access_control(): void
    {
        $this->attend(self::SATURDAY);

        $this->actingAs($this->staff)
            ->post(route('staff.comp-off.store'), ['worked_date' => self::SATURDAY, 'reason' => 'Weekend deploy'])
            ->assertRedirect(route('staff.comp-off.index'));

        $credit = \App\Models\Leave\CompOffCredit::firstOrFail();

        $this->actingAs($this->staff)->get(route('staff.comp-off.index'))->assertOk()->assertSee('Weekend deploy');
        $this->actingAs($this->staff)->get(route('admin.leaves.comp-off.index'))->assertForbidden();
        $this->actingAs($this->staff)->post(route('admin.leaves.comp-off.approve', $credit))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.leaves.comp-off.index'))->assertOk()->assertSee('Weekend deploy');
        $this->actingAs($this->admin)->post(route('admin.leaves.comp-off.approve', $credit))->assertRedirect();

        $this->assertSame('approved', $credit->fresh()->status);
    }
}
