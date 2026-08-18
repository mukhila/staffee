<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeTaxDeclaration;
use App\Models\User;
use App\Services\Payroll\TaxDeclarationService;
use Illuminate\Http\Request;

class TaxDeclarationController extends Controller
{
    public function __construct(private readonly TaxDeclarationService $service) {}

    /**
     * List all declarations, filterable by status and fiscal year.
     */
    public function index(Request $request)
    {
        $query = EmployeeTaxDeclaration::with(['user', 'taxRegime'])
            ->orderByDesc('fiscal_year')
            ->orderByDesc('submitted_at');

        if ($request->filled('status')) {
            $query->where('declaration_status', $request->status);
        }

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', $request->fiscal_year);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $declarations = $query->paginate(25)->withQueryString();

        $users        = User::orderBy('name')->get();
        $fiscalYears  = EmployeeTaxDeclaration::distinct()->pluck('fiscal_year')->sortDesc();
        $statuses     = ['draft', 'submitted', 'verified', 'locked'];

        return view('admin.payroll.tax-declarations.index', compact(
            'declarations', 'users', 'fiscalYears', 'statuses'
        ));
    }

    /**
     * Show a single declaration with proof files.
     */
    public function show(EmployeeTaxDeclaration $declaration)
    {
        $declaration->load(['user', 'taxRegime', 'verifier', 'proofs']);

        return view('admin.payroll.tax-declarations.show', compact('declaration'));
    }

    /**
     * Mark a submitted declaration as verified.
     */
    public function verify(EmployeeTaxDeclaration $declaration)
    {
        try {
            $this->service->verify($declaration, auth()->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return redirect()->route('admin.payroll.tax-declarations.show', $declaration)
            ->with('success', 'Declaration verified successfully.');
    }
}
