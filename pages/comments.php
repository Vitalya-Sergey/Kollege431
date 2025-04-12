<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Get the action type from the request
$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// Check authentication for actions that require it
if (in_array($action, ['add', 'edit', 'delete', 'like']) && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

try {
    switch ($action) {
        case 'get':
            // Get comments for a video
            $video_id = isset($_GET['video_id']) ? (int)$_GET['video_id'] : 0;
            
            if ($video_id <= 0) {
                throw new Exception('Неверный ID видео');
            }

            $stmt = $db->prepare("
                SELECT c.*, u.username, u.college_name
                FROM video_comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.video_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$video_id]);
            $comments = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'comments' => $comments
            ]);
            break;

        case 'add':
            // Add a new comment
            $video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : 0;
            $comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

            if ($video_id <= 0) {
                throw new Exception('Неверный ID видео');
            }

            if (empty($comment_text)) {
                throw new Exception('Текст комментария не может быть пустым');
            }

            // Check if video exists
            $stmt = $db->prepare("SELECT id FROM videos WHERE id = ?");
            $stmt->execute([$video_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Видео не найдено');
            }

            // Add comment
            $stmt = $db->prepare("
                INSERT INTO video_comments (video_id, user_id, comment_text)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$video_id, $_SESSION['user_id'], $comment_text]);
            $comment_id = $db->lastInsertId();

            // Get the new comment with user info
            $stmt = $db->prepare("
                SELECT c.*, u.username, u.college_name
                FROM video_comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'comment' => $comment
            ]);
            break;

        case 'edit':
            // Edit an existing comment
            $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
            $comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

            if ($comment_id <= 0) {
                throw new Exception('Неверный ID комментария');
            }

            if (empty($comment_text)) {
                throw new Exception('Текст комментария не может быть пустым');
            }

            // Check permissions
            $stmt = $db->prepare("SELECT user_id FROM video_comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch();

            if (!$comment) {
                throw new Exception('Комментарий не найден');
            }

            if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] !== $comment['user_id']) {
                throw new Exception('Нет прав для редактирования');
            }

            // Update comment
            $stmt = $db->prepare("
                UPDATE video_comments 
                SET comment_text = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$comment_text, $comment_id]);

            // Get updated comment
            $stmt = $db->prepare("
                SELECT c.*, u.username, u.college_name
                FROM video_comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$comment_id]);
            $updated_comment = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'comment' => $updated_comment
            ]);
            break;

        case 'delete':
            // Delete a comment
            $comment_id = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

            if ($comment_id <= 0) {
                throw new Exception('Неверный ID комментария');
            }

            // Check permissions
            $stmt = $db->prepare("SELECT user_id FROM video_comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch();

            if (!$comment) {
                throw new Exception('Комментарий не найден');
            }

            if ($_SESSION['role'] !== 'admin' && $comment['user_id'] != $_SESSION['user_id']) {
                throw new Exception('Нет прав для удаления комментария');
            }

            // Delete comment
            $stmt = $db->prepare("DELETE FROM video_comments WHERE id = ?");
            $stmt->execute([$comment_id]);

            echo json_encode(['success' => true]);
            break;

        case 'like':
            // Toggle like for a video
            $video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : 0;

            if ($video_id <= 0) {
                throw new Exception('Неверный ID видео');
            }

            // Check if video exists
            $stmt = $db->prepare("SELECT id FROM videos WHERE id = ?");
            $stmt->execute([$video_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Видео не найдено');
            }

            // Check if user already liked the video
            $stmt = $db->prepare("
                SELECT id FROM video_likes 
                WHERE video_id = ? AND user_id = ?
            ");
            $stmt->execute([$video_id, $_SESSION['user_id']]);
            $existing_like = $stmt->fetch();

            if ($existing_like) {
                // Unlike
                $stmt = $db->prepare("
                    DELETE FROM video_likes 
                    WHERE video_id = ? AND user_id = ?
                ");
                $stmt->execute([$video_id, $_SESSION['user_id']]);
                $liked = false;
            } else {
                // Like
                $stmt = $db->prepare("
                    INSERT INTO video_likes (video_id, user_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$video_id, $_SESSION['user_id']]);
                $liked = true;
            }

            // Get updated like count
            $stmt = $db->prepare("
                SELECT COUNT(*) as like_count 
                FROM video_likes 
                WHERE video_id = ?
            ");
            $stmt->execute([$video_id]);
            $like_count = $stmt->fetch()['like_count'];

            echo json_encode([
                'success' => true,
                'liked' => $liked,
                'like_count' => $like_count
            ]);
            break;

        default:
            throw new Exception('Неверное действие');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// JavaScript for handling comments and likes
if (!isset($_GET['action']) && !isset($_POST['action'])): ?>

<script>
// Load comments for a video
function loadComments(videoId) {
    fetch(`comments.php?action=get&video_id=${videoId}`)
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
    formData.append('action', 'add');
    formData.append('video_id', videoId);
    formData.append('comment_text', commentText);

    fetch('comments.php', {
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
    formData.append('action', 'delete');
    formData.append('comment_id', commentId);

    fetch('comments.php', {
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

// Edit a comment
function editComment(commentId, newText) {
    const formData = new FormData();
    formData.append('action', 'edit');
    formData.append('comment_id', commentId);
    formData.append('comment_text', newText);

    fetch('comments.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentElement = document.getElementById(`comment-${commentId}`);
            const commentBody = commentElement.querySelector('.comment-body');
            commentBody.textContent = newText;
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error editing comment:', error));
}

// Toggle like for a video
function toggleLike(videoId) {
    const formData = new FormData();
    formData.append('action', 'like');
    formData.append('video_id', videoId);

    fetch('comments.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeButton = document.getElementById(`like-btn-${videoId}`);
            const likeCount = document.getElementById(`like-count-${videoId}`);
            
            if (data.liked) {
                likeButton.classList.add('liked');
                likeButton.innerHTML = '<i class="fas fa-heart"></i>';
            } else {
                likeButton.classList.remove('liked');
                likeButton.innerHTML = '<i class="far fa-heart"></i>';
            }
            
            likeCount.textContent = data.like_count;
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error toggling like:', error));
}
</script>

<style>
.comment {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.comment-header {
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.comment-body {
    white-space: pre-wrap;
}

.like-button {
    background: none;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.like-button.liked {
    color: #dc3545;
}

.like-button:hover {
    transform: scale(1.1);
}

.like-count {
    font-size: 0.9em;
    color: #6c757d;
}
</style>
<?php endif; ?> 