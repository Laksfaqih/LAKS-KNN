# Product Requirements Document (PRD)
## Sistem Klasifikasi Kelayakan Lahan Pertanian Berbasis KNN dan GIS

---

### 1. Informasi Dokumen

| Field | Detail |
|-------|--------|
| **Nama Proyek** | Klasifikasi Kelayakan Lahan Pertanian Menggunakan Metode KNN Berbasis GIS |
| **Versi** | 1.0 |
| **Tanggal** | 1 Mei 2026 |
| **Penulis** | Moh. Alfian Husni Mubarok |
| **Institusi** | Program Studi Teknologi Informasi, Fakultas Sains dan Teknologi, Universitas Ibrahimy Situbondo |
| **Lokasi Studi** | Desa Curah Kalak, Kecamatan Jangkar, Kabupaten Situbondo |

---

### 2. Ringkasan Eksekutif

Sistem ini bertujuan untuk mengklasifikasikan kelayakan lahan pertanian menggunakan metode **K-Nearest Neighbor (KNN)** yang terintegrasi dengan **Geographic Information System (GIS)** berbasis web. Sistem akan menganalisis parameter karakteristik lahan dan menghasilkan klasifikasi tingkat kesesuaian lahan untuk komoditas pertanian (tebu, cabai, jagung, dan padi), kemudian menampilkan hasilnya dalam bentuk peta tematik interaktif.

---

### 3. Latar Belakang & Permasalahan

#### 3.1 Konteks
Indonesia sebagai negara agraris memiliki potensi besar di sektor pertanian. Desa Curah Kalak memiliki berbagai lahan pertanian dengan karakteristik yang berbeda-beda, namun belum ada sistem yang dapat mengevaluasi tingkat kelayakan lahan secara objektif dan berbasis data.

#### 3.2 Permasalahan yang Dihadapi
1. Setiap komoditas pertanian memiliki kebutuhan karakteristik fisik dan lingkungan yang berbeda
2. Penentuan tingkat kesesuaian lahan tidak dapat didasarkan pada satu parameter saja
3. Belum ada sistem terintegrasi untuk evaluasi kelayakan lahan berbasis data dan visualisasi geografis
4. Pemanfaatan lahan yang tidak tepat menyebabkan hasil produksi kurang optimal

#### 3.3 Solusi yang Diusulkan
Membangun sistem berbasis web yang mengintegrasikan metode klasifikasi KNN dengan GIS untuk:
- Menganalisis parameter lahan secara otomatis
- Mengklasifikasikan tingkat kelayakan lahan per komoditas
- Menampilkan hasil dalam peta tematik interaktif

---

### 4. Tujuan Produk

#### 4.1 Tujuan Utama
1. Mengklasifikasikan kelayakan lahan pertanian berdasarkan parameter karakteristik lahan menggunakan metode KNN
2. Menentukan tingkat kesesuaian lahan ke dalam kelas S1, S2, S3, dan N untuk komoditas tebu, cabai, jagung, dan padi
3. Menyajikan hasil klasifikasi dalam bentuk peta tematik berbasis web

#### 4.2 Tujuan Bisnis
- Membantu Pemerintah Desa dan Badan Penyuluhan Pertanian dalam perencanaan lahan
- Memberikan rekomendasi komoditas terbaik berdasarkan karakteristik lahan
- Mendukung pengambilan keputusan berbasis data

---

### 5. Target Pengguna

| Pengguna | Deskripsi | Kebutuhan |
|----------|-----------|-----------|
| **Admin/Peneliti** | Pengelola tunggal sistem (mahasiswa/peneliti) | Mengelola data latih, melakukan klasifikasi lahan, melihat hasil visualisasi GIS, dan mengelola seluruh konfigurasi sistem |

> **Catatan:** Sistem ini dirancang untuk penggunaan single-user oleh peneliti/admin yang bertanggung jawab penuh atas operasional sistem.

---

### 6. Fitur dan Fungsionalitas

#### 6.1 Modul Autentikasi
| ID | Fitur | Deskripsi | Prioritas |
|----|-------|-----------|-----------|
| AUTH-01 | Login | Admin dapat masuk ke sistem dengan username dan password | High |
| AUTH-02 | Logout | Admin dapat keluar dari sistem | High |

> **Catatan:** Sistem menggunakan single-user authentication. Hanya ada 1 akun admin yang sudah di-seed ke database.

#### 6.2 Modul Pengelolaan Data Latih
| ID | Fitur | Deskripsi | Prioritas |
|----|-------|-----------|-----------|
| DL-01 | Input Data Latih | Admin dapat memasukkan data latih dengan parameter lahan dan label kelas | High |
| DL-02 | Edit Data Latih | Admin dapat mengubah data latih yang sudah ada | High |
| DL-03 | Hapus Data Latih | Admin dapat menghapus data latih | High |
| DL-04 | Lihat Data Latih | Admin dapat melihat seluruh data latih dalam tabel | High |
| DL-05 | Import Data Latih | Admin dapat mengimpor data latih dari file Excel/CSV | Medium |
| DL-06 | Export Data Latih | Admin dapat mengekspor data latih ke file Excel/CSV | Low |

#### 6.3 Modul Klasifikasi Lahan (Inti)
| ID | Fitur | Deskripsi | Prioritas |
|----|-------|-----------|-----------|
| KL-01 | Input Data Uji | Pengguna dapat memasukkan data lahan yang akan diklasifikasi | High |
| KL-02 | Normalisasi Data | Sistem melakukan normalisasi data secara otomatis | High |
| KL-03 | Proses KNN | Sistem menghitung jarak Euclidean dan menentukan kelas berdasarkan K tetangga terdekat | High |
| KL-04 | Klasifikasi Multi-Komoditas | Sistem mengklasifikasi untuk 4 komoditas sekaligus (tebu, cabai, jagung, padi) | High |
| KL-05 | Perbandingan Kelayakan | Sistem menampilkan perbandingan tingkat kelayakan antar komoditas | High |
| KL-06 | Rekomendasi Komoditas | Sistem memberikan rekomendasi komoditas terbaik | High |
| KL-07 | Konfigurasi Nilai K | Admin dapat mengatur nilai K untuk algoritma KNN | Medium |

#### 6.4 Modul Visualisasi GIS
| ID | Fitur | Deskripsi | Prioritas |
|----|-------|-----------|-----------|
| GIS-01 | Peta Dasar | Menampilkan peta dasar wilayah Desa Curah Kalak | High |
| GIS-02 | Layer Kelayakan | Menampilkan layer peta berdasarkan tingkat kelayakan (S1, S2, S3, N) | High |
| GIS-03 | Filter Komoditas | Pengguna dapat memfilter tampilan berdasarkan komoditas | High |
| GIS-04 | Informasi Lahan | Klik pada area peta menampilkan detail informasi lahan | High |
| GIS-05 | Legenda Peta | Menampilkan legenda kode warna tingkat kelayakan | High |
| GIS-06 | Export Peta | Pengguna dapat mengekspor peta ke format gambar | Low |

#### 6.5 Modul Dashboard & Laporan
| ID | Fitur | Deskripsi | Prioritas |
|----|-------|-----------|-----------|
| DB-01 | Dashboard Statistik | Menampilkan ringkasan statistik kelayakan lahan | Medium |
| DB-02 | Grafik Distribusi | Menampilkan grafik distribusi kelayakan per komoditas | Medium |
| DB-03 | Laporan Klasifikasi | Generate laporan hasil klasifikasi dalam format PDF | Low |

---

### 7. Parameter Data Lahan

#### 7.1 Parameter Input (7 Parameter)

| No | Parameter | Satuan | Tipe Data | Keterangan |
|----|-----------|--------|-----------|------------|
| 1 | Kemiringan Lereng | % | Numerik | Memengaruhi risiko erosi dan kemudahan pengolahan |
| 2 | Luas Lahan | Ha | Numerik | Berkaitan dengan efisiensi produksi |
| 3 | pH Tanah | - | Numerik (0-14) | Memengaruhi ketersediaan unsur hara |
| 4 | Jenis Tanah | - | Kategorikal | Menentukan sifat fisik dan kimia tanah |
| 5 | Ketinggian Tempat | mdpl | Numerik | Berpengaruh terhadap suhu dan iklim mikro |
| 6 | Kedalaman Tanah | cm | Numerik | Memengaruhi perkembangan akar |
| 7 | Curah Hujan | mm/tahun | Numerik | Menentukan ketersediaan air |

#### 7.2 Output Klasifikasi

| Kode | Tingkat Kesesuaian | Deskripsi |
|------|-------------------|-----------|
| **S1** | Sangat Sesuai | Lahan tidak memiliki faktor pembatas yang berarti |
| **S2** | Sesuai | Lahan memiliki faktor pembatas ringan |
| **S3** | Cukup Sesuai | Lahan memiliki faktor pembatas sedang |
| **N** | Tidak Sesuai | Lahan memiliki faktor pembatas berat |

#### 7.3 Komoditas yang Dianalisis

| No | Komoditas | Keterangan |
|----|-----------|------------|
| 1 | Tebu | Tanaman perkebunan |
| 2 | Cabai | Tanaman hortikultura |
| 3 | Jagung | Tanaman pangan |
| 4 | Padi | Tanaman pangan utama |

---

### 8. Arsitektur Sistem

#### 8.1 Arsitektur Tingkat Tinggi

```
┌─────────────────────────────────────────────────────────────────┐
│                         CLIENT LAYER                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   Browser   │  │   Mobile    │  │   Desktop Application   │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      PRESENTATION LAYER                          │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              Web Application (PHP + HTML/CSS/JS)         │    │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────────┐  │    │
│  │  │  Login   │ │Dashboard │ │Klasifikasi│ │  Peta GIS  │  │    │
│  │  └──────────┘ └──────────┘ └──────────┘ └────────────┘  │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       BUSINESS LAYER                             │
│  ┌──────────────────┐  ┌──────────────────┐  ┌───────────────┐  │
│  │ Data Preprocessing│  │  KNN Algorithm   │  │ GIS Integration│ │
│  │   (Normalisasi)   │  │(Euclidean Dist.) │  │   (Leaflet)    │ │
│  └──────────────────┘  └──────────────────┘  └───────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         DATA LAYER                               │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    MySQL Database                        │    │
│  │  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌─────────┐  │    │
│  │  │Data Latih │ │ Data Uji  │ │Data Spasial│ │  Users  │  │    │
│  │  └───────────┘ └───────────┘ └───────────┘ └─────────┘  │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

#### 8.2 Alur Proses Klasifikasi KNN

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│  Input Data  │────▶│  Validasi &  │────▶│ Normalisasi  │
│    Lahan     │     │   Cleaning   │     │    Data      │
└──────────────┘     └──────────────┘     └──────────────┘
                                                  │
                                                  ▼
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Hasil      │◀────│  Voting      │◀────│  Hitung      │
│ Klasifikasi  │     │  Mayoritas   │     │  Euclidean   │
│  (S1/S2/S3/N)│     │  (K Nearest) │     │  Distance    │
└──────────────┘     └──────────────┘     └──────────────┘
        │
        ▼
┌──────────────┐     ┌──────────────┐
│ Perbandingan │────▶│  Visualisasi │
│  Komoditas   │     │   Peta GIS   │
└──────────────┘     └──────────────┘
```

#### 8.3 Formula Euclidean Distance

```
D(x,y) = √[Σ(xi - yi)²]   untuk i = 1 sampai n

Keterangan:
D    = Jarak kedekatan
x    = Data testing (uji)
y    = Data training (latih)
n    = Jumlah atribut/parameter (7 parameter)
i    = Indeks atribut
```

---

### 9. Tech Stack

#### 9.1 Frontend
| Komponen | Teknologi | Versi | Keterangan |
|----------|-----------|-------|------------|
| Markup | HTML5 | - | Struktur halaman |
| Styling | CSS3 / Bootstrap | 5.x | Responsive design |
| Scripting | JavaScript | ES6+ | Interaktivitas |
| Peta | Leaflet.js | 1.9.x | Library peta interaktif |
| Charts | Chart.js | 4.x | Visualisasi grafik |

#### 9.2 Backend
| Komponen | Teknologi | Versi | Keterangan |
|----------|-----------|-------|------------|
| Bahasa | PHP | 8.x | Server-side scripting |
| Web Server | Apache | 2.4.x | HTTP server |

#### 9.3 Database
| Komponen | Teknologi | Versi | Keterangan |
|----------|-----------|-------|------------|
| RDBMS | MySQL | 8.x | Penyimpanan data |

#### 9.4 Tools Pendukung
| Komponen | Teknologi | Keterangan |
|----------|-----------|------------|
| IDE | Visual Studio Code | Editor kode |
| GIS Desktop | QGIS | Pengolahan data spasial |
| Diagram | Draw.io | Flowchart dan diagram |
| Modeling | Sparx EA | UML modeling |
| Data Processing | Microsoft Excel | Pengolahan data tabular |
| Altimeter | Aplikasi Mobile | Pengukuran ketinggian |

---

### 10. Struktur Database

#### 10.1 Entity Relationship Diagram (Konseptual)

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│    Users    │       │ Data_Latih  │       │  Data_Uji   │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ id (PK)     │       │ id (PK)     │       │ id (PK)     │
│ username    │       │ kemiringan  │       │ kemiringan  │
│ password    │       │ luas        │       │ luas        │
│ created_at  │       │ ph_tanah    │       │ ph_tanah    │
└─────────────┘       │ jenis_tanah │       │ jenis_tanah │
                      │ ketinggian  │       │ ketinggian  │
                      │ kedalaman   │       │ kedalaman   │
                      │ curah_hujan │       │ curah_hujan │
                      │ kelas_tebu  │       │ latitude    │
                      │ kelas_cabai │       │ longitude   │
                      │ kelas_jagung│       │ created_at  │
                      │ kelas_padi  │       └─────────────┘
                      │ created_at  │              │
                      └─────────────┘              │
                                                   ▼
                                          ┌─────────────┐
                                          │   Hasil     │
                                          │ Klasifikasi │
                                          ├─────────────┤
                                          │ id (PK)     │
                                          │ data_uji_id │
                                          │ kelas_tebu  │
                                          │ kelas_cabai │
                                          │ kelas_jagung│
                                          │ kelas_padi  │
                                          │ rekomendasi │
                                          │ created_at  │
                                          └─────────────┘
```

> **Catatan:** Tabel Users hanya berisi 1 record admin (single-user). Kolom role dihapus karena tidak diperlukan.

#### 10.2 Tabel Users
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed data: Single admin user
INSERT INTO users (username, password, nama_lengkap)
VALUES ('admin', '$2y$10$...', 'Administrator');
```

> **Catatan:** Tabel ini hanya berisi 1 record admin. Kolom role dihapus karena sistem single-user.

#### 10.3 Tabel Data Latih
```sql
CREATE TABLE data_latih (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kemiringan_lereng DECIMAL(5,2) NOT NULL,
    luas_lahan DECIMAL(10,2) NOT NULL,
    ph_tanah DECIMAL(4,2) NOT NULL,
    jenis_tanah VARCHAR(50) NOT NULL,
    ketinggian_tempat INT NOT NULL,
    kedalaman_tanah INT NOT NULL,
    curah_hujan INT NOT NULL,
    kelas_tebu ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_cabai ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_jagung ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_padi ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 10.4 Tabel Data Uji
```sql
CREATE TABLE data_uji (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_lahan VARCHAR(100),
    kemiringan_lereng DECIMAL(5,2) NOT NULL,
    luas_lahan DECIMAL(10,2) NOT NULL,
    ph_tanah DECIMAL(4,2) NOT NULL,
    jenis_tanah VARCHAR(50) NOT NULL,
    ketinggian_tempat INT NOT NULL,
    kedalaman_tanah INT NOT NULL,
    curah_hujan INT NOT NULL,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

> **Catatan:** Kolom user_id dan foreign key dihapus karena sistem single-user (semua data otomatis milik admin).

#### 10.5 Tabel Hasil Klasifikasi
```sql
CREATE TABLE hasil_klasifikasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    data_uji_id INT NOT NULL,
    kelas_tebu ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_cabai ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_jagung ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_padi ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    rekomendasi_komoditas VARCHAR(50),
    nilai_k INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (data_uji_id) REFERENCES data_uji(id)
);
```

---

### 11. User Interface Wireframe

#### 11.1 Halaman Login
```
┌────────────────────────────────────────────────┐
│              SISTEM KLASIFIKASI                │
│           KELAYAKAN LAHAN PERTANIAN            │
├────────────────────────────────────────────────┤
│                                                │
│         ┌──────────────────────────┐           │
│         │       Username           │           │
│         └──────────────────────────┘           │
│         ┌──────────────────────────┐           │
│         │       Password           │           │
│         └──────────────────────────┘           │
│                                                │
│              [ LOGIN ]                         │
│                                                │
└────────────────────────────────────────────────┘
```

#### 11.2 Halaman Dashboard
```
┌────────────────────────────────────────────────────────────────┐
│  LOGO   │ Dashboard │ Data Latih │ Klasifikasi │ Peta │ [Logout]│
├─────────┴───────────┴────────────┴─────────────┴──────┴─────────┤
│                                                                  │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌─────────┐ │
│  │  Total Data  │ │  Total Data  │ │    Lahan     │ │  Lahan  │ │
│  │    Latih     │ │     Uji      │ │   Layak S1   │ │Tidak Layak│
│  │     150      │ │      45      │ │      28      │ │    12   │ │
│  └──────────────┘ └──────────────┘ └──────────────┘ └─────────┘ │
│                                                                  │
│  ┌────────────────────────────┐  ┌────────────────────────────┐ │
│  │                            │  │                            │ │
│  │   GRAFIK DISTRIBUSI        │  │   PETA MINI                │ │
│  │   KELAYAKAN LAHAN          │  │   DESA CURAH KALAK         │ │
│  │                            │  │                            │ │
│  └────────────────────────────┘  └────────────────────────────┘ │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

#### 11.3 Halaman Klasifikasi
```
┌────────────────────────────────────────────────────────────────┐
│  KLASIFIKASI KELAYAKAN LAHAN PERTANIAN                         │
├────────────────────────────────────────────────────────────────┤
│                                                                  │
│  INPUT PARAMETER LAHAN                                          │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │Kemiringan (%)   │  │ Luas Lahan (Ha) │  │ pH Tanah        │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │Jenis Tanah [▼]  │  │Ketinggian (mdpl)│  │Kedalaman (cm)   │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
│  ┌─────────────────┐                                            │
│  │Curah Hujan (mm) │     [ KLASIFIKASI ]                        │
│  └─────────────────┘                                            │
│                                                                  │
│  ─────────────────────────────────────────────────────────────  │
│  HASIL KLASIFIKASI                                              │
│  ┌───────────┬───────────┬───────────┬───────────┐              │
│  │   TEBU    │   CABAI   │   JAGUNG  │    PADI   │              │
│  │    S2     │    S1     │    S1     │    S3     │              │
│  │  Sesuai   │Sangat     │ Sangat    │  Cukup    │              │
│  │           │Sesuai     │ Sesuai    │  Sesuai   │              │
│  └───────────┴───────────┴───────────┴───────────┘              │
│                                                                  │
│  REKOMENDASI: Lahan ini paling cocok untuk tanaman CABAI        │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

#### 11.4 Halaman Peta GIS
```
┌────────────────────────────────────────────────────────────────┐
│  PETA KELAYAKAN LAHAN - DESA CURAH KALAK                       │
├────────────────────────────────────────────────────────────────┤
│  Filter: [Tebu ▼] [Cabai ▼] [Jagung ▼] [Padi ▼] [Semua]        │
├────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                                                          │  │
│  │                    PETA INTERAKTIF                       │  │
│  │                    (LEAFLET.JS)                          │  │
│  │                                                          │  │
│  │     ████████                                             │  │
│  │     ████████   ▓▓▓▓▓▓                                    │  │
│  │                ▓▓▓▓▓▓   ░░░░░░                           │  │
│  │                         ░░░░░░                           │  │
│  │                                    ▒▒▒▒▒▒                │  │
│  │                                    ▒▒▒▒▒▒                │  │
│  │                                                          │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                  │
│  LEGENDA:                                                       │
│  ████ S1 (Sangat Sesuai)    ▓▓▓▓ S2 (Sesuai)                   │
│  ░░░░ S3 (Cukup Sesuai)     ▒▒▒▒ N (Tidak Sesuai)              │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

### 12. Non-Functional Requirements

#### 12.1 Performa
| Requirement | Target |
|-------------|--------|
| Response Time | < 3 detik untuk klasifikasi |
| Load Time Peta | < 5 detik |
| Concurrent Users | Minimal 50 pengguna |
| Database Query | < 1 detik |

#### 12.2 Keamanan
| Requirement | Implementasi |
|-------------|--------------|
| Autentikasi | Username & password dengan hashing (single admin account) |
| Session Management | Session timeout 30 menit |
| Input Validation | Server-side validation untuk semua input |
| SQL Injection Prevention | Prepared statements |

> **Catatan:** Sistem tidak memerlukan role-based access control (RBAC) karena hanya ada 1 user (admin) yang memiliki akses penuh ke semua fitur.

#### 12.3 Usability
| Requirement | Target |
|-------------|--------|
| Browser Support | Chrome, Firefox, Edge, Safari (versi terbaru) |
| Responsive Design | Support desktop dan mobile |
| Bahasa | Bahasa Indonesia |
| Accessibility | Warna kontras tinggi untuk peta |

#### 12.4 Reliability
| Requirement | Target |
|-------------|--------|
| Uptime | 99% |
| Data Backup | Harian |
| Error Handling | Graceful error messages |

---

### 13. Acceptance Criteria

#### 13.1 Kriteria Sukses Sistem
1. Sistem dapat melakukan klasifikasi dengan akurasi minimal 85%
2. Hasil klasifikasi konsisten untuk input yang sama
3. Peta GIS menampilkan data dengan benar sesuai koordinat
4. Semua fitur CRUD berfungsi dengan baik
5. Sistem dapat diakses melalui berbagai browser

#### 13.2 Kriteria Validasi Algoritma KNN
1. Perhitungan Euclidean Distance menghasilkan nilai yang akurat
2. Proses normalisasi data berjalan dengan benar
3. Voting mayoritas K tetangga terdekat sesuai standar
4. Hasil klasifikasi sesuai dengan data latih yang valid

#### 13.3 Kriteria Validasi GIS
1. Koordinat lahan ditampilkan pada posisi yang benar
2. Layer peta dapat di-toggle sesuai komoditas
3. Legenda warna sesuai dengan tingkat kelayakan
4. Popup informasi menampilkan data yang akurat

---

### 14. Risiko dan Mitigasi

| No | Risiko | Dampak | Probabilitas | Mitigasi |
|----|--------|--------|--------------|----------|
| 1 | Data latih tidak mencukupi | Akurasi klasifikasi rendah | Medium | Kumpulkan data dari berbagai sumber valid |
| 2 | Parameter tidak lengkap | Hasil tidak akurat | Low | Validasi input wajib di frontend dan backend |
| 3 | Performa lambat dengan data besar | User experience buruk | Medium | Optimasi query dan implementasi caching |
| 4 | Data spasial tidak akurat | Peta tidak representatif | Low | Verifikasi koordinat dengan GPS |
| 5 | Browser compatibility issue | Beberapa user tidak bisa akses | Low | Testing cross-browser dan graceful degradation |

---

### 15. Timeline Pengembangan

| Fase | Kegiatan | Durasi |
|------|----------|--------|
| **Fase 1** | Pengumpulan referensi & penyusunan proposal | Minggu 1-2 |
| **Fase 2** | Pengumpulan data parameter lahan | Minggu 2-3 |
| **Fase 3** | Pra-pemrosesan dan normalisasi data | Minggu 3-4 |
| **Fase 4** | Implementasi algoritma KNN | Minggu 4-5 |
| **Fase 5** | Integrasi hasil klasifikasi dengan GIS | Minggu 5 |
| **Fase 6** | Pengembangan aplikasi web | Minggu 5-6 |
| **Fase 7** | Testing dan debugging | Minggu 6 |
| **Fase 8** | Analisis hasil dan dokumentasi | Minggu 6 |

---

### 16. Referensi

1. Artikanur, M. S. D., Widiatmaka, & Setiawan, Y. (2023). An Evaluation of Possible Sugarcane Plantations Expansion Areas in Lamongan, East Java, Indonesia.
2. Zilvanhisna, A., Irawan, A. D., & Madjid, A. (2025). Implementation of Decision Support System for Analyzing the Suitability of Plantation Crops.
3. Setianto, W., et al. (2023). Klasifikasi Stunting Dengan KNN Di Kota Pekalongan Berbasis Sistem Informasi Geografis.
4. Najib, A. Z., Achmadi, S., & Auliasari, K. (2024). Sistem Klasifikasi Data Penduduk Untuk Menentukan TPS Dengan Metode KNN.
5. Rudiyan, A., Dzulkifli, A. E., & Munazar, K. (2022). Klasifikasi Kebakaran Hutan Menggunakan Metode K-Nearest Neighbor.

---

### 17. Approval

| Role | Nama | Tanggal | Tanda Tangan |
|------|------|---------|--------------|
| **Project Owner** | Moh. Alfian Husni Mubarok | __________ | ____________ |
| **Pembimbing 1** | A. Hamdani, M.Kom. | __________ | ____________ |
| **Pembimbing 2** | Adi Susanto, M.Kom. | __________ | ____________ |
| **Ketua Prodi** | Firman Santoso, M.Kom. | __________ | ____________ |

---

*Dokumen ini dibuat berdasarkan Proposal Skripsi "Klasifikasi Kelayakan Lahan Pertanian Menggunakan Metode KNN (K-Nearest Neighbor) Berbasis GIS" oleh Moh. Alfian Husni Mubarok, Program Studi Teknologi Informasi, Universitas Ibrahimy Situbondo, 2026.*
