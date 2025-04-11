<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Get all colleges for dropdown
$stmt = $db->query("SELECT id, college_name FROM users WHERE role = 'college' ORDER BY college_name");
$colleges = $stmt->fetchAll();

// Создаем директорию для загрузки, если её нет
$upload_dir = __DIR__ . '/../../uploads/videos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $college_id = (int)($_POST['college_id'] ?? 0);
    $upload_type = $_POST['upload_type'] ?? 'url';
    $video_url = trim($_POST['video_url'] ?? '');
    $tags = isset($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];
    
    // Validate input
    if (empty($title) || empty($college_id)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } else {
        try {
            $db->beginTransaction();
            $file_path = '';

            if ($upload_type === 'file') {
                if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                    $file_info = pathinfo($_FILES['video_file']['name']);
                    $file_extension = strtolower($file_info['extension']);
                    
                    // Проверяем расширение файла
                    $allowed_extensions = ['mp4', 'webm', 'ogg'];
                    if (!in_array($file_extension, $allowed_extensions)) {
                        throw new Exception('Неподдерживаемый формат файла. Разрешены: ' . implode(', ', $allowed_extensions));
                    }
                    
                    // Генерируем уникальное имя файла
                    $file_name = uniqid('video_') . '.' . $file_extension;
                    $file_path = 'uploads/videos/' . $file_name;
                    $full_path = $upload_dir . $file_name;
                    
                    // Проверяем размер файла (максимум 500MB)
                    if ($_FILES['video_file']['size'] > 500 * 1024 * 1024) {
                        throw new Exception('Размер файла не должен превышать 500MB');
                    }
                    
                    // Перемещаем загруженный файл
                    if (!move_uploaded_file($_FILES['video_file']['tmp_name'], $full_path)) {
                        throw new Exception('Ошибка при загрузке файла');
                    }
                } else {
                    throw new Exception('Пожалуйста, выберите файл для загрузки');
                }
            } else {
                // Проверка URL
                if (empty($video_url)) {
                    throw new Exception('URL видео обязателен');
                }
                if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
                    throw new Exception('Неверный формат URL');
                }
                $file_path = $video_url;
            }

            // Insert video
            $stmt = $db->prepare("INSERT INTO videos (title, description, file_path, college_id, status, created_at) VALUES (?, ?, ?, ?, 'approved', NOW())");
            $stmt->execute([$title, $description, $file_path, $college_id]);
            $video_id = $db->lastInsertId();

            // Process tags
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
            $success = 'Видео успешно добавлено';
            
            // Redirect to admin dashboard
            header("Location: index.php?page=admin");
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            // Если произошла ошибка и файл был загружен, удаляем его
            if (isset($full_path) && file_exists($full_path)) {
                unlink($full_path);
            }
            $error = $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Добавить видео</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?page=admin&action=add_video" class="video-upload-form" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="college_id" class="form-label">Колледж</label>
                        <select class="form-select" id="college_id" name="college_id" required>
                            <option value="">Выберите колледж</option>
                            <?php foreach($colleges as $college): ?>
                                <option value="<?php echo $college['id']; ?>"><?php echo htmlspecialchars($college['college_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label required">Название видео</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Тип загрузки</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="upload_type" id="upload_type_url" value="url" checked>
                            <label class="form-check-label" for="upload_type_url">
                                Ссылка на видео
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="upload_type" id="upload_type_file" value="file">
                            <label class="form-check-label" for="upload_type_file">
                                Загрузить файл
                            </label>
                        </div>
                    </div>

                    <div id="url_upload_block" class="mb-3">
                        <label for="video_url" class="form-label">URL видео</label>
                        <input type="url" class="form-control" id="video_url" name="video_url"
                               placeholder="https://...">
                        <small class="text-muted">Поддерживаются ссылки с rutube, dzen, VK и других видеохостингов</small>
                    </div>

                    <div id="file_upload_block" class="mb-3" style="display: none;">
                        <label for="video_file" class="form-label">Видео файл</label>
                        <input type="file" class="form-control" id="video_file" name="video_file" accept="video/mp4,video/webm,video/ogg">
                        <small class="text-muted">Максимальный размер: 500MB. Поддерживаемые форматы: MP4, WebM, OGG</small>
                    </div>

                    <div class="mb-3">
                        <label for="tags" class="form-label">Теги</label>
                        <input type="text" class="form-control" id="tags" name="tags" 
                               placeholder="Введите теги через запятую">
                        <small class="text-muted">Например: математика, физика, химия</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Добавить видео</button>
                        <a href="index.php?page=admin" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlUploadBlock = document.getElementById('url_upload_block');
    const fileUploadBlock = document.getElementById('file_upload_block');
    const videoUrlInput = document.getElementById('video_url');
    const videoFileInput = document.getElementById('video_file');

    // Обработчик переключения типа загрузки
    document.querySelectorAll('input[name="upload_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'url') {
                urlUploadBlock.style.display = 'block';
                fileUploadBlock.style.display = 'none';
                videoUrlInput.required = true;
                videoFileInput.required = false;
            } else {
                urlUploadBlock.style.display = 'none';
                fileUploadBlock.style.display = 'block';
                videoUrlInput.required = false;
                videoFileInput.required = true;
            }
        });
    });

    // Проверка размера файла перед отправкой
    document.querySelector('form').addEventListener('submit', function(e) {
        const uploadType = document.querySelector('input[name="upload_type"]:checked').value;
        if (uploadType === 'file') {
            const fileSize = videoFileInput.files[0]?.size || 0;
            const maxSize = 500 * 1024 * 1024; // 500MB
            if (fileSize > maxSize) {
                e.preventDefault();
                alert('Размер файла не должен превышать 500MB');
            }
        }
    });
});
</script> 