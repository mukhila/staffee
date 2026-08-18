<?php

namespace App\Models\Client;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'gst_number',
        'country',
        'currency',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'client_id');
    }
}
