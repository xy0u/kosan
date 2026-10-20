# KosKu – Sistem Informasi Kosan

Website pengelolaan kosan berbasis **PHP native + MySQL + Bootstrap 5**, jalan di XAMPP.

## Cara menjalankan
1. Clone repo ini ke folder `htdocs` XAMPP dengan nama folder `kosan`:
   `git clone <url-repo> C:\xampp\htdocs\kosan`
   (Kalau nama foldernya beda, ubah `BASE_URL` di `config/database.php`.)
1. Buka **XAMPP Control Panel**, lalu Start **Apache** dan **MySQL**.
2. Buka http://localhost/phpmyadmin, masuk tab **Import**, pilih `database/kosan.sql`, lalu klik **Go**.
   (Import ulang file ini akan menghapus semua data lama.)
3. Buka http://localhost/kosan

## Akun contoh
Semua akun pakai password `password123`.

| Role | Email |
|---|---|
| Owner | owner@kosan.test |
| General Affair | ga@kosan.test |
| Penjaga | penjaga@kosan.test |
| Penghuni (punya sewa aktif) | budi@kosan.test |
| Penghuni (booking pending) | siti@kosan.test |

Ganti password-nya setelah login (menu nama di kanan atas, lalu **Profil**).

## Struktur folder
```
kosan/
├── config/database.php     ← koneksi DB, nama app, no. WA, rekening
├── includes/               ← helper (functions.php), header, footer
├── database/kosan.sql      ← struktur 16 tabel + data contoh
├── docs/                   ← user requirement & ERD
├── assets/                 ← CSS (style.css, motion.css), JS (app.js), gambar
├── uploads/                ← foto kamar, bukti transfer (otomatis)
├── index.php, kamar.php    ← halaman publik
├── login.php, register.php, profil.php, logout.php
├── owner/    ← dashboard, laporan keuangan, kelola staff
├── ga/       ← kamar, penghuni, booking, sewa, tagihan, pembayaran,
│               keluhan & tugas, pengeluaran, pengumuman
├── penghuni/ ← dashboard, booking, tagihan, bayar, keluhan
└── penjaga/  ← dashboard, tugas, buku tamu, laporan kejadian
```

## Alur utama
1. Calon penghuni daftar, pilih kamar, lalu **booking**.
2. GA **menerima booking**. Sistem otomatis membuat **sewa**, mengubah kamar jadi *terisi*, dan membuat **tagihan** bulan pertama.
3. Tiap awal bulan GA klik **Generate tagihan**.
4. Penghuni **upload bukti transfer**, lalu GA **validasi**. Setelah valid, tagihan jadi lunas.
5. Penghuni kirim **keluhan**, GA **menugaskan** ke penjaga, penjaga update status sampai *selesai* (status keluhan ikut berubah).
6. Penghuni mengajukan **perpanjang/berhenti**, GA menyetujui, lalu melakukan **check-out** (kamar kembali kosong).

## Keamanan yang sudah diterapkan
- Password di-hash (`password_hash` / `password_verify`)
- Semua query pakai prepared statement (aman dari SQL injection)
- Token CSRF di semua form
- Output di-escape (`e()`) untuk mencegah XSS
- Upload dicek tipe & ukurannya (maks 2 MB), nama file diacak, dan PHP tidak bisa dijalankan dari folder `uploads/`
- Hak akses per role (`require_role()`)
