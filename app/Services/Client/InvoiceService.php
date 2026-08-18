<?php

namespace App\Services\Client;

use App\Models\Client\Client;
use App\Models\Client\Invoice;
use App\Models\Client\InvoiceItem;
use Illuminate\Support\Carbon;

class InvoiceService
{
    /**
     * Create invoice with line items; auto-compute subtotal, tax, total.
     */
    public function createInvoice(Client $client, array $data, int $actorId): Invoice
    {
        $items = $data['items'] ?? [];

        $subtotal   = '0.000000';
        $taxAmount  = '0.000000';

        $preparedItems = [];
        $sortOrder = 0;

        foreach ($items as $item) {
            $qty       = (string) $item['quantity'];
            $unitPrice = (string) $item['unit_price'];
            $taxRate   = (string) ($item['tax_rate'] ?? '0');

            // line_total = quantity * unit_price (before tax)
            $lineTotal = bcmul($qty, $unitPrice, 6);

            // tax for this item = line_total * tax_rate / 100
            $itemTax = bcmul($lineTotal, bcdiv($taxRate, '100', 10), 6);

            $subtotal  = bcadd($subtotal, $lineTotal, 6);
            $taxAmount = bcadd($taxAmount, $itemTax, 6);

            $preparedItems[] = [
                'description' => $item['description'],
                'quantity'    => $qty,
                'unit_price'  => $unitPrice,
                'tax_rate'    => $taxRate,
                'line_total'  => $lineTotal,
                'sort_order'  => $sortOrder++,
            ];
        }

        $discount    = (string) ($data['discount_amount'] ?? '0');
        $totalAmount = bcsub(bcadd($subtotal, $taxAmount, 6), $discount, 6);

        $invoiceNumber = $this->generateInvoiceNumber();

        $invoice = Invoice::create([
            'invoice_number'  => $invoiceNumber,
            'client_id'       => $client->id,
            'project_id'      => $data['project_id'] ?? null,
            'issued_by'       => $actorId,
            'invoice_date'    => $data['invoice_date'],
            'due_date'        => $data['due_date'],
            'currency'        => $data['currency'] ?? $client->currency,
            'subtotal'        => $subtotal,
            'tax_amount'      => $taxAmount,
            'discount_amount' => $discount,
            'total_amount'    => $totalAmount,
            'amount_paid'     => '0.000000',
            'status'          => 'draft',
            'notes'           => $data['notes'] ?? null,
        ]);

        foreach ($preparedItems as $item) {
            InvoiceItem::create(array_merge($item, ['invoice_id' => $invoice->id]));
        }

        return $invoice;
    }

    /**
     * Record a payment (partial or full); update amount_paid, flip status.
     */
    public function recordPayment(Invoice $invoice, string $amount, int $actorId): void
    {
        $newAmountPaid = bcadd((string) $invoice->amount_paid, $amount, 6);

        $balance = bcsub((string) $invoice->total_amount, $newAmountPaid, 6);

        if (bccomp($balance, '0', 6) <= 0) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        $invoice->update([
            'amount_paid' => $newAmountPaid,
            'status'      => $status,
        ]);
    }

    /**
     * Mark invoice as sent (draft→sent).
     */
    public function markSent(Invoice $invoice, int $actorId): void
    {
        if ($invoice->status !== 'draft') {
            throw new \InvalidArgumentException("Invoice must be in draft status to mark as sent. Current status: {$invoice->status}");
        }

        $invoice->update(['status' => 'sent']);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . Carbon::now()->format('Ym') . '-';

        $last = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');

        if ($last) {
            $seq = (int) substr($last, strlen($prefix));
        } else {
            $seq = 0;
        }

        return $prefix . str_pad($seq + 1, 5, '0', STR_PAD_LEFT);
    }
}
