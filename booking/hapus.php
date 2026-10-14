<?php
require_once __DIR__ . '/../cek_login.php';
wajibLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    arahkan('index.php');
}
periksaCsrf();
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    aturPesan('ID booking tidak valid.', 'error');
    arahkan('index.php');
}

$isAdmin = $_SESSION['role'] === 'admin';
$sql = 'DELETE FROM bookings WHERE id = ?';
$parameter = [$id];
if (!$isAdmin) {
    $sql .= ' AND user_id = ?';
    $parameter[] = (int) $_SESSION['user_id'];
}
$hapus = $pdo->prepare($sql);
$hapus->execute($parameter);
aturPesan($hapus->rowCount() ? 'Booking berhasil dihapus.' : 'Booking tidak ditemukan atau Anda tidak memiliki akses.', $hapus->rowCount() ? 'success' : 'warning');
arahkan('index.php');
