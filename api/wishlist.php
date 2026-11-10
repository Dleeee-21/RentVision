<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $user = requireAuth();
        $conn = db();

        $stmt = $conn->prepare('
            SELECT w.*, p.name, p.price_per_day, p.rating, p.total_reviews,
                   c.name as category_name,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as thumbnail
            FROM wishlists w
            JOIN products p ON w.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();

        $wishlists = [];
        while ($row = $result->fetch_assoc()) {
            $wishlists[] = $row;
        }
        $stmt->close();

        jsonResponse(200, ['wishlists' => $wishlists]);
        break;

    case 'POST':
        $user  = requireAuth();
        $input = getInput();
        $conn  = db();

        $productId = (int)($input['product_id'] ?? 0);
        if (!$productId) {
            jsonResponse(400, ['error' => 'product_id wajib diisi']);
        }

        // Toggle wishlist
        $stmt = $conn->prepare('SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $user['id'], $productId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $stmt = $conn->prepare('DELETE FROM wishlists WHERE id = ?');
            $stmt->bind_param('i', $existing['id']);
            $stmt->execute();
            $stmt->close();
            jsonResponse(200, ['message' => 'Dihapus dari wishlist', 'action' => 'removed']);
        } else {
            $stmt = $conn->prepare('INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)');
            $stmt->bind_param('ii', $user['id'], $productId);
            $stmt->execute();
            $stmt->close();
            jsonResponse(201, ['message' => 'Ditambahkan ke wishlist', 'action' => 'added']);
        }
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
