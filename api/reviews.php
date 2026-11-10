<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $conn = db();

        if (isset($_GET['mine'])) {
            $user = requireAuth();
            $stmt = $conn->prepare('
                SELECT r.*, p.name as product_name, p.slug as product_slug, b.booking_code
                FROM reviews r
                JOIN products p ON r.product_id = p.id
                LEFT JOIN bookings b ON r.booking_id = b.id
                WHERE r.user_id = ?
                ORDER BY r.created_at DESC
            ');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();

            $mine = [];
            while ($row = $result->fetch_assoc()) {
                $mine[] = $row;
            }
            $stmt->close();

            jsonResponse(200, ['reviews' => $mine]);
        }

        $productId = (int)($_GET['product_id'] ?? 0);

        if (!$productId) {
            jsonResponse(400, ['error' => 'product_id wajib diisi']);
        }

        $stmt = $conn->prepare('
            SELECT r.*, u.name as user_name, u.avatar as user_avatar
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ?
            ORDER BY r.created_at DESC
        ');
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();

        // avg rating
        $stmt2 = $conn->prepare('SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE product_id = ?');
        $stmt2->bind_param('i', $productId);
        $stmt2->execute();
        $stats = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        jsonResponse(200, [
            'reviews'    => $reviews,
            'avg_rating' => round((float)($stats['avg_rating'] ?? 0), 1),
            'total'      => (int)($stats['total'] ?? 0),
        ]);
        break;

    case 'POST':
        $user  = requireAuth();
        $input = getInput();
        $conn  = db();

        $bookingId = (int)($input['booking_id'] ?? 0);
        $productId = (int)($input['product_id'] ?? 0);
        $rating    = (int)($input['rating'] ?? 0);
        $comment   = sanitize($input['comment'] ?? '');

        if (!$bookingId || !$productId || $rating < 1 || $rating > 5) {
            jsonResponse(400, ['error' => 'booking_id, product_id, dan rating (1-5) wajib diisi']);
        }

        // Check product exists
        $stmt = $conn->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            jsonResponse(404, ['error' => 'Produk tidak ditemukan']);
        }
        $stmt->close();

        // Booking harus milik user, sudah dikembalikan (completed), dan memuat produk ini
        $stmt = $conn->prepare('
            SELECT COUNT(*) as c
            FROM bookings b
            JOIN booking_items bi ON bi.booking_id = b.id
            WHERE b.id = ?
              AND b.user_id = ?
              AND bi.product_id = ?
              AND b.status = ?
        ');
        $completed = 'completed';
        $stmt->bind_param('iiis', $bookingId, $user['id'], $productId, $completed);
        $stmt->execute();
        $eligible = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();

        if (!$eligible) {
            jsonResponse(400, ['error' => 'Produk hanya bisa dirating setelah booking selesai dan dikembalikan']);
        }

        // Satu ulasan per produk per booking
        $stmt = $conn->prepare('SELECT id FROM reviews WHERE user_id = ? AND booking_id = ? AND product_id = ?');
        $stmt->bind_param('iii', $user['id'], $bookingId, $productId);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            jsonResponse(409, ['error' => 'Anda sudah memberikan ulasan untuk produk ini pada booking tersebut']);
        }
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO reviews (user_id, product_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iiiis', $user['id'], $productId, $bookingId, $rating, $comment);
        $stmt->execute();
        $reviewId = $conn->insert_id;
        $stmt->close();

        // Update product rating
        $conn->query("
            UPDATE products SET
                rating = (SELECT AVG(rating) FROM reviews WHERE product_id = {$productId}),
                total_reviews = (SELECT COUNT(*) FROM reviews WHERE product_id = {$productId})
            WHERE id = {$productId}
        ");

        jsonResponse(201, ['message' => 'Ulasan berhasil ditambahkan', 'review_id' => $reviewId]);
        break;

    case 'DELETE':
        $user = requireAuth();
        $id   = (int)($_GET['id'] ?? 0);
        $conn = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'Review ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT * FROM reviews WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $review = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$review) {
            jsonResponse(404, ['error' => 'Review tidak ditemukan']);
        }

        if ($user['role'] !== 'Dev' && $review['user_id'] !== $user['id']) {
            jsonResponse(403, ['error' => 'Anda tidak memiliki akses']);
        }

        $productId = $review['product_id'];

        $stmt = $conn->prepare('DELETE FROM reviews WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        // Recalculate product rating
        $conn->query("
            UPDATE products SET
                rating = COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = {$productId}), 0),
                total_reviews = (SELECT COUNT(*) FROM reviews WHERE product_id = {$productId})
            WHERE id = {$productId}
        ");

        jsonResponse(200, ['message' => 'Review berhasil dihapus']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
