<?php
require_once __DIR__ . '/../cek_login.php';
wajibLogin();
$isAdmin = $_SESSION['role'] === 'admin';
$sql = 'SELECT b.*, l.nama AS nama_lapangan, u.nama AS nama_pelanggan '
    . 'FROM bookings b JOIN lapangan l ON l.id = b.lapangan_id JOIN users u ON u.id = b.user_id';
$parameter = [];
if (!$isAdmin) {
    $sql .= ' WHERE b.user_id = ?';
    $parameter[] = (int) $_SESSION['user_id'];
}
$sql .= ' ORDER BY b.tanggal DESC, b.jam_mulai DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parameter);
$bookings = $stmt->fetchAll();
$judulHalaman = 'Daftar Booking';
$akarUrl = '../';
require __DIR__ . '/../header.php';
?>
<section class="page-heading">
    <div><p class="eyebrow"><?= $isAdmin ? 'SEMUA TRANSAKSI' : 'JADWAL ANDA' ?></p><h1><?= $isAdmin ? 'Kelola booking' : 'Booking saya' ?></h1><p class="muted"><?= $isAdmin ? 'Lihat dan kelola seluruh pemesanan lapangan.' : 'Lihat, ubah, atau batalkan booking Anda.' ?></p></div>
    <a class="button" href="tambah.php">+ Booking baru</a>
</section>
<section class="content-panel">
    <?php if ($bookings): ?>
        <div class="table-wrap"><table>
            <thead><tr><?php if ($isAdmin): ?><th>Pelanggan</th><?php endif; ?><th>Lapangan & jadwal</th><th>Durasi</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <?php if ($isAdmin): ?><td><strong><?= e($booking['nama_pelanggan']) ?></strong></td><?php endif; ?>
                    <td><strong><?= e($booking['nama_lapangan']) ?></strong><small class="table-subtitle"><?= e(date('d/m/Y', strtotime($booking['tanggal']))) ?> · <?= e(substr($booking['jam_mulai'], 0, 5)) ?></small></td>
                    <td><?= (int) $booking['durasi_jam'] ?> jam</td>
                    <td><?= rupiah($booking['total_harga']) ?></td>
                    <td><span class="status-pill status-<?= e($booking['status']) ?>"><?= e(labelStatusBooking($booking['status'])) ?></span></td>
                    <td><div class="table-actions">
                        <?php if ($isAdmin || $booking['status'] === 'menunggu'): ?><a class="button button-small button-outline" href="edit.php?id=<?= (int) $booking['id'] ?>">Edit</a><?php endif; ?>
                        <form action="hapus.php" method="post" data-confirm="Yakin ingin menghapus booking ini?">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $booking['id'] ?>">
                            <button class="button button-small button-danger-outline" type="submit">Hapus</button>
                        </form>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php else: ?>
        <div class="empty-state"><h3>Belum ada booking</h3><p>Jadwalkan permainan Anda dan booking akan muncul di halaman ini.</p><a class="button" href="tambah.php">Booking lapangan</a></div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../footer.php'; ?>
