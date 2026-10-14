<?php
require_once __DIR__ . '/../cek_login.php';
wajibAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    arahkan('index.php');
}
periksaCsrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    aturPesan('ID lapangan tidak valid.', 'error');
    arahkan('index.php');
}
try {
    $hapus = $pdo->prepare('DELETE FROM lapangan WHERE id = ?');
    $hapus->execute([$id]);
    aturPesan('Lapangan berhasil dihapus.');
} catch (PDOException $exception) {
    if (($exception->errorInfo[0] ?? '') !== '23000') {
        throw $exception;
    }
    aturPesan('Lapangan ini tidak dapat dihapus karena sudah memiliki data booking. Ubah statusnya menjadi nonaktif.', 'warning');
}
arahkan('index.php');
