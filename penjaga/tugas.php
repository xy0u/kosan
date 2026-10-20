<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('penjaga');

if (is_post()) {
    $t = q('SELECT * FROM tugas_penjaga WHERE id = ? AND penjaga_id = ?', [(int) $_POST['id'], $u['id']])->fetch();
    $status = $_POST['status'] ?? '';
    if ($t && in_array($status, ['diproses', 'selesai'], true)) {
        try {
            $foto = upload('foto_hasil', 'tugas') ?? $t['foto_hasil'];
            db()->beginTransaction();
            q(
                'UPDATE tugas_penjaga SET status = ?, foto_hasil = ?, catatan_penjaga = ?, selesai_at = ? WHERE id = ?',
                [$status, $foto, trim($_POST['catatan'] ?? ''), $status === 'selesai' ? date('Y-m-d H:i:s') : null, $t['id']]
            );
            // Keluhan penghuni ikut berubah status
            if ($t['keluhan_id']) {
                q('UPDATE keluhan SET status = ? WHERE id = ?', [$status, $t['keluhan_id']]);
            }
            db()->commit();
            flash('success', 'Status tugas diperbarui.');
        } catch (RuntimeException $ex) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            flash('danger', $ex->getMessage());
        }
    }
    back();
}

$tugas = q(
    "SELECT t.*, k.nomor_kamar FROM tugas_penjaga t
     LEFT JOIN keluhan kl ON kl.id = t.keluhan_id LEFT JOIN kamar k ON k.id = kl.kamar_id
     WHERE t.penjaga_id = ? ORDER BY t.status = 'selesai', t.id DESC",
    [$u['id']]
)->fetchAll();

$title = 'Tugas';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Tugas Saya</h1>
<?php foreach ($tugas as $t): ?>
    <div class="card shadow-sm mb-3" id="t<?= $t['id'] ?>"><div class="card-body">
        <div class="d-flex justify-content-between">
            <h5 class="mb-1"><?= e($t['judul']) ?></h5>
            <?= badge($t['status']) ?>
        </div>
        <div class="small text-muted mb-2">
            <?= $t['nomor_kamar'] ? 'Kamar ' . e($t['nomor_kamar']) . ' &middot; ' : '' ?>Diberikan <?= tgl($t['created_at'], true) ?>
            <?= $t['selesai_at'] ? ' &middot; Selesai ' . tgl($t['selesai_at'], true) : '' ?>
        </div>
        <p><?= nl2br(e($t['deskripsi'])) ?></p>
        <?php if ($t['foto_hasil']): ?><p><a href="<?= foto_url($t['foto_hasil']) ?>" target="_blank">Foto hasil</a></p><?php endif; ?>
        <?php if ($t['status'] !== 'selesai'): ?>
            <form method="post" enctype="multipart/form-data" class="row g-2">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                <div class="col-md-4"><input name="catatan" class="form-control" placeholder="Catatan" value="<?= e($t['catatan_penjaga']) ?>"></div>
                <div class="col-md-4"><input type="file" name="foto_hasil" class="form-control" accept="image/*"></div>
                <div class="col-6 col-md-2"><button name="status" value="diproses" class="btn btn-outline-primary w-100">Proses</button></div>
                <div class="col-6 col-md-2"><button name="status" value="selesai" class="btn btn-success w-100">Selesai</button></div>
            </form>
        <?php elseif ($t['catatan_penjaga']): ?>
            <p class="small mb-0"><b>Catatan:</b> <?= e($t['catatan_penjaga']) ?></p>
        <?php endif; ?>
    </div></div>
<?php endforeach; ?>
<?php if (!$tugas): ?><p class="text-muted">Belum ada tugas.</p><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
