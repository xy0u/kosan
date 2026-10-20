<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga', 'owner');

$id = (int) ($_GET['id'] ?? 0);
$k = $id ? q('SELECT * FROM kamar WHERE id = ?', [$id])->fetch() : null;
if ($id && !$k) {
    redirect('ga/kamar.php');
}
$k = $k ?: ['nomor_kamar' => '', 'tipe' => '', 'harga_per_bulan' => '', 'ukuran' => '', 'status' => 'kosong', 'deskripsi' => ''];

if (is_post()) {
    $data = [
        trim($_POST['nomor_kamar'] ?? ''),
        trim($_POST['tipe'] ?? ''),
        (int) ($_POST['harga_per_bulan'] ?? 0),
        trim($_POST['ukuran'] ?? ''),
        in_array($_POST['status'] ?? '', ['kosong', 'terisi', 'perbaikan'], true) ? $_POST['status'] : 'kosong',
        trim($_POST['deskripsi'] ?? ''),
    ];
    if ($data[0] === '' || $data[1] === '' || $data[2] <= 0) {
        flash('danger', 'Nomor kamar, tipe, dan harga wajib diisi.');
        back();
    }
    try {
        db()->beginTransaction();
        if ($id) {
            q('UPDATE kamar SET nomor_kamar = ?, tipe = ?, harga_per_bulan = ?, ukuran = ?, status = ?, deskripsi = ? WHERE id = ?', [...$data, $id]);
        } else {
            q('INSERT INTO kamar (nomor_kamar, tipe, harga_per_bulan, ukuran, status, deskripsi) VALUES (?, ?, ?, ?, ?, ?)', $data);
            $id = (int) db()->lastInsertId();
        }

        q('DELETE FROM kamar_fasilitas WHERE kamar_id = ?', [$id]);
        foreach ($_POST['fasilitas'] ?? [] as $fid) {
            q('INSERT INTO kamar_fasilitas (kamar_id, fasilitas_id) VALUES (?, ?)', [$id, (int) $fid]);
        }

        foreach ($_POST['hapus_foto'] ?? [] as $fotoId) {
            $path = q('SELECT path FROM foto_kamar WHERE id = ? AND kamar_id = ?', [(int) $fotoId, $id])->fetchColumn();
            if ($path) {
                q('DELETE FROM foto_kamar WHERE id = ?', [(int) $fotoId]);
                hapus_upload($path);
            }
        }

        // Upload banyak foto sekaligus (input name="foto[]")
        $files = $_FILES['foto'] ?? null;
        if ($files && is_array($files['name'])) {
            foreach (array_keys($files['name']) as $i) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $f = ['name' => $files['name'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
                q('INSERT INTO foto_kamar (kamar_id, path) VALUES (?, ?)', [$id, save_upload($f, 'kamar')]);
            }
        }
        db()->commit();
        flash('success', 'Data kamar disimpan.');
        redirect('ga/kamar.php');
    } catch (PDOException $ex) {
        db()->rollBack();
        flash('danger', $ex->getCode() === '23000' ? 'Nomor kamar sudah dipakai.' : 'Gagal menyimpan: ' . $ex->getMessage());
        back();
    } catch (RuntimeException $ex) {
        db()->rollBack();
        flash('danger', $ex->getMessage());
        back();
    }
}

$semuaFasilitas = q('SELECT * FROM fasilitas ORDER BY nama')->fetchAll();
$dipilih = $id ? q('SELECT fasilitas_id FROM kamar_fasilitas WHERE kamar_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN) : [];
$foto = $id ? q('SELECT * FROM foto_kamar WHERE kamar_id = ?', [$id])->fetchAll() : [];

$title = $id ? 'Edit Kamar' : 'Tambah Kamar';
require __DIR__ . '/../includes/header.php';
?>
<a href="<?= url('ga/kamar.php') ?>" class="btn btn-link px-0"><i class="bi bi-arrow-left"></i> Kembali</a>
<h1 class="h3 mb-4"><?= $title ?></h1>
<form method="post" enctype="multipart/form-data" class="card shadow-sm"><div class="card-body">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-md-3 mb-3"><label class="form-label">Nomor kamar</label><input name="nomor_kamar" class="form-control" value="<?= e($k['nomor_kamar']) ?>" required></div>
        <div class="col-md-3 mb-3"><label class="form-label">Tipe</label><input name="tipe" class="form-control" list="tipe" value="<?= e($k['tipe']) ?>" required>
            <datalist id="tipe"><option>Standar</option><option>Eksklusif</option></datalist></div>
        <div class="col-md-3 mb-3"><label class="form-label">Harga per bulan</label><input type="number" name="harga_per_bulan" class="form-control" value="<?= e($k['harga_per_bulan']) ?>" min="1" required></div>
        <div class="col-md-3 mb-3"><label class="form-label">Ukuran (m)</label><input name="ukuran" class="form-control" placeholder="3x4" value="<?= e($k['ukuran']) ?>"></div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['kosong', 'terisi', 'perbaikan'] as $s): ?>
                    <option value="<?= $s ?>" <?= $k['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-9 mb-3"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2"><?= e($k['deskripsi']) ?></textarea></div>
    </div>
    <div class="mb-3">
        <label class="form-label d-block">Fasilitas</label>
        <?php foreach ($semuaFasilitas as $f): ?>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="fasilitas[]" value="<?= $f['id'] ?>" id="f<?= $f['id'] ?>" <?= in_array($f['id'], $dipilih) ? 'checked' : '' ?>>
                <label class="form-check-label" for="f<?= $f['id'] ?>"><?= e($f['nama']) ?></label>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($foto): ?>
        <label class="form-label d-block">Foto saat ini <small class="text-muted">(centang untuk menghapus)</small></label>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($foto as $f): ?>
                <label class="position-relative">
                    <img src="<?= foto_url($f['path']) ?>" width="140" height="95" class="rounded object-fit-cover">
                    <input type="checkbox" name="hapus_foto[]" value="<?= $f['id'] ?>" class="form-check-input position-absolute top-0 end-0 m-1">
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="mb-3">
        <label class="form-label">Tambah foto (boleh pilih beberapa, maks 2 MB per foto)</label>
        <input type="file" name="foto[]" class="form-control" accept="image/*" multiple>
    </div>
    <button class="btn btn-primary">Simpan</button>
</div></form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
