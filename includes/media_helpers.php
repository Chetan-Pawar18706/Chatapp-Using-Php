<?php
/**
 * =====================================================
 * Media Upload Form Component
 * ChatApp - Reusable Upload Form
 * =====================================================
 */

// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

/**
 * Render Media Upload Form
 * 
 * @param int $receiver_id Receiver user ID (for personal chat)
 * @param int $group_id Group ID (for group chat)
 * @return string HTML output
 */
function render_media_upload_form($receiver_id = 0, $group_id = 0) {
    $csrf_token = session_generate_csrf();
    
    $html = '
    <div class="media-upload-container">
        <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">';
    
    if ($receiver_id > 0) {
        $html .= '<input type="hidden" name="receiver_id" value="' . intval($receiver_id) . '">';
    }
    
    if ($group_id > 0) {
        $html .= '<input type="hidden" name="group_id" value="' . intval($group_id) . '">';
    }
    
    $html .= '
        <div class="upload-drop-zone" id="uploadDropZone">
            <input type="file" class="file-input" id="fileInput" 
                   accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.mp4,.webm,.ogg,.mov,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.7z"
                   multiple>
            <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <h5>Drag & Drop files here</h5>
            <p>or click to browse</p>
            <span class="browse-btn">Browse Files</span>
            <div class="file-types">
                <strong>Supported:</strong> JPG, PNG, GIF, WEBP, MP4, PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP<br>
                <strong>Max Size:</strong> 20 MB
            </div>
        </div>
        
        <div class="upload-progress-container" id="uploadProgress"></div>
    </div>';
    
    return $html;
}

/**
 * Render Media Gallery Grid
 * 
 * @param array $media Array of media items
 * @return string HTML output
 */
function render_media_grid($media = []) {
    if (empty($media)) {
        return '<div class="no-media text-center py-4"><p class="text-muted">No files yet</p></div>';
    }
    
    $html = '<div class="media-grid">';
    
    foreach ($media as $item) {
        $html .= render_media_grid_item($item);
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render Single Media Grid Item
 * 
 * @param array $media Media item data
 * @return string HTML output
 */
function render_media_grid_item($media) {
    $id = intval($media['id']);
    $name = htmlspecialchars($media['original_name']);
    $size = htmlspecialchars($media['file_size_formatted'] ?? format_file_size($media['file_size']));
    $category = $media['category'];
    $extension = $media['file_extension'];
    $icon = get_file_icon($category, $extension);
    
    $html = '<div class="media-grid-item" data-id="' . $id . '" onclick="MediaModule.openLightbox(' . htmlspecialchars(json_encode($media)) . ')">';
    
    if ($media['is_image']) {
        $html .= '<img src="/chatapp/api/preview-media.php?id=' . $id . '" alt="' . $name . '" loading="lazy">';
    } elseif ($media['is_video']) {
        $html .= '
            <video class="video-thumbnail" preload="metadata">
                <source src="/chatapp/api/preview-media.php?id=' . $id . '" type="' . htmlspecialchars($media['file_type']) . '">
            </video>
            <div class="play-overlay">
                <i class="fas fa-play-circle"></i>
            </div>';
    } else {
        $html .= '
            <div class="document-preview" style="height: 100%; border: none; border-radius: 0;">
                <div class="doc-icon ' . $extension . '">
                    <i class="fas ' . $icon . '"></i>
                </div>
            </div>';
    }
    
    $html .= '
        <div class="file-info">
            <div class="file-name">' . $name . '</div>
            <div class="file-size">' . $size . '</div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Render Document Attachment for Chat
 * 
 * @param array $media Media item data
 * @return string HTML output
 */
function render_chat_document_attachment($media) {
    $id = intval($media['id']);
    $name = htmlspecialchars($media['original_name']);
    $size = htmlspecialchars($media['file_size_formatted'] ?? format_file_size($media['file_size']));
    $extension = $media['file_extension'];
    $icon = get_file_icon($media['category'], $extension);
    
    $iconBg = 'default';
    if (in_array($extension, ['pdf'])) $iconBg = 'pdf';
    elseif (in_array($extension, ['doc', 'docx'])) $iconBg = 'word';
    elseif (in_array($extension, ['xls', 'xlsx'])) $iconBg = 'excel';
    elseif (in_array($extension, ['ppt', 'pptx'])) $iconBg = 'powerpoint';
    elseif (in_array($extension, ['zip', 'rar', '7z'])) $iconBg = 'archive';
    
    return '
    <div class="document-attachment">
        <div class="doc-icon ' . $iconBg . '">
            <i class="fas ' . $icon . '"></i>
        </div>
        <div class="doc-info">
            <div class="doc-name">' . $name . '</div>
            <div class="doc-size">' . $size . '</div>
        </div>
        <button class="download-btn" onclick="event.stopPropagation(); MediaModule.downloadMedia(' . $id . ')" title="Download">
            <i class="fas fa-download"></i>
        </button>
    </div>';
}

/**
 * Render Image Attachment for Chat
 * 
 * @param array $media Media item data
 * @return string HTML output
 */
function render_chat_image_attachment($media) {
    $id = intval($media['id']);
    $name = htmlspecialchars($media['original_name']);
    
    return '<img src="/chatapp/api/preview-media.php?id=' . $id . '" alt="' . $name . '" loading="lazy" onclick="MediaModule.openLightbox(' . htmlspecialchars(json_encode($media)) . ')">';
}

/**
 * Render Video Attachment for Chat
 * 
 * @param array $media Media item data
 * @return string HTML output
 */
function render_chat_video_attachment($media) {
    $id = intval($media['id']);
    $type = htmlspecialchars($media['file_type']);
    
    return '
    <video controls preload="metadata">
        <source src="/chatapp/api/preview-media.php?id=' . $id . '" type="' . $type . '">
        Your browser does not support video playback.
    </video>';
}

/**
 * Render Media Attachment for Chat Message
 * 
 * @param array $media Media item data
 * @return string HTML output
 */
function render_chat_media_attachment($media) {
    if (!$media) return '';
    
    $html = '<div class="media-attachment">';
    
    if ($media['is_image']) {
        $html .= render_chat_image_attachment($media);
    } elseif ($media['is_video']) {
        $html .= render_chat_video_attachment($media);
    } else {
        $html .= render_chat_document_attachment($media);
    }
    
    $html .= '</div>';
    
    return $html;
}
