<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('owner');

$bulanIni = date('Y-m');
$kamar = q("SELECT COUNT(*) total, SUM(status = 'terisi') terisi, SUM(status = 'kosong') kosong, SUM(status = 'perbaikan') perbaikan FROM kamar")->fetch();
$okupansi = $kamar['total'] ? round($kamar['terisi'] / $kamar['total'] * 100) : 0;
$pemasukan = (int) q("SELECT COALESCE(SUM(jumlah), 0) FROM pembayaran WHERE status = 'valid' AND DATE_FORMAT(tanggal_bayar, '%Y-%m') = ?", [$bulanIni])->fetchColumn();
$pengeluaran = (int) q("SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?", [$bulanIni])->fetchColumn();
$nunggak = q(
    "SELECT t.*, u.nama, u.no_hp, k.nomor_kamar, DATEDIFF(CURDATE(), t.jatuh_tempo) AS telat FROM tagihan t
     JOIN sewa s ON s.id = t.sewa_id JOIN users u ON u.id = s.user_id JOIN kamar k ON k.id = s.kamar_id
     WHERE t.status <> 'lunas' AND t.jatuh_tempo < CURDATE() ORDER BY t.jatuh_tempo"
)->fetchAll();
$kejadian = q('SELECT l.*, u.nama FROM laporan_kejadian l JOIN users u ON u.id = l.penjaga_id ORDER BY l.id DESC LIMIT 5')->fetchAll();

// Grafik 6 bulan terakhir
$grafik = [];
for ($i = 5; $i >= 0; $i--) {
    $p = date('Y-m', strtotime("first day of -$i month"));
    $grafik[] = [
        'label'  => substr(BULAN[(int) substr($p, 5)], 0, 3),
        'masuk'  => (int) q("SELECT COALESCE(SUM(jumlah), 0) FROM pembayaran WHERE status = 'valid' AND DATE_FORMAT(tanggal_bayar, '%Y-%m') = ?", [$p])->fetchColumn(),
        'keluar' => (int) q("SELECT COALESCE(SUM(jumlah), 0) FROM pengeluaran WHERE DATE_FORMAT(tanggal, '%Y-%m') = ?", [$p])->fetchColumn(),
    ];
}
$maks = max(1, ...array_map(fn($g) => max($g['masuk'], $g['keluar']), $grafik));

$title = 'Dashboard Owner';
require __DIR__ . '/../includes/header.php';
?>
<?= welcome_banner('Selamat datang, ' . $u['nama'], "Hunian {$okupansi}% bulan ini · " . count($nunggak) . ' tagihan terlambat') ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card shadow-sm stat-card h-100"><div class="card-body">
        <div class="text-muted small">Tingkat hunian</div><div class="fs-2 fw-bold"><?= $okupansi ?>%</div>
        <div class="progress mt-1" style="height:6px"><div class="progress-bar" style="width:<?= $okupansi ?>%"></div></div>
        <div class="small text-muted mt-1"><?= (int) $kamar['terisi'] ?> terisi · <?= (int) $kamar['kosong'] ?> kosong · <?= (int) $kamar['perbaikan'] ?> perbaikan</div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card shadow-sm h-100"><div class="card-body">
        <div class="text-muted small">Pemasukan <?= periode_label($bulanIni) ?></div><div class="fs-4 fw-bold text-success"><?= rupiah($pemasukan) ?></div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card shadow-sm h-100"><div class="card-body">
        <div class="text-muted small">Pengeluaran <?= periode_label($bulanIni) ?></div><div class="fs-4 fw-bold text-danger"><?= rupiah($pengeluaran) ?></div>
    </div></div></div>
    <div class="col-6 col-lg-3"><div class="card shadow-sm h-100"><div class="card-body">
        <div class="text-muted small">Laba bersih</div><div class="fs-4 fw-bold <?= $pemasukan - $pengeluaran < 0 ? 'text-danger' : '' ?>"><?= rupiah($pemasukan - $pengeluaran) ?></div>
    </div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-4"><div class="card-body">
            <div class="d-flex justify-content-between"><h5>6 bulan terakhir</h5>
                <div class="small"><span class="d-inline-block rounded-1 me-1" style="width:10px;height:10px;background:#0f766e"></span>Pemasukan <span class="d-inline-block rounded-1 ms-3 me-1" style="width:10px;height:10px;background:#f59e0b"></span>Pengeluaran</div></div>
            <div class="d-flex align-items-end justify-content-around mt-3" style="height:200px" data-reveal>
                <?php foreach ($grafik as $g): ?>
                    <div class="text-center" style="width:14%">
                        <div class="d-flex align-items-end justify-content-center gap-1" style="height:175px">
                            <div class="rounded-top grow-bar" style="background:#0f766e;width:40%;height:<?= $g['masuk'] / $maks * 100 ?>%" title="Pemasukan <?= rupiah($g['masuk']) ?>" data-bs-toggle="tooltip"></div>
                            <div class="rounded-top grow-bar" style="background:#f59e0b;width:40%;height:<?= $g['keluar'] / $maks * 100 ?>%" title="Pengeluaran <?= rupiah($g['keluar']) ?>" data-bs-toggle="tooltip"></div>
                        </div>
                        <div class="small text-muted"><?= $g['label'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div></div>
        <div class="card shadow-sm"><div class="card-body">
            <h5>Tagihan terlambat <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis ms-1"><?= count($nunggak) ?></span></h5>
            <div class="table-responsive"><table class="table table-sm mb-0 text-nowrap">
                <?php foreach ($nunggak as $n): ?>
                    <tr>
                        <td><?= e($n['nomor_kamar']) ?></td><td><?= e($n['nama']) ?></td>
                        <td><?= periode_label($n['periode']) ?></td><td><?= rupiah($n['jumlah']) ?></td>
                        <td class="text-danger small">telat <?= $n['telat'] ?> hari</td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$nunggak): ?><tr><td class="text-muted">Tidak ada tagihan yang terlambat.</td></tr><?php endif; ?>
            </table></div>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Laporan kejadian terbaru</h5>
            <?php foreach ($kejadian as $l): ?>
                <div class="border-bottom py-2">
                    <b><?= e($l['judul']) ?></b>
                    <div class="small text-muted"><?= e($l['nama']) ?> &middot; <?= tgl($l['created_at'], true) ?></div>
                    <div class="small"><?= e(mb_strimwidth($l['isi'], 0, 120, '…')) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$kejadian): ?><p class="text-muted mb-0">Belum ada laporan.</p><?php endif; ?>
            <a href="<?= url('penjaga/kejadian.php') ?>" class="small d-block mt-2">Lihat semua →</a>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
