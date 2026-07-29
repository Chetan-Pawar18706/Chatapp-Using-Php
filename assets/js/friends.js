/**
 * =====================================================
 * ChatApp - Friend System JavaScript
 * Handles all friend-related functionality
 * =====================================================
 */

// =====================================================
// Friend System State
// =====================================================
const FriendSystem = {
    friends: [],
    receivedRequests: [],
    sentRequests: [],
    searchResults: [],
    currentFilter: 'all',
    currentRequestTab: 'received',
    selectedFriend: null,
    typingTimers: {}
};

// =====================================================
// Initialize Friend System
// =====================================================
function initializeFriendSystem() {
    initializeFriendsSearch();
    initializeFriendsFilter();
    initializeRequestTabs();
    initializeTypingStatus();
    initializeAddFriendForm();
    
    // Load friends data
    loadFriends();
    loadFriendRequests('received');
    loadFriendRequests('sent');
}

// =====================================================
// Friends Search
// =====================================================
function initializeFriendsSearch() {
    const searchInput = document.getElementById('friendsSearchInput');
    
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim().toLowerCase();
            
            searchTimeout = setTimeout(() => {
                filterFriendsList(query);
            }, 300);
        });
    }
}

function filterFriendsList(query) {
    const friendItems = document.querySelectorAll('.friend-item');
    
    friendItems.forEach(item => {
        const name = item.querySelector('.friend-name')?.textContent.toLowerCase() || '';
        const code = item.querySelector('.friend-code')?.textContent.toLowerCase() || '';
        
        if (name.includes(query) || code.includes(query)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// =====================================================
// Friends Filter (All/Online/Offline)
// =====================================================
function initializeFriendsFilter() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            FriendSystem.currentFilter = this.dataset.filter;
            loadFriends();
        });
    });
}

// =====================================================
// Request Tabs (Received/Sent)
// =====================================================
function initializeRequestTabs() {
    const tabs = document.querySelectorAll('.request-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            FriendSystem.currentRequestTab = this.dataset.tab;
            
            // Show corresponding tab content
            document.querySelectorAll('.request-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            const tabId = this.dataset.tab + 'RequestsTab';
            const tabContent = document.getElementById(tabId);
            if (tabContent) {
                tabContent.classList.add('active');
            }
            
            // Load requests for this tab
            loadFriendRequests(this.dataset.tab);
        });
    });
}

// =====================================================
// Load Friends
// =====================================================
async function loadFriends() {
    const result = await ChatApp.apiRequest(`/get-friends.php?filter=${FriendSystem.currentFilter}`, 'GET');
    
    if (result.success && result.data) {
        FriendSystem.friends = result.data.friends;
        renderFriendsList(result.data.friends);
        updateFriendsStats(result.data);
    }
}

function renderFriendsList(friends) {
    const container = document.getElementById('friendsList');
    if (!container) return;
    
    if (friends.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-user-group"></i>
                <p>No friends yet</p>
                <span>Search by username or friend code to add friends!</span>
            </div>
        `;
        return;
    }
    
    container.innerHTML = friends.map(friend => createFriendItem(friend)).join('');
}

function createFriendItem(friend) {
    const typingHtml = friend.is_typing ? 
        `<span class="typing-indicator">typing<span class="typing-dots"><span></span><span></span><span></span></span></span>` : '';
    
    const mutualHtml = friend.mutual_friends > 0 ? 
        `<span class="mutual-friends"><i class="fas fa-user-friends"></i> ${friend.mutual_friends} mutual</span>` : '';
    
    const unreadHtml = friend.unread_messages > 0 ? 
        `<span class="unread-badge">${friend.unread_messages}</span>` : '';
    
    return `
        <div class="friend-item" data-user-id="${friend.id}">
            <div class="friend-avatar" onclick="window.location.href='profile.php?user_id=${friend.id}'" style="cursor:pointer">
                ${renderAvatar(friend.avatar, friend.username)}
                <span class="status-indicator ${friend.is_online ? 'online' : 'offline'}"></span>
            </div>
            <div class="friend-info" onclick="window.location.href='profile.php?user_id=${friend.id}'" style="cursor:pointer">
                <div class="friend-name">
                    ${escapeHtml(friend.username)}
                    ${typingHtml}
                </div>
                <div class="friend-meta">
                    <span class="last-seen">
                        <i class="fas fa-clock"></i>
                        ${friend.is_online ? 'Online' : friend.last_seen}
                    </span>
                    ${mutualHtml}
                    ${unreadHtml}
                </div>
            </div>
            <div class="friend-actions">
                <button class="btn-icon btn-chat" onclick="openChat(${friend.id})" title="Send Message">
                    <i class="fas fa-comment"></i>
                </button>
                <button class="btn-icon btn-remove" onclick="confirmRemoveFriend(${friend.id}, '${escapeHtml(friend.username)}')" title="Remove Friend">
                    <i class="fas fa-user-minus"></i>
                </button>
            </div>
        </div>
    `;
}

function updateFriendsStats(data) {
    const onlineCount = document.getElementById('friendsOnlineCount');
    const totalCount = document.getElementById('friendsTotalCount');
    
    if (onlineCount) onlineCount.textContent = `${data.online} online`;
    if (totalCount) totalCount.textContent = `${data.total} total`;
}

// =====================================================
// Load Friend Requests
// =====================================================
async function loadFriendRequests(type = 'received') {
    const result = await ChatApp.apiRequest(`/get-friend-requests.php?type=${type}`, 'GET');
    
    if (result.success && result.data) {
        if (type === 'received') {
            FriendSystem.receivedRequests = result.data.requests;
            renderReceivedRequests(result.data.requests);
            updateRequestsBadge('received', result.data.total);
        } else {
            FriendSystem.sentRequests = result.data.requests;
            renderSentRequests(result.data.requests);
            updateRequestsBadge('sent', result.data.total);
        }
    }
}

function renderReceivedRequests(requests) {
    const container = document.getElementById('receivedRequestsList');
    if (!container) return;
    
    if (requests.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No received requests</p>
                <span>When someone sends you a request, it will appear here</span>
            </div>
        `;
        return;
    }
    
    container.innerHTML = requests.map(req => createReceivedRequestItem(req)).join('');
}

function renderSentRequests(requests) {
    const container = document.getElementById('sentRequestsList');
    if (!container) return;
    
    if (requests.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-paper-plane"></i>
                <p>No sent requests</p>
                <span>Your outgoing requests will appear here</span>
            </div>
        `;
        return;
    }
    
    container.innerHTML = requests.map(req => createSentRequestItem(req)).join('');
}

function createReceivedRequestItem(req) {
    const mutualHtml = req.mutual_friends > 0 ? 
        `<span class="mutual-friends"><i class="fas fa-user-friends"></i> ${req.mutual_friends} mutual</span>` : '';
    
    return `
        <div class="request-item" data-request-id="${req.friendship_id}">
            <div class="request-avatar" onclick="window.location.href='profile.php?user_id=${req.id}'" style="cursor:pointer">
                ${renderAvatar(req.avatar, req.username)}
                <span class="status-indicator ${req.is_online ? 'online' : 'offline'}"></span>
            </div>
            <div class="request-info" onclick="window.location.href='profile.php?user_id=${req.id}'" style="cursor:pointer">
                <div class="request-name">${escapeHtml(req.username)}</div>
                <div class="request-meta">
                    <span class="request-time"><i class="fas fa-clock"></i> ${req.request_date}</span>
                    ${mutualHtml}
                </div>
            </div>
            <div class="request-actions">
                <button class="btn btn-accept" onclick="acceptFriend(${req.friendship_id})">
                    <i class="fas fa-check"></i> Accept
                </button>
                <button class="btn btn-reject" onclick="rejectFriend(${req.friendship_id})">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
        </div>
    `;
}

function createSentRequestItem(req) {
    const mutualHtml = req.mutual_friends > 0 ? 
        `<span class="mutual-friends"><i class="fas fa-user-friends"></i> ${req.mutual_friends} mutual</span>` : '';
    
    return `
        <div class="request-item" data-request-id="${req.friendship_id}">
            <div class="request-avatar" onclick="window.location.href='profile.php?user_id=${req.id}'" style="cursor:pointer">
                ${renderAvatar(req.avatar, req.username)}
                <span class="status-indicator ${req.is_online ? 'online' : 'offline'}"></span>
            </div>
            <div class="request-info" onclick="window.location.href='profile.php?user_id=${req.id}'" style="cursor:pointer">
                <div class="request-name">${escapeHtml(req.username)}</div>
                <div class="request-meta">
                    <span class="request-time"><i class="fas fa-clock"></i> ${req.request_date}</span>
                    ${mutualHtml}
                </div>
            </div>
            <div class="request-actions">
                <button class="btn btn-cancel" onclick="cancelRequest(${req.friendship_id})">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    `;
}

function updateRequestsBadge(type, count) {
    if (type === 'received') {
        const badge = document.getElementById('receivedRequestsBadge');
        const navBadge = document.getElementById('requestBadge');
        if (badge) badge.textContent = count;
        if (navBadge) navBadge.textContent = count;
    } else {
        const badge = document.getElementById('sentRequestsBadge');
        if (badge) badge.textContent = count;
    }
}

// =====================================================
// Friend Actions
// =====================================================

// Send Friend Request
async function sendFriendRequest(userId) {
    const result = await ChatApp.apiRequest('/send-friend-request.php', 'POST', {
        user_id: userId,
        csrf_token: APP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        // Update search results if visible
        updateSearchResultStatus(userId, 'request_sent');
    } else {
        ChatApp.showToast(result.message, 'error');
    }
    
    return result;
}

// Accept Friend Request
async function acceptFriend(friendshipId) {
    const result = await ChatApp.apiRequest('/accept-friend.php', 'POST', {
        friendship_id: friendshipId,
        csrf_token: APP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        // Remove from received requests
        const item = document.querySelector(`.request-item[data-request-id="${friendshipId}"]`);
        if (item) {
            item.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                item.remove();
                loadFriendRequests('received');
                loadFriends();
                updateDashboardStats();
            }, 300);
        }
    } else {
        ChatApp.showToast(result.message, 'error');
    }
}

// Reject Friend Request
async function rejectFriend(friendshipId) {
    const result = await ChatApp.apiRequest('/reject-friend.php', 'POST', {
        friendship_id: friendshipId,
        csrf_token: APP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        const item = document.querySelector(`.request-item[data-request-id="${friendshipId}"]`);
        if (item) {
            item.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                item.remove();
                loadFriendRequests('received');
                updateDashboardStats();
            }, 300);
        }
    } else {
        ChatApp.showToast(result.message, 'error');
    }
}

// Cancel Sent Request
async function cancelRequest(friendshipId) {
    const result = await ChatApp.apiRequest('/cancel-request.php', 'POST', {
        friendship_id: friendshipId,
        csrf_token: APP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        const item = document.querySelector(`.request-item[data-request-id="${friendshipId}"]`);
        if (item) {
            item.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                item.remove();
                loadFriendRequests('sent');
                updateDashboardStats();
            }, 300);
        }
    } else {
        ChatApp.showToast(result.message, 'error');
    }
}

// Remove Friend
function confirmRemoveFriend(userId, username) {
    if (confirm(`Are you sure you want to remove ${username} from your friends?`)) {
        removeFriend(userId);
    }
}

async function removeFriend(userId) {
    const result = await ChatApp.apiRequest('/remove-friend.php', 'POST', {
        friend_id: userId,
        csrf_token: APP_CONFIG.csrfToken
    });
    
    if (result.success) {
        ChatApp.showToast(result.message, 'success');
        const item = document.querySelector(`.friend-item[data-user-id="${userId}"]`);
        if (item) {
            item.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                item.remove();
                loadFriends();
                updateDashboardStats();
            }, 300);
        }
    } else {
        ChatApp.showToast(result.message, 'error');
    }
}

// =====================================================
// Search Users (Global Search)
// =====================================================
async function searchUsers(query, type = 'all') {
    const result = await ChatApp.apiRequest(`/search-friends.php?q=${encodeURIComponent(query)}&type=${type}`, 'GET');
    
    if (result.success && result.data) {
        return result.data.users;
    }
    return [];
}

function renderSearchResults(users) {
    const container = document.getElementById('searchResults');
    if (!container) return;
    
    if (users.length === 0) {
        container.innerHTML = '<div class="search-result-item">No users found</div>';
        container.classList.add('show');
        return;
    }
    
    container.innerHTML = users.map(user => createSearchResultItem(user)).join('');
    container.classList.add('show');
}

function createSearchResultItem(user) {
    let statusBtn = '';
    
    switch (user.friendship_status) {
        case 'friends':
            statusBtn = `<button class="btn-friends" disabled><i class="fas fa-user-check"></i> Friends</button>`;
            break;
        case 'request_sent':
            statusBtn = `<button class="btn-pending" disabled><i class="fas fa-clock"></i> Pending</button>`;
            break;
        case 'request_received':
            statusBtn = `<button class="btn-accept" onclick="acceptFriendByCode('${user.friend_code}')"><i class="fas fa-check"></i> Accept</button>`;
            break;
        default:
            if (user.can_send_request) {
                statusBtn = `<button class="btn-add" onclick="sendFriendRequest(${user.id})"><i class="fas fa-user-plus"></i> Add</button>`;
            }
            break;
    }
    
    return `
        <div class="search-result-item" data-user-id="${user.id}">
            <div class="result-avatar">
                ${renderAvatar(user.avatar, user.username)}
                <span class="status-indicator ${user.is_online ? 'online' : 'offline'}"></span>
            </div>
            <div class="result-info">
                <div class="result-name">${escapeHtml(user.username)}</div>
                <div class="result-code">${escapeHtml(user.friend_code)}</div>
            </div>
            <div class="result-actions">
                ${statusBtn}
            </div>
        </div>
    `;
}

function updateSearchResultStatus(userId, status) {
    const item = document.querySelector(`.search-result-item[data-user-id="${userId}"]`);
    if (item) {
        const actionsDiv = item.querySelector('.result-actions');
        if (actionsDiv) {
            if (status === 'request_sent') {
                actionsDiv.innerHTML = `<button class="btn-pending" disabled><i class="fas fa-clock"></i> Pending</button>`;
            } else if (status === 'friends') {
                actionsDiv.innerHTML = `<button class="btn-friends" disabled><i class="fas fa-user-check"></i> Friends</button>`;
            }
        }
    }
}

// =====================================================
// Typing Status
// =====================================================
function initializeTypingStatus() {
    // Update typing status periodically
    setInterval(updateAllTypingStatus, 5000);
}

async function updateTypingStatus(userId, isTyping) {
    await ChatApp.apiRequest('/update-typing.php', 'POST', {
        user_id: userId,
        is_typing: isTyping,
        csrf_token: APP_CONFIG.csrfToken
    });
}

async function checkTypingStatus(userId) {
    const result = await ChatApp.apiRequest(`/get-typing.php?user_id=${userId}`, 'GET');
    
    if (result.success && result.data) {
        return result.data.is_typing;
    }
    return false;
}

async function updateAllTypingStatus() {
    // Check typing status for all friends
    for (const friend of FriendSystem.friends) {
        if (friend.is_typing) {
            // Refresh the friend list to update typing status
            loadFriends();
            break;
        }
    }
}

// =====================================================
// Dashboard Stats Update
// =====================================================
async function updateDashboardStats() {
    const result = await ChatApp.apiRequest('/dashboard.php', 'GET');
    
    if (result.success && result.data) {
        const stats = result.data.stats;
        
        const statFriends = document.getElementById('statFriends');
        const statRequests = document.getElementById('statRequests');
        
        if (statFriends) statFriends.textContent = stats.friends;
        if (statRequests) statRequests.textContent = stats.requests;
    }
}

// =====================================================
// Add Friend Form (for standalone pages)
// =====================================================
function initializeAddFriendForm() {
    const addFriendBtn = document.getElementById('addFriendBtn');
    const friendCodeInput = document.getElementById('friendCodeInput');
    
    if (addFriendBtn && friendCodeInput) {
        addFriendBtn.addEventListener('click', async function() {
            const friendCode = friendCodeInput.value.trim();
            
            if (!friendCode) {
                showAddFriendAlert('Please enter a friend code', 'error');
                return;
            }
            
            // Validate format
            if (!/^[A-Z]{3}-[A-Z0-9]{6}$/.test(friendCode)) {
                showAddFriendAlert('Invalid format. Use: XXX-XXXXXX', 'error');
                return;
            }
            
            addFriendBtn.disabled = true;
            addFriendBtn.innerHTML = '<span class="spinner"></span> Sending...';
            
            const result = await ChatApp.apiRequest('/add-friend.php', 'POST', {
                friend_code: friendCode,
                csrf_token: APP_CONFIG.csrfToken
            });
            
            addFriendBtn.disabled = false;
            addFriendBtn.innerHTML = '<i class="fas fa-user-plus"></i> Add Friend';
            
            if (result.success) {
                showAddFriendAlert(result.message, 'success');
                friendCodeInput.value = '';
                ChatApp.showToast(result.message, 'success');
            } else {
                showAddFriendAlert(result.message, 'error');
            }
        });
        
        // Enter key support
        friendCodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addFriendBtn.click();
            }
        });
    }
}

function showAddFriendAlert(message, type) {
    const alert = document.getElementById('addFriendAlert');
    if (alert) {
        alert.className = `alert alert-${type} show`;
        alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
        
        setTimeout(() => {
            alert.classList.remove('show');
        }, 5000);
    }
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

// Add fadeOut animation
(function() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(-20px); }
        }
    `;
    document.head.appendChild(style);
})();

// =====================================================
// Initialize on DOM Ready
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize friend system when on friends or requests section
    initializeFriendSystem();
    
    // Re-load data when switching to friends/requests sections
    const navItems = document.querySelectorAll('.nav-item[data-section]');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            const section = this.dataset.section;
            if (section === 'friends') {
                loadFriends();
            } else if (section === 'requests') {
                loadFriendRequests('received');
                loadFriendRequests('sent');
            }
        });
    });
});
