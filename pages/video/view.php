<?php
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$video_id) {
    header("Location: index.php?page=colleges");
    exit();
}

// Get video info
$stmt = $db->prepare("
    SELECT v.*, u.college_name, GROUP_CONCAT(t.name) as tags 
    FROM videos v 
    LEFT JOIN users u ON v.college_id = u.id
    LEFT JOIN video_tags vt ON v.id = vt.video_id
    LEFT JOIN tags t ON vt.tag_id = t.id
    WHERE v.id = ?
    GROUP BY v.id
");
$stmt->execute([$video_id]);
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
});
</script> 