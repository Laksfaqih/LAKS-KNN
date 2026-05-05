# Sistem Klasifikasi Kelayakan Lahan

Aplikasi web berbasis `CodeIgniter 4` untuk klasifikasi kelayakan lahan pertanian menggunakan `KNN` dan visualisasi peta.

## Fitur Utama

- Login admin
- Master `Kriteria/Komoditas`
- Master `Nilai Kriteria`
- Manajemen `Data Latih`
- Import dan export `Data Latih` CSV
- Klasifikasi lahan berbasis `KNN`
- Input area lahan menggunakan poligon pada peta
- Mapping hasil rekomendasi komoditas
- Dashboard statistik dan riwayat klasifikasi

## Kebutuhan Sistem

- PHP `>= 8.2`
- Composer
- MySQL / MariaDB
- Ekstensi PHP:
  - `intl`
  - `mbstring`
  - `json`
  - `mysqli` / `mysqlnd`

## Instalasi

1. Clone repository lalu masuk ke folder project

```bash
git clone <url-repository> kalasifikasi
cd kalasifikasi
```

2. Install dependency PHP

```bash
composer install
```

3. Siapkan file environment

```bash
cp env .env
```

4. Edit file `.env`, lalu sesuaikan minimal bagian berikut:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = klasifikasi_lahan
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

5. Buat database baru di MySQL

```sql
CREATE DATABASE klasifikasi_lahan;
```

6. Jalankan migration

```bash
php spark migrate
```

7. Seed data admin default

```bash
php spark db:seed DatabaseSeeder
```

8. Jalankan server development

```bash
php spark serve
```

9. Buka aplikasi di browser

```text
http://localhost:8080
```

## Login Default

- Username: `admin`
- Password: `admin123`

Segera ganti password admin setelah instalasi pertama jika aplikasi akan dipakai di lingkungan nyata.

## Menjalankan Ulang Database Dari Nol

Jika ingin reset database lokal:

```bash
php spark migrate:refresh
php spark db:seed DatabaseSeeder
```

Catatan:
- `migrate:refresh` akan menghapus seluruh tabel hasil migration lalu membuat ulang dari awal.
- Jangan jalankan ini di database produksi tanpa backup.

## Struktur Data Awal

Setelah migration berjalan, aplikasi akan menyiapkan struktur utama berikut:

- `users`
- `data_latih`
- `data_uji`
- `hasil_klasifikasi`
- `komoditas`
- `nilai_kriteria`
- `hasil_klasifikasi_detail`

Migration juga akan menambahkan komoditas awal:

- `Tebu`
- `Jagung`
- `Cabe`
- `Padi`

## Import CSV Data Latih

Import CSV dilakukan dari halaman `Data Latih`.

Format header CSV sudah tetap untuk model data latih baru. Contoh:

```csv
kemiringan_lereng,luas_lahan,ph_tanah,jenis_tanah,ketinggian_tempat,kedalaman_tanah,curah_hujan,jenis_tanaman,latitude,longitude
```

Nilai `jenis_tanaman` memakai nama komoditas aktif. Gunakan fitur `Export CSV` di halaman `Data Latih` untuk mendapatkan template paling aman sesuai kondisi master komoditas saat ini.

## Alternatif Import SQL Lama

Repository ini masih memiliki file `sql/schema.sql`, tetapi untuk kondisi aplikasi terbaru disarankan memakai:

```bash
php spark migrate
php spark db:seed DatabaseSeeder
```

Alasannya:
- migration mencakup struktur terbaru aplikasi
- schema lama belum tentu memuat seluruh perubahan fase terbaru

## Perintah Berguna

Menjalankan server:

```bash
php spark serve
```

Melihat daftar route:

```bash
php spark routes
```

Cek syntax PHP file tertentu:

```bash
php -l app/Controllers/Klasifikasi.php
```

Menjalankan test:

```bash
composer test
```

## Troubleshooting

`Database tidak terkoneksi`
- pastikan MySQL aktif
- periksa isi `.env`
- pastikan database `klasifikasi_lahan` sudah dibuat

`php spark migrate` gagal
- pastikan credential DB benar
- pastikan user MySQL punya izin membuat/mengubah tabel
- pastikan versi PHP minimal `8.2`

`Halaman CSS/asset tidak tampil`
- pastikan `app.baseURL` di `.env` sesuai URL akses
- jika pakai server selain `php spark serve`, arahkan document root ke folder `public`

`Login gagal`
- jalankan ulang seed admin:

```bash
php spark db:seed DatabaseSeeder
```

## Catatan Deployment

- arahkan web server ke folder `public`
- gunakan `CI_ENVIRONMENT = production` di server produksi
- nonaktifkan debug di produksi
- ganti password admin default
- backup database sebelum migration lanjutan

## Stack

- `CodeIgniter 4`
- `PHP 8.2+`
- `MySQL`
- `Leaflet`
- `Chart.js`
