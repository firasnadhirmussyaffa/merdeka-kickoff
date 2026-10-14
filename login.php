<?php
declare(strict_types=1);

// Session login dimulai dengan jelas di halaman autentikasi.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/cek_login.php';

if (sudahLogin()) {
    arahkan('dashboard.php');
}

$error = '';
$username = $_COOKIE['ingat_username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    periksaCsrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ingatUsername = isset($_POST['ingat_username']);

    $stmt = $pdo->prepare('SELECT id, nama, username, password, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        if ($ingatUsername) {
            setcookie('ingat_username', $username, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie('ingat_username', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        arahkan('dashboard.php');
    }

    $error = 'Username atau password belum benar.';
}

$judulHalaman = 'Login';
$akarUrl = '';
require __DIR__ . '/header.php';
?>
<section class="auth-layout">
    <div class="auth-aside">
        <p class="eyebrow">AKUN MERDEKA KICKOFF</p>
        <h1>Masuk dulu,<br><span>lalu turun ke lapangan.</span></h1>
        <p>Atur booking dan cek jadwal bermain Anda dari satu tempat.</p>
        <div class="auth-aside-ball">●</div>
    </div>
    <div class="form-card auth-card">
        <p class="eyebrow">SELAMAT DATANG KEMBALI</p>
        <h2>Login akun</h2>
        <p class="muted">Masukkan username dan password Anda.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post" class="form-stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label>Username
                <input name="username" type="text" autocomplete="username" required maxlength="50" value="<?= e($username) ?>" placeholder="Contoh: pemainbola">
            </label>
            <label>Password
                <input name="password" type="password" autocomplete="current-password" required placeholder="Masukkan password">
            </label>
            <label class="check-row">
                <input type="checkbox" name="ingat_username" value="1" <?= $username !== '' ? 'checked' : '' ?>>
                Ingat username di perangkat ini
            </label>
            <button class="button button-full" type="submit">Login</button>
        </form>
        <p class="form-footnote">Belum punya akun? <a href="register.php">Daftar sekarang</a>.</p>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
