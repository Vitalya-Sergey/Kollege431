<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get video info
$stmt = $db->prepare("
    SELECT v.*, u.college_name, GROUP_CONCAT(t.name) as tags 
    FROM videos v 
    JOIN users u ON v.college_id = u.id
    LEFT JOIN video_tags vt ON v.id = vt.video_id
    LEFT JOIN tags t ON vt.tag_id = t.id
    WHERE v.id = ?
    GROUP BY v.id
");
$stmt->execute([$video_id]);
$video = $stmt->fetch();

if (!$video) {
    header("Location: index.php?page=admin");
    exit();
}

// Get all colleges for dropdown
$stmt = $db->query("SELECT id, college_name FROM users WHERE role = 'college' ORDER BY college_name");
$colleges = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $college_id = (int)($_POST['college_id'] ?? 0);
    $file_path = trim($_POST['file_path'] ?? '');
    $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];

    if (empty($title) || empty($college_id)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } else {
        try {
            $db->beginTransaction();

            // Update video
            $stmt = $db->prepare("UPDATE videos SET title = ?, description = ?, college_id = ?, file_path = ? WHERE id = ?");
            $stmt->execute([$title, $description, $college_id, $file_path, $video_id]);

            // Delete old tags
            $stmt = $db->prepare("DELETE FROM video_tags WHERE video_id = ?");
            $stmt->execute([$video_id]);

            // Process new tags
            if (!empty($tags)) {
                foreach ($tags as $tag_name) {
                    if (empty($tag_name)) continue;

                    // Check if tag exists
                    $stmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tag_name]);
                    $tag = $stmt->fetch();

                    if (!$tag) {
                        // Create new tag
                        $stmt = $db->prepare("INSERT INTO tags (name) VALUES (?)");
                        $stmt->execute([$tag_name]);
                        $tag_id = $db->lastInsertId();
                    } else {
                        $tag_id = $tag['id'];
                    }

                    // Link tag to video
                    $stmt = $db->prepare("INSERT INTO video_tags (video_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$video_id, $tag_id]);
                }
            }

            $db->commit();
            $success = 'Видео успешно обновлено';
            
            // Refresh video data
            $stmt = $db->prepare("
                SELECT v.*, u.college_name, GROUP_CONCAT(t.name) as tags 
                FROM videos v 
                JOIN users u ON v.college_id = u.id
                LEFT JOIN video_tags vt ON v.id = vt.video_id
                LEFT JOIN tags t ON vt.tag_id = t.id
                WHERE v.id = ?
                GROUP BY v.id
            ");
            $stmt->execute([$video_id]);
            $video = $stmt->fetch();
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Произошла ошибка при обновлении видео';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Редактировать видео</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?page=admin&action=edit_video&id=<?php echo $video_id; ?>">
                    <div class="mb-3">
                        <label for="college_id" class="form-label">Колледж</label>
                        <select class="form-select" id="college_id" name="college_id" required>
                            <option value="">Выберите колледж</option>
                            <?php foreach($colleges as $college): ?>
                                <option value="<?php echo $college['id']; ?>" <?php echo $college['id'] == $video['college_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($college['college_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label required">Название видео</label>
                        <input type="text" class="form-control" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($video['title']); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($video['description']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="file_path" class="form-label">URL видео</label>
                        <input type="url" class="form-control" id="file_path" name="file_path" required
                               value="<?php echo htmlspecialchars($video['file_path']); ?>">
                        <small class="text-muted">Поддерживаются ссылки с rutube, dzen, VK и других видеохостингов</small>
                    </div>

                    <div class="mb-3">
                        <label for="tags" class="form-label">Теги</label>
                        <input type="text" class="form-control" id="tags" name="tags" 
                               placeholder="Введите теги через запятую"
                               value="<?php echo htmlspecialchars($video['tags'] ?? ''); ?>">
                        <small class="text-muted">Например: математика, физика, химия</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        <a href="index.php?page=admin" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> 