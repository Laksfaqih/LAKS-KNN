CREATE DATABASE IF NOT EXISTS klasifikasi_lahan;
USE klasifikasi_lahan;

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS komoditas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    warna_peta VARCHAR(20) NOT NULL,
    urutan_tampil INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS data_latih (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kemiringan_lereng DECIMAL(5,2) NOT NULL,
    luas_lahan DECIMAL(10,2) NOT NULL,
    ph_tanah DECIMAL(4,2) NOT NULL,
    jenis_tanah VARCHAR(50) NOT NULL,
    ketinggian_tempat INT NOT NULL,
    kedalaman_tanah INT NOT NULL,
    curah_hujan INT NOT NULL,
    komoditas_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (komoditas_id) REFERENCES komoditas(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS data_uji (
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
    polygon_geojson LONGTEXT,
    geometry_source VARCHAR(30),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS nilai_kriteria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    komoditas_id INT UNSIGNED NOT NULL,
    kelas VARCHAR(2) NOT NULL,
    kemiringan_min DECIMAL(5,2),
    kemiringan_max DECIMAL(5,2),
    luas_min DECIMAL(10,2),
    luas_max DECIMAL(10,2),
    ph_min DECIMAL(4,2),
    ph_max DECIMAL(4,2),
    ketinggian_min INT,
    ketinggian_max INT,
    kedalaman_min INT,
    kedalaman_max INT,
    curah_hujan_min INT,
    curah_hujan_max INT,
    jenis_tanah_json LONGTEXT,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY nilai_kriteria_komoditas_kelas_unique (komoditas_id, kelas),
    FOREIGN KEY (komoditas_id) REFERENCES komoditas(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS hasil_klasifikasi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    data_uji_id INT UNSIGNED NOT NULL,
    rekomendasi_komoditas VARCHAR(50),
    komoditas_rekomendasi_id INT UNSIGNED NULL,
    nilai_k INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (data_uji_id) REFERENCES data_uji(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (komoditas_rekomendasi_id) REFERENCES komoditas(id)
        ON DELETE CASCADE ON UPDATE SET NULL
);

CREATE TABLE IF NOT EXISTS hasil_klasifikasi_detail (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hasil_klasifikasi_id INT UNSIGNED NOT NULL,
    komoditas_id INT UNSIGNED NOT NULL,
    kelas ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY hasil_klasifikasi_komoditas_unique (hasil_klasifikasi_id, komoditas_id),
    FOREIGN KEY (hasil_klasifikasi_id) REFERENCES hasil_klasifikasi(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (komoditas_id) REFERENCES komoditas(id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO users (username, password, nama_lengkap)
VALUES ('admin', '$2y$10$LDUcKg9G.dlwGciIj30MB.9ZpkB.Cim4sBpreeNsW724YZo9sIG/W', 'Administrator')
ON DUPLICATE KEY UPDATE username = username;
