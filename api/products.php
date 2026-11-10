<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $conn = db();


        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $conn->prepare('
                SELECT p.*, c.name as category_name, c.slug as category_slug,
                       pr.store_name, pr.slug as provider_slug, pr.location as provider_location, pr.rating as provider_rating, pr.avatar as provider_avatar
                FROM products p
                JOIN categories c ON p.category_id = c.id
                JOIN providers pr ON p.provider_id = pr.id
                WHERE p.id = ?
            ');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$product) {
                jsonResponse(404, ['error' => 'Produk tidak ditemukan']);
            }


            $stmt2 = $conn->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC');
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $images = [];
            $imgResult = $stmt2->get_result();
            while ($img = $imgResult->fetch_assoc()) {
                $images[] = $img;
            }
            $product['images'] = $images;
            $stmt2->close();

            jsonResponse(200, $product);
        }

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int)($_GET['per_page'] ?? 12)));
        $search  = $_GET['search'] ?? '';
        $catId   = $_GET['category_id'] ?? '';
        $sort    = $_GET['sort'] ?? 'newest';
        $avail   = $_GET['available'] ?? '1';
        $mine    = $_GET['mine'] ?? '';

        $where  = [];
        $params = [];
        $types  = '';

        if ($mine) {
            $user = requireAuth();
            $s = $conn->prepare('SELECT id FROM providers WHERE user_id = ?');
            $s->bind_param('i', $user['id']);
            $s->execute();
            $pr = $s->get_result()->fetch_assoc();
            $s->close();
            if (!$pr) jsonResponse(403, ['error' => 'Anda belum terdaftar sebagai vendor']);
            $where[]  = 'p.provider_id = ?';
            $params[] = (int)$pr['id'];
            $types   .= 'i';
        }

        if ($search) {
            $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
            $like     = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }
        if ($catId) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int)$catId;
            $types   .= 'i';
        }
        if ($avail) {
            $where[]  = 'p.is_available = ?';
            $params[] = (int)$avail;
            $types   .= 'i';
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $orderMap = [
            'newest'     => 'p.created_at DESC',
            'price_asc'  => 'p.price_per_day ASC',
            'price_desc' => 'p.price_per_day DESC',
            'rating'     => 'p.rating DESC',
            'popular'    => 'p.total_reviews DESC',
        ];
        $orderBy = $orderMap[$sort] ?? 'p.created_at DESC';


        $countSQL = "SELECT COUNT(*) as total FROM products p {$whereSQL}";
        $stmt = $conn->prepare($countSQL);
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT p.*, c.name as category_name, c.slug as category_slug,
                   pr.store_name, pr.location as provider_location, pr.id as provider_id, pr.slug as provider_slug,
                   (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as thumbnail
            FROM products p
            JOIN categories c ON p.category_id = c.id
            JOIN providers pr ON p.provider_id = pr.id
            {$whereSQL}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($sql);
        $limitTypes = $types . 'ii';
        $limitParams = array_merge($params, [$perPage, $offset]);
        $stmt->bind_param($limitTypes, ...$limitParams);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();

        jsonResponse(200, [
            'items'       => $products,
            'total'       => (int)$total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ]);
        break;

    case 'POST':
        $user = requireAuth();
        if ($user['role'] !== 'Vendor' && $user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Hanya Vendor yang bisa menambah produk']);
        }

        $input = getInput();
        $conn  = db();

        $stmt = $conn->prepare('SELECT id FROM providers WHERE user_id = ?');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $provider = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$provider) {
            jsonResponse(403, ['error' => 'Anda belum terdaftar sebagai Vendor']);
        }

        $name        = sanitize($input['name'] ?? '');
        $categoryId  = (int)($input['category_id'] ?? 0);
        $description = sanitize($input['description'] ?? '');
        $priceDay    = (float)($input['price_per_day'] ?? 0);
        $priceWeek   = isset($input['price_per_week']) ? (float)$input['price_per_week'] : null;
        $deposit     = (float)($input['deposit'] ?? 0);
        $stock       = (int)($input['stock'] ?? 1);
        $condition   = $input['condition'] ?? 'baru';
        $location    = sanitize($input['location'] ?? '');

        if (!$name || !$categoryId || !$priceDay) {
            jsonResponse(400, ['error' => 'Nama, kategori, dan harga wajib diisi']);
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-') . '-' . substr(uniqid(), -4);

        $stmt = $conn->prepare('INSERT INTO products (provider_id, category_id, name, slug, description, price_per_day, price_per_week, deposit, stock, `condition`, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iisssdddiss', $provider['id'], $categoryId, $name, $slug, $description, $priceDay, $priceWeek, $deposit, $stock, $condition, $location);
        $stmt->execute();
        $productId = $conn->insert_id;
        $stmt->close();

        jsonResponse(201, ['message' => 'Produk berhasil ditambahkan', 'product_id' => $productId]);
        break;

    case 'PUT':
        $user = requireAuth();
        if ($user['role'] !== 'Vendor' && $user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Akses ditolak']);
        }

        $id    = (int)($_GET['id'] ?? 0);
        $input = getInput();
        $conn  = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'Produk ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT p.*, pr.user_id FROM products p JOIN providers pr ON p.provider_id = pr.id WHERE p.id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            jsonResponse(404, ['error' => 'Produk tidak ditemukan']);
        }

        if ($user['role'] !== 'Dev' && $product['user_id'] !== $user['id']) {
            jsonResponse(403, ['error' => 'Anda tidak memiliki akses ke produk ini']);
        }

        $name        = sanitize($input['name'] ?? $product['name']);
        $categoryId  = (int)($input['category_id'] ?? $product['category_id']);
        $description = sanitize($input['description'] ?? $product['description']);
        $priceDay    = (float)($input['price_per_day'] ?? $product['price_per_day']);
        $priceWeek   = isset($input['price_per_week']) ? (float)$input['price_per_week'] : $product['price_per_week'];
        $deposit     = (float)($input['deposit'] ?? $product['deposit']);
        $stock       = (int)($input['stock'] ?? $product['stock']);
        $condition   = $input['condition'] ?? $product['condition'];
        $location    = sanitize($input['location'] ?? $product['location']);
        $isAvailable = isset($input['is_available']) ? (int)$input['is_available'] : $product['is_available'];

        $stmt = $conn->prepare('UPDATE products SET name=?, category_id=?, description=?, price_per_day=?, price_per_week=?, deposit=?, stock=?, `condition`=?, location=?, is_available=? WHERE id=?');
        $stmt->bind_param('sisdddissii', $name, $categoryId, $description, $priceDay, $priceWeek, $deposit, $stock, $condition, $location, $isAvailable, $id);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['message' => 'Produk berhasil diupdate']);
        break;

    case 'DELETE':
        $user = requireAuth();
        if ($user['role'] !== 'Vendor' && $user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Akses ditolak']);
        }

        $id   = (int)($_GET['id'] ?? 0);
        $conn = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'Produk ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT p.*, pr.user_id FROM products p JOIN providers pr ON p.provider_id = pr.id WHERE p.id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            jsonResponse(404, ['error' => 'Produk tidak ditemukan']);
        }

        if ($user['role'] !== 'Dev' && $product['user_id'] !== $user['id']) {
            jsonResponse(403, ['error' => 'Anda tidak memiliki akses ke produk ini']);
        }

        $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['message' => 'Produk berhasil dihapus']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
