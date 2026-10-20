# User Requirement – Sistem Informasi Kosan

## A. Aktor
| Aktor | Peran |
|---|---|
| Owner | Pemilik kosan; memantau bisnis & keuangan, pengambil keputusan akhir |
| GA (General Affair) | Admin operasional: kamar, penghuni, tagihan, pembayaran, keluhan |
| Penghuni | Orang yang ngekos / calon penghuni yang booking |
| Penjaga | Petugas di lokasi: keamanan, kebersihan, perbaikan ringan, tamu |

## B. Kebutuhan fungsional

### Owner
| Kode | Kebutuhan | Halaman |
|---|---|---|
| UR-OWN-01 | Login | login.php |
| UR-OWN-02 | Dashboard hunian (terisi/kosong, okupansi) | owner/dashboard.php |
| UR-OWN-03 | Laporan pemasukan bulanan/tahunan | owner/laporan.php |
| UR-OWN-04 | Lihat penghuni nunggak | owner/dashboard.php |
| UR-OWN-05 | Lihat laporan pengeluaran | ga/pengeluaran.php, owner/laporan.php |
| UR-OWN-06 | Kelola akun GA & penjaga | owner/staff.php |
| UR-OWN-07 | Atur harga sewa kamar | ga/kamar_form.php |
| UR-OWN-08 | Export laporan PDF/Excel | owner/laporan.php |

### GA
| Kode | Kebutuhan | Halaman |
|---|---|---|
| UR-GA-01 | Login | login.php |
| UR-GA-02 | CRUD kamar + foto + fasilitas + status | ga/kamar.php, ga/kamar_form.php |
| UR-GA-03 | Kelola data penghuni (KTP, kontak darurat) | ga/penghuni.php |
| UR-GA-04 | Terima/tolak booking | ga/booking.php |
| UR-GA-05 | Buat data sewa | ga/booking.php, ga/sewa.php |
| UR-GA-06 | Generate tagihan bulanan | ga/tagihan.php |
| UR-GA-07 | Validasi bukti pembayaran | ga/pembayaran.php |
| UR-GA-08 | Terima keluhan dan teruskan ke penjaga | ga/keluhan.php |
| UR-GA-09 | Catat pengeluaran | ga/pengeluaran.php |
| UR-GA-10 | Kirim pengumuman | ga/pengumuman.php |
| UR-GA-11 | Check-out penghuni (cek kamar, deposit) | ga/sewa.php |

### Penghuni
| Kode | Kebutuhan | Halaman |
|---|---|---|
| UR-PNH-01 | Lihat kamar kosong tanpa login | index.php, kamar.php |
| UR-PNH-02 | Daftar & login | register.php, login.php |
| UR-PNH-03 | Booking kamar | penghuni/booking.php |
| UR-PNH-04 | Lihat status booking | penghuni/booking.php |
| UR-PNH-05 | Lihat tagihan & riwayat bayar | penghuni/tagihan.php |
| UR-PNH-06 | Upload bukti transfer | penghuni/bayar.php |
| UR-PNH-07 | Ajukan keluhan (+ foto) | penghuni/keluhan.php |
| UR-PNH-08 | Pantau status keluhan | penghuni/keluhan.php |
| UR-PNH-09 | Lihat pengumuman | penghuni/dashboard.php |
| UR-PNH-10 | Edit profil & ganti password | profil.php |
| UR-PNH-11 | Ajukan perpanjang / berhenti | penghuni/dashboard.php |

### Penjaga
| Kode | Kebutuhan | Halaman |
|---|---|---|
| UR-PJG-01 | Login, tampilan ramah HP | login.php |
| UR-PJG-02 | Lihat tugas dari GA | penjaga/tugas.php |
| UR-PJG-03 | Update status tugas + foto hasil | penjaga/tugas.php |
| UR-PJG-04 | Buku tamu (masuk/keluar) | penjaga/tamu.php |
| UR-PJG-05 | Lihat daftar penghuni & kamar | penjaga/dashboard.php |
| UR-PJG-06 | Lapor kejadian | penjaga/kejadian.php |

## C. Matriks hak akses
| Fitur | Owner | GA | Penghuni | Penjaga |
|---|:-:|:-:|:-:|:-:|
| Dashboard & laporan keuangan | ✅ | ✅ | ❌ | ❌ |
| Kelola akun staff | ✅ | ❌ | ❌ | ❌ |
| Kelola kamar | ✅ | ✅ | ❌ | ❌ |
| Data penghuni | 👁️ | ✅ | ❌ | 👁️ |
| Booking | ❌ | ✅ | ✅ milik sendiri | ❌ |
| Tagihan & pembayaran | 👁️ | ✅ | ✅ milik sendiri | ❌ |
| Keluhan & tugas | 👁️ | ✅ | ✅ ajukan | ✅ kerjakan |
| Buku tamu | 👁️ | 👁️ | ❌ | ✅ |
| Laporan kejadian | 👁️ | 👁️ | ❌ | ✅ |
| Pengumuman | ✅ | ✅ | 👁️ | ❌ |

## D. Kebutuhan non-fungsional
- Keamanan: password di-hash, akses dibatasi per role, data KTP hanya terlihat oleh GA/owner
- Responsif: nyaman dipakai di HP
- Mudah dipakai, termasuk oleh penjaga yang tidak terbiasa dengan teknologi
- Bisa diakses 24 jam
- Database di-backup rutin
