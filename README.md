Nurse Call Digital
Nurse Call Digital** adalah aplikasi digital nurse call untuk membantu pasien menyampaikan kebutuhan atau keluhan kepada perawat secara cepat melalui **QR Code** yang terpasang pada gelang identitas pasien.
Aplikasi ini dirancang untuk **terintegrasi dengan SIMRS Khanza**, sehingga data pasien dapat digunakan dalam proses pelayanan dan pemanggilan pasien selama menjalani perawatan di rumah sakit.

<img width="1440" height="778" alt="Screen Shot 2026-10-04 at 18 50 27" src="https://github.com/user-attachments/assets/28980424-fc4f-43ad-a676-c3b1c48e5cba" />

Konsep
Nurse Call Digital menggantikan proses pemanggilan perawat secara konvensional dengan sistem digital berbasis:
- QR Code
- Dashboard
- Notifikasi suara
- TV Display
- Nurse Station
Pasien tidak perlu menggunakan tombol nurse call khusus. Pasien cukup menggunakan smartphone untuk melakukan scan QR Code yang terdapat pada gelang pasien.
Setelah QR Code dipindai, pasien akan diarahkan ke halaman Nurse Call Digital dan dapat memilih jenis kebutuhan atau keluhan yang tersedia.
Ketika pasien menekan tombol **Lapor**, sistem akan mengirimkan panggilan kepada petugas/perawat dan menampilkan notifikasi pada **TV Monitor** dan **Dashboard Nurse Station**, disertai suara notifikasi.

Alur Sistem

<img width="1536" height="1024" alt="revisi" src="https://github.com/user-attachments/assets/ecd4d9ad-cc0e-4974-90e6-9e6543be2838" />


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
Pada saat proses pelayanan, sistem Nurse Call Digital dapat digunakan untuk mencetak gelang pasien yang dilengkapi dengan **QR Code unik**.

<img width="1440" height="777" alt="Screen Shot 2026-10-04 at 18 51 52" src="https://github.com/user-attachments/assets/3fa2e002-70c4-47f6-a553-196bda1a9355" />

QR Code tersebut menjadi identitas digital untuk menghubungkan pasien dengan data perawatan yang sesuai.

3. Pasien Melakukan Scan
Ketika pasien membutuhkan bantuan perawat, pasien cukup:
"Scan QR Code → Buka Nurse Call Digital"

<img width="591" height="1280" alt="HP1" src="https://github.com/user-attachments/assets/f9380592-11eb-4717-b5d1-77768843c319" />

Tidak diperlukan telepon atau komunikasi suara melalui aplikasi.

4. Pilih Keluhan / Kebutuhan
Setelah QR Code dipindai, pasien akan melihat pilihan kebutuhan atau keluhan.
Contoh:

<img width="591" height="1280" alt="HP2" src="https://github.com/user-attachments/assets/42311e59-ede9-4f6f-b5e7-36c9a4aee95a" />

Pilihan keluhan dapat disesuaikan dengan kebutuhan masing-masing rumah sakit.

5. Klik "Lapor"
Setelah memilih kebutuhan, pasien menekan tombol:

LAPOR

Sistem kemudian membuat data pemanggilan dan mengirimkan notifikasi ke Nurse Station.

6. Notifikasi Nurse Station
Pada Dashboard Nurse Station akan muncul:

<img width="1440" height="778" alt="Screen Shot 2026-10-04 at 18 58 59" src="https://github.com/user-attachments/assets/8bad3f05-afb5-401c-9939-7c8ed12db9a9" />

Dashboard juga memberikan **notifikasi suara** agar petugas segera mengetahui adanya panggilan baru.

7. TV Monitor
Panggilan juga dapat ditampilkan pada **TV Monitor Nurse Station**.
Contoh tampilan:

<img width="1440" height="779" alt="Screen Shot 2026-10-04 at 18 58 50" src="https://github.com/user-attachments/assets/9038c917-1187-4cf8-825d-f05037b42511" />

Petugas kemudian dapat menangani panggilan dan mengubah statusnya menjadi:
BARU - DITERIMA - DALAM PENANGANAN - SELESAI

Integrasi SIMRS Khanza
Nurse Call Digital dirancang untuk dapat terintegrasi dengan "SIMRS Khanza".
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

<img width="1440" height="779" alt="Screen Shot 2026-10-04 at 18 51 36" src="https://github.com/user-attachments/assets/01311581-3912-49d4-81a2-abc16f880ec6" />

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

<img width="1440" height="778" alt="Screen Shot 2026-10-04 at 19 56 13" src="https://github.com/user-attachments/assets/44b1c80f-fb91-4c96-bca5-94898cbfde9f" />


Data tersebut dapat digunakan sebagai bagian dari evaluasi dan peningkatan mutu pelayanan.

Ketentuan Penggunaan
Nurse Call Digital dikembangkan sebagai project untuk membantu pengembangan dan digitalisasi sistem pemanggilan pasien di lingkungan rumah sakit.
"Project ini tidak untuk diperjualbelikan"
Dilarang menjual, menyewakan, atau mengomersialkan source code Nurse Call Digital tanpa izin dari developer.
Project ini dapat digunakan, dipelajari, dikembangkan, dan dimodifikasi untuk kebutuhan internal sesuai dengan ketentuan yang berlaku.
Penggunaan di lingkungan rumah sakit tetap menjadi tanggung jawab pihak yang melakukan implementasi, termasuk aspek:

Dukungan & Donasi
Pengembangan Nurse Call Digital membutuhkan waktu dan sumber daya untuk pengembangan fitur, perbaikan bug, peningkatan keamanan, dokumentasi, serta pengembangan integrasi dengan sistem rumah sakit.
Bagi yang merasa project ini bermanfaat dan ingin mendukung pengembangannya, **donasi sangat dipersilakan**.

## ❤️ Dukungan & Donasi

Setiap dukungan, sekecil apa pun, sangat berarti untuk keberlanjutan pengembangan **Nurse Call Digital**.

### 💳 Rekening Donasi

| Metode | Informasi |
|---|---|
| 🏦 **BSI** | **M. Wira Satria Buana**<br>No. Rekening: **7134197557** |
| 🏦 **Bank Jago** | **M. WIRA SATRIA BUANA**<br>No. Rekening: **104886785030** |
| 📱 **GoPay** | No. HP: **082177846209** |

> ❤️ Terima kasih atas dukungan Anda.  
> Setiap donasi akan membantu mendukung pengembangan fitur, perbaikan sistem, keamanan, dokumentasi, dan integrasi **Nurse Call Digital**.

---

## 👨‍💻 Developer

**M. Wira Sb**  
Software Engineering  
**FixDigitech**

🌐 **Website:** https://www.fixdigitech.com

---

> 🏥 **Nurse Call Digital**  
> *Digital Nurse Call System berbasis QR Code.*



**Digital Nurse Call System berbasis QR Code**

> *Dikembangkan untuk membantu pelayanan, bukan untuk diperjualbelikan.*
