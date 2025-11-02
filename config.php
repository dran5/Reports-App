<?php
// config.php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'appdb');
define('DB_USER', 'root');
define('DB_PASS', '');

// Set your domain/URL (no trailing slash)
define('BASE_URL', 'http://localhost');

// Google client ID (keep as you have)
define('GOOGLE_CLIENT_ID', '20444944418-nq946u1kaa4in2fg6piboi6an7r6u8q2.apps.googleusercontent.com');

// PDO connection
function getPDO(){
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return $pdo;
}

// simple auth helper
function require_login() {
    if (empty($_SESSION['user'])) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
