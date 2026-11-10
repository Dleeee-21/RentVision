<?php
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$input  = getInput();

switch ($method) {
    case 'POST':
        $name     = sanitize($input['name']     ?? '');
        $email    = sanitize($input['email']    ?? '');
        $password = $input['password']          ?? '';
        $phone    = sanitize($input['phone']    ?? '');

        if (!$name || !$email || !$password) {
            jsonResponse(400, ['error' => 'Nama, email, dan password wajib diisi']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(400, ['error' => 'Format email tidak valid']);
        }
        if (strlen($password) < 6) {
            jsonResponse(400, ['error' => 'Password minimal 6 karakter']);
        }

        $conn = db();

        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            jsonResponse(409, ['error' => 'Email sudah terdaftar']);
        }
        $stmt->close();

        $hash  = password_hash($password, PASSWORD_DEFAULT);
        $role  = 'Customer';
        $stmt  = $conn->prepare('INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $name, $email, $hash, $phone, $role);
        $stmt->execute();
        $userId = $conn->insert_id;
        $stmt->close();

        $_SESSION['user_id']    = $userId;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = $role;

        jsonResponse(201, [
            'message' => 'Registrasi berhasil',
            'user'    => ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => $role],
        ]);
        break;

    default:
        jsonResponse(405, ['error' => 'Method not allowed']);
}
