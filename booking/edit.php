<?php
require_once __DIR__ . '/../cek_login.php';
wajibLogin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isAdmin = $_SESSION['role'] === 'admin';
$sql = 'SELECT * FROM bookings WHERE id = ?';
$parameter = [$id ?: 0];
if (!$isAdmin) {
    $sql .= ' AND user_id = ?';
    $parameter[] = (int) $_SESSION['user_id'];
}
$stmt = $pdo->prepare($sql);
$stmt->execute($parameter);
$booking = $stmt->fetch();
if (!$booking) {
    http_response_code(404);
    exit('Booking tidak ditemukan atau Anda tidak memiliki akses.');
}
if (!$isAdmin && $booking['status'] !== 'menunggu') {
    http_response_code(403);
    exit('Booking hanya dapat diubah selama statusnya masih menunggu.');
}

$error = '';
$lapanganAktif = $pdo->query("SELECT id, nama, harga_per_jam FROM lapangan WHERE status = 'aktif' ORDER BY nama")->fetchAll();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    periksaCsrf();
    $lapanganId = filter_var($_POST['lapangan_id'] ?? null, FILTER_VALIDATE_INT);
    $tanggal = (string) ($_POST['tanggal'] ?? '');
    $jamMulai = (string) ($_POST['jam_mulai'] ?? '');
    $durasi = filter_var($_POST['durasi_jam'] ?? null, FILTER_VALIDATE_INT);
    $keterangan = trim((string) ($_POST['keterangan'] ?? ''));
    $status = $isAdmin ? (string) ($_POST['status'] ?? $booking['status']) : $booking['status'];
    $tanggalObj = DateTime::createFromFormat('Y-m-d', $tanggal);
    $waktuValid = preg_match('/^(0[7-9]|1[0-9]|2[0-1]):00$/', $jamMulai) === 1;

    if (!$lapanganId || !$tanggalObj || $tanggalObj->format('Y-m-d') !== $tanggal || $tanggal < date('Y-m-d')) {
        $error = 'Pilih lapangan dan tanggal hari ini atau setelahnya.';
    } elseif (!$waktuValid || !in_array($durasi, [1, 2, 3], true)) {
        $error = 'Jam mulai harus antara 07.00–21.00 dan durasi maksimal 3 jam.';
    } elseif (!in_array($status, ['menunggu', 'dikonfirmasi', 'sedang_bermain', 'selesai', 'dibatalkan'], true)) {
        $error = 'Status booking tidak valid.';
    } elseif ($tanggal === date('Y-m-d') && $jamMulai <= date('H:i')) {
        $error = 'Jam tersebut sudah lewat. Silakan pilih waktu bermain berikutnya.';
    } elseif (mb_strlen($keterangan) > 500) {
        $error = 'Keterangan maksimal 500 karakter.';
    } else {
        $cekLapangan = $pdo->prepare("SELECT id, harga_per_jam FROM lapangan WHERE id = ? AND status = 'aktif'");
        $cekLapangan->execute([$lapanganId]);
        $lapanganDipilih = $cekLapangan->fetch();
        if (!$lapanganDipilih) {
            $error = 'Lapangan yang dipilih sudah tidak aktif.';
        } else {
            $tabrakan = $pdo->prepare(
                "SELECT COUNT(*) FROM bookings
                 WHERE lapangan_id = ? AND tanggal = ? AND id <> ? AND status <> 'dibatalkan'
                   AND TIME_TO_SEC(jam_mulai) < (TIME_TO_SEC(?) + (? * 3600))
                   AND (TIME_TO_SEC(jam_mulai) + (durasi_jam * 3600)) > TIME_TO_SEC(?)"
            );
            $tabrakan->execute([$lapanganId, $tanggal, (int) $booking['id'], $jamMulai, $durasi, $jamMulai]);
            if ((int) $tabrakan->fetchColumn() > 0) {
                $error = 'Jadwal tersebut bertabrakan dengan booking lain.';
            } else {
                $update = $pdo->prepare(
                    'UPDATE bookings SET lapangan_id = ?, tanggal = ?, jam_mulai = ?, durasi_jam = ?, total_harga = ?, status = ?, keterangan = ? WHERE id = ?'
                );
                $update->execute([
                    $lapanganId, $tanggal, $jamMulai, $durasi,
                    (int) $lapanganDipilih['harga_per_jam'] * $durasi,
                    $status, $keterangan, (int) $booking['id'],
                ]);
                aturPesan('Perubahan booking berhasil disimpan.');
                arahkan('index.php');
            }
        }
    }
}

$judulHalaman = 'Edit Booking';
$akarUrl = '../';
require __DIR__ . '/../header.php';
?>
<section class="page-heading"><div><p class="eyebrow">UBAH JADWAL BOOKING</p><h1>Edit booking #<?= (int) $booking['id'] ?></h1><p class="muted"><?= $isAdmin ? 'Admin dapat memperbarui detail dan status booking.' : 'Pelanggan dapat mengubah booking selama statusnya menunggu.' ?></p></div><a class="text-link" href="index.php">← Kembali</a></section>
<section class="form-card form-card-wide">
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-stack" data-booking-form data-availability-url="ketersediaan.php" data-booking-exclude="<?= (int) $booking['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Pilih lapangan<select name="lapangan_id" required data-field-select>
            <?php foreach ($lapanganAktif as $item): ?>
                <option value="<?= (int) $item['id'] ?>" data-price="<?= (int) $item['harga_per_jam'] ?>" <?= (int) ($_POST['lapangan_id'] ?? $booking['lapangan_id']) === (int) $item['id'] ? 'selected' : '' ?>>
                    <?= e($item['nama']) ?> · <?= rupiah($item['harga_per_jam']) ?>/jam
                </option>
            <?php endforeach; ?>
        </select></label>
        <div class="form-row">
            <label>Tanggal bermain<input name="tanggal" type="date" required min="<?= e(date('Y-m-d')) ?>" value="<?= e($_POST['tanggal'] ?? $booking['tanggal']) ?>" data-booking-date></label>
            <label>Jam mulai<select name="jam_mulai" required data-time-select>
                <option value="" disabled>-- Pilih jam tersedia --</option>
                <?php for ($jam = 7; $jam <= 21; $jam++): $waktu = sprintf('%02d:00', $jam); ?>
                    <option value="<?= $waktu ?>" data-slot-time="<?= $waktu ?>" <?= ($_POST['jam_mulai'] ?? substr($booking['jam_mulai'], 0, 5)) === $waktu ? 'selected' : '' ?>><?= $waktu ?></option>
                <?php endfor; ?>
            </select></label>
        </div>
        <div class="availability-panel" aria-live="polite">
            <div class="availability-heading"><strong>Ketersediaan jadwal</strong><span data-availability-summary>Pilih lapangan dan tanggal untuk melihat jam yang tersedia.</span></div>
            <div class="availability-slots" data-availability-slots></div>
            <div class="availability-legend"><span><i class="legend-dot legend-open"></i>Tersedia</span><span><i class="legend-dot legend-taken"></i>Terisi atau sudah lewat</span></div>
        </div>
        <div class="form-row">
            <label>Durasi<select name="durasi_jam" required data-duration-select>
                <?php for ($jam = 1; $jam <= 3; $jam++): ?>
                    <option value="<?= $jam ?>" <?= (int) ($_POST['durasi_jam'] ?? $booking['durasi_jam']) === $jam ? 'selected' : '' ?>><?= $jam ?> jam</option>
                <?php endfor; ?>
            </select></label>
            <div class="total-preview"><span>Perkiraan total</span><strong data-total-preview>Rp 0</strong><small>Harga akhir dihitung kembali oleh sistem.</small></div>
        </div>
        <?php if ($isAdmin): ?>
            <label>Status booking<select name="status">
                <?php foreach (['menunggu' => 'Menunggu', 'dikonfirmasi' => 'Dikonfirmasi', 'sedang_bermain' => 'Sedang bermain', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'] as $nilai => $label): ?>
                    <option value="<?= e($nilai) ?>" <?= ($_POST['status'] ?? $booking['status']) === $nilai ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select></label>
        <?php endif; ?>
        <label>Keterangan <span class="label-optional">(opsional)</span><textarea name="keterangan" rows="3" maxlength="500"><?= e($_POST['keterangan'] ?? $booking['keterangan']) ?></textarea></label>
        <div class="form-actions"><a class="button button-outline" href="index.php">Batal</a><button class="button" type="submit">Simpan perubahan</button></div>
    </form>
</section>
<?php require __DIR__ . '/../footer.php'; ?>
