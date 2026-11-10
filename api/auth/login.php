<?php
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();

switch ($method) {
    case 'POST':
        $email    = sanitize($input['email']    ?? '');
        $password = $input['password']          ?? '';

        if (!$email || !$password) {
            jsonResponse(400, ['error' => 'Email dan password wajib diisi']);
        }

        $conn = db();
        $stmt = $conn->prepare('SELECT id, name, email, password, role, is_active FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(401, ['error' => 'Email atau password salah']);
        }
        if (!$user['is_active']) {
            jsonResponse(403, ['error' => 'Akun telah dinonaktifkan']);
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        jsonResponse(200, [
            'message' => 'Login berhasil',
            'user'    => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
