<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">
                @if($composeType === 'reply') Reply @elseif($composeType === 'forward') Forward @else Compose @endif
            </h1>
            <span id="draftStatus" class="text-muted small"></span>
        </div>
        <a href="{{ route('mail.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('mail.store') }}" method="POST" id="composeForm">
                @csrf
                <input type="hidden" name="mail_type" value="{{ $composeType }}">
                <input type="hidden" name="parent_id" value="{{ $parentEmail?->id }}">
                <input type="hidden" name="draft_id" id="draftId" value="{{ request('draft') }}">

                <div class="mb-3">
                    <label class="form-label">To</label>
                    <select class="form-select" name="to_id" id="toId" {{ $composeType === 'reply' ? 'readonly' : '' }}>
                        <option value="">— Select Recipient —</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ ($prefill['to_id'] ?? null) == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->role }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" name="subject" id="subjectField" value="{{ $prefill['subject'] ?? '' }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" name="body" id="bodyField" rows="12" required>{{ $prefill['body'] ?? '' }}</textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fi fi-rr-paper-plane me-1"></i> Send</button>
                    <button type="button" class="btn btn-outline-secondary" id="saveDraftBtn">
                        <i class="fi fi-rr-save me-1"></i> Save Draft
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const storeUrl  = '{{ route("mail.store") }}';
const csrfToken = '{{ csrf_token() }}';
let saveTimer   = null;
let draftId     = document.getElementById('draftId').value || null;

function saveDraft(manual = false) {
    const body    = document.getElementById('bodyField').value.trim();
    const subject = document.getElementById('subjectField').value.trim();
    const toId    = document.getElementById('toId').value;

    if (!body && !subject) return;

    const payload = {
        to_id:     toId || null,
        subject:   subject,
        body:      body,
        is_draft:  true,
        mail_type: '{{ $composeType }}',
        parent_id: '{{ $parentEmail?->id }}',
        draft_id:  draftId,
        _token:    csrfToken,
    };

    document.getElementById('draftStatus').textContent = 'Saving…';

    fetch(storeUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            draftId = data.id;
            document.getElementById('draftId').value = draftId;
            document.getElementById('draftStatus').textContent = 'Draft saved ' + new Date().toLocaleTimeString();
        }
    })
    .catch(() => { document.getElementById('draftStatus').textContent = 'Could not save draft.'; });
}

// Auto-save on typing (debounced 5s)
['bodyField', 'subjectField', 'toId'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => saveDraft(), 5000);
    });
});

document.getElementById('saveDraftBtn').addEventListener('click', () => saveDraft(true));
</script>
</x-app-layout>
