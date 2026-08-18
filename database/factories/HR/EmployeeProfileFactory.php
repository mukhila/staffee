<?php

namespace Database\Factories\HR;

use App\Models\HR\EmployeeProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeProfile>
 */
class EmployeeProfileFactory extends Factory
{
    protected $model = EmployeeProfile::class;

    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'joining_date'       => now()->subYears(3)->toDateString(),
            'notice_period_days' => 30,
            'current_salary'     => '50000.00',
            'salary_currency'    => 'INR',
            'contract_type'      => 'permanent',
        ];
    }
}
