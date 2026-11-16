<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Setting;
use App\Models\User;
use App\Services\Shift\GeofenceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeofenceCheckInTest extends TestCase
{
    use RefreshDatabase;

    // Office, a point roughly 55 m north of it, and one roughly 1.1 km north.
    private const OFFICE_LAT = 13.0827000;
    private const OFFICE_LNG = 80.2707000;
    private const NEAR_LAT   = 13.0832000;
    private const FAR_LAT    = 13.0927000;

    private User $staff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 09:00:00');
        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function enableGeofence(int $radius = 200): void
    {
        foreach ([
            'geofence_enabled'   => '1',
            'geofence_latitude'  => (string) self::OFFICE_LAT,
            'geofence_longitude' => (string) self::OFFICE_LNG,
            'geofence_radius_m'  => (string) $radius,
        ] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public function test_distance_calculation_is_accurate(): void
    {
        $service = app(GeofenceService::class);

        $this->assertSame(0, $service->distanceMeters(self::OFFICE_LAT, self::OFFICE_LNG, self::OFFICE_LAT, self::OFFICE_LNG));
        // 0.01 degrees of latitude is about 1,112 m.
        $this->assertEqualsWithDelta(
            1112,
            $service->distanceMeters(self::OFFICE_LAT, self::OFFICE_LNG, self::FAR_LAT, self::OFFICE_LNG),
            3
        );
    }

    public function test_check_in_works_without_location_when_geofence_is_off(): void
    {
        $this->actingAs($this->staff)->post(route('attendance.check-in'))->assertSessionHas('success');

        $attendance = Attendance::firstOrFail();
        $this->assertNull($attendance->check_in_latitude);
        $this->assertNull($attendance->check_in_distance_m);
    }

    public function test_location_is_not_stored_when_geofence_is_off(): void
    {
        $this->actingAs($this->staff)
            ->post(route('attendance.check-in'), ['latitude' => self::NEAR_LAT, 'longitude' => self::OFFICE_LNG])
            ->assertSessionHas('success');

        $this->assertNull(Attendance::firstOrFail()->check_in_latitude);
    }

    public function test_check_in_inside_radius_is_recorded_with_distance(): void
    {
        $this->enableGeofence();

        $this->actingAs($this->staff)
            ->post(route('attendance.check-in'), ['latitude' => self::NEAR_LAT, 'longitude' => self::OFFICE_LNG])
            ->assertSessionHas('success');

        $attendance = Attendance::firstOrFail();
        $this->assertEqualsWithDelta(self::NEAR_LAT, (float) $attendance->check_in_latitude, 0.000001);
        $this->assertEqualsWithDelta(56, $attendance->check_in_distance_m, 3);
    }

    public function test_check_in_outside_radius_is_refused(): void
    {
        $this->enableGeofence();

        $this->actingAs($this->staff)
            ->post(route('attendance.check-in'), ['latitude' => self::FAR_LAT, 'longitude' => self::OFFICE_LNG])
            ->assertSessionHas('error');

        $this->assertSame(0, Attendance::count());
    }

    public function test_check_in_without_location_is_refused_when_geofence_is_on(): void
    {
        $this->enableGeofence();

        $this->actingAs($this->staff)->post(route('attendance.check-in'))->assertSessionHas('error');
        $this->actingAs($this->staff)
            ->post(route('attendance.check-in'), ['latitude' => 'abc', 'longitude' => '999'])
            ->assertSessionHas('error');

        $this->assertSame(0, Attendance::count());
    }

    public function test_check_out_is_also_geofenced(): void
    {
        $this->enableGeofence();
        $inside = ['latitude' => self::NEAR_LAT, 'longitude' => self::OFFICE_LNG];

        $this->actingAs($this->staff)->post(route('attendance.check-in'), $inside);

        $this->actingAs($this->staff)
            ->post(route('attendance.check-out'), ['latitude' => self::FAR_LAT, 'longitude' => self::OFFICE_LNG])
            ->assertSessionHas('error');
        $this->assertNull(Attendance::firstOrFail()->check_out);

        $this->actingAs($this->staff)->post(route('attendance.check-out'), $inside)->assertSessionHas('success');

        $attendance = Attendance::firstOrFail();
        $this->assertNotNull($attendance->check_out);
        $this->assertNotNull($attendance->check_out_distance_m);
    }

    public function test_geofence_switched_on_without_coordinates_is_not_enforced(): void
    {
        Setting::updateOrCreate(['key' => 'geofence_enabled'], ['value' => '1']);

        $this->assertFalse(app(GeofenceService::class)->isEnabled());
        $this->actingAs($this->staff)->post(route('attendance.check-in'))->assertSessionHas('success');
    }

    public function test_admin_can_save_geofence_settings(): void
    {
        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'geofence_enabled'   => '1',
            'geofence_latitude'  => '13.0827',
            'geofence_longitude' => '80.2707',
            'geofence_radius_m'  => '150',
        ])->assertSessionHasNoErrors();

        $config = app(GeofenceService::class)->config();
        $this->assertTrue($config['enabled']);
        $this->assertSame(150, $config['radius_m']);
    }

    public function test_settings_reject_enabling_without_coordinates_or_bad_values(): void
    {
        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'geofence_enabled' => '1',
        ])->assertSessionHasErrors(['geofence_latitude', 'geofence_longitude']);

        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'geofence_enabled'   => '0',
            'geofence_latitude'  => '123',
            'geofence_longitude' => '80',
            'geofence_radius_m'  => '5',
        ])->assertSessionHasErrors(['geofence_latitude', 'geofence_radius_m']);
    }

    // The dashboard itself is not rendered here: its existing queries use
    // MySQL-only time functions and cannot run on the SQLite test database.
    public function test_settings_page_renders_with_geofence_on(): void
    {
        $this->enableGeofence();

        $this->actingAs($this->admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Attendance Geofence');
    }
}
