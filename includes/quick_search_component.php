<?php
/**
 * =====================================================
 * Quick Search Component
 * ChatApp - Navbar Search Widget
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Render Quick Search Widget for Navbar
 * 
 * @return string HTML output
 */
function render_quick_search() {
    $html = '
    <div class="quick-search" id="quickSearch">
        <div class="quick-search-input">
            <i class="fas fa-search"></i>
            <input type="text" 
                   id="quickSearchInput" 
                   placeholder="Search..." 
                   autocomplete="off">
            <kbd>⌘K</kbd>
        </div>
        
        <div class="quick-search-dropdown" id="quickSearchDropdown">
            <div class="quick-search-loading" id="quickSearchLoading" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            
            <div class="quick-search-results" id="quickSearchResults">
                <div class="quick-search-empty">
                    <p>Start typing to search</p>
                    <div class="search-hints">
                        <span><kbd>@</kbd> Users</span>
                        <span><kbd>#</kbd> Groups</span>
                        <span><kbd>!</kbd> Messages</span>
                    </div>
                </div>
            </div>
            
            <div class="quick-search-footer">
                <a href="search.php">Open Advanced Search</a>
                <span class="shortcut-hint">
                    <kbd>↑↓</kbd> Navigate 
                    <kbd>↵</kbd> Select 
                    <kbd>Esc</kbd> Close
                </span>
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Render Quick Search CSS
 * 
 * @return string CSS output
 */
function render_quick_search_css() {
    return '
    <style>
        /* Quick Search */
        .quick-search {
            position: relative;
        }
        
        .quick-search-input {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            min-width: 200px;
            transition: all 0.2s ease;
        }
        
        .quick-search-input:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
            min-width: 300px;
        }
        
        .quick-search-input i {
            color: var(--text-muted);
            font-size: 14px;
        }
        
        .quick-search-input input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-size: 14px;
            outline: none;
        }
        
        .quick-search-input input::placeholder {
            color: var(--text-muted);
        }
        
        .quick-search-input kbd {
            padding: 2px 6px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 11px;
            color: var(--text-muted);
        }
        
        .quick-search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 400px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            z-index: 1000;
            overflow: hidden;
        }
        
        .quick-search.active .quick-search-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .quick-search-loading {
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
        }
        
        .quick-search-results {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .quick-search-empty {
            padding: 30px;
            text-align: center;
            color: var(--text-muted);
        }
        
        .quick-search-empty p {
            margin: 0 0 15px 0;
        }
        
        .search-hints {
            display: flex;
            justify-content: center;
            gap: 15px;
            font-size: 12px;
        }
        
        .search-hints span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .search-hints kbd {
            padding: 2px 6px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 11px;
        }
        
        .quick-search-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        
        .quick-search-item:hover,
        .quick-search-item.active {
            background: var(--bg-tertiary);
        }
        
        .quick-search-item-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            color: white;
            background: var(--primary-color);
        }
        
        .quick-search-item-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .quick-search-item-info {
            flex: 1;
            min-width: 0;
        }
        
        .quick-search-item-name {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .quick-search-item-meta {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .quick-search-item-type {
            font-size: 11px;
            padding: 2px 8px;
            background: var(--bg-secondary);
            border-radius: 4px;
            color: var(--text-muted);
        }
        
        .quick-search-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            border-top: 1px solid var(--border-color);
            font-size: 12px;
        }
        
        .quick-search-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .quick-search-footer a:hover {
            text-decoration: underline;
        }
        
        .shortcut-hint {
            display: flex;
            gap: 10px;
            color: var(--text-muted);
        }
        
        .shortcut-hint kbd {
            padding: 1px 5px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 3px;
            font-size: 10px;
        }
        
        @media (max-width: 768px) {
            .quick-search-input {
                min-width: 150px;
            }
            
            .quick-search-input:focus-within {
                min-width: 200px;
            }
            
            .quick-search-dropdown {
                width: calc(100vw - 20px);
                right: -10px;
            }
            
            .quick-search-input kbd,
            .shortcut-hint {
                display: none;
            }
        }
    </style>';
}

/**
 * Render Quick Search JavaScript
 * 
 * @return string JavaScript output
 */
function render_quick_search_js() {
    return '
    <script>
    const QuickSearch = {
        input: null,
        dropdown: null,
        results: null,
        isOpen: false,
        isLoading: false,
        selectedIndex: -1,
        debounceTimer: null,
        
        init: function() {
            this.input = document.getElementById("quickSearchInput");
            this.dropdown = document.getElementById("quickSearchDropdown");
            this.results = document.getElementById("quickSearchResults");
            
            if (!this.input) return;
            
            this.initEvents();
        },
        
        initEvents: function() {
            // Input events
            this.input.addEventListener("focus", () => this.open());
            this.input.addEventListener("input", (e) => this.handleInput(e.target.value));
            this.input.addEventListener("keydown", (e) => this.handleKeydown(e));
            
            // Close on outside click
            document.addEventListener("click", (e) => {
                if (!e.target.closest(".quick-search")) {
                    this.close();
                }
            });
            
            // Keyboard shortcut (Cmd/Ctrl + K)
            document.addEventListener("keydown", (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === "k") {
                    e.preventDefault();
                    this.input.focus();
                    this.open();
                }
            });
        },
        
        handleInput: function(value) {
            clearTimeout(this.debounceTimer);
            this.selectedIndex = -1;
            
            if (value.length < 2) {
                this.showEmpty();
                return;
            }
            
            this.debounceTimer = setTimeout(() => {
                this.search(value);
            }, 250);
        },
        
        handleKeydown: function(e) {
            if (!this.isOpen) return;
            
            const items = this.results.querySelectorAll(".quick-search-item");
            
            switch(e.key) {
                case "ArrowDown":
                    e.preventDefault();
                    this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
                    this.updateSelection(items);
                    break;
                case "ArrowUp":
                    e.preventDefault();
                    this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                    this.updateSelection(items);
                    break;
                case "Enter":
                    e.preventDefault();
                    if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                        items[this.selectedIndex].click();
                    } else {
                        window.location.href = "search.php?q=" + encodeURIComponent(this.input.value);
                    }
                    break;
                case "Escape":
                    this.close();
                    break;
            }
        },
        
        updateSelection: function(items) {
            items.forEach((item, i) => {
                item.classList.toggle("active", i === this.selectedIndex);
            });
        },
        
        open: function() {
            this.isOpen = true;
            document.querySelector(".quick-search")?.classList.add("active");
        },
        
        close: function() {
            this.isOpen = false;
            document.querySelector(".quick-search")?.classList.remove("active");
            this.input?.blur();
        },
        
        showLoading: function() {
            this.results.innerHTML = \'<div class="quick-search-loading"><i class="fas fa-spinner fa-spin"></i></div>\';
        },
        
        showEmpty: function() {
            this.results.innerHTML = \`
                <div class="quick-search-empty">
                    <p>Start typing to search</p>
                    <div class="search-hints">
                        <span><kbd>@</kbd> Users</span>
                        <span><kbd>#</kbd> Groups</span>
                        <span><kbd>!</kbd> Messages</span>
                    </div>
                </div>
            \`;
        },
        
        search: async function(query) {
            this.showLoading();
            
            try {
                const response = await fetch("api/global-search.php?q=" + encodeURIComponent(query) + "&limit=5");
                const data = await response.json();
                
                if (data.success) {
                    this.renderResults(data.data.results);
                } else {
                    this.showEmpty();
                }
            } catch (error) {
                console.error("Quick search error:", error);
                this.showEmpty();
            }
        },
        
        renderResults: function(results) {
            const { users, friends, groups, messages } = results;
            
            if (!users.length && !friends.length && !groups.length && !messages.length) {
                this.results.innerHTML = \'<div class="quick-search-empty"><p>No results found</p></div>\';
                return;
            }
            
            let html = "";
            
            // Friends first
            if (friends.length) {
                friends.slice(0, 3).forEach(friend => {
                    html += this.renderItem(friend, "chat.php?user=" + friend.id, "Friend");
                });
            }
            
            // Users
            if (users.length) {
                users.slice(0, 3).forEach(user => {
                    html += this.renderItem(user, "chat.php?user=" + user.id, "User");
                });
            }
            
            // Groups
            if (groups.length) {
                groups.slice(0, 2).forEach(group => {
                    html += this.renderGroupItem(group);
                });
            }
            
            this.results.innerHTML = html;
            
            // Add click handlers
            this.results.querySelectorAll(".quick-search-item").forEach(item => {
                item.addEventListener("click", () => {
                    window.location.href = item.dataset.url;
                });
            });
        },
        
        renderItem: function(user, url, type) {
            const avatar = user.avatar_url 
                ? \'<img src="\' + user.avatar_url + \'" alt="\' + user.username + \'">\'
                : \'<div class="avatar-initials">\' + user.initials + \'</div>\';
            
            return \`
                <div class="quick-search-item" data-url="\${url}">
                    <div class="quick-search-item-avatar">\${avatar}</div>
                    <div class="quick-search-item-info">
                        <div class="quick-search-item-name">\${this.escapeHtml(user.username)}</div>
                        <div class="quick-search-item-meta">\${user.bio ? this.escapeHtml(user.bio) : "No bio"}</div>
                    </div>
                    <span class="quick-search-item-type">\${type}</span>
                </div>
            \`;
        },
        
        renderGroupItem: function(group) {
            const avatar = group.avatar_url
                ? \'<img src="\' + group.avatar_url + \'" alt="\' + group.name + \'">\'
                : group.initials;
            
            return \`
                <div class="quick-search-item" data-url="group-chat.php?id=\${group.id}">
                    <div class="quick-search-item-avatar" style="border-radius: 8px;">\${avatar}</div>
                    <div class="quick-search-item-info">
                        <div class="quick-search-item-name">\${this.escapeHtml(group.name)}</div>
                        <div class="quick-search-item-meta">\${group.member_count_text}</div>
                    </div>
                    <span class="quick-search-item-type">Group</span>
                </div>
            \`;
        },
        
        escapeHtml: function(text) {
            if (!text) return "";
            const div = document.createElement("div");
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
    document.addEventListener("DOMContentLoaded", () => QuickSearch.init());
    </script>';
}
