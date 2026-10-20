<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('penghuni');
$sewa = sewa_aktif($u['id']);

if (is_post() && $sewa) {
    try {
        $judul = trim($_POST['judul'] ?? '');
        $isi = trim($_POST['isi'] ?? '');
        if ($judul === '' || $isi === '') {
            throw new RuntimeException('Judul dan isi keluhan wajib diisi.');
        }
        $foto = upload('foto', 'keluhan');
        q(
            'INSERT INTO keluhan (user_id, kamar_id, judul, isi, foto) VALUES (?, ?, ?, ?, ?)',
            [$u['id'], $sewa['kamar_id'], $judul, $isi, $foto]
        );
        flash('success', 'Keluhan terkirim. Pengelola akan menindaklanjuti.');
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
    }
    back();
}

$keluhan = q(
    "SELECT kl.*, k.nomor_kamar FROM keluhan kl JOIN kamar k ON k.id = kl.kamar_id
     WHERE kl.user_id = ? ORDER BY kl.id DESC",
    [$u['id']]
)->fetchAll();

$title = 'Keluhan';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Keluhan & Permintaan Perbaikan</h1>
<div class="row g-4">
    <div class="col-lg-5">
        <?php if ($sewa): ?>
            <form method="post" enctype="multipart/form-data" class="card shadow-sm"><div class="card-body">
                <?= csrf_field() ?>
                <p class="text-muted small">Kamar <?= e($sewa['nomor_kamar']) ?></p>
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input name="judul" class="form-control" placeholder="Contoh: AC tidak dingin" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Detail</label>
                    <textarea name="isi" class="form-control" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Foto (opsional)</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
                <button class="btn btn-primary w-100">Kirim keluhan</button>
            </div></form>
        <?php else: ?>
            <div class="alert alert-info">Fitur keluhan tersedia setelah kamu punya sewa aktif.</div>
        <?php endif; ?>
    </div>
    <div class="col-lg-7">
        <?php foreach ($keluhan as $k): ?>
            <div class="card shadow-sm mb-3"><div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-1"><?= e($k['judul']) ?></h6>
                    <?= badge($k['status']) ?>
                </div>
                <div class="small text-muted mb-2">Kamar <?= e($k['nomor_kamar']) ?> &middot; <?= tgl($k['created_at'], true) ?></div>
                <p class="mb-1"><?= nl2br(e($k['isi'])) ?></p>
                <?php if ($k['foto']): ?><a href="<?= foto_url($k['foto']) ?>" target="_blank" class="small">Lihat foto</a><?php endif; ?>
            </div></div>
        <?php endforeach; ?>
        <?php if (!$keluhan): ?><p class="text-muted">Belum ada keluhan.</p><?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
