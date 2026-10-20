<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga');

if (is_post()) {
    $aksi = $_POST['aksi'] ?? '';
    $penjagaId = (int) ($_POST['penjaga_id'] ?? 0);
    if ($aksi === 'tugaskan') {
        $k = q("SELECT * FROM keluhan WHERE id = ? AND status = 'baru'", [(int) $_POST['id']])->fetch();
        if ($k && $penjagaId) {
            db()->beginTransaction();
            q(
                'INSERT INTO tugas_penjaga (keluhan_id, penjaga_id, ditugaskan_oleh, judul, deskripsi) VALUES (?, ?, ?, ?, ?)',
                [$k['id'], $penjagaId, $u['id'], $k['judul'], trim($k['isi'] . "\n\nCatatan GA: " . ($_POST['catatan'] ?? ''))]
            );
            q("UPDATE keluhan SET status = 'diproses' WHERE id = ?", [$k['id']]);
            db()->commit();
            flash('success', 'Keluhan diteruskan ke penjaga.');
        }
    } elseif ($aksi === 'selesai') {
        q("UPDATE keluhan SET status = 'selesai' WHERE id = ?", [(int) $_POST['id']]);
        flash('success', 'Keluhan ditandai selesai.');
    } elseif ($aksi === 'tugas_baru' && $penjagaId && trim($_POST['judul'] ?? '') !== '') {
        q(
            'INSERT INTO tugas_penjaga (penjaga_id, ditugaskan_oleh, judul, deskripsi) VALUES (?, ?, ?, ?)',
            [$penjagaId, $u['id'], trim($_POST['judul']), trim($_POST['deskripsi'] ?? '')]
        );
        flash('success', 'Tugas dikirim ke penjaga.');
    }
    back();
}

$keluhan = q(
    "SELECT kl.*, u.nama, k.nomor_kamar FROM keluhan kl
     JOIN users u ON u.id = kl.user_id JOIN kamar k ON k.id = kl.kamar_id
     ORDER BY FIELD(kl.status, 'baru', 'diproses', 'selesai'), kl.id DESC LIMIT 100"
)->fetchAll();
$tugas = q(
    "SELECT t.*, p.nama AS penjaga FROM tugas_penjaga t JOIN users p ON p.id = t.penjaga_id
     ORDER BY t.status = 'selesai', t.id DESC LIMIT 50"
)->fetchAll();
$penjaga = q("SELECT id, nama FROM users WHERE role = 'penjaga' AND aktif = 1 ORDER BY nama")->fetchAll();

$title = 'Keluhan & Tugas';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Keluhan & Tugas Penjaga</h1>
<div class="row g-4">
    <div class="col-lg-7">
        <h5>Keluhan penghuni</h5>
        <?php foreach ($keluhan as $k): ?>
            <div class="card shadow-sm mb-3"><div class="card-body">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-1"><?= e($k['judul']) ?></h6><?= badge($k['status']) ?>
                </div>
                <div class="small text-muted mb-2"><?= e($k['nama']) ?> &middot; Kamar <?= e($k['nomor_kamar']) ?> &middot; <?= tgl($k['created_at'], true) ?></div>
                <p class="mb-2"><?= nl2br(e($k['isi'])) ?></p>
                <?php if ($k['foto']): ?><p><a href="<?= foto_url($k['foto']) ?>" target="_blank" class="small">Lihat foto</a></p><?php endif; ?>
                <?php if ($k['status'] === 'baru'): ?>
                    <form method="post" class="row g-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="tugaskan">
                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <div class="col-md-4">
                            <select name="penjaga_id" class="form-select form-select-sm" required>
                                <option value="">-- penjaga --</option>
                                <?php foreach ($penjaga as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nama']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5"><input name="catatan" class="form-control form-control-sm" placeholder="Catatan untuk penjaga"></div>
                        <div class="col-md-3"><button class="btn btn-sm btn-primary w-100">Tugaskan</button></div>
                    </form>
                <?php elseif ($k['status'] === 'diproses'): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="selesai">
                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <button class="btn btn-sm btn-outline-success">Tandai selesai</button>
                    </form>
                <?php endif; ?>
            </div></div>
        <?php endforeach; ?>
        <?php if (!$keluhan): ?><p class="text-muted">Belum ada keluhan.</p><?php endif; ?>
    </div>
    <div class="col-lg-5">
        <form method="post" class="card shadow-sm mb-4"><div class="card-body">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tugas_baru">
            <h5>Beri tugas langsung</h5>
            <div class="mb-2">
                <select name="penjaga_id" class="form-select" required>
                    <option value="">-- penjaga --</option>
                    <?php foreach ($penjaga as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['nama']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2"><input name="judul" class="form-control" placeholder="Contoh: Bersihkan selokan depan" required></div>
            <div class="mb-3"><textarea name="deskripsi" class="form-control" rows="2" placeholder="Detail"></textarea></div>
            <button class="btn btn-primary w-100">Kirim tugas</button>
        </div></form>
        <div class="card shadow-sm"><div class="card-body">
            <h5>Status tugas</h5>
            <?php foreach ($tugas as $t): ?>
                <div class="border-bottom py-2">
                    <div class="d-flex justify-content-between"><span><?= e($t['judul']) ?></span><?= badge($t['status']) ?></div>
                    <div class="small text-muted"><?= e($t['penjaga']) ?> &middot; <?= tgl($t['created_at']) ?>
                        <?php if ($t['foto_hasil']): ?> &middot; <a href="<?= foto_url($t['foto_hasil']) ?>" target="_blank">foto hasil</a><?php endif; ?>
                    </div>
                    <?php if ($t['catatan_penjaga']): ?><div class="small">“<?= e($t['catatan_penjaga']) ?>”</div><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$tugas): ?><p class="text-muted mb-0">Belum ada tugas.</p><?php endif; ?>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
