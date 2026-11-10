<?php
require_once __DIR__ . '/../config/database.php';

$user = requireAuth();
if ($user['role'] !== 'Vendor' && $user['role'] !== 'Dev') {
    jsonResponse(403, ['error' => 'Hanya vendor yang bisa mengakses panel ini']);
}

$conn = db();

$stmt = $conn->prepare('SELECT id, store_name, slug, location, avatar, rating, is_verified, description FROM providers WHERE user_id = ?');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$provider) {
    jsonResponse(403, ['error' => 'Anda belum terdaftar sebagai vendor']);
}
$pid = (int)$provider['id'];


$r = $conn->prepare('SELECT COUNT(*) as t FROM products WHERE provider_id = ?');
$r->bind_param('i', $pid);
$r->execute();
$totalProducts = (int)$r->get_result()->fetch_assoc()['t'];
$r->close();


$r = $conn->prepare('
    SELECT b.*, u.name as user_name,
           (SELECT GROUP_CONCAT(DISTINCT p3.name ORDER BY p3.name)
            FROM booking_items bi3
            JOIN products p3 ON bi3.product_id = p3.id
            WHERE bi3.booking_id = b.id AND p3.provider_id = ?) as product_names,
           (SELECT COALESCE(SUM(bi2.subtotal), 0)
            FROM booking_items bi2
            JOIN products p2 ON bi2.product_id = p2.id
            WHERE bi2.booking_id = b.id AND p2.provider_id = ?) as vendor_subtotal
    FROM booking_items bi
    JOIN bookings b ON bi.booking_id = b.id
    JOIN products p ON bi.product_id = p.id
    JOIN users u ON b.user_id = u.id
    WHERE p.provider_id = ?
    GROUP BY b.id
    ORDER BY b.created_at DESC
    LIMIT 20
');
$r->bind_param('iii', $pid, $pid, $pid);
$r->execute();
$bookings = $r->get_result()->fetch_all(MYSQLI_ASSOC);
$r->close();


$r = $conn->prepare("
    SELECT
        COUNT(DISTINCT b.id) as total,
        SUM(CASE WHEN b.status IN ('active','confirmed') THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM booking_items bi
    JOIN bookings b ON bi.booking_id = b.id
    JOIN products p ON bi.product_id = p.id
    WHERE p.provider_id = ?
");
$r->bind_param('i', $pid);
$r->execute();
$counts = $r->get_result()->fetch_assoc();
$r->close();

$r = $conn->prepare("
    SELECT COALESCE(SUM(bi.subtotal), 0) as rev
    FROM booking_items bi
    JOIN bookings b ON bi.booking_id = b.id
    JOIN products p ON bi.product_id = p.id
    WHERE p.provider_id = ? AND b.payment_status = 'paid'
");
$r->bind_param('i', $pid);
$r->execute();
$revenue = (float)$r->get_result()->fetch_assoc()['rev'];
$r->close();


$r = $conn->prepare('SELECT id, name, stock FROM products WHERE provider_id = ? AND stock <= 2 ORDER BY stock ASC LIMIT 5');
$r->bind_param('i', $pid);
$r->execute();
$lowStock = $r->get_result()->fetch_all(MYSQLI_ASSOC);
$r->close();

jsonResponse(200, [
    'provider' => $provider,
    'stats'    => [
        'total_products' => $totalProducts,
        'total_bookings' => (int)$counts['total'],
        'active_bookings'=> (int)$counts['active'],
        'pending_bookings' => (int)$counts['pending'],
        'revenue'        => $revenue,
    ],
    'bookings' => $bookings,
    'low_stock' => $lowStock,
]);
