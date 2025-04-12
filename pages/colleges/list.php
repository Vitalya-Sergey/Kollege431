<?php
// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare the base query
$query = "SELECT u.*, 
          COUNT(DISTINCT v.id) as video_count,
          COUNT(DISTINCT vl.id) as total_likes,
          COUNT(DISTINCT vc.id) as total_comments
          FROM users u
          LEFT JOIN videos v ON u.id = v.college_id AND v.status = 'approved'
          LEFT JOIN video_likes vl ON v.id = vl.video_id
          LEFT JOIN video_comments vc ON v.id = vc.video_id
          WHERE u.role = 'college'";

// Add search condition if search term is provided
if (!empty($search)) {
    $query .= " AND (u.college_name LIKE :search OR u.description LIKE :search)";
}

$query .= " GROUP BY u.id ORDER BY u.college_name";

// Prepare and execute the query
$stmt = $db->prepare($query);

if (!empty($search)) {
    $searchTerm = "%{$search}%";
    $stmt->bindParam(':search', $searchTerm);
}

$stmt->execute();
$colleges = $stmt->fetchAll();
?>

<div class="container py-4">
    <h1 class="mb-4">Колледжи</h1>
    
    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <form method="GET" action="index.php" class="d-flex gap-2">
                <input type="hidden" name="page" value="colleges">
                <input type="text" 
                       class="form-control form-control-lg" 
                       id="search" 
                       name="search" 
                       placeholder="Поиск по названию колледжа..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($search)): ?>
        <div class="mb-4">
            <h5 class="text-muted">
                Результаты поиска для "<?php echo htmlspecialchars($search); ?>"
                <small>(найдено: <?php echo count($colleges); ?>)</small>
            </h5>
            <a href="index.php?page=colleges" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times me-1"></i>Сбросить поиск
            </a>
        </div>
    <?php endif; ?>

    <?php if (empty($colleges)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <?php echo empty($search) ? 'Колледжи не найдены.' : 'По вашему запросу ничего не найдено.'; ?>
        </div>
    <?php else: ?>
        <!-- Colleges Grid -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach($colleges as $college): ?>
                <div class="col">
                    <div class="card h-100 hover-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($college['college_name']); ?></h5>
                            <?php if ($college['description']): ?>
                                <p class="card-text"><?php echo htmlspecialchars(mb_substr($college['description'], 0, 150)) . '...'; ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="btn-group">
                                    <span class="badge bg-primary me-2" title="Видео">
                                        <i class="fas fa-video me-1"></i><?php echo $college['video_count']; ?>
                                    </span>
                                    <span class="badge bg-success me-2" title="Лайки">
                                        <i class="fas fa-heart me-1"></i><?php echo $college['total_likes']; ?>
                                    </span>
                                    <span class="badge bg-info" title="Комментарии">
                                        <i class="fas fa-comment me-1"></i><?php echo $college['total_comments']; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="d-grid">
                                <a href="index.php?page=college&id=<?php echo $college['id']; ?>" 
                                   class="btn btn-primary">
                                    Подробнее
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

.badge {
    font-size: 0.9rem;
    padding: 0.5em 0.75em;
}

.card-title {
    font-size: 1.25rem;
    margin-bottom: 1rem;
}

.card-text {
    color: #6c757d;
    margin-bottom: 1.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}
</style> 
}
</style> 