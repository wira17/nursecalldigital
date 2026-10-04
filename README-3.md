# Nurse Call Digital untuk SIMRS Khanza

Sistem panggilan perawat berbasis web (PHP + MySQL/MariaDB + PWA) yang terhubung **langsung ke database SIMRS Khanza**.
Pasien atau keluarga cukup **scan QR Code di gelang** memakai kamera HP, memilih keluhan atau mengirim pesan suara,
lalu perawat langsung menerima **notifikasi, alarm, dan pengumuman suara** di komputer/HP nurse station serta di **layar TV** ruang perawatan.

> **Aplikasi ini GRATIS** untuk rumah sakit dan fasilitas kesehatan pengguna SIMRS Khanza.
> Baca bagian [Ketentuan Penggunaan](#ketentuan-penggunaan) sebelum memakai atau membagikan aplikasi ini.

---

## Daftar Isi

1. [Pengembang & Dukungan](#pengembang--dukungan)
2. [Ketentuan Penggunaan](#ketentuan-penggunaan)
3. [Fitur](#fitur)
4. [Alur Kerja](#alur-kerja)
5. [Kebutuhan Sistem](#kebutuhan-sistem)
6. [Instalasi](#instalasi)
7. [Konfigurasi `config.php`](#konfigurasi-configphp)
8. [Tabel yang Ditambahkan ke Database Khanza](#tabel-yang-ditambahkan-ke-database-khanza)
9. [Tabel Khanza yang Dipakai (Hanya Dibaca)](#tabel-khanza-yang-dipakai-hanya-dibaca)
10. [Pengguna & Hak Akses](#pengguna--hak-akses)
11. [Panduan Pemakaian](#panduan-pemakaian)
12. [Gelang & Label QR](#gelang--label-qr)
13. [Display TV](#display-tv)
14. [Pengaturan Aplikasi](#pengaturan-aplikasi)
15. [Keamanan](#keamanan)
16. [Struktur Folder](#struktur-folder)
17. [Pemecahan Masalah](#pemecahan-masalah)
18. [Mencopot Aplikasi](#mencopot-aplikasi)

---

## Pengembang & Dukungan

| | |
|---|---|
| **Pengembang** | M. Wira Satria Buana |
| **Website** | [www.fixdigitech.com](https://www.fixdigitech.com) |
| **WhatsApp / Telepon** | 0821-7784-6209 |

Aplikasi ini saya buat dan bagikan secara gratis sebagai kontribusi untuk peningkatan mutu pelayanan keperawatan
di rumah sakit pengguna SIMRS Khanza. Bagi Bapak/Ibu yang ingin berdonasi atau berpartisipasi dalam pengembangan
aplikasi ini, dukungan dapat disalurkan melalui:

| Metode | Nomor | Atas nama |
|---|---|---|
| **Bank Syariah Indonesia (BSI)** | `7134197557` | M. Wira Satria Buana |
| **GoPay** | `082177846209` | M. Wira Satria Buana |

Terima kasih atas dukungan dan kepercayaan Bapak/Ibu. 🙏

Informasi ini juga tersedia di dalam aplikasi melalui menu **Tentang Aplikasi**.

---

## Ketentuan Penggunaan

1. Aplikasi ini disediakan **secara gratis** bagi rumah sakit dan fasilitas kesehatan pengguna SIMRS Khanza.
2. Aplikasi ini **DILARANG diperjualbelikan, disewakan, atau dimanfaatkan untuk mencari keuntungan pribadi** dalam bentuk apa pun,
   termasuk dijual kembali sebagai produk sendiri, dijadikan paket berbayar, atau dipungut biaya pemasangan/lisensi atas aplikasi ini.
3. Aplikasi boleh dipakai, dipasang, dan disesuaikan untuk kebutuhan internal rumah sakit/fasilitas kesehatan Anda sendiri.
4. Saat membagikan aplikasi ini kepada pihak lain, **cantumkan nama pengembang** dan jangan menghapus informasi hak cipta,
   menu **Tentang Aplikasi**, maupun teks *Powered by www.fixdigitech.com* pada aplikasi.
5. Aplikasi disediakan **"sebagaimana adanya"**. Pengujian, pencadangan (backup) database, serta keamanan server menjadi tanggung jawab
   masing-masing rumah sakit. Lakukan uji coba terlebih dahulu sebelum dipakai di layanan sesungguhnya.
6. Aplikasi ini adalah **alat bantu komunikasi** dan **tidak menggantikan** bel pasien, prosedur keselamatan pasien,
   maupun pengawasan langsung oleh perawat.

© 2026 Nurse Call Digital — M. Wira Satria Buana · [www.fixdigitech.com](https://www.fixdigitech.com) · 0821-7784-6209

---

## Fitur

**Untuk pasien / keluarga** (tanpa login, cukup scan QR di gelang)
- Memilih keluhan/permintaan (Sakit/Nyeri, Sesak Napas, Infus Macet, Pusing, Lainnya, dan lainnya) dengan keterangan tambahan.
- Mengirim **pesan suara**: tekan ikon mikrofon, bicara, lalu kirim ke perawat.
- Memantau status laporan: *menunggu → ditangani oleh (nama perawat) → selesai*.

**Untuk perawat (nurse station)**
- Notifikasi **real-time**, pop-up alarm, bunyi bel, dan **pengumuman suara**, misalnya:
  *"Panggilan perawat. Aster 3A. Atas nama Yuli Lestari. Dengan laporan Pusing."*
- Tombol **Tangani** dan **Selesai** dengan catatan tindakan, serta putar pesan suara pasien.
- Daftar panggilan, laporan waktu respon per ruang/per perawat, dan export Excel.

**Untuk admisi / pendaftaran**
- Menu **Admisi** (data `reg_periksa`) dan **Rawat Inap** (data `kamar_inap`) langsung dari Khanza.
- Tombol **Buat Gelang** menampilkan pop-up label QR siap cetak dalam **3 ukuran** (hitam-putih untuk kertas stiker).

**Code Blue**
- Menu **Code Blue** berisi pemetaan semua kamar dari Khanza (per ruang, lengkap dengan nama pasien di kamar).
- Klik kamar (atau isi *lokasi lain*, mis. Lobi IGD) → konfirmasi → **layar biru berkedip, sirene, dan pengumuman
  "Code blue di …"** di **semua** nurse station & Display TV, berulang sampai ditandai **selesai**.
- Riwayat Code Blue tersimpan (waktu, lokasi, pasien, yang mengaktifkan & menyelesaikan, durasi).

**Code Red (kebakaran / asap)**
- Menu **Code Red** memakai pemetaan kamar yang sama, ditambah *lokasi lain* (mis. Dapur Gizi, Gudang Farmasi, Ruang Panel Listrik).
- Klik kamar / lokasi → konfirmasi → **layar merah berkedip, sirene alarm kebakaran, dan pengumuman
  "Code red di …, tim pemadam segera ke lokasi, lakukan evakuasi sesuai prosedur"** di **semua** nurse station & Display TV.
- Halaman Code Red menampilkan pengingat prosedur **R.A.C.E.** (Rescue, Alarm, Confine, Extinguish/Evacuate).
- Bila Code Blue & Code Red aktif bersamaan, **Code Red didahulukan** di layar; kode lain tampil di baris "Juga aktif".
- Di halaman Code Blue / Code Red alarm tampil sebagai bar di bawah, sehingga petugas tetap bisa mengaktifkan kode lain.
- Code Blue & Code Red disimpan di tabel yang sama (`nc_codeblue_wira`), dibedakan kolom `kode` (`blue` / `red`).

**Display TV**
- Layar besar berisi daftar pasien per bed dan panggilan aktif, dengan pengumuman layar penuh dan suara.

**Lainnya**
- Login memakai **NIK & password SIMRS Khanza**, tanpa membuat akun baru.
- **PWA**: bisa dipasang di HP/komputer seperti aplikasi.
- Warna tema dan halaman login (foto latar) dapat diubah.
- Gelang **otomatis nonaktif permanen** saat pasien pulang, untuk mencegah penyalahgunaan.

---

## Alur Kerja

```
Pasien terdaftar di Khanza (admisi / IGD)
        │
        ▼
Petugas klik "Buat Gelang" → cetak label QR → pasang di gelang / bed
        │
        ▼
Pasien masuk kamar rawat inap di Khanza → QR otomatis AKTIF
        │
        ▼
Pasien/keluarga scan QR → pilih keluhan / kirim pesan suara
        │
        ▼
Nurse station & Display TV: alarm + pengumuman suara (± 1–2 detik)
        │
        ▼
Perawat klik "Tangani" → datang ke pasien → "Selesai" + catatan tindakan
        │
        ▼
Pasien pulang di Khanza → gelang NONAKTIF permanen, panggilan terbuka ditutup otomatis
```

---

## Kebutuhan Sistem

| Komponen | Keterangan |
|---|---|
| **PHP** | 7.1 atau lebih baru (disarankan 7.4 / 8.x), ekstensi `pdo_mysql`, `json`, `session`, `mbstring` (disarankan) |
| **Database** | MySQL / MariaDB yang dipakai SIMRS Khanza (database yang sama) |
| **Web server** | Apache (XAMPP/LAMPP) atau Nginx |
| **Browser** | Chrome, Edge, Safari, atau Firefox versi terbaru |
| **Jaringan** | HP pasien harus bisa membuka alamat server (WiFi RS atau domain publik) |
| **HTTPS** | Disarankan. Perekaman pesan suara langsung dari browser memerlukan HTTPS. Tanpa HTTPS, pasien tetap bisa mengirim rekaman lewat aplikasi perekam HP |

---

## Instalasi

1. **Salin folder aplikasi** ke web server, misalnya:
   - XAMPP Windows: `C:\xampp\htdocs\nursecalldigital\`
   - XAMPP macOS: `/Applications/XAMPP/xamppfiles/htdocs/nursecalldigital/`
   - Linux: `/var/www/html/nursecalldigital/`
2. **Edit `config.php`**: isi nama database Khanza, user, dan password MySQL (lihat bagian berikutnya).
3. Pastikan folder `uploads/`, `uploads/suara/`, dan `data/` **bisa ditulis** oleh web server (Linux: `chmod -R 775 uploads data`).
4. **Buka aplikasi** di browser: `http://alamat-server/nursecalldigital/`
   - Tabel tambahan dibuat **otomatis** saat aplikasi pertama kali dibuka.
   - Jika gagal (misalnya user MySQL tidak punya hak `CREATE`), import `database.sql` ke database Khanza lewat phpMyAdmin:
     pilih database Khanza → tab **Import** → pilih `database.sql` → **Go**. File ini aman dijalankan berulang kali.
5. **Buka `cek.php`** (`http://alamat-server/nursecalldigital/cek.php`) untuk memeriksa PHP, koneksi, tabel, dan kelengkapan file.
   Semua baris harus berwarna hijau.
6. **Login** memakai NIK yang tercantum di `NC_ADMIN_NIK` (otomatis menjadi Admin), lalu:
   - buka **Pengaturan** → isi **Alamat publik aplikasi** (alamat yang bisa dibuka dari HP pasien, misalnya `http://192.168.1.10/nursecalldigital/`);
   - buka **Tampilan & Tema** untuk warna dan halaman login.
7. Setelah semuanya berjalan normal, ubah `APP_DEBUG` menjadi `false` dan **hapus file `cek.php`**.

> ⚠️ Jangan membuka aplikasi dari `localhost` saat mencetak gelang. QR Code berisi alamat aplikasi, jadi isi dulu
> **Alamat publik aplikasi** di menu Pengaturan agar QR bisa dibuka dari HP pasien.

---

## Konfigurasi `config.php`

| Konstanta | Contoh | Keterangan |
|---|---|---|
| `DB_HOST` | `localhost` | Server database Khanza |
| `DB_PORT` | `3306` | Port MySQL/MariaDB |
| `DB_NAME` | `sik` | **Nama database SIMRS Khanza** |
| `DB_USER` / `DB_PASS` | `root` / `` | User MySQL (butuh hak baca tabel Khanza, dan hak tulis ke tabel `nc_..._wira`) |
| `KHANZA_KEY_USER` | `nur` | Kunci AES kolom `user.id_user` (bawaan Khanza) |
| `KHANZA_KEY_PASS` | `windi` | Kunci AES kolom `user.password` (bawaan Khanza) |
| `NC_ADMIN_NIK` | `['12345678']` | NIK yang **selalu menjadi Admin** Nurse Call |
| `APP_NAME` | `Nurse Call` | Nama aplikasi |
| `APP_DEBUG` | `false` | `true` hanya saat instalasi (menampilkan detail error) |

Jika kunci AES di Khanza Anda sudah diubah, sesuaikan `KHANZA_KEY_USER` dan `KHANZA_KEY_PASS`.

---

## Tabel yang Ditambahkan ke Database Khanza

Nurse Call hanya **menambah 6 tabel** ke database Khanza. Semuanya berawalan **`nc_`** dan berakhiran **`_wira`**,
sehingga mudah dikenali dan **tidak mengubah satu pun tabel Khanza**.

| No | Tabel | Fungsi | Terisi saat |
|---|---|---|---|
| 1 | `nc_user_wira` | Role (Admin/User), ruang tugas, dan status aktif pegawai | Otomatis saat pegawai pertama kali login |
| 2 | `nc_gelang_wira` | Kode QR gelang per no. rawat dan status nonaktif | Petugas klik **Buat Gelang** |
| 3 | `nc_panggilan_wira` | Riwayat panggilan: keluhan, pesan, pesan suara, waktu respon, perawat | Pasien melapor lewat HP |
| 4 | `nc_keluhan_wira` | Pilihan keluhan di HP pasien | 8 keluhan bawaan saat instalasi |
| 5 | `nc_setting_wira` | Pengaturan aplikasi (tema, login, display TV, aturan panggilan) | Nilai bawaan saat instalasi |
| 6 | `nc_codeblue_wira` | Riwayat alarm **Code Blue & Code Red** (kolom `kode`): lokasi/kamar, pasien, waktu aktif, siapa yang mengaktifkan & menyelesaikan | Petugas mengaktifkan Code Blue |

Kolom kunci (`no_rawat`, `no_rkm_medis`, `kd_bangsal`, `kd_kamar`, `id_user`) memakai charset **latin1** seperti Khanza agar cocok saat digabung (JOIN).
Collation-nya otomatis disamakan dengan tabel Khanza Anda. Kolom teks bebas memakai **utf8mb4** agar emoji tersimpan.

### Query tambah tabel (file `database.sql`)

Semua query pembuatan tabel ada di **satu file tersendiri: `database.sql`** (folder utama aplikasi), siap copy–paste:

1. Buka **phpMyAdmin**, klik **database Khanza** Anda.
2. Buka tab **SQL**.
3. Buka `database.sql` dengan Notepad / TextEdit, **copy seluruh isinya**, tempel di kotak SQL.
4. Klik **Go**.

Atau lewat tab **Import** → pilih file `database.sql` → **Go**. Langkah ini opsional (tabel juga dibuat otomatis saat aplikasi
pertama dibuka) dan aman dijalankan berulang kali. Di bagian akhir file ada query tambahan khusus untuk yang sudah memasang versi lama.

### 1. `nc_user_wira`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id_user` | VARCHAR(30), **PK** | NIK / id_user Khanza (= `petugas.nip` / `dokter.kd_dokter`) |
| `nama` | VARCHAR(100) | Nama pegawai (mengikuti Khanza) |
| `role` | ENUM('admin','user') | Bawaan `user` |
| `kd_bangsal` | CHAR(5) | Ruang tugas (bangsal Khanza), filter awal nurse station |
| `no_hp` | VARCHAR(20) | Opsional |
| `aktif` | TINYINT(1) | 1 = boleh login, 0 = dinonaktifkan Admin |
| `last_login` | DATETIME | Login terakhir |
| `created_at` | DATETIME | Waktu akun dibuat |

### 2. `nc_gelang_wira`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `no_rawat` | VARCHAR(17), **PK** | No. rawat Khanza |
| `token` | CHAR(24), UNIQUE | Kode acak yang ditanam di QR |
| `dibuat` | DATETIME | Waktu gelang dibuat |
| `dibuat_oleh` | VARCHAR(30) | NIK petugas |
| `dicetak` | INT | Jumlah kali dicetak |
| `nonaktif` | DATETIME, NULL | Terisi otomatis saat pasien **pulang/batal**; QR ditolak permanen |

### 3. `nc_panggilan_wira`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT, **PK**, auto increment | |
| `no_rawat` | VARCHAR(17) | No. rawat pasien |
| `no_rkm_medis` | VARCHAR(15) | No. RM |
| `kd_bangsal` | CHAR(5) | Ruang saat memanggil |
| `kd_kamar` | VARCHAR(15) | Kamar/bed saat memanggil |
| `keluhan_id` | INT | Merujuk `nc_keluhan_wira.id` |
| `keluhan` | VARCHAR(60) | Nama keluhan saat itu |
| `prioritas` | ENUM('tinggi','sedang','rendah') | |
| `pesan` | VARCHAR(255) | Teks tambahan dari pasien |
| `audio` | VARCHAR(80) | Nama file pesan suara di `uploads/suara/` |
| `durasi_audio` | SMALLINT | Detik |
| `pelapor` | ENUM('pasien','keluarga') | |
| `status` | ENUM('baru','diproses','selesai') | |
| `waktu` | DATETIME | Panggilan masuk |
| `waktu_respon` | DATETIME | Mulai ditangani |
| `waktu_selesai` | DATETIME | Selesai |
| `perawat` | VARCHAR(30) | NIK yang menangani |
| `selesai_oleh` | VARCHAR(30) | NIK yang menyelesaikan |
| `tindakan` | VARCHAR(255) | Catatan tindakan |
| `ip` | VARCHAR(45) | Alamat IP pengirim |

Indeks: `(status, kd_bangsal)`, `waktu`, `no_rawat`.

### 4. `nc_keluhan_wira`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT, **PK**, auto increment | |
| `nama` | VARCHAR(60) | Contoh: Sakit / Nyeri, Pusing, Lainnya |
| `ikon` | VARCHAR(16) | Emoji |
| `prioritas` | ENUM('tinggi','sedang','rendah') | |
| `urut` | INT | Urutan tampil |
| `aktif` | TINYINT(1) | 1 = tampil di HP pasien |

### 6. `nc_codeblue_wira`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | INT, **PK**, auto increment | |
| `kode` | ENUM('blue','red') | `blue` = Code Blue (henti jantung/napas), `red` = Code Red (kebakaran/asap) |
| `kd_kamar` / `kd_bangsal` | VARCHAR(15) / CHAR(5) | Kamar & bangsal Khanza (kosong jika lokasi lain, mis. Lobi IGD) |
| `lokasi` | VARCHAR(120) | Teks lokasi yang ditampilkan & diumumkan |
| `no_rawat` / `pasien` | VARCHAR | Pasien di kamar tersebut saat kode diaktifkan |
| `status` | ENUM('aktif','selesai') | Aktif = alarm berbunyi di semua nurse station & Display TV |
| `waktu` / `oleh` | DATETIME / VARCHAR(30) | Kapan & NIK yang mengaktifkan |
| `waktu_selesai` / `selesai_oleh` | DATETIME / VARCHAR(30) | Kapan & NIK yang menandai selesai |
| `catatan` | VARCHAR(255) | Catatan (mis. ditutup otomatis setelah 60 menit) |

### 5. `nc_setting_wira`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `k` | VARCHAR(50), **PK** | Nama pengaturan |
| `v` | TEXT | Nilai pengaturan |

Isi bawaan: `nama_rs`, `url_publik`, `jeda_lapor` (60 detik), `batas_respon` (5 menit), `suara_aktif`, `suara_maks` (60 detik),
`tema_warna`, `login_bg`, `login_overlay`, `login_judul`, `login_teks`, `display_samarkan`, `display_suara`, `display_teks`, `display_key`.

### Hak akses user MySQL

User MySQL di `config.php` membutuhkan:
- `SELECT` pada tabel Khanza;
- `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER` pada tabel `nc_..._wira`.

`ALTER` hanya dipakai untuk menyesuaikan tabel `_wira` saat pembaruan versi.

---

## Tabel Khanza yang Dipakai (Hanya Dibaca)

Tabel berikut **sudah ada di Khanza** dan **tidak perlu ditambah**. Nurse Call hanya membacanya, tidak pernah mengubah atau menghapus isinya.

| Tabel Khanza | Dipakai untuk |
|---|---|
| `user` | Cek login NIK & password (AES) |
| `petugas`, `dokter` | Nama pegawai yang login, nama dokter/DPJP |
| `setting` | Nama, alamat, dan telepon rumah sakit |
| `pasien` | Nama, jenis kelamin, tanggal lahir pasien |
| `reg_periksa` | Data pendaftaran (menu Admisi), cara bayar, umur, status batal |
| `kamar_inap` | Pasien dirawat (`stts_pulang = '-'`), pindah kamar, pulang |
| `kamar`, `bangsal` | Nama ruang dan kamar |
| `poliklinik`, `penjab` | Unit/poli dan cara bayar |
| `dpjp_ranap` | Dokter DPJP pasien rawat inap |
| `pemeriksaan_ranap` | Tanda alergi pasien (kolom `alergi`) |

---

## Pengguna & Hak Akses

Semua pegawai yang memiliki akun **SIMRS Khanza** dapat login dengan **NIK & password Khanza**. Tidak ada akun atau password terpisah.

| Role | Menu |
|---|---|
| **Admin** | Semua menu: Dashboard, Admisi, Rawat Inap, Code Blue, Code Red, Pengguna, Jenis Keluhan, Tampilan & Tema, Pengaturan, Laporan, Display TV |
| **User** | Dashboard (nurse station: notifikasi, alarm, tangani panggilan), Admisi, Rawat Inap (lihat pasien & cetak gelang), Code Blue, Code Red |

- Pegawai yang pertama kali login otomatis menjadi **User**.
- Admin dapat mengubah role, ruang tugas, atau **menonaktifkan** akses seseorang di menu **Pengguna**.
- NIK di `NC_ADMIN_NIK` (config.php) selalu menjadi Admin.
- Menu **Tentang Aplikasi** tersedia untuk semua pengguna.

---

## Panduan Pemakaian

### Petugas admisi / pendaftaran
1. Buka **Admisi** (pendaftaran per tanggal) atau **Rawat Inap** (pasien sedang dirawat).
2. Klik **Buat Gelang** pada pasien, pilih ukuran label, lalu klik **Cetak**.
3. Tempel label di gelang pasien (atau label 100×50 mm di bed).

Gelang boleh dicetak sejak admisi/IGD. QR **otomatis aktif** begitu pasien masuk kamar rawat inap di Khanza.

### Perawat
1. Login, lalu buka **Dashboard** (nurse station). Pilih ruang yang dipantau bila perlu.
2. **Klik sekali di halaman** (atau tombol *Aktifkan suara*) agar browser mengizinkan bunyi alarm.
3. Saat ada panggilan: muncul pop-up **NURSE CALL** dengan alarm dan pengumuman suara.
4. Klik **Tangani**, datangi pasien, lalu klik **Selesai** dan isi catatan tindakan.

### Pasien / keluarga
1. Scan QR Code di gelang dengan kamera HP.
2. Pilih keluhan (atau **Lainnya** lalu tulis keterangan), atau tekan ikon **mikrofon** untuk pesan suara.
3. Tekan **Kirim**, lalu pantau status laporan di layar HP.

---

## Gelang & Label QR

Tersedia **3 model**, semuanya **hitam-putih** (tanpa warna) untuk **kertas stiker**:

| Model | Ukuran | Isi |
|---|---|---|
| **Gelang** | 200 × 27 mm | QR, nama RS, nama, No. RM, tgl lahir (L/P), ruang, tgl masuk, no. rawat, DPJP, alergi, "Scan untuk panggil perawat" |
| **Label** | 100 × 50 mm | QR besar dan data lengkap pasien, cocok juga ditempel di bed |
| **Stiker** | 33 × 15 mm | QR, nama, No. RM (L/P), "Scan panggil perawat" |

- Ukuran kertas cetak otomatis sama dengan ukuran label, dengan **margin 0**.
- Pada jendela cetak, pilih printer label/stiker dan skala **100%** (bukan "Sesuaikan halaman").
- Gelang hilang/rusak: buka **Detail pasien** → **Buat QR baru** (QR lama langsung tidak berlaku).

**Status gelang otomatis:**

| Kondisi di Khanza | Status QR |
|---|---|
| Terdaftar, belum masuk kamar | Belum aktif |
| Dirawat (`kamar_inap.stts_pulang = '-'`), termasuk pindah kamar | **Aktif** (lokasi ikut kamar terbaru) |
| Pulang atau registrasi batal | **Nonaktif permanen** |

---

## Display TV

1. Admin buka menu **Display TV** (dari Dashboard), lalu salin link untuk **semua ruang** atau **per ruang**.
2. Buka link di browser TV / mini PC / Android TV Box, lalu tekan **F** atau tombol ⛶ untuk layar penuh.
3. **Klik sekali / tekan OK di remote** agar suara pengumuman aktif.

Link berisi kunci akses. Jika bocor, klik **Buat ulang kunci**.
Opsi: bunyi dan suara pengumuman, samarkan nama pasien (misalnya *Andi P.*), dan teks berjalan.

---

## Pengaturan Aplikasi

| Menu | Pengaturan |
|---|---|
| **Pengaturan** | Identitas RS (kosong = dari tabel `setting` Khanza), alamat publik aplikasi (untuk QR), jeda minimal antar laporan, standar waktu respon, pesan suara aktif/nonaktif dan durasi maksimal |
| **Tampilan & Tema** | Warna tema aplikasi, foto latar halaman login, tingkat gelap overlay, judul dan teks halaman login |
| **Pengguna** | Role, ruang tugas, nonaktifkan akses |
| **Jenis Keluhan** | Tambah/ubah/sembunyikan pilihan keluhan dan prioritasnya |

---

## Keamanan

### Yang sudah dibangun di aplikasi
- **Tidak mengubah Khanza**: semua perintah tulis hanya ke tabel `nc_..._wira`. Tidak ada foreign key, trigger, view, atau ALTER pada tabel Khanza.
  Saat instalasi otomatis, aplikasi menolak menjalankan perintah apa pun selain membuat/mengisi tabel `_wira`.
- **Login Khanza (AES)** dibatasi 5 kali gagal per sesi **dan 10 kali gagal per alamat IP** dalam 15 menit
  (tidak bisa diakali dengan menghapus cookie).
- **Cookie sesi** `HttpOnly`, `SameSite=Lax`, dan otomatis `Secure` bila memakai HTTPS. **CSRF token** di semua form.
- **Header keamanan** otomatis: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, dan HSTS saat HTTPS.
- **Detail error teknis** (pesan SQL) hanya tampil ke jaringan lokal/server walaupun `APP_DEBUG = true`. Pengunjung dari internet hanya melihat pesan umum.
- **`cek.php` hanya bisa dibuka dari komputer server / jaringan lokal** (dari internet ditolak 403).
- **Gelang pasien pulang dinonaktifkan permanen**; nama pasien disamarkan di halaman QR yang sudah tidak aktif.
- **Jeda minimal** antar laporan pasien (anti-spam) dan pencegahan laporan ganda. QR memakai token acak 24 karakter.
- **Pesan suara** diperiksa jenis aslinya, disimpan dengan nama acak, dan hanya bisa diputar oleh perawat yang login.
- **Data internal** (penanda instalasi, cache Display TV, catatan login gagal) disimpan di folder `data/` yang tertutup dari browser.
- **Display TV** memakai kunci akses yang bisa dibuat ulang.

### File yang dikunci dari browser (Apache, lewat `.htaccess`)

| Diakses dari browser | Hasil |
|---|---|
| `config.php`, `database.sql`, `README.md`, file berawalan titik | 403 Ditolak |
| folder `inc/`, `data/`, `uploads/suara/` | 403 Ditolak |
| skrip/JSON di `uploads/`, daftar isi folder | 403 Ditolak |

Semua sudah diuji di Apache 2.4. Pastikan `AllowOverride All` aktif untuk folder aplikasi (bawaan XAMPP dan kebanyakan hosting cPanel).

### Checklist sebelum dionlinekan
1. **Pasang HTTPS** (SSL, misalnya Let's Encrypt/AutoSSL cPanel). Setelah aktif, buka `.htaccess` dan hapus tanda `#` pada 3 baris *paksa HTTPS*.
2. Di `config.php` set **`APP_DEBUG = false`**.
3. **Hapus `cek.php`** setelah instalasi selesai.
4. Gunakan **user MySQL khusus** (bukan `root`) dengan password kuat:
   ```sql
   CREATE USER 'nursecall'@'localhost' IDENTIFIED BY 'GantiPasswordKuat!';
   GRANT SELECT ON sik.* TO 'nursecall'@'localhost';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER ON sik.nc_user_wira      TO 'nursecall'@'localhost';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER ON sik.nc_gelang_wira    TO 'nursecall'@'localhost';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER ON sik.nc_panggilan_wira TO 'nursecall'@'localhost';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER ON sik.nc_keluhan_wira   TO 'nursecall'@'localhost';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER ON sik.nc_setting_wira   TO 'nursecall'@'localhost';
   GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER ON sik.nc_codeblue_wira  TO 'nursecall'@'localhost';
   ```
   Ganti `sik` dengan nama database Khanza Anda. Jalankan setelah tabel `_wira` dibuat, atau buat tabel lebih dulu memakai user admin.
5. **Jangan membuka port MySQL (3306) ke internet.** Database cukup diakses dari server aplikasi.
6. Isi **Alamat publik aplikasi** di menu Pengaturan dengan alamat online (misalnya `https://nursecall.rs-anda.id/`), lalu cetak ulang gelang.
7. Pastikan folder `uploads/`, `uploads/suara/`, dan `data/` bisa ditulis web server (`chmod 775`), dan **coba buka**
   `https://alamat-anda/nursecalldigital/config.php` serta `.../data/` untuk memastikan hasilnya **403**.
8. Batasi pegawai yang boleh login lewat menu **Pengguna** (nonaktifkan yang tidak perlu) dan ganti password Khanza yang lemah.
9. Lakukan **backup database** Khanza secara rutin.

### Server Nginx
Nginx tidak membaca `.htaccess`. Tambahkan aturan berikut di blok `server { ... }`, lalu sesuaikan nama folder:

```nginx
location ~ ^/nursecalldigital/(inc|data|uploads/suara)/ { deny all; return 403; }
location ~ ^/nursecalldigital/uploads/.*\.(php\d?|phtml|phar|json|txt|log)$ { deny all; return 403; }
location ~ /\. { deny all; return 403; }
location ~ \.(sql|md|log|bak|ini)$ { deny all; return 403; }
location = /nursecalldigital/config.php { deny all; return 403; }
autoindex off;
```

---

## Struktur Folder

```
nursecalldigital/
├── config.php               ← koneksi database Khanza & admin
├── database.sql             ← QUERY TAMBAH TABEL ke database Khanza (copy–paste ke phpMyAdmin)
├── cek.php                  ← pemeriksaan instalasi (hapus setelah selesai)
├── login.php  logout.php  index.php (Dashboard / Nurse Station)
├── admisi.php  ranap.php  pasien.php  gelang.php
├── panggilan.php  daftar.php  rekap.php  export.php
├── pengguna.php  tampilan.php  pengaturan.php  keluhan.php  ruang.php  profil.php  menu.php
├── tv.php (pengaturan Display TV)   display.php (layar TV)
├── codeblue.php  codered.php  ← alarm darurat Code Blue / Code Red
├── lapor.php                ← halaman pasien (dibuka dari scan QR)
├── manifest.php  sw.js  offline.html   ← PWA
├── api/        aksi.php · aksi_codeblue.php · audio.php · data_tv.php · poll.php · status.php
├── inc/        bootstrap.php · layout.php · fungsi_panggilan.php · halaman_darurat.php · filter_pg.php · head.php · foot.php
├── assets/     app.css · display.css · icons/ · js/ (app, station, codeblue, display, gelang-modal, rekam, qrcode)
├── data/       data internal (penanda instalasi, cache TV, login gagal) — tertutup dari browser, harus bisa ditulis
└── uploads/    foto latar login & uploads/suara/ (pesan suara) — harus bisa ditulis
```

> Perhatikan dua pasang file bernama mirip: `display.php` (halaman TV, folder utama) berbeda dengan `api/data_tv.php` (data TV).
> `codeblue.php` & `codered.php` (folder utama) sama-sama memakai `inc/halaman_darurat.php`, dan tombolnya memanggil `api/aksi_codeblue.php`.
> Setiap file **harus** berada di folder yang benar. Jika salah folder, aplikasi menampilkan pesan "Salah folder".

---

## Pemecahan Masalah

| Gejala | Solusi |
|---|---|
| Layar putih / error | Buka `cek.php`, perbaiki baris berwarna merah. Set `APP_DEBUG = true` untuk melihat detail |
| `Table '...nc_..._wira' doesn't exist` | Import `database.sql` ke database Khanza lewat phpMyAdmin |
| "MASIH VERSI LAMA" di `cek.php` | Timpa file tersebut dengan versi terbaru |
| Dashboard "Koneksi terputus" | Penyebabnya tertulis di belakang pesan. Pastikan file `api/` lengkap dan di folder yang benar |
| Alarm tidak berbunyi | Klik sekali di halaman nurse station / tekan tombol *Aktifkan suara* (aturan browser) |
| QR tidak bisa dibuka di HP pasien | Isi **Alamat publik aplikasi** di Pengaturan (bukan `localhost`), lalu cetak ulang gelang |
| QR "Gelang belum aktif" | Pasien belum masuk kamar rawat inap di Khanza |
| QR "Gelang sudah tidak aktif" | Pasien sudah pulang/batal. Jika dirawat lagi, klik **Buat Gelang** untuk QR baru |
| Tombol mikrofon tidak merekam | Perekaman langsung butuh **HTTPS**; tanpa HTTPS pasien memakai aplikasi perekam HP |
| Hasil cetak label tidak pas | Pilih ukuran kertas label yang sesuai di printer dan skala **100%** |
| Menu Code Red tidak muncul / error kolom `kode` | Timpa semua file terbaru, lalu buka aplikasi sekali — kolom `kode` ditambahkan otomatis. Manual: `ALTER TABLE nc_codeblue_wira ADD COLUMN kode ENUM('blue','red') NOT NULL DEFAULT 'blue' AFTER id;` |
| Tampilan tidak berubah setelah update | Muat ulang halaman (Ctrl/Cmd + Shift + R) |

---

## Mencopot Aplikasi

Hapus folder aplikasi, lalu jalankan perintah ini di phpMyAdmin pada database Khanza (riwayat panggilan ikut terhapus):

```sql
DROP TABLE IF EXISTS nc_panggilan_wira, nc_gelang_wira, nc_keluhan_wira, nc_user_wira, nc_setting_wira, nc_codeblue_wira;
```

Tabel dan data SIMRS Khanza **tidak terpengaruh**.

---

**Nurse Call Digital** · Gratis untuk pengguna SIMRS Khanza · **Dilarang diperjualbelikan**
Dikembangkan oleh **M. Wira Satria Buana** · [www.fixdigitech.com](https://www.fixdigitech.com) · 0821-7784-6209