<?php

namespace Database\Factories\Leave;

use App\Models\Leave\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->words(2, true),
            'code'              => strtoupper($this->faker->unique()->lexify('LT_???')),
            'category'          => 'paid_annual',
            'color'             => '#6366f1',
            'is_paid'           => true,
            'is_encashable'     => false,
            'requires_approval' => true,
            'max_days_per_year' => 21,
            'allow_half_day'    => true,
            'requires_document' => false,
            'is_active'         => true,
        ];
    }

    /**
     * Mark leave type as encashable (and paid — required for encashment).
     */
    public function encashable(): static
    {
        return $this->state(['is_paid' => true, 'is_encashable' => true]);
    }

    /**
     * Mark leave type as unpaid and non-encashable.
     */
    public function unpaid(): static
    {
        return $this->state(['is_paid' => false, 'is_encashable' => false, 'category' => 'unpaid']);
    }
}
