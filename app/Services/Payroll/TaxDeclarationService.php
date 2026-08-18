<?php

namespace App\Services\Payroll;

use App\Models\Payroll\EmployeeTaxDeclaration;
use App\Models\Payroll\TaxDeclarationProof;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class TaxDeclarationService
{
    /**
     * Create or update a draft tax declaration for a user in the given fiscal year.
     *
     * Rules:
     * - If no declaration exists for the user+FY, create a new draft.
     * - If a declaration exists and is in 'draft' status, update it.
     * - Otherwise throw an exception.
     *
     * @param  array<string, string>  $declaredAmounts  BCMath string values keyed by section (80C, 80D, HRA, LTA…)
     *
     * @throws \InvalidArgumentException if declaration is not editable or amounts are invalid
     */
    public function createOrUpdate(
        User $user,
        int $taxRegimeId,
        string $fiscalYear,
        array $declaredAmounts
    ): EmployeeTaxDeclaration {
        $this->validateAmounts($declaredAmounts);

        $declaration = EmployeeTaxDeclaration::where('user_id', $user->id)
            ->where('fiscal_year', $fiscalYear)
            ->first();

        if ($declaration === null) {
            return EmployeeTaxDeclaration::create([
                'user_id'            => $user->id,
                'tax_regime_id'      => $taxRegimeId,
                'fiscal_year'        => $fiscalYear,
                'declaration_status' => 'draft',
                'declared_amounts'   => $declaredAmounts,
                'proof_status'       => null,
            ]);
        }

        if (! $declaration->isEditable()) {
            throw new \InvalidArgumentException(
                "Cannot edit a declaration with status '{$declaration->declaration_status}'."
            );
        }

        $declaration->update([
            'tax_regime_id'    => $taxRegimeId,
            'declared_amounts' => $declaredAmounts,
        ]);

        return $declaration->fresh();
    }

    /**
     * Transition a draft declaration to 'submitted'.
     *
     * @throws \InvalidArgumentException if not in draft status
     */
    public function submit(EmployeeTaxDeclaration $declaration): void
    {
        if (! $declaration->isDraft()) {
            throw new \InvalidArgumentException(
                "Only draft declarations can be submitted. Current status: '{$declaration->declaration_status}'."
            );
        }

        $declaration->update([
            'declaration_status' => 'submitted',
            'submitted_at'       => now(),
        ]);
    }

    /**
     * Transition a submitted declaration to 'verified'.
     *
     * @throws \InvalidArgumentException if not in submitted status
     */
    public function verify(EmployeeTaxDeclaration $declaration, User $verifier): void
    {
        if (! $declaration->isSubmitted()) {
            throw new \InvalidArgumentException(
                "Only submitted declarations can be verified. Current status: '{$declaration->declaration_status}'."
            );
        }

        $declaration->update([
            'declaration_status' => 'verified',
            'verified_by'        => $verifier->id,
            'verified_at'        => now(),
        ]);
    }

    /**
     * Transition a verified declaration to 'locked'.
     * Called by payroll run — not exposed directly to users.
     *
     * @throws \InvalidArgumentException if not in verified status
     */
    public function lock(EmployeeTaxDeclaration $declaration): void
    {
        if (! $declaration->isVerified()) {
            throw new \InvalidArgumentException(
                "Only verified declarations can be locked. Current status: '{$declaration->declaration_status}'."
            );
        }

        $declaration->update(['declaration_status' => 'locked']);
    }

    /**
     * Store a proof file for a given section on the 'private' disk.
     * Updates the proof_status JSON with the new status.
     *
     * @throws \InvalidArgumentException if declaration is not editable (draft or submitted)
     */
    public function uploadProof(
        EmployeeTaxDeclaration $declaration,
        string $section,
        UploadedFile $file
    ): TaxDeclarationProof {
        // Allow proof upload for draft and submitted states (not verified/locked)
        if ($declaration->isVerified() || $declaration->isLocked()) {
            throw new \InvalidArgumentException(
                "Cannot upload proof for a declaration with status '{$declaration->declaration_status}'."
            );
        }

        $path = $file->store(
            "tax-declarations/{$declaration->id}/{$section}",
            'private'
        );

        $proof = TaxDeclarationProof::create([
            'tax_declaration_id' => $declaration->id,
            'section'            => $section,
            'file_path'          => $path,
            'original_name'      => $file->getClientOriginalName(),
            'uploaded_at'        => now(),
        ]);

        // Update proof_status JSON for the section
        $proofStatus = (array) ($declaration->proof_status ?? []);
        $proofStatus[$section] = 'uploaded';
        $declaration->update(['proof_status' => $proofStatus]);

        return $proof;
    }

    /**
     * Return the current (most recent) declaration for a user in the given fiscal year.
     */
    public function currentForUser(User $user, string $fiscalYear): ?EmployeeTaxDeclaration
    {
        return EmployeeTaxDeclaration::where('user_id', $user->id)
            ->where('fiscal_year', $fiscalYear)
            ->first();
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Validate that all declared amounts are non-negative BCMath-compatible strings.
     *
     * @param  array<string, string>  $amounts
     *
     * @throws \InvalidArgumentException
     */
    private function validateAmounts(array $amounts): void
    {
        foreach ($amounts as $section => $amount) {
            if (! is_string($amount) && ! is_numeric($amount)) {
                throw new \InvalidArgumentException(
                    "Amount for section '{$section}' must be a numeric string."
                );
            }
            if (bccomp((string) $amount, '0', 6) < 0) {
                throw new \InvalidArgumentException(
                    "Amount for section '{$section}' must be non-negative. Got: '{$amount}'."
                );
            }
        }
    }
}
