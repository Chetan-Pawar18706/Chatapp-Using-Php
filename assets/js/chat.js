/**
 * =====================================================
 * ChatApp - Chat JavaScript
 * Personal Messenger with Real-time Features
 * =====================================================
 */

// =====================================================
// Chat State
// =====================================================
const Chat = {
    currentUserId: CHAT_CONFIG.currentUserId,
    selectedUserId: CHAT_CONFIG.selectedUserId,
    messages: [],
    chatList: [],
    currentPage: 1,
    hasMoreMessages: true,
    isLoadingMessages: false,
    replyToMessage: null,
    selectedMessageId: null,
    pollingInterval: null,
    typingTimeout: null,
    isTyping: false,
    searchTimeout: null,
    selectedFile: null,
    autoDelete: '12hours'
};

// =====================================================
// Emoji Data
// =====================================================
const EMOJIS = [
    '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂',
    '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩',
    '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜',
    '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🫡',
    '🤐', '🤨', '😐', '😑', '😶', '😏', '😒', '🙄',
    '😬', '🤥', '😌', '😔', '😪', '🤤', '😴', '😷',
    '🤒', '🤕', '🤢', '🤮', '🥵', '🥶', '🥴', '😵',
    '🤯', '🤠', '🥳', '🥸', '😎', '🤓', '🧐', '😕',
    '👍', '👎', '👊', '✊', '🤛', '🤜', '👏', '🙌',
    '👐', '🤲', '🤝', '🙏', '❤️', '🧡', '💛', '💚',
    '💙', '💜', '🖤', '🤍', '💯', '💢', '💥', '💫',
    '💦', '💨', '🔥', '⭐', '🌟', '✨', '💫', '🎉'
];

// =====================================================
// Initialize Chat
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    initializeChatList();
    initializeMessageInput();
    initializeEmojiPicker();
    initializeContextMenu();
    initializeSearch();
    initializeBackButton();
    initializeFileUpload();
    initializeChatMenu();
    initializeAutoDelete();
    ChatLock.init();
    initializeLockedChatsBtn();
    
    // Load chat list
    loadChatList();
    
    // If a user is selected, check lock before opening
    if (Chat.selectedUserId) {
        checkAndOpenChat(Chat.selectedUserId);
    }
    
    // Start polling
    startPolling();
});

// =====================================================
// Chat List Functions
// =====================================================
function initializeChatList() {
    const searchInput = document.getElementById('chatSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            const items = document.querySelectorAll('#chatList .chat-item');
            items.forEach(item => {
                const name = item.querySelector('.chat-name span')?.textContent.toLowerCase() || '';
                item.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }
}

let lockedChatsMode = false;

function initializeLockedChatsBtn() {
    const btn = document.getElementById('lockedChatsBtn');
    if (!btn) return;
    btn.addEventListener('click', function() {
        lockedChatsMode = !lockedChatsMode;
        const title = document.querySelector('.chat-sidebar .sidebar-header h2');
        if (lockedChatsMode) {
            btn.classList.add('active');
            if (title) title.textContent = 'Locked Chats';
            resetChatWindow();
            showLockedChats();
        } else {
            btn.classList.remove('active');
            if (title) title.textContent = 'Chats';
            renderChatList(Chat.chatList || []);
        }
    });
}

function resetChatWindow() {
    Chat.selectedUserId = null;
    Chat.messages = [];
    const emptyChat = document.getElementById('emptyChat');
    const activeChat = document.getElementById('activeChat');
    const chatSidebar = document.getElementById('chatSidebar');
    if (emptyChat) emptyChat.style.display = 'flex';
    if (activeChat) activeChat.style.display = 'none';
    if (chatSidebar) chatSidebar.classList.remove('hidden');
    stopPolling();
}

async function showLockedChats() {
    const container = document.getElementById('chatList');
    if (!container) return;

    container.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading locked chats...</p>
        </div>
    `;

    try {
        const response = await fetch('/chatapp/api/chat-lock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: 'action=get_locked'
        });
        const result = await response.json();

        if (!result.success || !result.data || !result.data.locked_chats || result.data.locked_chats.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-lock"></i>
                    <p>No locked chats</p>
                    <span>Lock a chat from the chat menu to see it here</span>
                </div>
            `;
            return;
        }

        const locked = result.data.locked_chats;
        container.innerHTML = locked.map(chat => `
            <div class="chat-item chat-item-locked" 
                 data-user-id="${chat.target_id}" data-locked="1" 
                 onclick="handleChatClick(${chat.target_id}, true)">
                <div class="chat-avatar">
                    ${renderAvatar(chat.avatar, chat.username)}
                    <span class="status-dot offline"></span>
                </div>
                <div class="chat-info">
                    <div class="chat-name">
                        <span>${escapeHtml(chat.username)}</span>
                        <i class="fas fa-lock chat-lock-icon"></i>
                    </div>
                    <div class="chat-preview">
                        <span class="chat-message"><i class="fas fa-lock"></i> Password protected</span>
                    </div>
                </div>
            </div>
        `).join('');
    } catch(e) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-lock"></i>
                <p>No locked chats</p>
                <span>Lock a chat from the chat menu to see it here</span>
            </div>
        `;
    }
}


async function loadChatList() {
    const result = await ChatApp.apiRequest('/chat-list.php', 'GET');
    
    if (result.success && result.data) {
        Chat.chatList = result.data.chats;
        renderChatList(result.data.chats);
    }
}

function renderChatList(chats) {
    const container = document.getElementById('chatList');
    if (!container) return;
    
    if (chats.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <p>No conversations yet</p>
                <span>Start chatting with your friends!</span>
            </div>
        `;
        return;
    }
    
    container.innerHTML = chats.map(chat => createChatListItem(chat)).join('');
    
    // Highlight selected chat
    if (Chat.selectedUserId) {
        const activeItem = container.querySelector(`.chat-item[data-user-id="${Chat.selectedUserId}"]`);
        if (activeItem) {
            activeItem.classList.add('active');
        }
    }
}

function createChatListItem(chat) {
    const isLocked = chat.is_locked === true || chat.is_locked === 1;
    
    if (isLocked) {
        return `
            <div class="chat-item chat-item-locked" 
                 data-user-id="${chat.user_id}" data-locked="1" 
                 onclick="handleChatClick(${chat.user_id}, true)">
                <div class="chat-avatar">
                    ${renderAvatar(chat.avatar, chat.username)}
                </div>
                <div class="chat-info">
                    <div class="chat-name">
                        <span>${escapeHtml(chat.username)}</span>
                        <i class="fas fa-lock chat-lock-icon"></i>
                    </div>
                    <div class="chat-preview">
                        <span class="chat-message"><i class="fas fa-lock"></i> Password protected</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    const lastMessage = chat.last_message || 'No messages yet';
    const fromMe = chat.last_message_from_me;
    const unreadBadge = chat.unread_count > 0 ? 
        `<span class="unread-badge">${chat.unread_count}</span>` : '';
    
    const messageClass = fromMe ? 'from-me' : '';
    const timeClass = chat.unread_count > 0 ? ' style="color: var(--primary-light);"' : '';
    
    return `
        <div class="chat-item ${Chat.selectedUserId == chat.user_id ? 'active' : ''}" 
             data-user-id="${chat.user_id}" data-locked="0" 
             onclick="handleChatClick(${chat.user_id}, false)">
            <div class="chat-avatar">
                ${renderAvatar(chat.avatar, chat.username)}
                <span class="status-dot ${chat.is_online ? 'online' : 'offline'}"></span>
            </div>
            <div class="chat-info">
                <div class="chat-name">
                    <span>${escapeHtml(chat.username)}</span>
                    <span class="chat-time"${timeClass}>${chat.last_message_time || ''}</span>
                </div>
                <div class="chat-preview">
                    <span class="chat-message ${messageClass}">${escapeHtml(lastMessage)}</span>
                    ${unreadBadge}
                </div>
            </div>
        </div>
    `;
}

// =====================================================
// Chat Lock Module
// =====================================================
const ChatLock = {
    csrfToken: null,
    pendingUserId: null,
    mode: null,

    init: function() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                         (typeof CHAT_CONFIG !== 'undefined' ? CHAT_CONFIG.csrfToken : '');
    },

    openModal: function(userId, mode) {
        this.pendingUserId = userId;
        this.mode = mode;
        const modal = document.getElementById('chatLockModal');
        const title = document.getElementById('chatLockModalTitle');
        const desc = document.getElementById('chatLockModalDesc');
        const input = document.getElementById('chatLockPassword');
        const btn = document.getElementById('chatLockSubmitBtn');
        if (!modal) return;

        if (mode === 'set') {
            if (title) title.innerHTML = '<i class="fas fa-lock"></i> Lock Chat';
            if (desc) desc.textContent = 'Set a password to lock this chat (min 8 characters)';
            if (btn) btn.innerHTML = '<i class="fas fa-lock"></i> Lock';
        } else if (mode === 'remove') {
            if (title) title.innerHTML = '<i class="fas fa-unlock"></i> Unlock Chat';
            if (desc) desc.textContent = 'Enter the password to unlock this chat';
            if (btn) btn.innerHTML = '<i class="fas fa-unlock"></i> Unlock';
        } else {
            if (title) title.innerHTML = '<i class="fas fa-lock"></i> Chat Locked';
            if (desc) desc.textContent = 'Enter password to unlock this chat';
            if (btn) btn.innerHTML = '<i class="fas fa-unlock"></i> Unlock';
        }

        if (input) { input.value = ''; input.focus(); }
        modal.classList.add('active');
    },

    closeModal: function() {
        const modal = document.getElementById('chatLockModal');
        if (modal) modal.classList.remove('active');
        this.pendingUserId = null;
        this.mode = null;
    },

    submit: function() {
        if (this.mode === 'set') return this.doSetLock();
        if (this.mode === 'remove') return this.doRemoveLock();
        return this.doVerify();
    },

    doVerify: async function() {
        const password = document.getElementById('chatLockPassword')?.value;
        if (!password || !this.pendingUserId) return;
        const uid = this.pendingUserId;
        try {
            const response = await fetch('/chatapp/api/chat-lock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: 'action=verify&chat_type=chat&target_id=' + uid + '&password=' + encodeURIComponent(password)
            });
            const data = await response.json();
            if (data.verified) {
                this.closeModal();
                openChat(uid);
            } else {
                showToast(data.message || 'Incorrect password', 'error');
                var inp = document.getElementById('chatLockPassword');
                if (inp) { inp.value = ''; inp.focus(); }
            }
        } catch(e) {
            showToast('Verification failed. Try again.', 'error');
        }
    },

    doSetLock: async function() {
        const password = document.getElementById('chatLockPassword')?.value;
        if (!password || !this.pendingUserId) return;
        if (password.length < 8) {
            showToast('Password must be at least 8 characters', 'error');
            return;
        }
        const uid = this.pendingUserId;
        try {
            const response = await fetch('/chatapp/api/chat-lock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `action=set&chat_type=chat&target_id=${uid}&password=${encodeURIComponent(password)}&csrf_token=${this.csrfToken}`
            });
            const data = await response.json();
            if (data.success) {
                this.closeModal();
                showToast('Chat locked', 'success');
                if (Chat.selectedUserId == uid) {
                    resetChatWindow();
                }
                loadChatList();
            } else {
                showToast(data.message || 'Failed to lock chat', 'error');
            }
        } catch(e) {
            showToast('Failed to lock chat. Try again.', 'error');
        }
    },

    doRemoveLock: async function() {
        const password = document.getElementById('chatLockPassword')?.value;
        if (!password || !this.pendingUserId) return;
        const uid = this.pendingUserId;
        try {
            const response = await fetch('/chatapp/api/chat-lock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `action=remove&chat_type=chat&target_id=${uid}&password=${encodeURIComponent(password)}&csrf_token=${this.csrfToken}`
            });
            const data = await response.json();
            if (data.success) {
                this.closeModal();
                showToast('Chat unlocked', 'success');
                loadChatList();
                openChat(uid);
            } else {
                showToast(data.message || 'Failed to unlock chat', 'error');
            }
        } catch(e) {
            showToast('Failed to unlock chat. Try again.', 'error');
        }
    },

    // Legacy aliases
    promptPassword: function(userId) {
        this.openModal(userId, 'verify');
    },

    setLock: function(userId) {
        this.openModal(userId, 'set');
    },

    removeLock: function(userId) {
        this.openModal(userId, 'remove');
    }
};

function handleChatClick(userId, isLocked) {
    if (isLocked === true || isLocked === 'true' || isLocked === 1 || isLocked === '1') {
        ChatLock.promptPassword(userId);
    } else {
        openChat(userId);
    }
}

// =====================================================
// Check Lock & Open Chat
// =====================================================
async function checkAndOpenChat(userId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                      (typeof CHAT_CONFIG !== 'undefined' ? CHAT_CONFIG.csrfToken : '');
    const formData = new URLSearchParams();
    formData.append('action', 'check');
    formData.append('chat_type', 'chat');
    formData.append('target_id', userId);
    formData.append('csrf_token', csrfToken);
    
    try {
        const response = await fetch('/chatapp/api/chat-lock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: formData.toString()
        });
        const result = await response.json();
        if (result.success && result.locked) {
            ChatLock.promptPassword(userId);
        } else {
            openChat(userId);
        }
    } catch(e) {
        openChat(userId);
    }
}

// =====================================================
// Open Chat
// =====================================================
async function openChat(userId) {
    Chat.selectedUserId = userId;
    Chat.currentPage = 1;
    Chat.hasMoreMessages = true;
    Chat.messages = [];
    
    // Update UI
    var emptyChatEl = document.getElementById('emptyChat');
    var activeChatEl = document.getElementById('activeChat');
    if (emptyChatEl) emptyChatEl.style.display = 'none';
    if (activeChatEl) activeChatEl.style.display = 'flex';
    
    // Update sidebar active state
    document.querySelectorAll('.chat-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.userId == userId) {
            item.classList.add('active');
        }
    });
    
    // On mobile, hide sidebar and show chat
    if (window.innerWidth <= 768) {
        document.getElementById('chatSidebar').classList.add('hidden');
    }
    
    // Load user info
    await loadChatUserInfo(userId);
    
    // Load messages
    await loadMessages(userId);
    
    // Scroll to bottom
    scrollToBottom();
    
    // Mark messages as read
    await markMessagesAsRead(userId);
    
    // Clear unread badge in chat list
    clearUnreadBadge(userId);
    
    // Check lock status
    checkChatLockStatus(userId);
}

async function checkChatLockStatus(userId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                      (typeof CHAT_CONFIG !== 'undefined' ? CHAT_CONFIG.csrfToken : '');
    try {
        const response = await fetch('/chatapp/api/chat-lock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: `action=check&chat_type=chat&target_id=${userId}&csrf_token=${csrfToken}`
        });
        const result = await response.json();
        const lockChat = document.getElementById('menuLockChat');
        const unlockChat = document.getElementById('menuUnlockChat');
        if (result.success && result.locked) {
            if (lockChat) lockChat.style.display = 'none';
            if (unlockChat) unlockChat.style.display = 'block';
        } else {
            if (lockChat) lockChat.style.display = 'block';
            if (unlockChat) unlockChat.style.display = 'none';
        }
    } catch(e) {}
}

async function loadChatUserInfo(userId) {
    const result = await ChatApp.apiRequest(`/chat-user-info.php?user_id=${userId}`, 'GET');
    
    if (result.success && result.data) {
        const user = result.data;
        
        var chatAvatarEl = document.getElementById('chatAvatar');
        var chatUsernameEl = document.getElementById('chatUsername');
        if (chatAvatarEl) {
            var avatarParent = chatAvatarEl.parentNode;
            if (user.avatar) {
                avatarParent.innerHTML = '<img src="' + user.avatar + '" alt="' + escapeHtml(user.username) + '" class="user-avatar-img">' +
                    '<span class="status-dot" id="chatStatusDot"></span>';
            } else {
                chatAvatarEl.textContent = user.username.charAt(0).toUpperCase();
            }
        }
        if (chatUsernameEl) chatUsernameEl.textContent = user.username;
        
        const statusDot = document.getElementById('chatStatusDot');
        if (statusDot) statusDot.className = `status-dot ${user.is_online ? 'online' : 'offline'}`;
        
        const statusText = document.getElementById('chatUserStatus');
        if (user.is_typing) {
            statusText.textContent = 'typing...';
            statusText.className = 'user-status online';
        } else if (user.is_online) {
            statusText.textContent = 'Online';
            statusText.className = 'user-status online';
        } else {
            statusText.textContent = user.last_seen;
            statusText.className = 'user-status';
        }
    }
}

// =====================================================
// Messages Functions
// =====================================================
async function loadMessages(userId, page = 1, append = false) {
    if (Chat.isLoadingMessages) return;
    Chat.isLoadingMessages = true;
    
    const result = await ChatApp.apiRequest(
        `/get-messages.php?user_id=${userId}&page=${page}`, 
        'GET'
    );
    
    Chat.isLoadingMessages = false;
    
    if (result.success && result.data) {
        const messages = result.data.messages;
        Chat.hasMoreMessages = result.data.has_more;
        
        if (append) {
            // Prepend older messages
            Chat.messages = [...messages, ...Chat.messages];
            renderMessages(messages, true);
        } else {
            Chat.messages = messages;
            renderMessages(messages, false);
        }
        
        // Update load more button
        updateLoadMoreButton();
    }
}

function renderMessages(messages, prepend = false) {
    const container = document.getElementById('messagesList');
    if (!container) return;
    
    const html = messages.map(msg => createMessageBubble(msg)).join('');
    
    if (prepend) {
        const scrollHeight = document.getElementById('messagesContainer').scrollHeight;
        container.insertAdjacentHTML('afterbegin', html);
        // Maintain scroll position
        document.getElementById('messagesContainer').scrollTop = 
            document.getElementById('messagesContainer').scrollHeight - scrollHeight;
    } else {
        container.innerHTML = html;
    }
}

function createMessageBubble(msg) {
    const isSender = msg.is_sender;
    const messageClass = isSender ? 'sent' : 'received';
    
    // Handle deleted messages
    if (msg.is_deleted || msg.is_deleted_for_me) {
        return `
            <div class="message ${messageClass}" data-message-id="${msg.id}">
                <div class="message-bubble">
                    <div class="message-text deleted">
                        <i class="fas fa-ban"></i> ${escapeHtml(msg.content)}
                    </div>
                    <div class="message-meta">
                        <span class="message-time">${msg.timestamp}</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Handle uploading state
    if (msg.message_type === 'uploading') {
        return `
            <div class="message ${messageClass}" data-message-id="${msg.id}">
                <div class="message-bubble">
                    <div class="message-text">${escapeHtml(msg.content)}</div>
                    <div class="upload-progress-bar">
                        <div class="upload-progress-fill"></div>
                    </div>
                    <div class="message-meta">
                        <span class="message-time">${msg.timestamp}</span>
                        <i class="fas fa-spinner fa-spin message-status uploading"></i>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Reply content
    let replyHtml = '';
    if (msg.reply_to) {
        replyHtml = `
            <div class="message-reply" onclick="scrollToMessage(${msg.reply_to.id})">
                <div class="reply-sender">${escapeHtml(msg.reply_to.sender_name)}</div>
                <div class="reply-text">${escapeHtml(msg.reply_to.content)}</div>
            </div>
        `;
    }
    
    // Media content
    let mediaHtml = '';
    if (msg.media_id && msg.media) {
        const media = msg.media;
        if (media.is_image) {
            mediaHtml = `<div class="message-media"><img src="/chatapp/api/preview-media.php?id=${media.id}" alt="${escapeHtml(media.original_name)}" loading="lazy" onclick="window.open('/chatapp/api/preview-media.php?id=${media.id}', '_blank')"></div>`;
        } else if (media.is_video) {
            mediaHtml = `<div class="message-media"><video controls preload="metadata"><source src="/chatapp/api/preview-media.php?id=${media.id}" type="${escapeHtml(media.file_type)}"></video></div>`;
        } else {
            const iconMap = { pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word', xls: 'fa-file-excel', xlsx: 'fa-file-excel', ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint', txt: 'fa-file-alt', zip: 'fa-file-archive', rar: 'fa-file-archive', '7z': 'fa-file-archive' };
            const ext = media.file_extension || '';
            const iconClass = iconMap[ext] || 'fa-file';
            mediaHtml = `
                <div class="message-media document-attachment" onclick="window.open('/chatapp/api/preview-media.php?id=${media.id}', '_blank')">
                    <div class="doc-icon"><i class="fas ${iconClass}"></i></div>
                    <div class="doc-info">
                        <div class="doc-name">${escapeHtml(media.original_name)}</div>
                        <div class="doc-size">${media.file_size_formatted || ''}</div>
                    </div>
                </div>
            `;
        }
    }
    
    // Message status icon
    let statusHtml = '';
    if (isSender && msg.status) {
        const statusIcon = msg.status === 'seen' ? 'fa-check-double' : 
                          msg.status === 'delivered' ? 'fa-check-double' : 'fa-check';
        statusHtml = `<i class="fas ${statusIcon} message-status ${msg.status}"></i>`;
    }
    
    // Reactions
    let reactionsHtml = '';
    if (msg.reactions && msg.reactions.length > 0) {
        const reactionItems = msg.reactions.map(r => 
            `<button class="reaction-chip ${r.reacted ? 'reacted' : ''}" onclick="toggleReaction(${msg.id}, '${r.emoji}')" title="${r.count} reaction${r.count > 1 ? 's' : ''}">${r.emoji} <span>${r.count > 1 ? r.count : ''}</span></button>`
        ).join('');
        reactionsHtml = `<div class="message-reactions">${reactionItems}</div>`;
    }
    
    return `
        <div class="message ${messageClass}" data-message-id="${msg.id}">
            <div class="message-bubble ${msg.is_saved ? 'saved' : ''}" oncontextmenu="showContextMenu(event, ${msg.id})" onclick="hideContextMenu()" ondblclick="showReactionPicker(event, ${msg.id})">
                ${replyHtml}
                ${mediaHtml}
                ${msg.content ? `<div class="message-text">${formatMessageContent(msg.content)}</div>` : ''}
                <div class="message-meta">
                    <span class="message-time">${msg.timestamp}</span>
                    ${msg.is_saved ? '<i class="fas fa-bookmark saved-icon"></i>' : ''}
                    ${statusHtml}
                </div>
            </div>
            ${reactionsHtml}
        </div>
    `;
}

function removeUploadPlaceholder(uploadId) {
    const el = document.querySelector(`.message[data-message-id="${uploadId}"]`);
    if (el) el.remove();
}

// =====================================================
// Message Reactions
// =====================================================
const QUICK_REACTIONS = ['👍', '❤️', '😂', '😮', '😢', '🙏', '🔥', '👏'];

function showReactionPicker(event, messageId) {
    event.stopPropagation();
    hideReactionPicker();
    
    const picker = document.createElement('div');
    picker.className = 'reaction-picker';
    picker.id = 'reactionPicker';
    picker.innerHTML = QUICK_REACTIONS.map(e => 
        `<button class="reaction-option" onclick="toggleReaction(${messageId}, '${e}')">${e}</button>`
    ).join('');
    
    document.body.appendChild(picker);
    
    const msgEl = document.querySelector(`.message[data-message-id="${messageId}"] .message-bubble`);
    if (msgEl) {
        const rect = msgEl.getBoundingClientRect();
        picker.style.left = Math.min(rect.left, window.innerWidth - 280) + 'px';
        picker.style.top = (rect.top - 48) + 'px';
    }
    
    setTimeout(() => {
        document.addEventListener('click', hideReactionPicker, { once: true });
    }, 10);
}

function hideReactionPicker() {
    const p = document.getElementById('reactionPicker');
    if (p) p.remove();
}

async function toggleReaction(messageId, emoji) {
    hideReactionPicker();
    
    const result = await ChatApp.apiRequest('/toggle-reaction.php', 'POST', {
        message_id: messageId,
        emoji: emoji,
        csrf_token: CHAT_CONFIG.csrfToken
    });
    
    if (result.success) {
        // Refresh messages to show updated reactions
        loadMessages(Chat.selectedUserId);
    }
}

function formatMessageContent(content) {
    // Escape HTML and convert URLs to links
    let formatted = escapeHtml(content);
    
    // Convert URLs to clickable links
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    formatted = formatted.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
    
    return formatted;
}

function updateLoadMoreButton() {
    const loadMore = document.getElementById('loadMore');
    if (loadMore) {
        loadMore.style.display = Chat.hasMoreMessages ? 'block' : 'none';
    }
}

// =====================================================
// Message Input
// =====================================================
function initializeMessageInput() {
    const input = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    
    // Auto-resize textarea
    if (input) {
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            
            // Enable/disable send button
            sendBtn.disabled = !this.value.trim();
            
            // Handle typing status
            handleTyping();
        });
        
        // Send on Enter (Shift+Enter for new line)
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    // Send button click
    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }
    
    // Load more messages
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            if (Chat.hasMoreMessages && !Chat.isLoadingMessages) {
                Chat.currentPage++;
                loadMessages(Chat.selectedUserId, Chat.currentPage, true);
            }
        });
    }
    
    // Close reply preview
    const closeReply = document.getElementById('closeReply');
    if (closeReply) {
        closeReply.addEventListener('click', cancelReply);
    }
}

// =====================================================
// Auto-Delete Timer
// =====================================================
function initializeAutoDelete() {
    const btn = document.getElementById('autoDeleteBtn');
    const dropdown = document.getElementById('autoDeleteDropdown');
    
    if (btn && dropdown) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
        
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== btn) {
                dropdown.style.display = 'none';
            }
        });
        
        dropdown.querySelectorAll('.auto-delete-option').forEach(function(opt) {
            opt.addEventListener('click', function() {
                dropdown.querySelectorAll('.auto-delete-option').forEach(function(o) { o.classList.remove('active'); });
                this.classList.add('active');
                Chat.autoDelete = this.dataset.value;
                btn.classList.add('active-timer');
                dropdown.style.display = 'none';
            });
        });
    }
}

// =====================================================
// File Upload
// =====================================================
function initializeFileUpload() {
    const attachBtn = document.getElementById('attachBtn');
    const fileInput = document.getElementById('fileInput');
    const filePreviewBar = document.getElementById('filePreviewBar');
    const filePreviewContent = document.getElementById('filePreviewContent');
    const filePreviewClose = document.getElementById('filePreviewClose');
    const sendBtn = document.getElementById('sendBtn');

    if (attachBtn && fileInput) {
        attachBtn.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                Chat.selectedFile = this.files[0];
                showFilePreview(this.files[0]);
                sendBtn.disabled = false;
            }
        });
    }

    if (filePreviewClose) {
        filePreviewClose.addEventListener('click', clearFileSelection);
    }

    // Drag & Drop
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-over');
        });
        messagesContainer.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });
        messagesContainer.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                Chat.selectedFile = e.dataTransfer.files[0];
                showFilePreview(e.dataTransfer.files[0]);
                sendBtn.disabled = false;
            }
        });
    }
}

function showFilePreview(file) {
    const bar = document.getElementById('filePreviewBar');
    const content = document.getElementById('filePreviewContent');
    if (!bar || !content) return;

    let preview = '';
    if (file.type.startsWith('image/')) {
        const url = URL.createObjectURL(file);
        preview = `<img src="${url}" alt="Preview" class="file-preview-thumb">`;
    } else if (file.type.startsWith('video/')) {
        preview = `<i class="fas fa-file-video file-preview-icon video"></i>`;
    } else {
        const ext = file.name.split('.').pop().toLowerCase();
        let iconClass = 'fa-file';
        if (ext === 'pdf') iconClass = 'fa-file-pdf';
        else if (['doc', 'docx'].includes(ext)) iconClass = 'fa-file-word';
        else if (['xls', 'xlsx'].includes(ext)) iconClass = 'fa-file-excel';
        else if (['zip', 'rar', '7z'].includes(ext)) iconClass = 'fa-file-archive';
        preview = `<i class="fas ${iconClass} file-preview-icon doc"></i>`;
    }

    const sizeStr = formatFileSize(file.size);
    content.innerHTML = `
        ${preview}
        <div class="file-preview-info">
            <span class="file-preview-name">${escapeHtml(file.name)}</span>
            <span class="file-preview-size">${sizeStr}</span>
        </div>
    `;
    bar.style.display = 'flex';
}

function clearFileSelection() {
    Chat.selectedFile = null;
    const bar = document.getElementById('filePreviewBar');
    const fileInput = document.getElementById('fileInput');
    if (bar) bar.style.display = 'none';
    if (fileInput) fileInput.value = '';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

async function sendMessage() {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    const file = Chat.selectedFile;

    if (!content && !file) return;
    if (!Chat.selectedUserId) return;

    const hasFile = !!file;

    // Clear input immediately
    input.value = '';
    input.style.height = 'auto';
    document.getElementById('sendBtn').disabled = true;
    clearFileSelection();
    cancelReply();

    if (hasFile) {
        // Show uploading state
        const uploadId = 'upload-' + Date.now();
        appendMessage({
            id: uploadId,
            content: content || file.name,
            message_type: 'uploading',
            is_sender: true,
            is_deleted: false,
            timestamp: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
            upload_progress: 0
        });
        scrollToBottom();

        const formData = new FormData();
        formData.append('file', file);
        formData.append('receiver_id', Chat.selectedUserId);
        formData.append('message', content);
        formData.append('csrf_token', CHAT_CONFIG.csrfToken);
        if (Chat.replyToMessage) {
            formData.append('reply_to', Chat.replyToMessage.id);
        }

        try {
            const result = await ChatApp.apiRequest('/send-message-media.php', 'POST', formData, true);
            if (result.success && result.data) {
                // Remove uploading placeholder, add real message
                removeUploadPlaceholder(uploadId);
                appendMessage(result.data.message);
                updateChatListLastMessage(Chat.selectedUserId, content || file.name);
            } else {
                removeUploadPlaceholder(uploadId);
                input.value = content;
                ChatApp.showToast(result.message || 'Failed to send file', 'error');
            }
        } catch (e) {
            removeUploadPlaceholder(uploadId);
            input.value = content;
            ChatApp.showToast('Failed to send file', 'error');
        }
    } else {
        const messageData = {
            receiver_id: Chat.selectedUserId,
            content: content,
            message_type: 'text',
            auto_delete: Chat.autoDelete,
            csrf_token: CHAT_CONFIG.csrfToken
        };

        if (Chat.replyToMessage) {
            messageData.reply_to_id = Chat.replyToMessage.id;
        }

        const result = await ChatApp.apiRequest('/send-message.php', 'POST', messageData);

        if (result.success && result.data) {
            appendMessage(result.data.message);
            updateChatListLastMessage(Chat.selectedUserId, content);
        } else {
            input.value = content;
            ChatApp.showToast(result.message || 'Failed to send message', 'error');
        }
    }

    scrollToBottom();
}

function appendMessage(msg) {
    const container = document.getElementById('messagesList');
    if (!container) return;
    
    const html = createMessageBubble(msg);
    container.insertAdjacentHTML('beforeend', html);
    
    // Add to messages array
    Chat.messages.push(msg);
}

// =====================================================
// Reply Functions
// =====================================================
function replyToMessage(messageId) {
    const message = Chat.messages.find(m => m.id === messageId);
    if (!message) return;
    
    Chat.replyToMessage = message;
    
    // Show reply preview
    const preview = document.getElementById('replyPreview');
    const replyTo = document.getElementById('replyTo');
    const replyText = document.getElementById('replyText');
    
    replyTo.textContent = `Replying to ${message.is_sender ? 'yourself' : 'them'}`;
    replyText.textContent = message.content;
    preview.style.display = 'block';
    
    // Focus input
    document.getElementById('messageInput').focus();
    
    hideContextMenu();
}

function cancelReply() {
    Chat.replyToMessage = null;
    var replyPreview = document.getElementById('replyPreview');
    if (replyPreview) replyPreview.style.display = 'none';
}

// =====================================================
// Delete Functions
// =====================================================
function showDeleteOptions(messageId) {
    Chat.selectedMessageId = messageId;
    
    const message = Chat.messages.find(m => m.id === messageId);
    if (!message) return;
    
    const deleteForEveryone = document.getElementById('deleteForEveryoneMenuItem');
    const saveMenuItem = document.getElementById('saveMenuItem');
    
    // Only show "delete for everyone" for own messages within 7 days
    if (message.is_sender) {
        deleteForEveryone.style.display = 'block';
    } else {
        deleteForEveryone.style.display = 'none';
    }
    
    // Update save button label
    if (saveMenuItem) {
        if (message.is_saved) {
            saveMenuItem.innerHTML = '<i class="fas fa-bookmark"></i> Unsave';
        } else {
            saveMenuItem.innerHTML = '<i class="fas fa-bookmark"></i> Save';
        }
    }
}

async function toggleSaveMessage(messageId) {
    const result = await ChatApp.apiRequest('/save-message.php', 'POST', {
        message_id: messageId,
        csrf_token: CHAT_CONFIG.csrfToken
    });
    
    if (result.success) {
        const message = Chat.messages.find(m => m.id === messageId);
        if (message) {
            message.is_saved = result.data.saved;
        }
        
        // Update UI - show/hide saved indicator
        const msgElement = document.querySelector(`.message[data-message-id="${messageId}"]`);
        if (msgElement) {
            const bubble = msgElement.querySelector('.message-bubble');
            if (result.data.saved) {
                bubble.classList.add('saved');
                ChatApp.showToast('Message saved', 'success');
            } else {
                bubble.classList.remove('saved');
                ChatApp.showToast('Message unsaved', 'success');
            }
        }
    } else {
        ChatApp.showToast(result.message || 'Failed to save message', 'error');
    }
}

async function deleteMessage(deleteType) {
    if (!Chat.selectedMessageId) return;
    
    const result = await ChatApp.apiRequest('/delete-message.php', 'POST', {
        message_id: Chat.selectedMessageId,
        delete_type: deleteType,
        csrf_token: CHAT_CONFIG.csrfToken
    });
    
    if (result.success) {
        // Update message in UI
        const msgElement = document.querySelector(`.message[data-message-id="${Chat.selectedMessageId}"]`);
        if (msgElement) {
            const textEl = msgElement.querySelector('.message-text');
            if (textEl) {
                textEl.textContent = 'This message was deleted';
                textEl.classList.add('deleted');
            }
        }
        
        ChatApp.showToast(result.message, 'success');
    } else {
        ChatApp.showToast(result.message || 'Failed to delete message', 'error');
    }
    
    hideContextMenu();
}

// =====================================================
// Message Status
// =====================================================
async function markMessagesAsRead(userId) {
    await ChatApp.apiRequest('/mark-read.php', 'POST', {
        sender_id: userId,
        csrf_token: CHAT_CONFIG.csrfToken
    });
}

function clearUnreadBadge(userId) {
    const chatItem = document.querySelector(`.chat-item[data-user-id="${userId}"]`);
    if (chatItem) {
        const badge = chatItem.querySelector('.unread-badge');
        if (badge) badge.remove();
    }
}

function updateMessageStatus(messageId, status) {
    const msgElement = document.querySelector(`.message[data-message-id="${messageId}"]`);
    if (msgElement) {
        const statusEl = msgElement.querySelector('.message-status');
        if (statusEl) {
            statusEl.className = `fas fa-check-double message-status ${status}`;
        }
    }
}

// =====================================================
// Context Menu
// =====================================================
function initializeContextMenu() {
    // Close context menu on click outside
    document.addEventListener('click', hideContextMenu);
    
    // Reply menu item
    document.getElementById('replyMenuItem')?.addEventListener('click', function() {
        if (Chat.selectedMessageId) {
            replyToMessage(Chat.selectedMessageId);
        }
    });
    
    // Copy menu item
    document.getElementById('copyMenuItem')?.addEventListener('click', function() {
        if (Chat.selectedMessageId) {
            const message = Chat.messages.find(m => m.id === Chat.selectedMessageId);
            if (message) {
                navigator.clipboard.writeText(message.content);
                ChatApp.showToast('Message copied', 'success');
            }
        }
        hideContextMenu();
    });
    
    // Save/Unsave message
    document.getElementById('saveMenuItem')?.addEventListener('click', async function() {
        if (!Chat.selectedMessageId) return;
        await toggleSaveMessage(Chat.selectedMessageId);
        hideContextMenu();
    });
    
    // Delete for me
    document.getElementById('deleteForMeMenuItem')?.addEventListener('click', function() {
        deleteMessage('for_me');
    });
    
    // Delete for everyone
    document.getElementById('deleteForEveryoneMenuItem')?.addEventListener('click', function() {
        if (confirm('Delete this message for everyone?')) {
            deleteMessage('for_everyone');
        }
    });
}

function showContextMenu(event, messageId) {
    event.preventDefault();
    event.stopPropagation();
    
    Chat.selectedMessageId = messageId;
    showDeleteOptions(messageId);
    
    const menu = document.getElementById('contextMenu');
    if (!menu) return;
    menu.style.display = 'block';
    
    // Position menu
    const x = Math.min(event.clientX, window.innerWidth - 200);
    const y = Math.min(event.clientY, window.innerHeight - 200);
    menu.style.left = x + 'px';
    menu.style.top = y + 'px';
}

function hideContextMenu() {
    var menu = document.getElementById('contextMenu');
    if (menu) menu.style.display = 'none';
}

// =====================================================
// Emoji Picker
// =====================================================
function initializeEmojiPicker() {
    const emojiBtn = document.getElementById('emojiBtn');
    const emojiPicker = document.getElementById('emojiPicker');
    const emojiGrid = document.getElementById('emojiGrid');
    
    // Populate emojis
    if (emojiGrid) {
        emojiGrid.innerHTML = EMOJIS.map(emoji => 
            `<button class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</button>`
        ).join('');
    }
    
    // Toggle picker
    if (emojiBtn) {
        emojiBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            emojiPicker.style.display = emojiPicker.style.display === 'none' ? 'block' : 'none';
        });
    }
    
    // Close picker on click outside
    document.addEventListener('click', function(e) {
        if (!emojiPicker?.contains(e.target) && e.target !== emojiBtn) {
            emojiPicker.style.display = 'none';
        }
    });
}

function insertEmoji(emoji) {
    const input = document.getElementById('messageInput');
    if (input) {
        const start = input.selectionStart;
        const end = input.selectionEnd;
        input.value = input.value.substring(0, start) + emoji + input.value.substring(end);
        input.selectionStart = input.selectionEnd = start + emoji.length;
        input.focus();
        
        // Enable send button
        document.getElementById('sendBtn').disabled = !input.value.trim();
    }
}

// =====================================================
// Search Messages
// =====================================================
function initializeSearch() {
    const searchBtn = document.getElementById('searchChatBtn');
    const searchBar = document.getElementById('chatSearchBar');
    const closeSearch = document.getElementById('closeSearch');
    const searchInput = document.getElementById('messageSearchInput');
    
    // Toggle search bar
    searchBtn?.addEventListener('click', function() {
        searchBar.style.display = searchBar.style.display === 'none' ? 'block' : 'none';
        if (searchBar.style.display === 'block') {
            searchInput.focus();
        }
    });
    
    // Close search
    closeSearch?.addEventListener('click', function() {
        searchBar.style.display = 'none';
        searchInput.value = '';
        document.getElementById('searchResultsCount').textContent = '';
        // Reload original messages
        loadMessages(Chat.selectedUserId);
    });
    
    // Search input
    searchInput?.addEventListener('input', function() {
        clearTimeout(Chat.searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            document.getElementById('searchResultsCount').textContent = '';
            loadMessages(Chat.selectedUserId);
            return;
        }
        
        Chat.searchTimeout = setTimeout(() => {
            searchMessages(query);
        }, 300);
    });
}

async function searchMessages(query) {
    const result = await ChatApp.apiRequest(
        `/search-messages.php?user_id=${Chat.selectedUserId}&q=${encodeURIComponent(query)}`,
        'GET'
    );
    
    if (result.success && result.data) {
        const messages = result.data.messages;
        document.getElementById('searchResultsCount').textContent = 
            `${messages.length} message${messages.length !== 1 ? 's' : ''} found`;
        
        // Render search results
        const container = document.getElementById('messagesList');
        container.innerHTML = messages.map(msg => {
            const highlight = highlightText(msg.content, query);
            return `
                <div class="message ${msg.is_sender ? 'sent' : 'received'}" data-message-id="${msg.id}">
                    <div class="message-bubble" onclick="scrollToMessage(${msg.id})">
                        <div class="message-text">${highlight}</div>
                        <div class="message-meta">
                            <span class="message-time">${msg.timestamp}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
}

function highlightText(text, query) {
    const escaped = escapeHtml(text);
    const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
    return escaped.replace(regex, '<mark>$1</mark>');
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// =====================================================
// Typing Indicator
// =====================================================
function handleTyping() {
    if (!Chat.selectedUserId) return;
    
    if (!Chat.isTyping) {
        Chat.isTyping = true;
        updateTypingStatus(true);
    }
    
    clearTimeout(Chat.typingTimeout);
    Chat.typingTimeout = setTimeout(() => {
        Chat.isTyping = false;
        updateTypingStatus(false);
    }, 2000);
}

async function updateTypingStatus(isTyping) {
    await ChatApp.apiRequest('/update-typing.php', 'POST', {
        user_id: Chat.selectedUserId,
        is_typing: isTyping,
        csrf_token: CHAT_CONFIG.csrfToken
    });
}

function showTypingIndicator(username) {
    const indicator = document.getElementById('typingIndicator');
    if (!indicator) return;
    const avatar = document.getElementById('typingAvatar');
    const text = document.getElementById('typingText');
    if (avatar) avatar.textContent = (username || 'U').charAt(0).toUpperCase();
    if (text) text.textContent = (username || 'Friend') + ' is typing...';
    indicator.style.display = 'flex';
    scrollToBottom();
}

function hideTypingIndicator() {
    var indicator = document.getElementById('typingIndicator');
    if (indicator) indicator.style.display = 'none';
}

// =====================================================
// Polling
// =====================================================
function startPolling() {
    stopPolling();
    Chat.pollingInterval = setInterval(async () => {
        if (!Chat.selectedUserId) return;
        
        await refreshNewMessages();
        await checkTypingStatus();
        await refreshChatList();
    }, 2000);
}

function stopPolling() {
    if (Chat.pollingInterval) {
        clearInterval(Chat.pollingInterval);
        Chat.pollingInterval = null;
    }
}

async function refreshNewMessages() {
    if (Chat.isLoadingMessages) return;
    
    const lastMessageId = Chat.messages.length > 0 ? 
        Chat.messages[Chat.messages.length - 1].id : 0;
    
    const result = await ChatApp.apiRequest(
        `/get-messages.php?user_id=${Chat.selectedUserId}&page=1`,
        'GET'
    );
    
    if (result.success && result.data) {
        const newMessages = result.data.messages;
        
        // Find truly new messages
        const existingIds = new Set(Chat.messages.map(m => m.id));
        const trulyNew = newMessages.filter(m => !existingIds.has(m.id));
        
        if (trulyNew.length > 0) {
            // Check if we're at the bottom
            const container = document.getElementById('messagesContainer');
            const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;
            
            // Append new messages
            trulyNew.forEach(msg => appendMessage(msg));
            
            // Mark as read
            const newFromOther = trulyNew.filter(m => !m.is_sender);
            if (newFromOther.length > 0) {
                await markMessagesAsRead(Chat.selectedUserId);
                clearUnreadBadge(Chat.selectedUserId);
            }
            
            // Scroll to bottom if was at bottom
            if (isAtBottom) {
                scrollToBottom();
            }
        }
        
        // Update statuses of sent messages
        newMessages.forEach(msg => {
            if (msg.is_sender && msg.status) {
                updateMessageStatus(msg.id, msg.status);
            }
        });
    }
}

async function checkTypingStatus() {
    if (!Chat.selectedUserId) return;
    
    const result = await ChatApp.apiRequest(
        `/chat-user-info.php?user_id=${Chat.selectedUserId}`,
        'GET'
    );
    
    if (result.success && result.data) {
        const statusText = document.getElementById('chatUserStatus');
        
        if (result.data.is_typing) {
            statusText.textContent = 'typing...';
            statusText.className = 'user-status online';
            showTypingIndicator(result.data.username);
        } else {
            hideTypingIndicator();
            if (result.data.is_online) {
                statusText.textContent = 'Online';
                statusText.className = 'user-status online';
            } else {
                statusText.textContent = result.data.last_seen;
                statusText.className = 'user-status';
            }
        }
    }
}

async function refreshChatList() {
    const result = await ChatApp.apiRequest('/chat-list.php', 'GET');
    
    if (result.success && result.data) {
        Chat.chatList = result.data.chats;
        
        // Update current chat's unread count
        const currentChat = result.data.chats.find(c => c.user_id == Chat.selectedUserId);
        if (currentChat && currentChat.unread_count === 0) {
            clearUnreadBadge(Chat.selectedUserId);
        }
    }
}

// =====================================================
// Scroll Functions
// =====================================================
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 50);
    }
}

function scrollToMessage(messageId) {
    const msgElement = document.querySelector(`.message[data-message-id="${messageId}"]`);
    if (msgElement) {
        msgElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        msgElement.classList.add('highlight');
        setTimeout(() => msgElement.classList.remove('highlight'), 2000);
    }
}

// =====================================================
// Chat Menu (3-dots dropdown)
// =====================================================
function initializeChatMenu() {
    const menuBtn = document.getElementById('chatMenuBtn');
    const dropdown = document.getElementById('chatDropdownMenu');
    
    if (menuBtn && dropdown) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });
        
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && e.target !== menuBtn) {
                dropdown.style.display = 'none';
            }
        });
    }

    // View Profile
    document.getElementById('menuViewProfile')?.addEventListener('click', function() {
        if (Chat.selectedUserId) {
            window.open('profile.php?user_id=' + Chat.selectedUserId, '_blank');
        }
        dropdown.style.display = 'none';
    });

    // Chat Info
    document.getElementById('menuChatInfo')?.addEventListener('click', function() {
        if (Chat.selectedUserId) {
            loadChatInfo(Chat.selectedUserId);
        }
        dropdown.style.display = 'none';
    });

    // Search Messages (from dropdown)
    document.getElementById('menuSearchMessages')?.addEventListener('click', function() {
        const searchBar = document.getElementById('chatSearchBar');
        const searchInput = document.getElementById('messageSearchInput');
        if (searchBar) {
            searchBar.style.display = 'block';
            if (searchInput) searchInput.focus();
        }
        dropdown.style.display = 'none';
    });

    // Clear Chat
    document.getElementById('menuClearChat')?.addEventListener('click', async function() {
        if (!confirm('Clear all messages in this chat? This cannot be undone.')) return;
        
        const result = await ChatApp.apiRequest('/clear-chat.php', 'POST', {
            user_id: Chat.selectedUserId,
            csrf_token: CHAT_CONFIG.csrfToken
        });
        
        if (result.success) {
            document.getElementById('messagesList').innerHTML = '';
            Chat.messages = [];
            ChatApp.showToast('Chat cleared', 'success');
        } else {
            ChatApp.showToast(result.message || 'Failed to clear chat', 'error');
        }
        dropdown.style.display = 'none';
    });

    // Block User
    document.getElementById('menuBlockUser')?.addEventListener('click', async function() {
        if (!confirm('Block this user? They won\'t be able to send you messages.')) return;
        
        const result = await ChatApp.apiRequest('/block-user.php', 'POST', {
            user_id: Chat.selectedUserId,
            csrf_token: CHAT_CONFIG.csrfToken
        });
        
        if (result.success) {
            ChatApp.showToast('User blocked', 'success');
        } else {
            ChatApp.showToast(result.message || 'Failed to block user', 'error');
        }
        dropdown.style.display = 'none';
    });

    // Lock Chat
    document.getElementById('menuLockChat')?.addEventListener('click', function() {
        if (Chat.selectedUserId) {
            ChatLock.setLock(Chat.selectedUserId);
        }
        dropdown.style.display = 'none';
    });

    // Unlock Chat
    document.getElementById('menuUnlockChat')?.addEventListener('click', function() {
        if (Chat.selectedUserId) {
            ChatLock.removeLock(Chat.selectedUserId);
        }
        dropdown.style.display = 'none';
    });

    // Close chat info panel
    document.getElementById('closeChatInfo')?.addEventListener('click', function() {
        document.getElementById('chatInfoPanel').style.display = 'none';
        document.getElementById('activeChat').style.display = 'flex';
    });
}

async function loadChatInfo(userId) {
    const result = await ChatApp.apiRequest(`/chat-user-info.php?user_id=${userId}`, 'GET');
    
    if (result.success && result.data) {
        const user = result.data;
        const profileEl = document.getElementById('chatInfoProfile');
        const statsEl = document.getElementById('chatInfoStats');
        
        if (profileEl) {
            profileEl.innerHTML = `
                <div class="info-avatar">${user.avatar ? `<img src="${user.avatar}" alt="${escapeHtml(user.username)}">` : `<span class="avatar-initials">${user.username.charAt(0).toUpperCase()}</span>`}</div>
                <h3>${escapeHtml(user.username)}</h3>
                <p class="user-bio">${escapeHtml(user.bio || 'No bio')}</p>
                <p class="user-status-text">${user.is_online ? 'Online' : 'Last seen ' + user.last_seen}</p>
            `;
        }
        
        // Count messages
        const msgCount = Chat.messages.length;
        const mediaCount = Chat.messages.filter(m => m.media_id).length;
        
        if (statsEl) {
            statsEl.innerHTML = `
                <div class="info-stat-row"><span>Messages</span><strong>${msgCount}</strong></div>
                <div class="info-stat-row"><span>Media Shared</span><strong>${mediaCount}</strong></div>
                <div class="info-stat-row"><span>Chat Started</span><strong>${Chat.messages.length > 0 ? Chat.messages[0].date : 'N/A'}</strong></div>
                
                <div class="info-section">
                    <h4>Auto-Delete Messages</h4>
                    <p class="info-section-desc">Set default auto-delete for new messages in this chat</p>
                    <div class="auto-delete-settings">
                        <button class="auto-delete-setting ${Chat.autoDelete === 'view_once' ? 'active' : ''}" data-value="view_once" onclick="setChatAutoDelete('view_once')">
                            <i class="fas fa-eye"></i> View Once
                        </button>
                        <button class="auto-delete-setting ${Chat.autoDelete === '12hours' ? 'active' : ''}" data-value="12hours" onclick="setChatAutoDelete('12hours')">
                            <i class="fas fa-clock"></i> 12 Hours
                        </button>
                    </div>
                </div>
            `;
        }
        
        document.getElementById('chatInfoPanel').style.display = 'flex';
        document.getElementById('activeChat').style.display = 'none';
    }
}

function setChatAutoDelete(value) {
    Chat.autoDelete = value;
    
    // Update UI in chat info panel
    document.querySelectorAll('.auto-delete-setting').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.value === value);
    });
    
    // Update input area button
    const btn = document.getElementById('autoDeleteBtn');
    if (btn) {
        btn.classList.toggle('active-timer', value !== 'none');
    }
    
    // Update dropdown selection
    document.querySelectorAll('#autoDeleteDropdown .auto-delete-option').forEach(opt => {
        opt.classList.toggle('active', opt.dataset.value === value);
    });
    
    ChatApp.showToast('Auto-delete updated for this chat', 'success');
}

// =====================================================
// Chat List Updates
// =====================================================
function updateChatListLastMessage(userId, content) {
    const chatItem = document.querySelector(`.chat-item[data-user-id="${userId}"]`);
    if (chatItem) {
        const msgPreview = chatItem.querySelector('.chat-message');
        const timeEl = chatItem.querySelector('.chat-time');
        
        if (msgPreview) {
            msgPreview.textContent = content;
            msgPreview.classList.add('from-me');
        }
        if (timeEl) {
            timeEl.textContent = 'Just now';
        }
    }
}

// =====================================================
// Back Button (Mobile)
// =====================================================
function initializeBackButton() {
    document.getElementById('backToList')?.addEventListener('click', function() {
        var chatSidebarEl = document.getElementById('chatSidebar');
        var activeChatEl = document.getElementById('activeChat');
        var emptyChatEl = document.getElementById('emptyChat');
        if (chatSidebarEl) chatSidebarEl.classList.remove('hidden');
        if (activeChatEl) activeChatEl.style.display = 'none';
        if (emptyChatEl) emptyChatEl.style.display = 'flex';
        Chat.selectedUserId = null;
    });
    
    document.getElementById('backToDashboard')?.addEventListener('click', function() {
        window.location.href = 'dashboard.php';
    });
}

// =====================================================
// Helper Functions
// =====================================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (Chat.pollingInterval) {
        clearInterval(Chat.pollingInterval);
    }
    if (Chat.isTyping && Chat.selectedUserId) {
        updateTypingStatus(false);
    }
});
