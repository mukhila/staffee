<?php

namespace App\Models\Expense;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ExpenseClaim extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'expense_category_id',
        'title',
        'description',
        'amount',
        'currency',
        'expense_date',
        'receipt_path',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:6',
            'expense_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at'  => 'datetime',
            'paid_at'      => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'submitted');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
