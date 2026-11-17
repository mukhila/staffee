<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\HR\EmployeeProfile;
use App\Models\User;
use App\Services\People\CelebrationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private Department $eng;
    private User $ceo;
    private User $lead;
    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-12-28 10:00:00');

        $this->eng  = Department::create(['name' => 'Engineering', 'is_active' => true]);
        $this->ceo  = $this->person('Cara Chief', ['designation' => 'Chief Executive']);
        $this->lead = $this->person('Leo Lead', ['designation' => 'Engineering Lead', 'reporting_to' => $this->ceo->id, 'department_id' => $this->eng->id]);
        $this->dev  = $this->person('Dana Dev', ['designation' => 'Developer', 'reporting_to' => $this->lead->id, 'department_id' => $this->eng->id, 'phone' => '+91 90000 00001']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function person(string $name, array $attrs = []): User
    {
        $user = User::factory()->create(['name' => $name, 'role' => 'staff', 'is_active' => true]);
        $user->forceFill($attrs)->save();

        return $user;
    }

    private function profile(User $user, ?string $dob, ?string $joined): void
    {
        EmployeeProfile::create(['user_id' => $user->id, 'date_of_birth' => $dob, 'joining_date' => $joined]);
    }

    // ── Directory ─────────────────────────────────────────────────────────────

    public function test_directory_search_and_filter(): void
    {
        $this->person('Gone Person', ['is_active' => false]);
        $other = Department::create(['name' => 'Sales', 'is_active' => true]);
        $this->person('Sam Seller', ['department_id' => $other->id]);

        $this->actingAs($this->dev)->get(route('people.directory'))
            ->assertOk()->assertSee('Leo Lead')->assertSee('Sam Seller')->assertDontSee('Gone Person')
            ->assertSee('Reports to Cara Chief');

        $this->actingAs($this->dev)->get(route('people.directory', ['q' => 'developer']))
            ->assertOk()->assertSee('Dana Dev')->assertDontSee('Sam Seller');

        $this->actingAs($this->dev)->get(route('people.directory', ['department_id' => $other->id]))
            ->assertOk()->assertSee('Sam Seller')->assertDontSee('Leo Lead');
    }

    public function test_phone_numbers_only_shown_to_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($this->lead)->get(route('people.directory'))->assertOk()->assertDontSee('+91 90000 00001');
        $this->actingAs($admin)->get(route('people.directory'))->assertOk()->assertSee('+91 90000 00001');
    }

    // ── Org chart ─────────────────────────────────────────────────────────────

    public function test_org_chart_tree_and_team_sizes(): void
    {
        $response = $this->actingAs($this->dev)->get(route('people.org-chart'));
        $response->assertOk()->assertSeeInOrder(['Cara Chief', 'Leo Lead', 'Dana Dev']);

        $sizes = $response->viewData('teamSize');
        $this->assertSame(2, $sizes[$this->ceo->id]);
        $this->assertSame(1, $sizes[$this->lead->id]);
        $this->assertSame(0, $sizes[$this->dev->id]);
    }

    public function test_focusing_on_a_person_shows_their_team_and_reporting_line(): void
    {
        $this->actingAs($this->dev)->get(route('people.org-chart', ['root' => $this->lead->id]))
            ->assertOk()
            ->assertSee("Leo Lead's team")
            ->assertSee('Reporting line')
            ->assertSee('Dana Dev');
    }

    public function test_people_whose_manager_left_appear_at_the_top_and_loops_do_not_hang(): void
    {
        $this->ceo->update(['is_active' => false]);         // Leo's manager left
        $this->lead->forceFill(['reporting_to' => $this->dev->id])->save(); // loop: Leo ↔ Dana

        $this->actingAs($this->dev)->get(route('people.org-chart'))->assertOk();
        $this->actingAs($this->dev)->get(route('people.org-chart', ['root' => $this->dev->id]))->assertOk();
    }

    // ── Celebrations ──────────────────────────────────────────────────────────

    public function test_celebrations_across_year_end_without_birth_year(): void
    {
        $this->profile($this->dev, '1990-01-02', '2021-12-30');  // birthday 2 Jan (next year), 5th anniversary 30 Dec
        $this->profile($this->lead, '1985-12-28', '2026-06-01'); // birthday today; joined this year → no anniversary
        $gone = $this->person('Gone Person', ['is_active' => false]);
        $this->profile($gone, '1990-12-29', null);

        $events = app(CelebrationService::class)->upcoming(now(), 7);

        $this->assertSame(
            [['Leo Lead', 'birthday', '2026-12-28'], ['Dana Dev', 'anniversary', '2026-12-30'], ['Dana Dev', 'birthday', '2027-01-02']],
            $events->map(fn ($e) => [$e['user']->name, $e['type'], $e['date']->toDateString()])->all()
        );
        $this->assertTrue($events[0]['is_today']);
        $this->assertSame(5, $events[1]['years']);

        $this->actingAs($this->ceo)->get(route('people.directory'))
            ->assertOk()->assertSee('Today')->assertSee('5 years at the company')->assertDontSee('1990')->assertDontSee('1985');
    }

    public function test_leap_day_birthday_is_celebrated_on_28_february(): void
    {
        Carbon::setTestNow('2027-02-25 09:00:00');
        $this->profile($this->dev, '2000-02-29', null);

        $events = app(CelebrationService::class)->upcoming(now(), 7);

        $this->assertSame('2027-02-28', $events->first()['date']->toDateString());
    }
}
