<?php
// Get featured colleges (those with most videos)
$stmt = $db->query("SELECT u.*, COUNT(v.id) as video_count 
                    FROM users u 
                    LEFT JOIN videos v ON u.id = v.college_id AND v.status = 'approved'
                    WHERE u.role = 'college'
                    GROUP BY u.id 
                    ORDER BY video_count DESC 
                    LIMIT 3");
$featured_colleges = $stmt->fetchAll();

// Get recent videos
$stmt = $db->query("SELECT v.*, u.college_name 
                    FROM videos v 
                    JOIN users u ON v.college_id = u.id 
                    WHERE v.status = 'approved'
                    ORDER BY v.created_at DESC 
                    LIMIT 6");
$recent_videos = $stmt->fetchAll();
?>

<div class="jumbotron bg-light p-5 rounded-3 mb-4">
    <h1 class="display-4">Добро пожаловать на платформу колледжей</h1>
    <p class="lead">Здесь вы можете найти видео материалы от различных учебных заведений, получить информацию о колледжах и оставить свои комментарии.</p>
    <hr class="my-4">
    <p>Начните просмотр или войдите в систему, чтобы получить доступ к дополнительным возможностям.</p>
    <div class="mt-4">
        <a href="index.php?page=colleges" class="btn btn-primary btn-lg me-2">Смотреть колледжи</a>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <a href="index.php?page=login" class="btn btn-outline-primary btn-lg">Войти</a>
        <?php endif; ?>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-12">
        <h2 class="mb-4">Популярные колледжи</h2>
        <div class="row">
            <?php foreach($featured_colleges as $college): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($college['college_name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars(substr($college['description'], 0, 100)) . '...'; ?></p>
                            <p class="card-text">
                                <small class="text-muted">
                                    Видео: <?php echo $college['video_count']; ?>
                                </small>
                            </p>
                            <a href="index.php?page=college&id=<?php echo $college['id']; ?>" class="btn btn-primary">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Недавние видео</h2>
        <div class="row">
            <?php foreach($recent_videos as $video): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($video['title']); ?></h5>
                            <p class="card-text">
                                <small class="text-muted">
                                    Колледж: <?php echo htmlspecialchars($video['college_name']); ?>
                                </small>
                            </p>
                            <p class="card-text"><?php echo htmlspecialchars(substr($video['description'], 0, 100)) . '...'; ?></p>
                            <a href="index.php?page=video&id=<?php echo $video['id']; ?>" class="btn btn-primary">Смотреть</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div> 