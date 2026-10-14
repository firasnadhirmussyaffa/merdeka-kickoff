CREATE DATABASE IF NOT EXISTS merdeka_kickoff
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE merdeka_kickoff;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pelanggan') NOT NULL DEFAULT 'pelanggan',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lapangan (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    deskripsi VARCHAR(500) NULL,
    harga_per_jam INT UNSIGNED NOT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    lapangan_id INT UNSIGNED NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    durasi_jam TINYINT UNSIGNED NOT NULL,
    total_harga INT UNSIGNED NOT NULL,
    status ENUM('menunggu', 'dikonfirmasi', 'sedang_bermain', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu',
    keterangan VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_bookings_slot (lapangan_id, tanggal, jam_mulai),
    INDEX idx_bookings_user (user_id),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_lapangan FOREIGN KEY (lapangan_id)
        REFERENCES lapangan(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO lapangan (nama, deskripsi, harga_per_jam, status)
SELECT 'Lapangan Merdeka 1', 'Lapangan mini soccer rumput sintetis untuk latihan dan pertandingan.', 250000, 'aktif'
WHERE NOT EXISTS (SELECT 1 FROM lapangan WHERE nama = 'Lapangan Merdeka 1');

INSERT INTO lapangan (nama, deskripsi, harga_per_jam, status)
SELECT 'Lapangan Merdeka 2', 'Pilihan lapangan yang nyaman untuk bermain bersama teman dan keluarga.', 200000, 'aktif'
WHERE NOT EXISTS (SELECT 1 FROM lapangan WHERE nama = 'Lapangan Merdeka 2');
