<?php

namespace Tests\Feature;

use App\Models\Expense\ExpenseClaim;
use App\Models\User;
use App\Services\Expense\ExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseClaimTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseService $service;
    private User $staff;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExpenseService::class);
        $this->staff   = User::factory()->create(['role' => 'staff']);
        $this->admin   = User::factory()->create(['role' => 'admin']);
    }

    public function test_staff_can_create_expense_claim(): void
    {
        $this->actingAs($this->staff);

        $response = $this->post(route('staff.expenses.store'), [
            'title'        => 'Office Supplies',
            'amount'       => '500',
            'currency'     => 'INR',
            'expense_date' => '2026-08-10',
            'action'       => 'draft',
        ]);

        $response->assertRedirect(route('staff.expenses.index'));
        $this->assertDatabaseHas('expense_claims', [
            'user_id' => $this->staff->id,
            'title'   => 'Office Supplies',
            'status'  => 'draft',
        ]);
    }

    public function test_staff_can_submit_expense_claim(): void
    {
        $this->actingAs($this->staff);

        $response = $this->post(route('staff.expenses.store'), [
            'title'        => 'Travel Expense',
            'amount'       => '1500',
            'currency'     => 'INR',
            'expense_date' => '2026-08-10',
            'action'       => 'submit',
        ]);

        $response->assertRedirect(route('staff.expenses.index'));
        $this->assertDatabaseHas('expense_claims', [
            'user_id' => $this->staff->id,
            'title'   => 'Travel Expense',
            'status'  => 'submitted',
        ]);
    }

    public function test_staff_cannot_access_another_users_claim(): void
    {
        $other = User::factory()->create(['role' => 'staff']);
        $claim = ExpenseClaim::create([
            'user_id'      => $other->id,
            'title'        => 'Other Claim',
            'amount'       => '100.000000',
            'currency'     => 'INR',
            'expense_date' => '2026-08-10',
            'status'       => 'draft',
        ]);

        $this->actingAs($this->staff);
        $response = $this->get(route('staff.expenses.show', $claim));
        $response->assertStatus(403);
    }

    public function test_admin_can_approve_submitted_claim(): void
    {
        $claim = ExpenseClaim::create([
            'user_id'      => $this->staff->id,
            'title'        => 'Meal Expense',
            'amount'       => '200.000000',
            'currency'     => 'INR',
            'expense_date' => '2026-08-10',
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->service->approve($claim, $this->admin->id, 'Looks good');

        $claim->refresh();
        $this->assertSame('approved', $claim->status);
        $this->assertSame($this->admin->id, $claim->reviewed_by);
        $this->assertSame('Looks good', $claim->review_notes);
    }

    public function test_admin_can_reject_submitted_claim(): void
    {
        $claim = ExpenseClaim::create([
            'user_id'      => $this->staff->id,
            'title'        => 'Invalid Expense',
            'amount'       => '50.000000',
            'currency'     => 'INR',
            'expense_date' => '2026-08-10',
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->service->reject($claim, $this->admin->id, 'No receipt provided');

        $claim->refresh();
        $this->assertSame('rejected', $claim->status);
        $this->assertSame('No receipt provided', $claim->review_notes);
    }

    public function test_submit_throws_if_not_draft(): void
    {
        $claim = ExpenseClaim::create([
            'user_id'      => $this->staff->id,
            'title'        => 'Already Submitted',
            'amount'       => '300.000000',
            'currency'     => 'INR',
            'expense_date' => '2026-08-10',
            'status'       => 'submitted',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->submit($claim, $this->staff->id);
    }

    public function test_approve_throws_if_not_submitted(): void
    {
        $claim = ExpenseClaim::create([
            'user_id'      => $this->staff->id,
            'title'        => 'Draft Claim',
            'amount'       => '300.000000',
            'currency'     => 'INR',
            'expense_date' => '2026-08-10',
            'status'       => 'draft',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->approve($claim, $this->admin->id, null);
    }
}
