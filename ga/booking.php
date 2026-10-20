<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga');

if (is_post()) {
    $b = q("SELECT * FROM booking WHERE id = ? AND status = 'pending'", [(int) $_POST['id']])->fetch();
    if (!$b) {
        flash('danger', 'Booking tidak ditemukan atau sudah diproses.');
        back();
    }
    if (($_POST['aksi'] ?? '') === 'terima') {
        try {
            db()->beginTransaction();
            buat_sewa((int) $b['user_id'], (int) $b['kamar_id'], $b['tanggal_masuk'], (int) $b['durasi_bulan'], (int) ($_POST['deposit'] ?? 0), (int) $b['id']);
            q("UPDATE booking SET status = 'diterima' WHERE id = ?", [$b['id']]);
            // Booking lain untuk kamar yang sama otomatis ditolak
            q("UPDATE booking SET status = 'ditolak' WHERE kamar_id = ? AND status = 'pending'", [$b['kamar_id']]);
            db()->commit();
            flash('success', 'Booking diterima. Sewa & tagihan pertama sudah dibuat.');
        } catch (RuntimeException $ex) {
            db()->rollBack();
            flash('danger', $ex->getMessage());
        }
    } else {
        q("UPDATE booking SET status = 'ditolak' WHERE id = ?", [$b['id']]);
        flash('success', 'Booking ditolak.');
    }
    back();
}

$status = $_GET['status'] ?? 'pending';
$booking = q(
    "SELECT b.*, u.nama, u.no_hp, u.email, k.nomor_kamar, k.harga_per_bulan, k.status AS status_kamar
     FROM booking b JOIN users u ON u.id = b.user_id JOIN kamar k ON k.id = b.kamar_id
     WHERE ? = 'semua' OR b.status = ? ORDER BY b.id DESC",
    [$status, $status]
)->fetchAll();

$title = 'Booking';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Booking Kamar</h1>
    <div class="btn-group">
        <?php foreach (['pending', 'diterima', 'ditolak', 'semua'] as $s): ?>
            <a href="?status=<?= $s ?>" class="btn btn-sm btn-outline-primary <?= $status === $s ? 'active' : '' ?>"><?= ucfirst($s) ?></a>
        <?php endforeach; ?>
    </div>
</div>
<div class="card shadow-sm"><div class="card-body table-responsive">
    <table class="table">
        <thead><tr><th>Tanggal</th><th>Calon penghuni</th><th>Kamar</th><th>Masuk</th><th>Durasi</th><th>Catatan</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($booking as $b): ?>
            <tr>
                <td class="small"><?= tgl($b['tanggal_booking'], true) ?></td>
                <td><?= e($b['nama']) ?><div class="small text-muted"><?= e($b['no_hp']) ?> &middot; <?= e($b['email']) ?></div></td>
                <td><?= e($b['nomor_kamar']) ?><div class="small text-muted"><?= rupiah($b['harga_per_bulan']) ?></div></td>
                <td><?= tgl($b['tanggal_masuk']) ?></td>
                <td><?= $b['durasi_bulan'] ?> bln</td>
                <td class="small"><?= e($b['catatan']) ?></td>
                <td><?= badge($b['status']) ?></td>
                <td class="text-nowrap">
                    <?php if ($b['status'] === 'pending'): ?>
                        <form method="post" class="d-flex gap-1" data-confirm="Terima booking ini?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="aksi" value="terima">
                            <input type="number" name="deposit" class="form-control form-control-sm" style="width:110px" placeholder="Deposit" min="0">
                            <button class="btn btn-sm btn-success" <?= $b['status_kamar'] !== 'kosong' ? 'disabled title="Kamar sudah tidak kosong"' : '' ?>>Terima</button>
                        </form>
                        <form method="post" class="mt-1" data-confirm="Tolak booking ini?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="aksi" value="tolak">
                            <button class="btn btn-sm btn-outline-danger w-100">Tolak</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$booking): ?><tr><td colspan="8" class="text-muted">Tidak ada data.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
