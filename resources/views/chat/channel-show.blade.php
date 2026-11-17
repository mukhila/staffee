<x-app-layout>
<div class="container-fluid" style="height:calc(100vh - 120px);display:flex;flex-direction:column;">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between flex-shrink-0">
        <div>
            <h1 class="app-page-title"># {{ $channel->name }}</h1>
            <span>{{ $channel->description ?? ucfirst($channel->type) . ' channel' }} · {{ $channel->members->count() }} members</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('chat.search') }}" class="btn btn-outline-secondary btn-sm"><i class="fi fi-rr-search me-1"></i> Search</a>
            <a href="{{ route('chat.channels.index') }}" class="btn btn-secondary btn-sm">All Channels</a>
        </div>
    </div>

    <div class="card flex-grow-1" style="display:flex;flex-direction:column;overflow:hidden;">
        {{-- Messages --}}
        <div class="flex-grow-1 overflow-auto p-3" id="messagesContainer">
            @foreach($messages as $msg)
            @php $mine = $msg->user_id === auth()->id(); @endphp
            <div class="d-flex {{ $mine ? 'flex-row-reverse' : '' }} gap-2 mb-3 chat-msg" id="message-{{ $msg->id }}">
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
                    <div class="rounded-3 px-3 py-2 {{ $mine ? 'bg-primary text-white' : 'bg-light' }} js-body" style="word-break:break-word; white-space:pre-wrap;">{{ $msg->body }}</div>
                    @endif
                    @if($msg->attachment_path)
                    <div class="mt-1">
                        @if(in_array($msg->attachment_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true))
                        <a href="{{ $msg->attachment_url }}" target="_blank" rel="noopener"><img src="{{ $msg->attachment_url }}" alt="{{ $msg->attachment_name ?? 'Image' }}" class="img-fluid rounded" style="max-width:200px;"></a>
                        @else
                        <a href="{{ $msg->attachment_url }}" class="btn btn-sm btn-outline-secondary">
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
                    <div class="flex-grow-1 position-relative">
                        <input type="text" id="msgInput" class="form-control" placeholder="Message #{{ $channel->name }}… Type @ to mention someone" autocomplete="off"
                               aria-autocomplete="list" aria-controls="mentionList">
                        <ul id="mentionList" class="list-group position-absolute shadow-sm d-none" role="listbox" style="bottom:100%;left:0;min-width:220px;max-height:220px;overflow:auto;z-index:20;"></ul>
                    </div>
                    <label class="btn btn-outline-secondary mb-0" title="Attach file">
                        <i class="fi fi-rr-clip"></i>
                        <input type="file" id="attachInput" class="d-none" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.csv,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip">
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
const authName   = @json(auth()->user()->name);
const members    = @json($mentionable);
const imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
let lastId       = {{ $messages->last()?->id ?? 0 }};

// Message text and names are user input: always escape before putting them in the page.
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Bold any @Name of a channel member (runs on already-escaped text).
const allNames = [...members, authName].sort((a, b) => b.length - a.length);
function renderBody(text) {
    let html = escapeHtml(text);
    allNames.forEach(name => {
        const pattern = new RegExp(escapeRegExp(escapeHtml('@' + name)) + '(?![\\p{L}\\p{N}])', 'giu');
        html = html.replace(pattern, m => `<strong class="chat-mention">${m}</strong>`);
    });
    return html;
}

// Highlight mentions in messages already on the page.
document.querySelectorAll('.js-body').forEach(el => { el.innerHTML = renderBody(el.textContent); });

// Jump to a message linked from search or a mention notification.
if (location.hash.startsWith('#message-')) {
    const target = document.querySelector(location.hash);
    if (target) {
        target.scrollIntoView({ block: 'center' });
        target.classList.add('bg-warning-subtle', 'rounded');
    }
}

// Scroll to bottom
function scrollBottom() {
    document.getElementById('msgEnd').scrollIntoView({ behavior: 'smooth' });
}
if (!location.hash.startsWith('#message-')) scrollBottom();

// Render message
function appendMessage(msg) {
    const mine = msg.user_id === authId;
    const name = msg.user?.name ?? authName;
    const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    if (document.getElementById('message-' + msg.id)) return; // already on the page

    let attachment = '';
    if (msg.attachment_url) {
        const url = escapeHtml(msg.attachment_url);
        const label = escapeHtml(msg.attachment_name || 'Attachment');
        attachment = imageTypes.includes(msg.attachment_type)
            ? `<div class="mt-1"><a href="${url}" target="_blank" rel="noopener"><img src="${url}" alt="${label}" class="img-fluid rounded" style="max-width:200px;"></a></div>`
            : `<div class="mt-1"><a href="${url}" class="btn btn-sm btn-outline-secondary"><i class="fi fi-rr-file me-1"></i> ${label}</a></div>`;
    }

    const div = document.createElement('div');
    div.className = `d-flex ${mine ? 'flex-row-reverse' : ''} gap-2 mb-3 chat-msg`;
    div.id = 'message-' + msg.id;
    div.innerHTML = `
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
             style="width:32px;height:32px;background:#316AFF20;font-size:.75rem;font-weight:600;color:#316AFF;">
            ${escapeHtml(name.charAt(0).toUpperCase())}
        </div>
        <div style="max-width:65%;">
            <div class="d-flex gap-2 align-items-baseline ${mine ? 'flex-row-reverse' : ''} mb-1">
                <span class="fw-medium small">${escapeHtml(name)}</span>
                <span class="text-muted" style="font-size:.7rem;">${time}</span>
            </div>
            ${msg.body ? `<div class="rounded-3 px-3 py-2 ${mine ? 'bg-primary text-white' : 'bg-light'}" style="word-break:break-word; white-space:pre-wrap;">${renderBody(msg.body)}</div>` : ''}
            ${attachment}
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

    fetch(sendUrl, { method: 'POST', body: formData, headers: { 'Accept': 'application/json' } })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data: msg }) => {
            if (!ok) {
                const first = msg.errors ? Object.values(msg.errors)[0][0] : null;
                alert(first || msg.message || 'The message could not be sent.');
                return;
            }
            appendMessage(msg);
            lastId = Math.max(lastId, msg.id);
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
// ── @mention suggestions ────────────────────────────────────────────────────
const msgInput    = document.getElementById('msgInput');
const mentionList = document.getElementById('mentionList');
let mentionIndex  = 0;

function mentionQuery() {
    const upto = msgInput.value.slice(0, msgInput.selectionStart);
    const match = upto.match(/(^|\s)@([\p{L}\p{N} .'-]{0,30})$/u);
    return match ? { text: match[2], start: upto.length - match[2].length - 1 } : null;
}

function closeMentions() {
    mentionList.classList.add('d-none');
    mentionList.innerHTML = '';
}

function showMentions() {
    const q = mentionQuery();
    if (!q) return closeMentions();
    const term = q.text.toLowerCase();
    const hits = members.filter(n => n.toLowerCase().includes(term)).slice(0, 8);
    if (!hits.length) return closeMentions();
    mentionIndex = Math.min(mentionIndex, hits.length - 1);
    mentionList.innerHTML = hits.map((n, i) =>
        `<li class="list-group-item list-group-item-action py-1 small ${i === mentionIndex ? 'active' : ''}" role="option" data-name="${escapeHtml(n)}">@${escapeHtml(n)}</li>`
    ).join('');
    mentionList.classList.remove('d-none');
}

function pickMention(name) {
    const q = mentionQuery();
    if (!q) return;
    const before = msgInput.value.slice(0, q.start);
    const after  = msgInput.value.slice(msgInput.selectionStart);
    msgInput.value = `${before}@${name} ${after}`;
    const pos = before.length + name.length + 2;
    msgInput.setSelectionRange(pos, pos);
    closeMentions();
    msgInput.focus();
}

msgInput.addEventListener('input', () => { mentionIndex = 0; showMentions(); });
msgInput.addEventListener('blur', () => setTimeout(closeMentions, 150));
mentionList.addEventListener('mousedown', e => {
    const li = e.target.closest('li[data-name]');
    if (li) { e.preventDefault(); pickMention(li.dataset.name); }
});

msgInput.addEventListener('keydown', function (e) {
    if (!mentionList.classList.contains('d-none')) {
        const items = mentionList.querySelectorAll('li');
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            mentionIndex = (mentionIndex + (e.key === 'ArrowDown' ? 1 : items.length - 1)) % items.length;
            showMentions();
            return;
        }
        if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            pickMention(items[mentionIndex].dataset.name);
            return;
        }
        if (e.key === 'Escape') { closeMentions(); return; }
    }
});

document.getElementById('msgInput').addEventListener('keydown', function (e) {
    if (!mentionList.classList.contains('d-none')) return;
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('sendForm').dispatchEvent(new Event('submit'));
    }
});

// Real-time: subscribe via Laravel Echo (Reverb)
if (window.Echo) {
    window.Echo.private('chat-channel.{{ $channel->id }}')
        .listen('ChannelMessageSent', (e) => {
            if (e.message && e.message.user_id !== authId) {
                appendMessage(e.message);
            }
        });
} else {
    // Fallback: poll every 5 seconds if Echo not initialised (Reverb not running)
    setInterval(() => {
        fetch('{{ route("chat.channels.messages", $channel) }}?after=' + lastId)
            .then(r => r.json())
            .then(msgs => {
                msgs.filter(m => m.id > lastId && m.user_id !== authId).forEach(m => {
                    appendMessage(m);
                    lastId = m.id;
                });
                if (msgs.length) lastId = Math.max(lastId, msgs[msgs.length - 1].id);
            });
    }, 5000);
}
</script>
</x-app-layout>
