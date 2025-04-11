<?php
// Проверяем авторизацию и получаем ID видео
$video_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$video_id || !isset($_SESSION['user_id'])) {
    header("Location: index.php?page=colleges");
    exit();
}

// Получаем информацию о видео
$stmt = $db->prepare("
    SELECT v.*, GROUP_CONCAT(t.name) as tags 
    FROM videos v 
    LEFT JOIN video_tags vt ON v.id = vt.video_id
    LEFT JOIN tags t ON vt.tag_id = t.id
    WHERE v.id = ?
    GROUP BY v.id
");
$stmt->execute([$video_id]);
$video = $stmt->fetch();

// Проверяем права доступа (владелец видео или админ)
if (!$video || ($_SESSION['user_id'] != $video['college_id'] && $_SESSION['role'] != 'admin')) {
    header("Location: index.php?page=colleges");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];
    $status = $_POST['status'] ?? $video['status'];

    // Валидация
    if (empty($title)) {
        $error = 'Название видео обязательно';
    } else {
        try {
            $db->beginTransaction();

            // Обновляем основную информацию о видео
            if (!empty($video_url) && $video_url !== $video['file_path']) {
                // Если изменился URL видео
                $stmt = $db->prepare("UPDATE videos SET title = ?, description = ?, file_path = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, $video_url, $status, $video_id]);
            } else {
                // Если URL не менялся
                $stmt = $db->prepare("UPDATE videos SET title = ?, description = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, $status, $video_id]);
            }

            // Удаляем старые теги
            $stmt = $db->prepare("DELETE FROM video_tags WHERE video_id = ?");
            $stmt->execute([$video_id]);

            // Добавляем новые теги
            if (!empty($tags)) {
                foreach ($tags as $tag_name) {
                    if (empty($tag_name)) continue;

                    // Проверяем существование тега
                    $stmt = $db->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tag_name]);
                    $tag = $stmt->fetch();

                    if (!$tag) {
                        // Создаем новый тег
                        $stmt = $db->prepare("INSERT INTO tags (name) VALUES (?)");
                        $stmt->execute([$tag_name]);
                        $tag_id = $db->lastInsertId();
                    } else {
                        $tag_id = $tag['id'];
                    }

                    // Связываем тег с видео
                    $stmt = $db->prepare("INSERT INTO video_tags (video_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$video_id, $tag_id]);
                }
            }

            $db->commit();
            $success = 'Видео успешно обновлено';

            // Обновляем информацию о видео для отображения
            $stmt = $db->prepare("
                SELECT v.*, GROUP_CONCAT(t.name) as tags 
                FROM videos v 
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

<div class="content-wrapper">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title mb-4">Редактировать видео</h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=video&id=<?php echo $video_id; ?>&action=edit" class="video-edit-form">
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
                            <label for="video_url" class="form-label">URL видео</label>
                            <input type="url" class="form-control" id="video_url" name="video_url"
                                   value="<?php echo htmlspecialchars($video['file_path']); ?>"
                                   placeholder="Оставьте пустым, если не хотите менять">
                            <small class="text-muted">Введите новый URL только если хотите изменить источник видео</small>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Теги</label>
                            <input type="text" class="form-control" id="tags" name="tags" 
                                   placeholder="Введите теги через запятую"
                                   value="<?php echo htmlspecialchars($video['tags'] ?? ''); ?>">
                            <small class="text-muted">Например: математика, физика, химия</small>
                        </div>

                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <div class="mb-3">
                                <label for="status" class="form-label">Статус</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending" <?php echo $video['status'] === 'pending' ? 'selected' : ''; ?>>На модерации</option>
                                    <option value="approved" <?php echo $video['status'] === 'approved' ? 'selected' : ''; ?>>Одобрено</option>
                                    <option value="rejected" <?php echo $video['status'] === 'rejected' ? 'selected' : ''; ?>>Отклонено</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                            <a href="index.php?page=video&id=<?php echo $video_id; ?>" class="btn btn-secondary">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.video-edit-form .form-label.required:after {
    content: "*";
    color: red;
    margin-left: 4px;
}

.video-edit-form textarea {
    resize: vertical;
    min-height: 100px;
}

.alert {
    margin-bottom: 20px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Подтверждение при уходе со страницы с несохраненными изменениями
    const form = document.querySelector('.video-edit-form');
    let formChanged = false;

    form.addEventListener('input', function() {
        formChanged = true;
    });

    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    form.addEventListener('submit', function() {
        formChanged = false;
    });
});
</script> 