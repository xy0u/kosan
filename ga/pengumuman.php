<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga', 'owner');

if (is_post()) {
    if (($_POST['aksi'] ?? '') === 'hapus') {
        q('DELETE FROM pengumuman WHERE id = ?', [(int) $_POST['id']]);
        flash('success', 'Pengumuman dihapus.');
    } elseif (trim($_POST['judul'] ?? '') !== '' && trim($_POST['isi'] ?? '') !== '') {
        q('INSERT INTO pengumuman (judul, isi, dibuat_oleh) VALUES (?, ?, ?)', [trim($_POST['judul']), trim($_POST['isi']), $u['id']]);
        flash('success', 'Pengumuman diterbitkan, penghuni bisa melihatnya di dashboard.');
    }
    back();
}

$pengumuman = q('SELECT p.*, u.nama FROM pengumuman p LEFT JOIN users u ON u.id = p.dibuat_oleh ORDER BY p.id DESC')->fetchAll();

$title = 'Pengumuman';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Pengumuman</h1>
<div class="row g-4">
    <div class="col-lg-5">
        <form method="post" class="card shadow-sm"><div class="card-body">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label">Judul</label><input name="judul" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Isi</label><textarea name="isi" class="form-control" rows="5" required></textarea></div>
            <button class="btn btn-primary w-100"><i class="bi bi-megaphone"></i> Terbitkan</button>
        </div></form>
    </div>
    <div class="col-lg-7">
        <?php foreach ($pengumuman as $p): ?>
            <div class="card shadow-sm mb-3"><div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-1"><?= e($p['judul']) ?></h6>
                    <form method="post" data-confirm="Hapus pengumuman ini?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
                <div class="small text-muted mb-2"><?= e($p['nama'] ?? '-') ?> &middot; <?= tgl($p['created_at'], true) ?></div>
                <p class="mb-0"><?= nl2br(e($p['isi'])) ?></p>
            </div></div>
        <?php endforeach; ?>
        <?php if (!$pengumuman): ?><p class="text-muted">Belum ada pengumuman.</p><?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
