Nurse Call Digital
"Nurse Call Digital" adalah aplikasi digital nurse call untuk membantu pasien menyampaikan kebutuhan atau keluhan kepada perawat secara cepat melalui "QR Code" yang terpasang pada gelang identitas pasien.

<img width="1440" height="778" alt="Screen Shot 2026-10-04 at 18 50 27" src="https://github.com/user-attachments/assets/e28bf926-a4c2-4897-8c14-5a10fe7e4edf" />

Aplikasi ini dirancang untuk **terintegrasi dengan SIMRS Khanza**, sehingga data pasien dapat digunakan dalam proses pelayanan dan pemanggilan pasien selama menjalani perawatan di rumah sakit.

Konsep
Nurse Call Digital menggantikan proses pemanggilan perawat secara konvensional dengan sistem digital berbasis:
- QR Code
- Dashboard
- Notifikasi suara
- TV Display
- Nurse Station

Pasien tidak perlu menggunakan tombol nurse call khusus. Pasien cukup menggunakan smartphone untuk melakukan scan QR Code yang terdapat pada gelang pasien.
Setelah QR Code dipindai, pasien akan diarahkan ke halaman Nurse Call Digital dan dapat memilih jenis kebutuhan atau keluhan yang tersedia.
Ketika pasien menekan tombol "Lapor", sistem akan mengirimkan panggilan kepada petugas/perawat dan menampilkan notifikasi pada **TV Monitor** dan **Dashboard Nurse Station**, disertai suara notifikasi.

Alur Sistem

<img width="1536" height="1024" alt="Digital Nurse Call System Infographic" src="https://github.com/user-attachments/assets/de66937e-5298-4374-a88a-e43d46b8f279" />

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
- Jenis Pelayanan

2. Cetak Gelang Pasien
Pada saat proses pelayanan, sistem Nurse Call Digital dapat digunakan untuk mencetak gelang pasien yang dilengkapi dengan **QR Code unik".
QR Code tersebut menjadi identitas digital untuk menghubungkan pasien dengan data perawatan yang sesuai.

<img width="1440" height="777" alt="Screen Shot 2026-10-04 at 18 51 52" src="https://github.com/user-attachments/assets/cfcba051-afd0-4977-bddb-fbb4a391aa4e" />

4. Pasien Melakukan Scan
Ketika pasien membutuhkan bantuan perawat, pasien/keluara pasien cukup:
"Scan QR Code yang ada di Gelang Pasien→ Buka Nurse Call Digital"

<img width="591" height="1280" alt="HP1" src="https://github.com/user-attachments/assets/d0b7b90e-7e03-4526-a30b-fc64f966d5f2" />

Tidak diperlukan telepon atau komunikasi suara melalui aplikasi.

6. Pilih Keluhan / Kebutuhan
Setelah QR Code dipindai, pasien akan melihat pilihan kebutuhan atau keluhan.
Contoh:

<img width="591" height="1280" alt="HP2" src="https://github.com/user-attachments/assets/1a4e386c-f821-4fb6-8399-362ce7e09138" />

Pilihan keluhan dapat disesuaikan dengan kebutuhan masing-masing rumah sakit.

5. Klik "Lapor"
Setelah memilih kebutuhan, pasien menekan tombol:
"LAPOR"
Sistem kemudian membuat data pemanggilan dan mengirimkan notifikasi ke Nurse Station.

6. Notifikasi Nurse Station
Pada Dashboard Nurse Station akan muncul:

<img width="1440" height="779" alt="Screen Shot 2026-10-04 at 18 58 50" src="https://github.com/user-attachments/assets/3bb70820-8ae4-4a82-983c-123dffd856c4" />

<img width="1440" height="778" alt="Screen Shot 2026-10-04 at 18 58 59" src="https://github.com/user-attachments/assets/aab6a112-fd66-4b5c-b4a2-d662328f591a" />

Dashboard juga memberikan **notifikasi suara** agar petugas segera mengetahui adanya panggilan baru.

7. TV Monitor
Panggilan juga dapat ditampilkan pada **TV Monitor Nurse Station**.
Contoh tampilan:

<img width="1440" height="781" alt="Screen Shot 2026-10-04 at 18 59 19" src="https://github.com/user-attachments/assets/4cc56d7a-1362-495d-bcb2-688d91faba55" />

<img width="1437" height="778" alt="Screen Shot 2026-10-04 at 18 51 24" src="https://github.com/user-attachments/assets/2fa8dd8d-afdc-4cc6-84a4-534a4fb585d7" />

<img width="1440" height="779" alt="Screen Shot 2026-10-04 at 18 51 36" src="https://github.com/user-attachments/assets/b66d60df-f1e8-4f00-992b-a1cbbe1ce187" />

Petugas kemudian dapat menangani panggilan dan mengubah statusnya menjadi:
BARU - DITERIMA - DALAM PENANGANAN - SELESAI

Integrasi SIMRS Khanza
Nurse Call Digital dirancang untuk dapat terintegrasi dengan **SIMRS Khanza**.
Integrasi memungkinkan sistem Nurse Call mendapatkan informasi pasien yang sedang menjalani pelayanan, khususnya pasien rawat inap.
Contoh data yang dapat digunakan:
| Data | Keterangan |
|---|---|
| No. RM | Nomor Rekam Medis |
| No. Rawat | Nomor registrasi rawat |
| Nama Pasien | Identitas pasien |
| Ruang | Ruang perawatan |
| Bed | Nomor tempat tidur |
| Dokter | Dokter yang menangani |
| Status | Status perawatan |

Dengan integrasi tersebut, identitas pasien tidak perlu diketik ulang secara manual oleh petugas.

Komponen Sistem
Nurse Call Digital terdiri dari beberapa komponen utama.
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

Project ini tidak untuk diperjualbelikan.**
Dilarang menjual, menyewakan, atau mengomersialkan source code Nurse Call Digital tanpa izin dari developer.
Project ini dapat digunakan, dipelajari, dikembangkan, dan dimodifikasi untuk kebutuhan internal sesuai dengan ketentuan yang berlaku.
Penggunaan di lingkungan rumah sakit tetap menjadi tanggung jawab pihak yang melakukan implementasi, termasuk aspek:
- Keamanan sistem
- Perlindungan data pasien
- Keamanan database
- Integrasi dengan SIMRS
- Hak akses pengguna
- Kesesuaian dengan kebijakan rumah sakit
- Kesesuaian dengan regulasi yang berlaku

Dukungan & Donasi
Pengembangan Nurse Call Digital membutuhkan waktu dan sumber daya untuk pengembangan fitur, perbaikan bug, peningkatan keamanan, dokumentasi, serta pengembangan integrasi dengan sistem rumah sakit.
Bagi yang merasa project ini bermanfaat dan ingin mendukung pengembangannya, **donasi sangat dipersilakan**.
Rekening Donasi

BSI — Bank Syariah Indonesia
A.n. M. Wira Satria Buana
No. Rekening:7134197557

Bank Jago
A.n. M. WIRA SATRIA BUANA
No. Rekening: 104886785030

GoPay
No. HP: 082177846209

"Terima kasih atas dukungan Anda"
Setiap dukungan, sekecil apa pun, sangat berarti untuk keberlanjutan pengembangan project ini.

Developer
M. Wira Sb
Software Engineering
"FixDigitech"
Website: https://www.fixdigitech.com

Catatan
Nurse Call Digital merupakan project yang dikembangkan untuk tujuan pengembangan teknologi dan digitalisasi pelayanan rumah sakit.
"Tidak untuk diperjualbelikan"
Jika project ini digunakan dan memberikan manfaat, silakan memberikan dukungan melalui donasi untuk membantu pengembangan selanjutnya.
