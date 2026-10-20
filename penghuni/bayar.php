<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('penghuni');

$t = q(
    "SELECT t.*, k.nomor_kamar FROM tagihan t
     JOIN sewa s ON s.id = t.sewa_id JOIN kamar k ON k.id = s.kamar_id
     WHERE t.id = ? AND s.user_id = ?",
    [(int) ($_GET['id'] ?? 0), $u['id']]
)->fetch();
if (!$t) {
    redirect('penghuni/tagihan.php');
}
if ($t['status'] !== 'belum') {
    flash('info', 'Tagihan ini sudah dibayar atau sedang diverifikasi.');
    redirect('penghuni/tagihan.php');
}

if (is_post()) {
    try {
        $bukti = upload('bukti', 'bukti', true);
        db()->beginTransaction();
        q(
            'INSERT INTO pembayaran (tagihan_id, jumlah, metode, bukti) VALUES (?, ?, ?, ?)',
            [$t['id'], (int) $_POST['jumlah'], $_POST['metode'] ?? 'Transfer Bank', $bukti]
        );
        q("UPDATE tagihan SET status = 'menunggu' WHERE id = ?", [$t['id']]);
        db()->commit();
        flash('success', 'Bukti pembayaran terkirim, menunggu verifikasi pengelola.');
        redirect('penghuni/tagihan.php');
    } catch (RuntimeException $ex) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash('danger', $ex->getMessage());
        back();
    }
}

$title = 'Bayar Tagihan';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <h1 class="h3 mb-4">Bayar Tagihan</h1>
        <div class="card shadow-sm mb-3"><div class="card-body">
            <div class="d-flex justify-content-between"><span>Periode</span><b><?= periode_label($t['periode']) ?></b></div>
            <div class="d-flex justify-content-between"><span>Kamar</span><b><?= e($t['nomor_kamar']) ?></b></div>
            <div class="d-flex justify-content-between"><span>Jatuh tempo</span><b><?= tgl($t['jatuh_tempo']) ?></b></div>
            <hr>
            <div class="d-flex justify-content-between fs-5"><span>Total</span><b class="text-primary"><?= rupiah($t['jumlah']) ?></b></div>
        </div></div>
        <div class="alert alert-info"><i class="bi bi-bank"></i> Transfer ke: <b><?= e(REKENING) ?></b></div>
        <form method="post" enctype="multipart/form-data" class="card shadow-sm"><div class="card-body">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Metode</label>
                <select name="metode" class="form-select">
                    <option>Transfer Bank</option>
                    <option>E-Wallet</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Jumlah dibayar</label>
                <input type="number" name="jumlah" class="form-control" value="<?= $t['jumlah'] ?>" min="1" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Bukti transfer (JPG/PNG/PDF, maks 2 MB)</label>
                <input type="file" name="bukti" class="form-control" accept="image/*,application/pdf" required>
            </div>
            <button class="btn btn-primary w-100">Kirim bukti pembayaran</button>
        </div></form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
