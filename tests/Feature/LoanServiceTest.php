<?php

namespace Tests\Feature;

use App\Models\Payroll\EmployeeLoan;
use App\Models\Payroll\EmployeeLoanInstallment;
use App\Models\User;
use App\Services\Payroll\LoanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoanService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LoanService::class);
        $this->user    = User::factory()->create();
    }

    public function test_create_loan_generates_correct_installment_count(): void
    {
        $loan = $this->service->createLoan($this->user, [
            'loan_type'             => 'Personal',
            'principal_amount'      => '12000.000000',
            'issued_date'           => '2026-08-01',
            'recovery_start_period' => '2026-09',
            'installment_amount'    => '1000.000000',
            'total_installments'    => 12,
        ], $this->user->id);

        $this->assertSame(12, $loan->installments()->count());
    }

    public function test_installment_amounts_sum_to_principal(): void
    {
        $loan = $this->service->createLoan($this->user, [
            'loan_type'             => 'Education',
            'principal_amount'      => '10000.000000',
            'issued_date'           => '2026-08-01',
            'recovery_start_period' => '2026-09',
            'installment_amount'    => '3000.000000',
            'total_installments'    => 4,
        ], $this->user->id);

        $total = '0.000000';
        foreach ($loan->installments as $inst) {
            $total = bcadd($total, (string) $inst->scheduled_amount, 6);
        }

        $this->assertSame(
            bccomp($total, '10000.000000', 6),
            0,
            "Installments sum {$total} does not equal principal 10000.000000"
        );
    }

    public function test_due_installments_returns_only_correct_period_and_status(): void
    {
        $this->service->createLoan($this->user, [
            'loan_type'             => 'Home',
            'principal_amount'      => '6000.000000',
            'issued_date'           => '2026-08-01',
            'recovery_start_period' => '2026-09',
            'installment_amount'    => '1000.000000',
            'total_installments'    => 6,
        ], $this->user->id);

        $sept = $this->service->dueInstallments($this->user, '2026-09');
        $oct  = $this->service->dueInstallments($this->user, '2026-10');
        $mar  = $this->service->dueInstallments($this->user, '2027-03');

        $this->assertCount(1, $sept);
        $this->assertCount(1, $oct);
        $this->assertCount(0, $mar); // loan only runs 6 months: 2026-09 to 2027-02
    }

    public function test_mark_recovered_updates_balance_and_marks_completed(): void
    {
        $loan = $this->service->createLoan($this->user, [
            'loan_type'             => 'Emergency',
            'principal_amount'      => '2000.000000',
            'issued_date'           => '2026-08-01',
            'recovery_start_period' => '2026-09',
            'installment_amount'    => '1000.000000',
            'total_installments'    => 2,
        ], $this->user->id);

        $installments = $loan->installments()->orderBy('due_period')->get();

        // Recover first installment — loan should still be active
        $this->service->markRecovered($installments[0], '1000.000000');
        $loan->refresh();

        $this->assertSame('active', $loan->status);
        $this->assertSame(0, bccomp($loan->remaining_balance, '1000.000000', 6));

        // Recover second installment — loan should now be completed
        $this->service->markRecovered($installments[1], '1000.000000');
        $loan->refresh();

        $this->assertSame('completed', $loan->status);
        $this->assertSame(0, bccomp($loan->remaining_balance, '0.000000', 6));
    }

    public function test_due_installments_excludes_inactive_loans(): void
    {
        $loan = $this->service->createLoan($this->user, [
            'loan_type'             => 'Vehicle',
            'principal_amount'      => '3000.000000',
            'issued_date'           => '2026-08-01',
            'recovery_start_period' => '2026-09',
            'installment_amount'    => '1000.000000',
            'total_installments'    => 3,
        ], $this->user->id);

        $loan->update(['status' => 'cancelled']);

        $due = $this->service->dueInstallments($this->user, '2026-09');
        $this->assertCount(0, $due);
    }

    public function test_store_endpoint_validates_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->post(route('admin.payroll.loans.store'), []);

        $response->assertSessionHasErrors(['user_id', 'loan_type', 'principal_amount', 'issued_date',
            'recovery_start_period', 'installment_amount', 'total_installments']);
    }

    public function test_store_endpoint_creates_loan_and_installments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $response = $this->post(route('admin.payroll.loans.store'), [
            'user_id'                => $this->user->id,
            'loan_type'              => 'Personal',
            'principal_amount'       => '6000',
            'issued_date'            => '2026-08-01',
            'recovery_start_period'  => '2026-09',
            'installment_amount'     => '1000',
            'total_installments'     => 6,
            'notes'                  => 'Test loan',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employee_loans', ['user_id' => $this->user->id, 'loan_type' => 'Personal']);
        $this->assertSame(6, EmployeeLoanInstallment::whereHas('loan', fn($q) => $q->where('user_id', $this->user->id))->count());
    }
}
