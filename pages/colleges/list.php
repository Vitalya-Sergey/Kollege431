<?php
// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$tag = isset($_GET['tag']) ? $_GET['tag'] : '';

// Build query
$query = "SELECT u.*, COUNT(v.id) as video_count, GROUP_CONCAT(DISTINCT t.name) as tags 
          FROM users u 
          LEFT JOIN videos v ON u.id = v.college_id AND v.status = 'approved'
          LEFT JOIN video_tags vt ON v.id = vt.video_id
          LEFT JOIN tags t ON vt.tag_id = t.id
          WHERE u.role = 'college'";

$params = [];

if (!empty($search)) {
    $query .= " AND (u.college_name LIKE ? OR u.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($tag)) {
    $query .= " AND t.name = ?";
    $params[] = $tag;
}

$query .= " GROUP BY u.id";

$stmt = $db->prepare($query);
$stmt->execute($params);
$colleges = $stmt->fetchAll();

// Get all tags for filter
$stmt = $db->query("SELECT DISTINCT t.name FROM tags t JOIN video_tags vt ON t.id = vt.tag_id");
$all_tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="content-wrapper">
    <div class="row mb-4">
        <div class="col-md-6">
            <form method="GET" action="">
                <input type="hidden" name="page" value="colleges">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Поиск колледжей..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">Поиск</button>
                </div>
            </form>
        </div>
        <div class="col-md-6">
            <form method="GET" action="">
                <input type="hidden" name="page" value="colleges">
                <div class="input-group">
                    <select class="form-select" name="tag">
                        <option value="">Все теги</option>
                        <?php foreach($all_tags as $tag_name): ?>
                            <option value="<?php echo htmlspecialchars($tag_name); ?>" <?php echo $tag === $tag_name ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tag_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="submit">Фильтр</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row college-list">
        <?php foreach($colleges as $college): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($college['college_name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars(substr($college['description'], 0, 100)) . '...'; ?></p>
                        <p class="card-text">
                            <small class="text-muted">
                                Видео: <?php echo $college['video_count']; ?>
                            </small>
                        </p>
                        <?php if(!empty($college['tags'])): ?>
                            <div class="mb-2">
                                <?php foreach(explode(',', $college['tags']) as $tag): ?>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <a href="index.php?page=college&id=<?php echo $college['id']; ?>" class="btn btn-primary mt-auto">Подробнее</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div> 