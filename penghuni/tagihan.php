<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('penghuni');

$tagihan = q(
    "SELECT t.*, k.nomor_kamar FROM tagihan t
     JOIN sewa s ON s.id = t.sewa_id JOIN kamar k ON k.id = s.kamar_id
     WHERE s.user_id = ? ORDER BY t.periode DESC",
    [$u['id']]
)->fetchAll();
$pembayaran = q(
    "SELECT p.*, t.periode FROM pembayaran p
     JOIN tagihan t ON t.id = p.tagihan_id JOIN sewa s ON s.id = t.sewa_id
     WHERE s.user_id = ? ORDER BY p.tanggal_bayar DESC",
    [$u['id']]
)->fetchAll();

$title = 'Tagihan';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Tagihan & Pembayaran</h1>
<div class="card shadow-sm mb-4"><div class="card-body">
    <h5>Tagihan</h5>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Periode</th><th>Kamar</th><th>Jumlah</th><th>Jatuh tempo</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($tagihan as $t): ?>
                <tr>
                    <td><?= periode_label($t['periode']) ?></td>
                    <td><?= e($t['nomor_kamar']) ?></td>
                    <td><?= rupiah($t['jumlah']) ?></td>
                    <td class="<?= $t['status'] !== 'lunas' && $t['jatuh_tempo'] < date('Y-m-d') ? 'text-danger fw-bold' : '' ?>"><?= tgl($t['jatuh_tempo']) ?></td>
                    <td><?= badge($t['status']) ?></td>
                    <td class="text-end">
                        <?php if ($t['status'] === 'belum'): ?>
                            <a href="<?= url('penghuni/bayar.php?id=' . $t['id']) ?>" class="btn btn-sm btn-primary">Bayar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$tagihan): ?><tr><td colspan="6" class="text-muted">Belum ada tagihan.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div></div>

<div class="card shadow-sm"><div class="card-body">
    <h5>Riwayat pembayaran</h5>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Tanggal</th><th>Periode</th><th>Jumlah</th><th>Metode</th><th>Bukti</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($pembayaran as $p): ?>
                <tr>
                    <td><?= tgl($p['tanggal_bayar'], true) ?></td>
                    <td><?= periode_label($p['periode']) ?></td>
                    <td><?= rupiah($p['jumlah']) ?></td>
                    <td><?= e($p['metode']) ?></td>
                    <td><?= $p['bukti'] ? '<a href="' . foto_url($p['bukti']) . '" target="_blank">Lihat</a>' : '-' ?></td>
                    <td><?= badge($p['status']) ?><?= $p['catatan'] ? '<div class="small text-muted">' . e($p['catatan']) . '</div>' : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pembayaran): ?><tr><td colspan="6" class="text-muted">Belum ada pembayaran.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
