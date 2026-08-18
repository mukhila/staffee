<?php

namespace Tests\Feature;

use App\Models\Client\Client;
use App\Models\Client\Invoice;
use App\Models\User;
use App\Services\Client\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;
    private User $admin;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InvoiceService::class);
        $this->admin   = User::factory()->create(['role' => 'admin']);
        $this->client  = Client::create([
            'name'     => 'Acme Corp',
            'currency' => 'INR',
        ]);
    }

    public function test_create_invoice_computes_totals_using_bcmath(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'invoice_date' => '2026-08-18',
            'due_date'     => '2026-09-18',
            'currency'     => 'INR',
            'items'        => [
                [
                    'description' => 'Development Services',
                    'quantity'    => '10',
                    'unit_price'  => '5000',
                    'tax_rate'    => '18',
                ],
            ],
        ], $this->admin->id);

        // subtotal = 10 * 5000 = 50000
        $this->assertSame(0, bccomp((string) $invoice->subtotal, '50000.000000', 6));
        // tax = 50000 * 18 / 100 = 9000
        $this->assertSame(0, bccomp((string) $invoice->tax_amount, '9000.000000', 6));
        // total = 50000 + 9000 = 59000
        $this->assertSame(0, bccomp((string) $invoice->total_amount, '59000.000000', 6));
        $this->assertSame('INR', $invoice->currency);
    }

    public function test_create_invoice_with_multiple_items_and_discount(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'invoice_date'    => '2026-08-18',
            'due_date'        => '2026-09-18',
            'currency'        => 'INR',
            'discount_amount' => '500',
            'items'           => [
                [
                    'description' => 'Item 1',
                    'quantity'    => '2',
                    'unit_price'  => '1000',
                    'tax_rate'    => '0',
                ],
                [
                    'description' => 'Item 2',
                    'quantity'    => '1',
                    'unit_price'  => '3000',
                    'tax_rate'    => '10',
                ],
            ],
        ], $this->admin->id);

        // subtotal = (2*1000) + (1*3000) = 5000
        $this->assertSame(0, bccomp((string) $invoice->subtotal, '5000.000000', 6));
        // tax = 0 + 300 = 300
        $this->assertSame(0, bccomp((string) $invoice->tax_amount, '300.000000', 6));
        // total = 5000 + 300 - 500 = 4800
        $this->assertSame(0, bccomp((string) $invoice->total_amount, '4800.000000', 6));
        $this->assertCount(2, $invoice->items);
    }

    public function test_record_payment_updates_amount_paid_and_status_to_partial(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'invoice_date' => '2026-08-18',
            'due_date'     => '2026-09-18',
            'currency'     => 'INR',
            'items'        => [
                ['description' => 'Service', 'quantity' => '1', 'unit_price' => '10000', 'tax_rate' => '0'],
            ],
        ], $this->admin->id);

        $this->service->recordPayment($invoice, '4000', $this->admin->id);
        $invoice->refresh();

        $this->assertSame('partial', $invoice->status);
        $this->assertSame(0, bccomp((string) $invoice->amount_paid, '4000.000000', 6));
    }

    public function test_record_payment_flips_status_to_paid_when_fully_paid(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'invoice_date' => '2026-08-18',
            'due_date'     => '2026-09-18',
            'currency'     => 'INR',
            'items'        => [
                ['description' => 'Service', 'quantity' => '1', 'unit_price' => '5000', 'tax_rate' => '0'],
            ],
        ], $this->admin->id);

        $this->service->recordPayment($invoice, '5000', $this->admin->id);
        $invoice->refresh();

        $this->assertSame('paid', $invoice->status);
        $this->assertSame(0, bccomp((string) $invoice->amount_paid, '5000.000000', 6));
    }

    public function test_invoice_number_auto_generated_in_correct_format(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'invoice_date' => '2026-08-18',
            'due_date'     => '2026-09-18',
            'currency'     => 'INR',
            'items'        => [
                ['description' => 'Test', 'quantity' => '1', 'unit_price' => '100', 'tax_rate' => '0'],
            ],
        ], $this->admin->id);

        $this->assertMatchesRegularExpression('/^INV-\d{6}-\d{5}$/', $invoice->invoice_number);
        $this->assertStringStartsWith('INV-202608-', $invoice->invoice_number);
        $this->assertSame('INV-202608-00001', $invoice->invoice_number);
    }

    public function test_invoice_number_sequential_within_same_month(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->service->createInvoice($this->client, [
                'invoice_date' => '2026-08-18',
                'due_date'     => '2026-09-18',
                'currency'     => 'INR',
                'items'        => [
                    ['description' => "Item {$i}", 'quantity' => '1', 'unit_price' => '100', 'tax_rate' => '0'],
                ],
            ], $this->admin->id);
        }

        $numbers = Invoice::pluck('invoice_number')->sort()->values()->all();
        $this->assertSame('INV-202608-00001', $numbers[0]);
        $this->assertSame('INV-202608-00002', $numbers[1]);
        $this->assertSame('INV-202608-00003', $numbers[2]);
    }

    public function test_mark_sent_throws_if_not_draft(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'invoice_date' => '2026-08-18',
            'due_date'     => '2026-09-18',
            'currency'     => 'INR',
            'items'        => [
                ['description' => 'Test', 'quantity' => '1', 'unit_price' => '100', 'tax_rate' => '0'],
            ],
        ], $this->admin->id);

        // Mark sent once (ok)
        $this->service->markSent($invoice, $this->admin->id);
        $invoice->refresh();
        $this->assertSame('sent', $invoice->status);

        // Attempt to mark sent again — should throw
        $this->expectException(\InvalidArgumentException::class);
        $this->service->markSent($invoice, $this->admin->id);
    }

    public function test_balance_due_method_uses_bcmath(): void
    {
        $invoice = $this->service->createInvoice($this->client, [
            'invoice_date' => '2026-08-18',
            'due_date'     => '2026-09-18',
            'currency'     => 'INR',
            'items'        => [
                ['description' => 'Service', 'quantity' => '3', 'unit_price' => '1000', 'tax_rate' => '0'],
            ],
        ], $this->admin->id);

        $this->service->recordPayment($invoice, '1000', $this->admin->id);
        $invoice->refresh();

        $balance = $invoice->balanceDue();
        $this->assertIsString($balance);
        $this->assertSame(0, bccomp($balance, '2000.000000', 6));
    }
}
