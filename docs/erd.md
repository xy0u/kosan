# ERD – Database Kosan

Buka file ini di VS Code dengan ekstensi *Markdown Preview Mermaid Support*, atau tempel blok di bawah ke https://mermaid.live untuk melihat diagramnya.

```mermaid
erDiagram
    users ||--o{ booking : membuat
    users ||--o{ sewa : menyewa
    users ||--o{ keluhan : mengajukan
    users ||--o{ tugas_penjaga : "mengerjakan (penjaga)"
    users ||--o{ laporan_kejadian : melapor
    users ||--o{ tamu : mencatat
    users ||--o{ pengeluaran : mencatat
    users ||--o{ pengumuman : membuat

    kamar ||--o{ kamar_fasilitas : punya
    fasilitas ||--o{ kamar_fasilitas : dipakai
    kamar ||--o{ foto_kamar : punya
    kamar ||--o{ booking : dibooking
    kamar ||--o{ sewa : disewa
    kamar ||--o{ keluhan : tentang
    kamar ||--o{ tamu : dikunjungi

    booking |o--o| sewa : menjadi
    sewa ||--o{ tagihan : menghasilkan
    sewa ||--o{ pengajuan : punya
    tagihan ||--o{ pembayaran : dibayar
    keluhan |o--o{ tugas_penjaga : diteruskan

    users {
        int id PK
        varchar nama
        varchar email UK
        varchar password
        enum role "owner|ga|penghuni|penjaga"
        varchar no_ktp
        tinyint aktif
    }
    kamar {
        int id PK
        varchar nomor_kamar UK
        varchar tipe
        int harga_per_bulan
        enum status "kosong|terisi|perbaikan"
    }
    booking {
        int id PK
        int user_id FK
        int kamar_id FK
        date tanggal_masuk
        tinyint durasi_bulan
        enum status "pending|diterima|ditolak|batal"
    }
    sewa {
        int id PK
        int user_id FK
        int kamar_id FK
        int booking_id FK
        date tanggal_mulai
        date tanggal_selesai
        int harga_bulanan
        int deposit
        enum status "aktif|selesai"
    }
    tagihan {
        int id PK
        int sewa_id FK
        char periode "YYYY-MM"
        int jumlah
        date jatuh_tempo
        enum status "belum|menunggu|lunas"
    }
    pembayaran {
        int id PK
        int tagihan_id FK
        int jumlah
        varchar metode
        varchar bukti
        enum status "menunggu|valid|ditolak"
        int divalidasi_oleh FK
    }
    keluhan {
        int id PK
        int user_id FK
        int kamar_id FK
        varchar judul
        enum status "baru|diproses|selesai"
    }
    tugas_penjaga {
        int id PK
        int keluhan_id FK
        int penjaga_id FK
        int ditugaskan_oleh FK
        enum status "baru|diproses|selesai"
    }
```

## Daftar tabel (16)
| Tabel | Fungsi |
|---|---|
| users | Akun semua role |
| kamar | Data kamar |
| fasilitas | Master fasilitas (AC, WiFi, ...) |
| kamar_fasilitas | Relasi kamar ↔ fasilitas (many-to-many) |
| foto_kamar | Foto tiap kamar |
| booking | Pemesanan kamar oleh calon penghuni |
| sewa | Kontrak sewa aktif / selesai |
| pengajuan | Permintaan perpanjang / berhenti dari penghuni |
| tagihan | Tagihan bulanan per sewa |
| pembayaran | Bukti bayar & validasi |
| keluhan | Keluhan / permintaan perbaikan |
| tugas_penjaga | Tugas dari GA ke penjaga |
| tamu | Buku tamu |
| laporan_kejadian | Laporan kejadian dari penjaga |
| pengeluaran | Biaya operasional |
| pengumuman | Info untuk penghuni |
