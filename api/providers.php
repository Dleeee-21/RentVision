<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $conn = db();


        if (isset($_GET['id']) || isset($_GET['slug'])) {
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $conn->prepare('
                    SELECT pr.*, u.name as owner_name, u.email as owner_email
                    FROM providers pr
                    JOIN users u ON pr.user_id = u.id
                    WHERE pr.id = ?
                ');
                $stmt->bind_param('i', $id);
            } else {
                $slug = sanitize($_GET['slug']);
                $stmt = $conn->prepare('
                    SELECT pr.*, u.name as owner_name, u.email as owner_email
                    FROM providers pr
                    JOIN users u ON pr.user_id = u.id
                    WHERE pr.slug = ?
                ');
                $stmt->bind_param('s', $slug);
            }
            $stmt->execute();
            $provider = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$provider) {
                jsonResponse(404, ['error' => 'Provider tidak ditemukan']);
            }

            $stmt2 = $conn->prepare('
                SELECT p.*, c.name as category_name,
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as thumbnail
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.provider_id = ?
                ORDER BY p.created_at DESC
            ');
            $stmt2->bind_param('i', $provider['id']);
            $stmt2->execute();
            $products = [];
            $prodResult = $stmt2->get_result();
            while ($row = $prodResult->fetch_assoc()) {
                $products[] = $row;
            }
            $provider['products'] = $products;
            $stmt2->close();

            jsonResponse(200, $provider);
        }


        $result = $conn->query('SELECT pr.*, u.name as owner_name FROM providers pr JOIN users u ON pr.user_id = u.id ORDER BY pr.rating DESC');
        $providers = [];
        while ($row = $result->fetch_assoc()) {
            $providers[] = $row;
        }

        jsonResponse(200, ['providers' => $providers]);
        break;

    case 'PUT':
        $user = requireAuth();
        if ($user['role'] !== 'Dev' && $user['role'] !== 'Vendor') {
            jsonResponse(403, ['error' => 'Akses ditolak']);
        }

        $id    = (int)($_GET['id'] ?? 0);
        $input = getInput();
        $conn  = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'Provider ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT * FROM providers WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $provider = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$provider) {
            jsonResponse(404, ['error' => 'Provider tidak ditemukan']);
        }

        if ($user['role'] !== 'Dev' && $provider['user_id'] !== $user['id']) {
            jsonResponse(403, ['error' => 'Akses ditolak']);
        }

        $storeName   = sanitize($input['store_name'] ?? $provider['store_name']);
        $description = sanitize($input['description'] ?? $provider['description']);
        $location    = sanitize($input['location'] ?? $provider['location']);
        $isVerified  = isset($input['is_verified']) ? (int)$input['is_verified'] : $provider['is_verified'];

        $stmt = $conn->prepare('UPDATE providers SET store_name=?, description=?, location=?, is_verified=? WHERE id=?');
        $stmt->bind_param('sssii', $storeName, $description, $location, $isVerified, $id);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['message' => 'Provider berhasil diupdate']);
        break;

    case 'DELETE':
        $user = requireAuth();
        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Hanya Dev yang bisa menghapus provider']);
        }

        $id   = (int)($_GET['id'] ?? 0);
        $conn = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'Provider ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT * FROM providers WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            jsonResponse(404, ['error' => 'Provider tidak ditemukan']);
        }
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM providers WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['message' => 'Provider berhasil dihapus']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
