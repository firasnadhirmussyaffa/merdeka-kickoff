<?php
declare(strict_types=1);

require_once __DIR__ . '/koneksi.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function arahkan(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function sudahLogin(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['nama'], $_SESSION['role']);
}

function wajibLogin(): void
{
    if (!sudahLogin()) {
        $_SESSION['pesan_flash'] = [
            'jenis' => 'warning',
            'teks' => 'Silakan login untuk membuka halaman tersebut.',
        ];
        $direktoriScript = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $halamanLogin = str_ends_with($direktoriScript, '/booking')
            || str_ends_with($direktoriScript, '/lapangan')
            ? '../login.php'
            : 'login.php';
        arahkan($halamanLogin);
    }
}

function wajibAdmin(): void
{
    wajibLogin();

    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        exit('Akses ditolak. Halaman ini hanya untuk admin.');
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function periksaCsrf(): void
{
    $tokenDikirim = $_POST['csrf_token'] ?? '';
    $tokenSesi = $_SESSION['csrf_token'] ?? '';

    if (!is_string($tokenDikirim) || !is_string($tokenSesi)
        || $tokenSesi === '' || !hash_equals($tokenSesi, $tokenDikirim)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman, lalu coba kembali.');
    }
}

function pesanFlash(): ?array
{
    $pesan = $_SESSION['pesan_flash'] ?? null;
    unset($_SESSION['pesan_flash']);

    return is_array($pesan) ? $pesan : null;
}

function aturPesan(string $teks, string $jenis = 'success'): void
{
    $_SESSION['pesan_flash'] = ['jenis' => $jenis, 'teks' => $teks];
}

function rupiah(int|float|string $nominal): string
{
    return 'Rp ' . number_format((float) $nominal, 0, ',', '.');
}

function labelStatusBooking(string $status): string
{
    return [
        'menunggu' => 'Menunggu',
        'dikonfirmasi' => 'Dikonfirmasi',
        'sedang_bermain' => 'Sedang bermain',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ][$status] ?? ucfirst(str_replace('_', ' ', $status));
}
