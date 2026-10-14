<?php
require_once __DIR__ . '/../cek_login.php';
wajibAdmin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$stmt = $pdo->prepare('SELECT * FROM lapangan WHERE id = ?');
$stmt->execute([$id ?: 0]);
$lapangan = $stmt->fetch();
if (!$lapangan) {
    http_response_code(404);
    exit('Lapangan tidak ditemukan.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    periksaCsrf();
    $nama = trim((string) ($_POST['nama'] ?? ''));
    $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));
    $harga = trim((string) ($_POST['harga_per_jam'] ?? ''));
    $status = (string) ($_POST['status'] ?? '');

    if ($nama === '' || mb_strlen($nama) > 100) {
        $error = 'Nama lapangan wajib diisi (maksimal 100 karakter).';
    } elseif (!is_numeric($harga) || (float) $harga < 1) {
        $error = 'Harga per jam harus berupa angka lebih dari 0.';
    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $error = 'Status lapangan tidak valid.';
    } else {
        $update = $pdo->prepare('UPDATE lapangan SET nama = ?, deskripsi = ?, harga_per_jam = ?, status = ? WHERE id = ?');
        $update->execute([$nama, $deskripsi, (int) $harga, $status, (int) $lapangan['id']]);
        aturPesan('Perubahan lapangan berhasil disimpan.');
        arahkan('index.php');
    }
}

$judulHalaman = 'Edit Lapangan';
$akarUrl = '../';
require __DIR__ . '/../header.php';
?>
<section class="page-heading"><div><p class="eyebrow">DATA LAPANGAN</p><h1>Edit lapangan</h1><p class="muted">Perbarui informasi lapangan.</p></div><a class="text-link" href="index.php">← Kembali</a></section>
<section class="form-card form-card-wide">
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Nama lapangan<input name="nama" required maxlength="100" value="<?= e($_POST['nama'] ?? $lapangan['nama']) ?>"></label>
        <label>Deskripsi<textarea name="deskripsi" rows="3" maxlength="500"><?= e($_POST['deskripsi'] ?? $lapangan['deskripsi']) ?></textarea></label>
        <label>Harga sewa per jam (Rp)<input name="harga_per_jam" type="number" min="1" step="1000" required value="<?= e($_POST['harga_per_jam'] ?? $lapangan['harga_per_jam']) ?>"></label>
        <label>Status<select name="status"><option value="aktif" <?= ($_POST['status'] ?? $lapangan['status']) === 'aktif' ? 'selected' : '' ?>>Aktif</option><option value="nonaktif" <?= ($_POST['status'] ?? $lapangan['status']) === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></label>
        <div class="form-actions"><a class="button button-outline" href="index.php">Batal</a><button class="button" type="submit">Simpan perubahan</button></div>
    </form>
</section>
<?php require __DIR__ . '/../footer.php'; ?>
