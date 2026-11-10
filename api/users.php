<?php
require_once __DIR__ . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $user = requireAuth();
        $conn = db();


        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            if ($user['role'] !== 'Dev' && $user['id'] !== $id) {
                jsonResponse(403, ['error' => 'Akses ditolak']);
            }
            $stmt = $conn->prepare('SELECT id, name, email, phone, role, is_active, created_at FROM users WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$target) {
                jsonResponse(404, ['error' => 'User tidak ditemukan']);
            }
            jsonResponse(200, $target);
        }

        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Akses ditolak']);
        }

        $page   = max(1, (int)($_GET['page'] ?? 1));
        $search = $_GET['search'] ?? '';
        $role   = $_GET['role'] ?? '';

        $where  = [];
        $params = [];
        $types  = '';

        if ($search) {
            $where[]  = '(name LIKE ? OR email LIKE ?)';
            $like     = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $types   .= 'ss';
        }
        if ($role) {
            $where[]  = 'role = ?';
            $params[] = $role;
            $types   .= 's';
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $perPage  = 20;

        $countSQL = "SELECT COUNT(*) as total FROM users {$whereSQL}";
        $stmt = $conn->prepare($countSQL);
        if ($types) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT id, name, email, phone, role, is_active, created_at FROM users {$whereSQL} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $allTypes = $types . 'ii';
        $allParams = array_merge($params, [$perPage, $offset]);
        $stmt->bind_param($allTypes, ...$allParams);
        $stmt->execute();
        $result = $stmt->get_result();

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $stmt->close();

        jsonResponse(200, [
            'items'       => $users,
            'total'       => (int)$total,
            'page'        => $page,
            'total_pages' => $totalPages,
        ]);
        break;

    case 'PUT':
        $user = requireAuth();
        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Akses ditolak']);
        }

        $id    = (int)($_GET['id'] ?? 0);
        $input = getInput();
        $conn  = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'User ID wajib diisi']);
        }

        $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$target) {
            jsonResponse(404, ['error' => 'User tidak ditemukan']);
        }

        $name     = sanitize($input['name'] ?? $target['name']);
        $phone    = sanitize($input['phone'] ?? $target['phone']);
        $role     = $input['role'] ?? $target['role'];
        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : $target['is_active'];

        $allowedRoles = ['Dev', 'Customer', 'Vendor'];
        if (!in_array($role, $allowedRoles)) {
            jsonResponse(400, ['error' => 'Role tidak valid']);
        }

        $stmt = $conn->prepare('UPDATE users SET name=?, phone=?, role=?, is_active=? WHERE id=?');
        $stmt->bind_param('sssii', $name, $phone, $role, $isActive, $id);
        $stmt->execute();
        $stmt->close();

        jsonResponse(200, ['message' => 'User berhasil diupdate']);
        break;

    case 'DELETE':
        $user = requireAuth();
        if ($user['role'] !== 'Dev') {
            jsonResponse(403, ['error' => 'Akses ditolak']);
        }

        $id   = (int)($_GET['id'] ?? 0);
        $conn = db();

        if (!$id) {
            jsonResponse(400, ['error' => 'User ID wajib diisi']);
        }

        if ($id === $user['id']) {
            jsonResponse(400, ['error' => 'Tidak bisa menghapus akun sendiri']);
        }

        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();

        if ($conn->affected_rows === 0) {
            jsonResponse(404, ['error' => 'User tidak ditemukan']);
        }
        $stmt->close();

        jsonResponse(200, ['message' => 'User berhasil dihapus']);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
