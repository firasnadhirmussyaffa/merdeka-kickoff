<?php
declare(strict_types=1);

require_once __DIR__ . '/../koneksi.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function kirimJson(int $kode, array $data): never
{
    http_response_code($kode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    kirimJson(401, ['success' => false, 'message' => 'Silakan login kembali.']);
}

$lapanganId = filter_input(INPUT_GET, 'lapangan_id', FILTER_VALIDATE_INT);
$tanggal = trim((string) ($_GET['tanggal'] ?? ''));
$durasi = filter_input(INPUT_GET, 'durasi_jam', FILTER_VALIDATE_INT);
$excludeId = filter_input(INPUT_GET, 'exclude_booking_id', FILTER_VALIDATE_INT) ?: 0;

$tanggalObj = DateTime::createFromFormat('!Y-m-d', $tanggal);
$tanggalError = DateTime::getLastErrors();
$tanggalValid = $tanggalObj
    && $tanggalObj->format('Y-m-d') === $tanggal
    && ($tanggalError === false || ($tanggalError['warning_count'] === 0 && $tanggalError['error_count'] === 0));

if (!$lapanganId || !$tanggalValid || $tanggal < date('Y-m-d') || !in_array($durasi, [1, 2, 3], true)) {
    kirimJson(422, ['success' => false, 'message' => 'Pilih lapangan, tanggal hari ini atau setelahnya, dan durasi 1–3 jam.']);
}

if ($excludeId > 0) {
    $cekBooking = $pdo->prepare('SELECT id, user_id FROM bookings WHERE id = ?');
    $cekBooking->execute([$excludeId]);
    $bookingDikecualikan = $cekBooking->fetch();
    $bolehMengecualikan = $bookingDikecualikan
        && ($_SESSION['role'] === 'admin' || (int) $bookingDikecualikan['user_id'] === (int) $_SESSION['user_id']);

    if (!$bolehMengecualikan) {
        kirimJson(403, ['success' => false, 'message' => 'Anda tidak dapat mengubah booking tersebut.']);
    }
}

$cekLapangan = $pdo->prepare("SELECT id FROM lapangan WHERE id = ? AND status = 'aktif'");
$cekLapangan->execute([$lapanganId]);
if (!$cekLapangan->fetch()) {
    kirimJson(404, ['success' => false, 'message' => 'Lapangan tersebut tidak tersedia.']);
}

$sql = "SELECT jam_mulai, durasi_jam
        FROM bookings
        WHERE lapangan_id = ? AND tanggal = ? AND status <> 'dibatalkan'";
$parameter = [$lapanganId, $tanggal];
if ($excludeId > 0) {
    $sql .= ' AND id <> ?';
    $parameter[] = $excludeId;
}
$stmt = $pdo->prepare($sql);
$stmt->execute($parameter);
$bookingTerisi = $stmt->fetchAll();

$durasiDetik = (int) $durasi * 3600;
$waktuSekarang = ((int) date('H') * 3600) + ((int) date('i') * 60);
$slot = [];

for ($jam = 7; $jam <= 21; $jam++) {
    $waktu = sprintf('%02d:00', $jam);
    $mulaiDetik = $jam * 3600;
    $tersedia = !($tanggal === date('Y-m-d') && $mulaiDetik <= $waktuSekarang);

    foreach ($bookingTerisi as $booking) {
        [$jamBooking, $menitBooking] = array_map('intval', explode(':', substr($booking['jam_mulai'], 0, 5)));
        $bookingMulai = ($jamBooking * 3600) + ($menitBooking * 60);
        $bookingSelesai = $bookingMulai + ((int) $booking['durasi_jam'] * 3600);

        if ($mulaiDetik < $bookingSelesai && ($mulaiDetik + $durasiDetik) > $bookingMulai) {
            $tersedia = false;
            break;
        }
    }

    $slot[] = ['time' => $waktu, 'available' => $tersedia];
}

kirimJson(200, [
    'success' => true,
    'date' => $tanggal,
    'duration' => (int) $durasi,
    'slots' => $slot,
]);
