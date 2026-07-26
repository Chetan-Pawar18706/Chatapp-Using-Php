<?php
/**
 * =====================================================
 * Media Gallery Page
 * ChatApp - View & Manage Uploaded Files
 * =====================================================
 */

define('APP_RUNNING', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/media.php';
require_once __DIR__ . '/../includes/media_helpers.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/notification_component.php';

init_session();

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: ../login.php');
    exit;
}

$user_id = get_user_id();

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

// Get user's media
$media_query = "SELECT * FROM media WHERE user_id = ? ORDER BY created_at DESC";
$media_stmt = mysqli_prepare($conn, $media_query);
mysqli_stmt_bind_param($media_stmt, 'i', $user_id);
mysqli_stmt_execute($media_stmt);
$media_result = mysqli_stmt_get_result($media_stmt);
$media_files = [];

while ($row = mysqli_fetch_assoc($media_result)) {
    $row['file_size_formatted'] = format_file_size($row['file_size']);
    $row['icon'] = get_file_icon($row['category'], $row['file_extension']);
    $row['is_image'] = $row['category'] === 'images';
    $row['is_video'] = $row['category'] === 'videos';
    $media_files[] = $row;
}

// Get stats
$total_files = count($media_files);
$total_size = array_sum(array_column($media_files, 'file_size'));
$image_count = count(array_filter($media_files, fn($m) => $m['category'] === 'images'));
$video_count = count(array_filter($media_files, fn($m) => $m['category'] === 'videos'));
$doc_count = count(array_filter($media_files, fn($m) => $m['category'] === 'documents'));
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars(get_user_theme()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo session_generate_csrf(); ?>">
    <title>Media Gallery - ChatApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/media.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        .gallery-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .gallery-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .gallery-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .gallery-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border-color);
            text-align: center;
        }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 20px;
        }
        
        .stat-card .stat-icon.total {
            background: rgba(0, 123, 255, 0.15);
            color: #007bff;
        }
        
        .stat-card .stat-icon.size {
            background: rgba(111, 66, 193, 0.15);
            color: #6f42c1;
        }
        
        .stat-card .stat-icon.images {
            background: rgba(40, 167, 69, 0.15);
            color: #28a745;
        }
        
        .stat-card .stat-icon.videos {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }
        
        .stat-card .stat-icon.docs {
            background: rgba(253, 126, 20, 0.15);
            color: #fd7e14;
        }
        
        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .stat-card .stat-label {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .gallery-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .filter-btn:hover {
            background: var(--bg-tertiary);
        }
        
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .upload-section {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }
        
        .upload-section h4 {
            margin: 0 0 20px 0;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .media-section {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid var(--border-color);
        }
        
        .media-section h4 {
            margin: 0 0 20px 0;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .no-media {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
        
        .no-media i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .gallery-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .gallery-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .gallery-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php echo render_sidebar('media', $user, $user_id); ?>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-wrapper">
        <?php echo render_top_navbar('Media Gallery', $user, $user_id); ?>

        <main class="main-content">
        <div class="gallery-container">
            <!-- Header -->
            <div class="gallery-header">
                <h2><i class="fas fa-photo-film"></i> Media Gallery</h2>
                <button class="btn btn-primary" onclick="document.getElementById('globalFileInput').click()">
                    <i class="fas fa-upload"></i> Upload Files
                </button>
                <input type="file" id="globalFileInput" class="file-input" multiple 
                       accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.webm,.ogg,.mov,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.7z">
            </div>

            <!-- Stats -->
            <div class="gallery-stats">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-files"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_files; ?></div>
                    <div class="stat-label">Total Files</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon size">
                        <i class="fas fa-hdd"></i>
                    </div>
                    <div class="stat-value"><?php echo format_file_size($total_size); ?></div>
                    <div class="stat-label">Total Size</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon images">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-value"><?php echo $image_count; ?></div>
                    <div class="stat-label">Images</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon videos">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="stat-value"><?php echo $video_count; ?></div>
                    <div class="stat-label">Videos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon docs">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-value"><?php echo $doc_count; ?></div>
                    <div class="stat-label">Documents</div>
                </div>
            </div>

            <!-- Upload Section -->
            <div class="upload-section">
                <h4><i class="fas fa-cloud-upload-alt"></i> Upload New Files</h4>
                <?php echo render_media_upload_form(); ?>
            </div>

            <!-- Media Gallery -->
            <div class="media-section">
                <h4><i class="fas fa-th"></i> My Files</h4>
                
                <!-- Filters -->
                <div class="gallery-filters">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="images">
                        <i class="fas fa-image"></i> Images
                    </button>
                    <button class="filter-btn" data-filter="videos">
                        <i class="fas fa-video"></i> Videos
                    </button>
                    <button class="filter-btn" data-filter="documents">
                        <i class="fas fa-file"></i> Documents
                    </button>
                    <button class="filter-btn" data-filter="archives">
                        <i class="fas fa-archive"></i> Archives
                    </button>
                </div>
                
                <!-- Media Grid -->
                <?php if (!empty($media_files)): ?>
                    <div class="media-grid" id="mediaGrid">
                        <?php foreach ($media_files as $media): ?>
                            <?php echo render_media_grid_item($media); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-media">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h5>No files uploaded yet</h5>
                        <p>Upload your first file to get started</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/media.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <?php echo render_sidebar_scripts(); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.gallery-filters .filter-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.gallery-filters .filter-btn').forEach(function(b) { b.classList.remove('active'); });
                    this.classList.add('active');
                    var filter = this.dataset.filter;
                    var items = document.querySelectorAll('.media-grid-item');
                    items.forEach(function(item) {
                        if (filter === 'all') {
                            item.style.display = '';
                        } else {
                            var category = item.dataset.category || 
                                         (item.querySelector('img') ? 'images' : 
                                          item.querySelector('video') ? 'videos' : 'documents');
                            item.style.display = category === filter ? '' : 'none';
                        }
                    });
                });
            });
            
            document.getElementById('globalFileInput').addEventListener('change', function(e) {
                MediaModule.handleFiles(e.target.files);
                this.value = '';
            });
            
            MediaModule.onUploadComplete = function(data) {
                var grid = document.getElementById('mediaGrid');
                if (grid) {
                    var noMedia = document.querySelector('.no-media');
                    if (noMedia) noMedia.remove();
                }
            };
        });
    </script>
</body>
</html>
