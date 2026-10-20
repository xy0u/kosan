<?php
require_once __DIR__ . '/includes/functions.php';

if (user()) {
    redirect(home_for(user()['role']));
}

$data = ['nama' => '', 'email' => '', 'no_hp' => ''];
if (is_post()) {
    $data = [
        'nama'  => trim($_POST['nama'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'no_hp' => trim($_POST['no_hp'] ?? ''),
    ];
    $password = $_POST['password'] ?? '';

    $error = null;
    if ($data['nama'] === '' || $data['no_hp'] === '') {
        $error = 'Nama dan nomor HP wajib diisi.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== ($_POST['konfirmasi'] ?? '')) {
        $error = 'Konfirmasi password tidak sama.';
    } elseif (q('SELECT 1 FROM users WHERE email = ?', [$data['email']])->fetch()) {
        $error = 'Email sudah terdaftar.';
    }

    if ($error) {
        flash('danger', $error);
    } else {
        q(
            "INSERT INTO users (nama, email, password, no_hp, role) VALUES (?, ?, ?, ?, 'penghuni')",
            [$data['nama'], $data['email'], password_hash($password, PASSWORD_DEFAULT), $data['no_hp']]
        );
        flash('success', 'Pendaftaran berhasil, silakan login.');
        redirect('login.php');
    }
}

$title = 'Daftar';
$layout = 'auth';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 fw-bold mb-1">Buat akun penghuni</h1>
<p class="text-muted mb-4">Gratis. Dipakai untuk booking kamar dan membayar tagihan.</p>
            <form method="post">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Nama lengkap</label>
                    <input name="nama" class="form-control" value="<?= e($data['nama']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($data['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">No. HP / WhatsApp</label>
                    <input name="no_hp" class="form-control" value="<?= e($data['no_hp']) ?>" required>
                </div>
                <div class="row">
                    <div class="col-sm-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="form-label">Ulangi password</label>
                        <input type="password" name="konfirmasi" class="form-control" minlength="6" required>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg w-100 mt-2">Buat akun</button>
            </form>
<p class="text-muted small mt-4 mb-0">Sudah punya akun? <a href="<?= url('login.php') ?>" class="fw-semibold">Masuk</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
