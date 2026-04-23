<?php

namespace Database\Seeders;

use App\Models\Shift\Shift;
use App\Models\Shift\ShiftAssignment;
use App\Models\Shift\ShiftHoliday;
use App\Models\Shift\ShiftPattern;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->command->warn('No admin user — skipping ShiftSeeder.');
            return;
        }

        $this->command->info('Seeding shifts...');

        // ── 1. Morning Shift (Fixed, 9–6) ─────────────────────────────────────
        $morning = Shift::updateOrCreate(['code' => 'M1'], [
            'name'                       => 'Morning Shift',
            'shift_type'                 => 'fixed',
            'start_time'                 => '09:00:00',
            'end_time'                   => '18:00:00',
            'crosses_midnight'           => false,
            'break_duration_minutes'     => 60,
            'grace_in_minutes'           => 10,
            'grace_out_minutes'          => 10,
            'overtime_threshold_minutes' => 30,
            'min_hours_for_full_day'     => 8,
            'half_day_threshold_hours'   => 4,
            'working_days'               => ['Mon','Tue','Wed','Thu','Fri'],
            'color'                      => '#3B82F6',
            'timezone'                   => 'Asia/Kolkata',
            'description'                => 'Standard 9 to 6 office shift',
            'is_active'                  => true,
            'created_by'                 => $admin->id,
        ]);

        // ── 2. Night Shift (10 PM – 6 AM, crosses midnight) ───────────────────
        $night = Shift::updateOrCreate(['code' => 'N1'], [
            'name'                       => 'Night Shift',
            'shift_type'                 => 'night',
            'start_time'                 => '22:00:00',
            'end_time'                   => '06:00:00',
            'crosses_midnight'           => true,
            'break_duration_minutes'     => 30,
            'grace_in_minutes'           => 15,
            'grace_out_minutes'          => 10,
            'overtime_threshold_minutes' => 30,
            'min_hours_for_full_day'     => 7,
            'half_day_threshold_hours'   => 4,
            'working_days'               => ['Mon','Tue','Wed','Thu','Fri'],
            'color'                      => '#1E1B4B',
            'timezone'                   => 'Asia/Kolkata',
            'description'                => 'Night support shift (10 PM to 6 AM)',
            'is_active'                  => true,
            'created_by'                 => $admin->id,
        ]);

        // ── 3. Flexible Shift (arrive 8–10 AM, work 9 hours) ──────────────────
        $flexible = Shift::updateOrCreate(['code' => 'F1'], [
            'name'                       => 'Flexible Hours',
            'shift_type'                 => 'flexible',
            'start_time'                 => '08:00:00',
            'end_time'                   => '20:00:00',
            'crosses_midnight'           => false,
            'break_duration_minutes'     => 60,
            'grace_in_minutes'           => 0,
            'grace_out_minutes'          => 0,
            'overtime_threshold_minutes' => 30,
            'min_hours_for_full_day'     => 9,
            'half_day_threshold_hours'   => 5,
            'flexible_window_start'      => '08:00:00',
            'flexible_window_end'        => '10:00:00',
            'flexible_duration_hours'    => 9,
            'working_days'               => ['Mon','Tue','Wed','Thu','Fri'],
            'color'                      => '#06B6D4',
            'timezone'                   => 'Asia/Kolkata',
            'description'                => 'Arrive anytime 8–10 AM, work 9 hours',
            'is_active'                  => true,
            'created_by'                 => $admin->id,
        ]);

        // ── 4. Rotating Shift (2 on / 2 off, 6 AM – 2 PM) ────────────────────
        $rotating = Shift::updateOrCreate(['code' => 'R1'], [
            'name'                       => 'Rotating Shift A',
            'shift_type'                 => 'rotating',
            'start_time'                 => '06:00:00',
            'end_time'                   => '14:00:00',
            'crosses_midnight'           => false,
            'break_duration_minutes'     => 30,
            'grace_in_minutes'           => 10,
            'grace_out_minutes'          => 10,
            'overtime_threshold_minutes' => 30,
            'min_hours_for_full_day'     => 7,
            'half_day_threshold_hours'   => 4,
            'working_days'               => ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
            'color'                      => '#F59E0B',
            'timezone'                   => 'Asia/Kolkata',
            'description'                => '2-day work / 2-day off cycle',
            'is_active'                  => true,
            'created_by'                 => $admin->id,
        ]);

        // Pattern: 4-day cycle, days 1+2 work, days 3+4 off
        $pattern = ShiftPattern::updateOrCreate(
            ['shift_id' => $rotating->id],
            ['name' => '2-on 2-off', 'cycle_length_days' => 4, 'description' => 'Work 2 days, rest 2 days']
        );
        $pattern->days()->delete();
        foreach ([1 => true, 2 => true, 3 => false, 4 => false] as $day => $working) {
            $pattern->days()->create(['day_number' => $day, 'is_working_day' => $working]);
        }

        // ── Assign shifts to employees ─────────────────────────────────────────
        $employees = User::where('role', '!=', 'admin')->get();
        $shiftPool = [$morning, $morning, $morning, $flexible, $night]; // weighted
        $from      = Carbon::today()->subMonths(3);

        foreach ($employees as $i => $emp) {
            $shift = $shiftPool[$i % count($shiftPool)];
            ShiftAssignment::updateOrCreate(
                ['user_id' => $emp->id, 'status' => 'active'],
                [
                    'shift_id'            => $shift->id,
                    'effective_from'      => $from->toDateString(),
                    'effective_to'        => null,
                    'assigned_by'         => $admin->id,
                    'status'              => 'active',
                    'pattern_anchor_date' => $shift->isRotating() ? $from->toDateString() : null,
                ]
            );
        }

        // ── Sample holidays ────────────────────────────────────────────────────
        $holidays = [
            ['name' => 'Republic Day',       'date' => '2026-01-26', 'type' => 'national', 'recurring' => true],
            ['name' => 'Independence Day',   'date' => '2026-08-15', 'type' => 'national', 'recurring' => true],
            ['name' => 'Gandhi Jayanti',     'date' => '2026-10-02', 'type' => 'national', 'recurring' => true],
            ['name' => 'Company Foundation', 'date' => '2026-06-01', 'type' => 'company',  'recurring' => true],
            ['name' => 'Diwali',             'date' => '2026-11-05', 'type' => 'national', 'recurring' => false],
        ];

        foreach ($holidays as $h) {
            ShiftHoliday::firstOrCreate(
                ['date' => $h['date']],
                ['name' => $h['name'], 'holiday_type' => $h['type'], 'is_recurring' => $h['recurring'], 'is_active' => true]
            );
        }

        $this->command->info('✓ ShiftSeeder complete. 4 shifts, ' . $employees->count() . ' assignments, ' . count($holidays) . ' holidays.');
    }
}
