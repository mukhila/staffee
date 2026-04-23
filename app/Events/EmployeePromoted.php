<?php

namespace App\Events;

use App\Models\HR\PromotionRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeePromoted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $employee,
        public readonly PromotionRequest $promotion,
        public readonly User $approvedBy,
    ) {}
}
