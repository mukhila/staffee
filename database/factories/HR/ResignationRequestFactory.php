<?php

namespace Database\Factories\HR;

use App\Models\HR\ResignationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResignationRequest>
 */
class ResignationRequestFactory extends Factory
{
    protected $model = ResignationRequest::class;

    public function definition(): array
    {
        $submitted = now()->subDays(30);

        return [
            'user_id'              => User::factory(),
            'submitted_date'       => $submitted->toDateString(),
            'requested_last_date'  => $submitted->copy()->addDays(30)->toDateString(),
            'official_last_date'   => $submitted->copy()->addDays(30)->toDateString(),
            'notice_period_days'   => 30,
            'resignation_type'     => 'voluntary',
            'reason'               => 'Personal reasons',
            'notice_waived'        => false,
            'status'               => 'approved',
        ];
    }

    /**
     * HR-approved status — use this for settlement tests.
     *
     * NOTE (P-21): The SettlementService queries status = 'hr_approved', but the
     * resignation_requests migration's enum only contains 'approved' (not 'hr_approved').
     * That is a bug: either the migration enum or the service query needs to be aligned.
     * Using 'hr_approved' here so tests exercise the actual service code path.
     */
    public function hrApproved(): static
    {
        return $this->state(['status' => 'hr_approved']);
    }

    /**
     * Notice waived — settlement should produce zero shortfall.
     */
    public function noticeWaived(): static
    {
        return $this->state(['notice_waived' => true, 'status' => 'hr_approved']);
    }
}
