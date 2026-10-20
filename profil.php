<?php
require_once __DIR__ . '/includes/functions.php';

$u = require_role();
$isPenghuni = $u['role'] === 'penghuni';

if (is_post()) {
    if (($_POST['aksi'] ?? '') === 'password') {
        $hash = q('SELECT password FROM users WHERE id = ?', [$u['id']])->fetchColumn();
        $baru = $_POST['baru'] ?? '';
        if (!password_verify($_POST['lama'] ?? '', $hash)) {
            flash('danger', 'Password lama salah.');
        } elseif (strlen($baru) < 6 || $baru !== ($_POST['konfirmasi'] ?? '')) {
            flash('danger', 'Password baru minimal 6 karakter dan harus sama dengan konfirmasi.');
        } else {
            q('UPDATE users SET password = ? WHERE id = ?', [password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
            flash('success', 'Password berhasil diganti.');
        }
    } else {
        $nama = trim($_POST['nama'] ?? '');
        if ($nama === '') {
            flash('danger', 'Nama wajib diisi.');
        } else {
            q('UPDATE users SET nama = ?, no_hp = ? WHERE id = ?', [$nama, trim($_POST['no_hp'] ?? ''), $u['id']]);
            if ($isPenghuni) {
                q(
                    'UPDATE users SET no_ktp = ?, alamat = ?, kontak_darurat = ? WHERE id = ?',
                    [trim($_POST['no_ktp'] ?? ''), trim($_POST['alamat'] ?? ''), trim($_POST['kontak_darurat'] ?? ''), $u['id']]
                );
            }
            $_SESSION['user']['nama'] = $nama;
            flash('success', 'Profil berhasil disimpan.');
        }
    }
    back();
}

$p = q('SELECT * FROM users WHERE id = ?', [$u['id']])->fetch();
$title = 'Profil';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Profil Saya</h1>
<div class="row g-4">
    <div class="col-lg-7">
        <form method="post" class="card shadow-sm"><div class="card-body">
            <?= csrf_field() ?>
            <h5 class="mb-3">Data diri</h5>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" value="<?= e($p['email']) ?>" disabled>
            </div>
            <div class="row">
                <div class="col-sm-6 mb-3">
                    <label class="form-label">Nama</label>
                    <input name="nama" class="form-control" value="<?= e($p['nama']) ?>" required>
                </div>
                <div class="col-sm-6 mb-3">
                    <label class="form-label">No. HP</label>
                    <input name="no_hp" class="form-control" value="<?= e($p['no_hp']) ?>">
                </div>
            </div>
            <?php if ($isPenghuni): ?>
                <div class="mb-3">
                    <label class="form-label">No. KTP</label>
                    <input name="no_ktp" class="form-control" value="<?= e($p['no_ktp']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat asal</label>
                    <textarea name="alamat" class="form-control" rows="2"><?= e($p['alamat']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kontak darurat (nama &amp; no. HP)</label>
                    <input name="kontak_darurat" class="form-control" value="<?= e($p['kontak_darurat']) ?>">
                </div>
            <?php endif; ?>
            <button class="btn btn-primary">Simpan profil</button>
        </div></form>
    </div>
    <div class="col-lg-5">
        <form method="post" class="card shadow-sm"><div class="card-body">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="password">
            <h5 class="mb-3">Ganti password</h5>
            <div class="mb-3"><label class="form-label">Password lama</label><input type="password" name="lama" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password baru</label><input type="password" name="baru" class="form-control" minlength="6" required></div>
            <div class="mb-3"><label class="form-label">Ulangi password baru</label><input type="password" name="konfirmasi" class="form-control" minlength="6" required></div>
            <button class="btn btn-outline-primary">Ganti password</button>
        </div></form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
