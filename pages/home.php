<?php
// Получаем популярные колледжи (с наибольшим количеством видео)
$stmt = $db->prepare("
    SELECT u.*, 
           COUNT(v.id) as videos_count,
           COUNT(DISTINCT vl.id) as total_likes,
           COUNT(DISTINCT vc.id) as total_comments
    FROM users u
    LEFT JOIN videos v ON u.id = v.college_id
    LEFT JOIN video_likes vl ON v.id = vl.video_id
    LEFT JOIN video_comments vc ON v.id = vc.video_id
    WHERE u.role = 'college'
    GROUP BY u.id
    ORDER BY videos_count DESC
    LIMIT 6
");
$stmt->execute();
$popular_colleges = $stmt->fetchAll();

// Получаем последние видео (за последние 3 дня)
$stmt = $db->prepare("
    SELECT v.*, 
           u.college_name,
           COUNT(DISTINCT vl.id) as likes_count,
           COUNT(DISTINCT vc.id) as comments_count,
           EXISTS(SELECT 1 FROM video_likes WHERE video_id = v.id AND user_id = ?) as user_liked
    FROM videos v
    JOIN users u ON v.college_id = u.id
    LEFT JOIN video_likes vl ON v.id = vl.video_id
    LEFT JOIN video_comments vc ON v.id = vc.video_id
    WHERE v.created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)
    GROUP BY v.id
    ORDER BY v.created_at DESC
    LIMIT 6
");
$stmt->execute([isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0]);
$recent_videos = $stmt->fetchAll();

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

<div class="hero-section text-center py-5 mb-5">
    <div class="container">
        <h1 class="display-4 mb-3">Добро пожаловать на платформу колледжей</h1>
        <p class="lead mb-4">Здесь вы можете найти видео материалы от различных учебных заведений, получить информацию о колледжах и оставить свои комментарии.</p>
        <a href="index.php?page=colleges" class="btn btn-primary btn-lg">
            <i class="fas fa-university me-2"></i>Смотреть колледжи
        </a>
    </div>
</div>

<div class="container">
    <!-- Популярные колледжи -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Популярные колледжи</h2>
            <a href="index.php?page=colleges" class="btn btn-outline-primary">
                Все колледжи <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
        
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($popular_colleges as $college): ?>
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <?php if ($college['profile_image']): ?>
                                    <img src="<?php echo htmlspecialchars($college['profile_image']); ?>" 
                                         class="rounded-circle me-3" 
                                         width="60" height="60"
                                         alt="<?php echo htmlspecialchars($college['college_name']); ?>">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-3"
                                         style="width: 60px; height: 60px;">
                                        <span class="text-white h4 mb-0">
                                            <?php echo strtoupper(substr($college['college_name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <h5 class="card-title mb-0">
                                    <a href="index.php?page=college&id=<?php echo $college['id']; ?>" 
                                       class="text-decoration-none text-dark stretched-link">
                                        <?php echo htmlspecialchars($college['college_name']); ?>
                                    </a>
                                </h5>
                            </div>
                            <div class="row text-center">
                                <div class="col">
                                    <h6 class="mb-0"><?php echo $college['videos_count']; ?></h6>
                                    <small class="text-muted">видео</small>
                                </div>
                                <div class="col">
                                    <h6 class="mb-0"><?php echo $college['total_likes']; ?></h6>
                                    <small class="text-muted">лайков</small>
                                </div>
                                <div class="col">
                                    <h6 class="mb-0"><?php echo $college['total_comments']; ?></h6>
                                    <small class="text-muted">комментариев</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Недавние видео -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Недавние видео</h2>
            <a href="index.php?page=videos" class="btn btn-outline-primary">
                Все видео <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>

        <?php if (empty($recent_videos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                За последние 3 дня новых видео не было добавлено.
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($recent_videos as $video): ?>
                    <div class="col">
                        <div class="card h-100 border-0 shadow-sm hover-card">
                            <div class="ratio ratio-16x9 card-img-top">
                                <?php
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
                                        if (filter_var($video['file_path'], FILTER_VALIDATE_URL)) {
                                            echo '<iframe src="' . htmlspecialchars($video['file_path']) . '" 
                                                    frameborder="0" 
                                                    allowfullscreen
                                                    allow="clipboard-write; autoplay"
                                                    webkitallowfullscreen 
                                                    mozallowfullscreen></iframe>';
                                        } else {
                                            echo '<video src="' . htmlspecialchars($video['file_path']) . '" 
                                                    controls 
                                                    class="w-100 h-100"
                                                    preload="metadata"></video>';
                                        }
                                        break;
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
                                        <button type="button" class="btn btn-sm btn-outline-secondary">
                                            <i class="far fa-comment"></i>
                                            <span><?php echo $video['comments_count']; ?></span>
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <?php 
                                        $date = new DateTime($video['created_at']);
                                        $now = new DateTime();
                                        $interval = $date->diff($now);
                                        
                                        if ($interval->days == 0) {
                                            if ($interval->h == 0) {
                                                echo $interval->i . ' мин. назад';
                                            } else {
                                                echo $interval->h . ' ч. назад';
                                            }
                                        } else if ($interval->days == 1) {
                                            echo 'вчера';
                                        } else if ($interval->days == 2) {
                                            echo 'позавчера';
                                        } else {
                                            echo date('d.m.Y', strtotime($video['created_at']));
                                        }
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
.hero-section {
    background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
    color: white;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('assets/images/pattern.png') repeat;
    opacity: 0.1;
}

.hero-section .container {
    position: relative;
    z-index: 1;
}

.hover-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
}

.card-title {
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.btn-group .btn {
    transition: all 0.2s;
}

.btn-group .btn:hover {
    transform: scale(1.05);
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

.card {
    background: #fff;
}

@media (max-width: 768px) {
    .hero-section {
        padding: 2rem 0;
    }
    
    .display-4 {
        font-size: 2rem;
    }
    
    .lead {
        font-size: 1rem;
    }
}
</style>

<script>
function getVideoId(url) {
    let videoId = '';
    
    // RuTube
    if (url.includes('rutube.ru')) {
        const match = url.match(/(?:rutube\.ru\/video\/|rutube\.ru\/play\/embed\/)([a-zA-Z0-9]+)/);
        if (match) videoId = match[1];
    }
    
    // Dzen
    else if (url.includes('dzen.ru')) {
        const match = url.match(/(?:dzen\.ru\/video\/|dzen\.ru\/play\/embed\/)([a-zA-Z0-9]+)/);
        if (match) videoId = match[1];
    }
    
    // VK
    else if (url.includes('vk.com')) {
        const match = url.match(/video(-?\d+_\d+)/);
        if (match) videoId = match[1];
    }
    
    return videoId;
}
</script> 