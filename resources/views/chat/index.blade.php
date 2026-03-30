<x-app-layout>
    <div class="container-fluid h-100">
        <div class="row h-100">
            <!-- Users List -->
            <div class="col-md-3 border-end bg-white h-100 p-0">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">Chats</h5>
                </div>
                <div class="list-group list-group-flush overflow-auto" style="height: calc(100vh - 150px);">
                    @foreach($users as $user)
                        <a href="#" class="list-group-item list-group-item-action user-chat-item" data-id="{{ $user->id }}">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm rounded-circle me-2">
                                    @if($user->avatar)
                                        <img src="{{ Str::startsWith($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="rounded-circle object-cover">
                                    @else
                                        <img src="{{ asset('assets/images/avatar/avatar1.webp') }}" alt="">
                                    @endif
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $user->name }}</h6>
                                    <small class="text-muted">{{ $user->role }}</small>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Chat Area -->
            <div class="col-md-9 d-flex flex-column h-100 p-0">
                <div class="p-3 border-bottom bg-white d-none" id="chat-header">
                    <h5 class="mb-0" id="chat-user-name">Select a user</h5>
                </div>
                
                <div class="flex-grow-1 p-3 overflow-auto bg-light" id="chat-messages" style="height: calc(100vh - 220px);">
                    <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                        Select a user to start chatting
                    </div>
                </div>

                <div class="p-3 border-top bg-white d-none" id="chat-input-area">
                    <form id="chat-form" class="d-flex gap-2 align-items-center" enctype="multipart/form-data">
                        <input type="hidden" id="chat-to-id">
                        
                        <!-- Attachment Button -->
                        <label for="attachment-input" class="btn btn-light text-secondary mb-0" style="cursor: pointer;">
                            <i class="fi fi-rr-clip"></i>
                        </label>
                        <input type="file" id="attachment-input" class="d-none">

                        <!-- Emoji Button -->
                        <button type="button" class="btn btn-light text-secondary" id="emoji-btn">
                            <i class="fi fi-rr-smile"></i>
                        </button>

                        <input type="text" class="form-control" id="message-input" placeholder="Type a message..." autocomplete="off">
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-paper-plane"></i>
                        </button>
                    </form>
                    <div id="file-preview" class="mt-2 d-none">
                        <span class="badge bg-light text-dark border">
                            <span id="file-name"></span>
                            <button type="button" class="btn-close btn-close-white ms-2" style="font-size: 0.5rem;" id="remove-file"></button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div class="modal fade" id="chatSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chat Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Background Color</label>
                        <input type="color" class="form-control form-control-color" id="chat-bg-color" value="#f8f9fa">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Background Image URL (Optional)</label>
                        <input type="text" class="form-control" id="chat-bg-image" placeholder="https://example.com/image.jpg">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="save-chat-settings">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
        import { createPopup } from 'https://unpkg.com/@picmo/popup-picker@latest/dist/index.js?module';

        document.addEventListener('DOMContentLoaded', function () {
            const userItems = document.querySelectorAll('.user-chat-item');
            const chatHeader = document.getElementById('chat-header');
            const chatUserName = document.getElementById('chat-user-name');
            const chatMessages = document.getElementById('chat-messages');
            const chatInputArea = document.getElementById('chat-input-area');
            const chatForm = document.getElementById('chat-form');
            const chatToId = document.getElementById('chat-to-id');
            const messageInput = document.getElementById('message-input');
            const attachmentInput = document.getElementById('attachment-input');
            const filePreview = document.getElementById('file-preview');
            const fileNameSpan = document.getElementById('file-name');
            const removeFileBtn = document.getElementById('remove-file');
            const emojiBtn = document.getElementById('emoji-btn');

            let currentUserId = null;

            // Emoji Picker
            const picker = createPopup({}, {
                referenceElement: emojiBtn,
                triggerElement: emojiBtn,
                position: 'top-start'
            });

            emojiBtn.addEventListener('click', () => {
                picker.toggle();
            });

            picker.addEventListener('emoji:select', (selection) => {
                messageInput.value += selection.emoji;
            });

            // Settings
            const savedBgColor = localStorage.getItem('chatBgColor');
            const savedBgImage = localStorage.getItem('chatBgImage');
            
            if (savedBgColor) chatMessages.style.backgroundColor = savedBgColor;
            if (savedBgImage) chatMessages.style.backgroundImage = `url(${savedBgImage})`;
            if (savedBgImage) chatMessages.style.backgroundSize = 'cover';

            // Add Settings Button to Header
            const settingsBtn = document.createElement('button');
            settingsBtn.className = 'btn btn-sm btn-light ms-auto';
            settingsBtn.innerHTML = '<i class="fi fi-rr-settings"></i>';
            settingsBtn.onclick = () => new bootstrap.Modal(document.getElementById('chatSettingsModal')).show();
            chatHeader.appendChild(settingsBtn);

            document.getElementById('save-chat-settings').addEventListener('click', () => {
                const color = document.getElementById('chat-bg-color').value;
                const image = document.getElementById('chat-bg-image').value;

                localStorage.setItem('chatBgColor', color);
                localStorage.setItem('chatBgImage', image);

                chatMessages.style.backgroundColor = color;
                if (image) {
                    chatMessages.style.backgroundImage = `url(${image})`;
                    chatMessages.style.backgroundSize = 'cover';
                } else {
                    chatMessages.style.backgroundImage = 'none';
                }
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('chatSettingsModal')).hide();
            });

            // File Handling
            attachmentInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    filePreview.classList.remove('d-none');
                    fileNameSpan.textContent = this.files[0].name;
                }
            });

            removeFileBtn.addEventListener('click', function() {
                attachmentInput.value = '';
                filePreview.classList.add('d-none');
            });

            userItems.forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    userItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');

                    const userId = this.getAttribute('data-id');
                    const userName = this.querySelector('h6').innerText;

                    currentUserId = userId;
                    chatToId.value = userId;
                    chatUserName.innerText = userName;
                    
                    chatHeader.classList.remove('d-none');
                    chatInputArea.classList.remove('d-none');
                    
                    loadMessages(userId);
                });
            });

            chatForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const message = messageInput.value;
                const file = attachmentInput.files[0];

                if (!message && !file) return;

                const formData = new FormData();
                formData.append('to_id', currentUserId);
                if (message) formData.append('body', message);
                if (file) formData.append('attachment', file);

                fetch('{{ route("chat.send") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        // Content-Type not set for FormData
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    appendMessage(data, true);
                    messageInput.value = '';
                    attachmentInput.value = '';
                    filePreview.classList.add('d-none');
                    scrollToBottom();
                });
            });

            function loadMessages(userId) {
                chatMessages.innerHTML = '<div class="text-center p-3">Loading...</div>';
                
                fetch(`{{ url('chat/messages') }}/${userId}`)
                    .then(response => response.json())
                    .then(messages => {
                        chatMessages.innerHTML = '';
                        if (messages.length === 0) {
                            chatMessages.innerHTML = '<div class="text-center text-muted p-3">No messages yet</div>';
                        } else {
                            messages.forEach(msg => {
                                const isMe = msg.from_id == {{ Auth::id() }};
                                appendMessage(msg, isMe);
                            });
                        }
                        scrollToBottom();
                    });
            }

            function appendMessage(msg, isMe) {
                const div = document.createElement('div');
                div.className = `d-flex mb-3 ${isMe ? 'justify-content-end' : 'justify-content-start'}`;
                
                let attachmentHtml = '';
                if (msg.attachment_path) {
                    const url = `{{ asset('storage') }}/${msg.attachment_path}`;
                    if (msg.attachment_type && msg.attachment_type.startsWith('image/')) {
                        attachmentHtml = `<div class="mb-2"><img src="${url}" class="img-fluid rounded" style="max-height: 200px;"></div>`;
                    } else {
                        attachmentHtml = `<div class="mb-2"><a href="${url}" target="_blank" class="btn btn-sm btn-light"><i class="fi fi-rr-file"></i> Download Attachment</a></div>`;
                    }
                }

                div.innerHTML = `
                    <div class="card ${isMe ? 'bg-primary text-white' : 'bg-white'} shadow-sm" style="max-width: 70%;">
                        <div class="card-body p-2">
                            ${attachmentHtml}
                            <p class="mb-0">${msg.body || ''}</p>
                            <small class="${isMe ? 'text-white-50' : 'text-muted'} d-block text-end mt-1" style="font-size: 0.7rem;">
                                ${new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                            </small>
                        </div>
                    </div>
                `;
                chatMessages.appendChild(div);
            }

            function scrollToBottom() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            // Listen for new messages
            if (window.Echo) {
                window.Echo.private('chat.{{ Auth::id() }}')
                    .listen('MessageSent', (e) => {
                        console.log('Message received:', e);
                        if (currentUserId == e.message.from_id) {
                            appendMessage(e.message, false);
                            scrollToBottom();
                        } else {
                            // Optional: Show notification or highlight user in list
                        }
                    });
            } else {
                console.warn('Laravel Echo is not initialized. Real-time updates will not work.');
            }
        });
    </script>
    @endpush
</x-app-layout>
