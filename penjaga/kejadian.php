<?php
require_once __DIR__ . '/../includes/functions.php';

// Penjaga melapor; GA & owner membaca
$u = require_role('penjaga', 'ga', 'owner');
$bolehLapor = $u['role'] === 'penjaga';

if (is_post() && $bolehLapor) {
    try {
        $judul = trim($_POST['judul'] ?? '');
        $isi = trim($_POST['isi'] ?? '');
        if ($judul === '' || $isi === '') {
            throw new RuntimeException('Judul dan isi laporan wajib diisi.');
        }
        q(
            'INSERT INTO laporan_kejadian (penjaga_id, judul, isi, foto) VALUES (?, ?, ?, ?)',
            [$u['id'], $judul, $isi, upload('foto', 'kejadian')]
        );
        flash('success', 'Laporan terkirim ke pengelola.');
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
    }
    back();
}

$laporan = q(
    'SELECT l.*, u.nama FROM laporan_kejadian l JOIN users u ON u.id = l.penjaga_id ORDER BY l.id DESC LIMIT 100'
)->fetchAll();

$title = 'Laporan Kejadian';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Laporan Kejadian</h1>
<div class="row g-4">
    <?php if ($bolehLapor): ?>
        <div class="col-lg-5">
            <form method="post" enctype="multipart/form-data" class="card shadow-sm"><div class="card-body">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label">Judul</label><input name="judul" class="form-control" placeholder="Contoh: Pipa bocor di lantai 2" required></div>
                <div class="mb-3"><label class="form-label">Kronologi</label><textarea name="isi" class="form-control" rows="4" required></textarea></div>
                <div class="mb-3"><label class="form-label">Foto (opsional)</label><input type="file" name="foto" class="form-control" accept="image/*"></div>
                <button class="btn btn-danger w-100">Kirim laporan</button>
            </div></form>
        </div>
    <?php endif; ?>
    <div class="<?= $bolehLapor ? 'col-lg-7' : 'col-12' ?>">
        <?php foreach ($laporan as $l): ?>
            <div class="card shadow-sm mb-3"><div class="card-body">
                <h6 class="mb-1"><?= e($l['judul']) ?></h6>
                <div class="small text-muted mb-2"><?= e($l['nama']) ?> &middot; <?= tgl($l['created_at'], true) ?></div>
                <p class="mb-1"><?= nl2br(e($l['isi'])) ?></p>
                <?php if ($l['foto']): ?><a href="<?= foto_url($l['foto']) ?>" target="_blank" class="small">Lihat foto</a><?php endif; ?>
            </div></div>
        <?php endforeach; ?>
        <?php if (!$laporan): ?><p class="text-muted">Belum ada laporan.</p><?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
