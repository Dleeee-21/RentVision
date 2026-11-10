<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $conn = db();

        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $conn->prepare('SELECT * FROM categories WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $cat = $result->fetch_assoc();
            $stmt->close();

            if (!$cat) {
                jsonResponse(404, ['error' => 'Kategori tidak ditemukan']);
            }

            // ini kategori produknya
            $stmt2 = $conn->prepare('SELECT COUNT(*) as total FROM products WHERE category_id = ?');
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $cat['product_count'] = $stmt2->get_result()->fetch_assoc()['total'];
            $stmt2->close();

            jsonResponse(200, $cat);
        }

        $result = $conn->query('SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count FROM categories c ORDER BY c.name ASC');
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        jsonResponse(200, ['categories' => $categories]);
        break;

    case 'POST':
        $user = requireAuth();
        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Hanya Dev yang bisa menambah kategori']);
        }

        $input = getInput();
        $conn  = db();
        $name  = sanitize($input['name'] ?? '');

        if (!$name) {
            jsonResponse(400, ['error' => 'Nama kategori wajib diisi']);
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');


        $stmt = $conn->prepare('SELECT id FROM categories WHERE slug = ?');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            jsonResponse(409, ['error' => 'Kategori dengan nama serupa sudah ada']);
        }
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
        $stmt->bind_param('ss', $name, $slug);
        $stmt->execute();
        $catId = $conn->insert_id;
        $stmt->close();

        jsonResponse(201, ['message' => 'Kategori berhasil ditambahkan', 'category_id' => $catId]);
        break;

    case 'PUT':
        $user = requireAuth();
        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Hanya Dev yang bisa mengedit kategori']);
        }

        $id    = (int)($_GET['id'] ?? 0);
        $input = getInput();
        $conn  = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'Kategori ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $cat = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cat) {
            jsonResponse(404, ['error' => 'Kategori tidak ditemukan']);
        }

        $name = sanitize($input['name'] ?? $cat['name']);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');

        $stmt = $conn->prepare('UPDATE categories SET name=?, slug=? WHERE id=?');
        $stmt->bind_param('ssi', $name, $slug, $id);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['message' => 'Kategori berhasil diupdate']);
        break;

    case 'DELETE':
        $user = requireAuth();
        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Hanya Dev yang bisa menghapus kategori']);
        }

        $id   = (int)($_GET['id'] ?? 0);
        $conn = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'Kategori ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT COUNT(*) as total FROM products WHERE category_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        if ($total > 0) {
            jsonResponse(400, ['error' => "Tidak bisa menghapus kategori yang masih memiliki {$total} produk"]);
        }

        $stmt = $conn->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['message' => 'Kategori berhasil dihapus']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
