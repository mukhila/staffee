<?php

namespace App\Events;

use App\Models\HR\TerminationRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TerminationInitiated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $employee,
        public readonly TerminationRequest $termination,
        public readonly User $initiatedBy,
    ) {}
}
