/**
 * MITS Alumni Portal - Community Feed Client JS
 */

document.addEventListener('DOMContentLoaded', () => {
    initPostComposer();
});

/**
 * Initialize Post Composer (Image preview, AJAX submit)
 */
function initPostComposer() {
    const postForm = document.getElementById('post-composer-form');
    const imageInput = document.getElementById('post-image-input');
    const imagePreviewContainer = document.getElementById('composer-image-preview');
    const previewImg = document.getElementById('preview-img');
    const removeImageBtn = document.getElementById('remove-image-btn');

    if (imageInput) {
        imageInput.addEventListener('change', () => {
            if (imageInput.files && imageInput.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    imagePreviewContainer.classList.remove('d-none');
                };
                reader.readAsDataURL(imageInput.files[0]);
            }
        });
    }

    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', () => {
            imageInput.value = '';
            previewImg.src = '';
            imagePreviewContainer.classList.add('d-none');
        });
    }

    if (postForm) {
        postForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('publish-post-btn');
            const textarea = document.getElementById('post-content-textarea');
            const content = textarea.value.trim();

            if (!content) {
                showToast('warning', 'Please write something before publishing.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Publishing...';

            const formData = new FormData(postForm);

            try {
                const res = await fetch(getBaseUrl() + '/api/posts.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    showToast('success', 'Post published to the community feed!');
                    // Reset composer
                    textarea.value = '';
                    if (imageInput) imageInput.value = '';
                    if (imagePreviewContainer) imagePreviewContainer.classList.add('d-none');
                    // Reload stream
                    setTimeout(() => {
                        window.location.reload();
                    }, 400);
                } else {
                    showToast('danger', data.message || 'Failed to publish post.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Publish';
                }
            } catch (err) {
                console.error('Post submit error:', err);
                showToast('danger', 'Error connecting to post service.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-send-fill me-1"></i>Publish';
            }
        });
    }
}

/**
 * Toggle Like on a Post
 */
async function toggleLike(postId, btn) {
    const heartIcon = btn.querySelector('.like-icon');
    const countEl = btn.querySelector('.like-count');
    const wasLiked = btn.classList.contains('active-liked');

    // Optimistic UI update
    if (wasLiked) {
        btn.classList.remove('active-liked');
        heartIcon.className = 'bi bi-heart me-1 like-icon';
        countEl.textContent = Math.max(parseInt(countEl.textContent || 0) - 1, 0);
    } else {
        btn.classList.add('active-liked');
        heartIcon.className = 'bi bi-heart-fill text-danger me-1 like-icon animate-pulse';
        countEl.textContent = parseInt(countEl.textContent || 0) + 1;
    }

    try {
        const res = await apiFetch(getBaseUrl() + '/api/likes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId })
        });
        const data = await res.json();

        if (data.success) {
            countEl.textContent = data.likes_count;
            if (data.has_liked) {
                btn.classList.add('active-liked');
                heartIcon.className = 'bi bi-heart-fill text-danger me-1 like-icon';
            } else {
                btn.classList.remove('active-liked');
                heartIcon.className = 'bi bi-heart me-1 like-icon';
            }
        } else {
            showToast('danger', data.message || 'Could not update like.');
        }
    } catch (err) {
        console.error(err);
        showToast('danger', 'Error toggling like.');
    }
}

/**
 * Toggle & Load Comments for a Post
 */
async function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-section-${postId}`);
    if (!commentsSection) return;

    if (commentsSection.classList.contains('d-none')) {
        commentsSection.classList.remove('d-none');
        loadComments(postId);
    } else {
        commentsSection.classList.add('d-none');
    }
}

/**
 * Load Comments via AJAX
 */
async function loadComments(postId) {
    const listEl = document.getElementById(`comments-list-${postId}`);
    if (!listEl) return;

    listEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

    try {
        const res = await fetch(`${getBaseUrl()}/api/comments.php?post_id=${postId}`);
        const data = await res.json();

        if (data.success && data.comments.length > 0) {
            listEl.innerHTML = data.comments.map(c => renderCommentHtml(c, postId)).join('');
        } else {
            listEl.innerHTML = '<div class="text-center py-2 text-muted small fst-italic">No comments yet. Start the conversation!</div>';
        }
    } catch (e) {
        listEl.innerHTML = '<div class="text-center py-2 text-danger small">Failed to load comments.</div>';
    }
}

/**
 * Render single comment HTML
 */
function renderCommentHtml(c, postId) {
    return `
        <div class="d-flex gap-2 mb-3 comment-item" id="comment-${c.id}">
            <img src="${c.user.avatar}" alt="${c.user.name}" class="rounded-circle avatar-sm object-fit-cover mt-1">
            <div class="flex-grow-1 bg-light rounded-3 p-2 px-3 position-relative">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark small">${c.user.name}</span>
                    <small class="text-muted" style="font-size: 0.75rem;">${c.created_at}</small>
                </div>
                <div class="small text-secondary mb-1" style="font-size: 0.75rem;">${c.user.details}</div>
                <div class="small text-dark line-height-normal">${c.content}</div>
                ${c.can_delete ? `
                    <button type="button" class="btn btn-link text-danger p-0 position-absolute top-0 end-0 mt-2 me-2" style="font-size: 0.75rem;" title="Delete comment" onclick="deleteComment(${c.id}, ${postId})">
                        <i class="bi bi-trash"></i>
                    </button>
                ` : ''}
            </div>
        </div>
    `;
}

/**
 * Add Comment Form Submission
 */
async function submitComment(postId, form) {
    const input = form.querySelector('input[name="content"]');
    const content = input.value.trim();
    if (!content) return;

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try {
        const res = await apiFetch(getBaseUrl() + '/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, content: content })
        });
        const data = await res.json();

        if (data.success) {
            input.value = '';
            const listEl = document.getElementById(`comments-list-${postId}`);
            const countBtn = document.querySelector(`.post-comments-count-${postId}`);
            if (countBtn) countBtn.textContent = data.comments_count;

            // Remove empty placeholder if present
            if (listEl.querySelector('.fst-italic')) {
                listEl.innerHTML = '';
            }
            listEl.insertAdjacentHTML('beforeend', renderCommentHtml(data.comment, postId));
        } else {
            showToast('danger', data.message || 'Failed to post comment.');
        }
    } catch (err) {
        showToast('danger', 'Error adding comment.');
    } finally {
        submitBtn.disabled = false;
    }
}

/**
 * Delete Comment
 */
async function deleteComment(commentId, postId) {
    if (!confirm('Are you sure you want to delete this comment?')) return;

    try {
        const res = await apiFetch(getBaseUrl() + '/api/comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', comment_id: commentId })
        });
        const data = await res.json();

        if (data.success) {
            const commentEl = document.getElementById(`comment-${commentId}`);
            if (commentEl) commentEl.remove();

            const countBtn = document.querySelector(`.post-comments-count-${postId}`);
            if (countBtn) countBtn.textContent = data.comments_count;
            showToast('info', 'Comment removed.');
        } else {
            showToast('danger', data.message || 'Failed to delete comment.');
        }
    } catch (e) {
        showToast('danger', 'Error deleting comment.');
    }
}

/**
 * Delete Post
 */
async function deletePost(postId) {
    if (!confirm('Are you sure you want to delete this post? This cannot be undone.')) {
        return;
    }

    try {
        const res = await apiFetch(getBaseUrl() + '/api/posts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', post_id: postId })
        });
        const data = await res.json();

        if (data.success) {
            showToast('success', 'Post deleted.');
            const card = document.getElementById(`post-card-${postId}`);
            if (card) {
                card.remove();
            }
        } else {
            showToast('danger', data.message || 'Failed to delete post.');
        }
    } catch (e) {
        showToast('danger', 'Error deleting post.');
    }
}

/**
 * Copy Post Link to Clipboard
 */
function copyPostLink(postId) {
    const url = getBaseUrl() + '/feed.php#post-card-' + postId;
    navigator.clipboard.writeText(url).then(() => {
        showToast('info', 'Post link copied to clipboard!');
    }).catch(() => {
        prompt('Copy post URL:', url);
    });
}
