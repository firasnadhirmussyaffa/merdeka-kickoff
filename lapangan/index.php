<?php
require_once __DIR__ . '/../cek_login.php';
wajibAdmin();
$judulHalaman = 'Kelola Lapangan';
$akarUrl = '../';
$lapangan = $pdo->query('SELECT * FROM lapangan ORDER BY id DESC')->fetchAll();
require __DIR__ . '/../header.php';
?>
<section class="page-heading">
    <div><p class="eyebrow">PENGELOLAAN DATA</p><h1>Daftar lapangan</h1><p class="muted">Tambah, ubah, atau nonaktifkan lapangan yang tersedia.</p></div>
    <a class="button" href="tambah.php">+ Tambah lapangan</a>
</section>
<section class="content-panel">
    <?php if ($lapangan): ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Nama lapangan</th><th>Harga / jam</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($lapangan as $item): ?>
                <tr>
                    <td><strong><?= e($item['nama']) ?></strong><small class="table-subtitle"><?= e($item['deskripsi'] ?? '') ?></small></td>
                    <td><?= rupiah($item['harga_per_jam']) ?></td>
                    <td><span class="status-pill status-<?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span></td>
                    <td><div class="table-actions"><a class="button button-small button-outline" href="edit.php?id=<?= (int) $item['id'] ?>">Edit</a>
                        <form action="hapus.php" method="post" data-confirm="Hapus lapangan ini? Lapangan yang sudah memiliki booking tidak dapat dihapus.">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                            <button class="button button-small button-danger-outline" type="submit">Hapus</button>
                        </form></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php else: ?><div class="empty-state"><h3>Belum ada lapangan</h3><p>Tambahkan lapangan pertama untuk ditampilkan pada halaman beranda.</p><a class="button" href="tambah.php">Tambah lapangan</a></div><?php endif; ?>
</section>
<?php require __DIR__ . '/../footer.php'; ?>
