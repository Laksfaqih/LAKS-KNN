CREATE DATABASE IF NOT EXISTS klasifikasi_lahan;
USE klasifikasi_lahan;

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    kelas_tebu ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_cabai ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_jagung ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    kelas_padi ENUM('S1', 'S2', 'S3', 'N') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS hasil_klasifikasi (
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
        ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO users (username, password, nama_lengkap)
VALUES ('admin', '$2y$10$LDUcKg9G.dlwGciIj30MB.9ZpkB.Cim4sBpreeNsW724YZo9sIG/W', 'Administrator')
ON DUPLICATE KEY UPDATE username = username;
