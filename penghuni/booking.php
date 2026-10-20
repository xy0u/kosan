<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('penghuni');

if (is_post()) {
    if (($_POST['aksi'] ?? '') === 'batal') {
        q("UPDATE booking SET status = 'batal' WHERE id = ? AND user_id = ? AND status = 'pending'", [(int) $_POST['id'], $u['id']]);
        flash('success', 'Booking dibatalkan.');
        back();
    }

    $kamarId = (int) ($_POST['kamar_id'] ?? 0);
    $masuk = $_POST['tanggal_masuk'] ?? '';
    $durasi = (int) ($_POST['durasi_bulan'] ?? 0);
    $kamar = q("SELECT * FROM kamar WHERE id = ? AND status = 'kosong'", [$kamarId])->fetch();

    if (sewa_aktif($u['id'])) {
        flash('danger', 'Kamu masih punya sewa aktif.');
    } elseif (q("SELECT 1 FROM booking WHERE user_id = ? AND status = 'pending'", [$u['id']])->fetch()) {
        flash('danger', 'Kamu masih punya booking yang menunggu persetujuan.');
    } elseif (!$kamar) {
        flash('danger', 'Kamar tidak tersedia.');
    } elseif ($masuk < date('Y-m-d')) {
        flash('danger', 'Tanggal masuk tidak boleh sebelum hari ini.');
    } elseif ($durasi < 1 || $durasi > 24) {
        flash('danger', 'Durasi sewa 1 - 24 bulan.');
    } else {
        q(
            'INSERT INTO booking (user_id, kamar_id, tanggal_masuk, durasi_bulan, catatan) VALUES (?, ?, ?, ?, ?)',
            [$u['id'], $kamarId, $masuk, $durasi, trim($_POST['catatan'] ?? '')]
        );
        flash('success', 'Booking terkirim! Pengelola akan segera mengonfirmasi.');
    }
    back();
}

$kamarKosong = q("SELECT * FROM kamar WHERE status = 'kosong' ORDER BY nomor_kamar")->fetchAll();
$pilih = (int) ($_GET['kamar_id'] ?? 0);
$riwayat = q(
    "SELECT b.*, k.nomor_kamar, k.harga_per_bulan FROM booking b JOIN kamar k ON k.id = b.kamar_id
     WHERE b.user_id = ? ORDER BY b.id DESC",
    [$u['id']]
)->fetchAll();

$title = 'Booking';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Booking Kamar</h1>
<div class="row g-4">
    <div class="col-lg-5">
        <form method="post" class="card shadow-sm"><div class="card-body">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Kamar</label>
                <select name="kamar_id" class="form-select" required>
                    <option value="">-- pilih kamar --</option>
                    <?php foreach ($kamarKosong as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $k['id'] == $pilih ? 'selected' : '' ?>>
                            <?= e($k['nomor_kamar']) ?> - <?= e($k['tipe']) ?> (<?= rupiah($k['harga_per_bulan']) ?>/bln)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="row">
                <div class="col-7 mb-3">
                    <label class="form-label">Tanggal masuk</label>
                    <input type="date" name="tanggal_masuk" class="form-control" min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-5 mb-3">
                    <label class="form-label">Durasi (bulan)</label>
                    <input type="number" name="durasi_bulan" class="form-control" min="1" max="24" value="1" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Catatan</label>
                <textarea name="catatan" class="form-control" rows="2" placeholder="Misal: mahasiswa, bawa motor, dll"></textarea>
            </div>
            <button class="btn btn-primary w-100">Kirim booking</button>
        </div></form>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Riwayat booking</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Kamar</th><th>Masuk</th><th>Durasi</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($riwayat as $b): ?>
                        <tr>
                            <td><?= e($b['nomor_kamar']) ?></td>
                            <td><?= tgl($b['tanggal_masuk']) ?></td>
                            <td><?= $b['durasi_bulan'] ?> bln</td>
                            <td><?= badge($b['status']) ?></td>
                            <td class="text-end">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="post" data-confirm="Batalkan booking ini?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="aksi" value="batal">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger">Batal</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$riwayat): ?><tr><td colspan="5" class="text-muted">Belum ada booking.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
