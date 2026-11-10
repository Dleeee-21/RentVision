<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $user = requireAuth();
        $input = getInput();
        $conn  = db();

        $storeName  = sanitize($input['store_name'] ?? '');
        $location   = sanitize($input['location'] ?? '');
        $description = sanitize($input['description'] ?? '');

        if (!$storeName) {
            jsonResponse(400, ['error' => 'Nama toko wajib diisi']);
        }
        if (!$location) {
            jsonResponse(400, ['error' => 'Lokasi toko wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT id FROM providers WHERE user_id = ?');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            jsonResponse(409, ['error' => 'Anda sudah terdaftar sebagai partner']);
        }
        $stmt->close();

        $baseSlug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $storeName), '-'));
        if ($baseSlug === '') $baseSlug = 'toko';
        $slug = $baseSlug;
        $counter = 1;
        while (true) {
            $stmt = $conn->prepare('SELECT id FROM providers WHERE slug = ?');
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) break;
            $stmt->close();
            $slug = $baseSlug . '-' . $counter++;
        }
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO providers (user_id, store_name, slug, location, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $user['id'], $storeName, $slug, $location, $description);
        $stmt->execute();
        $providerId = $conn->insert_id;
        $stmt->close();

        if ($user['role'] !== 'Vendor') {
            $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
            $vendor = 'Vendor';
            $stmt->bind_param('si', $vendor, $user['id']);
            $stmt->execute();
            $stmt->close();
            $_SESSION['user_role'] = 'Vendor';
        }

        jsonResponse(201, [
            'message'   => 'Berhasil mendaftar sebagai partner',
            'provider'  => ['id' => $providerId, 'store_name' => $storeName, 'slug' => $slug],
        ]);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
