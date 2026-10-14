<?php
require_once __DIR__ . '/../cek_login.php';
wajibLogin();

$error = '';
$lapanganAktif = $pdo->query("SELECT id, nama, harga_per_jam FROM lapangan WHERE status = 'aktif' ORDER BY nama")->fetchAll();
$lapanganPilihan = (int) ($_POST['lapangan_id'] ?? $_GET['lapangan_id'] ?? 0);
$tanggal = (string) ($_POST['tanggal'] ?? '');
$jamMulai = (string) ($_POST['jam_mulai'] ?? '08:00');
$durasi = (int) ($_POST['durasi_jam'] ?? 1);
$keterangan = trim((string) ($_POST['keterangan'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    periksaCsrf();
    $lapanganId = filter_var($_POST['lapangan_id'] ?? null, FILTER_VALIDATE_INT);
    $tanggalObj = DateTime::createFromFormat('Y-m-d', $tanggal);
    $waktuValid = preg_match('/^(0[7-9]|1[0-9]|2[0-1]):00$/', $jamMulai) === 1;

    if (!$lapanganId || !$tanggalObj || $tanggalObj->format('Y-m-d') !== $tanggal || $tanggal < date('Y-m-d')) {
        $error = 'Pilih lapangan dan tanggal hari ini atau setelahnya.';
    } elseif (!$waktuValid || !in_array($durasi, [1, 2, 3], true)) {
        $error = 'Jam mulai harus antara 07.00–21.00 dan durasi maksimal 3 jam.';
    } elseif ($tanggal === date('Y-m-d') && $jamMulai <= date('H:i')) {
        $error = 'Jam tersebut sudah lewat. Silakan pilih waktu bermain berikutnya.';
    } elseif (mb_strlen($keterangan) > 500) {
        $error = 'Keterangan maksimal 500 karakter.';
    } else {
        $cekLapangan = $pdo->prepare("SELECT id, harga_per_jam FROM lapangan WHERE id = ? AND status = 'aktif'");
        $cekLapangan->execute([$lapanganId]);
        $lapangan = $cekLapangan->fetch();

        if (!$lapangan) {
            $error = 'Lapangan yang dipilih tidak tersedia.';
        } else {
            $tabrakan = $pdo->prepare(
                "SELECT COUNT(*) FROM bookings
                 WHERE lapangan_id = ? AND tanggal = ? AND status <> 'dibatalkan'
                   AND TIME_TO_SEC(jam_mulai) < (TIME_TO_SEC(?) + (? * 3600))
                   AND (TIME_TO_SEC(jam_mulai) + (durasi_jam * 3600)) > TIME_TO_SEC(?)"
            );
            $tabrakan->execute([$lapanganId, $tanggal, $jamMulai, $durasi, $jamMulai]);
            if ((int) $tabrakan->fetchColumn() > 0) {
                $error = 'Jadwal tersebut bertabrakan dengan booking lain. Silakan pilih jam yang berbeda.';
            } else {
                $total = (int) $lapangan['harga_per_jam'] * $durasi;
                $simpan = $pdo->prepare(
                    "INSERT INTO bookings (user_id, lapangan_id, tanggal, jam_mulai, durasi_jam, total_harga, status, keterangan)
                     VALUES (?, ?, ?, ?, ?, ?, 'menunggu', ?)"
                );
                $simpan->execute([(int) $_SESSION['user_id'], $lapanganId, $tanggal, $jamMulai, $durasi, $total, $keterangan]);
                aturPesan('Booking berhasil dibuat dan sedang menunggu konfirmasi.');
                arahkan('index.php');
            }
        }
    }
}

$judulHalaman = 'Booking Lapangan';
$akarUrl = '../';
require __DIR__ . '/../header.php';
?>
<section class="page-heading"><div><p class="eyebrow">PESAN WAKTU BERMAIN</p><h1>Booking lapangan</h1><p class="muted">Pilih tanggal, jam, dan durasi. Total dihitung otomatis.</p></div><a class="text-link" href="index.php">← Kembali ke booking</a></section>
<section class="form-card form-card-wide booking-form-card">
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!$lapanganAktif): ?>
        <div class="empty-state"><h3>Belum ada lapangan aktif</h3><p>Silakan kembali lagi nanti atau hubungi pengelola.</p><a class="button button-outline" href="../index.php">Kembali ke beranda</a></div>
    <?php else: ?>
    <form method="post" class="form-stack" data-booking-form data-availability-url="ketersediaan.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Pilih lapangan
            <select name="lapangan_id" required data-field-select>
                <option value="">-- Pilih lapangan --</option>
                <?php foreach ($lapanganAktif as $item): ?>
                    <option value="<?= (int) $item['id'] ?>" data-price="<?= (int) $item['harga_per_jam'] ?>" <?= $lapanganPilihan === (int) $item['id'] ? 'selected' : '' ?>>
                        <?= e($item['nama']) ?> · <?= rupiah($item['harga_per_jam']) ?>/jam
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <div class="form-row">
            <label>Tanggal bermain<input name="tanggal" type="date" required min="<?= e(date('Y-m-d')) ?>" value="<?= e($tanggal) ?>"></label>
            <label>Jam mulai<select name="jam_mulai" required data-time-select>
                <option value="" disabled <?= $jamMulai === '' ? 'selected' : '' ?>>-- Pilih jam tersedia --</option>
                <?php for ($jam = 7; $jam <= 21; $jam++): $waktu = sprintf('%02d:00', $jam); ?>
                    <option value="<?= $waktu ?>" data-slot-time="<?= $waktu ?>" <?= $jamMulai === $waktu ? 'selected' : '' ?>><?= $waktu ?></option>
                <?php endfor; ?>
            </select></label>
        </div>
        <div class="availability-panel" aria-live="polite">
            <div class="availability-heading"><strong>Ketersediaan jadwal</strong><span data-availability-summary>Pilih lapangan dan tanggal untuk melihat jam yang tersedia.</span></div>
            <div class="availability-slots" data-availability-slots></div>
            <div class="availability-legend"><span><i class="legend-dot legend-open"></i>Tersedia</span><span><i class="legend-dot legend-taken"></i>Terisi atau sudah lewat</span></div>
        </div>
        <div class="form-row">
            <label>Durasi sewa<select name="durasi_jam" required data-duration-select>
                <?php for ($jam = 1; $jam <= 3; $jam++): ?>
                    <option value="<?= $jam ?>" <?= $durasi === $jam ? 'selected' : '' ?>><?= $jam ?> jam</option>
                <?php endfor; ?>
            </select></label>
            <div class="total-preview"><span>Perkiraan total</span><strong data-total-preview>Rp 0</strong><small>Harga akhir dihitung kembali oleh sistem.</small></div>
        </div>
        <label>Keterangan <span class="label-optional">(opsional)</span><textarea name="keterangan" rows="3" maxlength="500" placeholder="Contoh: latihan tim kantor"><?= e($keterangan) ?></textarea></label>
        <div class="form-actions"><a class="button button-outline" href="index.php">Batal</a><button class="button" type="submit">Kirim booking</button></div>
    </form>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../footer.php'; ?>
