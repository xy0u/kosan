<?php
require_once __DIR__ . '/includes/functions.php';

$semua = !empty($_GET['semua']);

$sql = "SELECT k.*, (SELECT path FROM foto_kamar f WHERE f.kamar_id = k.id ORDER BY f.id LIMIT 1) AS foto
        FROM kamar k" . ($semua ? '' : " WHERE k.status = 'kosong'") . "
        ORDER BY k.status = 'kosong' DESC, k.nomor_kamar";
$kamar = q($sql)->fetchAll();
$fasilitas = fasilitas_map();
$ringkas = q("SELECT COUNT(*) total, SUM(status = 'kosong') kosong, MIN(harga_per_bulan) harga FROM kamar")->fetch();
$semuaFasilitas = q('SELECT nama FROM fasilitas ORDER BY nama')->fetchAll(PDO::FETCH_COLUMN);
$waUmum = 'https://wa.me/' . WA_NUMBER . '?text=' . rawurlencode('Halo ' . APP_NAME . ', saya ingin tanya kamar kos yang tersedia.');

$title = 'Kos bulanan di Kopo, ' . KOTA;
$layout = 'site';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-dark">
    <div class="hero-bg" aria-hidden="true">
        <span class="blob blob-1"></span><span class="blob blob-2"></span><span class="blob blob-3"></span>
        <span class="hero-grid"></span>
    </div>
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-chip anim-rise" style="--d:0ms"><span class="live-dot"></span><?= (int) $ringkas['kosong'] ?> kamar siap huni</span>
                <h1 class="hero-title mt-4 mb-4">
                    <span class="anim-rise" style="--d:80ms">Tempat pulang</span>
                    <span class="anim-rise" style="--d:160ms">yang <em class="highlight">tenang</em></span>
                    <span class="anim-rise" style="--d:240ms">di Kopo, <?= e(KOTA) ?>.</span>
                </h1>
                <p class="hero-lead anim-rise" style="--d:320ms">Satu harga untuk semua kamar, tanpa biaya tersembunyi. Booking, bayar tagihan, dan lapor kerusakan cukup dari ponsel.</p>
                <div class="d-flex flex-wrap gap-3 mt-4 anim-rise" style="--d:400ms">
                    <a href="#kamar" class="btn btn-accent btn-lg btn-shine">Lihat kamar tersedia</a>
                    <a href="<?= e($waUmum) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-lg"><i class="bi bi-whatsapp me-2"></i>Chat pengelola</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="price-card anim-pop" style="--d:300ms">
                    <div class="price-card-inner">
                        <div class="small text-uppercase fw-bold opacity-75" style="letter-spacing:.1em">Harga semua kamar</div>
                        <div class="price-big my-2"><?= rupiah($ringkas['harga'] ?: HARGA_SEWA) ?><span>/bulan</span></div>
                        <ul class="list-unstyled d-grid gap-2 mb-4 small">
                            <li><i class="bi bi-check-circle-fill"></i> Sewa minimal 1 bulan</li>
                            <li><i class="bi bi-check-circle-fill"></i> Booking online tanpa biaya</li>
                            <li><i class="bi bi-check-circle-fill"></i> Tagihan &amp; bukti bayar tercatat rapi</li>
                            <li><i class="bi bi-check-circle-fill"></i> Penjaga kos di lokasi</li>
                        </ul>
                        <div class="d-flex justify-content-between text-center border-top border-white border-opacity-25 pt-3">
                            <div><div class="fs-4 fw-bold" data-count="<?= (int) $ringkas['kosong'] ?>"><?= (int) $ringkas['kosong'] ?></div><div class="small opacity-75">Tersedia</div></div>
                            <div><div class="fs-4 fw-bold" data-count="<?= (int) $ringkas['total'] ?>"><?= (int) $ringkas['total'] ?></div><div class="small opacity-75">Total kamar</div></div>
                            <div><div class="fs-4 fw-bold" data-count="<?= count($semuaFasilitas) ?>"><?= count($semuaFasilitas) ?></div><div class="small opacity-75">Fasilitas</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if ($semuaFasilitas): ?>
<div class="marquee" aria-label="Fasilitas">
    <div class="marquee-track">
        <?php for ($r = 0; $r < 2; $r++): foreach ($semuaFasilitas as $f): ?>
            <span><i class="bi bi-stars"></i><?= e($f) ?></span>
        <?php endforeach; endfor; ?>
    </div>
</div>
<?php endif; ?>

<section class="section" id="kamar">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4" data-reveal>
            <div>
                <span class="eyebrow mb-2">Daftar kamar</span>
                <h2 class="section-title mb-0">Pilih kamar favoritmu</h2>
            </div>
            <div class="seg">
                <a href="<?= url('#kamar') ?>" class="<?= $semua ? '' : 'active' ?>">Tersedia</a>
                <a href="<?= url('?semua=1#kamar') ?>" class="<?= $semua ? 'active' : '' ?>">Semua kamar</a>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach ($kamar as $i => $k): ?>
                <div class="col-sm-6 col-lg-4" data-reveal data-reveal-delay="<?= ($i % 3) * 90 ?>">
                    <article class="card room-card h-100 position-relative" data-tilt>
                        <div class="thumb">
                            <img src="<?= foto_url($k['foto']) ?>" alt="Foto kamar <?= e($k['nomor_kamar']) ?>" loading="lazy">
                            <?= badge($k['status']) ?>
                            <span class="thumb-no"><?= e($k['nomor_kamar']) ?></span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h3 class="title mb-1">Kamar <?= e($k['nomor_kamar']) ?></h3>
                            <div class="meta mb-3"><i class="bi bi-arrows-angle-expand me-1"></i><?= e($k['ukuran']) ?> m</div>
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <?php foreach (array_slice($fasilitas[$k['id']] ?? [], 0, 4) as $f): ?>
                                    <span class="chip"><?= e($f) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top">
                                <div class="price"><?= rupiah($k['harga_per_bulan']) ?> <small>/ bulan</small></div>
                                <span class="go"><i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                        <a href="<?= url('kamar.php?id=' . $k['id']) ?>" class="stretched-link" aria-label="Lihat kamar <?= e($k['nomor_kamar']) ?>"></a>
                    </article>
                </div>
            <?php endforeach; ?>
            <?php if (!$kamar): ?>
                <div class="col-12" data-reveal>
                    <div class="card"><div class="card-body text-center py-5">
                        <i class="bi bi-door-closed fs-2 text-muted"></i>
                        <p class="fw-semibold mt-2 mb-1">Semua kamar sedang terisi</p>
                        <p class="text-muted small mb-3">Tanyakan jadwal kamar kosong berikutnya ke pengelola.</p>
                        <a href="<?= e($waUmum) ?>" target="_blank" rel="noopener" class="btn btn-light btn-sm"><i class="bi bi-whatsapp me-1"></i>Tanya pengelola</a>
                    </div></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section-alt" id="cara-sewa">
    <div class="container">
        <div class="text-center mb-5" data-reveal>
            <span class="eyebrow mb-2">Cara sewa</span>
            <h2 class="section-title mb-0">Empat langkah sampai pindahan</h2>
        </div>
        <div class="steps" data-observe>
            <?php
            $langkah = [
                ['chat-dots', 'Janjian survei', 'Chat pengelola via WhatsApp untuk atur waktu lihat kamar.'],
                ['calendar-check', 'Ajukan booking', 'Buat akun, pilih kamar, tanggal masuk, dan lama sewa.'],
                ['patch-check', 'Dikonfirmasi', 'Pengelola memeriksa dan menyetujui booking kamu.'],
                ['key', 'Bayar &amp; masuk', 'Bayar tagihan pertama, unggah bukti, lalu ambil kunci.'],
            ];
            foreach ($langkah as $n => [$ikon, $judul, $isi]): ?>
                <div class="step-item" data-reveal data-reveal-delay="<?= $n * 120 ?>">
                    <div class="step-icon"><i class="bi bi-<?= $ikon ?>"></i><span><?= $n + 1 ?></span></div>
                    <h3 class="h6 fw-bold mt-3"><?= $judul ?></h3>
                    <p class="text-muted small mb-0"><?= $isi ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="lokasi">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-5" data-reveal>
                <span class="eyebrow mb-2">Lokasi</span>
                <h2 class="section-title mb-3">Di Jalan Kopo, Gang Pakasih</h2>
                <p class="text-muted">Akses mudah ke angkot, minimarket, dan tempat makan di sepanjang Jalan Kopo.</p>
                <div class="notice my-4">
                    <i class="bi bi-info-circle-fill"></i>
                    <div><b>Kunjungan wajib janjian dulu.</b><br><span class="small">Tidak ada jam kunjungan bebas. Chat pengelola via WhatsApp sebelum datang supaya penjaga bisa menyiapkan kamar.</span></div>
                </div>
                <dl class="row small mb-4">
                    <dt class="col-3 text-muted fw-semibold">Alamat</dt><dd class="col-9"><?= e(ALAMAT) ?></dd>
                    <dt class="col-3 text-muted fw-semibold">WhatsApp</dt><dd class="col-9">+<?= e(WA_NUMBER) ?></dd>
                </dl>
                <a href="https://wa.me/<?= WA_NUMBER ?>?text=<?= rawurlencode('Halo ' . APP_NAME . ', saya ingin janjian survei kamar.') ?>" target="_blank" rel="noopener" class="btn btn-primary btn-shine"><i class="bi bi-whatsapp me-2"></i>Janjian survei</a>
            </div>
            <div class="col-lg-7" data-reveal data-reveal-delay="120">
                <div class="map-frame">
                    <iframe src="https://maps.google.com/maps?q=<?= rawurlencode('Gang Pakasih, Kopo, Bandung') ?>&output=embed" loading="lazy" title="Peta lokasi" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="cta-band" data-reveal>
            <div>
                <h2 class="h3 fw-bold mb-1 text-white">Siap pindah ke <?= e(APP_NAME) ?>?</h2>
                <p class="mb-0 opacity-75">Kamar terbatas — amankan sekarang dengan booking online.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('register.php') ?>" class="btn btn-accent btn-lg btn-shine">Daftar &amp; booking</a>
                <a href="<?= e($waUmum) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-lg"><i class="bi bi-whatsapp me-2"></i>WhatsApp</a>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
