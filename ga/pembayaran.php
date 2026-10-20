<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga');

if (is_post()) {
    $p = q("SELECT * FROM pembayaran WHERE id = ? AND status = 'menunggu'", [(int) $_POST['id']])->fetch();
    if ($p) {
        $valid = ($_POST['keputusan'] ?? '') === 'valid';
        db()->beginTransaction();
        q(
            'UPDATE pembayaran SET status = ?, catatan = ?, divalidasi_oleh = ? WHERE id = ?',
            [$valid ? 'valid' : 'ditolak', trim($_POST['catatan'] ?? ''), $u['id'], $p['id']]
        );
        q('UPDATE tagihan SET status = ? WHERE id = ?', [$valid ? 'lunas' : 'belum', $p['tagihan_id']]);
        db()->commit();
        flash('success', $valid ? 'Pembayaran valid, tagihan lunas.' : 'Pembayaran ditolak, penghuni perlu upload ulang.');
    }
    back();
}

$status = $_GET['status'] ?? 'menunggu';
$pembayaran = q(
    "SELECT p.*, t.periode, t.jumlah AS jumlah_tagihan, u.nama, k.nomor_kamar FROM pembayaran p
     JOIN tagihan t ON t.id = p.tagihan_id JOIN sewa s ON s.id = t.sewa_id
     JOIN users u ON u.id = s.user_id JOIN kamar k ON k.id = s.kamar_id
     WHERE ? = 'semua' OR p.status = ? ORDER BY p.tanggal_bayar DESC LIMIT 200",
    [$status, $status]
)->fetchAll();

$title = 'Pembayaran';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Validasi Pembayaran</h1>
    <div class="btn-group">
        <?php foreach (['menunggu', 'valid', 'ditolak', 'semua'] as $s): ?>
            <a href="?status=<?= $s ?>" class="btn btn-sm btn-outline-primary <?= $status === $s ? 'active' : '' ?>"><?= ucfirst($s) ?></a>
        <?php endforeach; ?>
    </div>
</div>
<div class="card shadow-sm"><div class="card-body table-responsive">
    <table class="table">
        <thead><tr><th>Tanggal</th><th>Penghuni</th><th>Periode</th><th>Dibayar</th><th>Metode</th><th>Bukti</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pembayaran as $p): ?>
            <tr>
                <td class="small"><?= tgl($p['tanggal_bayar'], true) ?></td>
                <td><?= e($p['nama']) ?> <span class="text-muted">(<?= e($p['nomor_kamar']) ?>)</span></td>
                <td><?= periode_label($p['periode']) ?></td>
                <td>
                    <?= rupiah($p['jumlah']) ?>
                    <?php if ($p['jumlah'] != $p['jumlah_tagihan']): ?><div class="small text-danger">Tagihan <?= rupiah($p['jumlah_tagihan']) ?></div><?php endif; ?>
                </td>
                <td><?= e($p['metode']) ?></td>
                <td><?= $p['bukti'] ? '<a href="' . foto_url($p['bukti']) . '" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-image"></i> Lihat</a>' : '-' ?></td>
                <td><?= badge($p['status']) ?></td>
                <td>
                    <?php if ($p['status'] === 'menunggu'): ?>
                        <form method="post" class="d-flex gap-1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input name="catatan" class="form-control form-control-sm" placeholder="Catatan" style="width:130px">
                            <button name="keputusan" value="valid" class="btn btn-sm btn-success">Valid</button>
                            <button name="keputusan" value="tolak" class="btn btn-sm btn-outline-danger">Tolak</button>
                        </form>
                    <?php else: ?>
                        <span class="small text-muted"><?= e($p['catatan']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$pembayaran): ?><tr><td colspan="8" class="text-muted">Tidak ada data.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
