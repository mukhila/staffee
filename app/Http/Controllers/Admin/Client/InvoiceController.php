<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Invoice;
use App\Models\Project;
use App\Services\Client\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function index(Request $request)
    {
        $invoices = Invoice::with(['client', 'issuedBy'])
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $clients  = Client::orderBy('name')->get();
        $statuses = ['draft', 'sent', 'partial', 'paid', 'overdue', 'cancelled'];

        return view('admin.invoices.index', compact('invoices', 'clients', 'statuses'));
    }

    public function create()
    {
        $clients  = Client::where('is_active', true)->orderBy('name')->get();
        $projects = Project::orderBy('name')->get();

        return view('admin.invoices.create', compact('clients', 'projects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'       => 'required|exists:clients,id',
            'project_id'      => 'nullable|exists:projects,id',
            'invoice_date'    => 'required|date',
            'due_date'        => 'required|date|after_or_equal:invoice_date',
            'currency'        => 'required|string|size:3',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
            'items'           => 'required|array|min:1',
            'items.*.description' => 'required|string|max:300',
            'items.*.quantity'    => 'required|numeric|min:0.0001',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.tax_rate'    => 'nullable|numeric|min:0|max:100',
        ]);

        $client  = Client::findOrFail($validated['client_id']);
        $invoice = $this->invoiceService->createInvoice($client, $validated, auth()->id());

        return redirect()->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'project', 'issuedBy', 'items']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function send(Invoice $invoice)
    {
        try {
            $this->invoiceService->markSent($invoice, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Invoice marked as sent.');
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $this->invoiceService->recordPayment($invoice, (string) $request->amount, auth()->id());

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function cancel(Invoice $invoice)
    {
        $invoice->update(['status' => 'cancelled']);

        return back()->with('success', 'Invoice cancelled.');
    }
}
