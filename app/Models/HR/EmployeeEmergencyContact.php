<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeEmergencyContact extends Model
{
    protected $table = 'employee_emergency_contacts';

    protected $fillable = [
        'user_id', 'name', 'relationship', 'phone',
        'alt_phone', 'email', 'address', 'is_primary',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function employee() { return $this->belongsTo(User::class, 'user_id'); }
}
