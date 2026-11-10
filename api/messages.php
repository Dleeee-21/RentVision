<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $user = requireAuth();
        $conn = db();

        $stmt = $conn->prepare('
            SELECT m.*, u.name as sender_name, u.avatar as sender_avatar
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.receiver_id = ?
            ORDER BY m.created_at DESC
        ');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();


        $stmt2 = $conn->prepare('SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0');
        $stmt2->bind_param('i', $user['id']);
        $stmt2->execute();
        $unread = $stmt2->get_result()->fetch_assoc()['count'];
        $stmt2->close();

        jsonResponse(200, ['messages' => $messages, 'unread' => (int)$unread]);
        break;

    case 'POST':
        $user  = requireAuth();
        $input = getInput();
        $conn  = db();

        $receiverId = (int)($input['receiver_id'] ?? 0);
        $subject    = sanitize($input['subject'] ?? '');
        $body       = sanitize($input['body'] ?? '');

        if (!$receiverId || !$body) {
            jsonResponse(400, ['error' => 'receiver_id dan body wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->bind_param('i', $receiverId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            jsonResponse(404, ['error' => 'Penerima tidak ditemukan']);
        }
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiss', $user['id'], $receiverId, $subject, $body);
        $stmt->execute();
        $msgId = $conn->insert_id;
        $stmt->close();

        jsonResponse(201, ['message' => 'Pesan berhasil dikirim', 'message_id' => $msgId]);
        break;

    case 'PUT':
        $user = requireAuth();
        $conn = db();


        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?');
            $stmt->bind_param('ii', $id, $user['id']);
            $stmt->execute();
            $stmt->close();
            jsonResponse(200, ['message' => 'Pesan ditandai sudah dibaca']);
        }


        $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $stmt->close();
        jsonResponse(200, ['message' => 'Semua pesan ditandai sudah dibaca']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}