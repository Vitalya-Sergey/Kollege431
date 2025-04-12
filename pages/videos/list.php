<?php
// Получаем все видео с информацией о колледже, лайках и комментариях
$query = "
    SELECT v.*, 
           u.college_name,
           COUNT(DISTINCT l.id) as likes_count,
           COUNT(DISTINCT c.id) as comments_count,
           EXISTS(SELECT 1 FROM video_likes WHERE video_id = v.id AND user_id = ?) as user_liked
    FROM videos v
    LEFT JOIN users u ON v.college_id = u.id
    LEFT JOIN video_likes l ON v.id = l.video_id
    LEFT JOIN video_comments c ON v.id = c.video_id
    " . (isset($_GET['college_id']) ? "WHERE v.college_id = ?" : "") . "
    GROUP BY v.id
    ORDER BY v.created_at DESC
";

$params = [isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0];
if (isset($_GET['college_id'])) {
    $params[] = (int)$_GET['college_id'];
}

$stmt = $db->prepare($query);
$stmt->execute($params);
$videos = $stmt->fetchAll();

// Функция для получения информации о видео
function getVideoInfo($url) {
    $video_info = ['type' => 'unknown', 'id' => ''];
    
    // RuTube
    if (strpos($url, 'rutube.ru') !== false) {
        if (preg_match('/(?:rutube\.ru\/video\/|rutube\.ru\/play\/embed\/)([a-zA-Z0-9]+)/', $url, $matches)) {
            return ['type' => 'rutube', 'id' => $matches[1]];
        }
    }
    
    // Dzen
    if (strpos($url, 'dzen.ru') !== false) {
        if (preg_match('/(?:dzen\.ru\/video\/|dzen\.ru\/embed\/)([a-zA-Z0-9]+)/', $url, $matches)) {
            return ['type' => 'dzen', 'id' => $matches[1]];
        }
    }   

    // VK
    if (strpos($url, 'vk.com') !== false) {
        if (preg_match('/video(-?\d+_\d+)/', $url, $matches)) {
            return ['type' => 'vk', 'id' => $matches[1]];
        }
    }
    
    return $video_info;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <?php echo isset($_GET['college_id']) ? 'Видео колледжа' : 'Все видео'; ?>
            </h2>
        </div>
    </div>

    <?php if (empty($videos)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <?php echo isset($_GET['college_id']) ? 'У этого колледжа пока нет видео.' : 'Пока нет загруженных видео.'; ?>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($videos as $video): ?>
                <div class="col">
                    <div class="card h-100 hover-card">
                        <div class="ratio ratio-16x9 card-img-top">
                            <?php
                            $is_local_file = !filter_var($video['file_path'], FILTER_VALIDATE_URL);
                            if ($is_local_file) {
                                echo '<video src="' . htmlspecialchars($video['file_path']) . '" 
                                        class="w-100 h-100"
                                        controls
                                        preload="metadata"></video>';
                            } else {
                                $video_info = getVideoInfo($video['file_path']);
                                switch ($video_info['type']) {
                                    case 'rutube':
                                        echo '<iframe src="https://rutube.ru/play/embed/' . $video_info['id'] . '" 
                                                frameborder="0" 
                                                allowfullscreen
                                                allow="clipboard-write; autoplay"
                                                webkitallowfullscreen 
                                                mozallowfullscreen></iframe>';
                                        break;
                                        
                                    case 'dzen':
                                        echo '<iframe src="https://dzen.ru/embed/' . $video_info['id'] . '" 
                                                frameborder="0" 
                                                allowfullscreen
                                                allow="clipboard-write; autoplay"
                                                webkitallowfullscreen 
                                                mozallowfullscreen></iframe>';
                                        break;
                                        
                                    case 'vk':
                                        echo '<iframe src="https://vk.com/video_ext.php?oid=' . $video_info['id'] . '" 
                                                frameborder="0" 
                                                allowfullscreen
                                                allow="clipboard-write; autoplay"
                                                webkitallowfullscreen 
                                                mozallowfullscreen></iframe>';
                                        break;
                                        
                                    default:
                                        echo '<iframe src="' . htmlspecialchars($video['file_path']) . '" 
                                                frameborder="0" 
                                                allowfullscreen
                                                allow="clipboard-write; autoplay"
                                                webkitallowfullscreen 
                                                mozallowfullscreen></iframe>';
                                        break;
                                }
                            }
                            ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="index.php?page=video&id=<?php echo $video['id']; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($video['title']); ?>
                                </a>
                            </h5>
                            <p class="card-text small text-muted mb-2">
                                <?php echo htmlspecialchars($video['college_name']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary <?php echo $video['user_liked'] ? 'active' : ''; ?>"
                                            onclick="toggleLike(<?php echo $video['id']; ?>)"
                                            id="like-btn-<?php echo $video['id']; ?>">
                                        <i class="fa<?php echo $video['user_liked'] ? 's' : 'r'; ?> fa-heart"></i>
                                        <span id="like-count-<?php echo $video['id']; ?>"><?php echo $video['likes_count']; ?></span>
                                    </button>
                                    <a href="index.php?page=video&id=<?php echo $video['id']; ?>#comments" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="far fa-comment"></i>
                                        <span><?php echo $video['comments_count']; ?></span>
                                    </a>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('d.m.Y', strtotime($video['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

.ratio-16x9 {
    position: relative;
    width: 100%;
    height: 0;
    padding-bottom: 56.25%;
    overflow: hidden;
    background: #000;
}

.ratio-16x9 iframe,
.ratio-16x9 video {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 0.5rem 0.5rem 0 0;
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
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
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
</script> 