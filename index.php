<?php
require_once __DIR__ . '/cek_login.php';
$judulHalaman = 'Sewa Lapangan Mini Soccer';
$akarUrl = '';
$daftarLapangan = $pdo->query(
    "SELECT id, nama, deskripsi, harga_per_jam, status
     FROM lapangan WHERE status = 'aktif' ORDER BY id"
)->fetchAll();
require __DIR__ . '/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">LAPANGAN MINI SOCCER · BOOKING ONLINE</p>
        <h1>Waktunya kumpulkan tim dan <span>mulai kickoff.</span></h1>
        <p class="hero-description">
            Pilih lapangan, tentukan jam main, lalu booking dengan mudah di Merdeka Kickoff.
            Cocok untuk latihan, tanding persahabatan, dan seru-seruan bareng.
        </p>
        <div class="hero-actions">
            <a class="button" href="<?= sudahLogin() ? 'booking/tambah.php' : 'register.php' ?>">
                Booking lapangan <span aria-hidden="true">→</span>
            </a>
            <a class="button button-outline" href="#daftar-lapangan">Lihat harga</a>
        </div>
        <div class="hero-note"><span class="live-dot"></span> Pilih jadwal dan booking langsung</div>
    </div>
    <div class="hero-art" aria-label="Ilustrasi lapangan mini soccer">
        <div class="stadium-score">
            <span>MERDEKA KICKOFF</span>
            <strong>MAIN BARENG, <em>MENANG BARENG.</em></strong>
        </div>
        <div class="field-lines">
            <span class="halfway"></span>
            <span class="center-circle"></span>
            <span class="goal goal-left"></span>
            <span class="goal goal-right"></span>
        </div>
        <span class="ball">●</span>
        <div class="stadium-base"></div>
    </div>
</section>

<section class="section-block" id="daftar-lapangan">
    <div class="section-heading">
        <div>
            <p class="eyebrow">PILIH TEMPAT MAIN</p>
            <h2>Daftar lapangan</h2>
            <p class="muted">Harga dihitung per jam. Pilih jadwal yang paling pas untuk tim Anda.</p>
        </div>
        <?php if (sudahLogin()): ?>
            <a class="text-link" href="booking/index.php">Lihat booking saya →</a>
        <?php endif; ?>
    </div>
    <?php if ($daftarLapangan): ?>
        <div class="field-grid">
            <?php foreach ($daftarLapangan as $index => $lapangan): ?>
                <article class="field-card">
                    <div class="field-art field-art-<?= ($index % 3) + 1 ?>">
                        <span class="field-number">0<?= $index + 1 ?></span>
                        <span class="field-art-label">MINI SOCCER</span>
                        <span class="mini-ball">●</span>
                    </div>
                    <div class="field-card-body">
                        <div class="field-card-title">
                            <h3><?= e($lapangan['nama']) ?></h3>
                            <span class="status-pill status-aktif">Tersedia</span>
                        </div>
                        <p><?= e($lapangan['deskripsi'] ?: 'Lapangan mini soccer siap digunakan.') ?></p>
                        <div class="field-card-bottom">
                            <span class="price"><?= rupiah($lapangan['harga_per_jam']) ?><small> / jam</small></span>
                            <a class="button button-small" href="<?= sudahLogin() ? 'booking/tambah.php?lapangan_id=' . (int) $lapangan['id'] : 'login.php' ?>">Pilih</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>Belum ada lapangan aktif</h3>
            <p>Admin dapat menambahkan lapangan melalui dashboard.</p>
        </div>
    <?php endif; ?>
</section>

<section class="how-section">
    <div>
        <p class="eyebrow">TIGA LANGKAH MUDAH</p>
        <h2>Tim siap,<br>tinggal main.</h2>
    </div>
    <div class="steps">
        <div class="step"><span>01</span><div><h3>Pilih lapangan</h3><p>Lihat daftar lapangan dan harga sewanya.</p></div></div>
        <div class="step"><span>02</span><div><h3>Tentukan jadwal</h3><p>Pilih tanggal, jam mulai, dan lama bermain.</p></div></div>
        <div class="step"><span>03</span><div><h3>Datang dan kickoff</h3><p>Pantau status booking melalui akun Anda.</p></div></div>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
