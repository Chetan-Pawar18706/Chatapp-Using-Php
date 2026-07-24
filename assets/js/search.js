/**
 * =====================================================
 * Global Search JavaScript
 * ChatApp - Search Users, Friends, Groups, Messages
 * =====================================================
 */

const SearchModule = {
    csrfToken: null,
    searchInput: null,
    resultsContainer: null,
    debounceTimer: null,
    currentQuery: '',
    currentFilter: 'all',
    isLoading: false,
    
    /**
     * Initialize Search Module
     */
    init: function() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.searchInput = document.getElementById('searchInput');
        this.resultsContainer = document.getElementById('searchResults');
        
        if (this.searchInput) {
            this.initEventListeners();
            this.initFilters();
            this.initRecentSearches();
        }
    },
    
    /**
     * Initialize Event Listeners
     */
    initEventListeners: function() {
        // Search input
        this.searchInput.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });
        
        // Search on Enter
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.performSearch(this.searchInput.value);
            }
        });
        
        // Search button click
        document.getElementById('searchBtn')?.addEventListener('click', () => {
            this.performSearch(this.searchInput.value);
        });
        
        // Clear search
        document.getElementById('clearSearch')?.addEventListener('click', () => {
            this.clearSearch();
        });
        
        // Focus search input
        this.searchInput.focus();
    },
    
    /**
     * Initialize Filters
     */
    initFilters: function() {
        document.querySelectorAll('.search-filters .filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                // Update active state
                document.querySelectorAll('.search-filters .filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Update current filter
                this.currentFilter = btn.dataset.filter;
                
                // Re-run search if there's a query
                if (this.currentQuery) {
                    this.performSearch(this.currentQuery);
                }
            });
        });
        
        // See all buttons
        document.querySelectorAll('.see-all-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.type;
                document.querySelector(`.filter-btn[data-filter="${type}"]`)?.click();
            });
        });
    },
    
    /**
     * Initialize Recent Searches
     */
    initRecentSearches: function() {
        // Clear all recent searches
        document.getElementById('clearRecent')?.addEventListener('click', () => {
            this.clearRecentSearches();
        });
        
        // Delete individual recent search
        document.querySelectorAll('.delete-recent-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.dataset.id;
                this.deleteRecentSearch(id);
            });
        });
        
        // Click on recent search
        document.querySelectorAll('.recent-item').forEach(item => {
            item.addEventListener('click', () => {
                const query = item.querySelector('.recent-query').textContent;
                this.searchInput.value = query;
                this.performSearch(query);
            });
        });
    },
    
    /**
     * Handle Input
     */
    handleInput: function(value) {
        const clearBtn = document.getElementById('clearSearch');
        if (clearBtn) {
            clearBtn.style.display = value ? 'flex' : 'none';
        }
        
        // Debounce search
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            if (value.length >= 2) {
                this.performSearch(value);
            } else if (value.length === 0) {
                this.showInitialState();
            }
        }, 300);
    },
    
    /**
     * Perform Search
     */
    performSearch: async function(query) {
        if (this.isLoading || query.length < 2) return;
        
        this.currentQuery = query;
        this.isLoading = true;
        this.showLoading();
        
        try {
            const response = await fetch(`api/global-search.php?q=${encodeURIComponent(query)}&type=${this.currentFilter}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderResults(data.data);
                this.saveRecentSearch(query);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Search failed. Please try again.');
        } finally {
            this.isLoading = false;
        }
    },
    
    /**
     * Show Loading State
     */
    showLoading: function() {
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('noResults').style.display = 'none';
        document.getElementById('resultsContent').style.display = 'none';
        document.getElementById('loadingState').style.display = 'flex';
    },
    
    /**
     * Show Initial State
     */
    showInitialState: function() {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('noResults').style.display = 'none';
        document.getElementById('resultsContent').style.display = 'none';
        document.getElementById('emptyState').style.display = 'flex';
    },
    
    /**
     * Show No Results State
     */
    showNoResults: function() {
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('resultsContent').style.display = 'none';
        document.getElementById('noResults').style.display = 'flex';
    },
    
    /**
     * Show Error State
     */
    showError: function(message) {
        this.showNoResults();
        const noResults = document.getElementById('noResults');
        if (noResults) {
            noResults.querySelector('h4').textContent = 'Error';
            noResults.querySelector('p').textContent = message;
        }
    },
    
    /**
     * Render Results
     */
    renderResults: function(data) {
        const { results, counts } = data;
        
        // Check if any results
        const totalResults = counts.users + counts.friends + counts.groups + counts.messages;
        
        if (totalResults === 0) {
            this.showNoResults();
            return;
        }
        
        document.getElementById('loadingState').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('noResults').style.display = 'none';
        document.getElementById('resultsContent').style.display = 'flex';
        
        // Render each section
        this.renderUsersSection(results.users, counts.users);
        this.renderFriendsSection(results.friends, counts.friends);
        this.renderGroupsSection(results.groups, counts.groups);
        this.renderMessagesSection(results.messages, counts.messages);
    },
    
    /**
     * Render Users Section
     */
    renderUsersSection: function(users, count) {
        const section = document.getElementById('usersSection');
        const list = document.getElementById('usersList');
        
        if (!section || !list) return;
        
        if (this.currentFilter !== 'all' && this.currentFilter !== 'users') {
            section.style.display = 'none';
            return;
        }
        
        if (users.length === 0) {
            section.style.display = 'none';
            return;
        }
        
        section.style.display = 'block';
        
        list.innerHTML = users.map(user => `
            <a href="chat.php?user=${user.id}" class="result-item">
                <div class="result-avatar">
                    ${user.avatar_url 
                        ? `<img src="${user.avatar_url}" alt="${user.username}">`
                        : `<div class="avatar-initials">${user.initials}</div>`
                    }
                    <span class="status-dot ${user.status_color}"></span>
                </div>
                <div class="result-info">
                    <div class="result-name">${this.escapeHtml(user.username)}</div>
                    <div class="result-meta">${user.bio ? this.escapeHtml(user.bio) : 'No bio'}</div>
                </div>
                <div class="result-actions">
                    <button class="result-action-btn message" onclick="event.stopPropagation();" title="Message">
                        <i class="fas fa-comment"></i>
                    </button>
                </div>
            </a>
        `).join('');
    },
    
    /**
     * Render Friends Section
     */
    renderFriendsSection: function(friends, count) {
        const section = document.getElementById('friendsSection');
        const list = document.getElementById('friendsList');
        
        if (!section || !list) return;
        
        if (this.currentFilter !== 'all' && this.currentFilter !== 'friends') {
            section.style.display = 'none';
            return;
        }
        
        if (friends.length === 0) {
            section.style.display = 'none';
            return;
        }
        
        section.style.display = 'block';
        
        list.innerHTML = friends.map(friend => `
            <a href="chat.php?user=${friend.id}" class="result-item">
                <div class="result-avatar">
                    ${friend.avatar_url 
                        ? `<img src="${friend.avatar_url}" alt="${friend.username}">`
                        : `<div class="avatar-initials">${friend.initials}</div>`
                    }
                    <span class="status-dot ${friend.status_color}"></span>
                </div>
                <div class="result-info">
                    <div class="result-name">
                        ${this.escapeHtml(friend.username)}
                        <span class="badge friend">Friend</span>
                    </div>
                    <div class="result-meta">${friend.last_seen_text}</div>
                </div>
                <div class="result-actions">
                    <button class="result-action-btn message" onclick="event.stopPropagation();" title="Message">
                        <i class="fas fa-comment"></i>
                    </button>
                </div>
            </a>
        `).join('');
    },
    
    /**
     * Render Groups Section
     */
    renderGroupsSection: function(groups, count) {
        const section = document.getElementById('groupsSection');
        const list = document.getElementById('groupsList');
        
        if (!section || !list) return;
        
        if (this.currentFilter !== 'all' && this.currentFilter !== 'groups') {
            section.style.display = 'none';
            return;
        }
        
        if (groups.length === 0) {
            section.style.display = 'none';
            return;
        }
        
        section.style.display = 'block';
        
        list.innerHTML = groups.map(group => `
            <a href="group-chat.php?id=${group.id}" class="result-item">
                <div class="group-avatar">
                    ${group.avatar_url 
                        ? `<img src="${group.avatar_url}" alt="${group.name}">`
                        : group.initials
                    }
                </div>
                <div class="result-info">
                    <div class="result-name">
                        ${this.escapeHtml(group.name)}
                        ${group.is_member ? '<span class="badge member">Member</span>' : ''}
                    </div>
                    <div class="result-meta">${group.member_count_text}</div>
                </div>
                <div class="result-actions">
                    <button class="result-action-btn" onclick="event.stopPropagation();" title="View Group">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </a>
        `).join('');
    },
    
    /**
     * Render Messages Section
     */
    renderMessagesSection: function(messages, count) {
        const section = document.getElementById('messagesSection');
        const list = document.getElementById('messagesList');
        
        if (!section || !list) return;
        
        if (this.currentFilter !== 'all' && this.currentFilter !== 'messages') {
            section.style.display = 'none';
            return;
        }
        
        if (messages.length === 0) {
            section.style.display = 'none';
            return;
        }
        
        section.style.display = 'block';
        
        list.innerHTML = messages.map(msg => `
            <a href="${msg.chat_url}" class="message-preview">
                <div class="message-sender-avatar">
                    ${msg.sender_avatar_url 
                        ? `<img src="${msg.sender_avatar_url}" alt="${msg.sender_name}">`
                        : `<div class="avatar-initials">${msg.sender_initials}</div>`
                    }
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">${this.escapeHtml(msg.sender_name)}</span>
                        <span class="message-time">${msg.time_ago}</span>
                    </div>
                    <div class="message-chat-name">${this.escapeHtml(msg.chat_name)}</div>
                    <div class="message-text">${msg.content_preview}</div>
                </div>
            </a>
        `).join('');
    },
    
    /**
     * Clear Search
     */
    clearSearch: function() {
        this.searchInput.value = '';
        this.currentQuery = '';
        document.getElementById('clearSearch').style.display = 'none';
        this.showInitialState();
        this.searchInput.focus();
    },
    
    /**
     * Save Recent Search
     */
    saveRecentSearch: async function(query) {
        try {
            await fetch('api/recent-searches.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `csrf_token=${this.csrfToken}&search_type=user&search_query=${encodeURIComponent(query)}`
            });
        } catch (error) {
            console.error('Failed to save search:', error);
        }
    },
    
    /**
     * Delete Recent Search
     */
    deleteRecentSearch: async function(id) {
        try {
            const response = await fetch(`api/recent-searches.php?id=${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const item = document.querySelector(`.recent-item[data-id="${id}"]`);
                if (item) {
                    item.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        item.remove();
                        // Hide section if empty
                        const recentList = document.getElementById('recentList');
                        if (recentList && recentList.children.length === 0) {
                            document.getElementById('recentSearches').style.display = 'none';
                        }
                    }, 300);
                }
            }
        } catch (error) {
            console.error('Failed to delete search:', error);
        }
    },
    
    /**
     * Clear All Recent Searches
     */
    clearRecentSearches: async function() {
        if (!confirm('Clear all recent searches?')) return;
        
        try {
            const response = await fetch('api/recent-searches.php?action=clear', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('recentSearches').style.display = 'none';
            }
        } catch (error) {
            console.error('Failed to clear searches:', error);
        }
    },
    
    /**
     * Escape HTML
     */
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    SearchModule.init();
});

// Add animation keyframe
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(-100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
