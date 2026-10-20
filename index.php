<?php
require_once __DIR__ . '/includes/functions.php';

$tipe = $_GET['tipe'] ?? '';
$maks = (int) ($_GET['maks'] ?? 0);
$semua = !empty($_GET['semua']);

$sql = "SELECT k.*, (SELECT path FROM foto_kamar f WHERE f.kamar_id = k.id ORDER BY f.id LIMIT 1) AS foto
        FROM kamar k WHERE 1";
$params = [];
if ($tipe !== '') {
    $sql .= ' AND k.tipe = ?';
    $params[] = $tipe;
}
if ($maks > 0) {
    $sql .= ' AND k.harga_per_bulan <= ?';
    $params[] = $maks;
}
if (!$semua) {
    $sql .= " AND k.status = 'kosong'";
}
$sql .= " ORDER BY k.status = 'kosong' DESC, k.nomor_kamar";

$kamar = q($sql, $params)->fetchAll();
$fasilitas = fasilitas_map();
$daftarTipe = q('SELECT DISTINCT tipe FROM kamar ORDER BY tipe')->fetchAll(PDO::FETCH_COLUMN);
$ringkas = q("SELECT COUNT(*) total, SUM(status = 'kosong') kosong, MIN(harga_per_bulan) termurah FROM kamar")->fetch();
$difilter = $tipe !== '' || $maks > 0;

$title = 'Kos bulanan di ' . KOTA;
$layout = 'site';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7" data-reveal>
                <span class="eyebrow">Kos putra &amp; putri · <?= e(KOTA) ?></span>
                <h1 class="mb-3">Kamar bulanan yang rapi, dikelola dengan jelas.</h1>
                <p class="lead mb-0">Pilih kamar, ajukan booking secara online, lalu pantau tagihan dan laporan perbaikan dari satu akun.</p>
                <div class="hero-facts">
                    <div><div class="num" data-count="<?= (int) $ringkas['kosong'] ?>"><?= (int) $ringkas['kosong'] ?></div><div class="lbl">Kamar tersedia</div></div>
                    <div><div class="num"><?= rupiah($ringkas['termurah']) ?></div><div class="lbl">Harga mulai / bulan</div></div>
                    <div><div class="num" data-count="<?= (int) $ringkas['total'] ?>"><?= (int) $ringkas['total'] ?></div><div class="lbl">Total kamar</div></div>
                </div>
            </div>
            <div class="col-lg-5" data-reveal data-reveal-delay="120">
                <form class="card search-panel" action="<?= url('#kamar') ?>">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3">Cari kamar</h2>
                        <div class="mb-3">
                            <label class="form-label">Tipe kamar</label>
                            <select name="tipe" class="form-select">
                                <option value="">Semua tipe</option>
                                <?php foreach ($daftarTipe as $t): ?>
                                    <option <?= $t === $tipe ? 'selected' : '' ?>><?= e($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Anggaran maksimal per bulan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">Rp</span>
                                <input type="number" name="maks" class="form-control" placeholder="1.000.000" min="0" step="50000" value="<?= $maks ?: '' ?>">
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="semua" value="1" id="semua" <?= $semua ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="semua">Tampilkan juga kamar yang sedang terisi</label>
                        </div>
                        <button class="btn btn-primary w-100">Tampilkan kamar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="section" id="kamar">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4" data-reveal>
            <div>
                <span class="eyebrow mb-2">Daftar kamar</span>
                <h2 class="section-title mb-0"><?= $semua ? 'Semua kamar' : 'Kamar yang tersedia' ?></h2>
            </div>
            <?php if ($difilter || $semua): ?>
                <a href="<?= url('#kamar') ?>" class="small"><i class="bi bi-x-circle me-1"></i>Hapus filter</a>
            <?php endif; ?>
        </div>
        <div class="row g-4">
            <?php foreach ($kamar as $i => $k): ?>
                <div class="col-sm-6 col-lg-4" data-reveal data-reveal-delay="<?= ($i % 3) * 80 ?>">
                    <article class="card room-card h-100 position-relative">
                        <div class="thumb">
                            <img src="<?= foto_url($k['foto']) ?>" alt="Foto kamar <?= e($k['nomor_kamar']) ?>" loading="lazy">
                            <?= badge($k['status']) ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <h3 class="title mb-0">Kamar <?= e($k['nomor_kamar']) ?></h3>
                                <span class="meta"><?= e($k['tipe']) ?></span>
                            </div>
                            <div class="meta mb-3"><?= e($k['ukuran']) ?> m &middot; <?= e(implode(' · ', array_slice($fasilitas[$k['id']] ?? [], 0, 3))) ?></div>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="price"><?= rupiah($k['harga_per_bulan']) ?> <small>/ bulan</small></div>
                                <span class="text-primary small fw-semibold">Detail <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                        <a href="<?= url('kamar.php?id=' . $k['id']) ?>" class="stretched-link" aria-label="Lihat kamar <?= e($k['nomor_kamar']) ?>"></a>
                    </article>
                </div>
            <?php endforeach; ?>
            <?php if (!$kamar): ?>
                <div class="col-12">
                    <div class="card"><div class="card-body text-center py-5">
                        <i class="bi bi-door-closed fs-2 text-muted"></i>
                        <p class="fw-semibold mt-2 mb-1">Belum ada kamar yang cocok</p>
                        <p class="text-muted small mb-3">Coba ubah tipe atau naikkan anggaran, atau tanyakan jadwal kamar kosong berikutnya.</p>
                        <a href="https://wa.me/<?= WA_NUMBER ?>" target="_blank" rel="noopener" class="btn btn-light btn-sm"><i class="bi bi-whatsapp me-1"></i>Tanya pengelola</a>
                    </div></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section-alt" id="cara-sewa">
    <div class="container">
        <div class="mb-5" data-reveal>
            <span class="eyebrow mb-2">Cara sewa</span>
            <h2 class="section-title mb-0">Empat langkah sampai pindahan</h2>
        </div>
        <div class="row g-4">
            <?php
            $langkah = [
                ['Pilih kamar', 'Bandingkan harga, ukuran, dan fasilitas tiap kamar langsung dari daftar di atas.'],
                ['Ajukan booking', 'Buat akun, tentukan tanggal masuk dan lama sewa. Tidak perlu datang dulu.'],
                ['Konfirmasi pengelola', 'Pengelola memeriksa dan menyetujui booking, biasanya di hari yang sama.'],
                ['Bayar &amp; masuk', 'Tagihan pertama muncul di akunmu. Transfer, unggah bukti, lalu ambil kunci.'],
            ];
            foreach ($langkah as $n => [$judul, $isi]): ?>
                <div class="col-sm-6 col-lg-3" data-reveal data-reveal-delay="<?= $n * 80 ?>">
                    <div class="step-num mb-3"><?= $n + 1 ?></div>
                    <h3 class="h6 fw-bold"><?= $judul ?></h3>
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
                <span class="eyebrow mb-2">Lokasi &amp; kunjungan</span>
                <h2 class="section-title mb-3">Mau lihat kamarnya dulu?</h2>
                <p class="text-muted">Silakan datang di jam kunjungan atau kabari kami lewat WhatsApp supaya penjaga bisa menyiapkan kamar yang ingin kamu lihat.</p>
                <dl class="row small mb-4">
                    <dt class="col-4 text-muted fw-semibold">Alamat</dt><dd class="col-8"><?= e(ALAMAT) ?></dd>
                    <dt class="col-4 text-muted fw-semibold">Jam kunjungan</dt><dd class="col-8"><?= e(JAM_KUNJUNGAN) ?></dd>
                    <dt class="col-4 text-muted fw-semibold">Email</dt><dd class="col-8"><?= e(EMAIL_KONTAK) ?></dd>
                </dl>
                <a href="https://wa.me/<?= WA_NUMBER ?>?text=<?= rawurlencode('Halo, saya ingin jadwalkan kunjungan ke ' . APP_NAME . '.') ?>" target="_blank" rel="noopener" class="btn btn-primary"><i class="bi bi-whatsapp me-2"></i>Jadwalkan kunjungan</a>
            </div>
            <div class="col-lg-7" data-reveal data-reveal-delay="120">
                <div class="ratio ratio-16x9 rounded-3 overflow-hidden border">
                    <iframe src="https://maps.google.com/maps?q=<?= rawurlencode(ALAMAT) ?>&output=embed" loading="lazy" title="Peta lokasi" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
