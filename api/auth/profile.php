<?php
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$user   = requireAuth();
$conn   = db();

switch ($method) {
    case 'GET':
        // Profil pengguna sendiri
        $stmt = $conn->prepare('SELECT id, name, email, phone, avatar, role, created_at FROM users WHERE id = ?');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            jsonResponse(404, ['error' => 'User tidak ditemukan']);
        }

        jsonResponse(200, [
            'id'         => (int)$row['id'],
            'name'       => $row['name'],
            'email'      => $row['email'],
            'phone'      => $row['phone'],
            'avatar'     => $row['avatar'],
            'role'       => $row['role'],
            'created_at' => date('j F Y', strtotime($row['created_at'])),
        ]);
        break;

    case 'PUT':
        // Ganti password
        if (isset($_GET['op']) && $_GET['op'] === 'password') {
            $input          = getInput();
            $current        = (string)($input['current_password'] ?? '');
            $new            = (string)($input['new_password'] ?? '');
            $newConfirm     = (string)($input['new_password2'] ?? '');

            if (!$current || !$new || !$newConfirm) {
                jsonResponse(400, ['error' => 'Semua kolom password wajib diisi']);
            }
            if (strlen($new) < 6) {
                jsonResponse(400, ['error' => 'Password baru minimal 6 karakter']);
            }
            if ($new !== $newConfirm) {
                jsonResponse(400, ['error' => 'Konfirmasi password baru tidak cocok']);
            }

            $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->bind_param('i', $user['id']);
            $stmt->execute();
            $hash = $stmt->get_result()->fetch_assoc()['password'];
            $stmt->close();

            if (!password_verify($current, $hash)) {
                jsonResponse(400, ['error' => 'Password saat ini salah']);
            }

            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->bind_param('si', $newHash, $user['id']);
            $stmt->execute();
            $stmt->close();

            jsonResponse(200, ['message' => 'Password berhasil diubah']);
        }

        // Edit profil (name, phone, avatar)
        $input = getInput();
        $name  = sanitize($input['name'] ?? '');

        if (!$name) {
            jsonResponse(400, ['error' => 'Nama wajib diisi']);
        }

        $phone  = sanitize($input['phone'] ?? '');
        $avatar = null;
        if (isset($input['avatar']) && trim((string)$input['avatar']) !== '') {
            $avatar = sanitize((string)$input['avatar']);
            if (strlen($avatar) > 500) {
                jsonResponse(400, ['error' => 'URL avatar terlalu panjang']);
            }
        }

        $stmt = $conn->prepare('UPDATE users SET name = ?, phone = ?, avatar = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $phone, $avatar, $user['id']);
        $stmt->execute();
        $stmt->close();

        // Sinkronkan session agar chip/header ikut berubah
        $_SESSION['user_name']   = $name;
        $_SESSION['user_avatar'] = $avatar;

        jsonResponse(200, ['message' => 'Profil berhasil diperbarui']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
