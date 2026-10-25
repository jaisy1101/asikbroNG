# Panduan Penggunaan Sistem ASIKBRO

**ASIKBRO — Sistem Informasi Rekonsiliasi dan Kompilasi PDRB**  
**BPS Provinsi Sulawesi Selatan**  
**Dokumentasi artefak skripsi — 2026**

> **Status dokumen:** Panduan awal berdasarkan struktur modul dan kode proyek ASIKBRO. Nama menu, tombol, urutan tahapan, dan hak akses perlu dicocokkan dengan tampilan sistem sebelum diterbitkan sebagai panduan final.

## 1. Pendahuluan

ASIKBRO adalah sistem informasi berbasis web untuk mendukung pengolahan, kompilasi, dan rekonsiliasi Produk Domestik Regional Bruto (PDRB) triwulanan di BPS Provinsi Sulawesi Selatan. Sistem mencakup pengelolaan PDRB menurut lapangan usaha dan menurut pengeluaran, integrasi data, pemeriksaan hasil rekonsiliasi (Konserda), serta monitoring proses.

## 2. Persiapan Penggunaan

1. Pastikan perangkat terhubung ke jaringan yang dapat mengakses server ASIKBRO.
2. Buka peramban web dan akses alamat sistem yang diberikan oleh pengelola.
3. Siapkan akun yang memiliki hak akses sesuai tugas pengguna.
4. Gunakan data dan periode kerja yang telah ditetapkan oleh pengelola kegiatan.

**Catatan:** Alamat server, akun contoh, dan tangkapan layar tidak dicantumkan karena belum tersedia dalam bahan yang diperiksa. Jangan menuliskan kata sandi asli dalam dokumen panduan.

## 3. Masuk ke Sistem

1. Buka halaman ASIKBRO melalui alamat yang diberikan pengelola.
2. Masukkan kredensial akun pada halaman masuk.
3. Setelah autentikasi berhasil, buka modul sesuai hak akses akun.
4. Apabila gagal masuk atau modul tidak terlihat, hubungi pengelola sistem untuk memeriksa akun dan peran pengguna.

## 4. Gambaran Modul

Berdasarkan struktur kode proyek, ASIKBRO memiliki komponen berikut.

| Modul / proses | Kegunaan umum | Komponen kode terkait |
|---|---|---|
| Data PDRB lapangan usaha | Pengelolaan data menurut lapangan usaha | `app/Services/LapanganUsaha/`, `app/Models/DataPdrbLapanganUsaha.php` |
| Data PDRB pengeluaran | Pengelolaan data menurut pengeluaran | `app/Services/Pengeluaran/`, `app/Models/DataPdrbPengeluaran.php` |
| Entri dan pengiriman data | Memfasilitasi entri atau submission data | `EntriTabelController.php`, `SubmissionController.php`, `SubmissionDataController.php` |
| Integrasi PDRB | Mengolah atau menyatukan data yang digunakan dalam proses rekonsiliasi | `IntegrasiPdrbController.php`, `IntegrasiPdrbService.php` |
| Rekonsiliasi dan putaran | Pengelolaan periode dan putaran kegiatan | `RekonsiliasiController.php`, `Rekonsiliasi.php`, `Putaran.php` |
| Konserda | Pemeriksaan dan penyimpanan hasil rekonsiliasi | `KonserdaController.php`, `KonserdaService.php`, `HasilKonserda.php` |
| Monitoring | Memantau proses kegiatan | `MonitoringController.php` |
| Pengumuman | Pengelolaan informasi atau pemberitahuan | `PengumumanController.php` |
| Ekspor | Mendukung keluaran data dalam format Excel | `app/Services/Pdrb/PdrbExcelExport.php` |

Nama modul pada tabel ini adalah **istilah fungsional**, bukan pernyataan bahwa label menu antarmuka sama persis.

## 5. Alur Penggunaan Umum

### 5.1 Memilih periode dan konteks pengolahan

1. Buka modul pengolahan atau rekonsiliasi yang tersedia bagi akun.
2. Periksa tahun, triwulan, wilayah, dan putaran yang sedang dikerjakan apabila pilihan tersebut ditampilkan.
3. Pastikan konteks yang dipilih sesuai kegiatan sebelum melakukan entri maupun pemeriksaan data.
4. Untuk melihat hasil historis, pilih periode atau putaran yang dimaksud apabila fungsi histori tersedia pada halaman terkait.

### 5.2 Mengelola data PDRB menurut lapangan usaha

1. Buka bagian pengolahan PDRB menurut lapangan usaha.
2. Tentukan periode, wilayah, dan jenis tabel yang tersedia.
3. Lakukan entri atau impor data melalui fasilitas yang ditampilkan sesuai kewenangan akun.
4. Periksa kembali kategori, periode, dan nilai yang dimasukkan.
5. Simpan atau kirim data sesuai mekanisme pada halaman, kemudian periksa respons sistem.

### 5.3 Mengelola data PDRB menurut pengeluaran

1. Buka bagian pengolahan PDRB menurut pengeluaran.
2. Tentukan periode, wilayah, dan jenis tabel yang akan diolah.
3. Masukkan atau impor data melalui fasilitas yang tersedia.
4. Periksa kesesuaian komponen dan nilai data.
5. Simpan atau kirim data, lalu periksa status hasil proses.

### 5.4 Menjalankan integrasi PDRB

1. Buka modul integrasi sesuai hak akses.
2. Periksa periode, wilayah, jenis tabel, dan putaran yang digunakan.
3. Tampilkan hasil integrasi yang tersedia di sistem.
4. Periksa nilai PDRB menurut lapangan usaha dan pengeluaran serta indikator perbandingannya.
5. Jika muncul selisih, telusuri kembali data sumber atau hasil pengolahan sesuai prosedur rekonsiliasi yang berlaku.

**Catatan teknis:** Proses perhitungan integrasi diimplementasikan di `app/Services/Integrasi/IntegrasiPdrbService.php` dan berkaitan dengan `IntegrasiPdrbController.php`. Cara memicu pembaruan hasil melalui antarmuka perlu dikonfirmasi langsung pada sistem.

### 5.5 Memeriksa hasil Konserda

1. Buka modul pemeriksaan hasil Konserda yang tersedia.
2. Tentukan konteks rekonsiliasi/putaran.
3. Tinjau hasil pemeriksaan atau diskrepansi yang disajikan.
4. Untuk nilai yang belum sesuai, identifikasi periode atau kategori yang perlu ditindaklanjuti.
5. Gunakan hasil pemeriksaan sebagai bahan tindak lanjut sebelum penutupan kegiatan sesuai prosedur instansi.

**Catatan teknis:** Logika Konserda tersedia pada `app/Services/Konserda/KonserdaService.php`, dengan proses pekerjaan latar belakang di `app/Jobs/GenerateKonserdaJob.php`.

### 5.6 Memantau status proses

1. Buka halaman monitoring sesuai kewenangan akun.
2. Pilih kegiatan atau periode yang hendak diperiksa.
3. Tinjau informasi status pengiriman data dan/atau hasil pemeriksaan yang ditampilkan.
4. Jika ada data yang belum lengkap atau terdapat selisih, koordinasikan tindak lanjut dengan pihak terkait.

### 5.7 Mengunduh keluaran data

1. Buka halaman yang memiliki fasilitas ekspor.
2. Tentukan periode, wilayah, dan jenis data jika diminta.
3. Gunakan fasilitas ekspor yang tersedia.
4. Periksa file hasil unduhan sebelum dipakai dalam pengolahan atau pelaporan lebih lanjut.

## 6. Hak Akses Pengguna

Proyek memiliki model `Role.php` dan middleware `AdminMiddleware.php`, `UserMiddleware.php`, serta `PutaranBerlangsungMiddleware.php`. Hal ini menunjukkan adanya pembatasan akses berdasarkan peran dan kondisi putaran.

**Penting:** Matriks hak akses per menu belum diverifikasi dari konfigurasi rute dan tampilan. Oleh sebab itu, panduan ini tidak menetapkan tindakan spesifik yang boleh dilakukan setiap peran. Untuk panduan final, tambahkan tabel kewenangan yang telah dicocokkan dengan implementasi.

## 7. Pesan dan Penanganan Kendala Umum

| Situasi | Tindakan awal |
|---|---|
| Tidak dapat masuk | Periksa akun dan koneksi; hubungi pengelola jika tetap gagal |
| Menu tidak terlihat | Periksa kesesuaian peran atau hak akses pengguna |
| Data tidak muncul | Periksa wilayah, tahun, triwulan, jenis tabel, dan putaran yang dipilih |
| Entri tidak berhasil | Baca pesan validasi dan periksa kondisi putaran serta kelengkapan data |
| Hasil integrasi/rekonsiliasi belum sesuai | Tinjau nilai sumber dan hasil pengolahan, lalu lakukan tindak lanjut sesuai prosedur |
| Ekspor gagal | Periksa kembali pilihan konteks dan pesan kesalahan; laporkan kepada pengelola |

## 8. Keamanan dan Kerahasiaan Data

- Jangan membagikan kredensial akses.
- Jangan menyebarkan data internal atau berkas hasil unduhan tanpa kewenangan.
- Pastikan data yang digunakan dalam contoh atau repository publik adalah data terbuka atau data dummy yang aman.
- Keluar dari akun setelah selesai, terutama pada perangkat bersama.

## 9. Dokumen Pendukung dan Pengujian

Dokumentasi white-box yang telah terdapat di proyek:

- `docs/white-box-testing.md`
- `docs/white-box-konserda.md`
- `docs/white-box-results.xml`
- `docs/white-box-source-hashes.txt`

Kode pengujian juga tersedia dalam folder `tests/`, termasuk `WhiteBoxPdrbTest.php`, `EntriTabelTest.php`, `NilaiEfektifPdrbTest.php`, dan `EksporExcelTest.php`.

## 10. Penyempurnaan Sebelum Pengumpulan

Agar menjadi panduan penggunaan yang benar-benar operasional, tambahkan setelah dicocokkan dengan aplikasi:

1. URL akses atau petunjuk akses jaringan yang aman.
2. Tangkapan layar halaman masuk, dashboard, masing-masing modul, dan hasil pemeriksaan.
3. Nama menu dan tombol sebagaimana ditampilkan pada sistem.
4. Rincian hak akses masing-masing peran.
5. Contoh alur satu periode/putaran menggunakan data dummy.

---

**Peneliti:** Jaisy Nurhasanah  
**Institusi:** Politeknik Statistika STIS  
**Tahun:** 2026
