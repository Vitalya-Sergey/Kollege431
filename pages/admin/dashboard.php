<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Handle actions
if (isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'delete_college':
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'college'");
                $stmt->execute([(int)$_GET['id']]);
            }
            break;
        case 'delete_video':
            if (isset($_GET['id'])) {
                $stmt = $db->prepare("DELETE FROM videos WHERE id = ?");
                $stmt->execute([(int)$_GET['id']]);
            }
            break;
    }
}

// Get all colleges
$stmt = $db->query("SELECT * FROM users WHERE role = 'college' ORDER BY created_at DESC");
$colleges = $stmt->fetchAll();

// Get all videos
$stmt = $db->query("SELECT v.*, u.college_name 
                    FROM videos v 
                    JOIN users u ON v.college_id = u.id 
                    ORDER BY v.created_at DESC");
$videos = $stmt->fetchAll();
?>

<div class="container-fluid py-4">
    <!-- Colleges Section -->
    <section id="colleges" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Колледжи</h2>
            <a href="index.php?page=admin&action=add_college" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Добавить колледж
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Название колледжа</th>
                                <th>Логин</th>
                                <th>Email</th>
                                <th>Дата создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($colleges as $college): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($college['college_name']); ?></td>
                                    <td><?php echo htmlspecialchars($college['username']); ?></td>
                                    <td><?php echo htmlspecialchars($college['email']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($college['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="index.php?page=college&id=<?php echo $college['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="index.php?page=admin&action=edit_college&id=<?php echo $college['id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="index.php?page=admin&action=delete_college&id=<?php echo $college['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Вы уверены, что хотите удалить этот колледж?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Videos Section -->
    <section id="videos">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Видео</h2>
            <a href="index.php?page=admin&action=add_video" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Добавить видео
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Название</th>
                                <th>Колледж</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($videos as $video): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td><?php echo htmlspecialchars($video['college_name']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($video['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="index.php?page=video&id=<?php echo $video['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="index.php?page=admin&action=edit_video&id=<?php echo $video['id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="index.php?page=admin&action=delete_video&id=<?php echo $video['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Вы уверены, что хотите удалить это видео?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.btn-group {
    gap: 5px;
}

.table td {
    vertical-align: middle;
}
</style> 