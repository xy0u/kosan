<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga', 'owner');

const KATEGORI = ['Listrik', 'Air', 'Internet', 'Perbaikan', 'Kebersihan', 'Gaji', 'Lainnya'];
$bulan = preg_match('/^\d{4}-\d{2}$/', $_GET['bulan'] ?? '') ? $_GET['bulan'] : date('Y-m');

if (is_post()) {
    if (($_POST['aksi'] ?? '') === 'hapus') {
        q('DELETE FROM pengeluaran WHERE id = ?', [(int) $_POST['id']]);
        flash('success', 'Pengeluaran dihapus.');
    } elseif ((int) ($_POST['jumlah'] ?? 0) > 0 && in_array($_POST['kategori'] ?? '', KATEGORI, true)) {
        q(
            'INSERT INTO pengeluaran (tanggal, kategori, keterangan, jumlah, dicatat_oleh) VALUES (?, ?, ?, ?, ?)',
            [$_POST['tanggal'] ?: date('Y-m-d'), $_POST['kategori'], trim($_POST['keterangan'] ?? ''), (int) $_POST['jumlah'], $u['id']]
        );
        flash('success', 'Pengeluaran dicatat.');
    } else {
        flash('danger', 'Kategori dan jumlah wajib diisi.');
    }
    back();
}

$data = q(
    "SELECT p.*, u.nama FROM pengeluaran p LEFT JOIN users u ON u.id = p.dicatat_oleh
     WHERE DATE_FORMAT(p.tanggal, '%Y-%m') = ? ORDER BY p.tanggal DESC, p.id DESC",
    [$bulan]
)->fetchAll();
$perKategori = [];
foreach ($data as $d) {
    $perKategori[$d['kategori']] = ($perKategori[$d['kategori']] ?? 0) + $d['jumlah'];
}
arsort($perKategori);

$title = 'Pengeluaran';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Pengeluaran <?= periode_label($bulan) ?></h1>
    <form><input type="month" name="bulan" class="form-control" value="<?= $bulan ?>" onchange="this.form.submit()"></form>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <form method="post" class="card shadow-sm mb-4"><div class="card-body">
            <?= csrf_field() ?>
            <h5>Catat pengeluaran</h5>
            <div class="mb-2"><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>"></div>
            <div class="mb-2">
                <select name="kategori" class="form-select"><?php foreach (KATEGORI as $k): ?><option><?= $k ?></option><?php endforeach; ?></select>
            </div>
            <div class="mb-2"><input name="keterangan" class="form-control" placeholder="Keterangan"></div>
            <div class="mb-3"><input type="number" name="jumlah" class="form-control" placeholder="Jumlah (Rp)" min="1" required></div>
            <button class="btn btn-primary w-100">Simpan</button>
        </div></form>
        <div class="card shadow-sm"><div class="card-body">
            <h6>Per kategori</h6>
            <?php foreach ($perKategori as $kat => $jml): ?>
                <div class="d-flex justify-content-between small border-bottom py-1"><span><?= e($kat) ?></span><b><?= rupiah($jml) ?></b></div>
            <?php endforeach; ?>
            <div class="d-flex justify-content-between pt-2"><span>Total</span><b class="text-danger"><?= rupiah(array_sum($perKategori)) ?></b></div>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm"><div class="card-body table-responsive">
            <table class="table">
                <thead><tr><th>Tanggal</th><th>Kategori</th><th>Keterangan</th><th class="text-end">Jumlah</th><th>Dicatat</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($data as $d): ?>
                    <tr>
                        <td><?= tgl($d['tanggal']) ?></td>
                        <td><?= e($d['kategori']) ?></td>
                        <td><?= e($d['keterangan']) ?></td>
                        <td class="text-end"><?= rupiah($d['jumlah']) ?></td>
                        <td class="small text-muted"><?= e($d['nama'] ?? '-') ?></td>
                        <td>
                            <form method="post" data-confirm="Hapus pengeluaran ini?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$data): ?><tr><td colspan="6" class="text-muted">Belum ada pengeluaran bulan ini.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
