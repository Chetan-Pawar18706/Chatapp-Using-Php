/**
 * =====================================================
 * Media Module JavaScript
 * ChatApp - File Upload & Management
 * =====================================================
 */

const MediaModule = {
    csrfToken: null,
    maxFileSize: 20 * 1024 * 1024, // 20 MB
    allowedExtensions: {
        images: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        videos: ['mp4', 'webm', 'ogg', 'mov'],
        documents: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        archives: ['zip', 'rar', '7z']
    },
    
    /**
     * Initialize Media Module
     */
    init: function() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.initDropZone();
        this.initFileInput();
        this.initLightbox();
    },
    
    /**
     * Initialize Drop Zone
     */
    initDropZone: function() {
        const dropZones = document.querySelectorAll('.upload-drop-zone');
        
        dropZones.forEach(zone => {
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                zone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                });
            });
            
            // Highlight drop zone
            ['dragenter', 'dragover'].forEach(eventName => {
                zone.addEventListener(eventName, () => {
                    zone.classList.add('drag-over');
                });
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                zone.addEventListener(eventName, () => {
                    zone.classList.remove('drag-over');
                });
            });
            
            // Handle dropped files
            zone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                this.handleFiles(files);
            });
            
            // Handle click to browse
            zone.addEventListener('click', () => {
                const input = zone.querySelector('.file-input') || document.getElementById('fileInput');
                if (input) input.click();
            });
        });
    },
    
    /**
     * Initialize File Input
     */
    initFileInput: function() {
        const inputs = document.querySelectorAll('.file-input');
        
        inputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleFiles(e.target.files);
                input.value = ''; // Reset input
            });
        });
    },
    
    /**
     * Initialize Lightbox
     */
    initLightbox: function() {
        // Create lightbox if it doesn't exist
        if (!document.querySelector('.media-lightbox')) {
            const lightbox = document.createElement('div');
            lightbox.className = 'media-lightbox';
            lightbox.innerHTML = `
                <div class="lightbox-content">
                    <button class="lightbox-close" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="lightbox-media"></div>
                    <div class="lightbox-actions">
                        <button class="action-btn download-btn" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button class="action-btn delete-btn" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(lightbox);
            
            // Close lightbox
            lightbox.querySelector('.lightbox-close').addEventListener('click', () => {
                this.closeLightbox();
            });
            
            lightbox.addEventListener('click', (e) => {
                if (e.target === lightbox) {
                    this.closeLightbox();
                }
            });
            
            // Download button
            lightbox.querySelector('.download-btn').addEventListener('click', () => {
                const mediaId = lightbox.dataset.mediaId;
                if (mediaId) this.downloadMedia(mediaId);
            });
            
            // Delete button
            lightbox.querySelector('.delete-btn').addEventListener('click', () => {
                const mediaId = lightbox.dataset.mediaId;
                if (mediaId && confirm('Are you sure you want to delete this file?')) {
                    this.deleteMedia(mediaId);
                }
            });
        }
    },
    
    /**
     * Handle Files
     */
    handleFiles: function(files) {
        Array.from(files).forEach(file => {
            this.validateAndUpload(file);
        });
    },
    
    /**
     * Validate and Upload File
     */
    validateAndUpload: function(file) {
        // Get file extension
        const extension = file.name.split('.').pop().toLowerCase();
        
        // Check if extension is allowed
        const category = this.getFileCategory(extension);
        if (!category) {
            this.showError('File type not allowed. Allowed: JPG, PNG, GIF, WEBP, MP4, PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP');
            return;
        }
        
        // Check file size
        const maxSize = this.getSizeLimit(category);
        if (file.size > maxSize) {
            this.showError(`File too large. Maximum size: ${this.formatSize(maxSize)}`);
            return;
        }
        
        // Upload file
        this.uploadFile(file, category);
    },
    
    /**
     * Get File Category
     */
    getFileCategory: function(extension) {
        for (const [category, extensions] of Object.entries(this.allowedExtensions)) {
            if (extensions.includes(extension)) {
                return category;
            }
        }
        return null;
    },
    
    /**
     * Get Size Limit for Category
     */
    getSizeLimit: function(category) {
        const limits = {
            images: 10 * 1024 * 1024,    // 10 MB
            videos: 20 * 1024 * 1024,    // 20 MB
            documents: 20 * 1024 * 1024, // 20 MB
            archives: 20 * 1024 * 1024   // 20 MB
        };
        return limits[category] || this.maxFileSize;
    },
    
    /**
     * Upload File
     */
    uploadFile: function(file, category) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('csrf_token', this.csrfToken);
        
        // Add receiver or group ID if available
        const receiverId = document.querySelector('[name="receiver_id"]')?.value;
        const groupId = document.querySelector('[name="group_id"]')?.value;
        
        if (receiverId) formData.append('receiver_id', receiverId);
        if (groupId) formData.append('group_id', groupId);
        
        // Create progress element
        const progressId = 'upload-' + Date.now();
        const progressEl = this.createProgressElement(progressId, file, category);
        
        // Get progress container
        const progressContainer = document.querySelector('.upload-progress-container');
        if (progressContainer) {
            progressContainer.classList.add('active');
            progressContainer.appendChild(progressEl);
        }
        
        // Create XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                this.updateProgress(progressId, percent);
            }
        });
        
        // Handle upload complete
        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 300) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    this.onUploadSuccess(progressId, response.data);
                } else {
                    this.onUploadError(progressId, response.message);
                }
            } else {
                this.onUploadError(progressId, 'Upload failed');
            }
        });
        
        // Handle upload error
        xhr.addEventListener('error', () => {
            this.onUploadError(progressId, 'Network error occurred');
        });
        
        // Handle upload abort
        xhr.addEventListener('abort', () => {
            this.onUploadError(progressId, 'Upload cancelled');
        });
        
        // Store XHR for cancellation
        progressEl.dataset.xhr = xhr;
        
        // Send request
        xhr.open('POST', '../api/upload-media.php');
        xhr.send(formData);
    },
    
    /**
     * Create Progress Element
     */
    createProgressElement: function(id, file, category) {
        const div = document.createElement('div');
        div.className = 'upload-progress-item';
        div.id = id;
        
        const iconClass = this.getFileIcon(category);
        const iconBg = this.getIconBackground(category);
        
        div.innerHTML = `
            <div class="upload-progress-header">
                <div class="file-icon ${iconBg}">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="upload-progress-info">
                    <div class="file-name">${this.escapeHtml(file.name)}</div>
                    <div class="file-size">${this.formatSize(file.size)}</div>
                </div>
                <div class="upload-progress-actions">
                    <button class="btn-icon cancel" title="Cancel">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
            <div class="upload-progress-status">
                <span class="status-text">Uploading...</span>
                <span class="percent-text">0%</span>
            </div>
        `;
        
        // Add cancel handler
        div.querySelector('.cancel').addEventListener('click', () => {
            this.cancelUpload(id);
        });
        
        return div;
    },
    
    /**
     * Get File Icon
     */
    getFileIcon: function(category) {
        const icons = {
            images: 'fa-file-image',
            videos: 'fa-file-video',
            documents: 'fa-file',
            archives: 'fa-file-archive'
        };
        return icons[category] || 'fa-file';
    },
    
    /**
     * Get Icon Background Class
     */
    getIconBackground: function(category) {
        const classes = {
            images: 'image',
            videos: 'video',
            documents: 'document',
            archives: 'archive'
        };
        return classes[category] || 'document';
    },
    
    /**
     * Update Progress
     */
    updateProgress: function(id, percent) {
        const el = document.getElementById(id);
        if (!el) return;
        
        const progressBar = el.querySelector('.progress-bar');
        const percentText = el.querySelector('.percent-text');
        
        if (progressBar) progressBar.style.width = percent + '%';
        if (percentText) percentText.textContent = percent + '%';
    },
    
    /**
     * Cancel Upload
     */
    cancelUpload: function(id) {
        const el = document.getElementById(id);
        if (!el) return;
        
        const xhr = el.dataset.xhr;
        if (xhr) xhr.abort();
        
        el.remove();
    },
    
    /**
     * On Upload Success
     */
    onUploadSuccess: function(id, data) {
        const el = document.getElementById(id);
        if (!el) return;
        
        el.classList.add('complete');
        
        const statusText = el.querySelector('.status-text');
        const percentText = el.querySelector('.percent-text');
        const cancelBtn = el.querySelector('.cancel');
        
        if (statusText) statusText.textContent = 'Upload complete';
        if (percentText) percentText.textContent = '100%';
        if (cancelBtn) cancelBtn.style.display = 'none';
        
        // Remove after delay
        setTimeout(() => {
            el.remove();
            this.checkProgressContainer();
        }, 2000);
        
        // Trigger callback if available
        if (typeof this.onUploadComplete === 'function') {
            this.onUploadComplete(data);
        }
        
        // Add to media grid if exists
        this.addToMediaGrid(data);
    },
    
    /**
     * On Upload Error
     */
    onUploadError: function(id, message) {
        const el = document.getElementById(id);
        if (!el) return;
        
        el.classList.add('error');
        
        const statusText = el.querySelector('.status-text');
        const percentText = el.querySelector('.percent-text');
        const cancelBtn = el.querySelector('.cancel');
        
        if (statusText) statusText.textContent = message;
        if (percentText) percentText.textContent = 'Failed';
        if (cancelBtn) cancelBtn.innerHTML = '<i class="fas fa-trash"></i>';
        
        this.showError(message);
    },
    
    /**
     * Check Progress Container
     */
    checkProgressContainer: function() {
        const container = document.querySelector('.upload-progress-container');
        if (container && container.children.length === 0) {
            container.classList.remove('active');
        }
    },
    
    /**
     * Add to Media Grid
     */
    addToMediaGrid: function(data) {
        const grid = document.querySelector('.media-grid');
        if (!grid) return;
        
        const item = document.createElement('div');
        item.className = 'media-grid-item';
        item.dataset.id = data.id;
        
        if (data.is_image) {
            item.innerHTML = `
                <img src="../api/preview-media.php?id=${data.id}" alt="${this.escapeHtml(data.original_name)}">
                <div class="file-info">
                    <div class="file-name">${this.escapeHtml(data.original_name)}</div>
                    <div class="file-size">${data.file_size_formatted}</div>
                </div>
            `;
        } else if (data.is_video) {
            item.innerHTML = `
                <video class="video-thumbnail" preload="metadata">
                    <source src="../api/preview-media.php?id=${data.id}" type="${data.file_type}">
                </video>
                <div class="play-overlay">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="file-info">
                    <div class="file-name">${this.escapeHtml(data.original_name)}</div>
                    <div class="file-size">${data.file_size_formatted}</div>
                </div>
            `;
        } else {
            item.innerHTML = `
                <div class="document-preview" style="height: 100%; border: none; border-radius: 0;">
                    <div class="doc-icon ${data.file_extension}">
                        <i class="fas ${data.icon}"></i>
                    </div>
                </div>
                <div class="file-info">
                    <div class="file-name">${this.escapeHtml(data.original_name)}</div>
                    <div class="file-size">${data.file_size_formatted}</div>
                </div>
            `;
        }
        
        // Add click handler
        item.addEventListener('click', () => {
            this.openLightbox(data);
        });
        
        grid.insertBefore(item, grid.firstChild);
    },
    
    /**
     * Open Lightbox
     */
    openLightbox: function(data) {
        const lightbox = document.querySelector('.media-lightbox');
        if (!lightbox) return;
        
        lightbox.dataset.mediaId = data.id;
        
        const mediaContainer = lightbox.querySelector('.lightbox-media');
        
        if (data.is_image) {
            mediaContainer.innerHTML = `<img src="../api/preview-media.php?id=${data.id}" alt="${this.escapeHtml(data.original_name)}">`;
        } else if (data.is_video) {
            mediaContainer.innerHTML = `
                <video controls autoplay>
                    <source src="../api/preview-media.php?id=${data.id}" type="${data.file_type}">
                    Your browser does not support video playback.
                </video>
            `;
        }
        
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    },
    
    /**
     * Close Lightbox
     */
    closeLightbox: function() {
        const lightbox = document.querySelector('.media-lightbox');
        if (!lightbox) return;
        
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
        
        // Stop video if playing
        const video = lightbox.querySelector('video');
        if (video) video.pause();
    },
    
    /**
     * Download Media
     */
    downloadMedia: function(id) {
        window.open(`../api/download-media.php?id=${id}`, '_blank');
    },
    
    /**
     * Delete Media
     */
    deleteMedia: function(id) {
        fetch('../api/delete-media.php?id=' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `csrf_token=${this.csrfToken}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove from grid
                const item = document.querySelector(`.media-grid-item[data-id="${id}"]`);
                if (item) item.remove();
                
                // Close lightbox
                this.closeLightbox();
                
                this.showSuccess('File deleted successfully');
            } else {
                this.showError(data.message);
            }
        })
        .catch(error => {
            this.showError('Failed to delete file');
        });
    },
    
    /**
     * Format File Size
     */
    formatSize: function(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return size.toFixed(2) + ' ' + units[unitIndex];
    },
    
    /**
     * Escape HTML
     */
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    /**
     * Show Error Message
     */
    showError: function(message) {
        if (typeof showToast === 'function') {
            showToast(message, 'error');
        } else {
            alert('Error: ' + message);
        }
    },
    
    /**
     * Show Success Message
     */
    showSuccess: function(message) {
        if (typeof showToast === 'function') {
            showToast(message, 'success');
        }
    },
    
    /**
     * Upload Complete Callback (to be overridden)
     */
    onUploadComplete: null
};

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    MediaModule.init();
});
