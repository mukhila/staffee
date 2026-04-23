<?php

namespace App\Models\HR;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TransferRequest extends Model
{
    protected $table = 'transfer_requests';

    protected $fillable = [
        'user_id', 'requested_by', 'from_department_id', 'to_department_id',
        'from_role', 'to_role', 'from_reporting_to', 'to_reporting_to',
        'from_designation', 'to_designation', 'effective_date', 'reason',
        'status', 'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['effective_date' => 'date', 'approved_at' => 'datetime'];
    }

    public function employee()       { return $this->belongsTo(User::class, 'user_id'); }
    public function requestedBy()    { return $this->belongsTo(User::class, 'requested_by'); }
    public function fromDepartment() { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment()   { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function approver()       { return $this->belongsTo(User::class, 'approved_by'); }
}
