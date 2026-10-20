<?php
require_once __DIR__ . '/../includes/functions.php';

$u = require_role('owner');

if (is_post()) {
    if (($_POST['aksi'] ?? '') === 'toggle') {
        q("UPDATE users SET aktif = 1 - aktif WHERE id = ? AND role IN ('ga', 'penjaga')", [(int) $_POST['id']]);
        flash('success', 'Status akun diubah.');
    } else {
        $email = trim($_POST['email'] ?? '');
        $role = in_array($_POST['role'] ?? '', ['ga', 'penjaga'], true) ? $_POST['role'] : null;
        if (!$role || trim($_POST['nama'] ?? '') === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($_POST['password'] ?? '') < 6) {
            flash('danger', 'Lengkapi data. Password minimal 6 karakter.');
        } elseif (q('SELECT 1 FROM users WHERE email = ?', [$email])->fetch()) {
            flash('danger', 'Email sudah terdaftar.');
        } else {
            q(
                'INSERT INTO users (nama, email, password, no_hp, role) VALUES (?, ?, ?, ?, ?)',
                [trim($_POST['nama']), $email, password_hash($_POST['password'], PASSWORD_DEFAULT), trim($_POST['no_hp'] ?? ''), $role]
            );
            flash('success', 'Akun staff dibuat.');
        }
    }
    back();
}

$staff = q("SELECT * FROM users WHERE role IN ('ga', 'penjaga') ORDER BY role, nama")->fetchAll();

$title = 'Kelola Staff';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-4">Kelola Staff (GA & Penjaga)</h1>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm"><div class="card-body table-responsive">
            <table class="table">
                <thead><tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Role</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($staff as $s): ?>
                    <tr class="<?= $s['aktif'] ? '' : 'text-muted' ?>">
                        <td><?= e($s['nama']) ?></td>
                        <td><?= e($s['email']) ?></td>
                        <td><?= e($s['no_hp']) ?></td>
                        <td><?= e(ROLE_LABEL[$s['role']]) ?></td>
                        <td><?= $s['aktif'] ? badge('aktif') : '<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
                        <td>
                            <form method="post" data-confirm="<?= $s['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?> akun <?= e($s['nama']) ?>?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="toggle">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button class="btn btn-sm <?= $s['aktif'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $s['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <div class="col-lg-4">
        <form method="post" class="card shadow-sm"><div class="card-body">
            <?= csrf_field() ?>
            <h5>Tambah staff</h5>
            <div class="mb-2">
                <select name="role" class="form-select"><option value="ga">General Affair</option><option value="penjaga">Penjaga</option></select>
            </div>
            <div class="mb-2"><input name="nama" class="form-control" placeholder="Nama" required></div>
            <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
            <div class="mb-2"><input name="no_hp" class="form-control" placeholder="No. HP"></div>
            <div class="mb-3"><input name="password" class="form-control" placeholder="Password awal (min 6)" minlength="6" required></div>
            <button class="btn btn-primary w-100">Buat akun</button>
        </div></form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
