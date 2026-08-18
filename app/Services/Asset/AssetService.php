<?php

namespace App\Services\Asset;

use App\Models\Asset\Asset;
use App\Models\Asset\AssetAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;

class AssetService
{
    /**
     * Assign an available asset to a user.
     */
    public function assign(Asset $asset, User $user, int $actorId): AssetAssignment
    {
        if ($asset->status !== 'available') {
            throw new \InvalidArgumentException(
                "Asset must be 'available' to assign. Current status: {$asset->status}"
            );
        }

        $assignment = AssetAssignment::create([
            'asset_id'    => $asset->id,
            'user_id'     => $user->id,
            'assigned_by' => $actorId,
            'assigned_at' => Carbon::now(),
            'is_current'  => true,
        ]);

        $asset->update(['status' => 'assigned']);

        return $assignment;
    }

    /**
     * Return an assigned asset.
     */
    public function returnAsset(AssetAssignment $assignment, string $condition, ?string $notes, int $actorId): void
    {
        if (! $assignment->is_current) {
            throw new \InvalidArgumentException('This assignment is already closed.');
        }

        $assignment->update([
            'returned_at'      => Carbon::now(),
            'return_condition' => $condition,
            'return_notes'     => $notes,
            'is_current'       => false,
        ]);

        $assignment->asset->update(['status' => 'available']);
    }

    /**
     * Mark an asset as in_repair.
     */
    public function sendForRepair(Asset $asset, int $actorId): void
    {
        if ($asset->status === 'in_repair') {
            throw new \InvalidArgumentException('Asset is already in repair.');
        }

        if (! in_array($asset->status, ['available', 'assigned'])) {
            throw new \InvalidArgumentException(
                "Asset must be 'available' or 'assigned' to send for repair. Current status: {$asset->status}"
            );
        }

        // If currently assigned, close the assignment
        if ($asset->status === 'assigned') {
            $asset->currentAssignment?->update([
                'returned_at' => Carbon::now(),
                'is_current'  => false,
            ]);
        }

        $asset->update(['status' => 'in_repair']);
    }
}
