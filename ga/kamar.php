<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga', 'owner');

if (is_post()) {
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'hapus') {
            $id = (int) $_POST['id'];
            $foto = q('SELECT path FROM foto_kamar WHERE kamar_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN);
            q('DELETE FROM kamar WHERE id = ?', [$id]);
            array_map('hapus_upload', $foto);
            flash('success', 'Kamar dihapus.');
        } elseif ($aksi === 'tambah_fasilitas' && trim($_POST['nama'] ?? '') !== '') {
            q('INSERT INTO fasilitas (nama) VALUES (?)', [trim($_POST['nama'])]);
            flash('success', 'Fasilitas ditambahkan.');
        } elseif ($aksi === 'hapus_fasilitas') {
            q('DELETE FROM fasilitas WHERE id = ?', [(int) $_POST['id']]);
            flash('success', 'Fasilitas dihapus.');
        }
    } catch (PDOException $ex) {
        flash('danger', $aksi === 'hapus'
            ? 'Kamar tidak bisa dihapus karena punya riwayat sewa. Ubah statusnya saja.'
            : 'Fasilitas sudah ada.');
    }
    back();
}

$kamar = q(
    "SELECT k.*, (SELECT COUNT(*) FROM foto_kamar f WHERE f.kamar_id = k.id) AS jml_foto,
            (SELECT u.nama FROM sewa s JOIN users u ON u.id = s.user_id WHERE s.kamar_id = k.id AND s.status = 'aktif' LIMIT 1) AS penghuni
     FROM kamar k ORDER BY k.nomor_kamar"
)->fetchAll();
$fasilitas = q('SELECT * FROM fasilitas ORDER BY nama')->fetchAll();

$title = 'Kelola Kamar';
require __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Kelola Kamar</h1>
    <a href="<?= url('ga/kamar_form.php') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah kamar</a>
</div>
<div class="row g-4">
    <div class="col-lg-9">
        <div class="card shadow-sm"><div class="card-body table-responsive">
            <table class="table">
                <thead><tr><th>No.</th><th>Tipe</th><th>Harga/bln</th><th>Ukuran</th><th>Foto</th><th>Penghuni</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($kamar as $k): ?>
                    <tr>
                        <td class="fw-bold"><?= e($k['nomor_kamar']) ?></td>
                        <td><?= e($k['tipe']) ?></td>
                        <td><?= rupiah($k['harga_per_bulan']) ?></td>
                        <td><?= e($k['ukuran']) ?></td>
                        <td><?= $k['jml_foto'] ?></td>
                        <td><?= e($k['penghuni'] ?? '-') ?></td>
                        <td><?= badge($k['status']) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= url('ga/kamar_form.php?id=' . $k['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" class="d-inline" data-confirm="Hapus kamar <?= e($k['nomor_kamar']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="col-lg-3">
        <div class="card shadow-sm"><div class="card-body">
            <h6>Master fasilitas</h6>
            <form method="post" class="input-group input-group-sm mb-3">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="tambah_fasilitas">
                <input name="nama" class="form-control" placeholder="Fasilitas baru" required>
                <button class="btn btn-primary">Tambah</button>
            </form>
            <?php foreach ($fasilitas as $f): ?>
                <form method="post" class="d-flex justify-content-between align-items-center border-bottom py-1" data-confirm="Hapus fasilitas <?= e($f['nama']) ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="hapus_fasilitas">
                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                    <span class="small"><?= e($f['nama']) ?></span>
                    <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button>
                </form>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
