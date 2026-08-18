<?php

namespace App\Models\Client;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'client_id',
        'project_id',
        'issued_by',
        'invoice_date',
        'due_date',
        'currency',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date'    => 'date',
            'due_date'        => 'date',
            'subtotal'        => 'decimal:6',
            'tax_amount'      => 'decimal:6',
            'discount_amount' => 'decimal:6',
            'total_amount'    => 'decimal:6',
            'amount_paid'     => 'decimal:6',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id')->orderBy('sort_order');
    }

    public function balanceDue(): string
    {
        return bcsub((string) $this->total_amount, (string) $this->amount_paid, 6);
    }
}
