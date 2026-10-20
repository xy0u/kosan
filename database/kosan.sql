-- =====================================================================
-- Database Sistem Informasi Kosan
-- Import lewat phpMyAdmin (tab Import) atau:
--   C:\xampp\mysql\bin\mysql.exe -u root < database\kosan.sql
-- PERHATIAN: import ulang file ini akan MENGHAPUS semua data lama.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS kosan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kosan;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS laporan_kejadian, pengumuman, pengeluaran, tamu, tugas_penjaga, keluhan,
    pembayaran, tagihan, pengajuan, sewa, booking, foto_kamar, kamar_fasilitas, fasilitas, kamar, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Akun semua role: owner, ga (general affair), penghuni, penjaga
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama           VARCHAR(100) NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    no_hp          VARCHAR(20),
    role           ENUM('owner','ga','penghuni','penjaga') NOT NULL DEFAULT 'penghuni',
    no_ktp         VARCHAR(20),
    alamat         TEXT,
    kontak_darurat VARCHAR(100),
    aktif          TINYINT(1) NOT NULL DEFAULT 1,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Kamar & fasilitas
-- ---------------------------------------------------------------------
CREATE TABLE kamar (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nomor_kamar     VARCHAR(10) NOT NULL UNIQUE,
    tipe            VARCHAR(50) NOT NULL,
    harga_per_bulan INT UNSIGNED NOT NULL,
    ukuran          VARCHAR(20),
    status          ENUM('kosong','terisi','perbaikan') NOT NULL DEFAULT 'kosong',
    deskripsi       TEXT,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE fasilitas (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE kamar_fasilitas (
    kamar_id     INT UNSIGNED NOT NULL,
    fasilitas_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (kamar_id, fasilitas_id),
    FOREIGN KEY (kamar_id) REFERENCES kamar(id) ON DELETE CASCADE,
    FOREIGN KEY (fasilitas_id) REFERENCES fasilitas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE foto_kamar (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kamar_id INT UNSIGNED NOT NULL,
    path     VARCHAR(255) NOT NULL,
    FOREIGN KEY (kamar_id) REFERENCES kamar(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Alur sewa: booking -> sewa -> tagihan -> pembayaran
-- ---------------------------------------------------------------------
CREATE TABLE booking (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    kamar_id        INT UNSIGNED NOT NULL,
    tanggal_booking DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tanggal_masuk   DATE NOT NULL,
    durasi_bulan    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    catatan         TEXT,
    status          ENUM('pending','diterima','ditolak','batal') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kamar_id) REFERENCES kamar(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sewa (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    kamar_id        INT UNSIGNED NOT NULL,
    booking_id      INT UNSIGNED NULL,
    tanggal_mulai   DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    harga_bulanan   INT UNSIGNED NOT NULL,
    deposit         INT UNSIGNED NOT NULL DEFAULT 0,
    deposit_kembali INT UNSIGNED NULL,
    catatan_keluar  TEXT,
    status          ENUM('aktif','selesai') NOT NULL DEFAULT 'aktif',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (kamar_id) REFERENCES kamar(id),
    FOREIGN KEY (booking_id) REFERENCES booking(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Pengajuan perpanjangan / berhenti ngekos dari penghuni
CREATE TABLE pengajuan (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sewa_id         INT UNSIGNED NOT NULL,
    jenis           ENUM('perpanjang','berhenti') NOT NULL,
    durasi_bulan    TINYINT UNSIGNED NULL,
    tanggal_rencana DATE NULL,
    catatan         TEXT,
    status          ENUM('pending','disetujui','ditolak') NOT NULL DEFAULT 'pending',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sewa_id) REFERENCES sewa(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tagihan (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sewa_id     INT UNSIGNED NOT NULL,
    periode     CHAR(7) NOT NULL COMMENT 'format YYYY-MM',
    jumlah      INT UNSIGNED NOT NULL,
    jatuh_tempo DATE NOT NULL,
    status      ENUM('belum','menunggu','lunas') NOT NULL DEFAULT 'belum',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tagihan (sewa_id, periode),
    FOREIGN KEY (sewa_id) REFERENCES sewa(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pembayaran (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tagihan_id      INT UNSIGNED NOT NULL,
    tanggal_bayar   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jumlah          INT UNSIGNED NOT NULL,
    metode          VARCHAR(30) NOT NULL,
    bukti           VARCHAR(255) NULL,
    status          ENUM('menunggu','valid','ditolak') NOT NULL DEFAULT 'menunggu',
    catatan         VARCHAR(255),
    divalidasi_oleh INT UNSIGNED NULL,
    FOREIGN KEY (tagihan_id) REFERENCES tagihan(id) ON DELETE CASCADE,
    FOREIGN KEY (divalidasi_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Operasional: keluhan, tugas penjaga, tamu, kejadian
-- ---------------------------------------------------------------------
CREATE TABLE keluhan (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    kamar_id   INT UNSIGNED NOT NULL,
    judul      VARCHAR(100) NOT NULL,
    isi        TEXT NOT NULL,
    foto       VARCHAR(255) NULL,
    status     ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kamar_id) REFERENCES kamar(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tugas_penjaga (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keluhan_id      INT UNSIGNED NULL,
    penjaga_id      INT UNSIGNED NOT NULL,
    ditugaskan_oleh INT UNSIGNED NULL,
    judul           VARCHAR(100) NOT NULL,
    deskripsi       TEXT,
    status          ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
    foto_hasil      VARCHAR(255) NULL,
    catatan_penjaga TEXT,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    selesai_at      DATETIME NULL,
    FOREIGN KEY (keluhan_id) REFERENCES keluhan(id) ON DELETE SET NULL,
    FOREIGN KEY (penjaga_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ditugaskan_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE tamu (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_tamu    VARCHAR(100) NOT NULL,
    no_hp        VARCHAR(20),
    kamar_id     INT UNSIGNED NULL,
    keperluan    VARCHAR(150),
    jam_masuk    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jam_keluar   DATETIME NULL,
    dicatat_oleh INT UNSIGNED NULL,
    FOREIGN KEY (kamar_id) REFERENCES kamar(id) ON DELETE SET NULL,
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE laporan_kejadian (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    penjaga_id INT UNSIGNED NOT NULL,
    judul      VARCHAR(100) NOT NULL,
    isi        TEXT NOT NULL,
    foto       VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (penjaga_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Keuangan & informasi
-- ---------------------------------------------------------------------
CREATE TABLE pengeluaran (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tanggal      DATE NOT NULL,
    kategori     VARCHAR(30) NOT NULL,
    keterangan   VARCHAR(255),
    jumlah       INT UNSIGNED NOT NULL,
    dicatat_oleh INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE pengumuman (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    judul       VARCHAR(150) NOT NULL,
    isi         TEXT NOT NULL,
    dibuat_oleh INT UNSIGNED NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- DATA CONTOH (password semua akun: lihat README.md)
-- =====================================================================
INSERT INTO users (id, nama, email, password, no_hp, role, no_ktp, alamat, kontak_darurat) VALUES
(1, 'Pak Owner',      'owner@kosan.test',   '$2y$10$4e7CVONfZa3kTFt4hOijOODODtvwlvgQaM.fL17lsc5WavDGVkpiW', '081200000001', 'owner',    NULL, NULL, NULL),
(2, 'Mbak GA',        'ga@kosan.test',      '$2y$10$4e7CVONfZa3kTFt4hOijOODODtvwlvgQaM.fL17lsc5WavDGVkpiW', '081200000002', 'ga',       NULL, NULL, NULL),
(3, 'Mang Penjaga',   'penjaga@kosan.test', '$2y$10$4e7CVONfZa3kTFt4hOijOODODtvwlvgQaM.fL17lsc5WavDGVkpiW', '081200000003', 'penjaga',  NULL, NULL, NULL),
(4, 'Budi Santoso',   'budi@kosan.test',    '$2y$10$4e7CVONfZa3kTFt4hOijOODODtvwlvgQaM.fL17lsc5WavDGVkpiW', '081200000004', 'penghuni', '3273010101010001', 'Jl. Merdeka No. 1, Bandung', 'Ibu Budi - 081299990001'),
(5, 'Siti Aminah',    'siti@kosan.test',    '$2y$10$4e7CVONfZa3kTFt4hOijOODODtvwlvgQaM.fL17lsc5WavDGVkpiW', '081200000005', 'penghuni', NULL, NULL, NULL);

INSERT INTO fasilitas (id, nama) VALUES
(1, 'AC'), (2, 'Kipas Angin'), (3, 'WiFi'), (4, 'Kamar Mandi Dalam'), (5, 'Kasur'), (6, 'Lemari'), (7, 'Meja Belajar'), (8, 'Water Heater');

INSERT INTO kamar (id, nomor_kamar, tipe, harga_per_bulan, ukuran, status, deskripsi) VALUES
(1, 'A01', 'Standar',   800000,  '3x3', 'terisi',    'Kamar lantai 1 dekat dapur bersama.'),
(2, 'A02', 'Standar',   800000,  '3x3', 'kosong',    'Kamar lantai 1, jendela menghadap taman.'),
(3, 'A03', 'Standar',   850000,  '3x4', 'kosong',    'Kamar lantai 1 sudut, lebih luas.'),
(4, 'B01', 'Eksklusif', 1500000, '4x4', 'kosong',    'Lantai 2, AC dan kamar mandi dalam dengan water heater.'),
(5, 'B02', 'Eksklusif', 1500000, '4x4', 'kosong',    'Lantai 2, balkon pribadi.'),
(6, 'B03', 'Eksklusif', 1450000, '4x4', 'perbaikan', 'Sedang pengecatan ulang.');

INSERT INTO kamar_fasilitas (kamar_id, fasilitas_id) VALUES
(1,2),(1,3),(1,5),(1,6),
(2,2),(2,3),(2,5),(2,6),
(3,2),(3,3),(3,5),(3,6),(3,7),
(4,1),(4,3),(4,4),(4,5),(4,6),(4,7),(4,8),
(5,1),(5,3),(5,4),(5,5),(5,6),(5,7),(5,8),
(6,1),(6,3),(6,4),(6,5),(6,6);

-- Budi ngekos di A01 sejak Agustus 2026 selama 6 bulan
INSERT INTO sewa (id, user_id, kamar_id, tanggal_mulai, tanggal_selesai, harga_bulanan, deposit, status) VALUES
(1, 4, 1, '2026-08-01', '2027-02-01', 800000, 500000, 'aktif');

INSERT INTO tagihan (id, sewa_id, periode, jumlah, jatuh_tempo, status) VALUES
(1, 1, '2026-08', 800000, '2026-08-01', 'lunas'),
(2, 1, '2026-09', 800000, '2026-09-10', 'lunas'),
(3, 1, '2026-10', 800000, '2026-10-10', 'belum');

INSERT INTO pembayaran (tagihan_id, tanggal_bayar, jumlah, metode, status, divalidasi_oleh) VALUES
(1, '2026-08-01 10:00:00', 800000, 'Tunai',         'valid', 2),
(2, '2026-09-08 19:30:00', 800000, 'Transfer Bank', 'valid', 2);

-- Siti booking kamar A03, menunggu persetujuan GA
INSERT INTO booking (user_id, kamar_id, tanggal_masuk, durasi_bulan, catatan, status) VALUES
(5, 3, '2026-11-01', 3, 'Saya mahasiswi, rencana masuk awal bulan.', 'pending');

INSERT INTO keluhan (user_id, kamar_id, judul, isi, status) VALUES
(4, 1, 'Lampu kamar mati', 'Lampu utama kamar A01 mati sejak semalam.', 'baru');

INSERT INTO pengeluaran (tanggal, kategori, keterangan, jumlah, dicatat_oleh) VALUES
('2026-09-05', 'Listrik',  'Token listrik September', 650000, 2),
('2026-09-10', 'Internet', 'WiFi bulanan',            350000, 2),
('2026-10-05', 'Listrik',  'Token listrik Oktober',   700000, 2),
('2026-10-10', 'Internet', 'WiFi bulanan',            350000, 2),
('2026-10-15', 'Perbaikan','Cat ulang kamar B03',     900000, 2);

INSERT INTO pengumuman (judul, isi, dibuat_oleh) VALUES
('Selamat datang!', 'Pembayaran sewa paling lambat tanggal 10 setiap bulan. Bukti transfer bisa diupload lewat menu Tagihan.', 2);
