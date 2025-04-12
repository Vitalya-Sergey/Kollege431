<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

$video_id = isset($_GET['video_id']) ? (int)$_GET['video_id'] : 0;

if (!$video_id) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $db->prepare("
        SELECT c.*, u.username, u.college_name
        FROM video_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.video_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$video_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Форматируем дату для каждого комментария
    foreach ($comments as &$comment) {
        $comment['created_at'] = date('d.m.Y H:i', strtotime($comment['created_at']));
    }

    echo json_encode($comments);
} catch (PDOException $e) {
    echo json_encode([]);
} 