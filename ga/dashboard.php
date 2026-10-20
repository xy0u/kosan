<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga');

$stat = [
    ['Booking baru', "SELECT COUNT(*) FROM booking WHERE status = 'pending'", 'ga/booking.php', 'bi-calendar-plus', 'warning'],
    ['Pembayaran perlu dicek', "SELECT COUNT(*) FROM pembayaran WHERE status = 'menunggu'", 'ga/pembayaran.php', 'bi-cash-coin', 'success'],
    ['Keluhan baru', "SELECT COUNT(*) FROM keluhan WHERE status = 'baru'", 'ga/keluhan.php', 'bi-chat-left-dots', 'danger'],
    ['Pengajuan penghuni', "SELECT COUNT(*) FROM pengajuan WHERE status = 'pending'", 'ga/sewa.php', 'bi-arrow-repeat', 'info'],
    ['Kamar kosong', "SELECT COUNT(*) FROM kamar WHERE status = 'kosong'", 'ga/kamar.php', 'bi-door-open', 'primary'],
    ['Tagihan lewat jatuh tempo', "SELECT COUNT(*) FROM tagihan WHERE status <> 'lunas' AND jatuh_tempo < CURDATE()", 'ga/tagihan.php', 'bi-exclamation-octagon', 'danger'],
];
$periodeIni = date('Y-m');
$belumGenerate = (int) q(
    "SELECT COUNT(*) FROM sewa s WHERE s.status = 'aktif' AND s.tanggal_selesai > ?
     AND NOT EXISTS (SELECT 1 FROM tagihan t WHERE t.sewa_id = s.id AND t.periode = ?)",
    [$periodeIni . '-01', $periodeIni]
)->fetchColumn();
$kejadian = q('SELECT l.*, u.nama FROM laporan_kejadian l JOIN users u ON u.id = l.penjaga_id ORDER BY l.id DESC LIMIT 5')->fetchAll();
$habis = q(
    "SELECT s.*, u.nama, k.nomor_kamar FROM sewa s JOIN users u ON u.id = s.user_id JOIN kamar k ON k.id = s.kamar_id
     WHERE s.status = 'aktif' AND s.tanggal_selesai <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY s.tanggal_selesai"
)->fetchAll();

$title = 'Dashboard GA';
require __DIR__ . '/../includes/header.php';
?>
<?= welcome_banner('Selamat bekerja, ' . $u['nama'], 'Ringkasan pekerjaan yang perlu ditindaklanjuti hari ini.') ?>
<?php if ($belumGenerate): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span><?= $belumGenerate ?> sewa aktif belum punya tagihan <?= periode_label($periodeIni) ?>.</span>
        <a href="<?= url('ga/tagihan.php') ?>" class="btn btn-sm btn-warning">Buat tagihan</a>
    </div>
<?php endif; ?>
<div class="row g-3 mb-4">
    <?php foreach ($stat as [$label, $sql, $link, $icon, $warna]): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="<?= url($link) ?>" class="card shadow-sm stat-card h-100 text-decoration-none"><div class="card-body">
                <i class="bi <?= $icon ?> text-<?= $warna ?>"></i>
                <?php $n = (int) q($sql)->fetchColumn(); ?>
                <div class="fs-2 fw-bold text-body" data-count="<?= $n ?>"><?= $n ?></div>
                <div class="small text-muted"><?= $label ?></div>
            </div></a>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Sewa berakhir ≤ 30 hari</h5>
            <table class="table table-sm mb-0">
                <?php foreach ($habis as $s): ?>
                    <tr><td><?= e($s['nomor_kamar']) ?></td><td><?= e($s['nama']) ?></td><td class="text-end"><?= tgl($s['tanggal_selesai']) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$habis): ?><tr><td class="text-muted">Tidak ada.</td></tr><?php endif; ?>
            </table>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Laporan kejadian terbaru</h5>
            <?php foreach ($kejadian as $l): ?>
                <div class="border-bottom py-2"><b><?= e($l['judul']) ?></b><div class="small text-muted"><?= e($l['nama']) ?> &middot; <?= tgl($l['created_at'], true) ?></div></div>
            <?php endforeach; ?>
            <?php if (!$kejadian): ?><p class="text-muted mb-0">Belum ada laporan.</p><?php endif; ?>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
