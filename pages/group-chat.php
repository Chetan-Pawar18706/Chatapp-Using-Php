<?php
/**
 * =====================================================
 * Group Chat Page
 * ChatApp - Group Messenger
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sidebar.php';

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

$group_id = (int)($_GET['group_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(get_user_theme()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Group Chat - ChatApp</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/chat.css" rel="stylesheet">
    <link href="../assets/css/groups.css" rel="stylesheet">
</head>
<body>
    <?php echo render_sidebar('group-chat', $user_data, $user_id); ?>
    <div class="chat-app">
        <!-- Left Sidebar - Groups List -->
        <aside class="chat-sidebar" id="chatSidebar">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <div class="header-left">
                    <button class="icon-btn mobile-back" id="backToDashboard" title="Back to Dashboard">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h2>Groups</h2>
                </div>
                <div class="header-actions">
                    <button class="icon-btn" id="lockedChatsBtn" title="Locked Chats">
                        <i class="fas fa-lock"></i>
                    </button>
                    <button class="icon-btn" id="createGroupBtn" title="Create Group">
                        <i class="fas fa-users-group"></i>
                    </button>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="sidebar-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search groups" id="groupSearchInput">
            </div>
            
            <!-- Groups List -->
            <div class="chat-list" id="groupsList">
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <span>Loading groups...</span>
                </div>
            </div>
        </aside>
        
        <!-- Right Side - Group Chat Window -->
        <main class="chat-window" id="chatWindow">
            <!-- Empty State -->
            <div class="empty-chat" id="emptyChat">
                <div class="empty-chat-content">
                    <div class="empty-icon">
                        <i class="fas fa-users-group"></i>
                    </div>
                    <h2>Group Chat</h2>
                    <p>Select a group or create a new one</p>
                    <button class="btn btn-primary mt-3" id="createGroupBtnEmpty">
                        <i class="fas fa-plus"></i> Create Group
                    </button>
                </div>
            </div>
            
            <!-- Active Group Chat -->
            <div class="active-chat" id="activeChat" style="display: none;">
                <!-- Chat Header -->
                <div class="chat-header">
                    <button class="mobile-back-btn" id="backToList">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div class="chat-user-info" id="chatUserInfo">
                        <div class="user-avatar group-avatar">
                            <span id="chatAvatar">G</span>
                        </div>
                        <div class="user-details">
                            <h3 id="chatGroupName">Group</h3>
                            <span class="user-status" id="chatGroupMembers">0 members</span>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <button class="icon-btn" id="searchChatBtn" title="Search Messages">
                            <i class="fas fa-search"></i>
                        </button>
                        <div class="chat-menu-wrapper">
                            <button class="icon-btn" id="chatMenuBtn" title="More Options">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="chat-dropdown-menu" id="chatDropdownMenu" style="display: none;">
                                <button class="dropdown-item" id="menuGroupInfo">
                                    <i class="fas fa-info-circle"></i> Group Info
                                </button>
                                <button class="dropdown-item" id="menuSearchMessages">
                                    <i class="fas fa-search"></i> Search Messages
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item" id="menuLockChat">
                                    <i class="fas fa-lock"></i> Lock Chat
                                </button>
                                <button class="dropdown-item" id="menuUnlockChat" style="display: none;">
                                    <i class="fas fa-unlock"></i> Unlock Chat
                                </button>
                                <div class="dropdown-divider"></div>
                                <button class="dropdown-item danger" id="menuLeaveGroup">
                                    <i class="fas fa-right-from-bracket"></i> Leave Group
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Search Bar -->
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
                        <div class="load-more" id="loadMore" style="display: none;">
                            <button class="btn-load-more" id="loadMoreBtn">
                                <i class="fas fa-chevron-up"></i> Load earlier messages
                            </button>
                        </div>
                        <div class="messages-list" id="messagesList"></div>
                    </div>
                </div>
                
                <!-- Reply Preview -->
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
                        <button class="icon-btn attach-btn" id="attachBtn" title="Attach file">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <input type="file" id="fileInput" class="hidden-file-input" 
                               accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.ogg,.mov,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.7z">
                        <div class="auto-delete-wrapper">
                            <button class="icon-btn auto-delete-btn" id="autoDeleteBtn" title="Auto-delete timer">
                                <i class="fas fa-clock"></i>
                            </button>
                            <div class="auto-delete-dropdown" id="autoDeleteDropdown" style="display: none;">
                                <button class="auto-delete-option active" data-value="none">
                                    <i class="fas fa-infinity"></i> Keep forever
                                </button>
                                <button class="auto-delete-option" data-value="24hours">
                                    <i class="fas fa-clock"></i> 24 hours
                                </button>
                                <button class="auto-delete-option" data-value="7days">
                                    <i class="fas fa-clock"></i> 7 days
                                </button>
                                <button class="auto-delete-option" data-value="30days">
                                    <i class="fas fa-clock"></i> 30 days
                                </button>
                            </div>
                        </div>
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
                
                <!-- File Preview (Hidden by default) -->
                <div class="file-preview-bar" id="filePreviewBar" style="display: none;">
                    <div class="file-preview-content" id="filePreviewContent"></div>
                    <button class="file-preview-close" id="filePreviewClose">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Emoji Picker -->
                <div class="emoji-picker" id="emojiPicker" style="display: none;">
                    <div class="emoji-grid" id="emojiGrid"></div>
                </div>
            </div>
        </main>
        
        <!-- Group Info Panel -->
        <div class="group-info-panel" id="groupInfoPanel" style="display: none;">
            <div class="panel-header">
                <h3>Group Info</h3>
                <button class="icon-btn" id="closeGroupInfo">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="panel-content" id="groupInfoContent">
                <!-- Group info will be loaded here -->
            </div>
        </div>
        
        <!-- Context Menu -->
        <div class="context-menu" id="contextMenu" style="display: none;">
            <button class="context-item" id="replyMenuItem">
                <i class="fas fa-reply"></i> Reply
            </button>
            <button class="context-item" id="copyMenuItem">
                <i class="fas fa-copy"></i> Copy
            </button>
            <button class="context-item delete-for-me" id="deleteMenuItem">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
    
    <!-- Create Group Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Group</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createGroupForm">
                        <div class="form-group mb-3">
                            <label for="groupName">Group Name *</label>
                            <input type="text" class="form-control" id="groupName" 
                                   placeholder="Enter group name" required maxlength="100">
                        </div>
                        <div class="form-group mb-3">
                            <label for="groupDescription">Description</label>
                            <textarea class="form-control" id="groupDescription" 
                                      placeholder="What's this group about?" rows="3" maxlength="500"></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label>Invite Friends</label>
                            <div class="friends-checkbox-list" id="friendsCheckboxList">
                                <!-- Friends will be loaded here -->
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="createGroupSubmit">
                        <i class="fas fa-users-group"></i> Create Group
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Invite Members Modal -->
    <div class="modal fade" id="inviteMembersModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invite Members</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select friends to invite</label>
                        <div class="friends-checkbox-list" id="inviteFriendsList">
                            <!-- Friends will be loaded here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="inviteMembersSubmit">
                        <i class="fas fa-user-plus"></i> Invite
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <!-- Chat Lock Modal -->
    <div class="modal" id="chatLockModal">
        <div class="modal-overlay" onclick="GroupChatLock.closeModal()"></div>
        <div class="modal-content">
            <button class="modal-close" onclick="GroupChatLock.closeModal()">&times;</button>
            <h3><i class="fas fa-lock"></i> Chat Locked</h3>
            <p>Enter password to unlock this chat</p>
            <input type="password" id="chatLockPassword" class="form-control" placeholder="Enter password" 
                   onkeypress="if(event.key==='Enter') GroupChatLock.verify()">
            <div class="secret-actions">
                <button class="btn btn-secondary" onclick="GroupChatLock.closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="GroupChatLock.verify()">
                    <i class="fas fa-unlock"></i> Unlock
                </button>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/app.js"></script>
    <script>
        const GROUP_CONFIG = {
            currentUserId: <?php echo $user_id; ?>,
            currentUserInitial: '<?php echo htmlspecialchars(substr($user_data['username'] ?? 'U', 0, 1)); ?>',
            selectedGroupId: <?php echo $group_id; ?>,
            csrfToken: '<?php echo $csrf_token; ?>'
        };
    </script>
    <script src="../assets/js/group-chat.js"></script>
</body>
</html>
