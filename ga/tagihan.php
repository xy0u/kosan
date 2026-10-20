<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga');

$periode = preg_match('/^\d{4}-\d{2}$/', $_GET['periode'] ?? '') ? $_GET['periode'] : date('Y-m');

if (is_post()) {
    if (($_POST['aksi'] ?? '') === 'generate') {
        $n = generate_tagihan($periode);
        flash('success', $n ? "$n tagihan baru dibuat untuk " . periode_label($periode) . '.' : 'Semua tagihan periode ini sudah ada.');
    } elseif (($_POST['aksi'] ?? '') === 'tunai') {
        $t = q("SELECT * FROM tagihan WHERE id = ? AND status <> 'lunas'", [(int) $_POST['id']])->fetch();
        if ($t) {
            db()->beginTransaction();
            q("INSERT INTO pembayaran (tagihan_id, jumlah, metode, status, divalidasi_oleh) VALUES (?, ?, 'Tunai', 'valid', ?)", [$t['id'], $t['jumlah'], $u['id']]);
            q("UPDATE tagihan SET status = 'lunas' WHERE id = ?", [$t['id']]);
            db()->commit();
            flash('success', 'Tagihan ditandai lunas (tunai).');
        }
    }
    back();
}

$tagihan = q(
    "SELECT t.*, u.nama, u.no_hp, k.nomor_kamar FROM tagihan t
     JOIN sewa s ON s.id = t.sewa_id JOIN users u ON u.id = s.user_id JOIN kamar k ON k.id = s.kamar_id
     WHERE t.periode = ? ORDER BY k.nomor_kamar",
    [$periode]
)->fetchAll();
$total = array_sum(array_column($tagihan, 'jumlah'));
$lunas = array_sum(array_map(fn($t) => $t['status'] === 'lunas' ? $t['jumlah'] : 0, $tagihan));

$title = 'Tagihan';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Tagihan <?= periode_label($periode) ?></h1>
    <div class="d-flex flex-wrap gap-2">
        <form><input type="month" name="periode" class="form-control" value="<?= $periode ?>" onchange="this.form.submit()"></form>
        <form method="post" data-confirm="Buat tagihan <?= periode_label($periode) ?> untuk semua sewa aktif?">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="generate">
            <button class="btn btn-primary text-nowrap"><i class="bi bi-lightning"></i> Generate tagihan</button>
        </form>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Total tagihan</div><div class="fs-4 fw-bold"><?= rupiah($total) ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Sudah lunas</div><div class="fs-4 fw-bold text-success"><?= rupiah($lunas) ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="text-muted small">Belum masuk</div><div class="fs-4 fw-bold text-danger"><?= rupiah($total - $lunas) ?></div></div></div></div>
</div>
<div class="card shadow-sm"><div class="card-body table-responsive">
    <table class="table">
        <thead><tr><th>Kamar</th><th>Penghuni</th><th>Jumlah</th><th>Jatuh tempo</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tagihan as $t): ?>
            <tr>
                <td class="fw-bold"><?= e($t['nomor_kamar']) ?></td>
                <td><?= e($t['nama']) ?><div class="small text-muted"><?= e($t['no_hp']) ?></div></td>
                <td><?= rupiah($t['jumlah']) ?></td>
                <td class="<?= $t['status'] !== 'lunas' && $t['jatuh_tempo'] < date('Y-m-d') ? 'text-danger fw-bold' : '' ?>"><?= tgl($t['jatuh_tempo']) ?></td>
                <td><?= badge($t['status']) ?></td>
                <td class="text-end text-nowrap">
                    <?php if ($t['status'] === 'menunggu'): ?>
                        <a href="<?= url('ga/pembayaran.php') ?>" class="btn btn-sm btn-warning">Cek bukti</a>
                    <?php elseif ($t['status'] === 'belum'): ?>
                        <?php $wa = preg_replace('/^0/', '62', preg_replace('/\D/', '', $t['no_hp'])); ?>
                        <a target="_blank" rel="noopener" class="btn btn-sm btn-outline-success" title="Ingatkan via WhatsApp"
                           href="https://wa.me/<?= $wa ?>?text=<?= rawurlencode("Halo {$t['nama']}, mengingatkan tagihan kos kamar {$t['nomor_kamar']} periode " . periode_label($t['periode']) . ' sebesar ' . rupiah($t['jumlah']) . '. Terima kasih.') ?>"><i class="bi bi-whatsapp"></i></a>
                        <form method="post" class="d-inline" data-confirm="Tandai lunas (bayar tunai)?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="tunai">
                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                            <button class="btn btn-sm btn-outline-primary">Lunas tunai</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$tagihan): ?><tr><td colspan="6" class="text-muted">Belum ada tagihan periode ini. Klik "Generate tagihan".</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
