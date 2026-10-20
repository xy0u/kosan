<?php
require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}

/** Jalankan query dengan prepared statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

// ---------------------------------------------------------------------
// Helper umum
// ---------------------------------------------------------------------
function e($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

/** URL file CSS/JS + versi dari waktu ubah file, supaya browser tidak memakai cache lama. */
function asset(string $path): string
{
    $file = __DIR__ . '/../' . $path;
    return url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Kembali ke halaman yang sama (dipakai setelah POST). */
function back(): void
{
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

function rupiah($n): string
{
    return 'Rp ' . number_format((int) $n, 0, ',', '.');
}

const BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function tgl(?string $date, bool $jam = false): string
{
    if (!$date) {
        return '-';
    }
    $t = strtotime($date);
    $s = date('j', $t) . ' ' . substr(BULAN[(int) date('n', $t)], 0, 3) . ' ' . date('Y', $t);
    return $jam ? $s . ' ' . date('H:i', $t) : $s;
}

/** '2026-10' -> 'Oktober 2026' */
function periode_label(string $periode): string
{
    [$y, $m] = explode('-', $periode);
    return BULAN[(int) $m] . ' ' . $y;
}

function badge(string $status): string
{
    $warna = [
        'kosong' => 'success', 'terisi' => 'secondary', 'perbaikan' => 'warning',
        'pending' => 'warning', 'diterima' => 'success', 'ditolak' => 'danger', 'batal' => 'secondary',
        'aktif' => 'success', 'selesai' => 'success', 'disetujui' => 'success',
        'belum' => 'danger', 'menunggu' => 'warning', 'lunas' => 'success', 'valid' => 'success',
        'baru' => 'info', 'diproses' => 'primary',
    ];
    $label = [
        'kosong' => 'Tersedia', 'pending' => 'Menunggu', 'belum' => 'Belum dibayar',
        'menunggu' => 'Diverifikasi', 'valid' => 'Terverifikasi',
    ];
    $w = $warna[$status] ?? 'secondary';
    return '<span class="badge rounded-pill bg-' . $w . '-subtle text-' . $w . '-emphasis border border-' . $w . '-subtle">'
        . e($label[$status] ?? ucfirst($status)) . '</span>';
}

/** Inisial nama untuk avatar, misal "Budi Santoso" -> "BS" */
function inisial(string $nama): string
{
    $kata = preg_split('/\s+/', trim($nama));
    return strtoupper(substr($kata[0], 0, 1) . (isset($kata[1]) ? substr($kata[1], 0, 1) : ''));
}

/** Banner sambutan berwarna di atas halaman dashboard. */
function welcome_banner(string $judul, string $sub): string
{
    return '<div class="welcome"><div class="hero-bg" aria-hidden="true"><span class="blob blob-1"></span><span class="blob blob-2"></span><span class="hero-grid"></span></div>'
        . '<div class="small text-uppercase fw-bold opacity-75 mb-1" style="letter-spacing:.1em">' . e(tgl(date('Y-m-d'))) . '</div>'
        . '<h1 class="h3">' . e($judul) . '</h1><p class="mb-0 opacity-75">' . e($sub) . '</p></div>';
}

function logo_svg(): string
{
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9v11h14V9"/><path d="M10 20v-6h4v6"/></svg>';
}

function foto_url(?string $path): string
{
    return $path ? url('uploads/' . $path) : url('assets/img/no-photo.svg');
}

// ---------------------------------------------------------------------
// Flash message
// ---------------------------------------------------------------------
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = [$type, $msg];
}

function show_flash(): string
{
    $html = '';
    foreach ($_SESSION['flash'] ?? [] as [$type, $msg]) {
        $html .= '<div class="alert alert-' . $type . ' alert-dismissible fade show">' . e($msg)
            . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

// ---------------------------------------------------------------------
// Keamanan: CSRF & login
// ---------------------------------------------------------------------
function csrf_field(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

/** True kalau request POST, sekaligus cek token CSRF. */
function is_post(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Sesi kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
    return true;
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Wajib login dengan salah satu role yang diizinkan. Mengembalikan data user. */
function require_role(string ...$roles): array
{
    $u = user();
    if (!$u) {
        $_SESSION['next'] = $_SERVER['REQUEST_URI'];
        flash('warning', 'Silakan login dulu.');
        redirect('login.php');
    }
    if ($roles && !in_array($u['role'], $roles, true)) {
        http_response_code(403);
        exit('Akses ditolak. <a href="' . url(home_for($u['role'])) . '">Kembali</a>');
    }
    return $u;
}

function home_for(string $role): string
{
    return $role . '/dashboard.php';
}

const ROLE_LABEL = ['owner' => 'Owner', 'ga' => 'General Affair', 'penghuni' => 'Penghuni', 'penjaga' => 'Penjaga'];

/**
 * Menu sidebar per role: daftar [judul seksi|null, [[label, path, ikon], ...]].
 * Ikon dari Bootstrap Icons (tanpa prefix "bi-").
 */
function menu_for(string $role): array
{
    switch ($role) {
        case 'owner':
            return [
                [null, [
                    ['Dashboard', 'owner/dashboard.php', 'grid-1x2'],
                    ['Laporan Keuangan', 'owner/laporan.php', 'bar-chart-line'],
                ]],
                ['Hunian', [
                    ['Kamar', 'ga/kamar.php', 'door-closed'],
                    ['Penghuni', 'ga/penghuni.php', 'people'],
                ]],
                ['Operasional', [
                    ['Pengeluaran', 'ga/pengeluaran.php', 'wallet2'],
                    ['Pengumuman', 'ga/pengumuman.php', 'megaphone'],
                    ['Buku Tamu', 'penjaga/tamu.php', 'journal-text'],
                    ['Laporan Kejadian', 'penjaga/kejadian.php', 'exclamation-triangle'],
                ]],
                ['Administrasi', [
                    ['Staff', 'owner/staff.php', 'person-badge'],
                ]],
            ];
        case 'ga':
            return [
                [null, [
                    ['Dashboard', 'ga/dashboard.php', 'grid-1x2'],
                ]],
                ['Hunian', [
                    ['Kamar', 'ga/kamar.php', 'door-closed'],
                    ['Booking', 'ga/booking.php', 'calendar-check'],
                    ['Penghuni', 'ga/penghuni.php', 'people'],
                    ['Sewa & Pengajuan', 'ga/sewa.php', 'file-earmark-text'],
                ]],
                ['Keuangan', [
                    ['Tagihan', 'ga/tagihan.php', 'receipt'],
                    ['Pembayaran', 'ga/pembayaran.php', 'credit-card'],
                    ['Pengeluaran', 'ga/pengeluaran.php', 'wallet2'],
                ]],
                ['Operasional', [
                    ['Keluhan & Tugas', 'ga/keluhan.php', 'tools'],
                    ['Buku Tamu', 'penjaga/tamu.php', 'journal-text'],
                    ['Laporan Kejadian', 'penjaga/kejadian.php', 'exclamation-triangle'],
                    ['Pengumuman', 'ga/pengumuman.php', 'megaphone'],
                ]],
            ];
        case 'penghuni':
            return [
                [null, [
                    ['Beranda', 'penghuni/dashboard.php', 'house'],
                    ['Tagihan', 'penghuni/tagihan.php', 'receipt'],
                    ['Booking', 'penghuni/booking.php', 'calendar-check'],
                    ['Keluhan', 'penghuni/keluhan.php', 'chat-left-text'],
                ]],
                ['Lainnya', [
                    ['Lihat Kamar', 'index.php', 'search'],
                ]],
            ];
        case 'penjaga':
            return [
                [null, [
                    ['Dashboard', 'penjaga/dashboard.php', 'grid-1x2'],
                    ['Tugas', 'penjaga/tugas.php', 'tools'],
                    ['Buku Tamu', 'penjaga/tamu.php', 'journal-text'],
                    ['Lapor Kejadian', 'penjaga/kejadian.php', 'exclamation-triangle'],
                ]],
            ];
    }
    return [];
}

/** Angka notifikasi kecil di sidebar: [path => jumlah]. */
function nav_counts(array $u): array
{
    switch ($u['role']) {
        case 'ga':
            return [
                'ga/booking.php'    => (int) q("SELECT COUNT(*) FROM booking WHERE status = 'pending'")->fetchColumn(),
                'ga/pembayaran.php' => (int) q("SELECT COUNT(*) FROM pembayaran WHERE status = 'menunggu'")->fetchColumn(),
                'ga/keluhan.php'    => (int) q("SELECT COUNT(*) FROM keluhan WHERE status = 'baru'")->fetchColumn(),
                'ga/sewa.php'       => (int) q("SELECT COUNT(*) FROM pengajuan WHERE status = 'pending'")->fetchColumn(),
            ];
        case 'penjaga':
            return ['penjaga/tugas.php' => (int) q("SELECT COUNT(*) FROM tugas_penjaga WHERE penjaga_id = ? AND status <> 'selesai'", [$u['id']])->fetchColumn()];
        case 'penghuni':
            return ['penghuni/tagihan.php' => (int) q(
                "SELECT COUNT(*) FROM tagihan t JOIN sewa s ON s.id = t.sewa_id WHERE s.user_id = ? AND t.status = 'belum'",
                [$u['id']]
            )->fetchColumn()];
    }
    return [];
}

function is_current(string $path): bool
{
    return $_SERVER['SCRIPT_NAME'] === url($path);
}

// ---------------------------------------------------------------------
// Upload file (gambar / PDF, maks 2 MB, nama acak)
// ---------------------------------------------------------------------
function save_upload(array $f, string $subdir): string
{
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload file gagal (kode ' . $f['error'] . ').');
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran file maksimal 2 MB.');
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format file harus JPG, PNG, WEBP, atau PDF.');
    }
    $dir = UPLOAD_DIR . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) {
        throw new RuntimeException('File gagal disimpan.');
    }
    return "$subdir/$name";
}

/** Upload satu file dari input $field. Null kalau tidak ada file. */
function upload(string $field, string $subdir, bool $wajib = false): ?string
{
    $f = $_FILES[$field] ?? null;
    if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
        if ($wajib) {
            throw new RuntimeException('File wajib diupload.');
        }
        return null;
    }
    return save_upload($f, $subdir);
}

function hapus_upload(?string $path): void
{
    if ($path && is_file(UPLOAD_DIR . $path)) {
        unlink(UPLOAD_DIR . $path);
    }
}

// ---------------------------------------------------------------------
// Logika bisnis sewa & tagihan
// ---------------------------------------------------------------------
function sewa_aktif(int $userId): ?array
{
    return q(
        "SELECT s.*, k.nomor_kamar, k.ukuran FROM sewa s JOIN kamar k ON k.id = s.kamar_id
         WHERE s.user_id = ? AND s.status = 'aktif'",
        [$userId]
    )->fetch() ?: null;
}

function tambah_bulan(string $tanggal, int $bulan): string
{
    return date('Y-m-d', strtotime("$tanggal +$bulan months"));
}

/**
 * Buat sewa baru + tandai kamar terisi + tagihan bulan pertama.
 * Panggil di dalam transaksi.
 */
function buat_sewa(int $userId, int $kamarId, string $mulai, int $durasi, int $deposit, ?int $bookingId = null): int
{
    $kamar = q("SELECT * FROM kamar WHERE id = ? FOR UPDATE", [$kamarId])->fetch();
    if (!$kamar || $kamar['status'] !== 'kosong') {
        throw new RuntimeException('Kamar tidak tersedia.');
    }
    if (sewa_aktif($userId)) {
        throw new RuntimeException('Penghuni ini masih punya sewa aktif.');
    }
    q(
        "INSERT INTO sewa (user_id, kamar_id, booking_id, tanggal_mulai, tanggal_selesai, harga_bulanan, deposit)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$userId, $kamarId, $bookingId, $mulai, tambah_bulan($mulai, $durasi), $kamar['harga_per_bulan'], $deposit]
    );
    $sewaId = (int) db()->lastInsertId();
    q("UPDATE kamar SET status = 'terisi' WHERE id = ?", [$kamarId]);
    q(
        "INSERT INTO tagihan (sewa_id, periode, jumlah, jatuh_tempo) VALUES (?, ?, ?, ?)",
        [$sewaId, substr($mulai, 0, 7), $kamar['harga_per_bulan'], $mulai]
    );
    return $sewaId;
}

/** Buat tagihan periode (YYYY-MM) untuk semua sewa aktif yang belum punya. Mengembalikan jumlah tagihan baru. */
function generate_tagihan(string $periode): int
{
    $awal = $periode . '-01';
    $akhir = date('Y-m-t', strtotime($awal));
    $jatuhTempo = $periode . '-' . str_pad((string) JATUH_TEMPO_TANGGAL, 2, '0', STR_PAD_LEFT);
    return q(
        "INSERT INTO tagihan (sewa_id, periode, jumlah, jatuh_tempo)
         SELECT s.id, ?, s.harga_bulanan, ? FROM sewa s
         WHERE s.status = 'aktif' AND s.tanggal_mulai <= ? AND s.tanggal_selesai > ?
           AND NOT EXISTS (SELECT 1 FROM tagihan t WHERE t.sewa_id = s.id AND t.periode = ?)",
        [$periode, $jatuhTempo, $akhir, $awal, $periode]
    )->rowCount();
}

/** Daftar [kamar_id => [nama fasilitas, ...]] */
function fasilitas_map(): array
{
    $map = [];
    foreach (q("SELECT kf.kamar_id, f.nama FROM kamar_fasilitas kf JOIN fasilitas f ON f.id = kf.fasilitas_id ORDER BY f.nama") as $r) {
        $map[$r['kamar_id']][] = $r['nama'];
    }
    return $map;
}
