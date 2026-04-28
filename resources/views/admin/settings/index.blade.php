<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Settings</h1>
            <span>Manage company and application settings</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        {{-- Company Details --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Company Details</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Company Logo</label>
                            @if(!empty($settings['company_logo']))
                            <div class="mb-2">
                                <img src="{{ Storage::url($settings['company_logo']) }}" alt="Logo" style="max-height:60px;max-width:180px;object-fit:contain;">
                            </div>
                            @endif
                            <input type="file" name="company_logo" class="form-control" accept="image/*">
                            <div class="form-text">PNG/JPG, max 2MB</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" value="{{ $settings['company_name'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="company_address" rows="3">{{ $settings['company_address'] ?? '' }}</textarea>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="company_email" value="{{ $settings['company_email'] ?? '' }}">
                            </div>
                            <div class="col">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Details</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Working Hours & Leave Year --}}
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Working Hours</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Working Days</label>
                            <input type="text" class="form-control" name="working_days" value="{{ $settings['working_days'] ?? 'Monday - Saturday' }}">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <label class="form-label">Start Time</label>
                                <input type="time" class="form-control" name="work_start_time" value="{{ $settings['work_start_time'] ?? '09:00' }}">
                            </div>
                            <div class="col">
                                <label class="form-label">End Time</label>
                                <input type="time" class="form-control" name="work_end_time" value="{{ $settings['work_end_time'] ?? '18:00' }}">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <label class="form-label">Min Hours / Day</label>
                                <input type="number" class="form-control" name="min_work_hours" value="{{ $settings['min_work_hours'] ?? '8' }}" min="1" max="24">
                            </div>
                            <div class="col">
                                <label class="form-label">Timezone</label>
                                <select name="app_timezone" class="form-select">
                                    @foreach(timezone_identifiers_list() as $tz)
                                    <option value="{{ $tz }}" {{ ($settings['app_timezone'] ?? config('app.timezone')) === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Hours</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Leave Year Configuration</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col">
                                <label class="form-label">Leave Year Start Month</label>
                                <select name="leave_year_start_month" class="form-select">
                                    @foreach(range(1,12) as $m)
                                    <option value="{{ $m }}" {{ ($settings['leave_year_start_month'] ?? '1') == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">Max Carry-Forward Days</label>
                                <input type="number" class="form-control" name="leave_carry_forward_days" value="{{ $settings['leave_carry_forward_days'] ?? '0' }}" min="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Probation Period (months)</label>
                            <input type="number" class="form-control" name="probation_months" value="{{ $settings['probation_months'] ?? '6' }}" min="0" max="24">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Leave Config</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- SMTP Settings --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h6 class="card-title mb-0">SMTP / Email Settings</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">SMTP Host</label>
                                <input type="text" class="form-control" name="smtp_host" value="{{ $settings['smtp_host'] ?? '' }}" placeholder="smtp.gmail.com">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Port</label>
                                <input type="number" class="form-control" name="smtp_port" value="{{ $settings['smtp_port'] ?? '587' }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Encryption</label>
                                <select name="smtp_encryption" class="form-select">
                                    <option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="" {{ ($settings['smtp_encryption'] ?? '') === '' ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">From Name</label>
                                <input type="text" class="form-control" name="smtp_from_name" value="{{ $settings['smtp_from_name'] ?? $settings['company_name'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">From Email</label>
                                <input type="email" class="form-control" name="smtp_from_email" value="{{ $settings['smtp_from_email'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SMTP Username</label>
                                <input type="text" class="form-control" name="smtp_username" value="{{ $settings['smtp_username'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SMTP Password</label>
                                <input type="password" class="form-control" name="smtp_password" placeholder="{{ isset($settings['smtp_password']) ? '••••••••' : 'Enter password' }}" autocomplete="new-password">
                                <div class="form-text">Leave blank to keep existing password.</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Save SMTP Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
