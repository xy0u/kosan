<?php
require_once __DIR__ . '/includes/functions.php';

$k = q('SELECT * FROM kamar WHERE id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$k) {
    http_response_code(404);
    exit('Kamar tidak ditemukan. <a href="' . url() . '">Kembali</a>');
}
$foto = q('SELECT path FROM foto_kamar WHERE kamar_id = ? ORDER BY id', [$k['id']])->fetchAll(PDO::FETCH_COLUMN);
$fasilitas = fasilitas_map()[$k['id']] ?? [];
$waText = rawurlencode("Halo, saya tertarik dengan Kamar {$k['nomor_kamar']} ({$k['tipe']}). Apakah masih tersedia?");
$lainnya = q(
    "SELECT k.*, (SELECT path FROM foto_kamar f WHERE f.kamar_id = k.id ORDER BY f.id LIMIT 1) AS foto
     FROM kamar k WHERE k.status = 'kosong' AND k.id <> ? ORDER BY ABS(k.harga_per_bulan - ?) LIMIT 3",
    [$k['id'], $k['harga_per_bulan']]
)->fetchAll();

$ikon = [
    'AC' => 'snow', 'Kipas Angin' => 'fan', 'WiFi' => 'wifi', 'Kamar Mandi Dalam' => 'droplet',
    'Kasur' => 'moon-stars', 'Lemari' => 'archive', 'Meja Belajar' => 'pencil-square', 'Water Heater' => 'thermometer-half',
];

$title = 'Kamar ' . $k['nomor_kamar'];
$layout = 'site';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4 py-lg-5">
    <nav class="small mb-3" data-reveal>
        <a href="<?= url() ?>" class="text-muted">Beranda</a> <span class="text-muted mx-1">/</span>
        <a href="<?= url('#kamar') ?>" class="text-muted">Kamar</a> <span class="text-muted mx-1">/</span>
        <span>Kamar <?= e($k['nomor_kamar']) ?></span>
    </nav>
    <div class="row g-5">
        <div class="col-lg-7" data-reveal>
            <img src="<?= foto_url($foto[0] ?? null) ?>" class="gallery-main mb-2" id="mainFoto" alt="Foto kamar <?= e($k['nomor_kamar']) ?>">
            <?php if (count($foto) > 1): ?>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($foto as $i => $p): ?>
                        <img src="<?= foto_url($p) ?>" class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>" data-gallery="#mainFoto" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-5">
                <h2 class="h5 fw-bold mb-3">Tentang kamar ini</h2>
                <p class="text-muted"><?= nl2br(e($k['deskripsi'] ?: 'Belum ada deskripsi.')) ?></p>
            </div>
            <div class="mt-4">
                <h2 class="h5 fw-bold mb-3">Fasilitas kamar</h2>
                <div class="spec-list">
                    <?php foreach ($fasilitas as $f): ?>
                        <div><i class="bi bi-<?= $ikon[$f] ?? 'check2' ?>"></i><?= e($f) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mt-5">
                <h2 class="h5 fw-bold mb-3">Ketentuan umum</h2>
                <ul class="text-muted small ps-3 mb-0 d-grid gap-1">
                    <li>Pembayaran sewa paling lambat tanggal <?= JATUH_TEMPO_TANGGAL ?> setiap bulan.</li>
                    <li>Tamu wajib lapor ke penjaga dan tidak menginap.</li>
                    <li>Deposit dikembalikan saat check-out setelah pemeriksaan kamar.</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-5" data-reveal data-reveal-delay="100">
            <div class="card sticky-panel"><div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <h1 class="h3 fw-bold mb-0">Kamar <?= e($k['nomor_kamar']) ?></h1>
                    <?= badge($k['status']) ?>
                </div>
                <p class="text-muted mb-4"><?= e($k['tipe']) ?> &middot; <?= e($k['ukuran']) ?> m</p>
                <div class="mb-4">
                    <span class="fs-3 fw-bold"><?= rupiah($k['harga_per_bulan']) ?></span>
                    <span class="text-muted">/ bulan</span>
                </div>
                <?php if ($k['status'] === 'kosong'): ?>
                    <a href="<?= url('penghuni/booking.php?kamar_id=' . $k['id']) ?>" class="btn btn-primary btn-lg w-100 mb-2">Ajukan booking</a>
                <?php else: ?>
                    <div class="alert alert-secondary small mb-2">Kamar ini sedang tidak tersedia. Lihat kamar lain di bawah atau tanyakan jadwal kosong berikutnya.</div>
                <?php endif; ?>
                <a href="https://wa.me/<?= WA_NUMBER ?>?text=<?= $waText ?>" target="_blank" rel="noopener" class="btn btn-light w-100"><i class="bi bi-whatsapp me-2 text-success"></i>Tanya lewat WhatsApp</a>
                <hr class="my-4">
                <ul class="list-unstyled small text-muted mb-0 d-grid gap-2">
                    <li><i class="bi bi-shield-check me-2 text-primary"></i>Booking tidak dipungut biaya</li>
                    <li><i class="bi bi-calendar2-week me-2 text-primary"></i>Sewa minimal 1 bulan</li>
                    <li><i class="bi bi-geo-alt me-2 text-primary"></i><?= e(ALAMAT) ?></li>
                </ul>
            </div></div>
        </div>
    </div>

    <?php if ($lainnya): ?>
        <div class="mt-5 pt-4 border-top">
            <h2 class="h5 fw-bold mb-4" data-reveal>Kamar lain yang tersedia</h2>
            <div class="row g-4">
                <?php foreach ($lainnya as $i => $l): ?>
                    <div class="col-sm-6 col-lg-4" data-reveal data-reveal-delay="<?= $i * 80 ?>">
                        <article class="card room-card h-100 position-relative">
                            <div class="thumb"><img src="<?= foto_url($l['foto']) ?>" alt="" loading="lazy"></div>
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div><div class="title">Kamar <?= e($l['nomor_kamar']) ?></div><div class="meta"><?= e($l['tipe']) ?></div></div>
                                <div class="price"><?= rupiah($l['harga_per_bulan']) ?></div>
                            </div>
                            <a href="<?= url('kamar.php?id=' . $l['id']) ?>" class="stretched-link" aria-label="Kamar <?= e($l['nomor_kamar']) ?>"></a>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
