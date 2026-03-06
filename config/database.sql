CREATE DATABASE IF NOT EXISTS db_masyanto;
USE db_masyanto;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'karyawan') NOT NULL DEFAULT 'karyawan',
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nomor_hp VARCHAR(20)
);

CREATE TABLE IF NOT EXISTS ketersediaan_karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hari ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    bukti_kuliah VARCHAR(255),
    keterangan TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS jadwal_shift (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    status ENUM('aktif', 'tukar', 'selesai', 'libur') DEFAULT 'aktif',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    jadwal_id INT,
    waktu_check_in DATETIME,
    waktu_check_out DATETIME,
    status ENUM('hadir', 'telat', 'alpha', 'izin') DEFAULT 'hadir',
    bukti_foto VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal_shift(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS izin_karyawan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    alasan TEXT NOT NULL,
    status ENUM('pending', 'disetujui', 'ditolak') DEFAULT 'pending',
    bukti_izin VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tukar_jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengaju_id INT NOT NULL,
    penerima_id INT NOT NULL,
    jadwal_pengaju_id INT NOT NULL,
    jadwal_penerima_id INT NOT NULL,
    status ENUM('pending_karyawan', 'pending_admin', 'disetujui', 'ditolak') DEFAULT 'pending_karyawan',
    alasan TEXT,
    FOREIGN KEY (pengaju_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (penerima_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (jadwal_pengaju_id) REFERENCES jadwal_shift(id) ON DELETE CASCADE,
    FOREIGN KEY (jadwal_penerima_id) REFERENCES jadwal_shift(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS penilaian_kinerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bulan VARCHAR(7) NOT NULL, -- Format YYYY-MM
    nilai_kerajinan INT CHECK (nilai_kerajinan BETWEEN 1 AND 100),
    nilai_sikap INT CHECK (nilai_sikap BETWEEN 1 AND 100),
    catatan TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS penggajian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bulan VARCHAR(7) NOT NULL, -- Format YYYY-MM
    nominal DECIMAL(10, 2) NOT NULL,
    bukti_transfer VARCHAR(255),
    tanggal_kirim DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert Default Admin
INSERT IGNORE INTO users (role, nama, username, password, nomor_hp) 
VALUES ('admin', 'Admin SDM', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890'); -- password: password
