<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $user = requireAuth();
        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Akses ditolak']);
        }

        $conn   = db();
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $countResult = $conn->query('SELECT COUNT(*) as total FROM reviews');
        $total = $countResult->fetch_assoc()['total'];

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $conn->prepare('
            SELECT r.*, u.name as user_name, p.name as product_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN products p ON r.product_id = p.id
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ');
        $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();

        jsonResponse(200, [
            'items'       => $reviews,
            'total'       => (int)$total,
            'page'        => $page,
            'total_pages' => $totalPages,
        ]);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
