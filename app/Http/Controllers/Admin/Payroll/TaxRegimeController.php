<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\TaxBracket;
use App\Models\Payroll\TaxRegime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxRegimeController extends Controller
{
    public function index()
    {
        $this->authorize('view-staff');
        $regimes = TaxRegime::withCount('brackets')->latest()->paginate(15);
        return view('admin.payroll.tax-regimes.index', compact('regimes'));
    }

    public function create()
    {
        $this->authorize('edit-staff');
        return view('admin.payroll.tax-regimes.create');
    }

    public function store(Request $request)
    {
        $this->authorize('edit-staff');

        $validated = $request->validate([
            'country_code'       => 'required|size:2',
            'fiscal_year'        => 'required|string|max:20',
            'regime_code'        => 'required|string|max:50',
            'name'               => 'required|string|max:200',
            'standard_deduction' => 'nullable|numeric|min:0',
            'rebate_amount'      => 'nullable|numeric|min:0',
            'cess_percent'       => 'nullable|numeric|min:0|max:100',
            'status'             => 'required|in:active,inactive',
            'effective_from'     => 'required|date',
            'effective_to'       => 'nullable|date|after:effective_from',
            'brackets'           => 'nullable|array',
            'brackets.*.income_from'     => 'required_with:brackets|numeric|min:0',
            'brackets.*.income_to'       => 'nullable|numeric|gt:brackets.*.income_from',
            'brackets.*.rate_percent'    => 'required_with:brackets|numeric|min:0|max:100',
            'brackets.*.fixed_tax_amount'=> 'nullable|numeric|min:0',
            'brackets.*.rebate_eligible' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $brackets = $validated['brackets'] ?? [];
            unset($validated['brackets']);

            $regime = TaxRegime::create($validated);

            foreach ($brackets as $b) {
                $regime->brackets()->create([
                    'income_from'      => $b['income_from'],
                    'income_to'        => $b['income_to'] ?? null,
                    'rate_percent'     => $b['rate_percent'],
                    'fixed_tax_amount' => $b['fixed_tax_amount'] ?? 0,
                    'rebate_eligible'  => !empty($b['rebate_eligible']),
                ]);
            }
        });

        return redirect()->route('admin.payroll.tax-regimes.index')
            ->with('success', 'Tax regime created successfully.');
    }

    public function show(TaxRegime $taxRegime)
    {
        $this->authorize('view-staff');
        $taxRegime->load('brackets');
        return view('admin.payroll.tax-regimes.show', compact('taxRegime'));
    }

    public function edit(TaxRegime $taxRegime)
    {
        $this->authorize('edit-staff');
        $taxRegime->load('brackets');
        return view('admin.payroll.tax-regimes.edit', compact('taxRegime'));
    }

    public function update(Request $request, TaxRegime $taxRegime)
    {
        $this->authorize('edit-staff');

        $validated = $request->validate([
            'country_code'       => 'required|size:2',
            'fiscal_year'        => 'required|string|max:20',
            'regime_code'        => 'required|string|max:50',
            'name'               => 'required|string|max:200',
            'standard_deduction' => 'nullable|numeric|min:0',
            'rebate_amount'      => 'nullable|numeric|min:0',
            'cess_percent'       => 'nullable|numeric|min:0|max:100',
            'status'             => 'required|in:active,inactive',
            'effective_from'     => 'required|date',
            'effective_to'       => 'nullable|date|after:effective_from',
            'brackets'           => 'nullable|array',
            'brackets.*.income_from'     => 'required_with:brackets|numeric|min:0',
            'brackets.*.income_to'       => 'nullable|numeric',
            'brackets.*.rate_percent'    => 'required_with:brackets|numeric|min:0|max:100',
            'brackets.*.fixed_tax_amount'=> 'nullable|numeric|min:0',
            'brackets.*.rebate_eligible' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $taxRegime) {
            $brackets = $validated['brackets'] ?? [];
            unset($validated['brackets']);

            $taxRegime->update($validated);

            $taxRegime->brackets()->delete();
            foreach ($brackets as $b) {
                $taxRegime->brackets()->create([
                    'income_from'      => $b['income_from'],
                    'income_to'        => $b['income_to'] ?? null,
                    'rate_percent'     => $b['rate_percent'],
                    'fixed_tax_amount' => $b['fixed_tax_amount'] ?? 0,
                    'rebate_eligible'  => !empty($b['rebate_eligible']),
                ]);
            }
        });

        return redirect()->route('admin.payroll.tax-regimes.show', $taxRegime)
            ->with('success', 'Tax regime updated.');
    }

    public function destroy(TaxRegime $taxRegime)
    {
        $this->authorize('edit-staff');
        $taxRegime->brackets()->delete();
        $taxRegime->delete();
        return redirect()->route('admin.payroll.tax-regimes.index')
            ->with('success', 'Tax regime deleted.');
    }
}
