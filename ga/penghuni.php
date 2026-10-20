<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('ga', 'owner');
$bolehEdit = $u['role'] === 'ga';

if (is_post() && $bolehEdit) {
    if (($_POST['aksi'] ?? '') === 'tambah') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($_POST['password'] ?? '') < 6) {
            flash('danger', 'Email harus valid dan password minimal 6 karakter.');
        } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
            flash('danger', 'Email sudah terdaftar.');
        } else {
            q(
                "INSERT INTO users (nama, email, password, no_hp, no_ktp, role) VALUES (?, ?, ?, ?, ?, 'penghuni')",
                [trim($_POST['nama']), $email, password_hash($_POST['password'], PASSWORD_DEFAULT), trim($_POST['no_hp'] ?? ''), trim($_POST['no_ktp'] ?? '')]
            );
            flash('success', 'Penghuni ditambahkan. Buat sewanya di menu Sewa.');
        }
    } elseif (($_POST['aksi'] ?? '') === 'edit') {
        q(
            "UPDATE users SET nama = ?, no_hp = ?, no_ktp = ?, alamat = ?, kontak_darurat = ? WHERE id = ? AND role = 'penghuni'",
            [trim($_POST['nama']), trim($_POST['no_hp']), trim($_POST['no_ktp']), trim($_POST['alamat']), trim($_POST['kontak_darurat']), (int) $_POST['id']]
        );
        flash('success', 'Data penghuni disimpan.');
    }
    back();
}

$detail = isset($_GET['id']) ? q("SELECT * FROM users WHERE id = ? AND role = 'penghuni'", [(int) $_GET['id']])->fetch() : null;

$title = 'Data Penghuni';
require __DIR__ . '/../includes/header.php';

if ($detail):
    $riwayat = q('SELECT s.*, k.nomor_kamar FROM sewa s JOIN kamar k ON k.id = s.kamar_id WHERE s.user_id = ? ORDER BY s.id DESC', [$detail['id']])->fetchAll();
    ?>
    <a href="<?= url('ga/penghuni.php') ?>" class="btn btn-link px-0"><i class="bi bi-arrow-left"></i> Kembali</a>
    <h1 class="h3 mb-4"><?= e($detail['nama']) ?></h1>
    <div class="row g-4">
        <div class="col-lg-6">
            <form method="post" class="card shadow-sm"><div class="card-body">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="edit">
                <input type="hidden" name="id" value="<?= $detail['id'] ?>">
                <fieldset <?= $bolehEdit ? '' : 'disabled' ?>>
                    <p class="text-muted small mb-3"><?= e($detail['email']) ?> &middot; terdaftar <?= tgl($detail['created_at']) ?></p>
                    <div class="mb-2"><label class="form-label">Nama</label><input name="nama" class="form-control" value="<?= e($detail['nama']) ?>"></div>
                    <div class="mb-2"><label class="form-label">No. HP</label><input name="no_hp" class="form-control" value="<?= e($detail['no_hp']) ?>"></div>
                    <div class="mb-2"><label class="form-label">No. KTP</label><input name="no_ktp" class="form-control" value="<?= e($detail['no_ktp']) ?>"></div>
                    <div class="mb-2"><label class="form-label">Alamat asal</label><textarea name="alamat" class="form-control"><?= e($detail['alamat']) ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Kontak darurat</label><input name="kontak_darurat" class="form-control" value="<?= e($detail['kontak_darurat']) ?>"></div>
                    <?php if ($bolehEdit): ?><button class="btn btn-primary">Simpan</button><?php endif; ?>
                </fieldset>
            </div></form>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm"><div class="card-body">
                <h5>Riwayat sewa</h5>
                <table class="table table-sm">
                    <?php foreach ($riwayat as $s): ?>
                        <tr><td><?= e($s['nomor_kamar']) ?></td><td><?= tgl($s['tanggal_mulai']) ?> – <?= tgl($s['tanggal_selesai']) ?></td><td><?= badge($s['status']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$riwayat): ?><tr><td class="text-muted">Belum pernah menyewa.</td></tr><?php endif; ?>
                </table>
            </div></div>
        </div>
    </div>
<?php else:
    $cari = trim($_GET['cari'] ?? '');
    $penghuni = q(
        "SELECT u.*, k.nomor_kamar FROM users u
         LEFT JOIN sewa s ON s.user_id = u.id AND s.status = 'aktif' LEFT JOIN kamar k ON k.id = s.kamar_id
         WHERE u.role = 'penghuni' AND (u.nama LIKE ? OR u.email LIKE ? OR k.nomor_kamar LIKE ?)
         ORDER BY k.nomor_kamar IS NULL, k.nomor_kamar, u.nama",
        ["%$cari%", "%$cari%", "%$cari%"]
    )->fetchAll();
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Data Penghuni</h1>
        <form class="d-flex"><input name="cari" class="form-control" placeholder="Cari nama / kamar" value="<?= e($cari) ?>"></form>
    </div>
    <div class="row g-4">
        <div class="<?= $bolehEdit ? 'col-lg-8' : 'col-12' ?>">
            <div class="card shadow-sm"><div class="card-body table-responsive">
                <table class="table">
                    <thead><tr><th>Nama</th><th>Kontak</th><th>Kamar</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($penghuni as $p): ?>
                        <tr>
                            <td><?= e($p['nama']) ?></td>
                            <td class="small"><?= e($p['no_hp']) ?><br><?= e($p['email']) ?></td>
                            <td><?= $p['nomor_kamar'] ? e($p['nomor_kamar']) : '<span class="text-muted">-</span>' ?></td>
                            <td class="text-end"><a href="?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
        <?php if ($bolehEdit): ?>
            <div class="col-lg-4">
                <form method="post" class="card shadow-sm"><div class="card-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="aksi" value="tambah">
                    <h5>Tambah penghuni (walk-in)</h5>
                    <div class="mb-2"><input name="nama" class="form-control" placeholder="Nama" required></div>
                    <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                    <div class="mb-2"><input name="no_hp" class="form-control" placeholder="No. HP"></div>
                    <div class="mb-2"><input name="no_ktp" class="form-control" placeholder="No. KTP"></div>
                    <div class="mb-3"><input name="password" class="form-control" placeholder="Password awal (min 6)" minlength="6" required></div>
                    <button class="btn btn-primary w-100">Tambah</button>
                </div></form>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
