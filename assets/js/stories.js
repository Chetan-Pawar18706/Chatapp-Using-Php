/**
 * Stories Module
 * ChatApp - Instagram-style Stories
 */

const StoriesModule = {
    stories: [],
    currentStoryGroup: null,
    currentStoryIndex: 0,
    storyTimer: null,
    storyDuration: 5000,
    selectedBgColor: '#6366f1',
    selectedTextColor: '#ffffff',
    storyType: 'text',
    selectedFile: null,

    async init() {
        await this.loadStories();
    },

    async loadStories() {
        const result = await ChatApp.apiRequest('/get-stories.php', 'GET');
        if (result.success) {
            this.stories = result.data.stories;
            this.renderStories();
        }
    },

    renderStories() {
        const container = document.getElementById('storiesContainer');
        if (!container) return;

        if (this.stories.length === 0) {
            container.innerHTML = `
                <div class="no-stories">
                    <i class="fas fa-circle-play"></i>
                    <p>No stories yet</p>
                    <small>Be the first to share a story!</small>
                </div>
            `;
            return;
        }

        let html = '<div class="stories-grid">';

        this.stories.forEach((group, index) => {
            const hasUnviewed = group.has_unviewed;
            const ringClass = hasUnviewed ? '' : 'viewed';
            const displayName = group.is_own ? 'Your Story' : group.username;
            const avatarSrc = group.avatar || '';
            const latestStory = group.stories[group.stories.length - 1];
            const timeAgo = this.getTimeAgo(latestStory.created_at);

            html += `
                <div class="story-card" onclick="StoriesModule.openStory(${index})">
                    ${latestStory.media_type === 'image' ? 
                        `<img class="story-media" src="../${latestStory.media_path}" alt="Story">` :
                        latestStory.media_type === 'video' ?
                        `<video class="story-media" muted><source src="../${latestStory.media_path}"></video>` :
                        `<div class="story-bg" style="background-color: ${latestStory.bg_color}; color: ${latestStory.text_color}; font-size: ${latestStory.font_size}px;">
                            ${latestStory.content}
                        </div>`
                    }
                    <div class="story-ring ${ringClass}">
                        ${avatarSrc ? `<img src="${avatarSrc}" alt="${group.username}">` : `<div style="width:100%;height:100%;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;">${(group.username || 'U')[0].toUpperCase()}</div>`}
                    </div>
                    ${group.is_own ? `
                        <button class="delete-btn" onclick="event.stopPropagation(); StoriesModule.deleteStory(${latestStory.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                    <div class="story-overlay">
                        <div class="story-username">${displayName}</div>
                        <div class="story-time">${group.stories.length} story • ${timeAgo}</div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    },

    openStory(groupIndex) {
        this.currentStoryGroup = this.stories[groupIndex];
        this.currentStoryIndex = 0;
        this.showStory();
        document.getElementById('storyViewer').classList.add('active');
    },

    showStory() {
        if (!this.currentStoryGroup) return;
        
        const story = this.currentStoryGroup.stories[this.currentStoryIndex];
        if (!story) {
            this.closeStoryViewer();
            return;
        }

        // Update progress bar
        this.renderProgressBar();

        // Update header
        document.getElementById('viewerAvatar').src = this.currentStoryGroup.avatar || '';
        document.getElementById('viewerName').textContent = this.currentStoryGroup.is_own ? 'Your Story' : this.currentStoryGroup.username;
        document.getElementById('viewerTime').textContent = this.getTimeAgo(story.created_at);

        // Update content
        const contentEl = document.getElementById('storyContent');
        if (story.media_type === 'image') {
            contentEl.innerHTML = `<img src="../${story.media_path}" alt="Story">`;
        } else if (story.media_type === 'video') {
            contentEl.innerHTML = `<video autoplay controls><source src="../${story.media_path}"></video>`;
        } else {
            contentEl.innerHTML = `<div class="story-viewer-text" style="background-color: ${story.bg_color}; color: ${story.text_color}; font-size: ${story.font_size}px;">${story.content}</div>`;
        }

        // Update viewers count
        document.getElementById('viewersCount').innerHTML = `<i class="fas fa-eye"></i> <span>${story.view_count}</span> views`;

        // Mark as viewed
        if (!story.has_viewed && !this.currentStoryGroup.is_own) {
            this.viewStory(story.id);
            story.has_viewed = true;
        }

        // Auto-advance timer
        clearTimeout(this.storyTimer);
        this.storyTimer = setTimeout(() => this.nextStory(), this.storyDuration);
    },

    renderProgressBar() {
        const container = document.getElementById('storyProgressBar');
        const stories = this.currentStoryGroup.stories;
        let html = '';

        stories.forEach((s, i) => {
            let statusClass = '';
            if (i < this.currentStoryIndex) statusClass = 'completed';
            else if (i === this.currentStoryIndex) statusClass = 'active';

            html += `
                <div class="story-progress-segment ${statusClass}">
                    <div class="story-progress-fill"></div>
                </div>
            `;
        });

        container.innerHTML = html;
    },

    nextStory() {
        clearTimeout(this.storyTimer);
        if (!this.currentStoryGroup) return;

        if (this.currentStoryIndex < this.currentStoryGroup.stories.length - 1) {
            this.currentStoryIndex++;
            this.showStory();
        } else {
            // Move to next user's stories
            const currentGroupIndex = this.stories.indexOf(this.currentStoryGroup);
            if (currentGroupIndex < this.stories.length - 1) {
                this.currentStoryGroup = this.stories[currentGroupIndex + 1];
                this.currentStoryIndex = 0;
                this.showStory();
            } else {
                this.closeStoryViewer();
            }
        }
    },

    prevStory() {
        clearTimeout(this.storyTimer);
        if (!this.currentStoryGroup) return;

        if (this.currentStoryIndex > 0) {
            this.currentStoryIndex--;
            this.showStory();
        } else {
            const currentGroupIndex = this.stories.indexOf(this.currentStoryGroup);
            if (currentGroupIndex > 0) {
                this.currentStoryGroup = this.stories[currentGroupIndex - 1];
                this.currentStoryIndex = 0;
                this.showStory();
            }
        }
    },

    closeStoryViewer() {
        clearTimeout(this.storyTimer);
        document.getElementById('storyViewer').classList.remove('active');
        this.currentStoryGroup = null;
        this.currentStoryIndex = 0;
        this.loadStories();
    },

    async viewStory(storyId) {
        await ChatApp.apiRequest('/view-story.php', 'POST', {
            story_id: storyId,
            csrf_token: STORIES_CONFIG.csrfToken
        });
    },

    async deleteStory(storyId) {
        if (!confirm('Delete this story?')) return;

        const result = await ChatApp.apiRequest('/delete-story.php', 'POST', {
            story_id: storyId,
            csrf_token: STORIES_CONFIG.csrfToken
        });

        if (result.success) {
            ChatApp.showToast('Story deleted', 'success');
            this.loadStories();
        }
    },

    getTimeAgo(datetime) {
        const now = new Date();
        const storyDate = new Date(datetime);
        const diff = Math.floor((now - storyDate) / 1000);

        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }
};

// Create Story Functions
function openCreateModal() {
    document.getElementById('createStoryModal').classList.add('active');
}

function closeCreateModal() {
    document.getElementById('createStoryModal').classList.remove('active');
    document.getElementById('storyText').value = '';
    document.getElementById('storyPreview').style.display = 'none';
    document.getElementById('storyUploadArea').style.display = 'block';
    document.getElementById('storyFileInput').value = '';
    StoriesModule.selectedFile = null;
}

function switchStoryType(type) {
    StoriesModule.storyType = type;
    document.querySelectorAll('.story-type-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.type === type);
    });
    document.getElementById('textStorySection').style.display = type === 'text' ? 'block' : 'none';
    document.getElementById('mediaStorySection').style.display = type === 'media' ? 'block' : 'none';
}

function previewStoryMedia(input) {
    const file = input.files[0];
    if (!file) return;

    StoriesModule.selectedFile = file;
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById('storyPreview');
        if (file.type.startsWith('video/')) {
            preview.outerHTML = `<video class="story-preview" id="storyPreview" controls><source src="${e.target.result}"></video>`;
        } else {
            const preview = document.getElementById('storyPreview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        document.getElementById('storyUploadArea').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

async function postStory() {
    const btn = document.getElementById('postStoryBtn');
    btn.disabled = true;
    btn.textContent = 'Posting...';

    try {
        if (StoriesModule.storyType === 'text') {
            const content = document.getElementById('storyText').value.trim();
            if (!content) {
                ChatApp.showToast('Please enter some text', 'error');
                btn.disabled = false;
                btn.textContent = 'Share Story';
                return;
            }

            const result = await ChatApp.apiRequest('/create-story.php', 'POST', {
                content: content,
                bg_color: StoriesModule.selectedBgColor,
                text_color: StoriesModule.selectedTextColor,
                font_size: 18,
                csrf_token: STORIES_CONFIG.csrfToken
            });

            if (result.success) {
                ChatApp.showToast('Story posted!', 'success');
                closeCreateModal();
                StoriesModule.loadStories();
            }
        } else {
            if (!StoriesModule.selectedFile) {
                ChatApp.showToast('Please select a photo or video', 'error');
                btn.disabled = false;
                btn.textContent = 'Share Story';
                return;
            }

            const formData = new FormData();
            formData.append('media', StoriesModule.selectedFile);
            formData.append('csrf_token', STORIES_CONFIG.csrfToken);

            const response = await fetch('../api/create-story.php', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': STORIES_CONFIG.csrfToken },
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();
            if (result.success) {
                ChatApp.showToast('Story posted!', 'success');
                closeCreateModal();
                StoriesModule.loadStories();
            }
        }
    } catch (error) {
        ChatApp.showToast('Failed to post story', 'error');
    }

    btn.disabled = false;
    btn.textContent = 'Share Story';
}

// Color picker
document.querySelectorAll('.color-option').forEach(opt => {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.color-option').forEach(o => o.classList.remove('active'));
        this.classList.add('active');
        StoriesModule.selectedBgColor = this.dataset.bg;
        StoriesModule.selectedTextColor = this.dataset.color;
    });
});

// Keyboard navigation for story viewer
document.addEventListener('keydown', function(e) {
    if (!document.getElementById('storyViewer').classList.contains('active')) return;
    
    if (e.key === 'ArrowRight' || e.key === ' ') {
        e.preventDefault();
        StoriesModule.nextStory();
    } else if (e.key === 'ArrowLeft') {
        e.preventDefault();
        StoriesModule.prevStory();
    } else if (e.key === 'Escape') {
        StoriesModule.closeStoryViewer();
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    StoriesModule.init();
});
