<?php
require_once __DIR__ . '/../config/database.php';

$user = requireAuth();
if ($user['role'] !== 'Vendor' && $user['role'] !== 'Dev') {
    jsonResponse(403, ['error' => 'Hanya vendor yang bisa mengelola gambar']);
}

$conn  = db();
$method = $_SERVER['REQUEST_METHOD'];

function vendorProviderId(mysqli $conn, int $userId): int {
    $s = $conn->prepare('SELECT id FROM providers WHERE user_id = ?');
    $s->bind_param('i', $userId);
    $s->execute();
    $p = $s->get_result()->fetch_assoc();
    $s->close();
    if (!$p) jsonResponse(403, ['error' => 'Anda belum terdaftar sebagai vendor']);
    return (int)$p['id'];
}

switch ($method) {
    case 'POST':
        $pid = vendorProviderId($conn, $user['id']);

        if (empty($_FILES['file'])) {
            jsonResponse(400, ['error' => 'File tidak ditemukan']);
        }
        $file = $_FILES['file'];

        $allowed = ['jpg','jpeg','png','webp','gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            jsonResponse(400, ['error' => 'Format gambar tidak didukung (jpg, png, webp, gif)']);
        }
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
            jsonResponse(400, ['error' => 'Gagal mengunggah atau file > 5MB']);
        }

        $dir = __DIR__ . '/../../uploads/products/';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $name = uniqid('p_') . '.' . $ext;
        $dest = $dir . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            jsonResponse(500, ['error' => 'Gagal menyimpan file']);
        }
        $url = '/pkk/uploads/products/' . $name;

        $productId = (int)($_POST['product_id'] ?? 0);
        $isPrimary = isset($_POST['is_primary']) ? (int)$_POST['is_primary'] : 0;

        if ($productId) {

            $s = $conn->prepare('SELECT p.provider_id FROM products p JOIN providers pr ON p.provider_id=pr.id WHERE p.id=? AND pr.user_id=?');
            $s->bind_param('ii', $productId, $user['id']);
            $s->execute();
            if (!$s->get_result()->fetch_assoc()) {
                unlink($dest);
                jsonResponse(403, ['error' => 'Produk tidak ditemukan atau bukan milik Anda']);
            }
            $s->close();

            if ($isPrimary) {
                $s = $conn->prepare('UPDATE product_images SET is_primary=0 WHERE product_id=?');
                $s->bind_param('i', $productId);
                $s->execute();
                $s->close();
            } else {
                $s = $conn->prepare('SELECT COUNT(*) as c FROM product_images WHERE product_id=?');
                $s->bind_param('i', $productId);
                $s->execute();
                if ((int)$s->get_result()->fetch_assoc()['c'] === 0) $isPrimary = 1;
                $s->close();
            }

            $sort = 0;
            $s = $conn->prepare('SELECT COALESCE(MAX(sort_order), -1) as m FROM product_images WHERE product_id=?');
            $s->bind_param('i', $productId);
            $s->execute();
            $sort = (int)$s->get_result()->fetch_assoc()['m'] + 1;
            $s->close();

            $s = $conn->prepare('INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES (?,?,?,?)');
            $s->bind_param('isii', $productId, $url, $isPrimary, $sort);
            $s->execute();
            $imageId = $conn->insert_id;
            $s->close();

            jsonResponse(201, ['message' => 'Gambar berhasil diunggah', 'id' => $imageId, 'url' => $url]);
        }

        jsonResponse(201, ['message' => 'Gambar berhasil diunggah', 'id' => null, 'url' => $url]);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(400, ['error' => 'Image ID wajib diisi']);

        $s = $conn->prepare('SELECT pi.* FROM product_images pi JOIN products p ON pi.product_id=p.id JOIN providers pr ON p.provider_id=pr.id WHERE pi.id=? AND pr.user_id=?');
        $s->bind_param('ii', $id, $user['id']);
        $s->execute();
        $img = $s->get_result()->fetch_assoc();
        $s->close();
        if (!$img) jsonResponse(404, ['error' => 'Gambar tidak ditemukan']);

        $s = $conn->prepare('DELETE FROM product_images WHERE id=?');
        $s->bind_param('i', $id);
        $s->execute();
        $s->close();

        $path = __DIR__ . '/../../' . ltrim($img['image_url'], '/');
        if (strpos($img['image_url'], '/pkk/uploads/products/') === 0 && file_exists($path)) {
            unlink($path);
        }
        jsonResponse(200, ['message' => 'Gambar dihapus']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
