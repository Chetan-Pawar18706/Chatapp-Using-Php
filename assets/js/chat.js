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
    
    // Load chat list
    loadChatList();
    
    // If a user is selected, open chat with them
    if (Chat.selectedUserId) {
        openChat(Chat.selectedUserId);
    }
    
    // Start polling
    startPolling();
});

// =====================================================
// Chat List Functions
// =====================================================
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
    const lastMessage = chat.last_message || 'No messages yet';
    const fromMe = chat.last_message_from_me;
    const unreadBadge = chat.unread_count > 0 ? 
        `<span class="unread-badge">${chat.unread_count}</span>` : '';
    
    const messageClass = fromMe ? 'from-me' : '';
    const timeClass = chat.unread_count > 0 ? ' style="color: var(--primary-light);"' : '';
    
    return `
        <div class="chat-item ${Chat.selectedUserId == chat.user_id ? 'active' : ''}" 
             data-user-id="${chat.user_id}" onclick="openChat(${chat.user_id})">
            <div class="chat-avatar">
                <div class="user-avatar">${chat.username.charAt(0).toUpperCase()}</div>
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
// Open Chat
// =====================================================
async function openChat(userId) {
    Chat.selectedUserId = userId;
    Chat.currentPage = 1;
    Chat.hasMoreMessages = true;
    Chat.messages = [];
    
    // Update UI
    document.getElementById('emptyChat').style.display = 'none';
    document.getElementById('activeChat').style.display = 'flex';
    
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
}

async function loadChatUserInfo(userId) {
    const result = await ChatApp.apiRequest(`/chat-user-info.php?user_id=${userId}`, 'GET');
    
    if (result.success && result.data) {
        const user = result.data;
        
        document.getElementById('chatAvatar').textContent = user.username.charAt(0).toUpperCase();
        document.getElementById('chatUsername').textContent = user.username;
        
        const statusDot = document.getElementById('chatStatusDot');
        statusDot.className = `status-dot ${user.is_online ? 'online' : 'offline'}`;
        
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
    
    // Message status icon
    let statusHtml = '';
    if (isSender && msg.status) {
        const statusIcon = msg.status === 'seen' ? 'fa-check-double' : 
                          msg.status === 'delivered' ? 'fa-check-double' : 'fa-check';
        statusHtml = `<i class="fas ${statusIcon} message-status ${msg.status}"></i>`;
    }
    
    return `
        <div class="message ${messageClass}" data-message-id="${msg.id}">
            <div class="message-bubble" oncontextmenu="showContextMenu(event, ${msg.id})" onclick="hideContextMenu()">
                ${replyHtml}
                <div class="message-text">${formatMessageContent(msg.content)}</div>
                <div class="message-meta">
                    <span class="message-time">${msg.timestamp}</span>
                    ${statusHtml}
                </div>
            </div>
        </div>
    `;
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

async function sendMessage() {
    const input = document.getElementById('messageInput');
    const content = input.value.trim();
    
    if (!content || !Chat.selectedUserId) return;
    
    const messageData = {
        receiver_id: Chat.selectedUserId,
        content: content,
        message_type: 'text',
        csrf_token: CHAT_CONFIG.csrfToken
    };
    
    // Add reply_to if set
    if (Chat.replyToMessage) {
        messageData.reply_to_id = Chat.replyToMessage.id;
    }
    
    // Clear input immediately
    input.value = '';
    input.style.height = 'auto';
    document.getElementById('sendBtn').disabled = true;
    
    // Cancel reply
    cancelReply();
    
    // Send request
    const result = await ChatApp.apiRequest('/send-message.php', 'POST', messageData);
    
    if (result.success && result.data) {
        // Add message to UI
        appendMessage(result.data.message);
        
        // Update chat list
        updateChatListLastMessage(Chat.selectedUserId, content);
        
        // Scroll to bottom
        scrollToBottom();
    } else {
        // Show error and restore input
        input.value = content;
        ChatApp.showToast(result.message || 'Failed to send message', 'error');
    }
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
    document.getElementById('replyPreview').style.display = 'none';
}

// =====================================================
// Delete Functions
// =====================================================
function showDeleteOptions(messageId) {
    Chat.selectedMessageId = messageId;
    
    const message = Chat.messages.find(m => m.id === messageId);
    if (!message) return;
    
    const deleteForEveryone = document.getElementById('deleteForEveryoneMenuItem');
    
    // Only show "delete for everyone" for own messages within 7 days
    if (message.is_sender) {
        deleteForEveryone.style.display = 'block';
    } else {
        deleteForEveryone.style.display = 'none';
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
    menu.style.display = 'block';
    
    // Position menu
    const x = Math.min(event.clientX, window.innerWidth - 200);
    const y = Math.min(event.clientY, window.innerHeight - 200);
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
    const avatar = indicator.querySelector('.typing-avatar');
    
    avatar.textContent = username.charAt(0).toUpperCase();
    indicator.style.display = 'flex';
    scrollToBottom();
}

function hideTypingIndicator() {
    document.getElementById('typingIndicator').style.display = 'none';
}

// =====================================================
// Polling
// =====================================================
function startPolling() {
    // Poll for new messages every 2 seconds
    Chat.pollingInterval = setInterval(async () => {
        if (!Chat.selectedUserId) return;
        
        // Refresh messages
        await refreshNewMessages();
        
        // Check typing status
        await checkTypingStatus();
        
        // Refresh chat list
        await refreshChatList();
    }, 2000);
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
        document.getElementById('chatSidebar').classList.remove('hidden');
        document.getElementById('activeChat').style.display = 'none';
        document.getElementById('emptyChat').style.display = 'flex';
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
