<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Edit Client</h1>
                <span>{{ $client->name }}</span>
            </div>
            <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-outline-secondary">
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
                <form action="{{ route('admin.clients.update', $client) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $client->name) }}" required maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $client->contact_person) }}" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $client->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $client->phone) }}" maxlength="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="{{ old('country', $client->country) }}" maxlength="60">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Currency</label>
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', $client->currency) }}" maxlength="3">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">GST Number</label>
                            <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number', $client->gst_number) }}" maxlength="20">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $client->address) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $client->notes) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                       {{ old('is_active', $client->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Active Client</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Update Client</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
