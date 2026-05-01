# Fase Pengembangan Aplikasi
## Sistem Klasifikasi Kelayakan Lahan Pertanian Berbasis KNN dan GIS

---

## Overview

Dokumen ini membagi pengembangan aplikasi menjadi **6 fase** yang dapat dikerjakan secara bertahap. Setiap fase memiliki deliverables yang jelas dan dapat diuji secara independen.

```
┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐
│ Fase 1  │──▶│ Fase 2  │──▶│ Fase 3  │──▶│ Fase 4  │──▶│ Fase 5  │──▶│ Fase 6  │
│ Setup   │   │  Auth   │   │  Data   │   │   KNN   │   │   GIS   │   │Dashboard│
└─────────┘   └─────────┘   └─────────┘   └─────────┘   └─────────┘   └─────────┘
```

---

## Fase 1: Project Setup & Database

### Tujuan
Menyiapkan fondasi proyek termasuk struktur folder, konfigurasi database, dan koneksi.

### Deliverables

#### 1.1 Struktur Folder Proyek
```
kalasifikasi/
├── index.php
├── config/
│   └── database.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── includes/
│   ├── header.php
│   └── footer.php
├── pages/
│   ├── login.php
│   ├── dashboard.php
│   ├── data-latih/
│   ├── klasifikasi/
│   └── peta/
└── sql/
    └── schema.sql
```

#### 1.2 Database Schema
- Membuat database `klasifikasi_lahan`
- Membuat tabel `users` (dengan seed admin)
- Membuat tabel `data_latih`
- Membuat tabel `data_uji`
- Membuat tabel `hasil_klasifikasi`

#### 1.3 Konfigurasi Koneksi Database
- File `config/database.php` dengan koneksi PDO
- Error handling untuk koneksi gagal

### Kriteria Selesai
- [x] Database dapat diakses
- [x] Semua tabel berhasil dibuat
- [x] User admin berhasil di-seed
- [x] Koneksi database berfungsi

---

## Fase 2: Autentikasi (Login/Logout)

### Tujuan
Implementasi sistem login dan logout untuk single admin user.

### Deliverables

#### 2.1 Halaman Login
- Form login (username & password)
- Validasi input
- Tampilan error message
- Redirect ke dashboard setelah login sukses

#### 2.2 Proses Autentikasi
- Verifikasi password dengan `password_verify()`
- Manajemen session (`$_SESSION`)
- Session timeout 30 menit

#### 2.3 Logout
- Destroy session
- Redirect ke halaman login

#### 2.4 Middleware Auth
- Proteksi halaman yang membutuhkan login
- Redirect ke login jika belum autentikasi

### Fitur Terkait PRD
| ID | Fitur | Status |
|----|-------|--------|
| AUTH-01 | Login | Fase ini |
| AUTH-02 | Logout | Fase ini |

### Kriteria Selesai
- [x] Admin dapat login dengan kredensial yang benar
- [x] Error ditampilkan untuk kredensial salah
- [x] Session tersimpan setelah login
- [x] Logout berhasil menghapus session
- [x] Halaman terproteksi tidak bisa diakses tanpa login

---

## Fase 3: Modul Data Latih (CRUD)

### Tujuan
Implementasi pengelolaan data latih yang akan digunakan untuk algoritma KNN.

### Deliverables

#### 3.1 Tampilan Data Latih
- Tabel dengan pagination
- Menampilkan semua 7 parameter + 4 kelas komoditas
- Fitur pencarian/filter (opsional)

#### 3.2 Input Data Latih
- Form input untuk 7 parameter:
  - Kemiringan Lereng (%)
  - Luas Lahan (Ha)
  - pH Tanah
  - Jenis Tanah (dropdown)
  - Ketinggian Tempat (mdpl)
  - Kedalaman Tanah (cm)
  - Curah Hujan (mm/tahun)
- Input untuk 4 kelas kesesuaian:
  - Kelas Tebu (S1/S2/S3/N)
  - Kelas Cabai (S1/S2/S3/N)
  - Kelas Jagung (S1/S2/S3/N)
  - Kelas Padi (S1/S2/S3/N)
- Validasi input (server-side)

#### 3.3 Edit Data Latih
- Form edit dengan data existing
- Update ke database

#### 3.4 Hapus Data Latih
- Konfirmasi sebelum hapus
- Soft delete atau hard delete

#### 3.5 Import Data Latih (Opsional - Medium Priority)
- Upload file CSV
- Parsing dan validasi data
- Bulk insert ke database

### Fitur Terkait PRD
| ID | Fitur | Status |
|----|-------|--------|
| DL-01 | Input Data Latih | Fase ini |
| DL-02 | Edit Data Latih | Fase ini |
| DL-03 | Hapus Data Latih | Fase ini |
| DL-04 | Lihat Data Latih | Fase ini |
| DL-05 | Import Data Latih | Selesai (CSV) |
| DL-06 | Export Data Latih | Fase 6 |

### Kriteria Selesai
- [x] Data latih dapat ditampilkan dalam tabel
- [x] Data latih baru dapat ditambahkan
- [x] Data latih dapat diedit
- [x] Data latih dapat dihapus
- [x] Validasi input berfungsi dengan baik

---

## Fase 4: Algoritma KNN & Klasifikasi

### Tujuan
Implementasi algoritma K-Nearest Neighbor untuk klasifikasi kelayakan lahan.

### Deliverables

#### 4.1 Normalisasi Data
- Fungsi normalisasi Min-Max
- Normalisasi data latih
- Normalisasi data uji

```php
// Formula normalisasi Min-Max
$normalized = ($value - $min) / ($max - $min);
```

#### 4.2 Perhitungan Euclidean Distance
- Fungsi hitung jarak Euclidean
- Menghitung jarak antara data uji dan semua data latih

```php
// Formula Euclidean Distance
// D(x,y) = √[Σ(xi - yi)²]
```

#### 4.3 Algoritma KNN
- Mendapatkan K tetangga terdekat
- Voting mayoritas untuk menentukan kelas
- Konfigurasi nilai K (default: 3)

#### 4.4 Klasifikasi Multi-Komoditas
- Klasifikasi untuk 4 komoditas sekaligus:
  - Tebu
  - Cabai
  - Jagung
  - Padi
- Hasil: S1, S2, S3, atau N untuk setiap komoditas

#### 4.5 Form Input Data Uji
- Form input 7 parameter lahan
- Input koordinat (latitude, longitude) untuk GIS
- Nama lahan (opsional)

#### 4.6 Tampilan Hasil Klasifikasi
- Menampilkan hasil klasifikasi 4 komoditas
- Perbandingan visual antar komoditas
- Rekomendasi komoditas terbaik
- Simpan hasil ke database

### Fitur Terkait PRD
| ID | Fitur | Status |
|----|-------|--------|
| KL-01 | Input Data Uji | Selesai |
| KL-02 | Normalisasi Data | Selesai |
| KL-03 | Proses KNN | Selesai |
| KL-04 | Klasifikasi Multi-Komoditas | Selesai |
| KL-05 | Perbandingan Kelayakan | Selesai |
| KL-06 | Rekomendasi Komoditas | Selesai |
| KL-07 | Konfigurasi Nilai K | Selesai |

### Kriteria Selesai
- [x] Normalisasi data berfungsi dengan benar
- [x] Perhitungan Euclidean Distance akurat
- [x] Algoritma KNN menghasilkan klasifikasi yang benar
- [x] Klasifikasi berjalan untuk 4 komoditas
- [x] Hasil klasifikasi tersimpan di database
- [x] Rekomendasi komoditas ditampilkan

---

## Fase 5: Visualisasi GIS (Peta)

### Tujuan
Integrasi hasil klasifikasi dengan peta interaktif menggunakan Leaflet.js.

### Deliverables

#### 5.1 Peta Dasar
- Integrasi Leaflet.js
- Menampilkan peta wilayah Desa Curah Kalak
- Tile layer dari OpenStreetMap

#### 5.2 Marker Lahan
- Menampilkan area/poligon untuk setiap data uji yang sudah diklasifikasi
- Warna area berbeda berdasarkan komoditas rekomendasi:
  - Tebu: Hijau
  - Jagung: Kuning
  - Cabe: Orange
  - Padi: Merah

#### 5.3 Filter Komoditas
- Legenda komoditas berdasarkan warna rekomendasi
- Popup menampilkan semua hasil komoditas untuk setiap area

#### 5.4 Popup Informasi Lahan
- Klik area menampilkan detail:
  - Nama lahan
  - Parameter lahan
  - Semua hasil klasifikasi komoditas
  - Rekomendasi komoditas

#### 5.5 Legenda Peta
- Legenda warna untuk komoditas rekomendasi
- Posisi di pojok peta

### Fitur Terkait PRD
| ID | Fitur | Status |
|----|-------|--------|
| GIS-01 | Peta Dasar | Selesai |
| GIS-02 | Layer Kelayakan | Selesai |
| GIS-03 | Filter Komoditas | Selesai |
| GIS-04 | Informasi Lahan | Selesai |
| GIS-05 | Legenda Peta | Selesai |
| GIS-06 | Export Peta | Fase 6 (opsional) |

### Kriteria Selesai
- [x] Peta Leaflet berhasil ditampilkan
- [x] Area lahan/poligon muncul di posisi yang benar
- [x] Warna area sesuai komoditas rekomendasi
- [x] Popup seluruh hasil komoditas berfungsi
- [x] Popup informasi menampilkan data yang benar
- [x] Legenda ditampilkan dengan benar

---

## Fase 6: Dashboard & Finishing

### Tujuan
Implementasi dashboard statistik dan fitur pelengkap lainnya.

### Deliverables

#### 6.1 Dashboard Statistik
- Total data latih
- Total data uji
- Distribusi kelayakan per komoditas
- Peta mini overview

#### 6.2 Grafik Distribusi
- Grafik pie/bar menggunakan Chart.js
- Distribusi S1, S2, S3, N per komoditas

#### 6.3 Riwayat Klasifikasi
- Tabel riwayat hasil klasifikasi
- Detail per klasifikasi
- Tampilan hasil per komoditas dinamis

#### 6.4 Export Data (Opsional)
- Export data latih ke CSV
- Export hasil klasifikasi ke PDF

#### 6.5 Polishing & Optimization
- UI/UX improvements
- Responsive design check
- Performance optimization
- Error handling review

### Fitur Terkait PRD
| ID | Fitur | Status |
|----|-------|--------|
| DB-01 | Dashboard Statistik | Selesai |
| DB-02 | Grafik Distribusi | Selesai |
| DB-03 | Laporan Klasifikasi | Selesai |
| DL-06 | Export Data Latih | Selesai (CSV) |
| GIS-06 | Export Peta | Fase ini (opsional) |

### Kriteria Selesai
- [x] Dashboard menampilkan statistik dengan benar
- [x] Grafik distribusi berfungsi
- [x] Riwayat klasifikasi dapat dilihat
- [x] UI responsif di desktop dan mobile
- [x] Tidak ada error pada semua halaman

### Catatan Implementasi Saat Ini
- Modul `Kriteria` aktif sebagai master komoditas dinamis
- Modul `Nilai Kriteria` aktif sebagai master acuan/rentang admin untuk 7 parameter
- Proses klasifikasi tetap menggunakan KNN murni; `Nilai Kriteria` tidak ikut menentukan hasil
- Input spasial klasifikasi menggunakan gambar area/poligon di peta
- Header halaman dibuat responsif agar tombol aksi tidak terpotong pada lebar konten yang sempit
- Tabel `Nilai Kriteria` dibuat versi compact tanpa lebar minimum 1200px agar muat penuh tanpa scroll horizontal di desktop umum
- Tabel `Data Latih` juga dibuat lebih compact agar 7 parameter + kolom komoditas tetap muat tanpa scroll horizontal pada desktop umum
- Wrapper layout dashboard dipaksa `width: 100%` dan area konten diberi `min-width: 0` agar tidak menyisakan ruang kosong di sisi kanan
- `README.md` diperbarui menjadi panduan instalasi project yang spesifik, termasuk setup `.env`, migration, seeder, login default, dan troubleshooting dasar

---

## Ringkasan Fase

| Fase | Nama | Prioritas | Dependencies |
|------|------|-----------|--------------|
| 1 | Project Setup & Database | Critical | - |
| 2 | Autentikasi | Critical | Fase 1 |
| 3 | Modul Data Latih | High | Fase 2 |
| 4 | Algoritma KNN & Klasifikasi | High | Fase 3 |
| 5 | Visualisasi GIS | High | Fase 4 |
| 6 | Dashboard & Finishing | Medium | Fase 5 |

---

## Catatan Pengembangan

1. **Setiap fase harus di-test** sebelum lanjut ke fase berikutnya
2. **Commit secara berkala** untuk setiap fitur yang selesai
3. **Dokumentasikan** setiap perubahan penting
4. **Backup database** secara berkala selama pengembangan
5. **Gunakan prepared statements** untuk semua query database

---

*Dokumen ini dibuat berdasarkan PRD Sistem Klasifikasi Kelayakan Lahan Pertanian Berbasis KNN dan GIS*
