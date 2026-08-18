<?php

namespace App\Models\Expense;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'requires_receipt',
    ];

    protected function casts(): array
    {
        return [
            'requires_receipt' => 'boolean',
        ];
    }

    public function claims()
    {
        return $this->hasMany(ExpenseClaim::class, 'expense_category_id');
    }
}
