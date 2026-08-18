<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">New Expense Claim</h1>
                <span>Submit an expense for reimbursement</span>
            </div>
            <a href="{{ route('staff.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('staff.expenses.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                   value="{{ old('title') }}" required maxlength="200">
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Expense Date <span class="text-danger">*</span></label>
                            <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror"
                                   value="{{ old('expense_date', date('Y-m-d')) }}" required>
                            @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}" required min="0.01">
                            @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Currency</label>
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', 'INR') }}" maxlength="3">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="expense_category_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Project</label>
                            <select name="project_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($projects as $proj)
                                <option value="{{ $proj->id }}" {{ old('project_id') == $proj->id ? 'selected' : '' }}>
                                    {{ $proj->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Receipt Path / Reference</label>
                            <input type="text" name="receipt_path" class="form-control" value="{{ old('receipt_path') }}" maxlength="400">
                        </div>

                        <div class="col-12 d-flex gap-2">
                            <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">
                                <i class="fi fi-rr-save me-1"></i> Save as Draft
                            </button>
                            <button type="submit" name="action" value="submit" class="btn btn-primary">
                                <i class="fi fi-rr-paper-plane me-1"></i> Submit for Approval
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
