<?php

namespace Tests\Feature;

use App\Models\Payroll\EmployeeTaxDeclaration;
use App\Models\Payroll\TaxRegime;
use App\Models\User;
use App\Services\Payroll\TaxDeclarationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaxDeclarationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxDeclarationService $service;
    private User $user;
    private User $admin;
    private TaxRegime $regime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TaxDeclarationService::class);
        $this->admin   = User::factory()->create(['role' => 'admin']);
        $this->user    = User::factory()->create(['role' => 'staff']);

        $this->regime = TaxRegime::create([
            'country_code'      => 'IN',
            'fiscal_year'       => '2025-26',
            'regime_code'       => 'OLD',
            'name'              => 'Old Tax Regime 2025-26',
            'standard_deduction'=> '50000.000000',
            'rebate_amount'     => '0.000000',
            'cess_percent'      => '4.000000',
            'status'            => 'active',
            'effective_from'    => '2025-04-01',
        ]);

        Storage::fake('private');
    }

    // ── createOrUpdate: valid transitions ────────────────────────────────────

    public function test_create_or_update_creates_draft_when_none_exists(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '100000.000000']
        );

        $this->assertSame('draft', $decl->declaration_status);
        $this->assertSame($this->user->id, $decl->user_id);
        $this->assertSame('2025-26', $decl->fiscal_year);
    }

    public function test_create_or_update_updates_existing_draft(): void
    {
        $first = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '50000.000000']
        );

        $second = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000', '80D' => '25000.000000']
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame('150000.000000', $second->declared_amounts['80C']);
        $this->assertSame('25000.000000', $second->declared_amounts['80D']);
    }

    // ── createOrUpdate: invalid amounts ──────────────────────────────────────

    public function test_create_or_update_rejects_negative_amount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/non-negative/i');

        $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '-5000.000000']
        );
    }

    public function test_create_or_update_accepts_zero_amount(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '0.000000']
        );

        $this->assertSame('draft', $decl->declaration_status);
    }

    // ── State machine: submit ────────────────────────────────────────────────

    public function test_submit_transitions_draft_to_submitted(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '100000.000000']
        );

        $this->service->submit($decl);
        $decl->refresh();

        $this->assertSame('submitted', $decl->declaration_status);
        $this->assertNotNull($decl->submitted_at);
    }

    public function test_submit_fails_if_already_submitted(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '100000.000000']
        );

        $this->service->submit($decl);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->submit($decl);
    }

    // ── State machine: verify ────────────────────────────────────────────────

    public function test_verify_transitions_submitted_to_verified(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['HRA' => '60000.000000']
        );

        $this->service->submit($decl);
        $this->service->verify($decl, $this->admin);
        $decl->refresh();

        $this->assertSame('verified', $decl->declaration_status);
        $this->assertSame($this->admin->id, $decl->verified_by);
        $this->assertNotNull($decl->verified_at);
    }

    public function test_verify_fails_if_not_submitted(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '100000.000000']
        );

        // Still in draft — cannot verify
        $this->expectException(\InvalidArgumentException::class);
        $this->service->verify($decl, $this->admin);
    }

    // ── State machine: lock ──────────────────────────────────────────────────

    public function test_lock_transitions_verified_to_locked(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['LTA' => '30000.000000']
        );

        $this->service->submit($decl);
        $this->service->verify($decl, $this->admin);
        $this->service->lock($decl);
        $decl->refresh();

        $this->assertSame('locked', $decl->declaration_status);
    }

    public function test_lock_fails_if_not_verified(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '100000.000000']
        );

        $this->service->submit($decl);
        // verified step skipped — should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->service->lock($decl);
    }

    // ── uploadProof: stores on private disk ──────────────────────────────────

    public function test_upload_proof_stores_on_private_disk(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000']
        );

        $file  = UploadedFile::fake()->create('elss_statement.pdf', 300, 'application/pdf');
        $proof = $this->service->uploadProof($decl, '80C', $file);

        Storage::disk('private')->assertExists($proof->file_path);
        $this->assertSame('elss_statement.pdf', $proof->original_name);
        $this->assertSame('80C', $proof->section);
    }

    public function test_upload_proof_updates_proof_status_json(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80D' => '25000.000000']
        );

        $file = UploadedFile::fake()->create('health_ins.jpg', 150, 'image/jpeg');
        $this->service->uploadProof($decl, '80D', $file);

        $decl->refresh();
        $this->assertSame('uploaded', $decl->proof_status['80D']);
    }

    // ── currentForUser ───────────────────────────────────────────────────────

    public function test_current_for_user_returns_existing_declaration(): void
    {
        $this->service->createOrUpdate(
            $this->user,
            $this->regime->id,
            '2025-26',
            ['80C' => '50000.000000']
        );

        $found = $this->service->currentForUser($this->user, '2025-26');

        $this->assertNotNull($found);
        $this->assertSame($this->user->id, $found->user_id);
    }

    public function test_current_for_user_returns_null_when_none_exists(): void
    {
        $result = $this->service->currentForUser($this->user, '2024-25');

        $this->assertNull($result);
    }
}
