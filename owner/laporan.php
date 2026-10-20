<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('owner');

$tahun = (int) ($_GET['tahun'] ?? date('Y'));
$masuk = q(
    "SELECT MONTH(tanggal_bayar) b, SUM(jumlah) j FROM pembayaran WHERE status = 'valid' AND YEAR(tanggal_bayar) = ? GROUP BY b",
    [$tahun]
)->fetchAll(PDO::FETCH_KEY_PAIR);
$keluar = q(
    'SELECT MONTH(tanggal) b, SUM(jumlah) j FROM pengeluaran WHERE YEAR(tanggal) = ? GROUP BY b',
    [$tahun]
)->fetchAll(PDO::FETCH_KEY_PAIR);

$rows = [];
for ($m = 1; $m <= 12; $m++) {
    $rows[] = ['bulan' => BULAN[$m], 'masuk' => (int) ($masuk[$m] ?? 0), 'keluar' => (int) ($keluar[$m] ?? 0)];
}
$totalMasuk = array_sum(array_column($rows, 'masuk'));
$totalKeluar = array_sum(array_column($rows, 'keluar'));

// Export CSV (bisa dibuka di Excel)
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=laporan-kosan-$tahun.csv");
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Bulan', 'Pemasukan', 'Pengeluaran', 'Laba'], ';');
    foreach ($rows as $r) {
        fputcsv($out, [$r['bulan'], $r['masuk'], $r['keluar'], $r['masuk'] - $r['keluar']], ';');
    }
    fputcsv($out, ['TOTAL', $totalMasuk, $totalKeluar, $totalMasuk - $totalKeluar], ';');
    exit;
}

$tahunTersedia = q(
    'SELECT DISTINCT y FROM (SELECT YEAR(tanggal_bayar) y FROM pembayaran UNION SELECT YEAR(tanggal) FROM pengeluaran UNION SELECT YEAR(CURDATE())) t ORDER BY y DESC'
)->fetchAll(PDO::FETCH_COLUMN);

$title = 'Laporan Keuangan';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Laporan Keuangan <?= $tahun ?></h1>
    <div class="d-flex gap-2 no-print">
        <form>
            <select name="tahun" class="form-select" onchange="this.form.submit()">
                <?php foreach ($tahunTersedia as $y): ?><option <?= $y == $tahun ? 'selected' : '' ?>><?= $y ?></option><?php endforeach; ?>
            </select>
        </form>
        <a href="?tahun=<?= $tahun ?>&export=csv" class="btn btn-outline-success text-nowrap"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <button onclick="window.print()" class="btn btn-outline-danger text-nowrap"><i class="bi bi-file-earmark-pdf"></i> PDF / Cetak</button>
    </div>
</div>
<div class="card shadow-sm"><div class="card-body table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Bulan</th><th class="text-end">Pemasukan</th><th class="text-end">Pengeluaran</th><th class="text-end">Laba</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= $r['bulan'] ?></td>
                <td class="text-end"><?= rupiah($r['masuk']) ?></td>
                <td class="text-end"><?= rupiah($r['keluar']) ?></td>
                <td class="text-end fw-semibold <?= $r['masuk'] - $r['keluar'] < 0 ? 'text-danger' : '' ?>"><?= rupiah($r['masuk'] - $r['keluar']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot class="fw-bold">
            <tr>
                <td>TOTAL</td>
                <td class="text-end text-success"><?= rupiah($totalMasuk) ?></td>
                <td class="text-end text-danger"><?= rupiah($totalKeluar) ?></td>
                <td class="text-end"><?= rupiah($totalMasuk - $totalKeluar) ?></td>
            </tr>
        </tfoot>
    </table>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
