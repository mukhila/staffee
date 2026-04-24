<?php

namespace App\Models\Payroll;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GradeStructure extends Model
{
    protected $table = 'payroll_grade_structures';

    protected $fillable = [
        'code', 'name', 'department_id', 'currency_code', 'pay_frequency',
        'min_ctc', 'max_ctc', 'status', 'effective_from', 'effective_to',
        'created_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'min_ctc' => 'decimal:6',
            'max_ctc' => 'decimal:6',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function salaryStructures()
    {
        return $this->hasMany(EmployeeSalaryStructure::class, 'grade_structure_id');
    }
}
