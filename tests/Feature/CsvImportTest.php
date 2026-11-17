<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use App\Models\Shift\ShiftHoliday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-17 09:00:00');
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function csv(string $content, string $name = 'import.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    /** Upload and return the preview data passed to the view. */
    private function preview(string $type, string $content): array
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.imports.preview', $type), ['file' => $this->csv($content)]);
        $response->assertOk();

        return $response->viewData('result');
    }

    private function confirm(string $type, string $token)
    {
        return $this->actingAs($this->admin)->post(route('admin.imports.confirm', $type), ['token' => $token]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public function test_pages_and_templates(): void
    {
        $this->actingAs($this->admin)->get(route('admin.imports.index'))->assertOk()->assertSee('Leave opening balances');
        $this->actingAs($this->admin)->get(route('admin.imports.show', 'staff'))->assertOk()->assertSee('manager_email');

        $template = $this->actingAs($this->admin)->get(route('admin.imports.template', 'holidays'));
        $template->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringStartsWith("date,name,type,recurring\n", $template->getContent());

        $this->actingAs($this->admin)->get(route('admin.imports.show', 'nonsense'))->assertNotFound();
    }

    public function test_old_staff_import_link_redirects(): void
    {
        $this->actingAs($this->admin)->get(route('admin.staff.import'))->assertRedirect('/admin/imports/staff');
    }

    public function test_staff_cannot_use_imports(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('admin.imports.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.imports.preview', 'departments'), ['file' => $this->csv("name\nX")])->assertForbidden();
    }

    // ── Preview does not write; confirm does ──────────────────────────────────

    public function test_preview_writes_nothing_and_confirm_imports_valid_rows(): void
    {
        $result = $this->preview('departments', "Name,Description,Active\nEngineering,Builds things,yes\n,Missing name,yes\nSales,,no\n");

        $this->assertSame(3, $result['total']);
        $this->assertSame(2, $result['valid']);
        $this->assertSame(3, $result['errors'][0]['line']);
        $this->assertStringContainsString('"name" is empty', $result['errors'][0]['message']);
        $this->assertSame(0, Department::count());

        $this->confirm('departments', $result['token'])
            ->assertRedirect(route('admin.imports.show', 'departments'))
            ->assertSessionHas('success', 'Departments imported: 2 added.');

        $this->assertSame(['Engineering', 'Sales'], Department::orderBy('name')->pluck('name')->all());
        $this->assertFalse((bool) Department::where('name', 'Sales')->value('is_active'));
        $this->assertNotNull(AuditLog::where('event', 'import')->first());
    }

    public function test_preview_token_works_once_and_only_for_its_owner(): void
    {
        $result = $this->preview('departments', "name\nEngineering\n");

        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($otherAdmin)->post(route('admin.imports.confirm', 'departments'), ['token' => $result['token']])
            ->assertSessionHas('error');
        $this->assertSame(0, Department::count());

        $this->confirm('departments', $result['token'])->assertSessionHas('success');
        $this->confirm('departments', $result['token'])->assertSessionHas('error');
        $this->assertSame(1, Department::count());
    }

    public function test_file_level_problems(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.imports.preview', 'holidays'), ['file' => $this->csv("name,type\nNew Year,national\n")])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'missing column: date'));

        $this->actingAs($this->admin)
            ->post(route('admin.imports.preview', 'holidays'), ['file' => $this->csv("date,name\n")])
            ->assertSessionHas('error', 'The file has a header row but no data rows.');

        $this->actingAs($this->admin)
            ->post(route('admin.imports.preview', 'holidays'), [])
            ->assertSessionHasErrors('file');
    }

    public function test_handles_semicolons_bom_and_ignored_columns(): void
    {
        $result = $this->preview('holidays', "\xEF\xBB\xBFDate;Name;Notes\n2026-12-25;Christmas Day;office closed\n");

        $this->assertSame(1, $result['valid']);
        $this->assertSame(['notes'], $result['ignored_columns']);
    }

    // ── Each type ─────────────────────────────────────────────────────────────

    public function test_staff_import_creates_accounts_without_a_known_password(): void
    {
        $dept    = Department::create(['name' => 'Engineering', 'is_active' => true]);
        $manager = User::factory()->create(['email' => 'lead@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        $result = $this->preview('staff', implode("\n", [
            'name,email,role,department,manager_email',
            'Priya Raman,PRIYA@example.com,staff,engineering,lead@example.com',
            'Dup,taken@example.com,,,',
            'Bad Role,x@example.com,wizard,,',
            'No Dept,y@example.com,,Marketing,',
            'Bad Email,not-an-email,,,',
            'Twice,priya@example.com,,,',
        ]));

        $this->assertSame(1, $result['valid']);
        $messages = array_column($result['errors'], 'message');
        $this->assertStringContainsString('already has an account', $messages[0]);
        $this->assertStringContainsString('Role "wizard" does not exist', $messages[1]);
        $this->assertStringContainsString('Department "Marketing" does not exist', $messages[2]);
        $this->assertStringContainsString('not a valid email', $messages[3]);
        $this->assertStringContainsString('more than once', $messages[4]);

        $this->confirm('staff', $result['token'])->assertSessionHas('success');

        $priya = User::where('email', 'priya@example.com')->firstOrFail();
        $this->assertSame($dept->id, $priya->department_id);
        $this->assertSame($manager->id, $priya->reporting_to);
        $this->assertFalse(Hash::check('Password@123', $priya->password));
    }

    public function test_holiday_import_updates_instead_of_duplicating(): void
    {
        ShiftHoliday::create(['name' => 'Christmas Day', 'date' => '2026-12-25', 'holiday_type' => 'national', 'is_recurring' => false, 'is_active' => true]);

        $result = $this->preview('holidays', "date,name,type,recurring\n2026-12-25,Christmas Day,company,yes\n25/01/2027,Founders Day,,\n2026-13-01,Bad Date,,\n");

        $this->assertSame(2, $result['valid']);
        $this->assertStringContainsString('must be a date', $result['errors'][0]['message']);

        $this->confirm('holidays', $result['token'])->assertSessionHas('success', 'Holidays imported: 1 added, 1 updated.');
        $this->assertSame(2, ShiftHoliday::count());
        $this->assertTrue(ShiftHoliday::where('name', 'Christmas Day')->first()->is_recurring);
        $this->assertSame('2027-01-25', ShiftHoliday::where('name', 'Founders Day')->first()->date->toDateString());
    }

    public function test_leave_balance_import_sets_opening_balance_only(): void
    {
        $user = User::factory()->create(['email' => 'priya@example.com']);
        $type = LeaveType::factory()->create(['code' => 'CL']);
        LeaveBalance::create(['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => 2026, 'opening_balance' => 0, 'used_days' => 2, 'accrued_days' => 1, 'pending_days' => 0]);

        $result = $this->preview('leave-balances', "email,leave_type,year,opening_balance\npriya@example.com,cl,2026,7.5\npriya@example.com,CL,2027,3.3\nnobody@example.com,CL,2026,1\npriya@example.com,XX,2026,1\n");

        $this->assertSame(1, $result['valid']);
        $this->assertCount(3, $result['errors']);

        $this->confirm('leave-balances', $result['token']);

        $balance = LeaveBalance::where('user_id', $user->id)->where('year', 2026)->firstOrFail();
        $this->assertSame(0, bccomp((string) $balance->opening_balance, '7.5', 1));
        $this->assertSame(0, bccomp((string) $balance->used_days, '2', 1));
    }

    public function test_attendance_import(): void
    {
        $user = User::factory()->create(['email' => 'priya@example.com']);
        Attendance::create(['user_id' => $user->id, 'date' => '2026-11-10', 'status' => 'absent']);

        $result = $this->preview('attendance', implode("\n", [
            'email,date,check_in,check_out,status',
            'priya@example.com,2026-11-10,09:00,17:30,',
            'priya@example.com,2026-11-11,,,leave',
            'priya@example.com,2026-11-12,18:00,09:00,',
            'priya@example.com,2026-11-30,09:00,,',
            'priya@example.com,2026-11-13,25:00,,',
        ]));

        $this->assertSame(2, $result['valid']);
        $messages = array_column($result['errors'], 'message');
        $this->assertStringContainsString('must be after', $messages[0]);
        $this->assertStringContainsString('in the future', $messages[1]);
        $this->assertStringContainsString('24-hour time', $messages[2]);

        $this->confirm('attendance', $result['token'])->assertSessionHas('success', 'Attendance (past days) imported: 1 added, 1 updated.');

        $day = Attendance::where('user_id', $user->id)->whereDate('date', '2026-11-10')->firstOrFail();
        $this->assertSame('present', $day->status);
        $this->assertSame(510, (int) $day->worked_minutes);
        $this->assertSame(2, Attendance::count());
    }
}
