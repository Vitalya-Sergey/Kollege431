<?php
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$video_id) {
    header("Location: index.php?page=colleges");
    exit();
}

// Get video info
$query = "SELECT v.*, u.college_name, GROUP_CONCAT(t.name) as tags 
          FROM videos v 
          JOIN users u ON v.college_id = u.id
          LEFT JOIN video_tags vt ON v.id = vt.video_id
          LEFT JOIN tags t ON vt.tag_id = t.id
          WHERE v.id = ?";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $query .= " AND v.status = 'approved'";
}

$query .= " GROUP BY v.id";

$stmt = $db->prepare($query);
$stmt->execute([$video_id]);
$video = $stmt->fetch();

if (!$video) {
    header("Location: index.php?page=colleges");
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?page=login");
        exit();
    }
    
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $db->prepare("INSERT INTO comments (video_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$video_id, $_SESSION['user_id'], $comment]);
    }
}

// Get comments
$stmt = $db->prepare("SELECT c.*, u.college_name 
                       FROM comments c 
                       JOIN users u ON c.user_id = u.id 
                       WHERE c.video_id = ? 
                       ORDER BY c.created_at DESC");
$stmt->execute([$video_id]);
$comments = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="card-title"><?php echo htmlspecialchars($video['title']); ?></h2>
                <p class="card-text">
                    <small class="text-muted">
                        Колледж: <?php echo htmlspecialchars($video['college_name']); ?>
                    </small>
                </p>
                <p class="card-text"><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                
                <?php if(!empty($video['tags'])): ?>
                    <div class="mb-3">
                        <?php foreach(explode(',', $video['tags']) as $tag): ?>
                            <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <p class="card-text">
                        <small class="text-muted">
                            Статус: <?php echo $video['status']; ?>
                        </small>
                    </p>
                </div>
                
                <?php if(isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $video['college_id'] || $_SESSION['role'] == 'admin')): ?>
                    <div class="mt-3">
                        <a href="index.php?page=video&id=<?php echo $video_id; ?>&action=edit" class="btn btn-secondary">Редактировать</a>
                        <a href="index.php?page=video&id=<?php echo $video_id; ?>&action=delete" class="btn btn-danger" onclick="return confirm('Вы уверены?')">Удалить</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Комментарии</h3>
            </div>
            <div class="card-body">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <textarea class="form-control" name="comment" rows="3" placeholder="Оставьте комментарий..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Отправить</button>
                    </form>
                <?php else: ?>
                    <p>Пожалуйста, <a href="index.php?page=login">войдите</a>, чтобы оставить комментарий.</p>
                <?php endif; ?>
                
                <div class="mt-4">
                    <?php foreach($comments as $comment): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">
                                    <?php echo htmlspecialchars($comment['college_name']); ?>
                                    <small><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></small>
                                </h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div> 