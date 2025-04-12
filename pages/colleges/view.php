<?php
$college_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($college_id <= 0) {
    header("Location: index.php?page=colleges");
    exit();
}

// Get college info
$stmt = $db->prepare("
    SELECT u.*, 
           COUNT(DISTINCT v.id) as video_count,
           COUNT(DISTINCT vl.id) as total_likes,
           COUNT(DISTINCT vc.id) as total_comments
    FROM users u
    LEFT JOIN videos v ON u.id = v.college_id AND v.status = 'approved'
    LEFT JOIN video_likes vl ON v.id = vl.video_id
    LEFT JOIN video_comments vc ON v.id = vc.video_id
    WHERE u.id = ? AND u.role = 'college'
    GROUP BY u.id
");
$stmt->execute([$college_id]);
$college = $stmt->fetch();

if (!$college) {
    header("Location: index.php?page=colleges");
    exit();
}

// Get college's videos
$stmt = $db->prepare("
    SELECT v.*, 
           COUNT(DISTINCT vl.id) as likes_count,
           COUNT(DISTINCT vc.id) as comments_count
    FROM videos v
    LEFT JOIN video_likes vl ON v.id = vl.video_id
    LEFT JOIN video_comments vc ON v.id = vc.video_id
    WHERE v.college_id = ? AND v.status = 'approved'
    GROUP BY v.id
    ORDER BY v.created_at DESC
");
$stmt->execute([$college_id]);
$videos = $stmt->fetchAll();
?>

<div class="container py-4">
    <!-- College Header -->
    <div class="card mb-4">
        <div class="card-body">
            <h1 class="card-title"><?php echo htmlspecialchars($college['college_name']); ?></h1>
            <p class="card-text"><?php echo nl2br(htmlspecialchars($college['description'])); ?></p>
            
            <div class="d-flex justify-content-start align-items-center mb-3">
                <div class="btn-group">
                    <span class="badge bg-primary me-2">
                        <i class="fas fa-video me-1"></i><?php echo $college['video_count']; ?> видео
                    </span>
                    <span class="badge bg-success me-2">
                        <i class="fas fa-heart me-1"></i><?php echo $college['total_likes']; ?> лайков
                    </span>
                    <span class="badge bg-info">
                        <i class="fas fa-comment me-1"></i><?php echo $college['total_comments']; ?> комментариев
                    </span>
                </div>
            </div>

            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $college_id): ?>
                <a href="index.php?page=college&action=edit" class="btn btn-outline-primary">
                    <i class="fas fa-edit me-2"></i>Редактировать профиль
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Videos Grid -->
    <h2 class="mb-4">Видео</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach($videos as $video): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="ratio ratio-16x9">
                        <?php if (strpos($video['file_path'], 'youtube.com') !== false): ?>
                            <?php
                                $video_id = '';
                                if (preg_match('/[\\?\\&]v=([^\\?\\&]+)/', $video['file_path'], $matches)) {
                                    $video_id = $matches[1];
                                }
                            ?>
                            <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" 
                                    title="YouTube video" 
                                    allowfullscreen></iframe>
                        <?php else: ?>
                            <video src="<?php echo htmlspecialchars($video['file_path']); ?>" 
                                   controls>
                                Ваш браузер не поддерживает видео.
                            </video>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($video['title']); ?></h5>
                        <p class="card-text small">
                            <?php echo htmlspecialchars(substr($video['description'], 0, 100)) . '...'; ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary like-btn" 
                                        id="like-btn-<?php echo $video['id']; ?>"
                                        onclick="toggleLike(<?php echo $video['id']; ?>)"
                                        <?php echo isset($video['user_liked']) && $video['user_liked'] ? 'data-liked="true"' : ''; ?>>
                                    <i class="fa<?php echo isset($video['user_liked']) && $video['user_liked'] ? 's' : 'r'; ?> fa-heart"></i>
                                    <span id="like-count-<?php echo $video['id']; ?>" class="ms-1">
                                        <?php echo $video['likes_count']; ?>
                                    </span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        onclick="showComments(<?php echo $video['id']; ?>)">
                                    <i class="fas fa-comment"></i>
                                    <span class="ms-1"><?php echo $video['comments_count']; ?></span>
                                </button>
                            </div>
                            <small class="text-muted">
                                <?php echo date('d.m.Y', strtotime($video['created_at'])); ?>
                            </small>
                        </div>

                        <!-- Comments Section (Hidden by default) -->
                        <div id="comments-section-<?php echo $video['id']; ?>" class="comments-section" style="display: none;">
                            <div class="mb-3">
                                <textarea id="comment-text-<?php echo $video['id']; ?>" 
                                          class="form-control" 
                                          placeholder="Добавить комментарий"></textarea>
                                <button class="btn btn-primary btn-sm mt-2" 
                                        onclick="addComment(<?php echo $video['id']; ?>)">
                                    Отправить
                                </button>
                            </div>
                            <div id="comments-container-<?php echo $video['id']; ?>"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
}

.comments-section {
    border-top: 1px solid #dee2e6;
    padding-top: 1rem;
    margin-top: 1rem;
}

.comment {
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
}

.comment-header {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.comment-body {
    font-size: 0.9rem;
}
</style>

<script>
// Show/hide comments section
function showComments(videoId) {
    const commentsSection = document.getElementById(`comments-section-${videoId}`);
    if (commentsSection.style.display === 'none') {
        commentsSection.style.display = 'block';
        loadComments(videoId);
    } else {
        commentsSection.style.display = 'none';
    }
}

// Initialize comments when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Load comments for any open sections
    document.querySelectorAll('.comments-section').forEach(section => {
        if (section.style.display !== 'none') {
            const videoId = section.id.split('-')[2];
            loadComments(videoId);
        }
    });
});
</script> 