<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['error' => 'Method not allowed']);
}

session_destroy();
session_start();
jsonResponse(200, ['message' => 'Logout berhasil']);
