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
    searchTimeout: null
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
    initializeGroupsList();
    initializeMessageInput();
    initializeEmojiPicker();
    initializeContextMenu();
    initializeSearch();
    initializeModals();
    initializeGroupInfo();
    
    loadGroupsList();
    
    if (GroupChat.selectedGroupId) {
        openGroupChat(GroupChat.selectedGroupId);
    }
    
    startPolling();
});

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
    
    container.innerHTML = groups.map(group => createGroupListItem(group)).join('');
    
    if (GroupChat.selectedGroupId) {
        const activeItem = container.querySelector(`.group-item[data-group-id="${GroupChat.selectedGroupId}"]`);
        if (activeItem) activeItem.classList.add('active');
    }
}

function createGroupListItem(group) {
    const lastMessage = group.last_message || 'No messages yet';
    const fromMe = group.last_message_from_me;
    const unreadBadge = group.unread_count > 0 ? 
        `<span class="unread-badge">${group.unread_count}</span>` : '';
    
    const messageClass = fromMe ? 'from-me' : '';
    
    return `
        <div class="group-item ${GroupChat.selectedGroupId == group.id ? 'active' : ''}" 
             data-group-id="${group.id}" onclick="openGroupChat(${group.id})">
            <div class="group-avatar">${group.name.charAt(0).toUpperCase()}</div>
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
    
    document.getElementById('emptyChat').style.display = 'none';
    document.getElementById('activeChat').style.display = 'flex';
    
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
    await markGroupMessagesRead(groupId);
    clearUnreadBadge(groupId);
}

async function loadGroupInfo(groupId) {
    const result = await ChatApp.apiRequest(`/group-info.php?group_id=${groupId}`, 'GET');
    
    if (result.success && result.data) {
        const group = result.data.group;
        GroupChat.groupMembers = result.data.members;
        
        document.getElementById('chatAvatar').textContent = group.name.charAt(0).toUpperCase();
        document.getElementById('chatGroupName').textContent = group.name;
        document.getElementById('chatGroupMembers').textContent = `${group.member_count} members`;
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
    
    return `
        <div class="message ${messageClass}" data-message-id="${msg.id}">
            <div class="message-bubble" oncontextmenu="showContextMenu(event, ${msg.id})" onclick="hideContextMenu()">
                ${senderHtml}
                ${replyHtml}
                <div class="message-text">${formatMessageContent(msg.content)}</div>
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
    
    if (!content || !GroupChat.selectedGroupId) return;
    
    const messageData = {
        group_id: GroupChat.selectedGroupId,
        content: content,
        csrf_token: GROUP_CONFIG.csrfToken
    };
    
    if (GroupChat.replyToMessage) {
        messageData.reply_to_id = GroupChat.replyToMessage.id;
    }
    
    input.value = '';
    input.style.height = 'auto';
    document.getElementById('sendBtn').disabled = true;
    cancelReply();
    
    const result = await ChatApp.apiRequest('/send-group-message.php', 'POST', messageData);
    
    if (result.success && result.data) {
        appendGroupMessage(result.data.message);
        updateGroupListLastMessage(GroupChat.selectedGroupId, content);
        scrollToBottom();
    } else {
        input.value = content;
        ChatApp.showToast(result.message || 'Failed to send message', 'error');
    }
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
    document.getElementById('replyTo').textContent = `Replying to ${message.sender_name}`;
    document.getElementById('replyText').textContent = message.content;
    preview.style.display = 'block';
    
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
    document.getElementById('contextMenu').style.display = 'none';
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
// Group Info Panel
// =====================================================
function initializeGroupInfo() {
    const infoBtn = document.getElementById('groupInfoBtn');
    const closeBtn = document.getElementById('closeGroupInfo');
    const panel = document.getElementById('groupInfoPanel');
    
    infoBtn?.addEventListener('click', function() {
        panel.style.display = panel.style.display === 'none' ? 'flex' : 'none';
        if (panel.style.display === 'flex') {
            renderGroupInfo();
        }
    });
    
    closeBtn?.addEventListener('click', function() {
        panel.style.display = 'none';
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
                <div class="user-avatar group-avatar group-avatar-lg">${group.name.charAt(0).toUpperCase()}</div>
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
            <div class="user-avatar member-avatar">
                ${member.username.charAt(0).toUpperCase()}
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
        document.getElementById('activeChat').style.display = 'none';
        document.getElementById('emptyChat').style.display = 'flex';
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
                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                    ${friend.username.charAt(0).toUpperCase()}
                </div>
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
                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.8rem;">
                        ${friend.username.charAt(0).toUpperCase()}
                    </div>
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
    document.getElementById('chatSidebar').classList.remove('hidden');
    document.getElementById('activeChat').style.display = 'none';
    document.getElementById('emptyChat').style.display = 'flex';
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
