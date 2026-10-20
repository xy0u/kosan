<?php
require_once __DIR__ . '/../includes/functions.php';

// Penjaga mencatat; GA & owner hanya melihat
$u = require_role('penjaga', 'ga', 'owner');
$bolehCatat = $u['role'] === 'penjaga';

if (is_post() && $bolehCatat) {
    if (($_POST['aksi'] ?? '') === 'keluar') {
        q('UPDATE tamu SET jam_keluar = NOW() WHERE id = ? AND jam_keluar IS NULL', [(int) $_POST['id']]);
        flash('success', 'Tamu sudah keluar.');
    } elseif (trim($_POST['nama_tamu'] ?? '') !== '') {
        q(
            'INSERT INTO tamu (nama_tamu, no_hp, kamar_id, keperluan, dicatat_oleh) VALUES (?, ?, ?, ?, ?)',
            [trim($_POST['nama_tamu']), trim($_POST['no_hp'] ?? ''), ((int) $_POST['kamar_id']) ?: null, trim($_POST['keperluan'] ?? ''), $u['id']]
        );
        flash('success', 'Tamu dicatat.');
    }
    back();
}

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$tamu = q(
    "SELECT t.*, k.nomor_kamar FROM tamu t LEFT JOIN kamar k ON k.id = t.kamar_id
     WHERE DATE(t.jam_masuk) = ? OR t.jam_keluar IS NULL ORDER BY t.jam_keluar IS NULL DESC, t.jam_masuk DESC",
    [$tanggal]
)->fetchAll();
$kamarTerisi = q("SELECT id, nomor_kamar FROM kamar WHERE status = 'terisi' ORDER BY nomor_kamar")->fetchAll();

$title = 'Buku Tamu';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Buku Tamu</h1>
    <form class="d-flex gap-2">
        <input type="date" name="tanggal" class="form-control" value="<?= e($tanggal) ?>" onchange="this.form.submit()">
    </form>
</div>
<div class="row g-4">
    <?php if ($bolehCatat): ?>
        <div class="col-lg-4">
            <form method="post" class="card shadow-sm"><div class="card-body">
                <?= csrf_field() ?>
                <h5>Tamu masuk</h5>
                <div class="mb-2"><input name="nama_tamu" class="form-control" placeholder="Nama tamu" required></div>
                <div class="mb-2"><input name="no_hp" class="form-control" placeholder="No. HP"></div>
                <div class="mb-2">
                    <select name="kamar_id" class="form-select">
                        <option value="0">-- tujuan kamar --</option>
                        <?php foreach ($kamarTerisi as $k): ?><option value="<?= $k['id'] ?>"><?= e($k['nomor_kamar']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><input name="keperluan" class="form-control" placeholder="Keperluan"></div>
                <button class="btn btn-primary w-100">Catat</button>
            </div></form>
        </div>
    <?php endif; ?>
    <div class="<?= $bolehCatat ? 'col-lg-8' : 'col-12' ?>">
        <div class="card shadow-sm"><div class="card-body table-responsive">
            <table class="table">
                <thead><tr><th>Nama</th><th>Kamar</th><th>Keperluan</th><th>Masuk</th><th>Keluar</th></tr></thead>
                <tbody>
                <?php foreach ($tamu as $t): ?>
                    <tr>
                        <td><?= e($t['nama_tamu']) ?><div class="small text-muted"><?= e($t['no_hp']) ?></div></td>
                        <td><?= e($t['nomor_kamar'] ?? '-') ?></td>
                        <td><?= e($t['keperluan']) ?></td>
                        <td><?= tgl($t['jam_masuk'], true) ?></td>
                        <td>
                            <?php if ($t['jam_keluar']): ?>
                                <?= tgl($t['jam_keluar'], true) ?>
                            <?php elseif ($bolehCatat): ?>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="aksi" value="keluar">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success">Keluar</button>
                                </form>
                            <?php else: ?>
                                <span class="badge text-bg-warning">Di dalam</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$tamu): ?><tr><td colspan="5" class="text-muted">Tidak ada tamu.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
