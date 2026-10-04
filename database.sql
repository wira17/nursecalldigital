-- =====================================================================
--  NURSE CALL DIGITAL — QUERY TAMBAH TABEL KE DATABASE SIMRS KHANZA
--  (c) 2026 M. Wira Satria Buana · www.fixdigitech.com · 0821-7784-6209
--
--  CARA PAKAI (cukup copy–paste):
--    1. Buka phpMyAdmin, klik DATABASE KHANZA Anda di kiri (mis. sik / sikrsph).
--    2. Klik tab SQL.
--    3. Copy SELURUH isi file ini (Ctrl/Cmd + A, lalu Ctrl/Cmd + C), tempel di kotak SQL.
--    4. Klik Go. Selesai — 6 tabel nc_..._wira terbentuk.
--
--  Catatan:
--  - Langkah ini OPSIONAL: aplikasi juga membuat tabel ini otomatis saat pertama dibuka.
--  - Aman dijalankan berulang kali (CREATE TABLE IF NOT EXISTS / INSERT IGNORE), data lama tidak hilang.
--  - Hanya MENAMBAH tabel berawalan nc_ dan berakhiran _wira. Tabel & data Khanza TIDAK diubah sama sekali
--    (tanpa ALTER, foreign key, trigger, atau view pada tabel Khanza).
--
--  Tabel yang ditambahkan:
--    1. nc_user_wira       role (admin/user) & ruang tugas pegawai
--    2. nc_gelang_wira     QR gelang pasien per no_rawat
--    3. nc_keluhan_wira    pilihan keluhan di HP pasien
--    4. nc_panggilan_wira  riwayat panggilan perawat
--    5. nc_codeblue_wira   riwayat alarm Code Blue & Code Red
--    6. nc_setting_wira    pengaturan aplikasi
--
--  Tabel Khanza yang hanya DIBACA: user, petugas, dokter, setting, pasien, reg_periksa, kamar_inap,
--    kamar, bangsal, poliklinik, penjab, dpjp_ranap, pemeriksaan_ranap.
-- =====================================================================
SET NAMES utf8mb4;

-- Pengguna Nurse Call: pegawai Khanza + role di Nurse Call
-- role: admin (semua menu) | user (Dashboard / nurse station, Admisi, Rawat Inap, cetak gelang)
CREATE TABLE IF NOT EXISTS nc_user_wira (
  id_user VARCHAR(30) CHARACTER SET latin1 NOT NULL PRIMARY KEY,  -- NIK / id_user Khanza (= petugas.nip / dokter.kd_dokter)
  nama VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  kd_bangsal CHAR(5) CHARACTER SET latin1 DEFAULT NULL,          -- ruang tugas (bangsal Khanza) = filter awal nurse station
  no_hp VARCHAR(20) DEFAULT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- QR Code gelang per rawat inap (no_rawat). Saat pasien pulang / batal di Khanza, kolom nonaktif terisi dan QR tidak berlaku lagi (permanen).
CREATE TABLE IF NOT EXISTS nc_gelang_wira (
  no_rawat VARCHAR(17) CHARACTER SET latin1 NOT NULL PRIMARY KEY,
  token CHAR(24) CHARACTER SET latin1 NOT NULL UNIQUE,
  dibuat DATETIME NOT NULL,
  dibuat_oleh VARCHAR(30) CHARACTER SET latin1 DEFAULT NULL,
  dicetak INT NOT NULL DEFAULT 0,
  nonaktif DATETIME DEFAULT NULL                                   -- diisi otomatis saat pasien pulang / batal: QR ditolak permanen
) ENGINE=InnoDB;

-- Pilihan keluhan / permintaan di HP pasien
CREATE TABLE IF NOT EXISTS nc_keluhan_wira (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(60) CHARACTER SET utf8mb4 NOT NULL,
  ikon VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL DEFAULT '🔔',
  prioritas ENUM('tinggi','sedang','rendah') NOT NULL DEFAULT 'sedang',
  urut INT NOT NULL DEFAULT 0,
  aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Panggilan perawat
CREATE TABLE IF NOT EXISTS nc_panggilan_wira (
  id INT AUTO_INCREMENT PRIMARY KEY,
  no_rawat VARCHAR(17) CHARACTER SET latin1 NOT NULL,
  no_rkm_medis VARCHAR(15) CHARACTER SET latin1 NOT NULL,
  kd_bangsal CHAR(5) CHARACTER SET latin1 NOT NULL,         -- ruang saat memanggil
  kd_kamar VARCHAR(15) CHARACTER SET latin1 NOT NULL,       -- kamar / bed saat memanggil
  keluhan_id INT DEFAULT NULL,
  keluhan VARCHAR(60) CHARACTER SET utf8mb4 NOT NULL,
  prioritas ENUM('tinggi','sedang','rendah') NOT NULL DEFAULT 'sedang',
  pesan VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  audio VARCHAR(80) CHARACTER SET latin1 DEFAULT NULL,      -- file pesan suara (uploads/suara/)
  durasi_audio SMALLINT DEFAULT NULL,
  pelapor ENUM('pasien','keluarga') NOT NULL DEFAULT 'pasien',
  status ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  waktu DATETIME NOT NULL,
  waktu_respon DATETIME DEFAULT NULL,
  waktu_selesai DATETIME DEFAULT NULL,
  perawat VARCHAR(30) CHARACTER SET latin1 DEFAULT NULL,    -- id_user yang menangani
  selesai_oleh VARCHAR(30) CHARACTER SET latin1 DEFAULT NULL,
  tindakan VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  INDEX idx_status (status, kd_bangsal),
  INDEX idx_waktu (waktu),
  INDEX idx_rawat (no_rawat)
) ENGINE=InnoDB;

-- Code Blue & Code Red: alarm darurat ke semua nurse station & Display TV
-- kode: 'blue' = henti jantung/napas, 'red' = kebakaran/asap
CREATE TABLE IF NOT EXISTS nc_codeblue_wira (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode ENUM('blue','red') NOT NULL DEFAULT 'blue',
  kd_kamar VARCHAR(15) CHARACTER SET latin1 DEFAULT NULL,     -- kamar Khanza (kosong jika lokasi lain)
  kd_bangsal CHAR(5) CHARACTER SET latin1 DEFAULT NULL,
  lokasi VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL,          -- teks lokasi yang ditampilkan / diumumkan
  no_rawat VARCHAR(17) CHARACTER SET latin1 DEFAULT NULL,      -- pasien di kamar saat itu (jika ada)
  pasien VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  status ENUM('aktif','selesai') NOT NULL DEFAULT 'aktif',
  waktu DATETIME NOT NULL,
  oleh VARCHAR(30) CHARACTER SET latin1 DEFAULT NULL,          -- NIK yang mengaktifkan
  waktu_selesai DATETIME DEFAULT NULL,
  selesai_oleh VARCHAR(30) CHARACTER SET latin1 DEFAULT NULL,
  catatan VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  INDEX idx_cb_kode (kode),
  INDEX idx_cb_status (status),
  INDEX idx_cb_waktu (waktu)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nc_setting_wira (
  k VARCHAR(50) CHARACTER SET latin1 NOT NULL PRIMARY KEY,
  v TEXT CHARACTER SET utf8mb4
) ENGINE=InnoDB;

-- =========================================================
--  DATA AWAL
-- =========================================================
INSERT IGNORE INTO nc_setting_wira (k, v) VALUES
('nama_rs', ''),              -- kosong = otomatis dari tabel setting Khanza (nama_instansi)
('url_publik', ''),           -- kosong = otomatis. Isi mis. https://rs-anda.id/nursecall/ agar QR bisa dibuka dari HP pasien
('jeda_lapor', '60'),         -- detik minimal antar laporan dari 1 pasien
('batas_respon', '5'),        -- menit; panggilan belum ditangani lebih dari ini ditandai terlambat
('suara_aktif', '1'),         -- 1 = pasien boleh mengirim pesan suara
('suara_maks', '60'),         -- durasi maksimal pesan suara (detik)
('tema_warna', '#3b7a67'),
('login_bg', ''),
('login_overlay', '55'),
('login_judul', 'Nurse Call Digital'),
('login_teks', 'Layanan cepat, pasien lebih nyaman.'),
('display_samarkan', '0'),
('display_suara', '1'),
('display_teks', 'Jaga kebersihan tangan · Pasien & keluarga dapat memanggil perawat dengan scan QR Code pada gelang');
INSERT IGNORE INTO nc_setting_wira (k, v) SELECT 'display_key', SUBSTRING(SHA2(CONCAT(RAND(), NOW(), UUID()), 256), 1, 24);

INSERT INTO nc_keluhan_wira (nama, ikon, prioritas, urut)
SELECT * FROM (
  SELECT 'Sakit / Nyeri' AS nama, '🤕' AS ikon, 'tinggi' AS prioritas, 1 AS urut UNION ALL
  SELECT 'Sesak Napas', '😮‍💨', 'tinggi', 2 UNION ALL
  SELECT 'Infus Macet / Habis', '💧', 'tinggi', 3 UNION ALL
  SELECT 'Mual / Muntah', '🤢', 'sedang', 4 UNION ALL
  SELECT 'Pusing', '😵', 'sedang', 5 UNION ALL
  SELECT 'Butuh Bantuan ke Toilet', '🚻', 'rendah', 6 UNION ALL
  SELECT 'Minta Minum / Makan', '🥤', 'rendah', 7 UNION ALL
  SELECT 'Lainnya', '💬', 'sedang', 99
) x WHERE NOT EXISTS (SELECT 1 FROM nc_keluhan_wira);

-- =====================================================================
--  KHUSUS YANG SUDAH PERNAH PASANG VERSI LAMA (sebelum ada Code Red)
--  Biasanya TIDAK perlu: aplikasi menambah kolom ini otomatis saat dibuka.
--  Jika ingin manual, jalankan SEKALI baris di bawah (hapus tanda "-- " di depannya).
--  Jika muncul "Duplicate column name 'kode'", artinya kolom sudah ada — abaikan.
-- =====================================================================
-- ALTER TABLE nc_codeblue_wira ADD COLUMN kode ENUM('blue','red') NOT NULL DEFAULT 'blue' AFTER id, ADD INDEX idx_cb_kode (kode);