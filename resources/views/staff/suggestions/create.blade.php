<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Submit a Suggestion</h1>
                <span>Your ideas help us improve</span>
            </div>
            <a href="{{ route('staff.suggestions.index') }}" class="btn btn-outline-secondary">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.suggestions.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                    value="{{ old('title') }}" maxlength="200" required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Suggestion <span class="text-danger">*</span></label>
                                <textarea name="body" class="form-control @error('body') is-invalid @enderror"
                                    rows="6" required placeholder="Describe your suggestion in detail...">{{ old('body') }}</textarea>
                                @error('body')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror">
                                    <option value="">— Select category (optional) —</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input type="hidden" name="is_anonymous" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_anonymous" value="1" id="is_anonymous"
                                        {{ old('is_anonymous') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_anonymous">
                                        Submit anonymously <small class="text-muted">(your name will not be attached)</small>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary waves-effect waves-light">
                                <i class="fi fi-rr-paper-plane me-1"></i> Submit Suggestion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
