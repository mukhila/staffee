<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExitChecklist extends Model
{
    protected $table = 'exit_checklists';

    protected $fillable = ['termination_id', 'user_id', 'is_complete', 'completed_at'];

    protected function casts(): array
    {
        return ['is_complete' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function termination() { return $this->belongsTo(TerminationRequest::class, 'termination_id'); }
    public function employee()    { return $this->belongsTo(User::class, 'user_id'); }
    public function items()       { return $this->hasMany(ExitChecklistItem::class, 'checklist_id'); }

    public function completionPercentage(): int
    {
        $total = $this->items()->count();
        if ($total === 0) return 0;
        $done = $this->items()->where('is_completed', true)->count();
        return (int) round(($done / $total) * 100);
    }

    public function markCompleteIfAllDone(): bool
    {
        if ($this->items()->where('is_completed', false)->exists()) {
            return false;
        }
        $this->update(['is_complete' => true, 'completed_at' => now()]);
        return true;
    }
}
