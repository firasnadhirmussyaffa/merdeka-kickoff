<?php
require_once __DIR__ . '/cek_login.php';
wajibAdmin();

require_once __DIR__ . '/fpdf/fpdf.php';

function teksPdf(string $teks): string
{
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $teks) ?: $teks;
}

$dari = trim((string) ($_GET['dari'] ?? ''));
$sampai = trim((string) ($_GET['sampai'] ?? ''));
$syarat = [];
$parameter = [];
if ($dari !== '' && DateTime::createFromFormat('Y-m-d', $dari)) {
    $syarat[] = 'b.tanggal >= ?';
    $parameter[] = $dari;
}
if ($sampai !== '' && DateTime::createFromFormat('Y-m-d', $sampai)) {
    $syarat[] = 'b.tanggal <= ?';
    $parameter[] = $sampai;
}
$sql = 'SELECT b.id, b.tanggal, b.jam_mulai, b.durasi_jam, b.total_harga, b.status, '
    . 'u.nama AS nama_pelanggan, l.nama AS nama_lapangan '
    . 'FROM bookings b JOIN users u ON u.id = b.user_id JOIN lapangan l ON l.id = b.lapangan_id';
if ($syarat) {
    $sql .= ' WHERE ' . implode(' AND ', $syarat);
}
$sql .= ' ORDER BY b.tanggal DESC, b.jam_mulai DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parameter);
$baris = $stmt->fetchAll();

if (isset($_GET['cetak']) && $_GET['cetak'] === '1') {
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 17);
    $pdf->SetTextColor(24, 60, 43);
    $pdf->Cell(0, 10, teksPdf('LAPORAN BOOKING MERDEKA KICKOFF'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(90, 100, 94);
    $periode = ($dari !== '' ? $dari : 'Semua tanggal') . ' s.d. ' . ($sampai !== '' ? $sampai : 'Semua tanggal');
    $pdf->Cell(0, 8, teksPdf('Periode: ' . $periode . ' | Dicetak: ' . date('d/m/Y H:i')), 0, 1, 'C');
    $pdf->Ln(4);

    $lebar = [14, 31, 48, 31, 25, 22, 36, 58];
    $judulKolom = ['ID', 'Tanggal', 'Pelanggan', 'Lapangan', 'Mulai', 'Durasi', 'Total', 'Status'];
    $pdf->SetFillColor(34, 91, 62);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    foreach ($judulKolom as $index => $judul) {
        $pdf->Cell($lebar[$index], 9, teksPdf($judul), 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(35, 45, 39);
    $totalKeseluruhan = 0;
    foreach ($baris as $index => $booking) {
        if ($pdf->GetY() > 183) {
            $pdf->AddPage();
        }
        $data = [
            (string) $booking['id'],
            date('d/m/Y', strtotime($booking['tanggal'])),
            $booking['nama_pelanggan'],
            $booking['nama_lapangan'],
            substr($booking['jam_mulai'], 0, 5),
            $booking['durasi_jam'] . ' jam',
            'Rp ' . number_format((float) $booking['total_harga'], 0, ',', '.'),
            labelStatusBooking($booking['status']),
        ];
        foreach ($data as $kolom => $nilai) {
            $pdf->Cell($lebar[$kolom], 8, teksPdf((string) $nilai), 1, 0, $kolom === 6 ? 'R' : 'L');
        }
        $pdf->Ln();
        $totalKeseluruhan += (int) $booking['total_harga'];
    }
    if (!$baris) {
        $pdf->Cell(array_sum($lebar), 10, teksPdf('Tidak ada booking pada periode ini.'), 1, 1, 'C');
    }
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(array_sum($lebar) - $lebar[6] - $lebar[7], 10, teksPdf('Jumlah data: ' . count($baris)), 1, 0, 'L');
    $pdf->Cell($lebar[6] + $lebar[7], 10, teksPdf('Total: Rp ' . number_format($totalKeseluruhan, 0, ',', '.')), 1, 1, 'R');
    $pdf->Output('I', 'laporan-booking-merdeka-kickoff.pdf');
    exit;
}

$judulHalaman = 'Laporan Booking';
$akarUrl = '';
require __DIR__ . '/header.php';
?>
<section class="page-heading"><div><p class="eyebrow">LAPORAN ADMIN</p><h1>Laporan booking</h1><p class="muted">Data booking dapat difilter dan dicetak menjadi PDF.</p></div><a class="text-link" href="dashboard.php">← Dashboard</a></section>
<section class="content-panel report-filter">
    <form method="get" class="filter-form">
        <label>Dari tanggal<input type="date" name="dari" value="<?= e($dari) ?>"></label>
        <label>Sampai tanggal<input type="date" name="sampai" value="<?= e($sampai) ?>"></label>
        <button class="button button-small" type="submit">Terapkan filter</button>
        <a class="button button-small button-outline" href="laporan.php">Reset</a>
        <button class="button button-small button-dark" type="submit" name="cetak" value="1">Cetak PDF (FPDF)</button>
    </form>
</section>
<section class="content-panel">
    <div class="panel-heading"><div><p class="eyebrow">HASIL LAPORAN</p><h2><?= count($baris) ?> booking ditemukan</h2></div></div>
    <?php if ($baris): ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Tanggal</th><th>Pelanggan</th><th>Lapangan</th><th>Jadwal</th><th>Total</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($baris as $booking): ?><tr>
                <td><?= e(date('d/m/Y', strtotime($booking['tanggal']))) ?></td><td><?= e($booking['nama_pelanggan']) ?></td><td><?= e($booking['nama_lapangan']) ?></td>
                <td><?= e(substr($booking['jam_mulai'], 0, 5)) ?> · <?= (int) $booking['durasi_jam'] ?> jam</td><td><?= rupiah($booking['total_harga']) ?></td>
                <td><span class="status-pill status-<?= e($booking['status']) ?>"><?= e(labelStatusBooking($booking['status'])) ?></span></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    <?php else: ?><div class="empty-state compact"><h3>Data belum tersedia</h3><p>Booking sesuai filter akan tampil di sini.</p></div><?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>
