<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Compose Email</h1>
            <span>Send a new email</span>
          </div>
          <a href="{{ route('mail.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('mail.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="to_id" class="form-label">To</label>
                        <select class="form-select" id="to_id" name="to_id" required>
                            <option value="">Select Recipient</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="body" class="form-label">Message</label>
                        <textarea class="form-control" id="body" name="body" rows="10" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Email</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
