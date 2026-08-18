<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeTaxDeclaration;
use App\Models\Payroll\TaxRegime;
use App\Services\Payroll\TaxDeclarationService;
use Illuminate\Http\Request;

class TaxDeclarationController extends Controller
{
    public function __construct(private readonly TaxDeclarationService $service) {}

    /**
     * List the authenticated employee's own declarations.
     */
    public function index()
    {
        $declarations = EmployeeTaxDeclaration::with('taxRegime')
            ->where('user_id', auth()->id())
            ->orderByDesc('fiscal_year')
            ->paginate(20);

        return view('staff.tax-declarations.index', compact('declarations'));
    }

    /**
     * Show the form to create or edit a draft declaration.
     */
    public function create()
    {
        $taxRegimes  = TaxRegime::where('status', 'active')->orderBy('name')->get();
        $currentYear = $this->currentFiscalYear();

        // Load an existing draft for the current FY so the form is pre-filled
        $existing = $this->service->currentForUser(auth()->user(), $currentYear);

        return view('staff.tax-declarations.create', compact('taxRegimes', 'currentYear', 'existing'));
    }

    /**
     * Create or update a draft declaration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tax_regime_id'    => 'required|exists:tax_regimes,id',
            'fiscal_year'      => 'required|string|regex:/^\d{4}-\d{2}$/',
            'declared_amounts' => 'required|array',
            'declared_amounts.*' => 'nullable|numeric|min:0',
        ]);

        // Normalize amounts to BCMath strings, skip null/empty entries
        $amounts = [];
        foreach ($validated['declared_amounts'] as $section => $value) {
            if ($value !== null && $value !== '') {
                $amounts[$section] = number_format((float) $value, 6, '.', '');
            }
        }

        try {
            $this->service->createOrUpdate(
                auth()->user(),
                (int) $validated['tax_regime_id'],
                $validated['fiscal_year'],
                $amounts
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }

        return redirect()->route('staff.tax-declarations.index')
            ->with('success', 'Tax declaration saved as draft.');
    }

    /**
     * Submit a draft declaration for verification.
     */
    public function submit(EmployeeTaxDeclaration $declaration)
    {
        $this->authorizeOwnership($declaration);

        try {
            $this->service->submit($declaration);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return redirect()->route('staff.tax-declarations.index')
            ->with('success', 'Declaration submitted for verification.');
    }

    /**
     * Upload a proof document for a section.
     */
    public function uploadProof(EmployeeTaxDeclaration $declaration, Request $request)
    {
        $this->authorizeOwnership($declaration);

        $validated = $request->validate([
            'section' => 'required|string|max:20',
            'file'    => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            $this->service->uploadProof(
                $declaration,
                $validated['section'],
                $request->file('file')
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('success', "Proof uploaded for section {$validated['section']}.");
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function authorizeOwnership(EmployeeTaxDeclaration $declaration): void
    {
        if ($declaration->user_id !== auth()->id()) {
            abort(403, 'You are not authorized to access this declaration.');
        }
    }

    private function currentFiscalYear(): string
    {
        $now   = now();
        $year  = $now->year;
        $month = $now->month;

        // Indian fiscal year: April–March
        if ($month < 4) {
            $start = $year - 1;
        } else {
            $start = $year;
        }
        $end = $start + 1;

        return $start . '-' . substr((string) $end, 2);
    }
}
