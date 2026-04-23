<?php

namespace App\Events;

use App\Models\HR\ResignationRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResignationSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $employee,
        public readonly ResignationRequest $resignation,
    ) {}
}
