<?php
// Koneksi database (default XAMPP: user root tanpa password)
define('DB_HOST', 'localhost');
define('DB_NAME', 'kosan');
define('DB_USER', 'root');
define('DB_PASS', '');

// Pengaturan aplikasi
define('APP_NAME', 'KosKu');
define('BASE_URL', '/kosan/');                 // sesuaikan kalau nama folder di htdocs beda
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('WA_NUMBER', '6281200000002');          // nomor WhatsApp pengelola (format 62...)
define('REKENING', 'BCA 1234567890 a.n. Pak Owner');
define('JATUH_TEMPO_TANGGAL', 10);             // tagihan bulanan jatuh tempo tiap tanggal ini

// Info yang tampil di website publik
define('KOTA', 'Bandung');
define('ALAMAT', 'Jl. Contoh Raya No. 12, Coblong, Bandung 40132');
define('EMAIL_KONTAK', 'halo@kosku.test');
define('JAM_KUNJUNGAN', 'Setiap hari, 08.00 – 20.00 WIB');
