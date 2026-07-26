/**
 * =====================================================
 * ChatApp - Dashboard JavaScript
 * Handles all dashboard interactions
 * =====================================================
 */

// =====================================================
// Dashboard State
// =====================================================
const Dashboard = {
    currentSection: 'dashboard',
    userData: null,
    isLoading: false
};

// =====================================================
// Initialize Dashboard
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeNavbar();
    initializeNavigation();
    initializeFriendCode();
    initializeAddFriend();
    initializeSettings();
    initializeSearch();
    
    // Load dashboard data
    loadDashboardData();
});

// =====================================================
// Sidebar Functions
// =====================================================
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar on mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.add('show');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Handle logout
    const logoutBtns = document.querySelectorAll('[data-action="logout"]');
    logoutBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                handleLogout();
            }
        });
    });
}

// =====================================================
// Navbar Functions
// =====================================================
function initializeNavbar() {
    const navbarAvatar = document.getElementById('navbarAvatar');
    const userDropdown = document.getElementById('userDropdown');
    
    // Toggle user dropdown
    if (navbarAvatar && userDropdown) {
        navbarAvatar.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && e.target !== navbarAvatar) {
                userDropdown.classList.remove('show');
            }
        });
    }
}

// =====================================================
// Navigation Functions
// =====================================================
function initializeNavigation() {
    // Sidebar navigation
    const navItems = document.querySelectorAll('.nav-item[data-section]');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.dataset.section;
            navigateToSection(section);
        });
    });
    
    // View all links
    const viewAllLinks = document.querySelectorAll('.view-all[data-section]');
    viewAllLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.dataset.section;
            navigateToSection(section);
        });
    });
    
    // Dropdown menu items
    const dropdownItems = document.querySelectorAll('.dropdown-item[data-section]');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.dataset.section;
            navigateToSection(section);
            document.getElementById('userDropdown').classList.remove('show');
        });
    });
}

function navigateToSection(sectionName) {
    // Update active nav item
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeNavItem = document.querySelector(`.nav-item[data-section="${sectionName}"]`);
    if (activeNavItem) {
        activeNavItem.classList.add('active');
    }
    
    // Show/hide sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    const activeSection = document.getElementById(`section-${sectionName}`);
    if (activeSection) {
        activeSection.classList.add('active');
    }
    
    // Update page title
    const pageTitle = document.getElementById('pageTitle');
    const titles = {
        'dashboard': 'Dashboard',
        'chats': 'Chats',
        'friends': 'Friends',
        'requests': 'Friend Requests',
        'groups': 'Groups',
        'settings': 'Settings'
    };
    if (pageTitle) {
        pageTitle.textContent = titles[sectionName] || 'Dashboard';
    }
    
    // Close sidebar on mobile
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('show');
    if (sidebarOverlay) sidebarOverlay.classList.remove('show');
    document.body.style.overflow = '';
    
    Dashboard.currentSection = sectionName;
    
    // Load section-specific data
    loadSectionData(sectionName);
}

function loadSectionData(section) {
    switch(section) {
        case 'chats':
            loadRecentChats(true);
            break;
        case 'friends':
            loadFriendsList();
            break;
        case 'requests':
            loadFriendRequests(true);
            break;
        case 'groups':
            loadGroups(true);
            break;
    }
}

// =====================================================
// Dashboard Data Loading
// =====================================================
async function loadDashboardData() {
    if (Dashboard.isLoading) return;
    Dashboard.isLoading = true;
    
    const result = await ChatApp.apiRequest('/dashboard.php', 'GET');
    
    Dashboard.isLoading = false;
    
    if (result.success && result.data) {
        Dashboard.userData = result.data.user;
        updateDashboardUI(result.data);
    }
}

function updateDashboardUI(data) {
    const user = data.user;
    const stats = data.stats;
    
    // Update welcome message
    const welcomeUsername = document.getElementById('welcomeUsername');
    if (welcomeUsername) {
        welcomeUsername.textContent = user.username;
    }
    
    // Update avatars
    const avatarElements = document.querySelectorAll('#sidebarAvatar, #navbarAvatar, #profileAvatar, #dropdownAvatar');
    avatarElements.forEach(el => {
        if (el) el.innerHTML = renderAvatar(user.avatar, user.username);
    });
    
    // Update sidebar user info
    const sidebarUserName = document.querySelector('.sidebar-user .user-name');
    if (sidebarUserName) {
        sidebarUserName.textContent = user.username;
    }
    
    // Update profile card
    const profileName = document.querySelector('.profile-name');
    const profileEmail = document.querySelector('.profile-email');
    if (profileName) profileName.textContent = user.username;
    if (profileEmail) profileEmail.textContent = user.email;
    
    // Update friend code
    const friendCode = document.getElementById('friendCode');
    if (friendCode) {
        friendCode.textContent = user.friend_code;
    }
    
    // Update stats
    document.getElementById('statFriends').textContent = stats.friends;
    document.getElementById('statMessages').textContent = stats.unread_messages;
    document.getElementById('statRequests').textContent = stats.requests;
    document.getElementById('statGroups').textContent = stats.groups;
    
    // Update badges
    const unreadBadge = document.getElementById('unreadBadge');
    const requestBadge = document.getElementById('requestBadge');
    const notificationCount = document.getElementById('notificationCount');
    
    if (unreadBadge) unreadBadge.textContent = stats.unread_messages;
    if (requestBadge) requestBadge.textContent = stats.requests;
    if (notificationCount) {
        const totalNotifications = stats.unread_messages + stats.requests;
        notificationCount.textContent = totalNotifications;
        notificationCount.style.display = totalNotifications > 0 ? 'block' : 'none';
    }
    
    // Load recent data
    loadRecentChats();
    loadFriendRequests();
    loadGroups();
}

// =====================================================
// Friend Code Copy Functionality
// =====================================================
function initializeFriendCode() {
    const copyBtn = document.getElementById('copyFriendCode');
    const friendCode = document.getElementById('friendCode');
    
    if (copyBtn && friendCode) {
        copyBtn.addEventListener('click', function() {
            const code = friendCode.textContent;
            
            // Copy to clipboard
            navigator.clipboard.writeText(code).then(() => {
                // Show success state
                copyBtn.classList.add('copied');
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                
                ChatApp.showToast('Friend code copied to clipboard!', 'success');
                
                // Reset after 2 seconds
                setTimeout(() => {
                    copyBtn.classList.remove('copied');
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = code;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                copyBtn.classList.add('copied');
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                ChatApp.showToast('Friend code copied!', 'success');
                
                setTimeout(() => {
                    copyBtn.classList.remove('copied');
                    copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            });
        });
    }
}

// =====================================================
// Add Friend Functionality
// =====================================================
function initializeAddFriend() {
    const addFriendBtn = document.getElementById('addFriendBtn');
    const friendCodeInput = document.getElementById('friendCodeInput');
    
    if (addFriendBtn && friendCodeInput) {
        addFriendBtn.addEventListener('click', async function() {
            const friendCode = friendCodeInput.value.trim();
            
            if (!friendCode) {
                showFormAlert('addFriendAlert', 'Please enter a friend code', 'error');
                return;
            }
            
            // Validate format
            if (!/^[A-Z]{3}-[A-Z0-9]{6}$/.test(friendCode)) {
                showFormAlert('addFriendAlert', 'Invalid format. Use: XXX-XXXXXX', 'error');
                return;
            }
            
            addFriendBtn.disabled = true;
            addFriendBtn.innerHTML = '<span class="spinner"></span> Sending...';
            
            const result = await ChatApp.apiRequest('/add-friend.php', 'POST', {
                friend_code: friendCode,
                csrf_token: APP_CONFIG.csrfToken
            });
            
            addFriendBtn.disabled = false;
            addFriendBtn.innerHTML = '<i class="fas fa-plus"></i> Add';
            
            if (result.success) {
                showFormAlert('addFriendAlert', result.message, 'success');
                friendCodeInput.value = '';
                ChatApp.showToast(result.message, 'success');
            } else {
                showFormAlert('addFriendAlert', result.message, 'error');
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

function showFormAlert(elementId, message, type) {
    const alert = document.getElementById(elementId);
    if (alert) {
        alert.className = `alert alert-${type} show`;
        alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
        
        setTimeout(() => {
            alert.classList.remove('show');
        }, 5000);
    }
}

// =====================================================
// Load Recent Chats
// =====================================================
async function loadRecentChats(fullList = false) {
    const result = await ChatApp.apiRequest('/recent-chats.php', 'GET');
    
    if (result.success && result.data && result.data.chats) {
        const chats = result.data.chats;
        
        // Update recent chats on dashboard
        const recentChatsList = document.getElementById('recentChatsList');
        if (recentChatsList) {
            if (chats.length === 0) {
                recentChatsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <p>No recent chats</p>
                        <span>Start a conversation with a friend!</span>
                    </div>
                `;
            } else {
                recentChatsList.innerHTML = chats.slice(0, 5).map(chat => createChatItem(chat)).join('');
            }
        }
        
        // Update full chats list
        if (fullList) {
            const chatsFullList = document.getElementById('chatsFullList');
            if (chatsFullList) {
                if (chats.length === 0) {
                    chatsFullList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-comments"></i>
                            <p>No conversations yet</p>
                            <span>Add friends to start chatting!</span>
                        </div>
                    `;
                } else {
                    chatsFullList.innerHTML = chats.map(chat => createChatItem(chat)).join('');
                }
            }
        }
    }
}

function createChatItem(chat) {
    return `
        <div class="chat-item" onclick="openChat(${chat.user_id})">
            <div class="chat-avatar">
                ${renderAvatar(chat.avatar, chat.username)}
                <span class="status-dot ${chat.is_online ? 'online' : 'offline'}"></span>
            </div>
            <div class="chat-info">
                <div class="chat-name">${escapeHtml(chat.username)}</div>
                <div class="chat-message">${chat.is_sender ? 'You: ' : ''}${escapeHtml(chat.last_message || 'No messages yet')}</div>
            </div>
            <div class="chat-meta">
                <div class="chat-time">${chat.last_message_time}</div>
                ${chat.unread_count > 0 ? `<div class="chat-unread">${chat.unread_count}</div>` : ''}
            </div>
        </div>
    `;
}

function openChat(userId) {
    window.location.href = 'chat.php?user_id=' + userId;
}

// =====================================================
// Load Friend Requests
// =====================================================
async function loadFriendRequests(fullList = false) {
    const result = await ChatApp.apiRequest('/friend-requests.php', 'GET');
    
    if (result.success && result.data && result.data.requests) {
        const requests = result.data.requests;
        
        // Update requests on dashboard
        const requestsList = document.getElementById('friendRequestsList');
        if (requestsList) {
            if (requests.length === 0) {
                requestsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-user-check"></i>
                        <p>No pending requests</p>
                        <span>You're all caught up!</span>
                    </div>
                `;
            } else {
                requestsList.innerHTML = requests.slice(0, 3).map(req => createRequestItem(req)).join('');
            }
        }
        
        // Update full requests list
        if (fullList) {
            const requestsFullList = document.getElementById('requestsFullList');
            if (requestsFullList) {
                if (requests.length === 0) {
                    requestsFullList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-user-check"></i>
                            <p>No pending requests</p>
                            <span>You're all caught up!</span>
                        </div>
                    `;
                } else {
                    requestsFullList.innerHTML = requests.map(req => createRequestItem(req)).join('');
                }
            }
        }
    }
}

function createRequestItem(request) {
    return `
        <div class="request-item" id="request-${request.friendship_id}">
            ${renderAvatar(request.avatar, request.username)}
            <div class="request-info">
                <div class="request-name">${escapeHtml(request.username)}</div>
                <div class="request-time">${request.request_date}</div>
            </div>
            <div class="request-actions">
                <button class="btn btn-accept" onclick="respondToFriend(${request.friendship_id}, 'accept')">
                    <i class="fas fa-check"></i>
                </button>
                <button class="btn btn-reject" onclick="respondToFriend(${request.friendship_id}, 'reject')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
}

async function respondToFriend(friendshipId, action) {
    const result = await ChatApp.apiRequest('/respond-friend.php', 'POST', {
        friendship_id: friendshipId,
        action: action,
        csrf_token: APP_CONFIG.csrfToken
    });
    
    if (result.success) {
        // Remove request from UI
        const requestElement = document.getElementById(`request-${friendshipId}`);
        if (requestElement) {
            requestElement.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => {
                requestElement.remove();
                // Check if list is empty
                checkEmptyLists();
            }, 300);
        }
        
        ChatApp.showToast(result.message, 'success');
        
        // Reload dashboard data to update stats
        loadDashboardData();
    } else {
        ChatApp.showToast(result.message, 'error');
    }
}

function checkEmptyLists() {
    const requestsList = document.getElementById('friendRequestsList');
    if (requestsList && requestsList.children.length === 0) {
        requestsList.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-user-check"></i>
                <p>No pending requests</p>
                <span>You're all caught up!</span>
            </div>
        `;
    }
}

// =====================================================
// Load Groups
// =====================================================
async function loadGroups(fullList = false) {
    const result = await ChatApp.apiRequest('/groups.php', 'GET');
    
    if (result.success && result.data && result.data.groups) {
        const groups = result.data.groups;
        
        // Update groups on dashboard
        const groupsList = document.getElementById('groupsList');
        if (groupsList) {
            if (groups.length === 0) {
                groupsList.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-people-group"></i>
                        <p>No groups yet</p>
                        <span>Create or join a group to get started!</span>
                    </div>
                `;
            } else {
                groupsList.innerHTML = groups.slice(0, 4).map(group => createGroupCard(group)).join('');
            }
        }
        
        // Update full groups list
        if (fullList) {
            const groupsFullList = document.getElementById('groupsFullList');
            if (groupsFullList) {
                if (groups.length === 0) {
                    groupsFullList.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-people-group"></i>
                            <p>No groups yet</p>
                            <span>Create a group to start chatting!</span>
                        </div>
                    `;
                } else {
                    groupsFullList.innerHTML = groups.map(group => createGroupCard(group)).join('');
                }
            }
        }
    }
}

function createGroupCard(group) {
    return `
        <div class="group-card" onclick="openGroup(${group.group_id})">
            <div class="group-avatar">
                ${group.avatar ? `<img src="${group.avatar}" alt="${escapeHtml(group.name)}" class="user-avatar-img">` : `<div class="user-avatar">${group.name.charAt(0).toUpperCase()}</div>`}
            </div>
            <div class="group-name">${escapeHtml(group.name)}</div>
            <div class="group-members">${group.member_count} members</div>
        </div>
    `;
}

function openGroup(groupId) {
    window.location.href = 'group-chat.php?group_id=' + groupId;
}

// =====================================================
// Friends List
// =====================================================
async function loadFriendsList() {
    if (typeof loadFriends === 'function') {
        loadFriends();
    } else {
        const friendsList = document.getElementById('friendsList');
        if (friendsList) {
            friendsList.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-user-group"></i>
                    <p>Friends list coming soon</p>
                    <span>Use the friend code feature to connect with others!</span>
                </div>
            `;
        }
    }
}

// =====================================================
// Search Functionality
// =====================================================
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    
    if (searchInput && searchResults) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.classList.remove('show');
                return;
            }
            
            searchTimeout = setTimeout(async () => {
                const result = await ChatApp.apiRequest(`/search-users.php?q=${encodeURIComponent(query)}`, 'GET');
                
                if (result.success && result.data.users.length > 0) {
                    searchResults.innerHTML = result.data.users.map(user => `
                        <div class="search-result-item" onclick="selectSearchUser(${user.id}, '${escapeHtml(user.username)}')">
                            ${renderAvatar(user.avatar, user.username)}
                            <div>
                                <div class="user-name">${escapeHtml(user.username)}</div>
                                <div class="user-code">${escapeHtml(user.friend_code)}</div>
                            </div>
                        </div>
                    `).join('');
                    searchResults.classList.add('show');
                } else {
                    searchResults.innerHTML = '<div class="search-result-item">No users found</div>';
                    searchResults.classList.add('show');
                }
            }, 300);
        });
        
        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.remove('show');
            }
        });
    }
}

function selectSearchUser(userId, username) {
    window.location.href = 'chat.php?user_id=' + userId;
}

// =====================================================
// Logout Handler
// =====================================================
async function handleLogout() {
    const result = await ChatApp.apiRequest('/logout.php', 'POST', {
        csrf_token: APP_CONFIG.csrfToken
    });
    
    window.location.href = result.data?.redirect || '../index.php';
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
