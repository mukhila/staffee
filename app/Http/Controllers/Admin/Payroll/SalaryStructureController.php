<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\ComponentDefinition;
use App\Models\Payroll\SalaryComponent;
use App\Models\Payroll\SalaryRevisionRequest;
use App\Models\Payroll\SalaryStructure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryStructureController extends Controller
{
    public function index()
    {
        $structures = SalaryStructure::with(['employee', 'components.definition'])
            ->latest('effective_from')
            ->paginate(15);

        return view('admin.payroll.salary-structures.index', compact('structures'));
    }

    public function create()
    {
        $employees = User::active()->excludeAdmin()->withHrProfile()->get();
        $components = ComponentDefinition::where('status', 'active')->orderBy('display_order')->get();

        return view('admin.payroll.salary-structures.create', compact('employees', 'components'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'base_salary' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'pay_frequency' => 'required|in:monthly,bi_weekly',
            'standard_work_days' => 'required|integer|min:1|max:31',
            'standard_work_hours' => 'required|numeric|min:1|max:24',
            'overtime_eligible' => 'nullable|boolean',
            'components' => 'array',
            'components.*.component_definition_id' => 'required|exists:payroll_component_definitions,id',
            'components.*.amount' => 'nullable|numeric|min:0',
            'components.*.percentage' => 'nullable|numeric|min:0',
            'components.*.amount_type' => 'required_with:components|in:fixed,percentage',
        ]);

        DB::transaction(function () use ($validated) {
            $latestVersion = (int) SalaryStructure::where('user_id', $validated['user_id'])->max('version_no');

            $structure = SalaryStructure::create([
                'user_id' => $validated['user_id'],
                'pay_frequency' => $validated['pay_frequency'],
                'currency_code' => strtoupper($validated['currency_code']),
                'monthly_base_salary' => $validated['base_salary'],
                'version_no' => $latestVersion + 1,
                'standard_work_days' => $validated['standard_work_days'],
                'standard_work_hours' => $validated['standard_work_hours'],
                'overtime_eligible' => (bool) ($validated['overtime_eligible'] ?? false),
                'status' => 'active',
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
                'approval_status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'created_by' => auth()->id(),
            ]);

            $basicDefinition = ComponentDefinition::where('code', 'BASIC')->first();
            if ($basicDefinition) {
                $structure->components()->create([
                    'component_definition_id' => $basicDefinition->id,
                    'amount_type' => 'fixed',
                    'amount' => $validated['base_salary'],
                    'sequence' => 10,
                ]);
            }

            foreach ($validated['components'] ?? [] as $index => $component) {
                SalaryComponent::create([
                    'salary_structure_id' => $structure->id,
                    'component_definition_id' => $component['component_definition_id'],
                    'amount_type' => $component['amount_type'],
                    'amount' => $component['amount'] ?? null,
                    'percentage' => $component['percentage'] ?? null,
                    'sequence' => ($index + 1) * 10,
                ]);
            }
        });

        return redirect()->route('admin.payroll.salary-structures.index')
            ->with('success', 'Salary structure created successfully.');
    }

    public function edit(SalaryStructure $salaryStructure)
    {
        $salaryStructure->load('components.definition', 'employee');
        $components = ComponentDefinition::where('status', 'active')->orderBy('display_order')->get();

        return view('admin.payroll.salary-structures.edit', compact('salaryStructure', 'components'));
    }

    public function update(Request $request, SalaryStructure $salaryStructure)
    {
        $validated = $request->validate([
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'base_salary' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'components' => 'array',
            'components.*.id' => 'nullable|exists:employee_salary_components,id',
            'components.*.component_definition_id' => 'required|exists:payroll_component_definitions,id',
            'components.*.amount' => 'nullable|numeric|min:0',
            'components.*.percentage' => 'nullable|numeric|min:0',
            'components.*.amount_type' => 'required_with:components|in:fixed,percentage',
        ]);

        DB::transaction(function () use ($validated, $salaryStructure) {
            $salaryStructure->update([
                'monthly_base_salary' => $validated['base_salary'],
                'currency_code' => strtoupper($validated['currency_code']),
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
            ]);

            $salaryStructure->components()->delete();
            foreach ($validated['components'] ?? [] as $index => $component) {
                SalaryComponent::create([
                    'salary_structure_id' => $salaryStructure->id,
                    'component_definition_id' => $component['component_definition_id'],
                    'amount_type' => $component['amount_type'],
                    'amount' => $component['amount'] ?? null,
                    'percentage' => $component['percentage'] ?? null,
                    'sequence' => ($index + 1) * 10,
                ]);
            }
        });

        return redirect()->route('admin.payroll.salary-structures.index')
            ->with('success', 'Salary structure updated successfully.');
    }

    public function revisions(SalaryStructure $salaryStructure)
    {
        $revisions = SalaryRevisionRequest::where('user_id', $salaryStructure->user_id)
            ->latest('effective_date')
            ->get();

        return view('admin.payroll.salary-structures.revisions', compact('salaryStructure', 'revisions'));
    }
}
