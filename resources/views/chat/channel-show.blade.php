<x-app-layout>
<div class="container-fluid" style="height:calc(100vh - 120px);display:flex;flex-direction:column;">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between flex-shrink-0">
        <div>
            <h1 class="app-page-title"># {{ $channel->name }}</h1>
            <span>{{ $channel->description ?? ucfirst($channel->type) . ' channel' }} · {{ $channel->members->count() }} members</span>
        </div>
        <a href="{{ route('chat.channels.index') }}" class="btn btn-secondary btn-sm">All Channels</a>
    </div>

    <div class="card flex-grow-1" style="display:flex;flex-direction:column;overflow:hidden;">
        {{-- Messages --}}
        <div class="flex-grow-1 overflow-auto p-3" id="messagesContainer">
            @foreach($messages as $msg)
            @php $mine = $msg->user_id === auth()->id(); @endphp
            <div class="d-flex {{ $mine ? 'flex-row-reverse' : '' }} gap-2 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:32px;height:32px;background:#316AFF20;font-size:.75rem;font-weight:600;color:#316AFF;">
                    {{ strtoupper(substr($msg->user?->name ?? '?', 0, 1)) }}
                </div>
                <div style="max-width:65%;">
                    <div class="d-flex gap-2 align-items-baseline {{ $mine ? 'flex-row-reverse' : '' }} mb-1">
                        <span class="fw-medium small">{{ $msg->user?->name }}</span>
                        <span class="text-muted" style="font-size:.7rem;">{{ $msg->created_at->format('H:i') }}</span>
                    </div>
                    @if($msg->body)
                    <div class="rounded-3 px-3 py-2 {{ $mine ? 'bg-primary text-white' : 'bg-light' }}" style="word-break:break-word;">
                        {{ $msg->body }}
                    </div>
                    @endif
                    @if($msg->attachment_path)
                    <div class="mt-1">
                        @if(str_starts_with($msg->attachment_type ?? '', 'image/'))
                        <img src="{{ Storage::url($msg->attachment_path) }}" class="img-fluid rounded" style="max-width:200px;">
                        @else
                        <a href="{{ Storage::url($msg->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fi fi-rr-file me-1"></i> {{ $msg->attachment_name ?? 'Attachment' }}
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            <div id="msgEnd"></div>
        </div>

        {{-- Input --}}
        <div class="border-top p-3 flex-shrink-0">
            <form id="sendForm" enctype="multipart/form-data">
                @csrf
                <div class="d-flex gap-2 align-items-end">
                    <div class="flex-grow-1">
                        <input type="text" id="msgInput" class="form-control" placeholder="Message #{{ $channel->name }}…" autocomplete="off">
                    </div>
                    <label class="btn btn-outline-secondary mb-0" title="Attach file">
                        <i class="fi fi-rr-clip"></i>
                        <input type="file" id="attachInput" class="d-none" accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx">
                    </label>
                    <button type="submit" class="btn btn-primary"><i class="fi fi-rr-paper-plane"></i></button>
                </div>
                <div id="attachPreview" class="text-muted small mt-1"></div>
            </form>
        </div>
    </div>
</div>

<script>
const channelId  = {{ $channel->id }};
const sendUrl    = '{{ route("chat.channels.send", $channel) }}';
const csrfToken  = '{{ csrf_token() }}';
const authId     = {{ auth()->id() }};
const authName   = '{{ auth()->user()->name }}';

// Scroll to bottom
function scrollBottom() {
    document.getElementById('msgEnd').scrollIntoView({ behavior: 'smooth' });
}
scrollBottom();

// Render message
function appendMessage(msg) {
    const mine = msg.user_id === authId;
    const name = msg.user?.name ?? authName;
    const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    const div = document.createElement('div');
    div.className = `d-flex ${mine ? 'flex-row-reverse' : ''} gap-2 mb-3`;
    div.innerHTML = `
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:32px;height:32px;background:#316AFF20;font-size:.75rem;font-weight:600;color:#316AFF;">
            ${name.charAt(0).toUpperCase()}
        </div>
        <div style="max-width:65%;">
            <div class="d-flex gap-2 align-items-baseline ${mine ? 'flex-row-reverse' : ''} mb-1">
                <span class="fw-medium small">${name}</span>
                <span class="text-muted" style="font-size:.7rem;">${time}</span>
            </div>
            ${msg.body ? `<div class="rounded-3 px-3 py-2 ${mine ? 'bg-primary text-white' : 'bg-light'}" style="word-break:break-word;">${msg.body}</div>` : ''}
        </div>`;

    document.getElementById('msgEnd').before(div);
    scrollBottom();
}

// Send
document.getElementById('sendForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const body   = document.getElementById('msgInput').value.trim();
    const file   = document.getElementById('attachInput').files[0];
    if (!body && !file) return;

    const formData = new FormData();
    formData.append('_token', csrfToken);
    if (body) formData.append('body', body);
    if (file) formData.append('attachment', file);

    fetch(sendUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(msg => {
            appendMessage(msg);
            document.getElementById('msgInput').value = '';
            document.getElementById('attachInput').value = '';
            document.getElementById('attachPreview').textContent = '';
        });
});

document.getElementById('attachInput').addEventListener('change', function () {
    const f = this.files[0];
    document.getElementById('attachPreview').textContent = f ? `Attached: ${f.name}` : '';
});

// Send on Enter (Shift+Enter = newline)
document.getElementById('msgInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('sendForm').dispatchEvent(new Event('submit'));
    }
});

// Poll for new messages every 5 seconds
let lastId = {{ $messages->last()?->id ?? 0 }};
setInterval(() => {
    fetch('{{ route("chat.channels.messages", $channel) }}')
        .then(r => r.json())
        .then(msgs => {
            msgs.filter(m => m.id > lastId && m.user_id !== authId).forEach(m => {
                appendMessage(m);
                lastId = m.id;
            });
            if (msgs.length) lastId = Math.max(lastId, msgs[msgs.length - 1].id);
        });
}, 5000);
</script>
</x-app-layout>
