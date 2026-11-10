<?php
require_once __DIR__ . '/config/database.php';

$user = requireAuth();
$conn = db();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        $stmt->close();

        $stmt2 = $conn->prepare('SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt2->bind_param('i', $user['id']);
        $stmt2->execute();
        $unread = $stmt2->get_result()->fetch_assoc()['count'];
        $stmt2->close();

        jsonResponse(200, ['notifications' => $notifications, 'unread' => (int)$unread]);
        break;

    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id, $user['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $stmt->close();
        }
        jsonResponse(200, ['message' => 'Notifikasi ditandai sudah dibaca']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
