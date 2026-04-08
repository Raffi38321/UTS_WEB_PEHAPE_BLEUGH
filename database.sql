-- Database: booking_lapangan
CREATE DATABASE IF NOT EXISTS booking_lapangan;
USE booking_lapangan;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lapangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    harga_per_jam DECIMAL(10,2) NOT NULL,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif'
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lapangan_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    durasi INT NOT NULL COMMENT 'dalam jam',
    total_harga DECIMAL(10,2) NOT NULL,
    status ENUM('pending','dikonfirmasi','ditolak','selesai') DEFAULT 'pending',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (lapangan_id) REFERENCES lapangan(id)
);

CREATE TABLE notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    pesan TEXT NOT NULL,
    dibaca TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

-- Seed data
INSERT INTO users (nama, email, password, role) VALUES
('Admin', 'admin@booking.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- password: password

INSERT INTO lapangan (nama, deskripsi, harga_per_jam) VALUES
('Lapangan Futsal A', 'Lapangan futsal indoor dengan rumput sintetis', 100000),
('Lapangan Badminton 1', 'Lapangan badminton standar internasional', 75000),
('Lapangan Basket', 'Lapangan basket outdoor', 80000);
