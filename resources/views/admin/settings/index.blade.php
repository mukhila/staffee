<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Settings</h1>
            <span>Manage company and application settings</span>
          </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Company Details</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.update') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" value="{{ $settings['company_name'] ?? '' }}">
                            </div>
                            <div class="mb-3">
                                <label for="company_address" class="form-label">Address</label>
                                <textarea class="form-control" id="company_address" name="company_address" rows="3">{{ $settings['company_address'] ?? '' }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label for="company_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="company_email" name="company_email" value="{{ $settings['company_email'] ?? '' }}">
                            </div>
                            <div class="mb-3">
                                <label for="company_phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="company_phone" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}">
                            </div>
                             <div class="mb-3">
                                <label for="social_media" class="form-label">Social Media (JSON or Links)</label>
                                <textarea class="form-control" id="social_media" name="social_media" rows="2">{{ $settings['social_media'] ?? '' }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Details</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Working Hours</h6>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.settings.update') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="working_days" class="form-label">Working Days</label>
                                <input type="text" class="form-control" id="working_days" name="working_days" value="{{ $settings['working_days'] ?? 'Monday - Saturday' }}" placeholder="e.g. Monday - Saturday">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="work_start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="work_start_time" name="work_start_time" value="{{ $settings['work_start_time'] ?? '09:00' }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="work_end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="work_end_time" name="work_end_time" value="{{ $settings['work_end_time'] ?? '20:00' }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="min_work_hours" class="form-label">Minimum Working Hours / Day</label>
                                <input type="number" class="form-control" id="min_work_hours" name="min_work_hours" value="{{ $settings['min_work_hours'] ?? '8' }}">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Hours</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
