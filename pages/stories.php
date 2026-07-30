<?php
/**
 * =====================================================
 * Stories Page
 * ChatApp - View and Create Stories
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/notification_component.php';

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

// Get user avatar for story creation
$avatar = $user_data['avatar'] ?? null;
$username = $user_data['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(get_user_theme()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <title>Stories - ChatApp</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <style>
        .stories-page {
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .stories-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .stories-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .create-story-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .create-story-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .stories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .story-card {
            position: relative;
            aspect-ratio: 9/16;
            border-radius: var(--radius-md);
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s;
            background: var(--bg-tertiary);
        }
        
        .story-card:hover {
            transform: scale(1.02);
        }
        
        .story-card.own-story {
            border: 2px dashed var(--border-color);
        }
        
        .story-card .story-bg {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            text-align: center;
        }
        
        .story-card .story-media {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .story-card .story-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 0.75rem;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
        }
        
        .story-card .story-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .story-card .story-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }
        
        .story-card .story-username {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .story-card .story-time {
            font-size: 0.7rem;
            opacity: 0.8;
        }
        
        .story-card .story-ring {
            position: absolute;
            top: 8px;
            left: 8px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            padding: 2px;
        }
        
        .story-card .story-ring img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .story-card .viewed {
            border-color: var(--text-muted);
        }
        
        .story-card .add-story {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            gap: 0.5rem;
            color: var(--text-secondary);
        }
        
        .story-card .add-story i {
            font-size: 2rem;
            color: var(--primary);
        }
        
        .story-card .add-story span {
            font-size: 0.85rem;
        }
        
        .story-card .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .story-card:hover .delete-btn {
            opacity: 1;
        }
        
        /* Create Story Modal */
        .story-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .story-modal.active {
            display: flex;
        }
        
        .story-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
        }
        
        .story-modal-content {
            position: relative;
            width: 90%;
            max-width: 400px;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            overflow: hidden;
            z-index: 1;
        }
        
        .story-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .story-modal-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .story-modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .story-modal-body {
            padding: 1rem;
        }
        
        .story-type-toggle {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .story-type-btn {
            flex: 1;
            padding: 0.6rem;
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-secondary);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .story-type-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .story-text-input {
            width: 100%;
            min-height: 120px;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-input);
            color: var(--text-primary);
            font-size: 1rem;
            resize: none;
            margin-bottom: 1rem;
        }
        
        .story-color-picker {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .color-option {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .color-option:hover,
        .color-option.active {
            border-color: white;
            transform: scale(1.1);
        }
        
        .story-upload-area {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }
        
        .story-upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-bg);
        }
        
        .story-upload-area i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .story-upload-area p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .story-preview {
            width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            display: none;
        }
        
        .story-modal-footer {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-cancel {
            flex: 1;
            padding: 0.6rem;
            border: 1px solid var(--border-color);
            background: transparent;
            color: var(--text-secondary);
            border-radius: var(--radius-md);
            cursor: pointer;
        }
        
        .btn-post {
            flex: 1;
            padding: 0.6rem;
            border: none;
            background: var(--primary);
            color: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-post:hover {
            background: var(--primary-hover);
        }
        
        .btn-post:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Story Viewer Modal */
        .story-viewer {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 3000;
            background: rgba(0,0,0,0.95);
            align-items: center;
            justify-content: center;
        }
        
        .story-viewer.active {
            display: flex;
        }
        
        .story-viewer-container {
            position: relative;
            width: 100%;
            max-width: 420px;
            height: 90vh;
            max-height: 750px;
            background: #000;
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        
        .story-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            display: flex;
            gap: 4px;
            padding: 8px 12px;
            z-index: 10;
        }
        
        .story-progress-segment {
            flex: 1;
            height: 3px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .story-progress-fill {
            height: 100%;
            background: white;
            width: 0%;
            transition: width 0.1s linear;
        }
        
        .story-progress-segment.completed .story-progress-fill {
            width: 100%;
        }
        
        .story-progress-segment.active .story-progress-fill {
            width: 0%;
            animation: storyProgress 5s linear forwards;
        }
        
        @keyframes storyProgress {
            to { width: 100%; }
        }
        
        .story-viewer-header {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 10;
        }
        
        .story-viewer-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
        }
        
        .story-viewer-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .story-viewer-name {
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .story-viewer-time {
            font-size: 0.75rem;
            opacity: 0.7;
        }
        
        .story-viewer-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.8;
        }
        
        .story-viewer-close:hover {
            opacity: 1;
        }
        
        .story-viewer-content {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .story-viewer-content img,
        .story-viewer-content video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .story-viewer-text {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
            color: white;
            font-size: 1.2rem;
            line-height: 1.5;
        }
        
        .story-viewer-footer {
            position: absolute;
            bottom: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 10;
        }
        
        .story-viewers-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-size: 0.85rem;
        }
        
        .story-nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .story-nav-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .story-nav-prev {
            left: 8px;
        }
        
        .story-nav-next {
            right: 8px;
        }
        
        .no-stories {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }
        
        .no-stories i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }
    </style>
</head>
<body>
    <?php echo render_sidebar('stories', $user_data, $user_id); ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="main-wrapper">
        <?php echo render_top_navbar('Stories', $user_id); ?>
        
        <main class="main-content">
            <div class="stories-page">
                <div class="stories-header">
                    <h2><i class="fas fa-circle-play"></i> Stories</h2>
                    <button class="create-story-btn" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create Story
                    </button>
                </div>
                
                <div id="storiesContainer">
                    <div class="no-stories">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading stories...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Create Story Modal -->
    <div class="story-modal" id="createStoryModal">
        <div class="story-modal-overlay" onclick="closeCreateModal()"></div>
        <div class="story-modal-content">
            <div class="story-modal-header">
                <h3>Create Story</h3>
                <button class="story-modal-close" onclick="closeCreateModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="story-modal-body">
                <div class="story-type-toggle">
                    <button class="story-type-btn active" data-type="text" onclick="switchStoryType('text')">
                        <i class="fas fa-font"></i> Text
                    </button>
                    <button class="story-type-btn" data-type="media" onclick="switchStoryType('media')">
                        <i class="fas fa-image"></i> Photo/Video
                    </button>
                </div>
                
                <div id="textStorySection">
                    <textarea class="story-text-input" id="storyText" placeholder="What's on your mind?"></textarea>
                    <div class="story-color-picker" id="colorPicker">
                        <div class="color-option active" style="background: #6366f1" data-bg="#6366f1" data-color="#ffffff"></div>
                        <div class="color-option" style="background: #ec4899" data-bg="#ec4899" data-color="#ffffff"></div>
                        <div class="color-option" style="background: #f59e0b" data-bg="#f59e0b" data-color="#000000"></div>
                        <div class="color-option" style="background: #22c55e" data-bg="#22c55e" data-color="#ffffff"></div>
                        <div class="color-option" style="background: #3b82f6" data-bg="#3b82f6" data-color="#ffffff"></div>
                        <div class="color-option" style="background: #1e293b" data-bg="#1e293b" data-color="#ffffff"></div>
                    </div>
                </div>
                
                <div id="mediaStorySection" style="display: none;">
                    <div class="story-upload-area" id="storyUploadArea" onclick="document.getElementById('storyFileInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload photo or video</p>
                        <small>Max 10MB</small>
                    </div>
                    <input type="file" id="storyFileInput" accept="image/*,video/*" style="display: none;" onchange="previewStoryMedia(this)">
                    <img class="story-preview" id="storyPreview">
                </div>
            </div>
            <div class="story-modal-footer">
                <button class="btn-cancel" onclick="closeCreateModal()">Cancel</button>
                <button class="btn-post" id="postStoryBtn" onclick="postStory()">Share Story</button>
            </div>
        </div>
    </div>
    
    <!-- Story Viewer Modal -->
    <div class="story-viewer" id="storyViewer">
        <div class="story-viewer-container">
            <div class="story-progress-bar" id="storyProgressBar"></div>
            <div class="story-viewer-header">
                <div class="story-viewer-user">
                    <img class="story-viewer-avatar" id="viewerAvatar" src="" alt="">
                    <div>
                        <div class="story-viewer-name" id="viewerName"></div>
                        <div class="story-viewer-time" id="viewerTime"></div>
                    </div>
                </div>
                <button class="story-viewer-close" onclick="closeStoryViewer()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="story-viewer-content" id="storyContent"></div>
            <div class="story-viewer-footer">
                <div class="story-viewers-count" id="viewersCount">
                    <i class="fas fa-eye"></i> <span>0</span> views
                </div>
            </div>
            <button class="story-nav-btn story-nav-prev" onclick="prevStory()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="story-nav-btn story-nav-next" onclick="nextStory()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
    
    <script src="../assets/js/app.js"></script>
    <script>
        const STORIES_CONFIG = {
            userId: <?php echo $user_id; ?>,
            csrfToken: '<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>',
            avatar: '<?php echo htmlspecialchars($avatar ?? '', ENT_QUOTES, 'UTF-8'); ?>',
            username: '<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>'
        };
    </script>
    <script src="../assets/js/stories.js"></script>
</body>
</html>
