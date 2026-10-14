<?php
require_once __DIR__ . '/../cek_login.php';
wajibAdmin();
$error = '';
$nama = '';
$deskripsi = '';
$harga = '';
$status = 'aktif';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    periksaCsrf();
    $nama = trim((string) ($_POST['nama'] ?? ''));
    $deskripsi = trim((string) ($_POST['deskripsi'] ?? ''));
    $harga = trim((string) ($_POST['harga_per_jam'] ?? ''));
    $status = (string) ($_POST['status'] ?? 'aktif');

    if ($nama === '' || mb_strlen($nama) > 100) {
        $error = 'Nama lapangan wajib diisi (maksimal 100 karakter).';
    } elseif (!is_numeric($harga) || (float) $harga < 1) {
        $error = 'Harga per jam harus berupa angka lebih dari 0.';
    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $error = 'Status lapangan tidak valid.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO lapangan (nama, deskripsi, harga_per_jam, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$nama, $deskripsi, (int) $harga, $status]);
        aturPesan('Lapangan baru berhasil ditambahkan.');
        arahkan('index.php');
    }
}

$judulHalaman = 'Tambah Lapangan';
$akarUrl = '../';
require __DIR__ . '/../header.php';
?>
<section class="page-heading"><div><p class="eyebrow">DATA LAPANGAN</p><h1>Tambah lapangan</h1><p class="muted">Isi informasi yang akan dilihat pelanggan.</p></div><a class="text-link" href="index.php">← Kembali</a></section>
<section class="form-card form-card-wide">
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Nama lapangan<input name="nama" required maxlength="100" value="<?= e($nama) ?>" placeholder="Contoh: Lapangan Merdeka 1"></label>
        <label>Deskripsi<textarea name="deskripsi" rows="3" maxlength="500" placeholder="Ceritakan fasilitas atau kondisi lapangan"><?= e($deskripsi) ?></textarea></label>
        <label>Harga sewa per jam (Rp)<input name="harga_per_jam" type="number" min="1" step="1000" required value="<?= e($harga) ?>" placeholder="Contoh: 250000"></label>
        <label>Status<select name="status"><option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option><option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></label>
        <div class="form-actions"><a class="button button-outline" href="index.php">Batal</a><button class="button" type="submit">Simpan lapangan</button></div>
    </form>
</section>
<?php require __DIR__ . '/../footer.php'; ?>
