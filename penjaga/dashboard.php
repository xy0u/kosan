<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('penjaga');

$tugasAktif = q("SELECT * FROM tugas_penjaga WHERE penjaga_id = ? AND status <> 'selesai' ORDER BY id", [$u['id']])->fetchAll();
$tamuDiDalam = (int) q('SELECT COUNT(*) FROM tamu WHERE jam_keluar IS NULL')->fetchColumn();
$penghuni = q(
    "SELECT u.nama, u.no_hp, k.nomor_kamar FROM sewa s
     JOIN users u ON u.id = s.user_id JOIN kamar k ON k.id = s.kamar_id
     WHERE s.status = 'aktif' ORDER BY k.nomor_kamar"
)->fetchAll();

$title = 'Dashboard Penjaga';
require __DIR__ . '/../includes/header.php';
?>
<div class="mb-4">
    <h1 class="h3 mb-1">Ringkasan hari ini</h1>
    <p class="text-muted mb-0"><?= tgl(date('Y-m-d')) ?> &middot; Bertugas: <?= e($u['nama']) ?></p>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <a href="<?= url('penjaga/tugas.php') ?>" class="card shadow-sm stat-card text-decoration-none"><div class="card-body d-flex justify-content-between">
            <div><div class="text-muted small">Tugas aktif</div><div class="fs-2 fw-bold"><?= count($tugasAktif) ?></div></div>
            <i class="bi bi-tools text-primary"></i>
        </div></a>
    </div>
    <div class="col-6 col-md-4">
        <a href="<?= url('penjaga/tamu.php') ?>" class="card shadow-sm stat-card text-decoration-none"><div class="card-body d-flex justify-content-between">
            <div><div class="text-muted small">Tamu di dalam</div><div class="fs-2 fw-bold"><?= $tamuDiDalam ?></div></div>
            <i class="bi bi-person-badge text-success"></i>
        </div></a>
    </div>
    <div class="col-12 col-md-4">
        <a href="<?= url('penjaga/kejadian.php') ?>" class="btn btn-danger w-100 h-100 d-flex align-items-center justify-content-center fs-5">
            <i class="bi bi-exclamation-triangle me-2"></i> Lapor kejadian
        </a>
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Tugas yang harus dikerjakan</h5>
            <?php foreach ($tugasAktif as $t): ?>
                <a href="<?= url('penjaga/tugas.php#t' . $t['id']) ?>" class="d-flex justify-content-between border-bottom py-2 text-decoration-none text-body">
                    <span><?= e($t['judul']) ?></span><?= badge($t['status']) ?>
                </a>
            <?php endforeach; ?>
            <?php if (!$tugasAktif): ?><p class="text-muted mb-0">Tidak ada tugas aktif.</p><?php endif; ?>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Daftar penghuni</h5>
            <table class="table table-sm mb-0">
                <thead><tr><th>Kamar</th><th>Nama</th><th>No. HP</th></tr></thead>
                <?php foreach ($penghuni as $p): ?>
                    <tr><td><?= e($p['nomor_kamar']) ?></td><td><?= e($p['nama']) ?></td><td><?= e($p['no_hp']) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
