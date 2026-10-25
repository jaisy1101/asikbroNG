# Pengembangan Sistem Informasi Rekonsiliasi dan Kompilasi PDRB di BPS Provinsi Sulawesi Selatan

## Identitas Penelitian

| Keterangan | Informasi |
|---|---|
| Peneliti | Jaisy Nurhasanah |
| NIM | 222212676 |
| Institusi | Politeknik Statistika STIS |
| Tahun | 2026 |
| Objek penelitian | BPS Provinsi Sulawesi Selatan |
| Artefak | ASIKBRO — Sistem Informasi Rekonsiliasi dan Kompilasi PDRB |

## Deskripsi

ASIKBRO adalah aplikasi web untuk mendukung kompilasi dan rekonsiliasi Produk Domestik Regional Bruto (PDRB) triwulanan, meliputi pengelolaan PDRB menurut lapangan usaha dan pengeluaran, integrasi kedua pendekatan, pemeriksaan diskrepansi melalui proses Konserda, dan pemantauan hasil rekonsiliasi.

Penelitian menggunakan *Design Science Research Methodology* (DSRM). Artefak dikembangkan menggunakan Laravel (PHP), JavaScript, dan MySQL. Pengujian meliputi pengujian fungsional, white-box, serta evaluasi kegunaan menggunakan *System Usability Scale* (SUS).

## Struktur Proyek

Struktur berikut disusun berdasarkan daftar folder dan file aktual yang diperiksa pada 25 Oktober 2026. Ditampilkan bagian yang relevan dengan artefak; bukan daftar semua file.

```text
asikbroNG/
├── app/
│   ├── Console/Commands/         # Perintah pembangkitan data turunan dan integrasi
│   ├── Http/
│   │   ├── Controllers/Api/      # Endpoint API, entri tabel, integrasi, Konserda, monitoring
│   │   └── Middleware/           # Middleware hak akses dan putaran
│   ├── Jobs/                     # Proses pembangkitan turunan dan Konserda
│   ├── Models/                   # Model PDRB, periode, rekonsiliasi, wilayah, pengguna
│   └── Services/
│       ├── Derived/              # Transformasi data turunan LU dan pengeluaran
│       ├── Integrasi/            # Layanan integrasi PDRB
│       ├── Konserda/             # Layanan hasil pemeriksaan/rekonsiliasi
│       ├── LapanganUsaha/        # Impor PDRB lapangan usaha
│       ├── Pdrb/                 # Pengolahan, nilai efektif, ekspor, submission
│       └── Pengeluaran/          # Impor PDRB pengeluaran
├── bootstrap/                    # Inisialisasi Laravel
├── config/                       # Konfigurasi aplikasi
├── database/
│   ├── factories/                # Factory data uji
│   ├── migrations/               # Migrasi basis data
│   └── seeders/                  # Seeder Laravel
├── docs/
│   ├── white-box-konserda.md
│   ├── white-box-testing.md
│   ├── white-box-results.xml
│   └── white-box-source-hashes.txt
├── public/assets/               # CSS, JavaScript, gambar, dan aset frontend
├── resources/views/             # Halaman Blade, layout, dan komponen UI
├── routes/
│   ├── api.php                   # Definisi rute API
│   ├── web.php                   # Definisi rute web
│   └── console.php               # Definisi perintah terjadwal/console
├── storage/                      # Penyimpanan runtime Laravel (data sensitif tidak diunggah)
├── tests/
│   ├── Feature/                 # Pengujian entri, ekspor, nilai efektif, white-box PDRB
│   └── Unit/                    # Pengujian unit
├── .env.example                  # Contoh variabel lingkungan (tanpa kredensial)
├── composer.json                 # Dependensi PHP
├── composer.lock                 # Versi dependensi PHP
├── package.json                  # Dependensi frontend
├── package-lock.json             # Versi dependensi frontend
├── phpunit.xml                   # Konfigurasi pengujian
├── vite.config.js                # Konfigurasi Vite
├── artisan                       # CLI Laravel
└── README.md                     # Dokumentasi ini
```

Direktori `vendor/`, `node_modules/`, `.git/`, file `.env`, cache sementara, dan data runtime bukan bagian yang perlu dicantumkan atau diunggah sebagai artefak sumber.

## Lokasi Kode Pengolahan dan Pemodelan

Dalam struktur yang ada, kode perhitungan, transformasi, dan pengolahan PDRB **menjadi bagian dari aplikasi**, terutama pada:

- `app/Services/Derived/` — transformasi data turunan menurut lapangan usaha dan pengeluaran.
- `app/Services/Integrasi/IntegrasiPdrbService.php` — proses integrasi data PDRB.
- `app/Services/Konserda/KonserdaService.php` — pengolahan hasil pemeriksaan Konserda.
- `app/Services/Pdrb/` — layanan nilai efektif dan pengolahan data PDRB.
- `app/Console/Commands/` dan `app/Jobs/` — pemrosesan melalui command/job.

Belum ditemukan folder kode pemodelan terpisah. Jika terdapat skrip pemodelan di luar aplikasi, tambahkan skrip tersebut dan dokumentasikan lokasinya di sini.

## Dokumentasi Pengujian

Dokumentasi dan rekaman pengujian white-box berada di `docs/`, sedangkan kode pengujian otomatis ada di `tests/Feature/` dan `tests/Unit/`. Keberadaan file pengujian tidak dengan sendirinya membuktikan semua skenario telah lulus; hasilnya perlu dibaca dari laporan pengujian terkait.

## Instalasi untuk Lingkungan Pengembangan

Persyaratan versi PHP, Laravel, dan paket lain mengikuti `composer.json` dan `package.json` pada repository. Contoh alur persiapan aplikasi Laravel:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Pada Windows PowerShell, gunakan `Copy-Item .env.example .env` sebagai pengganti `cp` bila diperlukan. Sesuaikan koneksi MySQL dan pengaturan lingkungan di file `.env` setempat. **Jangan unggah file `.env` ke Git.**

```bash
php artisan serve
```

> **Catatan:** Instruksi ini merupakan persiapan umum Laravel. Ketersediaan skema database lengkap, data awal, dan urutan perintah impor ASIKBRO belum diverifikasi dari isi seluruh kode. Jangan menjalankan `migrate:fresh`, `db:wipe`, atau perintah penghapusan database pada data operasional.

## Dataset

**Belum ditemukan folder dataset khusus dalam struktur yang diperiksa.** Untuk memenuhi ketentuan pengumpulan, tambahkan direktori `dataset/` berisi hanya dataset terbuka untuk publik, atau dataset dummy paling sedikit lima baris yang sudah dimasking apabila data sumber tidak bersifat publik. Sertakan penjelasan sumber, struktur kolom, satuan, periode, dan status keterbukaan data. Jangan mengunggah data internal BPS atau salinan database operasional.

## Dokumentasi Sistem dan Panduan Penggunaan

Direktori `docs/` saat ini telah berisi dokumentasi white-box. Agar ketentuan pengumpulan terpenuhi, tambahkan dokumentasi rancangan/implementasi sistem (misalnya diagram arsitektur dan ERD) serta **panduan penggunaan** berdasarkan peran pengguna. Lokasinya dapat dibuat sebagai `docs/panduan-penggunaan.md` atau file PDF di `docs/`; perbarui README setelah berkasnya benar-benar tersedia.

## Keamanan dan Keterbatasan Publikasi

- Pastikan `.env`, token, kunci rahasia, kredensial, backup, dan file unggahan internal tidak masuk commit.
- Tinjau `database/database.sqlite` sebelum publikasi; jangan unggah apabila mengandung data riil atau sensitif.
- Periksa `git status` dan `git ls-files` untuk memastikan berkas yang akan dikirim telah sesuai.
- Jangan memasukkan `vendor/` dan `node_modules/` ke repository; dependensi dipasang dari file manifest.

## Status Kelengkapan Artefak

| Komponen persyaratan | Kondisi berdasarkan daftar file yang diperiksa |
|---|---|
| Kode artefak aplikasi | Tersedia dalam proyek Laravel |
| Kode pengolahan/pemodelan | Kode pengolahan tersedia di `app/Services/`, `app/Jobs/`, dan `app/Console/Commands/`; kode pemodelan terpisah belum teridentifikasi |
| Dataset publik/dummy | Belum teridentifikasi; perlu dilengkapi |
| Dokumentasi sistem | Dokumen pengujian tersedia; dokumen desain/implementasi perlu diperiksa atau dilengkapi |
| Panduan penggunaan | Belum teridentifikasi; perlu dilengkapi |
| README berisi identitas dan struktur | Dicakup dalam dokumen ini |

---

Repository disiapkan sebagai dokumentasi artefak penelitian skripsi Politeknik Statistika STIS tahun 2026. Struktur dan status dapat diperbarui setelah berkas pengumpulan ditambahkan.
