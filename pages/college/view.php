<?php
// Get college ID from URL
$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get college data with videos count
$stmt = $db->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM videos WHERE college_id = u.id) as videos_count
    FROM users u 
    WHERE u.id = ? AND u.role = 'college'
");
$stmt->execute([$college_id]);
$college = $stmt->fetch();

if (!$college) {
    header("Location: index.php");
    exit();
}

// Get latest videos
$stmt = $db->prepare("
    SELECT * FROM videos 
    WHERE college_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$college_id]);
$latest_videos = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8">
            <!-- College Info Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h2 mb-0"><?php echo htmlspecialchars($college['college_name']); ?></h1>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <div class="btn-group">
                                <a href="index.php?page=admin&action=edit_college&id=<?php echo $college_id; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-edit me-2"></i>Редактировать
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($college['description'])): ?>
                        <div class="mb-4">
                            <h5 class="text-muted mb-3">О колледже</h5>
                            <p class="text-justify"><?php echo nl2br(htmlspecialchars($college['description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-envelope text-primary fa-fw fa-lg"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block">Email</small>
                                    <span><?php echo htmlspecialchars($college['email']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user text-primary fa-fw fa-lg"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block">Логин</small>
                                    <span><?php echo htmlspecialchars($college['username']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-calendar text-primary fa-fw fa-lg"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block">Дата регистрации</small>
                                    <span><?php echo date('d.m.Y', strtotime($college['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-video text-primary fa-fw fa-lg"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <small class="text-muted d-block">Количество видео</small>
                                    <span><?php echo $college['videos_count']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Videos -->
            <?php if (!empty($latest_videos)): ?>
                <div class="card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Последние видео</h5>
                            <a href="index.php?page=videos&college_id=<?php echo $college_id; ?>" class="btn btn-link">
                                Все видео
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php foreach($latest_videos as $video): ?>
                                <div class="col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="ratio ratio-16x9">
                                            <?php if (strpos($video['video_url'], 'youtube.com') !== false): ?>
                                                <?php
                                                    $video_id = '';
                                                    if (preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $video['video_url'], $matches)) {
                                                        $video_id = $matches[1];
                                                    }
                                                ?>
                                                <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                                        title="YouTube video" 
                                                        allowfullscreen></iframe>
                                            <?php else: ?>
                                                <video src="<?php echo htmlspecialchars($video['video_url']); ?>" 
                                                       controls>
                                                    Ваш браузер не поддерживает видео.
                                                </video>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title text-truncate">
                                                <?php echo htmlspecialchars($video['title']); ?>
                                            </h5>
                                            <p class="card-text small text-muted">
                                                <?php echo date('d.m.Y', strtotime($video['created_at'])); ?>
                                            </p>
                                            <a href="index.php?page=video&id=<?php echo $video['id']; ?>" 
                                               class="stretched-link"></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Статистика</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Всего видео</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $college['videos_count']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Дней на платформе</span>
                        <span class="badge bg-primary rounded-pill">
                            <?php 
                                $days = ceil((time() - strtotime($college['created_at'])) / (60 * 60 * 24));
                                echo $days;
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-justify {
    text-align: justify;
}
.card {
    border-radius: 0.5rem;
    border: 1px solid rgba(0,0,0,.125);
}
.card-header {
    background-color: transparent;
    border-bottom: 1px solid rgba(0,0,0,.125);
}
.shadow-sm {
    box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important;
}
</style> 