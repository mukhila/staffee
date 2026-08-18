<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeTaxDeclaration extends Model
{
    protected $table = 'employee_tax_declarations';

    protected $fillable = [
        'user_id',
        'tax_regime_id',
        'fiscal_year',
        'declaration_status',
        'declared_amounts',
        'proof_status',
        'submitted_at',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'declared_amounts' => 'array',
            'proof_status'     => 'array',
            'submitted_at'     => 'datetime',
            'verified_at'      => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function taxRegime()
    {
        return $this->belongsTo(TaxRegime::class, 'tax_regime_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function proofs()
    {
        return $this->hasMany(TaxDeclarationProof::class, 'tax_declaration_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeForFiscalYear(Builder $query, string $fiscalYear): Builder
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('declaration_status', $status);
    }

    // ── Domain helpers ───────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->declaration_status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->declaration_status === 'submitted';
    }

    public function isVerified(): bool
    {
        return $this->declaration_status === 'verified';
    }

    public function isLocked(): bool
    {
        return $this->declaration_status === 'locked';
    }

    public function isEditable(): bool
    {
        return $this->isDraft();
    }

    /**
     * Return total declared amount as a BCMath string.
     */
    public function totalDeclared(): string
    {
        $total = '0';
        foreach ((array) $this->declared_amounts as $amount) {
            $total = bcadd($total, (string) $amount, 6);
        }
        return $total;
    }
}
