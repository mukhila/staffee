<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'decimal:4',
            'unit_price' => 'decimal:6',
            'tax_rate'   => 'decimal:2',
            'line_total' => 'decimal:6',
        ];
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
