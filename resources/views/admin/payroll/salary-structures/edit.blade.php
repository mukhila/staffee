<x-app-layout>
    <div class="container">
        <div class="app-page-head mb-4">
            <h1 class="app-page-title">Edit Salary Structure</h1>
        </div>

        <form method="POST" action="{{ route('admin.payroll.salary-structures.update', $salaryStructure) }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Employee</label>
                    <input type="text" class="form-control" value="{{ $salaryStructure->employee?->name }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Base Salary</label>
                    <input type="number" step="0.01" name="base_salary" value="{{ $salaryStructure->base_salary }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency_code" value="{{ $salaryStructure->currency_code }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Effective From</label>
                    <input type="date" name="effective_from" value="{{ $salaryStructure->effective_from?->toDateString() }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Effective To</label>
                    <input type="date" name="effective_to" value="{{ $salaryStructure->effective_to?->toDateString() }}" class="form-control">
                </div>
                <div class="col-12">
                    <h6 class="mb-3">Components</h6>
                    <div class="row g-3">
                        @foreach($components as $index => $component)
                            @php $existing = $salaryStructure->components->firstWhere('component_definition_id', $component->id); @endphp
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <div class="fw-medium mb-2">{{ $component->name }}</div>
                                    <input type="hidden" name="components[{{ $index }}][component_definition_id]" value="{{ $component->id }}">
                                    <select name="components[{{ $index }}][amount_type]" class="form-control mb-2">
                                        <option value="fixed" @selected(($existing?->amount_type ?? 'fixed') === 'fixed')>Fixed</option>
                                        <option value="percentage" @selected(($existing?->amount_type ?? '') === 'percentage')>Percentage</option>
                                    </select>
                                    <input type="number" step="0.01" name="components[{{ $index }}][amount]" value="{{ $existing?->amount }}" class="form-control mb-2" placeholder="Amount">
                                    <input type="number" step="0.01" name="components[{{ $index }}][percentage]" value="{{ $existing?->percentage }}" class="form-control" placeholder="Percentage">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update Structure</button>
            </div>
        </form>
    </div>
</x-app-layout>
