<?php
require_once __DIR__ . '/includes/functions.php';

if (user()) {
    redirect(home_for(user()['role']));
}

$email = '';
if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $u = q('SELECT * FROM users WHERE email = ?', [$email])->fetch();
    if (!$u || !password_verify($_POST['password'] ?? '', $u['password'])) {
        flash('danger', 'Email atau password salah.');
    } elseif (!$u['aktif']) {
        flash('danger', 'Akun kamu dinonaktifkan. Hubungi pengelola kos.');
    } else {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int) $u['id'], 'nama' => $u['nama'], 'role' => $u['role']];
        $next = $_SESSION['next'] ?? '';
        unset($_SESSION['next']);
        if ($next !== '' && str_starts_with($next, BASE_URL)) {
            header('Location: ' . $next);
            exit;
        }
        redirect(home_for($u['role']));
    }
}

$title = 'Masuk';
$layout = 'auth';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 fw-bold mb-1">Masuk ke akun</h1>
<p class="text-muted mb-4">Untuk penghuni, pengelola, dan penjaga.</p>
<form method="post">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" autocomplete="email" required autofocus>
    </div>
    <div class="mb-4">
        <label class="form-label" for="password">Password</label>
        <div class="input-group">
            <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
            <button class="btn btn-light border" type="button" data-toggle-password="#password" aria-label="Tampilkan password"><i class="bi bi-eye"></i></button>
        </div>
    </div>
    <button class="btn btn-primary btn-lg w-100">Masuk</button>
</form>
<p class="text-muted small mt-4 mb-0">Belum punya akun? <a href="<?= url('register.php') ?>" class="fw-semibold">Daftar sebagai penghuni</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>
