<?php

namespace Database\Seeders;

use App\Models\HR\EmployeeProfile;
use App\Models\HR\EmployeeEducation;
use App\Models\HR\EmployeeExperience;
use App\Models\HR\EmployeeSkill;
use App\Models\HR\LifecycleEvent;
use App\Models\HR\SalaryRevision;
use App\Models\User;
use Illuminate\Database\Seeder;

class HRSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $this->command->warn('No admin user found — skipping HR seeder.');
            return;
        }

        $employees = User::where('role', '!=', 'admin')->get();
        if ($employees->isEmpty()) {
            $this->command->warn('No staff users found — skipping HR seeder.');
            return;
        }

        $contractTypes = ['permanent', 'fixed_term', 'internship'];
        $locations     = ['office', 'remote', 'hybrid'];
        $genders       = ['male', 'female', 'other'];
        $currencies    = ['USD'];

        $sampleSkills = [
            ['PHP',         'Language',   'advanced'],
            ['Laravel',     'Framework',  'advanced'],
            ['JavaScript',  'Language',   'intermediate'],
            ['Vue.js',      'Framework',  'intermediate'],
            ['MySQL',       'Database',   'advanced'],
            ['Git',         'Tool',       'expert'],
            ['Docker',      'Tool',       'beginner'],
            ['React',       'Framework',  'beginner'],
            ['Python',      'Language',   'intermediate'],
            ['Linux',       'OS',         'intermediate'],
        ];

        $this->command->info("Seeding HR data for {$employees->count()} employees...");

        foreach ($employees as $i => $employee) {
            // Generate a unique employee ID
            $empId = 'EMP-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
            $employee->update([
                'employee_id'        => $empId,
                'designation'        => $this->designation($employee->role),
                'employment_status'  => 'active',
            ]);

            $joiningDate = now()->subMonths(rand(6, 48))->subDays(rand(0, 30));
            $salary      = rand(3000, 12000);

            // ── Profile ────────────────────────────────────────────────────
            EmployeeProfile::updateOrCreate(['user_id' => $employee->id], [
                'date_of_birth'      => now()->subYears(rand(23, 45))->subDays(rand(0, 365)),
                'gender'             => $genders[array_rand($genders)],
                'marital_status'     => rand(0, 1) ? 'single' : 'married',
                'nationality'        => 'Indian',
                'joining_date'       => $joiningDate,
                'probation_end_date' => $joiningDate->copy()->addMonths(3),
                'contract_type'      => $contractTypes[array_rand($contractTypes)],
                'notice_period_days' => [30, 60, 90][array_rand([30, 60, 90])],
                'work_location'      => $locations[array_rand($locations)],
                'current_salary'     => $salary,
                'salary_currency'    => 'USD',
                'bio'                => "Experienced professional with a passion for building great software.",
                'perm_city'          => ['Chennai', 'Bangalore', 'Mumbai', 'Hyderabad'][array_rand([0, 1, 2, 3])],
                'perm_country'       => 'India',
            ]);

            // ── Education ──────────────────────────────────────────────────
            EmployeeEducation::firstOrCreate(
                ['user_id' => $employee->id, 'institution_name' => 'Anna University'],
                [
                    'degree'         => 'Bachelor of Engineering',
                    'field_of_study' => 'Computer Science',
                    'start_year'     => 2016,
                    'end_year'       => 2020,
                    'grade_gpa'      => '8.2 / 10',
                ]
            );

            // ── Experience ─────────────────────────────────────────────────
            EmployeeExperience::firstOrCreate(
                ['user_id' => $employee->id, 'company_name' => 'Tech Corp Pvt Ltd'],
                [
                    'position'        => 'Junior Developer',
                    'employment_type' => 'full_time',
                    'start_date'      => now()->subYears(3),
                    'end_date'        => $joiningDate->copy()->subDay(),
                    'is_current'      => false,
                    'description'     => 'Worked on backend APIs and database design.',
                ]
            );

            EmployeeExperience::firstOrCreate(
                ['user_id' => $employee->id, 'company_name' => 'Current Company'],
                [
                    'position'        => $this->designation($employee->role),
                    'employment_type' => 'full_time',
                    'start_date'      => $joiningDate,
                    'is_current'      => true,
                ]
            );

            // ── Skills ─────────────────────────────────────────────────────
            $picked = array_slice($sampleSkills, rand(0, 3), rand(3, 6));
            foreach ($picked as [$name, $cat, $proficiency]) {
                EmployeeSkill::firstOrCreate(
                    ['user_id' => $employee->id, 'name' => $name],
                    ['category' => $cat, 'proficiency' => $proficiency]
                );
            }

            // ── Salary revision (joining entry) ────────────────────────────
            SalaryRevision::firstOrCreate(
                ['user_id' => $employee->id, 'revision_type' => 'joining'],
                [
                    'effective_date' => $joiningDate,
                    'new_salary'     => $salary,
                    'currency'       => 'USD',
                    'reason'         => 'Initial salary on joining',
                    'created_by'     => $admin->id,
                ]
            );

            // ── Lifecycle: joined ──────────────────────────────────────────
            LifecycleEvent::firstOrCreate(
                ['user_id' => $employee->id, 'event_type' => 'joined'],
                [
                    'title'          => "Joined as {$this->designation($employee->role)}",
                    'effective_date' => $joiningDate,
                    'new_role'       => $employee->role,
                    'new_designation'=> $this->designation($employee->role),
                    'new_salary'     => $salary,
                    'performed_by'   => $admin->id,
                ]
            );
        }

        $this->command->info('✓ HR seeder complete.');
    }

    private function designation(string $role): string
    {
        return match ($role) {
            'pm'    => 'Project Manager',
            'admin' => 'HR Administrator',
            default => ['Software Engineer', 'Senior Developer', 'QA Engineer', 'DevOps Engineer'][rand(0, 3)],
        };
    }
}
