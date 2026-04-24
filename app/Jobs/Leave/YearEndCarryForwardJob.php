<?php

namespace App\Jobs\Leave;

use App\Models\Leave\LeaveType;
use App\Models\User;
use App\Services\Leave\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class YearEndCarryForwardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $fromYear) {}

    public function handle(LeaveBalanceService $balanceSvc): void
    {
        Log::info("YearEndCarryForwardJob: processing carry-forward from {$this->fromYear}");

        $total = 0;

        LeaveType::active()->get()->each(function (LeaveType $type) use ($balanceSvc, &$total) {
            User::active()->excludeAdmin()->each(function (User $user) use ($type, $balanceSvc, &$total) {
                $carried = $balanceSvc->runYearEndCarryForward($user, $type, $this->fromYear);
                if ($carried > 0) {
                    $total++;
                    Log::debug("Carried forward {$carried} days of {$type->code} for user #{$user->id}");
                }
            });
        });

        Log::info("YearEndCarryForwardJob: {$total} carry-forward entries created");
    }
}
