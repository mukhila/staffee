<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExitChecklistItem extends Model
{
    protected $table = 'exit_checklist_items';

    protected $fillable = [
        'checklist_id', 'category', 'item', 'description',
        'responsible_user_id', 'is_completed', 'completed_by', 'completed_at',
        'notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_completed' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function checklist()     { return $this->belongsTo(ExitChecklist::class, 'checklist_id'); }
    public function responsible()   { return $this->belongsTo(User::class, 'responsible_user_id'); }
    public function completedBy()   { return $this->belongsTo(User::class, 'completed_by'); }

    public function complete(User $user, ?string $notes = null): void
    {
        $this->update([
            'is_completed' => true,
            'completed_by' => $user->id,
            'completed_at' => now(),
            'notes'        => $notes,
        ]);
        $this->checklist->markCompleteIfAllDone();
    }
}
