<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $user = requireAuth();
        $conn = db();


        if (isset($_GET['id'])) {
            $id     = (int)$_GET['id'];
            $stmt   = $conn->prepare('SELECT * FROM bookings WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id, $user['id']);
            $stmt->execute();
            $booking = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$booking) {
                jsonResponse(404, ['error' => 'Booking tidak ditemukan']);
            }


            $stmt2 = $conn->prepare('
                SELECT bi.*, p.name as product_name, p.slug as product_slug,
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as thumbnail,
                       (SELECT r.id FROM reviews r WHERE r.booking_id = b.id AND r.user_id = b.user_id AND r.product_id = bi.product_id LIMIT 1) as review_id,
                       (SELECT r.rating FROM reviews r WHERE r.booking_id = b.id AND r.user_id = b.user_id AND r.product_id = bi.product_id LIMIT 1) as my_rating
                FROM booking_items bi
                JOIN products p ON bi.product_id = p.id
                JOIN bookings b ON bi.booking_id = b.id
                WHERE bi.booking_id = ?
            ');
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $items = [];
            $itemResult = $stmt2->get_result();
            while ($row = $itemResult->fetch_assoc()) {
                $items[] = $row;
            }
            $booking['items'] = $items;
            $stmt2->close();

            jsonResponse(200, $booking);
        }


        $status = $_GET['status'] ?? '';
        $where  = 'WHERE b.user_id = ?';
        $params = [$user['id']];
        $types  = 'i';

        if ($status) {
            $where  .= ' AND b.status = ?';
            $params[] = $status;
            $types   .= 's';
        }

        $stmt = $conn->prepare("
            SELECT b.* FROM bookings b {$where} ORDER BY b.created_at DESC
        ");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        $stmt->close();

        jsonResponse(200, ['bookings' => $bookings]);
        break;

    case 'POST':
        $user  = requireAuth();
        $input = getInput();
        $conn  = db();

        $items    = $input['items'] ?? [];
        $startDate = $input['start_date'] ?? '';
        $endDate   = $input['end_date'] ?? '';
        $notes     = sanitize($input['notes'] ?? '');

        if (empty($items) || !$startDate || !$endDate) {
            jsonResponse(400, ['error' => 'Items, tanggal mulai, dan tanggal selesai wajib diisi']);
        }

        $start = new DateTime($startDate);
        $end   = new DateTime($endDate);
        if ($end <= $start) {
            jsonResponse(400, ['error' => 'Tanggal selesai harus setelah tanggal mulai']);
        }
        $totalDays = (int)$start->diff($end)->days;
        if ($totalDays < 1) $totalDays = 1;

        $totalPrice  = 0;
        $bookingItems = [];

        foreach ($items as $item) {
            $productId = (int)($item['product_id'] ?? 0);
            $quantity  = max(1, (int)($item['quantity'] ?? 1));

            $stmt = $conn->prepare('SELECT id, price_per_day, stock, is_available FROM products WHERE id = ?');
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$product || !$product['is_available']) {
                jsonResponse(400, ['error' => "Produk ID {$productId} tidak tersedia"]);
            }
            if ($product['stock'] < $quantity) {
                jsonResponse(400, ['error' => "Stok produk ID {$productId} tidak cukup"]);
            }

            $subtotal = $product['price_per_day'] * $totalDays * $quantity;
            $totalPrice += $subtotal;
            $bookingItems[] = [
                'product_id'    => $productId,
                'quantity'      => $quantity,
                'price_per_day' => $product['price_per_day'],
                'subtotal'      => $subtotal,
            ];
        }

        $bookingCode = generateBookingCode();

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare('INSERT INTO bookings (user_id, booking_code, start_date, end_date, total_days, total_price, status, payment_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $status     = 'active';
            $payStatus  = 'paid';
            $stmt->bind_param('isssiisss', $user['id'], $bookingCode, $startDate, $endDate, $totalDays, $totalPrice, $status, $payStatus, $notes);
            $stmt->execute();
            $bookingId = $conn->insert_id;
            $stmt->close();

            $stmt = $conn->prepare('INSERT INTO booking_items (booking_id, product_id, quantity, price_per_day, subtotal) VALUES (?, ?, ?, ?, ?)');
            foreach ($bookingItems as $bi) {
                $stmt->bind_param('iiidd', $bookingId, $bi['product_id'], $bi['quantity'], $bi['price_per_day'], $bi['subtotal']);
                $stmt->execute();
            }
            $stmt->close();


            $stmt = $conn->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
            foreach ($bookingItems as $bi) {
                $stmt->bind_param('ii', $bi['quantity'], $bi['product_id']);
                $stmt->execute();
            }
            $stmt->close();

            $conn->commit();

            jsonResponse(201, [
                'message'       => 'Booking berhasil dibuat',
                'booking_code'  => $bookingCode,
                'booking_id'    => $bookingId,
                'total_price'   => $totalPrice,
                'total_days'    => $totalDays,
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            jsonResponse(500, ['error' => 'Gagal membuat booking: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $user  = requireAuth();
        $input = getInput();
        $conn  = db();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            jsonResponse(400, ['error' => 'Booking ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            jsonResponse(404, ['error' => 'Booking tidak ditemukan']);
        }


        $newStatus = $input['status'] ?? null;
        $newPay    = $input['payment_status'] ?? null;

        if ($newStatus) {
            $allowedStatuses = ['pending', 'confirmed', 'active', 'completed', 'cancelled'];
            if (!in_array($newStatus, $allowedStatuses)) {
                jsonResponse(400, ['error' => 'Status tidak valid']);
            }


            if ($newStatus === 'cancelled' && $booking['status'] !== 'cancelled') {
                $stmt = $conn->prepare('SELECT product_id, quantity FROM booking_items WHERE booking_id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $items = $stmt->get_result();
                $stmtUpd = $conn->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                while ($item = $items->fetch_assoc()) {
                    $stmtUpd->bind_param('ii', $item['quantity'], $item['product_id']);
                    $stmtUpd->execute();
                }
                $stmtUpd->close();
                $stmt->close();
            }

            $stmt = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            $stmt->close();
        }

        if ($newPay) {
            $stmt = $conn->prepare('UPDATE bookings SET payment_status = ? WHERE id = ?');
            $stmt->bind_param('si', $newPay, $id);
            $stmt->execute();
            $stmt->close();
        }

        jsonResponse(200, ['message' => 'Booking berhasil diupdate']);
        break;

    case 'DELETE':
        $user = requireAuth();
        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Hanya Dev yang bisa menghapus booking']);
        }

        $id   = (int)($_GET['id'] ?? 0);
        $conn = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'Booking ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            jsonResponse(404, ['error' => 'Booking tidak ditemukan']);
        }


        if ($booking['status'] !== 'cancelled') {
            $stmt = $conn->prepare('SELECT product_id, quantity FROM booking_items WHERE booking_id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $items = $stmt->get_result();
            $stmtUpd = $conn->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            while ($item = $items->fetch_assoc()) {
                $stmtUpd->bind_param('ii', $item['quantity'], $item['product_id']);
                $stmtUpd->execute();
            }
            $stmtUpd->close();
            $stmt->close();
        }

        $conn->query("DELETE FROM booking_items WHERE booking_id = {$id}");
        $stmt = $conn->prepare('DELETE FROM bookings WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['message' => 'Booking berhasil dihapus']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
