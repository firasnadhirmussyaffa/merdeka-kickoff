<?php
declare(strict_types=1);

require_once __DIR__ . '/cek_login.php';

if (sudahLogin()) {
    arahkan('dashboard.php');
}

$error = '';
$nama = '';
$username = '';
$akunPertama = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    periksaCsrf();
    $nama = trim((string) ($_POST['nama'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi_password'] ?? '');

    if (mb_strlen($nama) < 2 || mb_strlen($nama) > 100) {
        $error = 'Nama harus terdiri dari 2 sampai 100 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username harus 3–50 karakter dan hanya boleh memakai huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        $cek = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $cek->execute([$username]);

        if ($cek->fetch()) {
            $error = 'Username tersebut sudah digunakan.';
        } else {
            $jumlahAkun = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $role = $jumlahAkun === 0 ? 'admin' : 'pelanggan';
            $simpan = $pdo->prepare(
                'INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)'
            );
            $simpan->execute([$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role]);

            aturPesan(
                $role === 'admin'
                    ? 'Akun pertama berhasil dibuat sebagai admin. Silakan login.'
                    : 'Pendaftaran berhasil. Silakan login untuk mulai booking.'
            );
            arahkan('login.php');
        }
    }
}

$judulHalaman = 'Daftar Akun';
$akarUrl = '';
require __DIR__ . '/header.php';
?>
<section class="auth-layout">
    <div class="auth-aside">
        <p class="eyebrow">GABUNG KE TIM</p>
        <h1>Satu akun untuk<br><span>semua jadwal main.</span></h1>
        <p>Buat akun pelanggan untuk mengatur dan melihat booking Anda.</p>
        <div class="auth-aside-ball">●</div>
    </div>
    <div class="form-card auth-card">
        <p class="eyebrow">BUAT AKUN BARU</p>
        <h2>Daftar pelanggan</h2>
        <p class="muted">Isi data singkat berikut untuk melanjutkan.</p>
        <?php if ($akunPertama): ?><div class="alert">Akun pertama yang didaftarkan akan menjadi admin untuk mengatur lapangan dan booking.</div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label>Nama lengkap
                <input name="nama" type="text" required maxlength="100" autocomplete="name" value="<?= e($nama) ?>" placeholder="Nama Anda">
            </label>
            <label>Username
                <input name="username" type="text" required minlength="3" maxlength="50" autocomplete="username" value="<?= e($username) ?>" placeholder="Contoh: pemainbola">
            </label>
            <label>Password
                <input name="password" type="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter">
            </label>
            <label>Konfirmasi password
                <input name="konfirmasi_password" type="password" required minlength="8" autocomplete="new-password" placeholder="Ulangi password">
            </label>
            <button class="button button-full" type="submit">Buat akun</button>
        </form>
        <p class="form-footnote">Sudah terdaftar? <a href="login.php">Login di sini</a>.</p>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
