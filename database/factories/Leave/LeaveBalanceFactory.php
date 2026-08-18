<?php

namespace Database\Factories\Leave;

use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'leave_type_id'      => LeaveType::factory(),
            'year'               => now()->year,
            'opening_balance'    => 0,
            'carry_forward_days' => 0,
            'accrued_days'       => 10,
            'used_days'          => 0,
            'pending_days'       => 0,
        ];
    }
}
