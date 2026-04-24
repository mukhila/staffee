<x-app-layout>
    <div class="container">
        <div class="app-page-head mb-4">
            <h1 class="app-page-title">Create Salary Structure</h1>
        </div>

        <form method="POST" action="{{ route('admin.payroll.salary-structures.store') }}" class="card">
            @csrf
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Employee</label>
                    <select name="user_id" class="form-control" required>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Base Salary</label>
                    <input type="number" step="0.01" name="base_salary" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency_code" value="USD" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pay Frequency</label>
                    <select name="pay_frequency" class="form-control">
                        <option value="monthly">Monthly</option>
                        <option value="bi_weekly">Bi-weekly</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective From</label>
                    <input type="date" name="effective_from" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Standard Work Days</label>
                    <input type="number" name="standard_work_days" value="26" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Standard Work Hours</label>
                    <input type="number" step="0.01" name="standard_work_hours" value="8" class="form-control" required>
                </div>

                <div class="col-12">
                    <h6 class="mb-3">Components</h6>
                    <div class="row g-3">
                        @foreach($components->where('code', '!=', 'BASIC') as $index => $component)
                            <div class="col-md-4">
                                <div class="border rounded p-3">
                                    <div class="fw-medium mb-2">{{ $component->name }}</div>
                                    <input type="hidden" name="components[{{ $index }}][component_definition_id]" value="{{ $component->id }}">
                                    <select name="components[{{ $index }}][amount_type]" class="form-control mb-2">
                                        <option value="fixed">Fixed</option>
                                        <option value="percentage">Percentage</option>
                                    </select>
                                    <input type="number" step="0.01" name="components[{{ $index }}][amount]" class="form-control mb-2" placeholder="Amount">
                                    <input type="number" step="0.01" name="components[{{ $index }}][percentage]" class="form-control" placeholder="Percentage">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Save Structure</button>
            </div>
        </form>
    </div>
</x-app-layout>
