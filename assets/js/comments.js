// Load comments for a video
function loadComments(videoId) {
    fetch(`ajax/get_comments.php?video_id=${videoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const commentsContainer = document.getElementById('comments-container');
                commentsContainer.innerHTML = '';
                
                data.comments.forEach(comment => {
                    const commentHtml = `
                        <div class="comment" id="comment-${comment.id}">
                            <div class="comment-header">
                                <strong>${comment.college_name}</strong>
                                <small class="text-muted">${new Date(comment.created_at).toLocaleString()}</small>
                                ${comment.user_id == currentUserId || isAdmin ? 
                                    `<button class="btn btn-sm btn-danger float-end" onclick="deleteComment(${comment.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>` : ''
                                }
                            </div>
                            <div class="comment-body">
                                ${comment.comment_text}
                            </div>
                        </div>
                    `;
                    commentsContainer.innerHTML += commentHtml;
                });
            }
        })
        .catch(error => console.error('Error loading comments:', error));
}

// Add a new comment
function addComment(videoId) {
    const commentText = document.getElementById('comment-text').value.trim();
    if (!commentText) return;

    const formData = new FormData();
    formData.append('video_id', videoId);
    formData.append('comment_text', commentText);

    fetch('ajax/add_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('comment-text').value = '';
            loadComments(videoId);
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error adding comment:', error));
}

// Delete a comment
function deleteComment(commentId) {
    if (!confirm('Вы уверены, что хотите удалить этот комментарий?')) return;

    const formData = new FormData();
    formData.append('comment_id', commentId);

    fetch('ajax/delete_comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentElement = document.getElementById(`comment-${commentId}`);
            commentElement.remove();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error deleting comment:', error));
} 