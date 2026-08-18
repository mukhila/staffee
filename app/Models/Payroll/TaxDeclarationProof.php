<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class TaxDeclarationProof extends Model
{
    protected $table = 'tax_declaration_proofs';

    protected $fillable = [
        'tax_declaration_id',
        'section',
        'file_path',
        'original_name',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function declaration()
    {
        return $this->belongsTo(EmployeeTaxDeclaration::class, 'tax_declaration_id');
    }
}
