<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Request a Document</h1>
                <span>Submit a new HR document request</span>
            </div>
            <a href="{{ route('staff.document-requests.index') }}" class="btn btn-outline-secondary">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.document-requests.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Document Type <span class="text-danger">*</span></label>
                                <select name="document_type" id="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                                    <option value="">— Select type —</option>
                                    @foreach($types as $value => $label)
                                    <option value="{{ $value }}" {{ old('document_type') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('document_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3" id="custom_type_wrapper" style="display:none">
                                <label class="form-label">Custom Document Name <span class="text-danger">*</span></label>
                                <input type="text" name="custom_type" class="form-control @error('custom_type') is-invalid @enderror"
                                    value="{{ old('custom_type') }}" maxlength="120" placeholder="e.g. Visa support letter">
                                @error('custom_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Purpose / Reason</label>
                                <textarea name="purpose" class="form-control @error('purpose') is-invalid @enderror"
                                    rows="4" maxlength="1000" placeholder="Explain why you need this document (optional)">{{ old('purpose') }}</textarea>
                                @error('purpose')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary waves-effect waves-light">
                                <i class="fi fi-rr-paper-plane me-1"></i> Submit Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('document_type').addEventListener('change', function () {
            document.getElementById('custom_type_wrapper').style.display =
                this.value === 'custom' ? 'block' : 'none';
        });
        // Trigger on page load if old value is custom
        if (document.getElementById('document_type').value === 'custom') {
            document.getElementById('custom_type_wrapper').style.display = 'block';
        }
    </script>
</x-app-layout>
