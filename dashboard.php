<?php
require_once __DIR__ . '/cek_login.php';
wajibLogin();

$judulHalaman = 'Dashboard';
$akarUrl = '';
$isAdmin = $_SESSION['role'] === 'admin';
$filterUser = $isAdmin ? '' : ' WHERE user_id = ' . (int) $_SESSION['user_id'];
$jumlahBooking = (int) $pdo->query('SELECT COUNT(*) FROM bookings' . $filterUser)->fetchColumn();
$jumlahMenunggu = (int) $pdo->query(
    'SELECT COUNT(*) FROM bookings' . ($isAdmin ? " WHERE status = 'menunggu'" : " WHERE user_id = " . (int) $_SESSION['user_id'] . " AND status = 'menunggu'")
)->fetchColumn();
$jumlahLapangan = (int) $pdo->query("SELECT COUNT(*) FROM lapangan WHERE status = 'aktif'")->fetchColumn();

$sqlTerbaru = 'SELECT b.id, b.tanggal, b.jam_mulai, b.durasi_jam, b.total_harga, b.status, '
    . 'l.nama AS nama_lapangan, u.nama AS nama_pelanggan '
    . 'FROM bookings b JOIN lapangan l ON l.id = b.lapangan_id '
    . 'JOIN users u ON u.id = b.user_id';
if (!$isAdmin) {
    $sqlTerbaru .= ' WHERE b.user_id = ' . (int) $_SESSION['user_id'];
}
$sqlTerbaru .= ' ORDER BY b.created_at DESC LIMIT 5';
$bookingTerbaru = $pdo->query($sqlTerbaru)->fetchAll();

require __DIR__ . '/header.php';
?>
<section class="page-heading">
    <div>
        <p class="eyebrow"><?= $isAdmin ? 'AREA ADMIN' : 'AREA PELANGGAN' ?></p>
        <h1>Halo, <?= e($_SESSION['nama']) ?>!</h1>
        <p class="muted"><?= $isAdmin ? 'Ringkasan booking dan lapangan hari ini.' : 'Ini ringkasan jadwal dan aktivitas booking Anda.' ?></p>
    </div>
    <a class="button" href="booking/tambah.php">+ Booking lapangan</a>
</section>
<section class="stats-grid">
    <article class="stat-card"><span>Total booking</span><strong><?= $jumlahBooking ?></strong><small><?= $isAdmin ? 'Semua booking' : 'Booking Anda' ?></small></article>
    <article class="stat-card stat-highlight"><span>Menunggu konfirmasi</span><strong><?= $jumlahMenunggu ?></strong><small>Perlu dicek kembali</small></article>
    <article class="stat-card"><span>Lapangan aktif</span><strong><?= $jumlahLapangan ?></strong><small>Siap untuk dibooking</small></article>
</section>
<section class="content-panel">
    <div class="panel-heading">
        <div><p class="eyebrow">AKTIVITAS TERBARU</p><h2>Booking terbaru</h2></div>
        <a class="text-link" href="booking/index.php">Semua booking →</a>
    </div>
    <?php if ($bookingTerbaru): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Lapangan</th><?php if ($isAdmin): ?><th>Pelanggan</th><?php endif; ?><th>Tanggal & jam</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($bookingTerbaru as $booking): ?>
                    <tr>
                        <td><strong><?= e($booking['nama_lapangan']) ?></strong></td>
                        <?php if ($isAdmin): ?><td><?= e($booking['nama_pelanggan']) ?></td><?php endif; ?>
                        <td><?= e(date('d/m/Y', strtotime($booking['tanggal']))) ?> · <?= e(substr($booking['jam_mulai'], 0, 5)) ?> (<?= (int) $booking['durasi_jam'] ?> jam)</td>
                        <td><?= rupiah($booking['total_harga']) ?></td>
                        <td><span class="status-pill status-<?= e($booking['status']) ?>"><?= e(labelStatusBooking($booking['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state compact"><h3>Belum ada booking</h3><p>Booking pertama Anda akan muncul di sini.</p><a class="text-link" href="booking/tambah.php">Buat booking →</a></div>
    <?php endif; ?>
</section>
<?php if ($isAdmin): ?>
    <section class="admin-shortcuts">
        <a href="lapangan/index.php"><span>Kelola lapangan</span><strong>Atur nama, harga, dan ketersediaan →</strong></a>
        <a href="laporan.php"><span>Laporan booking</span><strong>Lihat dan cetak laporan →</strong></a>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
