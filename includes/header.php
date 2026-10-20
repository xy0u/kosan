<?php
/**
 * Layout:
 *   - 'site' : website publik (navbar putih + footer)
 *   - 'auth' : halaman login / daftar (layout terbelah)
 *   - 'app'  : dashboard semua role (sidebar + topbar) — default kalau sudah login
 *
 * @var string $title
 * @var string|null $layout
 */
$u = user();
$layout = $layout ?? ($u ? 'app' : 'site');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Beranda') . ' — ' . APP_NAME) ?></title>
    <meta name="description" content="<?= e(APP_NAME . ' — kos bulanan di Jl. Kopo, Gg. Pakasih, ' . KOTA . '. Semua kamar ' . rupiah(HARGA_SEWA) . ' per bulan. Booking dan bayar tagihan secara online.') ?>">
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= asset('assets/css/motion.css') ?>" rel="stylesheet">
    <link href="<?= asset('assets/css/theme.css') ?>" rel="stylesheet">
</head>
<?php if ($layout === 'auth'): ?>
<body>
<div class="auth">
    <aside class="auth-aside">
        <div class="hero-bg" aria-hidden="true"><span class="blob blob-1"></span><span class="blob blob-2"></span><span class="hero-grid"></span></div>
        <a href="<?= url() ?>" class="logo"><span class="logo-mark"><?= logo_svg() ?></span><?= e(APP_NAME) ?></a>
        <div>
            <h2 class="mb-3">Tempat pulang yang tenang di Kopo, <?= e(KOTA) ?>.</h2>
            <p class="mb-0 opacity-75">Cek tagihan, kirim bukti bayar, dan laporkan kendala kamar langsung dari ponsel.</p>
        </div>
        <div class="small opacity-50">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; <?= e(KOTA) ?></div>
    </aside>
    <main class="auth-main">
        <div class="auth-form page-enter">
            <a href="<?= url() ?>" class="logo d-lg-none mb-4"><span class="logo-mark"><?= logo_svg() ?></span><?= e(APP_NAME) ?></a>
            <?= show_flash() ?>

<?php elseif ($layout === 'site'): ?>
<body class="site-body">
<div class="scroll-progress" id="scrollProgress"></div>
<nav class="navbar navbar-expand-lg site-nav sticky-top" id="siteNav">
    <div class="container">
        <a class="logo" href="<?= url() ?>"><span class="logo-mark"><?= logo_svg() ?></span><?= e(APP_NAME) ?></a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="<?= url('#kamar') ?>">Kamar</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('#cara-sewa') ?>">Cara Sewa</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('#lokasi') ?>">Lokasi</a></li>
            </ul>
            <div class="d-flex gap-2 py-2 py-lg-0">
                <?php if ($u): ?>
                    <a class="btn btn-primary" href="<?= url(home_for($u['role'])) ?>">Buka Dashboard</a>
                <?php else: ?>
                    <a class="btn btn-light" href="<?= url('login.php') ?>">Masuk</a>
                    <a class="btn btn-primary" href="<?= url('register.php') ?>">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<?php if ($flash = show_flash()): ?><div class="container pt-3"><?= $flash ?></div><?php endif; ?>

<?php else:
    $counts = nav_counts($u);
    $crumb = $title ?? '';
    ?>
<body>
<aside class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="sidebar">
    <div class="sidebar-brand d-flex align-items-center justify-content-between">
        <a href="<?= url(home_for($u['role'])) ?>" class="logo"><span class="logo-mark"><?= logo_svg() ?></span><?= e(APP_NAME) ?></a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="py-2">
            <?php foreach (menu_for($u['role']) as [$seksi, $items]): ?>
                <?php if ($seksi): ?><div class="nav-section"><?= e($seksi) ?></div><?php endif; ?>
                <?php foreach ($items as [$label, $path, $icon]): ?>
                    <a class="nav-link <?= is_current($path) ? 'active' : '' ?>" href="<?= url($path) ?>">
                        <i class="bi bi-<?= $icon ?>"></i><span><?= e($label) ?></span>
                        <?php if (!empty($counts[$path])): ?><span class="nav-count"><?= $counts[$path] ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot">
            <a href="<?= url() ?>" class="text-reset"><i class="bi bi-box-arrow-up-right me-1"></i> Lihat website</a>
        </div>
    </div>
</aside>
<div class="app-main">
    <header class="topbar">
        <button class="btn btn-light btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="crumb d-none d-sm-block"><?= e(ROLE_LABEL[$u['role']]) ?> <span class="mx-1">/</span> <b><?= e($crumb) ?></b></div>
        <div class="ms-auto dropdown">
            <a href="#" class="d-flex align-items-center gap-2 text-reset" data-bs-toggle="dropdown">
                <span class="avatar"><?= e(inisial($u['nama'])) ?></span>
                <span class="d-none d-md-block lh-sm">
                    <span class="d-block fw-semibold small"><?= e($u['nama']) ?></span>
                    <span class="d-block text-muted" style="font-size:.72rem"><?= e(ROLE_LABEL[$u['role']]) ?></span>
                </span>
                <i class="bi bi-chevron-down small text-muted"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= url('profil.php') ?>"><i class="bi bi-person me-2"></i>Profil saya</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
            </ul>
        </div>
    </header>
    <main class="app-content page-enter">
        <?= show_flash() ?>
<?php endif; ?>
