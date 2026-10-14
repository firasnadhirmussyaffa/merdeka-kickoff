<?php
declare(strict_types=1);

require_once __DIR__ . '/cek_login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    arahkan('index.php');
}

periksaCsrf();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $parameter = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $parameter['path'],
        'domain' => $parameter['domain'],
        'secure' => $parameter['secure'],
        'httponly' => $parameter['httponly'],
        'samesite' => 'Lax',
    ]);
}

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
setcookie('ingat_username', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_destroy();
session_start();
aturPesan('Anda berhasil logout. Sampai jumpa di lapangan.');
arahkan('index.php');
