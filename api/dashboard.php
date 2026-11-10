<?php
require_once __DIR__ . '/config/database.php';

$user = requireAuth();
$conn = db();

// Stats
$stats = [];

// Total products
$r = $conn->query('SELECT COUNT(*) as t FROM products');
$stats['total_products'] = (int)$r->fetch_assoc()['t'];

// Total bookings
$r = $conn->query('SELECT COUNT(*) as t FROM bookings');
$stats['total_bookings'] = (int)$r->fetch_assoc()['t'];

// Total users
$r = $conn->query('SELECT COUNT(*) as t FROM users');
$stats['total_users'] = (int)$r->fetch_assoc()['t'];

// Total revenue (paid bookings)
$r = $conn->query("SELECT COALESCE(SUM(total_price), 0) as t FROM bookings WHERE payment_status = 'paid'");
$stats['total_revenue'] = (float)$r->fetch_assoc()['t'];

// Active bookings
$r = $conn->query("SELECT COUNT(*) as t FROM bookings WHERE status IN ('active', 'confirmed')");
$stats['active_bookings'] = (int)$r->fetch_assoc()['t'];

// Recent bookings
$r = $conn->query('
    SELECT b.*, u.name as user_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC
    LIMIT 10
');
$recent = [];
while ($row = $r->fetch_assoc()) {
    $recent[] = $row;
}

// Recent users
$r = $conn->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5');
$recentUsers = [];
while ($row = $r->fetch_assoc()) {
    $recentUsers[] = $row;
}

jsonResponse(200, [
    'stats'        => $stats,
    'recent_bookings' => $recent,
    'recent_users' => $recentUsers,
]);
