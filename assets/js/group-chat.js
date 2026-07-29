/**
 * =====================================================
 * ChatApp - Group Chat JavaScript
 * Handles all group chat functionality
 * =====================================================
 */

// =====================================================
// Group Chat State
// =====================================================
const GroupChat = {
    currentUserId: GROUP_CONFIG.currentUserId,
    selectedGroupId: GROUP_CONFIG.selectedGroupId,
    groups: [],
    messages: [],
    groupMembers: [],
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
    autoDelete: 'none'
};

// =====================================================
// Emoji Data
// =====================================================
const EMOJIS = [
    '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂',
    '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '🤩',
    '😘', '😗', '😚', '😙', '🥲', '😋', '😛', '😜',
    '🤪', '😝', '🤑', '🤗', '🤭', '🤫', '🤔', '🫡',
    '👍', '👎', '👊', '✊', '🤛', '🤜', '👏', '🙌',
    '👐', '🤲', '🤝', '🙏', '❤️', '🧡', '💛', '💚',
    '💙', '💜', '🖤', '🤍', '💯', '💢', '💥', '💫',
    '💦', '💨', '🔥', '⭐', '🌟', '✨', '💫', '🎉'
];

// =====================================================
// Initialize Group Chat
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    initializeMessageInput();
    initializeEmojiPicker();
    initializeContextMenu();
    initializeSearch();
    initializeModals();
    initializeGroupInfo();
    initializeFileUpload();
    initializeAutoDelete();
    GroupChatLock.init();
    initializeLockedChatsBtn();
    
    loadGroupsList();
    
    if (GroupChat.selectedGroupId) {
        checkAndOpenGroupChat(GroupChat.selectedGroupId);
    }
    
    startPolling();
});

let lockedGroupsMode = false;

function initializeLockedChatsBtn() {
    const btn = document.getElementById('lockedChatsBtn');
    if (!btn) return;
    btn.addEventListener('click', function() {
        lockedGroupsMode = !lockedGroupsMode;
        const title = document.querySelector('.sidebar-header h2');
        if (lockedGroupsMode) {
            btn.classList.add('active');
            if (title) title.textContent = 'Locked Groups';
            showLockedGroups();
        } else {
            btn.classList.remove('active');
            if (title) title.textContent = 'Groups';
            loadGroupsList();
        }
    });
}

async function showLockedGroups() {
    const container = document.getElementById('groupsList');
    if (!container) return;

    container.innerHTML = `
        <div class="empty-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading locked groups...</p>
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

        if (!result.success || !result.data || !result.data.locked_chats) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-lock"></i>
                    <p>No locked groups</p>
                </div>
            `;
            return;
        }

        const locked = result.data.locked_chats.filter(c => c.chat_type === 'group');
        if (locked.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-lock"></i>
                    <p>No locked groups</p>
                </div>
            `;
            return;
        }

        container.innerHTML = locked.map(group => `
            <div class="group-item group-item-locked" 
                 data-group-id="${group.target_id}" data-locked="1"
                 onclick="handleGroupClick(${group.target_id}, true)">
                <div class="group-avatar">
                    ${renderAvatar(group.avatar, group.username)}
                </div>
                <div class="group-info">
                    <div class="group-name">
                        <span>${escapeHtml(group.username)}</span>
                        <i class="fas fa-lock chat-lock-icon"></i>
                    </div>
                    <div class="group-preview">
                        <span class="group-message"><i class="fas fa-lock"></i> Password protected</span>
                    </div>
                </div>
            </div>
        `).join('');
    } catch(e) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-lock"></i>
                <p>No locked groups</p>
            </div>
        `;
    }
}

async function checkAndOpenGroupChat(groupId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                      (typeof GROUP_CONFIG !== 'undefined' ? GROUP_CONFIG.csrfToken : '');
    const formData = new URLSearchParams();
    formData.append('action', 'check');
    formData.append('chat_type', 'group');
    formData.append('target_id', groupId);
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
            GroupChatLock.promptPassword(groupId);
        } else {
            openGroupChat(groupId);
        }
    } catch(e) {
        openGroupChat(groupId);
    }
}

// =====================================================
// Groups List Functions
// =====================================================
async function loadGroupsList() {
    const result = await ChatApp.apiRequest('/get-groups.php', 'GET');
    
    if (result.success && result.data) {
        GroupChat.groups = result.data.groups;
        renderGroupsList(result.data.groups);
    }
}

function renderGroupsList(groups) {
    const container = document.getElementById('groupsList');
    if (!container) return;
    
    if (groups.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-users-group"></i>
                <p>No groups yet</p>
                <span>Create a group to start chatting!</span>
            </div>
        `;
        return;
    }
    
    container.innerHTML = groups.map(group => createGroupListItem(group)).filter(html => html !== '').join('');
    
    if (GroupChat.selectedGroupId) {
        const activeItem = container.querySelector(`.group-item[data-group-id="${GroupChat.selectedGroupId}"]`);
        if (activeItem) activeItem.classList.add('active');
    }
}

// =====================================================
// Group Chat Lock Module
// =====================================================
const GroupChatLock = {
    csrfToken: null,
    pendingGroupId: null,
    mode: null,

    init: function() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                         (typeof GROUP_CONFIG !== 'undefined' ? GROUP_CONFIG.csrfToken : '');
    },

    openModal: function(groupId, mode) {
        this.pendingGroupId = groupId;
        this.mode = mode;
        const modal = document.getElementById('chatLockModal');
        const title = document.getElementById('chatLockModalTitle');
        const desc = document.getElementById('chatLockModalDesc');
        const input = document.getElementById('chatLockPassword');
        const btn = document.getElementById('chatLockSubmitBtn');
        if (!modal) return;

        if (mode === 'set') {
            if (title) title.innerHTML = '<i class="fas fa-lock"></i> Lock Group';
            if (desc) desc.textContent = 'Set a password to lock this group (min 8 characters)';
            if (btn) btn.innerHTML = '<i class="fas fa-lock"></i> Lock';
        } else if (mode === 'remove') {
            if (title) title.innerHTML = '<i class="fas fa-unlock"></i> Unlock Group';
            if (desc) desc.textContent = 'Enter the password to unlock this group';
            if (btn) btn.innerHTML = '<i class="fas fa-unlock"></i> Unlock';
        } else {
            if (title) title.innerHTML = '<i class="fas fa-lock"></i> Group Locked';
            if (desc) desc.textContent = 'Enter password to unlock this group';
            if (btn) btn.innerHTML = '<i class="fas fa-unlock"></i> Unlock';
        }

        if (input) { input.value = ''; input.focus(); }
        modal.classList.add('active');
    },

    closeModal: function() {
        const modal = document.getElementById('chatLockModal');
        if (modal) modal.classList.remove('active');
        this.pendingGroupId = null;
        this.mode = null;
    },

    submit: function() {
        if (this.mode === 'set') return this.doSetLock();
        if (this.mode === 'remove') return this.doRemoveLock();
        return this.doVerify();
    },

    doVerify: async function() {
        const password = document.getElementById('chatLockPassword')?.value;
        if (!password || !this.pendingGroupId) return;
        const gid = this.pendingGroupId;
        try {
            const response = await fetch('/chatapp/api/chat-lock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `action=verify&chat_type=group&target_id=${gid}&password=${encodeURIComponent(password)}`
            });
            const data = await response.json();
            if (data.verified) {
                this.closeModal();
                openGroupChat(gid);
            } else {
                showToast(data.message || 'Incorrect password', 'error');
                const input = document.getElementById('chatLockPassword');
                if (input) { input.value = ''; input.focus(); }
            }
        } catch(e) {
            showToast('Verification failed. Try again.', 'error');
        }
    },

    doSetLock: async function() {
        const password = document.getElementById('chatLockPassword')?.value;
        if (!password || !this.pendingGroupId) return;
        if (password.length < 8) {
            showToast('Password must be at least 8 characters', 'error');
            return;
        }
        const gid = this.pendingGroupId;
        try {
            const response = await fetch('/chatapp/api/chat-lock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `action=set&chat_type=group&target_id=${gid}&password=${encodeURIComponent(password)}&csrf_token=${this.csrfToken}`
            });
            const data = await response.json();
            if (data.success) {
                this.closeModal();
                showToast('Group locked', 'success');
                location.reload();
            } else {
                showToast(data.message || 'Failed to lock group', 'error');
            }
        } catch(e) {
            showToast('Failed to lock group. Try again.', 'error');
        }
    },

    doRemoveLock: async function() {
        const password = document.getElementById('chatLockPassword')?.value;
        if (!password || !this.pendingGroupId) return;
        const gid = this.pendingGroupId;
        try {
            const response = await fetch('/chatapp/api/chat-lock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                credentials: 'same-origin',
                body: `action=remove&chat_type=group&target_id=${gid}&password=${encodeURIComponent(password)}&csrf_token=${this.csrfToken}`
            });
            const data = await response.json();
            if (data.success) {
                this.closeModal();
                showToast('Group unlocked', 'success');
                location.reload();
            } else {
                showToast(data.message || 'Failed to unlock group', 'error');
            }
        } catch(e) {
            showToast('Failed to unlock group. Try again.', 'error');
        }
    },

    promptPassword: function(groupId) {
        this.openModal(groupId, 'verify');
    },

    setLock: function(groupId) {
        this.openModal(groupId, 'set');
    },

    removeLock: function(groupId) {
        this.openModal(groupId, 'remove');
    }
};

function handleGroupClick(groupId, isLocked) {
    if (isLocked === true || isLocked === 'true' || isLocked === 1 || isLocked === '1') {
        GroupChatLock.promptPassword(groupId);
    } else {
        openGroupChat(groupId);
    }
}

function createGroupListItem(group) {
    const isLocked = group.is_locked === true || group.is_locked === 1;
    
    // Locked groups completely hidden from normal list
    if (isLocked) {
        return '';
    }
    
    const lastMessage = group.last_message || 'No messages yet';
    const fromMe = group.last_message_from_me;
    const unreadBadge = group.unread_count > 0 ? 
        `<span class="unread-badge">${group.unread_count}</span>` : '';
    
    const messageClass = fromMe ? 'from-me' : '';
    
    return `
        <div class="group-item ${GroupChat.selectedGroupId == group.id ? 'active' : ''}" 
             data-group-id="${group.id}" data-locked="0"
             onclick="handleGroupClick(${group.id}, false)">
            <div class="group-avatar">${group.avatar ? `<img src="${group.avatar}" alt="${escapeHtml(group.name)}" class="user-avatar-img">` : `${group.name.charAt(0).toUpperCase()}`}</div>
            <div class="group-info">
                <div class="group-name">
                    <span>${escapeHtml(group.name)}</span>
                    <span class="group-time">${group.last_message_time || ''}</span>
                </div>
                <div class="group-preview">
                    <span class="group-message ${messageClass}">${escapeHtml(lastMessage)}</span>
                    ${unreadBadge}
                </div>
            </div>
        </div>
    `;
}


// =====================================================
// Open Group Chat
// =====================================================
async function openGroupChat(groupId) {
    GroupChat.selectedGroupId = groupId;
    GroupChat.currentPage = 1;
    GroupChat.hasMoreMessages = true;
    GroupChat.messages = [];
    
    var emptyChatEl = document.getElementById('emptyChat');
    var activeChatEl = document.getElementById('activeChat');
    if (emptyChatEl) emptyChatEl.style.display = 'none';
    if (activeChatEl) activeChatEl.style.display = 'flex';
    
    document.querySelectorAll('.group-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.groupId == groupId) {
            item.classList.add('active');
        }
    });
    
    if (window.innerWidth <= 768) {
        document.getElementById('chatSidebar').classList.add('hidden');
    }
    
    await loadGroupInfo(groupId);
    await loadGroupMessages(groupId);
    scrollToBottom();
    markGroupMessagesRead(groupId);
    checkGroupLockStatus(groupId);
}

async function checkGroupLockStatus(groupId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                      (typeof GROUP_CONFIG !== 'undefined' ? GROUP_CONFIG.csrfToken : '');
    try {
        const response = await fetch('/chatapp/api/chat-lock.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            credentials: 'same-origin',
            body: `action=check&chat_type=group&target_id=${groupId}&csrf_token=${csrfToken}`
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

async function markGroupMessagesRead(groupId) {
    try {
        await ChatApp.apiRequest('/mark-group-read.php', 'POST', {
            group_id: groupId,
            csrf_token: GROUP_CONFIG.csrfToken
        });
    } catch (e) {
        // Silent fail - not critical
    }
}

async function loadGroupInfo(groupId) {
    const result = await ChatApp.apiRequest(`/group-info.php?group_id=${groupId}`, 'GET');
    
    if (result.success && result.data) {
        const group = result.data.group;
        GroupChat.groupMembers = result.data.members;
        
        var chatAvatarEl = document.getElementById('chatAvatar');
        var chatGroupNameEl = document.getElementById('chatGroupName');
        var chatGroupMembersEl = document.getElementById('chatGroupMembers');
        if (chatAvatarEl) {
            var avatarParent = chatAvatarEl.parentNode;
            if (group.avatar) {
                avatarParent.innerHTML = '<img src="' + group.avatar + '" alt="' + escapeHtml(group.name) + '" class="user-avatar-img">';
            } else {
                chatAvatarEl.textContent = group.name.charAt(0).toUpperCase();
            }
        }
        if (chatGroupNameEl) chatGroupNameEl.textContent = group.name;
        if (chatGroupMembersEl) chatGroupMembersEl.textContent = `${group.member_count} members`;
    }
}

// =====================================================
// Group Messages Functions
// =====================================================
async function loadGroupMessages(groupId, page = 1, append = false) {
    if (GroupChat.isLoadingMessages) return;
    GroupChat.isLoadingMessages = true;
    
    const result = await ChatApp.apiRequest(
        `/get-group-messages.php?group_id=${groupId}&page=${page}`,
        'GET'
    );
    
    GroupChat.isLoadingMessages = false;
    
    if (result.success && result.data) {
        const messages = result.data.messages;
        GroupChat.hasMoreMessages = result.data.has_more;
        
        if (result.data.members) {
            GroupChat.groupMembers = result.data.members;
        }
        
        if (append) {
            GroupChat.messages = [...messages, ...GroupChat.messages];
            renderGroupMessages(messages, true);
        } else {
            GroupChat.messages = messages;
            renderGroupMessages(messages, false);
        }
        
        updateLoadMoreButton();
    }
}

function renderGroupMessages(messages, prepend = false) {
    const container = document.getElementById('messagesList');
    if (!container) return;
    
    const html = messages.map(msg => createGroupMessageBubble(msg)).join('');
    
    if (prepend) {
        const scrollHeight = document.getElementById('messagesContainer').scrollHeight;
        container.insertAdjacentHTML('afterbegin', html);
        document.getElementById('messagesContainer').scrollTop = 
            document.getElementById('messagesContainer').scrollHeight - scrollHeight;
    } else {
        container.innerHTML = html;
    }
}

function createGroupMessageBubble(msg) {
    const isSender = msg.is_sender;
    const messageClass = isSender ? 'sent' : 'received';
    
    if (msg.is_deleted) {
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
                    <div class="message-sender-name">${escapeHtml(msg.sender_name)}</div>
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
    
    let replyHtml = '';
    if (msg.reply_to) {
        replyHtml = `
            <div class="message-reply">
                <div class="reply-sender">${escapeHtml(msg.reply_to.sender_name)}</div>
                <div class="reply-text">${escapeHtml(msg.reply_to.content)}</div>
            </div>
        `;
    }
    
    const senderHtml = !isSender ? 
        `<div class="message-sender-name">${escapeHtml(msg.sender_name)}</div>` : '';
    
    const readHtml = isSender ? 
        `<div class="message-read-status">${msg.read_count} read</div>` : '';
    
    // Media content
    let mediaHtml = '';
    if (msg.media_id && msg.media) {
        const media = msg.media;
        if (media.is_image) {
            mediaHtml = `<div class="message-media"><img src="/chatapp/api/preview-media.php?id=${media.id}" alt="${escapeHtml(media.original_name)}" loading="lazy"></div>`;
        } else if (media.is_video) {
            mediaHtml = `<div class="message-media"><video controls preload="metadata"><source src="/chatapp/api/preview-media.php?id=${media.id}" type="${escapeHtml(media.file_type)}"></video></div>`;
        } else {
            const iconMap = { pdf: 'fa-file-pdf', doc: 'fa-file-word', docx: 'fa-file-word', xls: 'fa-file-excel', xlsx: 'fa-file-excel', ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint', txt: 'fa-file-alt', zip: 'fa-file-archive', rar: 'fa-file-archive', '7z': 'fa-file-archive' };
            const ext = media.file_extension || '';
            const iconClass = iconMap[ext] || 'fa-file';
            mediaHtml = `
                <div class="message-media document-attachment">
                    <div class="doc-icon"><i class="fas ${iconClass}"></i></div>
                    <div class="doc-info">
                        <div class="doc-name">${escapeHtml(media.original_name)}</div>
                        <div class="doc-size">${media.file_size_formatted || ''}</div>
                    </div>
                </div>
            `;
        }
    }
    
    return `
        <div class="message ${messageClass}" data-message-id="${msg.id}">
            <div class="message-bubble" oncontextmenu="showContextMenu(event, ${msg.id})" onclick="hideContextMenu()">
                ${senderHtml}
                ${replyHtml}
                ${mediaHtml}
                ${msg.content ? `<div class="message-text">${formatMessageContent(msg.content)}</div>` : ''}
                <div class="message-meta">
                    <span class="message-time">${msg.timestamp}</span>
                    ${readHtml}
                </div>
            </div>
        </div>
    `;
}

function formatMessageContent(content) {
    let formatted = escapeHtml(content);
    
    // Convert URLs to links
    const urlRegex = /(https?:\/\/[^\s]+)/g;
    formatted = formatted.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
    
    // Convert @mentions
    const mentionRegex = /@(\w+)/g;
    formatted = formatted.replace(mentionRegex, '<span class="mention">@$1</span>');
    
    return formatted;
}

function updateLoadMoreButton() {
    const loadMore = document.getElementById('loadMore');
    if (loadMore) {
        loadMore.style.display = GroupChat.hasMoreMessages ? 'block' : 'none';
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
                GroupChat.autoDelete = this.dataset.value;
                
                if (GroupChat.autoDelete !== 'none') {
                    btn.classList.add('active-timer');
                } else {
                    btn.classList.remove('active-timer');
                }
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
                GroupChat.selectedFile = this.files[0];
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
                GroupChat.selectedFile = e.dataTransfer.files[0];
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
    GroupChat.selectedFile = null;
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

function removeUploadPlaceholder(uploadId) {
    const el = document.querySelector(`.message[data-message-id="${uploadId}"]`);
    if (el) el.remove();
}

// =====================================================
// Message Input
// =====================================================
function initializeMessageInput() {
    const input = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    
    if (input) {
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            sendBtn.disabled = !this.value.trim();
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendGroupMessage();
            }
        });
    }
    
    if (sendBtn) {
        sendBtn.addEventListener('click', sendGroupMessage);
    }
    
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            if (GroupChat.hasMoreMessages && !GroupChat.isLoadingMessages) {
                GroupChat.currentPage++;
                loadGroupMessages(GroupChat.selectedGroupId, GroupChat.currentPage, true);
            }
        });
    }
    
    document.getElementById('closeReply')?.addEventListener('click', cancelReply);
}

async function sendGroupMessage() {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    const file = GroupChat.selectedFile;

    if (!content && !file) return;
    if (!GroupChat.selectedGroupId) return;

    const hasFile = !!file;

    input.value = '';
    input.style.height = 'auto';
    document.getElementById('sendBtn').disabled = true;
    clearFileSelection();
    cancelReply();

    if (hasFile) {
        const uploadId = 'upload-' + Date.now();
        appendGroupMessage({
            id: uploadId,
            content: content || file.name,
            message_type: 'uploading',
            is_sender: true,
            is_deleted: false,
            sender_name: 'You',
            timestamp: new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }),
            upload_progress: 0
        });
        scrollToBottom();

        const formData = new FormData();
        formData.append('file', file);
        formData.append('group_id', GroupChat.selectedGroupId);
        formData.append('message', content);
        formData.append('csrf_token', GROUP_CONFIG.csrfToken);
        if (GroupChat.replyToMessage) {
            formData.append('reply_to', GroupChat.replyToMessage.id);
        }

        try {
            const result = await ChatApp.apiRequest('/send-message-media.php', 'POST', formData, true);
            if (result.success && result.data) {
                removeUploadPlaceholder(uploadId);
                appendGroupMessage(result.data.message);
                updateGroupListLastMessage(GroupChat.selectedGroupId, content || file.name);
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
            group_id: GroupChat.selectedGroupId,
            content: content,
            auto_delete: GroupChat.autoDelete,
            csrf_token: GROUP_CONFIG.csrfToken
        };

        if (GroupChat.replyToMessage) {
            messageData.reply_to_id = GroupChat.replyToMessage.id;
        }

        const result = await ChatApp.apiRequest('/send-group-message.php', 'POST', messageData);

        if (result.success && result.data) {
            appendGroupMessage(result.data.message);
            updateGroupListLastMessage(GroupChat.selectedGroupId, content);
        } else {
            input.value = content;
            ChatApp.showToast(result.message || 'Failed to send message', 'error');
        }
    }

    scrollToBottom();
}

function appendGroupMessage(msg) {
    const container = document.getElementById('messagesList');
    if (!container) return;
    
    const html = createGroupMessageBubble(msg);
    container.insertAdjacentHTML('beforeend', html);
    GroupChat.messages.push(msg);
}

// =====================================================
// Reply Functions
// =====================================================
function replyToMessage(messageId) {
    const message = GroupChat.messages.find(m => m.id === messageId);
    if (!message) return;
    
    GroupChat.replyToMessage = message;
    
    const preview = document.getElementById('replyPreview');
    var replyToEl = document.getElementById('replyTo');
    var replyTextEl = document.getElementById('replyText');
    if (replyToEl) replyToEl.textContent = `Replying to ${message.sender_name}`;
    if (replyTextEl) replyTextEl.textContent = message.content;
    if (preview) preview.style.display = 'block';
    
    document.getElementById('messageInput').focus();
    hideContextMenu();
}

function cancelReply() {
    GroupChat.replyToMessage = null;
    document.getElementById('replyPreview').style.display = 'none';
}

// =====================================================
// Delete Functions
// =====================================================
async function deleteGroupMessage(deleteType) {
    if (!GroupChat.selectedMessageId) return;
    
    const result = await ChatApp.apiRequest('/delete-group-message.php', 'POST', {
        message_id: GroupChat.selectedMessageId,
        delete_type: deleteType,
        csrf_token: GROUP_CONFIG.csrfToken
    });
    
    if (result.success) {
        const msgElement = document.querySelector(`.message[data-message-id="${GroupChat.selectedMessageId}"]`);
        if (msgElement) {
            const textEl = msgElement.querySelector('.message-text');
            if (textEl) {
                textEl.textContent = 'This message was deleted';
                textEl.classList.add('deleted');
            }
        }
        ChatApp.showToast(result.message, 'success');
    } else {
        ChatApp.showToast(result.message || 'Failed to delete', 'error');
    }
    
    hideContextMenu();
}

// =====================================================
// Context Menu
// =====================================================
function initializeContextMenu() {
    document.addEventListener('click', hideContextMenu);
    
    document.getElementById('replyMenuItem')?.addEventListener('click', function() {
        if (GroupChat.selectedMessageId) {
            replyToMessage(GroupChat.selectedMessageId);
        }
    });
    
    document.getElementById('copyMenuItem')?.addEventListener('click', function() {
        if (GroupChat.selectedMessageId) {
            const message = GroupChat.messages.find(m => m.id === GroupChat.selectedMessageId);
            if (message) {
                navigator.clipboard.writeText(message.content);
                ChatApp.showToast('Message copied', 'success');
            }
        }
        hideContextMenu();
    });
    
    document.getElementById('deleteMenuItem')?.addEventListener('click', function() {
        deleteGroupMessage('for_me');
    });
}

function showContextMenu(event, messageId) {
    event.preventDefault();
    event.stopPropagation();
    
    GroupChat.selectedMessageId = messageId;
    
    const menu = document.getElementById('contextMenu');
    menu.style.display = 'block';
    
    const x = Math.min(event.clientX, window.innerWidth - 200);
    const y = Math.min(event.clientY, window.innerHeight - 150);
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
    
    if (emojiGrid) {
        emojiGrid.innerHTML = EMOJIS.map(emoji => 
            `<button class="emoji-item" onclick="insertEmoji('${emoji}')">${emoji}</button>`
        ).join('');
    }
    
    if (emojiBtn) {
        emojiBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            emojiPicker.style.display = emojiPicker.style.display === 'none' ? 'block' : 'none';
        });
    }
    
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
    
    searchBtn?.addEventListener('click', function() {
        searchBar.style.display = searchBar.style.display === 'none' ? 'block' : 'none';
        if (searchBar.style.display === 'block') searchInput.focus();
    });
    
    closeSearch?.addEventListener('click', function() {
        searchBar.style.display = 'none';
        searchInput.value = '';
        document.getElementById('searchResultsCount').textContent = '';
        loadGroupMessages(GroupChat.selectedGroupId);
    });
    
    searchInput?.addEventListener('input', function() {
        clearTimeout(GroupChat.searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            document.getElementById('searchResultsCount').textContent = '';
            loadGroupMessages(GroupChat.selectedGroupId);
            return;
        }
        
        GroupChat.searchTimeout = setTimeout(() => {
            searchGroupMessages(query);
        }, 300);
    });
}

async function searchGroupMessages(query) {
    const result = await ChatApp.apiRequest(
        `/search-messages.php?group_id=${GroupChat.selectedGroupId}&q=${encodeURIComponent(query)}`,
        'GET'
    );
    
    if (result.success && result.data) {
        const messages = result.data.messages;
        document.getElementById('searchResultsCount').textContent = 
            `${messages.length} message${messages.length !== 1 ? 's' : ''} found`;
        
        const container = document.getElementById('messagesList');
        container.innerHTML = messages.map(msg => {
            const highlight = highlightText(msg.content, query);
            return `
                <div class="message ${msg.is_sender ? 'sent' : 'received'}" data-message-id="${msg.id}">
                    <div class="message-bubble">
                        <div class="message-sender-name">${escapeHtml(msg.sender_name)}</div>
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
// Group Info Panel & Menu
// =====================================================
function initializeGroupInfo() {
    const closeBtn = document.getElementById('closeGroupInfo');
    const panel = document.getElementById('groupInfoPanel');
    
    closeBtn?.addEventListener('click', function() {
        panel.style.display = 'none';
    });
    
    // 3-dots dropdown menu
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
    
    // Menu items
    document.getElementById('menuGroupInfo')?.addEventListener('click', function() {
        if (GroupChat.selectedGroupId) {
            panel.style.display = 'flex';
            renderGroupInfo();
        }
        dropdown.style.display = 'none';
    });
    
    document.getElementById('menuSearchMessages')?.addEventListener('click', function() {
        const searchBar = document.getElementById('chatSearchBar');
        const searchInput = document.getElementById('messageSearchInput');
        if (searchBar) {
            searchBar.style.display = 'block';
            if (searchInput) searchInput.focus();
        }
        dropdown.style.display = 'none';
    });
    
    // Lock Chat
    document.getElementById('menuLockChat')?.addEventListener('click', function() {
        if (GroupChat.selectedGroupId) {
            GroupChatLock.setLock(GroupChat.selectedGroupId);
        }
        dropdown.style.display = 'none';
    });

    // Unlock Chat
    document.getElementById('menuUnlockChat')?.addEventListener('click', function() {
        if (GroupChat.selectedGroupId) {
            GroupChatLock.removeLock(GroupChat.selectedGroupId);
        }
        dropdown.style.display = 'none';
    });
    
    document.getElementById('menuLeaveGroup')?.addEventListener('click', async function() {
        if (!confirm('Are you sure you want to leave this group?')) return;
        
        const result = await ChatApp.apiRequest('/leave-group.php', 'POST', {
            group_id: GroupChat.selectedGroupId,
            csrf_token: GROUP_CONFIG.csrfToken
        });
        
        if (result.success) {
            ChatApp.showToast('Left group', 'success');
            window.location.href = 'group-chat.php';
        } else {
            ChatApp.showToast(result.message || 'Failed to leave group', 'error');
        }
        dropdown.style.display = 'none';
    });
}

async function renderGroupInfo() {
    if (!GroupChat.selectedGroupId) return;
    
    const result = await ChatApp.apiRequest(`/group-info.php?group_id=${GroupChat.selectedGroupId}`, 'GET');
    
    if (result.success && result.data) {
        const { group, members, notifications } = result.data;
        
        const content = document.getElementById('groupInfoContent');
        content.innerHTML = `
            <div class="group-details">
                <div class="group-avatar group-avatar-lg">${group.avatar ? `<img src="${group.avatar}" alt="${escapeHtml(group.name)}" class="user-avatar-img large">` : `${group.name.charAt(0).toUpperCase()}`}</div>
                <div class="group-name">${escapeHtml(group.name)}</div>
                <div class="group-description">${group.description ? escapeHtml(group.description) : 'No description'}</div>
                <div class="group-meta">Created by ${escapeHtml(group.creator_name)} on ${group.created_at}</div>
            </div>
            
            <div class="members-section">
                <h4>Members (${members.length})</h4>
                ${members.map(m => createMemberItem(m, group.my_role)).join('')}
            </div>
            
            <div class="notifications-section">
                <h4>Recent Activity</h4>
                ${notifications.length > 0 ? notifications.map(n => `
                    <div class="notification-item ${!n.is_read ? 'unread' : ''}">
                        <i class="fas ${getNotificationIcon(n.type)}"></i>
                        <span>${escapeHtml(n.message)}</span>
                    </div>
                `).join('') : '<div class="notification-item">No recent activity</div>'}
            </div>
            
            <div class="group-actions">
                <button class="btn btn-outline-primary" onclick="showInviteMembersModal()">
                    <i class="fas fa-user-plus"></i> Invite Members
                </button>
                <button class="btn btn-outline-danger" onclick="leaveGroup(${group.id})">
                    <i class="fas fa-sign-out-alt"></i> Leave Group
                </button>
            </div>
        `;
    }
}

function createMemberItem(member, myRole) {
    const canManage = myRole === 'admin' || (myRole === 'moderator' && member.role === 'member');
    const statusDot = member.is_online ? 'online' : 'offline';
    
    let roleBadge = '';
    if (member.role === 'admin') {
        roleBadge = '<span class="member-role admin">Admin</span>';
    } else if (member.role === 'moderator') {
        roleBadge = '<span class="member-role moderator">Mod</span>';
    }
    
    let actionsHtml = '';
    if (canManage && member.user_id !== GROUP_CONFIG.currentUserId) {
        actionsHtml = `
            <div class="member-actions">
                <button onclick="changeRole(${member.user_id}, 'moderator')" title="Make Moderator">
                    <i class="fas fa-shield-alt"></i>
                </button>
                <button onclick="removeMember(${member.user_id})" title="Remove" class="btn-danger">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    }
    
    return `
        <div class="member-item">
            <div class="member-avatar">
                ${renderAvatar(member.avatar, member.username)}
                <span class="status-dot ${statusDot}"></span>
            </div>
            <div class="member-info">
                <div class="member-name">
                    ${escapeHtml(member.username)} ${roleBadge}
                </div>
                <div class="member-status">${member.is_online ? 'Online' : member.last_seen}</div>
            </div>
            ${actionsHtml}
        </div>
    `;
}

function getNotificationIcon(type) {
    const icons = {
        'member_joined': 'fa-user-plus',
        'member_left': 'fa-user-minus',
        'member_removed': 'fa-user-times',
        'role_changed': 'fa-shield-alt',
        'new_message': 'fa-comment'
    };
    return icons[type] || 'fa-info-circle';
}

// =====================================================
// Group Management Functions
// =====================================================
async function leaveGroup(groupId) {
    if (!confirm('Are you sure you want to leave this group?')) return;
    
    const result = await ChatApp.apiRequest('/leave-group.php', 'POST', {
        group_id: groupId,
        csrf_token: GROUP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        GroupChat.selectedGroupId = null;
        var activeChatEl = document.getElementById('activeChat');
        var emptyChatEl = document.getElementById('emptyChat');
        if (activeChatEl) activeChatEl.style.display = 'none';
        if (emptyChatEl) emptyChatEl.style.display = 'flex';
        loadGroupsList();
    } else {
        ChatApp.showToast(result.message || 'Failed to leave group', 'error');
    }
}

async function removeMember(memberId) {
    if (!confirm('Are you sure you want to remove this member?')) return;
    
    const result = await ChatApp.apiRequest('/remove-member.php', 'POST', {
        group_id: GroupChat.selectedGroupId,
        member_id: memberId,
        csrf_token: GROUP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        renderGroupInfo();
    } else {
        ChatApp.showToast(result.message || 'Failed to remove member', 'error');
    }
}

async function changeRole(memberId, newRole) {
    const result = await ChatApp.apiRequest('/update-role.php', 'POST', {
        group_id: GroupChat.selectedGroupId,
        member_id: memberId,
        new_role: newRole,
        csrf_token: GROUP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        renderGroupInfo();
    } else {
        ChatApp.showToast(result.message || 'Failed to change role', 'error');
    }
}

// =====================================================
// Modals
// =====================================================
function initializeModals() {
    document.getElementById('createGroupBtn')?.addEventListener('click', showCreateGroupModal);
    document.getElementById('createGroupBtnEmpty')?.addEventListener('click', showCreateGroupModal);
    document.getElementById('createGroupSubmit')?.addEventListener('click', submitCreateGroup);
    document.getElementById('inviteMembersSubmit')?.addEventListener('click', submitInviteMembers);
}

async function showCreateGroupModal() {
    // Load friends list
    const result = await ChatApp.apiRequest('/get-friends.php', 'GET');
    
    if (result.success && result.data) {
        const list = document.getElementById('friendsCheckboxList');
        list.innerHTML = result.data.friends.map(friend => `
            <label class="friend-checkbox-item">
                <input type="checkbox" name="members[]" value="${friend.id}">
                ${renderAvatar(friend.avatar, friend.username)}
                <div class="friend-info">
                    <div class="friend-name">${escapeHtml(friend.username)}</div>
                    <div class="friend-code">${escapeHtml(friend.friend_code)}</div>
                </div>
            </label>
        `).join('');
    }
    
    const modal = new bootstrap.Modal(document.getElementById('createGroupModal'));
    modal.show();
}

async function submitCreateGroup() {
    const name = document.getElementById('groupName').value.trim();
    const description = document.getElementById('groupDescription').value.trim();
    
    if (!name) {
        ChatApp.showToast('Please enter a group name', 'error');
        return;
    }
    
    const checkboxes = document.querySelectorAll('#friendsCheckboxList input[type="checkbox"]:checked');
    const members = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    const result = await ChatApp.apiRequest('/create-group.php', 'POST', {
        name: name,
        description: description,
        members: members,
        csrf_token: GROUP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('createGroupModal')).hide();
        loadGroupsList();
        
        if (result.data.group_id) {
            openGroupChat(result.data.group_id);
        }
    } else {
        ChatApp.showToast(result.message || 'Failed to create group', 'error');
    }
}

async function showInviteMembersModal() {
    const result = await ChatApp.apiRequest('/get-friends.php', 'GET');
    
    if (result.success && result.data) {
        const currentMembers = GroupChat.groupMembers.map(m => m.user_id);
        const availableFriends = result.data.friends.filter(f => !currentMembers.includes(f.id));
        
        const list = document.getElementById('inviteFriendsList');
        if (availableFriends.length === 0) {
            list.innerHTML = '<div class="empty-state"><p>All friends are already in this group</p></div>';
        } else {
            list.innerHTML = availableFriends.map(friend => `
                <label class="friend-checkbox-item">
                    <input type="checkbox" name="invite_members[]" value="${friend.id}">
                    ${renderAvatar(friend.avatar, friend.username)}
                    <div class="friend-info">
                        <div class="friend-name">${escapeHtml(friend.username)}</div>
                    </div>
                </label>
            `).join('');
        }
    }
    
    const modal = new bootstrap.Modal(document.getElementById('inviteMembersModal'));
    modal.show();
}

async function submitInviteMembers() {
    const checkboxes = document.querySelectorAll('#inviteFriendsList input[type="checkbox"]:checked');
    const memberIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (memberIds.length === 0) {
        ChatApp.showToast('Please select at least one member', 'error');
        return;
    }
    
    const result = await ChatApp.apiRequest('/invite-members.php', 'POST', {
        group_id: GroupChat.selectedGroupId,
        member_ids: memberIds,
        csrf_token: GROUP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        bootstrap.Modal.getInstance(document.getElementById('inviteMembersModal')).hide();
        loadGroupInfo(GroupChat.selectedGroupId);
        renderGroupInfo();
    } else {
        ChatApp.showToast(result.message || 'Failed to invite members', 'error');
    }
}

// =====================================================
// Polling
// =====================================================
function startPolling() {
    GroupChat.pollingInterval = setInterval(async () => {
        if (!GroupChat.selectedGroupId) return;
        await refreshGroupMessages();
        await refreshGroupsList();
    }, 2000);
}

async function refreshGroupMessages() {
    if (GroupChat.isLoadingMessages) return;
    
    const lastMessageId = GroupChat.messages.length > 0 ? 
        GroupChat.messages[GroupChat.messages.length - 1].id : 0;
    
    const result = await ChatApp.apiRequest(
        `/get-group-messages.php?group_id=${GroupChat.selectedGroupId}&page=1`,
        'GET'
    );
    
    if (result.success && result.data) {
        const newMessages = result.data.messages;
        const existingIds = new Set(GroupChat.messages.map(m => m.id));
        const trulyNew = newMessages.filter(m => !existingIds.has(m.id));
        
        if (trulyNew.length > 0) {
            const container = document.getElementById('messagesContainer');
            const isAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 50;
            
            trulyNew.forEach(msg => appendGroupMessage(msg));
            
            if (isAtBottom) scrollToBottom();
        }
    }
}

async function refreshGroupsList() {
    const result = await ChatApp.apiRequest('/get-groups.php', 'GET');
    if (result.success && result.data) {
        GroupChat.groups = result.data.groups;
        renderGroupsList(result.data.groups);
    }
}

// =====================================================
// Helper Functions
// =====================================================
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 50);
    }
}

function clearUnreadBadge(groupId) {
    const groupItem = document.querySelector(`.group-item[data-group-id="${groupId}"]`);
    if (groupItem) {
        const badge = groupItem.querySelector('.unread-badge');
        if (badge) badge.remove();
    }
}

function updateGroupListLastMessage(groupId, content) {
    const groupItem = document.querySelector(`.group-item[data-group-id="${groupId}"]`);
    if (groupItem) {
        const msgPreview = groupItem.querySelector('.group-message');
        const timeEl = groupItem.querySelector('.group-time');
        
        if (msgPreview) {
            msgPreview.textContent = content;
            msgPreview.classList.add('from-me');
        }
        if (timeEl) {
            timeEl.textContent = 'Just now';
        }
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize back buttons
document.getElementById('backToList')?.addEventListener('click', function() {
    var chatSidebarEl = document.getElementById('chatSidebar');
    var activeChatEl = document.getElementById('activeChat');
    var emptyChatEl = document.getElementById('emptyChat');
    if (chatSidebarEl) chatSidebarEl.classList.remove('hidden');
    if (activeChatEl) activeChatEl.style.display = 'none';
    if (emptyChatEl) emptyChatEl.style.display = 'flex';
    GroupChat.selectedGroupId = null;
});

document.getElementById('backToDashboard')?.addEventListener('click', function() {
    window.location.href = 'dashboard.php';
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (GroupChat.pollingInterval) {
        clearInterval(GroupChat.pollingInterval);
    }
});
