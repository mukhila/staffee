<?php

namespace App\Services\Expense;

use App\Models\Expense\ExpenseClaim;
use Illuminate\Support\Carbon;

class ExpenseService
{
    public function submit(ExpenseClaim $claim, int $actorId): void
    {
        if ($claim->status !== 'draft') {
            throw new \InvalidArgumentException("Claim must be in draft status to submit. Current status: {$claim->status}");
        }

        $claim->update([
            'status'       => 'submitted',
            'submitted_at' => Carbon::now(),
        ]);
    }

    public function approve(ExpenseClaim $claim, int $reviewerId, ?string $notes): void
    {
        if ($claim->status !== 'submitted') {
            throw new \InvalidArgumentException("Claim must be in submitted status to approve. Current status: {$claim->status}");
        }

        $claim->update([
            'status'       => 'approved',
            'reviewed_by'  => $reviewerId,
            'reviewed_at'  => Carbon::now(),
            'review_notes' => $notes,
        ]);
    }

    public function reject(ExpenseClaim $claim, int $reviewerId, string $notes): void
    {
        if ($claim->status !== 'submitted') {
            throw new \InvalidArgumentException("Claim must be in submitted status to reject. Current status: {$claim->status}");
        }

        $claim->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewerId,
            'reviewed_at'  => Carbon::now(),
            'review_notes' => $notes,
        ]);
    }

    public function markPaid(ExpenseClaim $claim, int $actorId): void
    {
        if ($claim->status !== 'approved') {
            throw new \InvalidArgumentException("Claim must be in approved status to mark as paid. Current status: {$claim->status}");
        }

        $claim->update([
            'status'  => 'paid',
            'paid_at' => Carbon::now(),
        ]);
    }
}
