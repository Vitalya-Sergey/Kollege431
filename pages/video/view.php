<?php
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$video_id) {
    header("Location: index.php?page=colleges");
    exit();
}

// Get video info
$stmt = $db->prepare("
    SELECT v.*, 
           u.college_name, 
           GROUP_CONCAT(t.name) as tags,
           (SELECT COUNT(*) FROM video_likes WHERE video_id = v.id) as likes_count,
           (SELECT COUNT(*) FROM video_comments WHERE video_id = v.id) as comments_count,
           EXISTS(SELECT 1 FROM video_likes WHERE video_id = v.id AND user_id = ?) as user_liked
    FROM videos v 
    LEFT JOIN users u ON v.college_id = u.id
    LEFT JOIN video_tags vt ON v.id = vt.video_id
    LEFT JOIN tags t ON vt.tag_id = t.id
    WHERE v.id = ?
    GROUP BY v.id
");
$stmt->execute([isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0, $video_id]);
$video = $stmt->fetch();

if (!$video) {
    header("Location: index.php?page=colleges");
    exit();
}

// Проверяем, является ли видео локальным файлом или внешней ссылкой
$is_local_file = !filter_var($video['file_path'], FILTER_VALIDATE_URL);

// Если это локальный файл и запрошена загрузка
if ($is_local_file && isset($_GET['download'])) {
    $file_path = __DIR__ . '/../../' . $video['file_path'];
    if (file_exists($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($video['file_path']) . '"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit();
    }
}

// Функция для получения ID видео из URL
function getVideoId($url) {
    $video_id = '';
    
    // RuTube
    if (strpos($url, 'rutube.ru') !== false) {
        if (preg_match('/(?:rutube\.ru\/video\/|rutube\.ru\/play\/embed\/)([a-zA-Z0-9]+)/', $url, $matches)) {
            return ['type' => 'rutube', 'id' => $matches[1]];
        }
    }
    
    // Dzen
    if (strpos($url, 'dzen.ru') !== false) {
        if (preg_match('/(?:dzen\.ru\/video\/|dzen\.ru\/play\/embed\/)([a-zA-Z0-9]+)/', $url, $matches)) {
            return ['type' => 'dzen', 'id' => $matches[1]];
        }
    }   

    // VK
    if (strpos($url, 'vk.com') !== false) {
        if (preg_match('/video(-?\d+_\d+)/', $url, $matches)) {
            return ['type' => 'vk', 'id' => $matches[1]];
        }
    }
    
    return ['type' => 'unknown', 'id' => ''];
}
?>

<div class="content-wrapper">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($video['title']); ?></h2>
                    
                    <div class="video-container mb-4">
                        <?php if ($is_local_file): ?>
                            <video id="localVideo" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="auto" width="100%" height="450">
                                <source src="<?php echo htmlspecialchars($video['file_path']); ?>" type="video/mp4">
                                Ваш браузер не поддерживает видео.
                            </video>
                            <div class="mt-3">
                                <a href="?page=video&id=<?php echo $video_id; ?>&download=1" class="btn btn-primary">
                                    <i class="fas fa-download"></i> Скачать видео
                                </a>
                            </div>
                        <?php else: ?>
                            <?php
                            $video_info = getVideoId($video['file_path']);
                            switch ($video_info['type']) {
                                case 'rutube':
                                    echo '<div class="video-wrapper rutube-video">
                                            <iframe width="100%" height="450" 
                                                src="https://rutube.ru/play/embed/' . $video_info['id'] . '" 
                                                frameborder="0" 
                                                allow="clipboard-write; autoplay; fullscreen" 
                                                webkitallowfullscreen 
                                                mozallowfullscreen 
                                                allowfullscreen>
                                            </iframe>
                                          </div>';
                                    break;
                                    
                                case 'dzen':
                                    echo '<div class="video-wrapper dzen-video">
                                            <iframe src="https://dzen.ru/video/embed/' . $video_info['id'] . '" 
                                                frameborder="0" 
                                                allowfullscreen>
                                    </iframe>
                                    </div>';        

                                case 'vk':
                                    echo '<div class="video-wrapper vk-video">
                                            <iframe src="https://vk.com/video_ext.php?oid=' . $video_info['id'] . '&hd=1" 
                                                width="100%" 
                                                height="450" 
                                                frameborder="0" 
                                                allowfullscreen>
                                            </iframe>
                                          </div>';
                                    break;
                                    
                                default:
                                    echo '<div class="plyr__video-embed" id="player">
                                            <iframe
                                                src="' . htmlspecialchars($video['file_path']) . '"
                                                allowfullscreen
                                                allowtransparency
                                                allow="autoplay"
                                            ></iframe>
                                          </div>';
                                    break;
                            }
                            ?>
                        <?php endif; ?>
                    </div>

                    <div class="video-info">
                        <p class="text-muted">
                            Колледж: <?php echo htmlspecialchars($video['college_name']); ?><br>
                            Добавлено: <?php echo date('d.m.Y H:i', strtotime($video['created_at'])); ?>
                        </p>
                        
                        <?php if ($video['description']): ?>
                            <h4>Описание</h4>
                            <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($video['tags']): ?>
                            <div class="tags">
                                <?php foreach(explode(',', $video['tags']) as $tag): ?>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $video['college_id'] || $_SESSION['role'] == 'admin')): ?>
                        <div class="mt-4">
                            <a href="index.php?page=video&id=<?php echo $video_id; ?>&action=edit" class="btn btn-secondary">Редактировать</a>
                            <a href="index.php?page=video&id=<?php echo $video_id; ?>&action=delete" 
                               class="btn btn-danger" 
                               onclick="return confirm('Вы уверены, что хотите удалить это видео?')">Удалить</a>
                        </div>
                    <?php endif; ?>

                    <div class="container mt-4">
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-primary like-btn" 
                                                        id="like-btn-<?php echo $video['id']; ?>"
                                                        onclick="toggleLike(<?php echo $video['id']; ?>)"
                                                        <?php echo isset($video['user_liked']) && $video['user_liked'] ? 'data-liked="true"' : ''; ?>>
                                                    <i class="fa<?php echo isset($video['user_liked']) && $video['user_liked'] ? 's' : 'r'; ?> fa-heart"></i>
                                                    <span id="like-count-<?php echo $video['id']; ?>" class="ms-1">
                                                        <?php echo $video['likes_count']; ?>
                                                    </span>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        onclick="showComments(<?php echo $video['id']; ?>)">
                                                    <i class="fas fa-comment"></i>
                                                    <span class="ms-1"><?php echo $video['comments_count']; ?></span>
                                                </button>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y', strtotime($video['created_at'])); ?>
                                            </small>
                                        </div>

                                        <div class="mb-4">
                                            <h5>Описание</h5>
                                            <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                                        </div>

                                        <!-- Comments Section -->
                                        <div id="comments-section-<?php echo $video['id']; ?>" class="comments-section">
                                            <h5>Комментарии</h5>
                                            <?php if (isset($_SESSION['user_id'])): ?>
                                                <div class="mb-3">
                                                    <form onsubmit="addComment(<?php echo $video['id']; ?>); return false;">
                                                        <textarea id="comment-text-<?php echo $video['id']; ?>" 
                                                                  class="form-control" 
                                                                  placeholder="Добавить комментарий"
                                                                  required></textarea>
                                                        <button type="submit" class="btn btn-primary btn-sm mt-2">
                                                            Отправить
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    Чтобы оставить комментарий, пожалуйста, <a href="index.php?page=login">войдите</a> в систему.
                                                </div>
                                            <?php endif; ?>
                                            <div id="comments-container-<?php echo $video['id']; ?>" class="comments-list"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Подключаем Video.js для улучшенного видеоплеера -->
<link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet" />
<script src="https://vjs.zencdn.net/7.20.3/video.min.js"></script>

<!-- Подключаем Plyr для универсального видеоплеера -->
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.2/plyr.css" />
<script src="https://cdn.plyr.io/3.7.2/plyr.js"></script>

<style>
.video-container {
    position: relative;
    background: #000;
    border-radius: 4px;
    overflow: hidden;
}

.video-wrapper {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 */
    height: 0;
    overflow: hidden;
}

.video-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.video-js {
    width: 100%;
    height: 450px;
}

.video-info {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #dee2e6;
}

.video-info h4 {
    margin-bottom: 15px;
}

.tags {
    margin-top: 15px;
}

.badge {
    font-size: 0.9em;
    padding: 5px 10px;
}

/* Стили для Plyr плеера */
.plyr__video-embed {
    height: 450px;
}

.like-btn {
    position: relative;
    overflow: hidden;
}

.like-btn[data-liked="true"] {
    color: #dc3545;
    border-color: #dc3545;
}

.like-btn[data-liked="true"] i {
    color: #dc3545;
}

.like-btn .heart-animation {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    color: #dc3545;
    pointer-events: none;
    animation: heart-pop 0.5s ease-out;
}

@keyframes heart-pop {
    0% { transform: translate(-50%, -50%) scale(0); opacity: 0; }
    50% { transform: translate(-50%, -50%) scale(1.5); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(1); opacity: 0; }
}

.comments-section {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
    margin-top: 1rem;
}

.comment {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background-color: #fff;
}

.comment-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 0.5rem;
}

.comment-body {
    padding-top: 0.5rem;
    white-space: pre-wrap;
    word-break: break-word;
}

.comment:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.comment {
    transition: background-color 0.2s;
}

.comment:hover {
    background-color: #f8f9fa;
}

.comment-header {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 0.5rem;
}

.comment-body {
    white-space: pre-wrap;
}

.btn-outline-primary.active {
    color: #dc3545;
    border-color: #dc3545;
    background-color: transparent;
}

.btn-outline-primary.active:hover {
    color: #dc3545;
    border-color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация Video.js для локальных видео
    if (document.getElementById('localVideo')) {
        videojs('localVideo', {
            controls: true,
            fluid: true,
            preload: 'auto'
        });
    }

    // Инициализация Plyr для остальных видео
    if (document.getElementById('player')) {
        const player = new Plyr('#player', {
            controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen'],
            settings: ['captions', 'quality', 'speed'],
            hideControls: true
        });
    }

    // Загрузить комментарии при загрузке страницы
    const videoId = <?php echo $video['id']; ?>;
    loadComments(videoId);
});

// Like functionality
function toggleLike(videoId) {
    if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
        window.location.href = 'index.php?page=login';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'like');
    formData.append('video_id', videoId);

    fetch('comments.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeBtn = document.getElementById(`like-btn-${videoId}`);
            const likeCount = document.getElementById(`like-count-${videoId}`);
            const heartIcon = likeBtn.querySelector('i');

            if (data.liked) {
                heartIcon.classList.replace('far', 'fas');
                likeBtn.classList.add('active');
            } else {
                heartIcon.classList.replace('fas', 'far');
                likeBtn.classList.remove('active');
            }

            likeCount.textContent = data.like_count;
        }
    });
}

// Comments functionality
function showComments(videoId) {
    const commentsSection = document.getElementById(`comments-section-${videoId}`);
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        loadComments(videoId);
    } else {
        commentsSection.style.display = 'none';
    }
}

function loadComments(videoId) {
    fetch(`comments.php?action=get&video_id=${videoId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const container = document.getElementById(`comments-container-${videoId}`);
            container.innerHTML = '';

            data.comments.forEach(comment => {
                const commentDiv = document.createElement('div');
                commentDiv.className = 'comment mb-3 p-3 border rounded';
                commentDiv.innerHTML = `
                    <div class="comment-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="me-2">${comment.college_name}</strong>
                            <small class="text-muted ms-2">${new Date(comment.created_at).toLocaleString()}</small>
                        </div>
                        ${comment.user_id == <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; ?> || <?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'true' : 'false'; ?> ? 
                            `<button class="btn btn-sm btn-danger" onclick="deleteComment(${comment.id})">
                                <i class="fas fa-trash"></i>
                            </button>` : ''
                        }
                    </div>
                    <div class="comment-body mt-2">${comment.comment}</div>
                `;
                container.appendChild(commentDiv);
            });
        }
    });
}

function addComment(videoId) {
    if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
        window.location.href = 'index.php?page=login';
        return;
    }

    const commentText = document.getElementById(`comment-text-${videoId}`).value.trim();
    if (!commentText) return;

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('video_id', videoId);
    formData.append('comment_text', commentText);

    fetch('comments.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById(`comment-text-${videoId}`).value = '';
            loadComments(videoId);
        } else {
            alert(data.message || 'Произошла ошибка при добавлении комментария');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при добавлении комментария');
    });
}

function deleteComment(commentId) {
    if (!confirm('Вы уверены, что хотите удалить этот комментарий?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('comment_id', commentId);

    fetch('comments.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const commentElement = document.getElementById(`comment-${commentId}`);
            if (commentElement) {
                commentElement.remove();
            }
        } else {
            alert(data.message || 'Произошла ошибка при удалении комментария');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при удалении комментария');
    });
}

const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script> 