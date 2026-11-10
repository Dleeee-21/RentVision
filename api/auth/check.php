<?php
require_once __DIR__ . '/../config/database.php';

$user = optionalAuth();

if ($user) {
    jsonResponse(200, ['logged_in' => true, 'user' => $user]);
} else {
    jsonResponse(200, ['logged_in' => false, 'user' => null]);
}
