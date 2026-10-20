<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga');

if (is_post()) {
    $aksi = $_POST['aksi'] ?? '';
    try {
        db()->beginTransaction();
        if ($aksi === 'tambah') {
            $durasi = (int) $_POST['durasi_bulan'];
            if ($durasi < 1 || empty($_POST['tanggal_mulai'])) {
                throw new RuntimeException('Tanggal mulai dan durasi wajib diisi.');
            }
            buat_sewa((int) $_POST['user_id'], (int) $_POST['kamar_id'], $_POST['tanggal_mulai'], $durasi, (int) $_POST['deposit']);
            flash('success', 'Sewa dibuat beserta tagihan pertamanya.');
        } elseif ($aksi === 'checkout') {
            $s = q("SELECT * FROM sewa WHERE id = ? AND status = 'aktif'", [(int) $_POST['id']])->fetch();
            if (!$s) {
                throw new RuntimeException('Sewa tidak ditemukan.');
            }
            q(
                "UPDATE sewa SET status = 'selesai', tanggal_selesai = LEAST(tanggal_selesai, CURDATE()), deposit_kembali = ?, catatan_keluar = ? WHERE id = ?",
                [(int) $_POST['deposit_kembali'], trim($_POST['catatan_keluar'] ?? ''), $s['id']]
            );
            q('UPDATE kamar SET status = ? WHERE id = ?', [!empty($_POST['perlu_perbaikan']) ? 'perbaikan' : 'kosong', $s['kamar_id']]);
            q("UPDATE pengajuan SET status = 'disetujui' WHERE sewa_id = ? AND jenis = 'berhenti' AND status = 'pending'", [$s['id']]);
            flash('success', 'Penghuni sudah check-out.');
        } elseif ($aksi === 'pengajuan') {
            $p = q("SELECT * FROM pengajuan WHERE id = ? AND status = 'pending'", [(int) $_POST['id']])->fetch();
            if (!$p) {
                throw new RuntimeException('Pengajuan tidak ditemukan.');
            }
            $setuju = ($_POST['keputusan'] ?? '') === 'setuju';
            q('UPDATE pengajuan SET status = ? WHERE id = ?', [$setuju ? 'disetujui' : 'ditolak', $p['id']]);
            if ($setuju && $p['jenis'] === 'perpanjang') {
                $s = q('SELECT tanggal_selesai FROM sewa WHERE id = ?', [$p['sewa_id']])->fetch();
                q('UPDATE sewa SET tanggal_selesai = ? WHERE id = ?', [tambah_bulan($s['tanggal_selesai'], (int) $p['durasi_bulan']), $p['sewa_id']]);
            }
            flash('success', $setuju
                ? ($p['jenis'] === 'perpanjang' ? 'Sewa diperpanjang.' : 'Pengajuan berhenti disetujui. Lakukan check-out saat penghuni keluar.')
                : 'Pengajuan ditolak.');
        }
        db()->commit();
    } catch (RuntimeException $ex) {
        db()->rollBack();
        flash('danger', $ex->getMessage());
    }
    back();
}

$sewa = q(
    "SELECT s.*, u.nama, u.no_hp, k.nomor_kamar FROM sewa s
     JOIN users u ON u.id = s.user_id JOIN kamar k ON k.id = s.kamar_id
     WHERE s.status = 'aktif' ORDER BY k.nomor_kamar"
)->fetchAll();
$pengajuan = q(
    "SELECT p.*, u.nama, k.nomor_kamar FROM pengajuan p
     JOIN sewa s ON s.id = p.sewa_id JOIN users u ON u.id = s.user_id JOIN kamar k ON k.id = s.kamar_id
     WHERE p.status = 'pending' ORDER BY p.id"
)->fetchAll();
$calon = q(
    "SELECT id, nama FROM users u WHERE role = 'penghuni' AND aktif = 1
     AND NOT EXISTS (SELECT 1 FROM sewa s WHERE s.user_id = u.id AND s.status = 'aktif') ORDER BY nama"
)->fetchAll();
$kamarKosong = q("SELECT id, nomor_kamar, harga_per_bulan FROM kamar WHERE status = 'kosong' ORDER BY nomor_kamar")->fetchAll();

$title = 'Sewa';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Sewa & Pengajuan</h1>

<?php if ($pengajuan): ?>
    <div class="card shadow-sm mb-4 border-start border-warning border-4"><div class="card-body">
        <h5>Pengajuan dari penghuni</h5>
        <table class="table mb-0">
            <?php foreach ($pengajuan as $p): ?>
                <tr>
                    <td><?= e($p['nama']) ?> (<?= e($p['nomor_kamar']) ?>)</td>
                    <td><?= $p['jenis'] === 'perpanjang' ? 'Perpanjang ' . (int) $p['durasi_bulan'] . ' bulan' : 'Berhenti per ' . tgl($p['tanggal_rencana']) ?></td>
                    <td class="small"><?= e($p['catatan']) ?></td>
                    <td class="text-end text-nowrap">
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="pengajuan">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button name="keputusan" value="setuju" class="btn btn-sm btn-success">Setujui</button>
                            <button name="keputusan" value="tolak" class="btn btn-sm btn-outline-danger">Tolak</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm"><div class="card-body table-responsive">
            <h5>Sewa aktif</h5>
            <table class="table">
                <thead><tr><th>Kamar</th><th>Penghuni</th><th>Periode</th><th>Harga</th><th>Deposit</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($sewa as $s): ?>
                    <tr>
                        <td class="fw-bold"><?= e($s['nomor_kamar']) ?></td>
                        <td><?= e($s['nama']) ?><div class="small text-muted"><?= e($s['no_hp']) ?></div></td>
                        <td class="small"><?= tgl($s['tanggal_mulai']) ?> – <?= tgl($s['tanggal_selesai']) ?></td>
                        <td><?= rupiah($s['harga_bulanan']) ?></td>
                        <td><?= rupiah($s['deposit']) ?></td>
                        <td><button class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#co<?= $s['id'] ?>">Check-out</button></td>
                    </tr>
                    <tr class="collapse" id="co<?= $s['id'] ?>"><td colspan="6" class="bg-light">
                        <form method="post" class="row g-2 align-items-center" data-confirm="Proses check-out <?= e($s['nama']) ?>?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="aksi" value="checkout">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <div class="col-md-3"><input type="number" name="deposit_kembali" class="form-control form-control-sm" value="<?= $s['deposit'] ?>" min="0" title="Deposit dikembalikan"></div>
                            <div class="col-md-5"><input name="catatan_keluar" class="form-control form-control-sm" placeholder="Hasil cek kamar"></div>
                            <div class="col-md-2 form-check ms-2"><input type="checkbox" name="perlu_perbaikan" value="1" class="form-check-input" id="pp<?= $s['id'] ?>"><label class="form-check-label small" for="pp<?= $s['id'] ?>">Perlu perbaikan</label></div>
                            <div class="col-md-1"><button class="btn btn-sm btn-danger">OK</button></div>
                        </form>
                    </td></tr>
                <?php endforeach; ?>
                <?php if (!$sewa): ?><tr><td colspan="6" class="text-muted">Belum ada sewa aktif.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="col-lg-4">
        <form method="post" class="card shadow-sm"><div class="card-body">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tambah">
            <h5>Buat sewa manual</h5>
            <div class="mb-2">
                <select name="user_id" class="form-select" required>
                    <option value="">-- penghuni --</option>
                    <?php foreach ($calon as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['nama']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="mb-2">
                <select name="kamar_id" class="form-select" required>
                    <option value="">-- kamar kosong --</option>
                    <?php foreach ($kamarKosong as $k): ?><option value="<?= $k['id'] ?>"><?= e($k['nomor_kamar']) ?> (<?= rupiah($k['harga_per_bulan']) ?>)</option><?php endforeach; ?>
                </select>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-7"><input type="date" name="tanggal_mulai" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-5"><input type="number" name="durasi_bulan" class="form-control" min="1" value="1" title="Durasi (bulan)" required></div>
            </div>
            <div class="mb-3"><input type="number" name="deposit" class="form-control" placeholder="Deposit" min="0" value="0"></div>
            <button class="btn btn-primary w-100">Buat sewa</button>
        </div></form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
