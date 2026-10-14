<?php
declare(strict_types=1);

/*
 * Ubah pengaturan berikut sesuai MySQL lokal Anda.
 * Pengaturan awal cocok untuk instalasi XAMPP standar.
 */
$host = 'localhost';
$namaDatabase = 'merdeka_kickoff';
$usernameDatabase = 'root';
$passwordDatabase = '';
date_default_timezone_set('Asia/Jakarta');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$namaDatabase};charset=utf8mb4",
        $usernameDatabase,
        $passwordDatabase,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit(
        'Koneksi database gagal. Periksa koneksi.php dan pastikan database '
        . 'merdeka_kickoff sudah diimpor. Detail: '
        . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
    );
}
