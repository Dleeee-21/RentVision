<?php
require_once __DIR__ . '/../config/database.php';

$user = requireAuth();
if ($user['role'] !== 'Vendor' && $user['role'] !== 'Dev') {
    jsonResponse(403, ['error' => 'Hanya vendor yang bisa konfirmasi pengembalian']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['error' => 'Method not allowed']);
}

$conn = db();

$stmt = $conn->prepare('SELECT id FROM providers WHERE user_id = ?');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$provider) {
    jsonResponse(403, ['error' => 'Anda belum terdaftar sebagai vendor']);
}
$pid = (int)$provider['id'];

$input     = getInput();
$bookingId = (int)($input['booking_id'] ?? 0);
if (!$bookingId) {
    jsonResponse(400, ['error' => 'booking_id wajib diisi']);
}

$stmt = $conn->prepare('SELECT id, status, payment_status FROM bookings WHERE id = ?');
$stmt->bind_param('i', $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    jsonResponse(404, ['error' => 'Booking tidak ditemukan']);
}
if ($booking['status'] === 'completed' || $booking['status'] === 'cancelled') {
    jsonResponse(400, ['error' => 'Booking sudah selesai/dibatalkan']);
}

$stmt = $conn->prepare('
    SELECT COUNT(*) as c
    FROM booking_items bi
    JOIN products p ON bi.product_id = p.id
    WHERE bi.booking_id = ? AND p.provider_id != ?
');
$stmt->bind_param('ii', $bookingId, $pid);
$stmt->execute();
$outside = (int)$stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

if ($outside > 0) {
    jsonResponse(403, ['error' => 'Booking berisi produk vendor lain, tidak bisa diproses']);
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare('SELECT product_id, quantity FROM booking_items WHERE booking_id = ?');
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $items = $stmt->get_result();
    $stmtUpd = $conn->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
    while ($item = $items->fetch_assoc()) {
        $stmtUpd->bind_param('ii', $item['quantity'], $item['product_id']);
        $stmtUpd->execute();
    }
    $stmtUpd->close();
    $stmt->close();

    $stmt = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ?');
    $completed = 'completed';
    $stmt->bind_param('si', $completed, $bookingId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    jsonResponse(200, ['message' => 'Barang dikonfirmasi kembali']);
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(500, ['error' => 'Gagal memproses pengembalian: ' . $e->getMessage()]);
}