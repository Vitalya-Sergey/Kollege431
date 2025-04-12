<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Check if it's an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
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
                    INSERT INTO video_comments (video_id, user_id, comment)
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
    exit();
}

// If not an AJAX request, output the HTML/JS/CSS for the comments system
?>

<!-- Comments System HTML -->
<div class="comments-section mt-4">
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="comment-form mb-4">
            <textarea id="comment-text" class="form-control mb-2" rows="3" placeholder="Напишите комментарий..."></textarea>
            <button onclick="addComment(<?php echo $video_id; ?>)" class="btn btn-primary">
                <i class="fas fa-paper-plane me-1"></i> Отправить
            </button>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Чтобы оставить комментарий, пожалуйста, <a href="index.php?page=login">войдите</a> в систему.
        </div>
    <?php endif; ?>

    <div id="comments-container"></div>
</div>

<script>
// Global variables
const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
const isAdmin = <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'true' : 'false'; ?>;

// Load comments for a video
function loadComments(videoId) {
    fetch(`comments.php?action=get&video_id=${videoId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
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
                            ${comment.comment}
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
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
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
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
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

// Toggle like for a video
function toggleLike(videoId) {
    const formData = new FormData();
    formData.append('action', 'like');
    formData.append('video_id', videoId);

    fetch('comments.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
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
    background-color: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.comment-header {
    margin-bottom: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.comment-body {
    white-space: pre-wrap;
    color: #212529;
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

.comment-form textarea {
    border: 1px solid #ced4da;
    transition: border-color 0.2s;
}

.comment-form textarea:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
}

.btn-danger {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.alert {
    border-radius: 0.5rem;
}

.alert a {
    text-decoration: none;
    font-weight: 500;
}

.alert a:hover {
    text-decoration: underline;
}
</style> 