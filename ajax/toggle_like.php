<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit();
}

$video_id = isset($_POST['video_id']) ? (int)$_POST['video_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$video_id) {
    echo json_encode(['success' => false, 'message' => 'Неверный ID видео']);
    exit();
}

try {
    if ($action === 'like') {
        // Проверяем, не поставил ли пользователь уже лайк
        $stmt = $db->prepare("SELECT id FROM video_likes WHERE video_id = ? AND user_id = ?");
        $stmt->execute([$video_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            // Добавляем лайк
            $stmt = $db->prepare("INSERT INTO video_likes (video_id, user_id) VALUES (?, ?)");
            $stmt->execute([$video_id, $_SESSION['user_id']]);
        }
    } else {
        // Удаляем лайк
        $stmt = $db->prepare("DELETE FROM video_likes WHERE video_id = ? AND user_id = ?");
        $stmt->execute([$video_id, $_SESSION['user_id']]);
    }

    // Получаем обновленное количество лайков
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM video_likes WHERE video_id = ?");
    $stmt->execute([$video_id]);
    $result = $stmt->fetch();

    echo json_encode(['success' => true, 'likes_count' => $result['count']]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
} 