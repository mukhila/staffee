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

class TaxDeclarationTest extends TestCase
{
    use RefreshDatabase;

    private TaxDeclarationService $service;
    private User $staff;
    private User $admin;
    private TaxRegime $regime;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TaxDeclarationService::class);
        $this->admin   = User::factory()->create(['role' => 'admin']);
        $this->staff   = User::factory()->create(['role' => 'staff']);

        $this->regime = TaxRegime::create([
            'country_code'      => 'IN',
            'fiscal_year'       => '2025-26',
            'regime_code'       => 'NEW',
            'name'              => 'New Tax Regime 2025-26',
            'standard_deduction'=> '0.000000',
            'rebate_amount'     => '0.000000',
            'cess_percent'      => '4.000000',
            'status'            => 'active',
            'effective_from'    => '2025-04-01',
        ]);

        Storage::fake('private');
    }

    // ── Feature: Staff can create a draft declaration ────────────────────────

    public function test_staff_can_create_draft_declaration(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000', '80D' => '25000.000000']
        );

        $this->assertDatabaseHas('employee_tax_declarations', [
            'user_id'            => $this->staff->id,
            'fiscal_year'        => '2025-26',
            'declaration_status' => 'draft',
        ]);

        $this->assertSame('draft', $decl->declaration_status);
    }

    // ── Feature: Staff can update a draft declaration ────────────────────────

    public function test_staff_can_update_draft_declaration(): void
    {
        $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '100000.000000']
        );

        $updated = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000', 'HRA' => '60000.000000']
        );

        $this->assertSame('150000.000000', $updated->declared_amounts['80C']);
        $this->assertSame('60000.000000', $updated->declared_amounts['HRA']);

        // Should not create a duplicate row
        $this->assertSame(1, EmployeeTaxDeclaration::where('user_id', $this->staff->id)->count());
    }

    // ── Feature: Staff can submit a draft ────────────────────────────────────

    public function test_staff_can_submit_draft_declaration(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000']
        );

        $this->service->submit($decl);
        $decl->refresh();

        $this->assertSame('submitted', $decl->declaration_status);
        $this->assertNotNull($decl->submitted_at);
    }

    // ── Feature: Staff cannot edit submitted/locked declaration ──────────────

    public function test_staff_cannot_edit_submitted_declaration(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000']
        );

        $this->service->submit($decl);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '200000.000000']
        );
    }

    public function test_staff_cannot_edit_locked_declaration(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000']
        );

        $this->service->submit($decl);
        $this->service->verify($decl, $this->admin);
        $this->service->lock($decl);

        $this->expectException(\InvalidArgumentException::class);

        $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '200000.000000']
        );
    }

    // ── Feature: Staff can upload proof ─────────────────────────────────────

    public function test_staff_can_upload_proof_for_a_section(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000']
        );

        $file = UploadedFile::fake()->create('ppf_statement.pdf', 200, 'application/pdf');

        $proof = $this->service->uploadProof($decl, '80C', $file);

        $this->assertDatabaseHas('tax_declaration_proofs', [
            'tax_declaration_id' => $decl->id,
            'section'            => '80C',
            'original_name'      => 'ppf_statement.pdf',
        ]);

        Storage::disk('private')->assertExists($proof->file_path);

        $decl->refresh();
        $this->assertSame('uploaded', $decl->proof_status['80C']);
    }

    // ── Feature: Staff cannot upload proof for verified/locked declaration ───

    public function test_staff_cannot_upload_proof_after_verification(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000']
        );

        $this->service->submit($decl);
        $this->service->verify($decl, $this->admin);

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $this->expectException(\InvalidArgumentException::class);
        $this->service->uploadProof($decl, '80C', $file);
    }

    // ── Feature: Ownership enforcement ──────────────────────────────────────

    public function test_staff_cannot_submit_another_users_declaration(): void
    {
        $otherUser = User::factory()->create(['role' => 'staff']);

        $decl = $this->service->createOrUpdate(
            $otherUser,
            $this->regime->id,
            '2025-26',
            ['80C' => '100000.000000']
        );

        $this->actingAs($this->staff);

        $response = $this->post(route('staff.tax-declarations.submit', $decl));

        $response->assertForbidden();
    }

    public function test_staff_cannot_upload_proof_for_another_users_declaration(): void
    {
        $otherUser = User::factory()->create(['role' => 'staff']);

        $decl = $this->service->createOrUpdate(
            $otherUser,
            $this->regime->id,
            '2025-26',
            ['80C' => '100000.000000']
        );

        $this->actingAs($this->staff);

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->post(route('staff.tax-declarations.proof', $decl), [
            'section' => '80C',
            'file'    => $file,
        ]);

        $response->assertForbidden();
    }

    // ── Feature: Admin can verify a submitted declaration ────────────────────

    public function test_admin_can_verify_submitted_declaration(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '150000.000000']
        );

        $this->service->submit($decl);

        $this->actingAs($this->admin);

        $response = $this->post(route('admin.payroll.tax-declarations.verify', $decl));

        $response->assertRedirect(route('admin.payroll.tax-declarations.show', $decl));

        $decl->refresh();
        $this->assertSame('verified', $decl->declaration_status);
        $this->assertSame($this->admin->id, $decl->verified_by);
        $this->assertNotNull($decl->verified_at);
    }

    // ── Feature: Status transitions are correct ──────────────────────────────

    public function test_full_status_transition_draft_to_locked(): void
    {
        $decl = $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['HRA' => '72000.000000']
        );
        $this->assertSame('draft', $decl->declaration_status);

        $this->service->submit($decl);
        $decl->refresh();
        $this->assertSame('submitted', $decl->declaration_status);

        $this->service->verify($decl, $this->admin);
        $decl->refresh();
        $this->assertSame('verified', $decl->declaration_status);

        $this->service->lock($decl);
        $decl->refresh();
        $this->assertSame('locked', $decl->declaration_status);
    }

    // ── Feature: Admin index shows declarations ───────────────────────────────

    public function test_admin_can_view_declarations_index(): void
    {
        $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '50000.000000']
        );

        $this->actingAs($this->admin);

        $response = $this->get(route('admin.payroll.tax-declarations.index'));

        $response->assertOk();
        $response->assertSee('Tax Declarations');
    }

    // ── Feature: Staff index only shows own declarations ─────────────────────

    public function test_staff_index_only_shows_own_declarations(): void
    {
        $otherUser = User::factory()->create(['role' => 'staff']);

        $this->service->createOrUpdate(
            $this->staff,
            $this->regime->id,
            '2025-26',
            ['80C' => '50000.000000']
        );

        $this->service->createOrUpdate(
            $otherUser,
            $this->regime->id,
            '2025-26',
            ['80D' => '25000.000000']
        );

        $this->actingAs($this->staff);

        $response = $this->get(route('staff.tax-declarations.index'));

        $response->assertOk();
        $this->assertSame(1, EmployeeTaxDeclaration::where('user_id', $this->staff->id)->count());
    }
}
