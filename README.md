Nurse Call Digital
"Nurse Call Digital" adalah aplikasi digital nurse call untuk membantu pasien menyampaikan kebutuhan atau keluhan kepada perawat secara cepat melalui "QR Code" yang terpasang pada gelang identitas pasien.
Aplikasi ini dirancang untuk "terintegrasi dengan SIMRS Khanza", sehingga data pasien dapat digunakan dalam proses pelayanan dan pemanggilan pasien selama menjalani perawatan di rumah sakit.

Konsep
Nurse Call Digital menggantikan proses pemanggilan perawat secara konvensional dengan sistem digital berbasis "QR Code, Dashboard, dan Notifikasi Suara".
Pasien tidak perlu menggunakan tombol nurse call khusus. Pasien cukup menggunakan smartphone untuk melakukan scan QR Code yang terdapat pada gelang pasien.
Setelah QR Code dipindai, pasien akan diarahkan ke halaman Nurse Call Digital dan dapat memilih jenis kebutuhan atau keluhan yang tersedia.
Ketika pasien menekan tombol "Lapor", sistem akan mengirimkan panggilan kepada petugas/perawat dan menampilkan notifikasi pada TV Monitor dan Dashboard Nurse Station** disertai suara notifikasi.

🔄 Alur Sistem

┌──────────────────────┐
│      SIMRS Khanza    │
│   Data Pasien/Ranap  │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│   Nurse Call Digital │
│   Cetak Gelang QR    │
└──────────┬───────────┘
           │
           ▼
┌──────────────────────┐
│     GELANG PASIEN    │
│      QR CODE         │
└──────────┬───────────┘
           │
           │ Scan QR Code
           ▼
┌──────────────────────┐
│  Smartphone Pasien   │
│                      │
│  Pilih Keluhan /     │
│  Kebutuhan Pasien    │
└──────────┬───────────┘
           │
           │ Klik "LAPOR"
           ▼
┌──────────────────────┐
│   Nurse Call Server  │
└──────────┬───────────┘
           │
       ┌───┴────────────┐
       ▼                ▼
┌──────────────┐  ┌─────────────────┐
│ TV Monitor   │  │ Nurse Station   │
│              │  │ Dashboard       │
│ 🔔 NOTIFIKASI│  │ 🔔 NOTIFIKASI   │
│    SUARA     │  │    SUARA        │
└──────────────┘  └─────────────────┘

Cara Kerja

1. Registrasi Pasien
Pasien terdaftar melalui sistem rumah sakit/SIMRS.
Data pasien yang diperlukan dapat meliputi:

- Nomor Rekam Medis
- Nomor Rawat
- Nama Pasien
- Ruang
- Nomor Kamar/Bed
- Dokter
- Jenis pelayanan

2. Cetak Gelang Pasien
Pada saat proses pelayanan, sistem Nurse Call Digital dapat digunakan untuk mencetak gelang pasien yang dilengkapi dengan "QR Code unik".
QR Code tersebut menjadi identitas digital untuk menghubungkan pasien dengan data perawatan yang sesuai.

3. Pasien Melakukan Scan
Ketika pasien membutuhkan bantuan perawat, pasien cukup:
"Scan QR Code → Buka Nurse Call Digital"
Tidak diperlukan telepon atau komunikasi suara melalui aplikasi.

4. Pilih Keluhan / Kebutuhan
Setelah QR Code dipindai, pasien akan melihat pilihan kebutuhan atau keluhan.
Contoh:
- 🔔 Panggil Perawat
- 💊 Meminta Obat
- 🛏️ Membutuhkan Bantuan
- 🚿 Membutuhkan Bantuan ke Toilet
- 🍽️ Permintaan Makan
- 😣 Keluhan Nyeri
- 💧 Membutuhkan Air Minum
- ⚠️ Kondisi Darurat
- 📝 Keluhan Lainnya
Pilihan dapat disesuaikan dengan kebutuhan rumah sakit.

5. Klik "Lapor"
Setelah memilih kebutuhan, pasien menekan tombol:

"LAPOR"

Sistem kemudian membuat data pemanggilan dan mengirimkan notifikasi ke Nurse Station.

6. Notifikasi Nurse Station
Pada Dashboard Nurse Station akan muncul:
- Nama pasien
- Ruang
- Nomor bed
- Jenis keluhan
- Waktu pemanggilan
- Status panggilan
Dashboard juga memberikan **notifikasi suara** agar petugas segera mengetahui adanya panggilan baru.

7. TV Monitor
Panggilan juga dapat ditampilkan pada "TV Monitor Nurse Station".

Contoh:

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
       🔔 NURSE CALL
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PASien membutuhkan bantuan

Nama     : M. Wira Satria Buana
Ruang    : Mawar
Bed      : 03
Keluhan  : Meminta Bantuan
Waktu    : 14:32

        ⚠️ PANGGILAN BARU
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Petugas kemudian dapat menangani panggilan dan mengubah statusnya menjadi:

BARU
↓
DITERIMA
↓
DALAM PENANGANAN
↓
SELESAI

🔗 Integrasi SIMRS Khanza
Nurse Call Digital dirancang untuk dapat terintegrasi dengan **SIMRS Khanza**.
Integrasi memungkinkan sistem Nurse Call mendapatkan informasi pasien yang sedang menjalani pelayanan, khususnya pasien rawat inap.
Contoh data yang dapat digunakan:

| Data | Keterangan |
|---|---|
| No. RM | Nomor rekam medis |
| No. Rawat | Nomor registrasi rawat |
| Nama Pasien | Identitas pasien |
| Ruang | Ruang perawatan |
| Bed | Nomor tempat tidur |
| Dokter | Dokter yang menangani |
| Status | Status perawatan |

Dengan integrasi tersebut, identitas pasien tidak perlu diketik ulang secara manual oleh petugas.

Komponen Sistem
Nurse Call Digital terdiri dari beberapa komponen utama:
Patient
Digunakan oleh pasien melalui smartphone untuk:
- Scan QR Code
- Melihat identitas pelayanan
- Memilih kebutuhan
- Mengirim panggilan

Nurse Station Dashboard
Digunakan oleh perawat/petugas untuk:
- Melihat panggilan masuk
- Mendengar notifikasi suara
- Melihat detail pasien
- Menerima panggilan
- Memproses panggilan
- Menyelesaikan panggilan
- Melihat riwayat panggilan

TV Display
Digunakan untuk menampilkan panggilan secara visual di Nurse Station.

Admin
Digunakan untuk melakukan:
- Management pasien
- Management ruang
- Management pengguna
- Management jenis keluhan
- Monitoring panggilan
- Rekapitulasi panggilan
- Pengaturan sistem

Teknologi
Project ini dikembangkan sebagai aplikasi berbasis web sehingga dapat diakses melalui perangkat yang terhubung ke jaringan rumah sakit.
Komponen teknologi dapat meliputi:
- PHP
- MySQL
- JavaScript
- HTML5
- CSS
- REST API
- QR Code
- Web-based Dashboard
- TV Display
- SIMRS Integration

Tujuan
Nurse Call Digital dikembangkan dengan tujuan:
- Meningkatkan kecepatan respons perawat
- Mempermudah pasien meminta bantuan
- Mengurangi ketergantungan pada perangkat nurse call konvensional
- Menyediakan pencatatan setiap pemanggilan
- Menyediakan monitoring panggilan secara realtime
- Membantu rumah sakit melakukan evaluasi pelayanan
- Mengintegrasikan proses nurse call dengan sistem informasi rumah sakit

Monitoring
Setiap panggilan dapat dicatat dalam sistem sehingga rumah sakit dapat melakukan monitoring terhadap:
- Jumlah panggilan
- Jenis keluhan
- Ruang dengan panggilan terbanyak
- Waktu panggilan
- Waktu respons
- Waktu penyelesaian
- Riwayat pemanggilan pasien
Data tersebut dapat digunakan sebagai bagian dari evaluasi dan peningkatan mutu pelayanan.

Ketentuan Penggunaan
Nurse Call Digital dikembangkan sebagai project untuk membantu pengembangan dan digitalisasi sistem pemanggilan pasien di lingkungan rumah sakit.
"Project ini tidak untuk diperjualbelikan"
Dilarang menjual, menyewakan, atau mengomersialkan source code Nurse Call Digital tanpa izin dari developer.
Project ini dapat digunakan, dipelajari, dikembangkan, dan dimodifikasi untuk kebutuhan internal sesuai dengan ketentuan yang berlaku.
Penggunaan di lingkungan rumah sakit tetap menjadi tanggung jawab pihak yang melakukan implementasi, termasuk aspek keamanan sistem, perlindungan data pasien, integrasi dengan SIMRS, serta kesesuaian dengan kebijakan dan regulasi yang berlaku.

Dukungan & Donasi
Pengembangan Nurse Call Digital membutuhkan waktu dan sumber daya untuk pengembangan fitur, perbaikan bug, peningkatan keamanan, dokumentasi, serta pengembangan integrasi dengan sistem rumah sakit.
Bagi yang merasa project ini bermanfaat dan ingin mendukung pengembangannya, donasi sangat dipersilakan.
Rekening Donasi

BSI (Bank Syariah Indonesia)
A.n. M. Wira Satria Buana
No. Rekening: 7134197557

Bank Jago
A.n. M. WIRA SATRIA BUANA
No. Rekening: 104886785030

GoPay
No. HP: 08217784629

Terima kasih atas dukungan Anda.
Setiap dukungan, sekecil apa pun, sangat berarti untuk keberlanjutan pengembangan project ini.

Developer
M. Wira Sb 
Software Engineering
"FixDigitech"
Website: https://www.fixdigitech.com

Catatan
Nurse Call Digital merupakan project yang dikembangkan untuk tujuan pengembangan teknologi dan digitalisasi pelayanan rumah sakit.
"Tidak untuk diperjualbelikan
Jika project ini digunakan dan memberikan manfaat, silakan memberikan dukungan melalui donasi untuk membantu pengembangan selanjutnya.
"Nurse Call Digital — Digital Nurse Call System berbasis QR Code"likan.*
