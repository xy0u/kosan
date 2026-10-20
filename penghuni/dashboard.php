<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('penghuni');
$sewa = sewa_aktif($u['id']);

// Pengajuan perpanjang / berhenti
if (is_post() && $sewa) {
    $jenis = $_POST['jenis'] ?? '';
    $pending = q("SELECT 1 FROM pengajuan WHERE sewa_id = ? AND status = 'pending'", [$sewa['id']])->fetch();
    if ($pending) {
        flash('warning', 'Kamu masih punya pengajuan yang belum diproses.');
    } elseif ($jenis === 'perpanjang' && (int) $_POST['durasi_bulan'] >= 1) {
        q(
            "INSERT INTO pengajuan (sewa_id, jenis, durasi_bulan, catatan) VALUES (?, 'perpanjang', ?, ?)",
            [$sewa['id'], (int) $_POST['durasi_bulan'], trim($_POST['catatan'] ?? '')]
        );
        flash('success', 'Pengajuan perpanjangan terkirim ke pengelola.');
    } elseif ($jenis === 'berhenti' && !empty($_POST['tanggal_rencana'])) {
        q(
            "INSERT INTO pengajuan (sewa_id, jenis, tanggal_rencana, catatan) VALUES (?, 'berhenti', ?, ?)",
            [$sewa['id'], $_POST['tanggal_rencana'], trim($_POST['catatan'] ?? '')]
        );
        flash('success', 'Pengajuan berhenti ngekos terkirim ke pengelola.');
    } else {
        flash('danger', 'Lengkapi data pengajuan.');
    }
    back();
}

$tagihan = q(
    "SELECT t.* FROM tagihan t JOIN sewa s ON s.id = t.sewa_id
     WHERE s.user_id = ? AND t.status <> 'lunas' ORDER BY t.periode",
    [$u['id']]
)->fetchAll();
$booking = q(
    "SELECT b.*, k.nomor_kamar FROM booking b JOIN kamar k ON k.id = b.kamar_id
     WHERE b.user_id = ? ORDER BY b.id DESC LIMIT 1",
    [$u['id']]
)->fetch();
$pengajuan = $sewa ? q('SELECT * FROM pengajuan WHERE sewa_id = ? ORDER BY id DESC LIMIT 3', [$sewa['id']])->fetchAll() : [];
$pengumuman = q('SELECT * FROM pengumuman ORDER BY id DESC LIMIT 5')->fetchAll();

$title = 'Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<?= welcome_banner('Selamat datang, ' . $u['nama'], $sewa ? 'Kamar ' . $sewa['nomor_kamar'] . ' · sewa sampai ' . tgl($sewa['tanggal_selesai']) : 'Ringkasan sewa dan tagihan kamu.') ?>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4"><div class="card-body">
            <h5>Kamar saya</h5>
            <?php if ($sewa): ?>
                <div class="row text-center mt-3">
                    <div class="col-4"><div class="text-muted small">Kamar</div><div class="fs-4 fw-bold"><?= e($sewa['nomor_kamar']) ?></div><div class="small text-muted"><?= e($sewa['ukuran']) ?> m</div></div>
                    <div class="col-4"><div class="text-muted small">Mulai</div><div class="fw-bold"><?= tgl($sewa['tanggal_mulai']) ?></div></div>
                    <div class="col-4"><div class="text-muted small">Berakhir</div><div class="fw-bold"><?= tgl($sewa['tanggal_selesai']) ?></div></div>
                </div>
            <?php elseif ($booking && $booking['status'] === 'pending'): ?>
                <p class="mb-0">Booking kamar <b><?= e($booking['nomor_kamar']) ?></b> sedang menunggu persetujuan pengelola. <?= badge('pending') ?></p>
            <?php else: ?>
                <p>Kamu belum menyewa kamar.</p>
                <a href="<?= url('index.php') ?>" class="btn btn-primary">Cari kamar</a>
            <?php endif; ?>
        </div></div>

        <div class="card shadow-sm mb-4"><div class="card-body">
            <h5>Tagihan belum lunas</h5>
            <?php if (!$tagihan): ?>
                <p class="text-muted mb-0"><i class="bi bi-check2-circle text-success me-1"></i>Semua tagihan sudah lunas.</p>
            <?php else: ?>
                <table class="table mb-0">
                    <?php foreach ($tagihan as $t): ?>
                        <tr>
                            <td><?= periode_label($t['periode']) ?></td>
                            <td><?= rupiah($t['jumlah']) ?></td>
                            <td class="small <?= $t['jatuh_tempo'] < date('Y-m-d') ? 'text-danger fw-bold' : '' ?>">Jatuh tempo <?= tgl($t['jatuh_tempo']) ?></td>
                            <td><?= badge($t['status']) ?></td>
                            <td class="text-end">
                                <?php if ($t['status'] === 'belum'): ?>
                                    <a href="<?= url('penghuni/bayar.php?id=' . $t['id']) ?>" class="btn btn-sm btn-primary">Bayar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div></div>

        <?php if ($sewa): ?>
            <div class="card shadow-sm"><div class="card-body">
                <h5>Perpanjang atau akhiri sewa</h5>
                <form method="post" class="row g-2 mt-1">
                    <?= csrf_field() ?>
                    <div class="col-md-3">
                        <select name="jenis" class="form-select" onchange="this.form.querySelectorAll('[data-jenis]').forEach(el => el.hidden = el.dataset.jenis !== this.value)">
                            <option value="perpanjang">Perpanjang</option>
                            <option value="berhenti">Berhenti</option>
                        </select>
                    </div>
                    <div class="col-md-3" data-jenis="perpanjang">
                        <input type="number" name="durasi_bulan" class="form-control" min="1" max="24" value="1" placeholder="Bulan">
                    </div>
                    <div class="col-md-3" data-jenis="berhenti" hidden>
                        <input type="date" name="tanggal_rencana" class="form-control" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4"><input name="catatan" class="form-control" placeholder="Catatan (opsional)"></div>
                    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Ajukan</button></div>
                </form>
                <?php foreach ($pengajuan as $p): ?>
                    <div class="small mt-2">
                        <?= tgl($p['created_at']) ?> &middot; <?= e(ucfirst($p['jenis'])) ?>
                        <?= $p['jenis'] === 'perpanjang' ? e($p['durasi_bulan']) . ' bulan' : 'per ' . tgl($p['tanggal_rencana']) ?>
                        <?= badge($p['status']) ?>
                    </div>
                <?php endforeach; ?>
            </div></div>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm"><div class="card-body">
            <h5>Pengumuman</h5>
            <?php foreach ($pengumuman as $p): ?>
                <div class="border-bottom py-2">
                    <div class="fw-semibold"><?= e($p['judul']) ?></div>
                    <div class="small text-muted mb-1"><?= tgl($p['created_at']) ?></div>
                    <div class="small"><?= nl2br(e($p['isi'])) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$pengumuman): ?><p class="text-muted small mb-0">Belum ada pengumuman.</p><?php endif; ?>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
