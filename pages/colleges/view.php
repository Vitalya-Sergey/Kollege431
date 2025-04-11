<?php
$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$college_id) {
    header("Location: index.php?page=colleges");
    exit();
}

// Get college info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$college_id]);
$college = $stmt->fetch();

if (!$college) {
    header("Location: index.php?page=colleges");
    exit();
}

// Get videos
$query = "SELECT v.*, GROUP_CONCAT(t.name) as tags 
          FROM videos v 
          LEFT JOIN video_tags vt ON v.id = vt.video_id
          LEFT JOIN tags t ON vt.tag_id = t.id
          WHERE v.college_id = ?";

if (!isset($_SESSION['user_id']) || ($_SESSION['user_id'] != $college_id && $_SESSION['role'] != 'admin')) {
    $query .= " AND v.status = 'approved'";
}

$query .= " GROUP BY v.id ORDER BY v.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$college_id]);
$videos = $stmt->fetchAll();
?>

<div class="content-wrapper">
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="card-title"><?php echo htmlspecialchars($college['college_name']); ?></h2>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($college['description'])); ?></p>
                    
                    <?php if(isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $college_id || $_SESSION['role'] == 'admin')): ?>
                        <div class="mt-3">
                            <a href="index.php?page=video&action=add" class="btn btn-primary">Добавить видео</a>
                            <a href="index.php?page=college&id=<?php echo $college_id; ?>&action=edit" class="btn btn-secondary">Редактировать профиль</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <h3 class="mb-4">Видео</h3>
            <div class="row college-list">
                <?php foreach($videos as $video): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($video['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($video['description'], 0, 100)) . '...'; ?></p>
                                <?php if(!empty($video['tags'])): ?>
                                    <div class="mb-2">
                                        <?php foreach(explode(',', $video['tags']) as $tag): ?>
                                            <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Статус: <?php echo $video['status']; ?>
                                    </small>
                                </p>
                                <div class="mt-auto">
                                    <a href="index.php?page=video&id=<?php echo $video['id']; ?>" class="btn btn-primary">Смотреть</a>
                                    
                                    <?php if(isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $college_id || $_SESSION['role'] == 'admin')): ?>
                                        <a href="index.php?page=video&id=<?php echo $video['id']; ?>&action=edit" class="btn btn-secondary">Редактировать</a>
                                        <a href="index.php?page=video&id=<?php echo $video['id']; ?>&action=delete" class="btn btn-danger" onclick="return confirm('Вы уверены?')">Удалить</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div> 