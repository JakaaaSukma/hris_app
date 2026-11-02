# 🏢 HRIS GYOKAI INDONESIA
### Integrated Enterprise Human Resource Information System

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=white)](https://alpinejs.dev)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)

---

## 📌 Ringkasan Eksekutif (Executive Summary)

**HRIS Gyokai** adalah aplikasi *Human Resource Information System* berbasis web modern yang dirancang untuk mengotomatisasi, merapikan, dan mengamankan seluruh siklus pengelolaan SDM (*Employee Lifecycle*) di lingkungan perusahaan. 

Aplikasi ini tidak hanya mencakup administrasi standar, tetapi juga terintegrasi langsung secara real-time dengan **mesin absensi fingerprint Solution X609 (protokol cloud ADMS)**, mendukung **alur persetujuan berjenjang (multi-level approval)** dengan prinsip ketat *Segregation of Duties*, modul **penggajian & dispatch slip gaji via multi-SMTP**, hingga fitur mandiri **Penerbitan Surat Keterangan / Paklaring Resmi (Pixel-Perfect A4 Landscape)**.

---

## 🎯 Nilai Tambah & Fitur Utama untuk Pertimbangan HR

### 1. 🕒 Presensi Pintar & Integrasi Mesin Fingerprint (ADMS Cloud)
* **Real-time Push Data:** Terhubung langsung dengan mesin fingerprint Solution X609 melalui protokol cloud ADMS (`/iclock/cdata`). Karyawan melakukan scan di kantor, data langsung tersinkronisasi ke sistem tanpa perlu tarik kabel atau flashdisk.
* **Kebijakan Jam Masuk & Toleransi 15 Menit:** Batas masuk kantor pukul **09:00 WIB** dengan toleransi keterlambatan hingga **09:15 WIB** (scan &le; 09:15 WIB tercatat *Tepat Waktu*, &gt; 09:15 WIB tercatat *Terlambat*).
* **Otomasi Izin Terlambat (Dispensasi):** Pengajuan izin terlambat otomatis mencatat kehadiran pemohon. Saat permohonan disetujui oleh Manager Dept & Manager HR, status kehadiran otomatis diputihkan menjadi *Tepat Waktu* beserta catatan dispensasi resmi.
* **Fitur Reset Kehadiran Bilateral:** Kebijakan reset kehadiran yang transparan, mewajibkan persetujuan dua arah antara Manager HR dan Direktur Utama serta otomatis menyiarkan notifikasi resmi ke seluruh karyawan.

### 2. 🌴 Manajemen Cuti, Izin & Kontrol Sisa Cuti Tahunan
* **Saldo Cuti Transparan & Real-Time:** Setiap karyawan dan manager memiliki dasbor visual saldo cuti pribadi (jatah 12 hari, kuota terpakai, pending, sisa hari) lengkap dengan *multi-segment progress bar*.
* **Pusat Monitoring Seluruh Personel oleh HR:** Khusus Manager HR dan Direktur disediakan kontrol tabel agregat untuk memantau, memfilter, dan mengaudit saldo cuti tahunan seluruh 30 personel perusahaan secara cepat.
* **Anti Self-Approval:** Pembuat permohonan dilarang keras menyetujui pengajuannya sendiri. Setiap persetujuan/penolakan mencatat identitas penilai (`approved_by`), waktu evaluasi, dan alasan penolakan wajib.

### 3. 📜 Generator Surat Keterangan / Paklaring Resmi (Pixel Perfect)
* **Format Asli A4 Landscape 100% Presisi:** Menerbitkan Surat Keterangan Kerja / Paklaring resmi LPK Gyokai Indonesia dengan blangko resolusi tinggi (2480x1754 px), lengkap dengan watermark logo "G", bingkai ganda, cap stempel resmi, dan tanda tangan Direktur.
* **Live Visual Preview Berdampingan:** Form input dilengkapi pratinjau real-time dengan tipografi kaligrafi (*Apple Chancery*) yang responsif.
* **One-Click Cetak / Save as PDF:** Siap cetak langsung melalui dialog browser tanpa memerlukan aplikasi desain tambahan.
* **Penyimpanan Arsip Dokumen:** Seluruh paklaring yang diterbitkan tersimpan rapi dalam basis data riwayat dokumen.

### 4. 💰 Payroll & Pengiriman Slip Gaji Terjadwal (Email Dispatch)
* **Penerbitan Slip Gaji Masal & Satuan:** Kemudahan input gaji pokok, tunjangan, lembur, dan potongan secara masal per divisi atau individual.
* **Multi-SMTP Email Dispatcher:** Mengirimkan ribuan slip gaji berformat PDF terenkripsi langsung ke email masing-masing karyawan menggunakan algoritma rotasi multi-SMTP Gmail pool dengan penanganan live progress bar dan kuota harian.
* **Sinkronisasi Dua Arah:** Terhubung dan tersinkronisasi secara real-time dengan portal data kepegawaian internal.

### 5. 👥 Restrukturisasi Organisasi & Penggabungan Divisi GA ke HR
* **Integrasi Operasional:** Departemen General Affairs (GA) terintegrasi di bawah naungan Departemen Human Resources (HR).
* **Peran Asisten Manager GA:** Menjembatani koordinasi operasional aset, fasilitas, dan inventaris kantor di bawah arahan Manager HR dan Direktur Utama.
* **Segregasi Hak Akses Ketat (*Segregation of Duties*):**
  * Staf eks-GA **hanya** dapat mengakses modul inventaris dan fasilitas kantor, dilarang mengakses data kepegawaian HR.
  * Staf HR murni fokus pada operasional kepegawaian dan dilarang mengelola tiket fasilitas fisik.
  * Manager HR & Direktur memegang akses penuh (*Dual Access*) ke seluruh fasilitas.

### 6. 💬 Komunikasi & Kolaborasi Tim Terintegrasi
* **Chat Departemen Privat:** Saluran komunikasi internal antar staf dan manager di masing-masing divisi (privat, Direktur tidak mengintervensi ruang diskusi internal).
* **Saluran Komunikasi Direksi:** Jalur privat 1-on-1 antara Direktur Utama dengan seluruh Kepala Departemen (Manager).
* **Kalender & Jadwal Kerja Berjenjang:** Penjadwalan level individu, divisi, hingga koordinasi lintas departemen (*Antar-Departemen*) dengan persetujuan manager terkait.

---

## 🛡️ Standar Keamanan & Kepatuhan Sistem

1. **Anti Self-Approval Principle:** Sistem secara ketat melarang pengguna menyetujui klaim cuti, lembur, reimbursement, atau permohonan yang diajukan oleh dirinya sendiri.
2. **Session Expire on Browser Close:** Sesi autentikasi otomatis berakhir saat jendela browser ditutup untuk mencegah penyalahgunaan akun di komputer kantor bersama.
3. **Pembatasan 3 Perangkat Aktif (Concurrent Login Limit):** Setiap akun dibatasi login maksimal pada 3 perangkat berbeda secara bersamaan. Login baru otomatis mengakhiri sesi perangkat yang paling lama tidak aktif.
4. **Audit Trail Komprehensif:** Setiap aktivitas krusial (absen manual, approval, reset data, perubahan status akun) tercatat pada log aktivitas (*Activity Logs* & *Login Logs*).
5. **Perlindungan Berkas Rahasia:** Dokumen sensitif (.env, database backup, rekaman KTP/CV karyawan, error log) dilindungi secara ketat dari repositori publik.

---

## 🏛️ Matriks Peran & Hak Akses Pengguna

| Role | Cakupan Wewenang | Contoh Pengguna |
| :--- | :--- | :--- |
| **Direktur Utama** | Pengawasan global seluruh metrik perusahaan, persetujuan tertinggi jadwal & reset data, komunikasi langsung dengan seluruh Manager. | Hendra Wijaya |
| **Manager HR** | Pengelolaan master data karyawan, approval cuti seluruh staf, monitoring sisa cuti 30 personel, penerbitan paklaring resmi, kontrol GA & HR. | Siti Rahmawati, Fida, Rahmat |
| **Asisten Manager GA** | Memimpin operasional logistik, pengelolaan aset inventaris ATK, dan penyelesaian tiket pengaduan fasilitas kantor. | Joko Susanto, Harlan |
| **Manager Departemen** | Monitoring kinerja tim divisi, approval jadwal departemen, supervisi deals/reimbursement/keuangan tim. | Bambang (Finance), Dimas (BizDev) |
| **Karyawan Staff** | Presensi harian, kontrol sisa cuti pribadi, pelaporan tugas (KPI/task), permohonan reimbursement, chat internal divisi. | Staf HR, Finance, BizDev, Resepsionis, Trainer |

---

## 💻 Tech Stack & Kebutuhan Lingkungan

* **Backend Engine:** Laravel 10.x (PHP &ge; 8.1, Composer)
* **Frontend UI:** Blade Templating, Tailwind CSS 3.x, Alpine.js v3, Material Symbols
* **Basis Data:** MySQL 8.0 / MariaDB
* **Ekstensi PHP yang Dibutuhkan:** `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `curl`, `gd`, `zip`

---

## 🚀 Panduan Menjalankan Project (Quick Start)

1. **Clone Repository:**
   ```bash
   git clone https://github.com/JakaaaSukma/hris_app.git
   cd hris_app
   ```

2. **Install Dependensi:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Sesuaikan pengaturan database pada file `.env`.*

4. **Migrasi Basis Data:**
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan Aplikasi:**
   ```bash
   php artisan serve
   ```
   *Buka browser pada alamat `http://127.0.0.1:8000`.*

---

## 📄 Lisensi & Hak Cipta
Dikembangkan untuk keperluan operasional internal **LPK Gyokai Indonesia**. Seluruh hak cipta dan merek dagang dilindungi undang-undang.
