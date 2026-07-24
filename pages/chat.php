<?php
/**
 * =====================================================
 * Chat Page
 * ChatApp - Personal Messenger
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

session_initialize();

if (!session_verify_security()) {
    app_session_destroy();
    header('Location: ../login.php');
    exit;
}

if (!session_is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$user_id = session_get_user_id();
$user_data = session_get_user_data();
$csrf_token = session_generate_csrf();

// Get selected user ID from URL
$selected_user_id = (int)($_GET['user_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Chat - ChatApp</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/chat.css" rel="stylesheet">
</head>
<body>
    <div class="chat-app">
        <!-- Left Sidebar - Chat List -->
        <aside class="chat-sidebar" id="chatSidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <div class="header-left">
                    <div class="user-avatar" id="currentUserAvatar">
                        <?php echo htmlspecialchars(substr($user_data['username'] ?? 'U', 0, 1)); ?>
                    </div>
                    <h2>Chats</h2>
                </div>
                <div class="header-actions">
                    <button class="icon-btn" id="newChatBtn" title="New Chat">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="icon-btn mobile-back" id="backToDashboard" title="Back to Dashboard">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="sidebar-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search or start new chat" id="chatSearchInput">
            </div>
            
            <!-- Chat List -->
            <div class="chat-list" id="chatList">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <span>Loading chats...</span>
                </div>
            </div>
        </aside>
        
        <!-- Right Side - Chat Window -->
        <main class="chat-window" id="chatWindow">
            <!-- Empty State (No chat selected) -->
            <div class="empty-chat" id="emptyChat">
                <div class="empty-chat-content">
                    <div class="empty-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h2>Welcome to ChatApp</h2>
                    <p>Select a chat from the sidebar to start messaging</p>
                </div>
            </div>
            
            <!-- Active Chat (Hidden by default) -->
            <div class="active-chat" id="activeChat" style="display: none;">
                <!-- Chat Header -->
                <div class="chat-header">
                    <button class="mobile-back-btn" id="backToList">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="chat-user-info" id="chatUserInfo">
                        <div class="user-avatar">
                            <span id="chatAvatar">U</span>
                            <span class="status-dot" id="chatStatusDot"></span>
                        </div>
                        <div class="user-details">
                            <h3 id="chatUsername">User</h3>
                            <span class="user-status" id="chatUserStatus">Offline</span>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <button class="icon-btn" id="searchChatBtn" title="Search Messages">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="icon-btn" id="chatMenuBtn" title="More Options">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Search Bar (Hidden by default) -->
                <div class="chat-search-bar" id="chatSearchBar" style="display: none;">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search messages..." id="messageSearchInput">
                        <button class="close-search" id="closeSearch">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="search-results-count" id="searchResultsCount"></div>
                </div>
                
                <!-- Messages Container -->
                <div class="messages-container" id="messagesContainer">
                    <div class="messages-wrapper" id="messagesWrapper">
                        <!-- Load More Button -->
                        <div class="load-more" id="loadMore" style="display: none;">
                            <button class="btn-load-more" id="loadMoreBtn">
                                <i class="fas fa-chevron-up"></i> Load earlier messages
                            </button>
                        </div>
                        
                        <!-- Messages will be loaded here -->
                        <div class="messages-list" id="messagesList"></div>
                        
                        <!-- Typing Indicator -->
                        <div class="typing-indicator" id="typingIndicator" style="display: none;">
                            <div class="typing-avatar">U</div>
                            <div class="typing-bubble">
                                <div class="typing-dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reply Preview (Hidden by default) -->
                <div class="reply-preview" id="replyPreview" style="display: none;">
                    <div class="reply-content">
                        <div class="reply-header">
                            <span class="reply-to" id="replyTo">Replying to</span>
                            <button class="close-reply" id="closeReply">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="reply-text" id="replyText"></div>
                    </div>
                </div>
                
                <!-- Message Input -->
                <div class="message-input-container">
                    <div class="input-actions-left">
                        <button class="icon-btn emoji-btn" id="emojiBtn" title="Emoji">
                            <i class="fas fa-smile"></i>
                        </button>
                    </div>
                    <div class="message-input-wrapper">
                        <textarea id="messageInput" placeholder="Type a message" rows="1"></textarea>
                    </div>
                    <div class="input-actions-right">
                        <button class="icon-btn send-btn" id="sendBtn" title="Send Message" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Emoji Picker (Hidden by default) -->
                <div class="emoji-picker" id="emojiPicker" style="display: none;">
                    <div class="emoji-grid" id="emojiGrid"></div>
                </div>
            </div>
        </main>
        
        <!-- Context Menu (Hidden by default) -->
        <div class="context-menu" id="contextMenu" style="display: none;">
            <button class="context-item" id="replyMenuItem">
                <i class="fas fa-reply"></i> Reply
            </button>
            <button class="context-item" id="copyMenuItem">
                <i class="fas fa-copy"></i> Copy
            </button>
            <button class="context-item delete-for-me" id="deleteForMeMenuItem">
                <i class="fas fa-trash"></i> Delete for me
            </button>
            <button class="context-item delete-for-everyone" id="deleteForEveryoneMenuItem">
                <i class="fas fa-trash-alt"></i> Delete for everyone
            </button>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/app.js"></script>
    <script>
        // Pass PHP variables to JavaScript
        const CHAT_CONFIG = {
            currentUserId: <?php echo $user_id; ?>,
            currentUserInitial: '<?php echo htmlspecialchars(substr($user_data['username'] ?? 'U', 0, 1)); ?>',
            selectedUserId: <?php echo $selected_user_id; ?>,
            csrfToken: '<?php echo $csrf_token; ?>'
        };
    </script>
    <script src="../assets/js/chat.js"></script>
</body>
</html>
