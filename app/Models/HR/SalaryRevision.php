<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SalaryRevision extends Model
{
    protected $table = 'salary_revisions';

    protected $fillable = [
        'user_id', 'effective_date', 'old_salary', 'new_salary', 'currency',
        'revision_type', 'percentage_change', 'reason',
        'reference_type', 'reference_id', 'approved_by', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date'    => 'date',
            'old_salary'        => 'decimal:2',
            'new_salary'        => 'decimal:2',
            'percentage_change' => 'decimal:2',
        ];
    }

    public function employee()  { return $this->belongsTo(User::class, 'user_id'); }
    public function approver()  { return $this->belongsTo(User::class, 'approved_by'); }
    public function creator()   { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeLatestFirst($query) { return $query->orderByDesc('effective_date'); }
}
