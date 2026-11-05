# PROJECT OVERVIEW & ARCHITECTURE CHEATSHEET
> **Tujuan Dokumen:** Referensi ringkas & menyeluruh (cheat sheet) untuk AI Agent agar mempercepat pemahaman konteks, arsitektur, role & permissions, skema database, aturan bisnis, dan lokasi file kunci saat melakukan analisis atau modifikasi kode tanpa perlu membaca ulang seluruh codebase dari nol.

---

## 1. Quick Tech Stack & Environment
- **Framework:** Laravel 10.x (PHP 8.1+)
- **Frontend / Styling:** Tailwind CSS 3.x, Alpine.js v3, Vite, Material Symbols Outlined (Google Icons), Plus Jakarta Sans font.
- **Database:** MySQL / MariaDB via XAMPP (`127.0.0.1:3306`, db: `hris_app`).
- **Server:** Running via `php artisan serve` (`http://127.0.0.1:8000`).
- **Build Tool:** `npm run build` / `npm run dev` (Vite bundle).

---

## 2. Struktur Role, Akun & Matriks Otorisasi
Sistem mengadopsi prinsip **Segregation of Duties** ketat:
> **PENTING:** Pengguna yang membuat pengajuan (Cuti, Reimbursement, Inventaris, Jadwal, dll.) **TIDAK BISA** menyetujui pengajuannya sendiri (`self-approval prohibited`). Setiap approval **wajib mencatat siapa (`approved_by`) & kapan (`approved_at`)** approval diberikan.

| ID | Nama Akun | Role | Departemen | Hak Akses Utama |
|---|---|---|---|---|
| **1** | Hendra Wijaya | `direktur` | `Executive` | Hak penuh seluruh modul (Global View, Full Approval, Rekap KPI & Kehadiran, Direktur Laporan). |
| **2** | Siti Rahmawati | `manager` | `HR` (sub: `HR`) | Manager HR, kelola seluruh modul HR dan modul GA, presensi, rekrutmen, offboarding, ajukan jadwal dept & antar-dept. |
| **3** | Clarissa Putri | `karyawan` | `HR` (sub: `HR`) | Staf HR murni: akses modul HR, presensi, review jadwal HR. **DILARANG AKSES FITUR GA**. |
| **4** | Bambang Soediro | `manager` | `Finance` | Approval reimbursement, pengajuan inventaris GA, kelola slip gaji, review jadwal dept & antar-dept ke Finance. |
| **5** | Mega Lestari | `karyawan` | `Finance` | Input reimbursement, kelola invoice finance, review jadwal dept Finance. |
| **6** | Dimas Pratama | `manager` | `Bisnis Development`| Kelola pipeline CRM/leads deals bisnis, ajukan jadwal dept & antar-dept. |
| **7** | Fadhil Ihsan | `karyawan` | `Bisnis Development`| Pipeline deals & task bisnis dev. |
| **8** | Joko Susanto | `asisten_manager` | `HR` (sub: `GA`) | **Asisten Manager GA**: memimpin operasional GA di bawah naungan HR, kontrol inventaris, fasilitas & tiket GA. **DILARANG AKSES FITUR HR MURNI**. |
| **9** | Rudy Hermanto | `karyawan` | `HR` (sub: `GA`) | Staf eks-GA: **HANYA bisa akses fitur GA** (Inventaris, Tiket Fasilitas). **DILARANG AKSES FITUR HR**. |
| **10** | Nadia Safitri | `karyawan` | `Resepsionis` (Mandiri) | Presensi, ajukan cuti & reimburst, monitoring fasilitas/tamu lobby, tidak terikat departemen lain. |
| **11** | Bagus Wicaksono | `karyawan` | `Trainer` (Mandiri) | Presensi, ajukan cuti & reimburst, penjadwalan & task training, berdiri sendiri tanpa departemen lain. |
| **12** | Fida | `manager` | `HR` (sub: `HR`) | Manager HR, kelola karyawan, rekrutmen, reset kehadiran, approval cuti, akses GA & HR. |
| **13** | Prakoso | `manager` | `Trainer` | Manager Trainer, koordinasi dan approval jadwal pelatihan perusahaan. |
| **14** | Agustiadi | `karyawan` | `HR` (sub: `HR`) | Staf HR murni: presensi, operasional HR. **DILARANG AKSES FITUR GA**. |
| **15** | Aan | `karyawan` | `HR` (sub: `HR`) | Staf HR murni: presensi, operasional HR. **DILARANG AKSES FITUR GA**. |
| **16** | Risma | `manager` | `Resepsionis` | Manager Resepsionis, pengelolaan resepsionis, fasilitas & tamu lobby. |
| **17** | Yuni | `karyawan` | `HR` (sub: `GA`) | Staf eks-GA: **HANYA akses fitur GA** (Inventaris & Fasilitas). **DILARANG AKSES FITUR HR**. |
| **18** | Asep | `karyawan` | `HR` (sub: `GA`) | Staf eks-GA: **HANYA akses fitur GA** (Inventaris & Fasilitas). **DILARANG AKSES FITUR HR**. |
| **19** | Harlan | `asisten_manager` | `HR` (sub: `GA`) | **Asisten Manager GA**: memimpin operasional GA di bawah HR, kontrol inventaris, tiket & fasilitas GA. **DILARANG AKSES FITUR HR MURNI**. |
| **20** | Mia | `karyawan` | `HR` (sub: `GA`) | Staf eks-GA: **HANYA akses fitur GA** (Inventaris & Fasilitas). **DILARANG AKSES FITUR HR**. |
| **21** | Asim | `karyawan` | `HR` (sub: `GA`) | Staf eks-GA: **HANYA akses fitur GA** (Inventaris & Fasilitas). **DILARANG AKSES FITUR HR**. |
| **22** | Galang | `karyawan` | `HR` (sub: `HR`) | Staf HR murni: presensi & tugas departemen HR. **DILARANG AKSES FITUR GA**. |
| **23** | Jaka | `karyawan` | `Bisnis Development` | Staf Bisnis Development, pipeline deals CRM & task. |
| **24** | Budi | `karyawan` | `Bisnis Development` | Staf Bisnis Development, pipeline deals CRM & task. |
| **25** | Danu | `karyawan` | `HR` (sub: `HR`) | Staf HR murni: presensi & tugas departemen HR. **DILARANG AKSES FITUR GA**. |
| **26** | Viola | `karyawan` | `Finance` | Staff Finance, reimbursement, invoice & administrasi keuangan. |
| **27** | Disa | `karyawan` | `Bisnis Development` | Staf Bisnis Development, pipeline deals CRM & task. |
| **28** | Novitasari | `manager` | `Finance` | Manager Finance, approval reimbursement, laporan keuangan. |
| **29** | Wahyu | `manager` | `Bisnis Development` | Manager Bisnis Development, approval & supervisi pipeline deals. |
| **30** | Rahmat | `manager` | `HR` (sub: `HR`) | Manager HR, manajemen SDM, approval cuti & operasional, akses GA & HR. |
| **31** | Sekretaris Direktur | `sekretaris` | `Executive` (sub: `Sekretariat`) | **Sekretaris Direktur**: Monitoring & cek KPI serta kinerja seluruh departemen (HR, Finance, Bisnis Development, GA). Review & verifikasi awal jadwal yang diajukan ke Direktur Utama (karyawan/manager tidak bisa langsung menjadwalkan ke Direktur). Jalur komunikasi chat eksklusif para Manager (Manager tidak bisa chat langsung ke Direktur). **DILARANG mengambil keputusan operasional** (tidak ada approval cuti/reimbursement/inventaris/reset kehadiran). |

*Helper method otorisasi di `App\Models\User`:*
- `isDirektur()`, `isSekretaris()`, `isManager()`, `isAsistenManager()`, `isKaryawan()`
- `isPureHR()` -> Karyawan atau Manager HR murni (`department === 'HR' && sub_department !== 'GA'`).
- `canAccessGAFeatures()` -> Tim GA (Asisten Manager GA, Staf GA), Manager HR, Finance (approval inventaris), dan Direktur Utama. **Karyawan HR murni dilarang keras**.
- `canAccessHRFeatures()` -> Karyawan HR murni, Manager HR, dan Direktur Utama. **Staf GA dan Asisten Manager GA dilarang keras**.
- `canControlGA()` -> Asisten Manager GA, Staf GA, Manager HR, dan Direktur Utama (khusus pemrosesan tiket fasilitas & pengelolaan inventaris).
- `canApproveLeaves()` -> **Khusus Manager HR / Direktur** (Staf/Karyawan HR biasa HANYA bisa melihat & mengelola pengajuan cuti/izin/dispensasi miliknya sendiri, dilarang melihat pengajuan seluruh karyawan atau melakukan approval).
- `canQuickClock()` -> **Khusus Manager HR / Direktur** (Fitur Clock In Masuk, Clock Out Pulang, dan Presensi Cepat di web HANYA tersedia untuk Manager HR dan Direktur Utama. Semua karyawan & manager divisi lain tidak memiliki tombol clock in/out manual di web).
- `canViewCompanyMetrics()` -> **Khusus Manager HR / Direktur** (Kartu ringkasan metrik operasional perusahaan di Dashboard: Total Karyawan, Kehadiran Hari Ini, Cuti & Dispensasi, Perlu Persetujuan HANYA ditampilkan untuk Manager HR dan Direktur. Seluruh staf/karyawan & manager divisi lain disembunyikan dari tampilan metrik global ini).
- `canApproveReimbursements()` -> Finance / Direktur
- `canApproveInventoryRequests()` -> Finance / Manager HR / Direktur
- `canManageEmployees()` -> HR Murni / Direktur (Karyawan GA & Asisten Manager GA dilarang mengelola pegawai)
- `canManagePaklaring()` -> **Khusus HR Murni / Direktur** (Pembuatan dan penerbitan Surat Keterangan / Paklaring resmi LPK Gyokai Indonesia dengan template persis A4 Landscape).
- `canManageExecutiveLetters()` -> **Khusus Sekretaris Direktur & Direktur Utama** (Pengelolaan buku agenda surat keluar resmi kantor, registrasi nomor surat, auto-numbering, pengarsipan berkas digital, dan cetak lembar tanda terima ekspedisi).

---

## 3. Fitur Utama & Alur Bisnis (Core Workflows)

### A. Presensi & Mesin Fingerprint ADMS (Solution X609) serta Biometrik Face ID & GPS
- **Controller:** `App\Http\Controllers\AttendanceController.php` & `App\Http\Controllers\Api\AdmsAttendanceController.php`.
- **Fitur Baru Presensi Biometrik Wajah (Face ID) & Geolocation GPS:**
  - **Akses Pengguna:** Tersedia untuk **seluruh karyawan, manager, dan direksi** (`canFaceClock()`).
  - **Mekanisme Kamera Web:** Menggunakan webcam / kamera depan smartphone via HTML5 `navigator.mediaDevices.getUserMedia` dengan scanner overlay biometrik beranimasi radar, canvas snapshot beresolusi tajam, dan watermark digital otomatis (Nama Karyawan, Tanggal, Jam WIB, Koordinat GPS, dan watermark resmi *LPK Gyokai Biometric Verified*).
  - **Penyimpanan Foto Wajah:** Foto selfie kehadiran disimpan ke direktori `storage/app/public/attendance_photos/{Y-m}/` dan dicatat ke kolom `photo_in` (saat masuk) dan `photo_out` (saat pulang) pada tabel `attendances`.
  - **Geofencing & Deteksi GPS:** Koordinat satelit (`latitude`, `longitude`, `accuracy`) diambil via Browser Geolocation API. Jarak fisik dihitung menggunakan formula Haversine terhadap titik koordinat kantor pusat LPK Gyokai Indonesia (`OFFICE_LATITUDE` & `OFFICE_LONGITUDE`, toleransi radius `OFFICE_RADIUS_METERS`).
  - **Status Kerja & Radius:** Menampilkan indikator real-time *Dalam Radius Kantor* (&le; 500m / WFO) atau *Luar Radius Kantor* (WFH / Dinas Luar) tanpa memblokir presensi sah, sehingga fleksibilitas operasional terjaga dengan transparansi audit penuh.
  - **Tampilan Inspeksi HR & Direktur:** Pada tabel presensi web (`attendance/index.blade.php`), setiap presensi Face ID dilengkapi thumbnail foto wajah interaktif yang dapat diklik untuk membuka modal inspeksi foto resolusi tinggi, waktu persis, koordinat GPS, dan tautan langsung ke Google Maps (`open_in_new`).
  - **Streaming Foto Tanpa Symlink:** Endpoint `GET /attendance/photo/{attendance}/{type}` (`attendance.photo`) mengalirkan file foto langsung dari storage server via PHP response, memastikan foto presensi wajah dapat dimuat secara instan di shared hosting cPanel tanpa ketergantungan pada symlink Apache (`php artisan storage:link`).
  - **Rute Web:** `POST /attendance/face-record` (`attendance.faceRecord`), `GET /attendance/photo/{attendance}/{type?}` (`attendance.photo`).
- **Integrasi Mesin Fingerprint:** Mendukung protokol cloud ADMS ZKTeco / Solution via endpoint `/iclock/cdata`, `/iclock/ping`, `/iclock/getrequest`.
- **Filter Log Absensi:** Mendukung filter rentang tanggal (maksimal 31 hari/1 bulan) di view `attendance/index.blade.php`.
- **Akses HR:** Khusus akun departemen HR & Direktur dapat melihat log presensi **seluruh karyawan**, sedangkan karyawan biasa melihat riwayat presensi miliknya sendiri.
- **Audit Trail Presensi Manual / Presensi Cepat:**
  - Jika Manager HR atau Direktur melakukan absen manual untuk dirinya sendiri via web: log kehadiran otomatis mencantumkan label `[Absen Masuk Manual Mandiri oleh {Nama} ({Jabatan})]` / `[Absen Pulang Manual Mandiri...]`.
  - Jika Manager HR atau Direktur melakukan presensi cepat untuk karyawan/manager lain: log kehadiran otomatis mencantumkan label `[Diabsenkan Masuk oleh {Nama} ({Jabatan})]` / `[Diabsenkan Pulang oleh {Nama} ({Jabatan})]` pada kolom Catatan/Keterangan dan ditampilkan dengan badge audit khusus di UI serta laporan PDF.
- **Fitur Reset Kehadiran Seluruh Karyawan & Manager (Bilateral Approval):**
  - **Model & Migrasi:** `App\Models\AttendanceResetRequest.php` & tabel `attendance_reset_requests`.
  - **Hak Akses Pengajuan:** Hanya **Manager HRD** (`isHR() && isManager()`) dan **Direktur Utama** (`isDirektur()`).
  - **Alur Persetujuan Dua Arah:**
    1. Jika **Manager HRD** yang mengajukan: Wajib disetujui oleh **Direktur Utama**.
    2. Jika **Direktur Utama** yang mengajukan: Wajib disetujui oleh **Manager HRD**.
    3. **Anti Self-Approval:** Pembuat pengajuan dilarang keras menyetujui pengajuannya sendiri.
    4. **Penolakan:** Reviewer dapat menolak pengajuan dengan menyertakan alasan penolakan wajib.
  - **Cakupan Reset:** Pilihan `all_time` (menghapus seluruh rekaman kehadiran) atau `date_range` (menghapus data kehadiran pada rentang tanggal tertentu).
  - **Broadcast Notifikasi:** Saat pengajuan disetujui dan data direset, sistem otomatis menyiarkan notifikasi pengumuman resmi (`GeneralNotification`) kepada **seluruh akun karyawan dan manager** (`User::all()`) agar transparansi terjaga.
  - **Rute Web:**
    - `POST /attendance/reset-request` (`attendance.resetRequest`)
    - `POST /attendance/reset-requests/{id}/approve` (`attendance.approveReset`)
    - `POST /attendance/reset-requests/{id}/reject` (`attendance.rejectReset`)

### B. Kalender & Jadwal Kerja Berjenjang (Multi-Scope & Approval)
- **Controller:** `App\Http\Controllers\CalendarController.php`
- **Model:** `App\Models\CalendarSchedule.php`, `App\Models\CalendarScheduleApproval.php`
- **Aturan Cakupan (Scope):**
  1. `individu`: Karyawan & Manager bisa membuat. Langsung aktif (`approved`) untuk diri sendiri.
  2. `departemen`: Khusus Manager/Direktur. Berstatus `pending`, harus di-review dan disetujui oleh anggota/karyawan di departemen tersebut sebelum resmi terjadwal.
  3. `antar_departemen`: Manager/Direktur/Karyawan dapat memilih **satu atau lebih dari departemen tujuan** (`target_departments`: HR, Finance, Bisnis Development, GA, Resepsionis, Trainer, Executive). Berstatus `pending`, harus di-review dan disetujui oleh masing-masing Manager dari setiap departemen tujuan yang dipilih.
  4. **Aturan Khusus Pengajuan Jadwal ke Direktur Utama (Executive):**
     - Baik **karyawan maupun manager TIDAK BISA langsung mengajukan jadwal ke Direktur**.
     - Setiap jadwal yang ditujukan ke Direksi (`target_departments = ['Executive']`) berstatus `pending` dan **wajib melalui verifikasi dan persetujuan Sekretaris Direktur terlebih dahulu**.
     - Jadwal yang diajukan ke Direktur **HANYA AKAN MUNCUL di kalender Direktur setelah disetujui resmi oleh Sekretaris Direktur**.
     - Panel review pending Direksi hanya dimunculkan pada akun **Sekretaris Direktur**; Direktur terlindungi dari pengajuan sepihak.
- **Isolasi Visibilitas Jadwal Akun Direktur:**
  - Akun Direktur memiliki agenda kerja eksekutif mandiri dan **tidak melihat jadwal mentah/pending dari karyawan, manager, maupun departemen operasional**.
  - Direktur hanya melihat jadwal yang dibuat oleh dirinya sendiri (`user_id = authUser->id`), pengumuman resmi perusahaan (`is_company_wide = true`), atau jadwal lintas departemen yang telah diverifikasi dan disetujui oleh Sekretaris Direktur.
- **Anti Self-Approval & Mandatory Rejection Reason:** Pembuat jadwal tidak bisa meng-approve jadwalnya sendiri. Setiap penolakan jadwal wajib menyertakan alasan penolakan.

### C. Ekspor Laporan & Template PDF
- **Controller:** `App\Http\Controllers\ReportExportController.php`
- **PDF Views:** `resources/views/reports/kpi_pdf.blade.php`, `resources/views/reports/attendance_pdf.blade.php`.
- **Fitur:** Export PDF berbasis styling print A4 elegan (CSS page layout), ringkasan metrik statistik KPI, dan riwayat presensi yang bersih & siap cetak/analisis.

### D. Tema Gelap & Terang Adaptif (Dark / Light Mode)
- **File CSS:** `resources/css/app.css`
- **Mekanisme:** Toggle tema disimpan di `localStorage ('gyokai_theme')`.
- **Aturan Kontras:** Saat mode terang aktif, seluruh warna teks (angka, headline, label, table row, input) disesuaikan secara otomatis (`text-slate-900`, `text-slate-800`, dll.), dan elemen bertingkat kartu berlatar terang dengan kontras tajam.

### E. Optimasi Performa Pop-up / Modal Formulir
- **File:** `resources/views/layouts/app.blade.php` & view modul terkait.
- **Optimasi:** Mengeliminasi efek `backdrop-blur` pada seluruh overlay modal untuk menghilangkan GPU paint lag, menggunakan akselerasi `transform: translateZ(0)` dan transisi cepat (75ms).

### F. Sistem Notifikasi Terpadu (In-App Notifications)
- **Controller:** `App\Http\Controllers\NotificationController.php`
- **Class Notifikasi:** `App\Notifications\GeneralNotification.php` (Channel database)
- **Komponen UI:** 
  - Dropdown lonceng pada Top Header Bar (`resources/views/layouts/app.blade.php`) dengan indikator badge unread beranimasi pulse, preview 5 notifikasi terbaru, aksi "Tandai Semua Dibaca", dan link cepat ke modul terkait.
  - Menu navigasi sidebar "Notifikasi" dengan indikator jumlah notifikasi belum dibaca.
  - Halaman penuh riwayat notifikasi (`resources/views/notifications/index.blade.php`) dengan pagination, aksi hapus, dan direct link.
- **Trigger Notifikasi Otomatis:**
  - **Cuti & Izin:** Pengajuan cuti baru memberi notifikasi ke HR & Direktur; hasil persetujuan/penolakan cuti memberi notifikasi kembali ke pemohon.
  - **Reimbursement:** Klaim baru memberi notifikasi ke Finance & Direktur; persetujuan/pembayaran/penolakan memberi notifikasi ke pemohon.
  - **Tugas (Tasks):** Penugasan task baru memberi notifikasi ke PIC karyawan atau seluruh anggota tim; perubahan progres task memberi notifikasi ke pembuat tugas.

### G. Chat Departemen & Saluran Komunikasi Eksekutif
- **Controller:** `App\Http\Controllers\DepartmentChatController.php`
- **Model:** `App\Models\DepartmentMessage.php`, `App\Models\DirectMessage.php`
- **Tabel Database:** 
  - `department_messages` (`department`, `user_id`, `message`, `attachment_url`, `timestamps`)
  - `direct_messages` (`sender_id`, `receiver_id`, `message`, `read_at`, `timestamps`)
- **Fitur & Alur Bisnis:**
  - **Isolasi Privasi Departemen (Direktur TIDAK BISA melihat chat internal departemen):** Chat internal divisi murni diperuntukkan bagi koordinasi internal antara staf/karyawan dan manager di departemen tersebut. Direktur dilarang/diblokir melihat percakapan internal ini.
  - **Aturan Ketat Chat Manager ke Eksekutif (Hanya ke Sekretaris Direktur):**
    - Sesuai instruksi pimpinan, **fitur chat Manager HANYA terhubung ke Sekretaris Direktur** (Manager **DILARANG / TIDAK BISA chat langsung ke Direktur**).
    - Seluruh koordinasi eksekutif para Manager disaring, dijembatani, dan direspons melalui Sekretaris Direktur.
  - **Sekretaris Direktur Sebagai Executive Communication Hub:**
    - Sekretaris Direktur memiliki akses pusat obrolan: berkomunikasi langsung 1-on-1 dengan seluruh Kepala Departemen (Manager) dan memiliki saluran koordinasi privat dengan Direktur Utama.
  - **Saluran Direktur ↔ Sekretaris:**
    - Direktur Utama berkomunikasi langsung dengan Sekretaris Direktur untuk memberikan arahan eksekutif dan menerima laporan ringkasan komunikasi dari para Manager.
  - **Dropdown Saluran Chat pada Manager:** Pada akun Manager, menu di navigasi sidebar maupun tombol di header halaman chat berbentuk **dropdown interaktif** dengan dua pilihan saluran:
    1. **Chat Departemen:** Komunikasi internal bersama karyawan departemennya.
    2. **Chat Sekretaris Direktur:** Saluran koordinasi eksekutif privat ke Sekretaris Direktur Utama.
  - **Pemberitahuan Otomatis:** Setiap pesan baru memicu `GeneralNotification` in-app ke penerima terkait.
  - **Live Auto-Sync & Audio Cue:** Menggunakan asynchronous JSON fetch polling (3 detik) dengan Web Audio notification chime untuk pesan masuk baru.
  - **Fitur Interaktif Lanjutan:** Pencarian pesan & filter pengirim secara real-time, balasan pesan kontekstual (Quote/Reply context banner), pemilih quick emoji (`👍 ❤️ 🔥 👏 🎯 🚀 ✅ 😂`), tombol salin pesan ke clipboard, serta pencarian anggota tim di sidebar.
  - **Layout Fleksibel & Anti-Squish:** Menggunakan container CSS flexbox full-width (`flex: 1 1 0%; min-width: 0;` dan sidebar `320px`) yang responsif dan kokoh.

---

## 4. Peta File Kunci Proyek (Directory Sitemap)

```text
hris_app/
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/
│   │   │   ├── AdmsAttendanceController.php  # Integrasi mesin fingerprint Solution X609 (ADMS)
│   │   │   ├── AuthApiController.php         # REST API Mobile: Login Sanctum, Profil & Sesi
│   │   │   ├── AttendanceApiController.php   # REST API Mobile: Presensi Face ID, Geofence GPS & Anti Fake GPS
│   │   │   ├── LeaveApiController.php        # REST API Mobile: Cuti, Two-Tier Approval & Kuota
│   │   │   └── DashboardApiController.php    # REST API Mobile: Ringkasan Metrik & Notifikasi
│   │   ├── AttendanceController.php         # Absensi Web, Clock-In/Out, Filter Rentang Tanggal
│   │   ├── AuthController.php               # Login/Logout & Multi-Role Redirect
│   │   ├── CalendarController.php           # Kalender, Jadwal Kerja, Multi-Scope & Approval
│   │   ├── DashboardController.php          # Dashboard Analitik per Role & Departemen
│   │   ├── DepartmentChatController.php     # Komunikasi Chat Departemen (Manager & Karyawan)
│   │   ├── EmployeeController.php           # Master Data Karyawan (HR & Direktur)
│   │   ├── FacilityTicketController.php     # Pengaduan Fasilitas & Helpdesk GA
│   │   ├── FinanceController.php            # Reimbursement & Slip Gaji Karyawan
│   │   ├── GeneralAffairsController.php     # Inventaris Aset & ATK GA
│   │   ├── GuideController.php              # Panduan Penggunaan & Diagram Alur Sistem Seluruh Akun
│   │   ├── LeaveRequestController.php       # Pengajuan Cuti/Izin & Approval HR
│   │   ├── OffboardingController.php        # Offboarding Karyawan
│   │   ├── OutgoingLetterController.php     # Tata Persuratan Resmi, Penomoran Surat Keluar & Lembar Ekspedisi
│   │   ├── PaklaringController.php          # Generator & Penerbitan Surat Keterangan / Paklaring LPK
│   │   ├── ProfileController.php            # Pengaturan Profil, Sesi Perangkat & Log Aktivitas
│   │   ├── RecruitmentController.php        # Rekrutmen & Onboarding Calon Karyawan
│   │   ├── ReportExportController.php       # Ekspor PDF Laporan KPI & Presensi
│   │   └── TaskController.php               # Manajemen Task Departemen & Laporan Direktur
│   ├── Notifications/
│   │   └── GeneralNotification.php          # Notification class untuk database channel
│   └── Models/
│       ├── Attendance.php                   # Record Log Presensi
│       ├── CalendarSchedule.php             # Jadwal Kalender, Scope & Status Approval
│       ├── CalendarScheduleApproval.php     # Log Approver Jadwal Departemen/Antar-Dept
│       ├── DepartmentMessage.php            # Pesan Chat Internal Departemen
│       ├── Employee.php                     # Detail Karyawan (NIP, Posisi, Gaji, dll.)
│       ├── LeaveRequest.php                 # Pengajuan Cuti
│       ├── OfficeSupply.php                 # Master Data ATK Kantor & Ambang Batas Stok
│       ├── OfficeSupplyUsage.php            # Log Pemakaian & Distribusi ATK Karyawan
│       ├── OutgoingLetter.php               # Agenda Surat Keluar Resmi, Penomoran Otomatis & Status
│       ├── Paklaring.php                    # Record Arsip Surat Keterangan / Paklaring LPK
│       ├── Reimbursement.php                # Klaim Biaya Operasional
│       ├── SalarySlip.php                   # Slip Gaji
│       ├── Task.php                         # Tugas / Pekerjaan Tim
│       └── User.php                         # Akun Pengguna & Helper Permissions
├── database/
│   └── migrations/                          # Migrasi skema database aplikasi
├── resources/
│   ├── css/app.css                          # Variabel tema, override Dark/Light, Modal optim
│   └── views/
│       ├── layouts/app.blade.php            # Layout Master (Sidebar, Header, Modal Presensi & Notifikasi)
│       ├── chat/                            # UI Chat Departemen
│       │   └── index.blade.php
│       ├── notifications/                   # UI Halaman Index Notifikasi
│       │   └── index.blade.php
│       ├── calendar/index.blade.php         # UI Kalender Grid & Panel Agenda Kanan
│       ├── attendance/index.blade.php       # UI Log Presensi & Filter Tanggal
│       ├── leave/index.blade.php            # UI Pengajuan Cuti & Approval
│       ├── reports/                         # Template PDF Siap Cetak (KPI & Absensi)
│       ├── departments/                     # View khusus HR, Finance, GA, BizDev
│       └── secretary/                       # View khusus Sekretaris Direktur & Direksi
│           └── letters/index.blade.php      # UI Buku Agenda Surat Keluar & Penomoran Resmi
└── routes/
    ├── api.php                              # REST API terproteksi Bearer Token Sanctum (Mobile Android)
    └── web.php                              # Seluruh route web & endpoint ADMS iclock
```

---

## 5. Pedoman Penting bagi Agent Saat Mengedit / Menambah Fitur
1. **Periksa Role User:** Selalu gunakan helper method `auth()->user()->isDirektur()`, `isManager()`, atau `isKaryawan()` daripada hardcode string.
2. **Approval & Rejection Pattern:**
   - **Anti Self-Approval:** Pastikan setiap fungsi approval memvalidasi `if ($model->user_id === auth()->id()) { return back()->with('error', 'Anti-self approval'); }` dan mencatat `approved_by = auth()->id()`.
   - **Wajib Alasan Penolakan (Mandatory Rejection Note):** Setiap penolakan pengajuan (Cuti/Izin, Reimbursement, Pengajuan Inventaris GA, Jadwal Kalender, dll.) **WAJIB menyertakan alasan penolakan** baik di validasi controller backend (`required_if:status,rejected` / `required|string`) maupun frontend prompt/form input, serta disimpan dan ditampilkan kembali kepada pemohon.
3. **Penyelarasan Desain UI:** Pertahankan style dark mode elegan (`bg-[#111113]`, `border-[#26262e]`, aksen `rose`/`#e50914`, font headline bold).
4. **Performa UI:** Jangan menambahkan CSS `backdrop-blur` pada elemen modal popup dinamis agar tidak memicu lag rendering.
5. **Setelah Edit Blade / CSS:** Selalu jalankan `php artisan view:clear` atau `npm run build` jika memodifikasi file `app.css`/`app.js`.
6. **Mobile / Responsive Layout:** Navigasi sidebar mengadopsi pola off-canvas slide drawer di mobile (< 1024px) dengan tombol hamburger dan backdrop overlay, serta margin adaptif (`w-full lg:pl-64`, `px-3 sm:px-6`).
7. **Standar Enkripsi & Keamanan Data (Security & Privacy):**
   - **Password Protection:** 100% password user wajib di-hash menggunakan algoritma satu arah Bcrypt (`Hash::make()` / cost factor 12). Dilarang menyimpan plaintext password.
   - **Model Serialization Protection:** Model `User` menyembunyikan field sensitif (`$hidden = ['password', 'remember_token']`) dan menerapkan cast `'password' => 'hashed'`. (Lihat: `app/Models/User.php`)
   - **Isolasi Data Sensitif (RBAC):** Data pribadi seperti slip gaji (`SalarySlip`), nomor rekening, dan nominal take-home pay dilindungi oleh otorisasi role ketat di mana staf non-finance hanya bisa mengakses data miliknya sendiri (`employee_id === auth()->id()`).

---

## 6. Modul Khusus Resepsionis: Bank Data Member Gyokai (Live Cross-Project Sync)
- **Route Prefix:** `/receptionist/members` (`routes/web.php`)
- **Controller:** `App\Http\Controllers\MemberController.php`
- **Model Eloquent:** `App\Models\Member.php` (Connection: `gyokai` -> tabel `members` di database `gyokai02`)
- **Views:** `resources/views/receptionist/members/index.blade.php`, `create.blade.php`, `edit.blade.php`
- **Hak Akses:** Hanya dapat diakses oleh akun **Resepsionis** (`nadia.receptionist@gyokai.com` / `isResepsionis()`) dan **Direktur Utama**.
- **Mekanisme Sinkronisasi:**
  - Terhubung langsung ke database `gyokai02` tabel `members`.
  - Segala penambahan member baru, edit biodata, perubahan status inline/masal, maupun penghapusan member dari akun resepsionis di HRIS **otomatis ter-update seketika di akun Company `Gyokai02`**, begitu juga sebaliknya tanpa jeda waktu.
  - Dilengkapi filter lengkap (Nama/Email/NIK, Referensi, Gender, Umur, Status Seleksi), fitur Bulk Update status, dan Export CSV/Excel.

---

---

## 7. Alur Perubahan & Persetujuan Status Role Akun (Karyawan <-> Manager)
- **Tabel & Model:** `role_change_requests` & `App\Models\RoleChangeRequest.php`.
- **Aturan Bisnis & Wewenang:**
  1. **Manager HRD:**
     - Dapat mengajukan peningkatan (promosi) dari `karyawan` ke `manager`, atau penyesuaian (demosi) dari `manager` ke `karyawan`.
     - Perubahan role oleh Manager HRD **TIDAK langsung aktif**, melainkan masuk ke antrean approval Direktur (`status = 'pending'`).
     - Manager HRD **WAJIB menyertakan alasan** pengajuan.
     - Setelah diajukan, Direktur Utama menerima notifikasi sistem secara otomatis.
  2. **Direktur Utama:**
     - **Approval Pengajuan:** Direktur dapat menyetujui (`approve`) atau menolak (`reject`) pengajuan dari HRD di halaman Direktori Karyawan (`/employees`). Penolakan wajib menyertakan alasan penolakan.
     - **Perubahan Langsung (Direct Override):** Direktur Utama dapat **langsung mengubah role** kapan saja di modal atur akun tanpa memerlukan persetujuan siapa pun.
     - **Notifikasi Otomatis ke Manager HRD:** Setiap kali Direktur mengubah role (baik via persetujuan maupun perubahan langsung), seluruh Manager HRD dan user bersangkutan akan menerima notifikasi otomatis secara seketika.

---

## 8. Sistem Pengingat Otomatis (Reminder Jadwal & Tugas)
- **Service & Engine:** `App\Services\ReminderService.php` & Artisan Command `php artisan reminders:send`.
- **Scheduler:** Terdaftar di `App\Console\Kernel.php` (`dailyAt('07:00')`) dan dieksekusi secara otomatis saat pengguna membuka aplikasi (idempotent 1x per hari).
- **Aturan Pengingat (Business Logic):**
  1. **Pengingat Penjadwalan (Calendar Schedule):**
     - Pengingat dikirimkan tepat **1 hari sebelum jadwal (H-1)** kepada seluruh anggota/pemilik agenda terkait (Individu, Departemen, maupun Antar-Departemen).
  2. **Pengingat Tugas & Deadline (Tasks):**
     - **Deadline > 7 Hari:** Pengingat berkala dikirimkan setiap **7 hari sekali** (kelipatan sisa 7 hari menuju deadline, misal: sisa 28, 21, 14, 7 hari).
     - **Deadline Kritis / < 7 Hari:** Pengingat intensif dikirimkan tepat **3 hari sebelum deadline (H-3)** dan **1 hari sebelum deadline (H-1)**.
  3. **Pencegahan Spam / Duplikasi:** Menggunakan tagging berbasis tanggal pada riwayat database notifications sehingga aman dan tidak membanjiri notifikasi pengguna.

---

## 9. Sistem Pengiriman Slip Gaji (Email Dispatch & Sinkronisasi Gyokai02)
- **Controller & Models:** 
  - `App\Http\Controllers\FinanceDispatchController`
  - `App\Models\PayslipLog` (Connection: `gyokai`, Table: `payslip_logs`)
  - `App\Models\PayslipSendSession` (Connection: `gyokai`, Table: `payslip_send_sessions`)
  - `App\Models\SmtpMailer` (Connection: `gyokai`, Table: `smtp_mailers`)
- **Hak Akses & Otorisasi:**
  - Hanya dapat diakses oleh akun dengan role Departemen **`Finance`** dan **`Direktur`**. Akun departemen lain akan diblokir dengan kode respon HTTP `403 Forbidden`.
- **Rute Web (`/finance/dispatch`):**
  - `GET /finance/dispatch` (`finance.dispatch.index`): Dashboard upload berkas, monitoring sesi pengiriman, dan filter riwayat log pengiriman.
  - `POST /finance/dispatch/upload` (`finance.dispatch.upload`): Upload berkas (.pdf atau .zip maks 100MB), ekstraksi PDF, auto-parsing teks email & periode slip via `smalot/pdfparser`.
  - `POST /finance/dispatch/send/start` (`finance.dispatch.send.start`): Inisialisasi antrean pengiriman, dukungan input CC dan BCC masal, serta pencatatan ke `payslip_send_sessions`.
  - `GET /finance/dispatch/sending` (`finance.dispatch.sending`): Halaman live visual progress bar pengiriman email.
  - `POST /finance/dispatch/send/batch` (`finance.dispatch.send.batch`): Eksekutor batch (10 email per iterasi) dengan algoritma rotasi multi-SMTP Gmail pool, auto-failover, dan penanganan cooldown kuota limit harian.
  - `POST /finance/dispatch/send/cancel` (`finance.dispatch.send.cancel`): Pembatalan sesi pengiriman secara aman dan pencatatan status antrean tersisa.
  - `GET /finance/dispatch/reports/{sendSession}` (`finance.dispatch.report`): Laporan audit rinci per sesi pengiriman.
- **Sinkronisasi Dua Arah Gyokai02:**
  - Terhubung langsung ke shared database `gyokai02` via koneksi `gyokai` pada Eloquent. Segala aktivitas pengiriman, riwayat log, pembatalan, dan update kuota SMTP di `hris_app` seketika terbaca di `Gyokai02`, begitu pun sebaliknya.

---

## 10. Sistem Keamanan Akun, Pembatasan Device & Log Aktivitas
- **Tabel & Model:**
  - `user_devices` & `App\Models\UserDevice.php` (Session & Device Tracking)
  - `login_logs` & `App\Models\LoginLog.php` (Audit Riwayat Login/Logout/Terminasi)
  - `activity_logs` & `App\Models\ActivityLog.php` (Audit Jejak Aksi & Mutasi Akun)
- **Aturan Keamanan:**
  1. **Sesi Berakhir Otomatis Saat Keluar (Expire on Close):**
     - Konfigurasi `SESSION_EXPIRE_ON_CLOSE=true` di `.env` dan `config/session.php`.
     - Checkbox "Ingat Saya" ditiadakan pada form login. Sesi cookie browser tidak disimpan permanen, sehingga saat browser ditutup atau pengguna keluar dari web, sesi otomatis berakhir dan harus login kembali demi keamanan data perusahaan.
  2. **Batas Maksimal 3 Perangkat Bersamaan (Concurrent Devices Limit):**
     - Akun pengguna dibatasi maksimal aktif di **3 perangkat berbeda** secara bersamaan.
     - Jika pengguna login dari perangkat ke-4, sesi perangkat yang paling lama tidak aktif akan otomatis diakhiri (di-kick) dan dicatat status `terminated` pada log login.
     - Middleware `EnsureDeviceSessionIsValid` memverifikasi keabsahan sesi perangkat pada setiap request aktif.
     - Pengguna juga dapat memutus sesi perangkat lain secara mandiri melalui halaman Profil Akun.
  3. **Pencatatan Log Login & Log Aktivitas:**
     - Menangkap waktu, IP Address, User Agent, dan deteksi otomatis OS & Browser via `App\Services\DeviceDetector`.
     - Riwayat log login (sukses, gagal, logout, terminated) dan riwayat aktivitas akun ditampilkan langsung di tab keamanan halaman Profil Pengguna (`/profile`).

---

## 11. Penggabungan Departemen GA ke HR & Peran Asisten Manager GA (Segregation of Duties)
- **Latar Belakang & Restrukturisasi:**
  Departemen General Affairs (GA) secara resmi diintegrasikan ke dalam Departemen Human Resources (HR). Struktur organisasi GA kini bernaung di bawah kepemimpinan HR.
- **Skema Database & Kolom Baru:**
  1. Tabel `users`:
     - Kolom `role`: Diubah dari `ENUM` menjadi `VARCHAR(30)` untuk mendukung peran baru `asisten_manager`.
     - Kolom `sub_department`: `VARCHAR(50) NULL` (berisi `'GA'` untuk eks-personel GA atau `'HR'` untuk staf HR murni).
     - Kolom `department`: Seluruh eks-personel GA dimigrasikan ke `department = 'HR'`.
  2. Tabel `employees`:
     - Kolom `sub_department`: `VARCHAR(50) NULL` untuk sinkronisasi identitas unit kepegawaian.
- **Perubahan Role Eks-Manager GA:**
  - Akun **Joko Susanto** (ID 8) dan **Harlan** (ID 19) yang sebelumnya berstatus `manager` GA otomatis diperbarui menjadi `role = 'asisten_manager'` dengan jabatan `Asisten Manager GA`.
  - Asisten Manager GA memimpin operasional harian General Affairs (inventaris, fasilitas, maintenance) mendampingi Manager HR & Direktur Utama.
- **Prinsip Segregasi Hak Akses Ketat (Segregation of Duties):**
  1. **Karyawan Eks-GA (`sub_department = 'GA'`):**
     - **HANYA BISA** mengakses fitur-fitur GA (Menu Inventaris & Aset GA `/ga`, pelaporan dan pemrosesan kendala fasilitas `/facilities`).
     - **DILARANG KERAS** mengakses fitur-fitur internal HR (Direktori Pegawai `/employees`, Rekrutmen & Onboarding `/hr/recruitment`, Offboarding `/hr/offboarding`, Surat Paklaring `/hr/paklaring`). Akses diblokir oleh `DepartmentMiddleware` dengan respon HTTP `403 Forbidden`.
  2. **Karyawan HR Murni (`sub_department = 'HR'`):**
     - **TIDAK BISA** mengakses fitur-fitur inventaris GA (`/ga`) dan dilarang mengupdate progress tiket fasilitas.
     - Hanya personel berstatus GA yang berhak memproses dan mengelola inventaris/kendala fasilitas kantor.
  3. **Manager HR & Direktur Utama:**
     - Memiliki hak akses penuh (**Dual Access**) ke seluruh modul HR maupun modul GA secara terpadu.
- **Middleware & Controller Proteksi:**
  - `App\Http\Middleware\DepartmentMiddleware.php`: Memeriksa target departemen secara dinamis:
    - Target `ga`: Mengizinkan pengguna dengan `canAccessGAFeatures()`, memblokir staf HR murni dengan `abort(403)`.
    - Target `hr`: Mengizinkan pengguna dengan `canAccessHRFeatures()`, memblokir staf GA dan Asisten Manager GA dengan `abort(403)`.
  - `GeneralAffairsController@index`: Menolak staf HR murni dengan pengecekan `isPureHR() && !isManager() && !isDirektur()`.
  - `FacilityTicketController@updateProgress`: Membatasi wewenang update tiket fasilitas hanya bagi pengguna dengan otorisasi `canControlGA()`.
  - `EmployeeController.php`: Validasi pembuatan dan pembaruan akun (`store`, `update`, `updateAccount`) mendukung role `asisten_manager` dan sinkronisasi `sub_department`.

### Manajemen Stok & Pemakaian ATK Kantor (Office Supplies / Consumables):
- **Model:** `App\Models\OfficeSupply.php` & `App\Models\OfficeSupplyUsage.php`
- **Tabel Database:** 
  1. `office_supplies`: `item_code` (unique), `name`, `category`, `unit`, `stock_qty`, `min_stock`, `location`, `notes`, `timestamps`.
  2. `office_supply_usages`: `office_supply_id`, `employee_id`, `department`, `quantity`, `usage_date`, `purpose`, `recorded_by`, `notes`, `timestamps`.
- **Fitur & Logika Bisnis:**
  1. *Katalog Stok ATK Tersedia:* Manajemen barang habis pakai kantor (Kertas HVS, Pulpen, Spidol Whiteboard, Map Snelhechter, Toner Printer, Binder Clip, Lakban, dll.) lengkap dengan ambang batas stok minimum (`min_stock`) dan visual indikator status (Tersedia / Menipis / Habis).
  2. *Distribusi & Pencatatan Pemakaian:* Tim GA mencatat pengambilan ATK oleh karyawan dari departemen mana pun (HR, Finance, Bisnis Development, Executive, Resepsionis, Trainer) beserta jumlah, tanggal, dan keperluannya.
  3. *Pengurangan Stok Otomatis:* Stok fisik barang di gudang otomatis terpotong saat pemakaian dicatat (`decrement`). Sistem menolak pemakaian jika kuantitas melebihi sisa stok yang tersedia.
  4. *Restock / Pasokan Masuk:* Penambahan stok instan untuk barang baru datang dari pengadaan belanja ATK (`increment`).
  5. *Rollback Otomatis Saat Pembatalan:* Jika catatan pemakaian dihapus/dibatalkan, kuantitas stok otomatis dikembalikan ke gudang.
- **Rute Web:**
  - `POST /ga/supplies` (`ga.supplies.store`)
  - `PATCH /ga/supplies/{id}` (`ga.supplies.update`)
  - `DELETE /ga/supplies/{id}` (`ga.supplies.destroy`)
  - `POST /ga/supplies/{id}/restock` (`ga.supplies.restock`)
  - `POST /ga/supplies/usage` (`ga.supplies.recordUsage`)
  - `DELETE /ga/supplies/usage/{id}` (`ga.supplies.destroyUsage`)

### Tata Persuratan Resmi & Penomoran Surat Keluar (Sekretaris Direktur & Direktur Utama):
- **Model:** `App\Models\OutgoingLetter.php`
- **Tabel Database:** `outgoing_letters`
  - Kolom: `letter_number` (unique), `sequence_number`, `letter_category`, `organization_code`, `month_roman`, `year`, `letter_date`, `recipient`, `recipient_address`, `subject`, `regarding_summary`, `urgency` (biasa/penting/segera/rahasia), `status` (draft/reserved/signed/sent/archived), `signer_name`, `signer_title`, `file_path`, `notes`, `created_by`, `signed_at`, `sent_at`, `timestamps`.
  - Indexes: composite `[year, sequence_number]`, `status`, `letter_category`, `created_by`.
- **Standar Format Penomoran Resmi:**
  - Pola: `[Nomor Urut 3 Digit]/[Kode Kategori]/[Kode Organisasi]/[Bulan Romawi]/[Tahun]`
  - Contoh: `001/SK/DIR-GYOKAI/XI/2026`
  - Auto-Increment Tahunan: Nomor urut berulang dari 1 pada setiap pergantian tahun takwim (`getNextSequence($year)`).
  - Kategori Surat: SK (Surat Keputusan), SPK (Surat Perjanjian Kerja), ST (Surat Tugas), UND (Surat Undangan), SKet (Surat Keterangan), SPH (Surat Penawaran Harga), MOU (Nota Kesepahaman), NOTA (Nota Dinas), SE (Surat Edaran), BAST (Berita Acara Serah Terima), UMUM (Surat Keluar Umum).
- **Fitur & Logika Bisnis:**
  1. *Registrasi Nomor Surat Baru:* Otomatisasi perhitungan nomor urut berikutnya dan konversi bulan romawi secara real-time via AJAX endpoint `GET /secretary/letters/next-number`.
  2. *Reservasi / Booking Nomor Cepat:* Kemampuan memesan nomor urut resmi lebih awal sebelum draft fisik surat selesai ditandatangani untuk mencegah bentrok nomor surat antar dokumen.
  3. *Manajemen Status Persuratan:* Alur status transparan: Draft &rarr; Reserved &rarr; Signed &rarr; Sent &rarr; Archived.
  4. *Pengarsipan Berkas Digital:* Unggah salinan berkas resmi (PDF, JPG, PNG hingga 10MB) yang disimpan terstruktur per tahun takwim (`storage/app/public/outgoing_letters/{year}/`).
  5. *Streaming Berkas Tanpa Symlink:* Endpoint `GET /secretary/letters/{id}/download` mengalirkan file langsung via controller stream response untuk kompatibilitas penuh lingkungan hosting cPanel yang menonaktifkan symlink.
  6. *Cetak Lembar Tanda Terima Ekspedisi:* Fitur cetak bukti tanda terima pengiriman surat fisik / kurir (Lembar Ekspedisi Persuratan) siap print A4.
- **Rute Web:**
  - `GET /secretary/letters` (`secretary.letters.index`)
  - `POST /secretary/letters` (`secretary.letters.store`)
  - `GET /secretary/letters/next-number` (`secretary.letters.nextNumber`)
  - `PATCH /secretary/letters/{id}` (`secretary.letters.update`)
  - `DELETE /secretary/letters/{id}` (`secretary.letters.destroy`)
  - `GET /secretary/letters/{id}/download` (`secretary.letters.download`)

---

## 12. Riwayat Pembaruan Dokumentasi (Changelog)
- **2026-11-03:** **Implementasi Modul Tata Persuratan Resmi & Penomoran Surat Keluar (Buku Agenda & Ekspedisi Persuratan Eksekutif):**
  - **Latar Belakang:** Menambahkan modul Buku Agenda Surat Keluar Resmi & Penomoran Otomatis pada akun Sekretaris Direktur dan Direktur Utama untuk menertibkan tata naskah dinas, penomoran resmi perusahaan, pengarsipan berkas surat digital, dan bukti ekspedisi pengiriman surat keluar.
  - **Skema & Migrasi Database:**
    - Membuat migrasi `2026_11_03_122731_create_outgoing_letters_table.php` dengan indeks performa pencarian.
    - Tabel `outgoing_letters`: `letter_number` (unique), `sequence_number`, `letter_category`, `organization_code`, `month_roman`, `year`, `letter_date`, `recipient`, `recipient_address`, `subject`, `regarding_summary`, `urgency`, `status`, `signer_name`, `signer_title`, `file_path`, `notes`, `created_by`, `signed_at`, `sent_at`.
  - **Model & Logika Bisnis:**
    - `OutgoingLetter.php`: Helper `convertMonthToRoman()`, `formatLetterNumber()`, `getNextSequence()`, `categoryOptions()`, `organizationOptions()`, dan accessor status badge warna Tailwind.
    - `User.php`: Penambahan helper `canManageExecutiveLetters()` yang membatasi hak akses eksklusif hanya untuk Sekretaris Direktur (`isSekretaris()`) dan Direktur Utama (`isDirektur()`).
    - `OutgoingLetterController.php`: Method `index`, `store`, `update`, `destroy`, `getNextNumber` (AJAX real-time generator nomor surat), dan `downloadFile` (resilient direct streaming tanpa symlink).
  - **Antarmuka & Komponen Desain (`secretary/letters/index.blade.php`):**
    - Desain premium tema dark GYOKAI dengan kartu statistik: Total Surat Keluar Tahun Berjalan, Terkirim & Diarsipkan, Pending / Reservasi Nomor, dan Terbit Bulan Ini.
    - Filter pencarian cepat: Kata kunci (perihal/nomor/penerima/penandatangan), Kategori Surat, Status Surat, Tingkat Urgensi, dan Filter Tahun Takwim.
    - Modal popup interaktif tanpa `backdrop-blur`: Modal Registrasi Surat Keluar (Live Preview Nomor Surat), Modal Reservasi Nomor Cepat, Modal Edit / Pembaruan Status Surat, dan Modal Cetak Lembar Ekspedisi Pengiriman Surat.
    - Navigasi sidebar dinamis pada `layouts/app.blade.php` dengan badge "Sekretaris" / "Direksi".
  - **Seeder & Automated Testing:**
    - Seeder `OutgoingLetterSeeder.php` dengan 7 contoh surat resmi realistis (SK, SPK, ST, UND, SKet, SPH, MOU).
    - Test Suite `tests/Feature/OutgoingLetterTest.php` (5 pengujian mencakup index, otorisasi 403 karyawan biasa, AJAX auto-numbering, pendaftaran surat, pembaruan status; seluruhnya lolos 100%).
- **2026-11-03:** **Implementasi Modul Manajemen Stok & Pemakaian ATK Kantor (Office Supplies / Consumables):**
  - **Latar Belakang:** Menambahkan modul pengelolaan persediaan barang habis pakai kantor (ATK) pada menu General Affairs (GA) untuk memonitor ketersediaan stok fisik dan mencatat distribusi pemakaian ke seluruh departemen (HR, Finance, Bisnis Development, Executive, Resepsionis, Trainer).
  - **Skema & Migrasi Database:**
    - Membuat migrasi `2026_11_03_080327_create_office_supplies_and_usages_tables.php`.
    - Tabel `office_supplies`: `item_code` (unique), `name`, `category`, `unit`, `stock_qty`, `min_stock`, `location`, `notes`.
    - Tabel `office_supply_usages`: `office_supply_id`, `employee_id`, `department`, `quantity`, `usage_date`, `purpose`, `recorded_by`, `notes`.
  - **Model & Logika Bisnis:**
    - `OfficeSupply.php`: Relasi ke usages, helper method `isLowStock()`, `isOutOfStock()`, serta accessor `status_label` dan `status_color`.
    - `OfficeSupplyUsage.php`: Relasi ke `officeSupply`, `employee`, dan `recorder` (User).
    - `GeneralAffairsController.php`: Method `storeSupply`, `updateSupply`, `restockSupply`, `destroySupply`, `recordSupplyUsage`, dan `destroySupplyUsage`.
    - Pengurangan stok otomatis saat pemakaian dicatat (`decrement`) dengan validasi anti-overdraft (menolak pencatatan jika stok tidak mencukupi).
    - Rollback stok otomatis saat catatan pemakaian dibatalkan/dihapus (`increment`).
  - **Antarmuka & Komponen Desain (`ga/index.blade.php`):**
    - Tab utama "Stok & Pemakaian ATK Kantor" dengan badge peringatan restock jika ada stok kritis.
    - Metrik kartu: Total Jenis ATK, Total Stok Fisik Unit, Peringatan Stok Menipis/Habis, dan Total Terdistribusi Bulan Ini.
    - Sub-tab terpisah: *Katalog Stok ATK Tersedia* dan *Riwayat Log Pemakaian & Distribusi ATK*.
    - Modal popup interaktif tanpa `backdrop-blur`: Modal Catat Pemakaian, Modal Tambah Master ATK, Modal Restock, dan Modal Edit ATK.
  - **Seeder & Automated Testing:**
    - Seeder `OfficeSupplySeeder.php` dengan 12 data master ATK kantor umum dan riwayat pemakaian awal.
    - Test Suite `tests/Feature/GeneralAffairsAtkTest.php` (6 pengujian lolos 100%).

- **2026-11-02:** **Penyelesaian Komprehensif Template Paklaring (Blank / 404 Image & `filemtime` Stat Failed):**
  - **Masalah:** Di server live/cPanel (`/home/lpkx2536/...`), berkas fisik `public/images/paklaring/exact_base_template.png` belum ter-upload sehingga:
    1. Awalnya memicu fatal error `filemtime(): stat failed for ...`.
    2. Setelah pengecekan filemtime diamankan, halaman tidak crash namun background sertifikat kosong/putih (404 Not Found), logo/watermark/tanda tangan hilang, dan pratinjau live kolaps karena gambar dasar tidak termuat.
  - **Solusi Mandiri & Self-Healing (100% Bebas Ketergantungan Upload Manual):**
    1. *Self-Contained Controller ([PaklaringController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/PaklaringController.php)):* Menyematkan data base64 template asli (2480x1754 px) langsung di dalam controller sehingga tidak membutuhkan class eksternal atau service baru.
    2. *Auto-Restore Fisik:* Method `PaklaringController::getTemplateUrl()` secara otomatis membuatkan kembali berkas `public/images/paklaring/exact_base_template.png` di hosting jika belum ada.
    3. *Inline Data URI Fallback:* Jika izin tulis folder hosting dibatasi, view otomatis fallback menggunakan Data URI Base64 murni (`data:image/png;base64,...`) sehingga pratinjau maupun cetak A4 Landscape dijamin 100% muncul seketika tanpa memerlukan transfer berkas manual.
    4. *Endpoint Khusus:* Menambahkan route `GET /hr/paklaring/template-image` (`hr.paklaring.templateImage`) pada [routes/web.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/routes/web.php) dan method `templateImage()` pada [PaklaringController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/PaklaringController.php).
    5. *Penyempurnaan View:* Mengoptimalkan [index.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/paklaring/index.blade.php) dan [print.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/paklaring/print.blade.php) agar selalu merender gambar blangko lengkap tanpa ketergantungan class eksternal.
- **2026-10-31:** **Penggabungan Departemen GA ke Departemen HR & Implementasi Role Asisten Manager GA:**
  - **Integrasi Organisasi:** Menggabungkan divisi General Affairs (GA) ke bawah naungan divisi Human Resources (HR).
  - **Pembaruan Skema & Migrasi Database:**
    - Membuat dan menjalankan migrasi `2026_10_31_130000_merge_ga_to_hr_and_add_asisten_manager_role.php`.
    - Mengubah kolom `users.role` menjadi `VARCHAR(30)` untuk mendukung peran baru `asisten_manager`.
    - Menambahkan kolom `sub_department` pada tabel `users` dan `employees`.
    - Memigrasikan seluruh akun eks-GA ke `department = 'HR'` dan `sub_department = 'GA'`.
  - **Penetapan Role Asisten Manager GA:**
    - Mengubah eks-Manager GA (Joko Susanto & Harlan) menjadi `role = 'asisten_manager'` dan `role_title = 'Asisten Manager GA'`.
  - **Segregasi Hak Akses Ketat (Segregation of Duties):**
    - Karyawan eks-GA (Rudy Hermanto, Yuni, Asep, Mia, Asim) hanya dapat mengakses fitur-fitur GA (Inventaris, Tiket Fasilitas), dilarang membuka modul HR (Direktori Pegawai, Rekrutmen, Offboarding, Paklaring).
    - Karyawan HR murni (Clarissa Putri, Agustiadi, Aan, Galang, Danu) dilarang mengakses modul GA dan dilarang mengupdate tiket fasilitas.
    - Manager HR (Siti Rahmawati, Fida, Rahmat) dan Direktur Utama (Hendra Wijaya) memiliki hak akses penuh ke modul HR maupun GA.
  - **File yang Dimodifikasi:**
    1. [User.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Models/User.php): Menambahkan `isAsistenManager()`, `isPureHR()`, `canAccessGAFeatures()`, `canAccessHRFeatures()`, dan memperbarui `canControlGA()`, `canManageEmployees()`, `canManagePaklaring()`.
    2. [DepartmentMiddleware.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Middleware/DepartmentMiddleware.php): Memfilter target `ga` dan `hr` berdasarkan hak akses segregasi.
    3. [GeneralAffairsController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/GeneralAffairsController.php): Proteksi 403 untuk staf HR murni dan validasi `canControlGA()`.
    4. [FacilityTicketController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/FacilityTicketController.php): Sinkronisasi pemuatan staf GA (`sub_department = 'GA'`) dan proteksi 403 pada `updateProgress`.
    5. [DashboardController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/DashboardController.php): Penyesuaian KPI departemen untuk Asisten Manager GA & Staf GA.
    6. [EmployeeController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/EmployeeController.php): Penambahan opsi `asisten_manager` pada validasi akun dan penyimpanan `sub_department`.
    7. [app.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/layouts/app.blade.php): Penyesuaian menu sidebar dan profil top navbar untuk Asisten Manager GA (`Asisten Mgr`, badge violet) serta segregasi modul divisi HR vs GA.
    8. [employees/index.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/employees/index.blade.php): Penambahan badge `ASISTEN MGR`, pemetaan `sub_department`, dan opsi dropdown Asisten Manager pada modal akun.
- **2026-10-30:** **Fitur Controlling Sisa Cuti Tahunan (Mandiri per Karyawan/Manager vs Monitoring Seluruh Personel oleh HR):**
  - **Tujuan:** Mengakomodasi kebutuhan pemantauan sisa cuti tahunan yang transparan dan aman: setiap staf/karyawan dan manager divisi non-HR hanya dapat mengontrol kuota cuti tahunan milik mereka sendiri, sedangkan **Manager HR** dan **Direktur Utama** (`canApproveLeaves()`) memiliki akses penuh untuk memantau, memfilter, dan mengaudit saldo cuti tahunan seluruh 30 personel perusahaan.
  - **Arsitektur Otorisasi & Visibilitas:**
    1. *Karyawan & Manager Non-HR:* Memantau saldo cuti mandiri (Hak Kuota, Terpakai/Approved, Pending, Sisa Efektif, multi-segment progress bar) dan tabel riwayat pemakaian cuti tahunan pribadinya. Data personel lain diproteksi secara ketat.
    2. *Manager HR & Direktur (`canApproveLeaves()`):* Memantau KPI kuota cuti global perusahaan (Total Kuota, Total Terpakai, Total Sisa, Kuota Kritis) serta tabel master controlling seluruh 30 karyawan dan manager dengan pencarian nama/NIP real-time, filter departemen, filter status sisa (aman, menipis, kritis, habis), dan fitur penyesuaian kuota modal cepat.
  - **Rute Baru:** `PATCH /leave/employees/{id}/quota` (`LeaveRequestController@updateQuota`) dengan proteksi otorisasi ketat khusus Manager HR & Direktur.
  - **File yang Dimodifikasi:**
    1. [LeaveRequestController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/LeaveRequestController.php): kalkulasi metrik kuota personal, agregasi KPI perusahaan, relasi riwayat cuti, dan method `updateQuota`.
    2. [routes/web.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/routes/web.php): penambahan rute `leave.updateQuota`.
    3. [resources/views/leave/index.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/leave/index.blade.php): sub-tab navigasi (Permohonan Cuti vs Control Sisa Cuti), kartu visual saldo pribadi, dan tabel monitoring 30 personel untuk Manager HR.
- **2026-10-30:** **Modul Penerbitan Surat Keterangan / Paklaring (LPK Gyokai Indonesia):**
  - **Tujuan:** Memenuhi kebutuhan Departemen HR & Direksi dalam menerbitkan Surat Keterangan / Paklaring resmi untuk peserta pemagangan atau mantan karyawan dengan template sertifikat A4 Landscape yang sama persis 100% dengan dokumen asli.
  - **Model & Database:** `App\Models\Paklaring.php` & migrasi tabel `paklarings` (menyimpan no sertifikat, nama penerima, program/posisi, periode masa berlaku, unit penempatan, kota, tanggal terbit, dan relasi pembuat).
  - **Controller & Rute:** [PaklaringController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/PaklaringController.php) dengan route prefix `/hr/paklaring` (`index`, `store`, `print`, `directPrint`, `destroy`).
  - **Fitur Antarmuka (UI/UX):**
    1. *Form Generator Interaktif:* Input data penerima (mendukung auto-fill nama/posisi/join date dari data karyawan terdaftar atau input bebas peserta luar), program/jabatan, periode masa berlaku, unit perusahaan penempatan, dan tempat/tanggal terbit.
    2. *Live Visual Preview (Real-Time):* Pratinjau langsung berdampingan menggunakan Alpine.js yang menampilkan template sertifikat A4 Landscape lengkap dengan watermark logo "G" Gyokai, bingkai merah marun ganda, font kaligrafi cursive elegan untuk nama, dan legalisasi stempel resmi + tanda tangan Direktur Anita Sandra Dewi.
    3. *Mode Cetak / PDF Presisi (Pixel Perfect):* View cetak [paklaring/print.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/paklaring/print.blade.php) yang dioptimalkan untuk ukuran `A4 landscape` tanpa margin dan siap print / Save as PDF via browser dialog (`window.print()`).
    4. *Riwayat Arsip Dokumen:* Tabel pencatatan riwayat paklaring yang telah diterbitkan lengkap dengan kolom *Diterbitkan Oleh* (`creator` / nama dan jabatan penerbit), tombol cetak ulang dan hapus arsip.
    5. *Penyempurnaan Visual Base Template & Tipografi:* Menghilangkan artefak warna kotak di sekitar teks dengan membersihkan stream teks PDF asli secara langsung tanpa menimpa patch putih buatan (sehingga watermark 'G' dan kertas putih 100% mulus/seamless), memisahkan baris 'Tangerang, [Tanggal]' dengan 'LPK Gyokai Indonesia' menggunakan layout vertikal berjarak rapi, serta menggunakan font kaligrafi resmi **Apple Chancery** untuk nama penerima (tersedia via `@font-face` lokal & sistem).
    6. *Penyempurnaan Alur Simpan & Cetak:* Memperbaiki pembacaan input formulir pada controller sehingga dokumen tersimpan secara presisi ke tabel `paklarings`, pencatatan akun pembuat (`created_by`), dan tombol *Simpan & Buka Cetak* langsung mengarahkan pengguna ke halaman cetak dokumen (`hr.paklaring.print`).
  - **Hak Akses:** Khusus Departemen **HR** dan **Direktur Utama** melalui helper `canManagePaklaring()`.
- **2026-10-30:** **Redesain & Optimalisasi Estetika Pusat Kontrol Sisa Cuti Tahunan (`/leave?tab=balances`):**
  - **Latar Belakang:** Sebelumnya tampilan kontrol saldo cuti pribadi pada tab kedua (`Control Sisa Cuti Tahunan`) mengalami layout collapse (menyempit menjadi kolom kurus ~250px di sisi kiri layar dengan teks berantakan dan area kanan kosong/gelap) serta inefisiensi ruang karena kartu ringkasan kuota menumpuk statis di atas tab.
  - **Penyempurnaan Arsitektur & Estetika UI:**
    1. *Struktur Full-Width Terjamin:* Memperbaiki layout container dengan `w-full flex flex-col lg:flex-row items-start gap-6`, kolom kiri fleksibel (`w-full lg:flex-1 min-w-0`), dan sidebar kanan (`w-full lg:w-80 xl:w-96 shrink-0`), mengeliminasi bug class Tailwind uncompiled dan wrapping teks per karakter.
    2. *Hero Quota Dashboard Mandiri:* Menghadirkan Hero Card modern di bagian atas Tab 2 dengan identitas profil (Avatar, Nama, NIP, Departemen), badge status sisa kuota (Aman/Menipis/Habis dengan animasi denyut), 4 tile metrik simetris (*Hak Kuota Resmi*, *Telah Digunakan*, *Sedang Diajukan*, *Sisa Efektif*), serta visual multi-segment progress bar tebal dengan warna gradien terpisah (Rose untuk terpakai, Amber untuk pending, Emerald untuk sisa tersedia) disertai legenda persentase.
    3. *Tabel Riwayat Cuti Pribadi Responsif:* Mengoptimalkan tabel riwayat dengan header `whitespace-nowrap px-4 py-3`, penanda durasi hari kerja, badge status permohonan (*Disetujui*, *Menunggu*, *Ditolak*), identitas penyetuju HR, dan tampilan *Empty State* estetik berilustrasi menarik saat kuota karyawan masih utuh (12 hari) lengkap dengan tombol cepat pengajuan baru.
    4. *Sidebar Ketentuan SOP & Privasi Saldo:* Dua kartu terpisah di sisi kanan yang merangkum aturan resmi cuti tahunan PT Gyokai (jatah 12 hari kalender, kewajiban pengajuan H-3, jalur verifikasi HR dan Direksi) serta penjelasan resmi keamanan privasi saldo cuti individu.
    5. *Quick Quota Tile di Tab 1:* Menambahkan widget ringkas pada tab *Daftar Permohonan Cuti* yang menampilkan sisa kuota aktif dan link instan menuju Tab 2, sehingga pengguna tidak perlu menebak sisa cuti sebelum mengajukan permohonan.
    6. *Monitoring Global 30 Personel Tetap Kokoh:* Tampilan khusus Manager HR dan Direktur (`canApproveLeaves()`) tetap menyediakan monitoring agregat 5 kartu metrik, pencarian instan nama/NIP, filter departemen, filter status sisa, serta modal penyesuaian kuota cuti tanpa lag GPU (`backdrop-blur` prohibited).
- **2026-10-30:** **Pemberlakuan Toleransi Keterlambatan Presensi 15 Menit (Batas 09:15:00 WIB):**
  - **Aturan Jam Masuk & Toleransi:** Jam masuk resmi kantor adalah **09:00 WIB**, dengan kebijakan **toleransi keterlambatan selama 15 menit** (hingga pukul **09:15:00 WIB**).
  - **Logika Kalkulasi Status:**
    - Scan masuk / clock in **&le; 09:15:00 WIB** otomatis berstatus **Tepat Waktu** (*on_time*).
    - Scan masuk / clock in **&gt; 09:15:00 WIB** otomatis berstatus **Terlambat** (*late*).
  - **File yang Disesuaikan:**
    1. [AttendanceController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/AttendanceController.php): update evaluasi status clock in web menjadi `($nowTime > '09:15:00') ? 'late' : 'on_time'`.
    2. [AdmsAttendanceController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/Api/AdmsAttendanceController.php): update evaluasi status scan ADMS Solution X609 menjadi `($time > '09:15:00')`.
    3. [RecalculateAttendanceStatus.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Console/Commands/RecalculateAttendanceStatus.php): penyesuaian artisan command sinkronisasi status presensi historis.
    4. [attendance/index.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/attendance/index.blade.php): badge metrik "Tepat Waktu (&le; 09:15)" & "Terlambat (&gt; 09:15)" serta panduan modal.
    5. [guide/index.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/guide/index.blade.php): diagram alur & SOP presensi.
- **2026-10-29:** **Restriksi Visibilitas 4 Kartu Metrik Operasional Dashboard:**
  - **Tampilan:** Kartu ringkasan operasional di halaman utama Dashboard (`resources/views/dashboard/index.blade.php`), yang mencakup:
    1. *Total Karyawan* (jumlah personel & rekrutmen bulan ini)
    2. *Kehadiran Hari Ini* (rasio hadir, jumlah tepat waktu & terlambat)
    3. *Cuti & Dispensasi* (rasio tidak masuk cuti)
    4. *Perlu Persetujuan* (antrean permohonan tertunda)
  - **Aturan Otorisasi:** Menggunakan helper method `canViewCompanyMetrics()` di [User.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Models/User.php). Kartu data ini **HANYA ditampilkan untuk Manager HR (`isHR() && isManager()`) dan Direktur Utama (`isDirektur()`)**.
- **2026-10-30:** **Otomasi Pencatatan Presensi & Sinkronisasi Alasan Dispensasi Izin Terlambat (`late_permission`):**
  - **Latar Belakang & Aturan Bisnis:**
    - Saat karyawan atau manager mengajukan izin terlambat (dispensasi keterlambatan masuk kantor), sistem secara otomatis mencatat kehadiran (`attendances` record) pada tanggal yang diajukan dengan jam masuk (`clock_in`) sesuai perkiraan jam tiba (`expected_time`) yang diajukan pemohon.
    - Status awal kehadiran dicatat sebagai `late` dengan keterangan pending permohonan dispensasi keterlambatan.
    - Saat permohonan disetujui berjenjang oleh **Manager Departemen** (Tahap 1) dan **Manager HR / Direktur** (Tahap 2):
      1. Status kehadiran pada tabel `attendances` resmi disahkan menjadi `on_time` (bebas dari penalti keterlambatan).
      2. Kolom `notes` pada log kehadiran mencatat keterangan resmi bahwa yang bersangkutan melakukan absen dengan alasan dispensasi yang diajukan:
         `Dispensasi Keterlambatan Kantor Resmi Disetujui [Manager Dept: {Nama} & Manager HR: {Nama}]. Alasan: {Alasan Pengajuan}` (atau `[Manager HR: {Nama}]` jika pemohon adalah Manager).
      3. Jika permohonan ditolak pada tingkat Manager Departemen atau HR, kolom `notes` mencatat alasan penolakan dan alasan pengajuan awal.
  - **Controller & Logika Sinkronisasi (`AttendanceExcuseController.php`):**
    - Method `store`: Saat `type === 'late_permission'`, sistem langsung mencari atau membuat rekaman `Attendance` pada tanggal terkait dengan `clock_in = $formattedTime ?: '09:30:00'` dan `notes` keterangan pengajuan dispensasi.
    - Method `updateStatus`:
      - Pada persetujuan Tahap 1 oleh Manager Departemen: Mengupdate catatan log kehadiran dengan info persetujuan rekomendasi Manager Dept dan alasan pengajuan.
      - Pada persetujuan Tahap 2 oleh Manager HR / Direktur: Mengupdate record `attendances` menjadi `status = 'on_time'` dan menyematkan catatan dispensasi resmi lengkap beserta nama kedua approver dan alasan pengajuan.
  - **Penyempurnaan Tampilan Log Kehadiran (`attendance/index.blade.php`):**
    - Kolom Status menampilkan badge ganda yang rapi: `Tepat Waktu` + sub-badge berikon terverifikasi `Dispensasi` (amber/gold) jika kehadiran disetujui melalui jalur dispensasi, atau `Terlambat` + `Dispensasi Menunggu` jika permohonan masih dalam proses peninjauan.
    - Kolom Catatan (Notes) menampilkan chip alert berikon `verified` / `schedule` / `cancel` dengan styling warna adaptif (`amber`, `yellow`, atau `rose`) yang memperjelas alasan dispensasi dan siapa pihak yang menyetujui.
- **2026-10-30:** **Fitur Ekspor Task, Progres & KPI Departemen (Khusus Manager Departemen & Direktur):**
  - **Latar Belakang & Aturan Bisnis:**
    - Setiap **Manager Departemen (`isManager()`)** dapat mengunduh rekap tugas dan progres pada departemennya masing-masing, sementara **Direktur Utama (`isDirektur()`)** dapat mengunduh per departemen maupun seluruh divisi.
    - Karyawan biasa dibatasi secara ketat (`abort(403)`) guna menjaga privasi evaluasi kinerja dan pembagian tugas tim.
  - **Pilihan Periode Waktu:**
    - Opsi **1 Minggu Terakhir** (`period=1_week`): Meliputi 7 hari ke belakang.
    - Opsi **1 Bulan Terakhir** (`period=1_month`): Meliputi 30 hari ke belakang.
  - **Kandungan Informasi Laporan:**
    1. *Evaluasi KPI Departemen:* Indeks skor KPI divisi (0-100 pts), tingkat penyelesaian tugas (completion rate %), rata-rata progres tim %, dan ketepatan waktu penyelesaian (on-time delivery %).
    2. *Ringkasan Kinerja Per PIC:* Daftar anggota tim departemen beserta total tugas yang dibebankan, jumlah tugas selesai, tugas berjalan, rata-rata progres %, dan skor KPI individu.
    3. *Detail Rinci Tugas:* Nomor, judul tugas, deskripsi pekerjaan, skala/tipe (Daily Task / Tim Dept / Individu), PIC penanggung jawab & NIP, prioritas, tanggal mulai, tenggat waktu (due date), tanggal selesai, status, progres %, indikator ketepatan waktu, dan pembuat tugas.
  - **Format Ekspor:**
    - **Native Microsoft Excel (.xlsx):** Dibuat menggunakan `PhpSpreadsheet` dengan styling korporat eksekutif, kop surat resmi, banner KPI cards, zebra striping selang-seling, auto-fit lebar kolom, freeze panes, dan download biner bebas korupsi via temporary storage + `ob_end_clean()`.
    - **Cetak Laporan PDF:** View template [`reports/tasks_pdf.blade.php`](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/reports/tasks_pdf.blade.php) berstandar ISO A4 Portrait dengan kop resmi, badge kode dokumen otomatis, dan kolom tanda tangan Manager Departemen & Direktur Utama.
  - **Routes & Controller:**
    - Routes: `GET /tasks/export/excel` (`tasks.export.excel`) dan `GET /tasks/export/pdf` (`tasks.export.pdf`) di [routes/web.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/routes/web.php).
    - Method: `prepareTaskExportData()`, `exportExcel()`, & `exportPdf()` di [TaskController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/TaskController.php).
  - **Antarmuka Pengguna (UI/UX):**
    - Tombol bergradasi emerald `Tarik Data Task & KPI` pada header halaman Manajemen Tugas ([`tasks/index.blade.php`](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/tasks/index.blade.php)) dan Monitoring Direksi ([`tasks/director_index.blade.php`](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/tasks/director_index.blade.php)).
    - Modal interaktif (tanpa `backdrop-blur` per Rule 3) dengan kartu pilihan periode interaktif dan 2 tombol unduh instan.
- **2026-10-30:** **Fitur Wajib Keterangan Alasan Lost pada Pipeline Deals & Peluang Bisnis (BizDev):**
  - **Latar Belakang & Aturan Bisnis:**
    - Pada modul Bisnis Development (`departments/bizdev/index.blade.php`), setiap kali deal bisnis diubah tahapannya ke status **Lost (Gagal Closing)**, pengguna **wajib** menyertakan keterangan/alasan kenapa deal tersebut gagal.
    - Hal ini krusial untuk evaluasi performa penawaran, analisis penolakan klien, dan pelaporan strategis kepada jajaran Direksi.
  - **Skema Database:**
    - Migrasi: `database/migrations/2026_10_30_160500_add_lost_reason_to_lead_projects_table.php`.
    - Menambahkan kolom `lost_reason` (text, nullable) pada tabel `lead_projects`.
  - **Controller & Validasi (`BisnisDevelopmentController.php`):**
    - Method `updateStage`: Menambahkan validasi `'lost_reason' => 'required_if:stage,lost|nullable|string|max:1000'` dengan pesan error kustom dalam Bahasa Indonesia.
    - Saat stage menjadi `lost`, kolom `lost_reason` disimpan ke database. Jika di masa mendatang deal diaktifkan kembali ke tahapan lain (seperti prospect/proposal/negotiation), `lost_reason` dibersihkan (`null`).
    - Method `store`: Mendukung penyimpanan awal `lost_reason` jika status lost disertakan.
    - Method `index`: Menghitung metrik ringkasan `totalLost` (akumulasi rupiah lost deals) dan `lostDealsCount` (jumlah proyek lost).
  - **Penyempurnaan Antarmuka (UI/UX Blade):**
    - Modal interaktif konfirmasi `openLostModal` tanpa `backdrop-blur` (Rule 3 compliant) yang otomatis muncul saat pengguna memilih opsi `Lost` pada dropdown tahapan deal.
    - Modal merangkum nama klien, judul proyek, nilai deal, dan menyediakan textarea wajib dengan placeholder edukatif.
    - Tombol "Batal" otomatis mengembalikan nilai select dropdown ke tahapan semula tanpa submit.
    - Tombol khusus "Edit Alasan" pada kolom Update Stage untuk memperbarui catatan alasan tanpa harus mengubah tahapan bolak-balik.
    - Kolom Tahapan menampilkan badge `LOST` merah dan kotak keterangan alasan lost yang rapi (`bg-red-950/30 border border-red-900/40 text-red-300`).
    - Tab filter baru: Menambahkan tab `Lost Deals (X)` agar tim dapat meninjau seluruh riwayat proyek yang gagal beserta keterangannya.
    - Kartu metrik baru: Menampilkan ringkasan metrik 4 kolom mencakup *Deals Lost* berdampingan dengan *Active Pipeline*, *Won*, dan *Proyek Aktif*.
- **2026-10-30:** **Fitur Menarik & Ekspor Data Karyawan (Khusus Direktur Utama & Manager HR):**
  - **Latar Belakang & Aturan Bisnis:**
    - Memberikan kewenangan khusus kepada **Direktur Utama (`isDirektur()`)** dan **Manager HR (`isHR() && isManager()`)** untuk menarik/mengunduh data master seluruh karyawan perusahaan, baik dalam format spreadsheet Microsoft Excel / CSV maupun cetak Laporan Dokumen PDF.
    - Staf karyawan biasa maupun Manager dari departemen non-HR dibatasi secara ketat (`abort(403)`) guna menjaga kerahasiaan data profil, kontak pribadi, dan informasi kepegawaian.
  - **Routes & Controller:**
    - Route: `GET /employees/export/excel` (`employees.export.excel`), `GET /employees/export/csv` (`employees.export.csv`), & `GET /employees/export/pdf` (`employees.export.pdf`) pada [web.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/routes/web.php).
    - Method Controller: `exportExcel(Request $request)` & `exportPdf(Request $request)` di [EmployeeController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/EmployeeController.php).
    - Otorisasi: Method `canExportEmployees()` di [User.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Models/User.php).
  - **Fitur Ekspor Native Microsoft Excel (.xlsx):**
    - Menggunakan library `phpoffice/phpspreadsheet` untuk menghasilkan file spreadsheet native OpenXML (`.xlsx`) berstandar eksekutif perusahaan.
    - Mengatasi masalah file CSV yang berantakan (semua teks masuk ke Kolom A, karakter UTF-8 BOM `Ôªø` terkorupsi, atau nomor telepon terpotong angka nolnya).
    - **Solusi Pencegahan Korupsi File XLSX:** File disimpan ke temporary storage (`tempnam(sys_get_temp_dir(), 'hris_emp_') . '.xlsx'`) dengan pembersihan output buffer (`while (ob_get_level()) { ob_end_clean(); }`) sebelum dikirimkan via `response()->download()->deleteFileAfterSend(true)`. Hal ini mencegah interferensi chunking/buffer pada stream non-seekable ZipStream yang sebelumnya menyebabkan dialog error Microsoft Excel *"cannot open the file ... format or extension is not valid"*.
    - Dilengkapi kop resmi PT GYOKAI GLOBAL INDONESIA, banner judul, strip parameter metadata filter, header tabel navy (`#1E293B`) dengan teks putih tebal, zebra striping selang-seling (`#FFFFFF` & `#F8FAFC`), border tipis rapi, freeze panes baris header (`A9`), auto-fit lebar kolom otomatis (A s/d O), nomor telepon berformat string murni agar angka `08..` tidak terpotong, badge warna status aktif/non-aktif, serta baris TOTAL formula `=SUM()` di bagian bawah.
  - **Fitur Cetak & PDF Laporan Direktori Karyawan:**
    - View template: [reports/employees_pdf.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/reports/employees_pdf.blade.php).
    - Dilengkapi kop resmi PT GYOKAI GLOBAL INDONESIA / LPK GYOKAI INDONESIA, kode dokumen otomatis `RPT-EMP-YYYYMMDD`, tanggal cetak, identitas pencetak, parameter filter aktif, kartu ringkasan metrik (Total Karyawan, Aktif, Tetap, Kontrak), tabel komprehensif, serta kolom tanda tangan Manager HR dan Direktur Utama.
  - **Antarmuka Pengguna (UI/UX Modal):**
    - Tombol khusus bergradasi emerald `Tarik Data Karyawan` pada header halaman Direktori Karyawan (`employees/index.blade.php`).
    - Modal interaktif tanpa `backdrop-blur` (bebas lag GPU) dengan filter divisi, status, ikatan kerja, dan kata kunci pencarian, serta 2 tombol pilihan unduhan instan (Excel/CSV dan Laporan PDF).
- **2026-10-30:** **Implementasi Alur Berjenjang Persetujuan Izin & Cuti Karyawan (Two-Tier Approval Workflow):**
  - **Latar Belakang & Aturan Bisnis:**
    - Setiap karyawan dari seluruh departemen non-HR (Finance, GA, Bisnis Development, Resepsionis, Trainer) yang mengajukan izin apapun (Cuti Tahunan, Izin Khusus, Sakit, Melahirkan, maupun Dispensasi Presensi seperti Izin Terlambat, Dinas Luar, dan Tidak Absen Pulang) **wajib disetujui terlebih dahulu oleh Manager Departemen terkait**.
    - Setelah Manager Departemen memberikan persetujuan, permohonan **secara otomatis diteruskan ke Manager HR** untuk peninjauan dan persetujuan final.
    - Pengecualian otomatis: Jika pemohon adalah karyawan/staf dari departemen HR sendiri, atau jika pemohon berkedudukan sebagai Manager Departemen, permohonan langsung diarahkan ke antrean persetujuan Manager HR / Direktur Utama tanpa melalui tahap Manager Departemen.
  - **Perubahan Skema Database:**
    - Migrasi: `database/migrations/2026_10_30_124416_add_two_tier_approval_to_leave_and_excuses_tables.php`.
    - Kolom `status` pada tabel `leave_requests` dan `attendance_excuses` diubah dari enum kaku menjadi `VARCHAR(30) DEFAULT 'pending'` guna mendukung state baru:
      1. `pending_manager`: Menunggu peninjauan dan persetujuan Manager Departemen pemohon.
      2. `pending_hr`: Telah disetujui Manager Departemen, menunggu persetujuan final Manager HR / Direktur.
      3. `approved`: Telah disetujui sepenuhnya oleh Manager HR / Direktur (kuota cuti berkurang atau catatan presensi disinkronkan).
      4. `rejected`: Ditolak (baik pada tingkat Manager Departemen maupun Manager HR, dengan catatan alasan penolakan wajib).
    - Menambahkan kolom `manager_approved_by` (foreignId to `employees.id`, nullOnDelete) untuk mencatat identitas Manager Departemen yang menyetujui.
    - Menambahkan kolom `manager_approved_at` (timestamp, nullable) untuk mencatat waktu persetujuan tahap 1.
    - Menambahkan kolom `manager_note` (text, nullable).
  - **Perubahan Model:**
    - [LeaveRequest.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Models/LeaveRequest.php): Menambahkan relasi `managerApprover()`, helper `isPendingManager()`, `isPendingHR()`, `isApproved()`, `isRejected()`.
    - [AttendanceExcuse.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Models/AttendanceExcuse.php): Menambahkan relasi `managerApprover()`, casting `manager_approved_at`, dan status helpers.
    - [User.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Models/User.php): Menambahkan method otorisasi `canApproveDepartmentLeave($model)` dengan validasi departemen dan aturan ketat **Anti Self-Approval**.
  - **Perubahan Controller & Logika Otorisasi:**
    - [LeaveRequestController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/LeaveRequestController.php) & [AttendanceExcuseController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/AttendanceExcuseController.php):
      - Method `store`: Mendeteksi pemohon non-HR dan menetapkan initial status `pending_manager`, mengirim notifikasi in-app real-time ke Manager Departemen pemohon.
      - Method `updateStatus`: Menangani alur persetujuan Tahap 1 (`pending_manager` &rarr; `pending_hr` dengan mencatat `manager_approved_by` dan mengirim notifikasi broadcast ke Tim HR & pemohon) serta Tahap 2 (`pending_hr` &rarr; `approved`/`rejected` dengan pemotongan kuota cuti tahunan dan sinkronisasi presensi).
      - Method `index`: Memperluas cakupan visibilitas agar Manager Departemen dapat memantau permohonan staf departemennya serta pengajuan pribadi mereka, sementara Manager HR dan Direktur tetap memiliki visibilitas global.
    - [DashboardController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/DashboardController.php): Memperbarui antrean widget `Persetujuan Menunggu` agar Manager Departemen dapat langsung meninjau permohonan departemennya dari Dashboard.
  - **Penyempurnaan Blade Views:**
    - [leave/index.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/leave/index.blade.php), [attendance/excuses.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/attendance/excuses.blade.php), dan [dashboard/index.blade.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/resources/views/dashboard/index.blade.php):
      - Badge status berjenjang informatif: `Menunggu Review Mgr Dept` (amber) & `Menunggu Review Mgr HR` (indigo dengan nama Manager Departemen yang merekomendasikan).
      - Tombol aksi kontekstual: Manager Departemen melihat tombol `Setujui & Ke HR` (forward ke HR) dan `Tolak` (wajib alasan), sementara Manager HR melihat tombol `Setujui Final HR`.
      - Menerapkan perlindungan anti self-approval dengan menampilkan badge terkunci `Pengajuan Sendiri` bagi pemohon.
- **2026-10-30:** **Penyempurnaan Tampilan & Efisiensi Kontrol Sisa Cuti Tahunan (`leave/index.blade.php`):**
  - Mengubah tampilan kontrol kuota cuti tahunan menjadi desain card-based ringkas dan estetis (Dark Neomorphic Dashboard).
  - Menyediakan filter interaktif per departemen, filter status sisa cuti, dan pencarian instan nama karyawan tanpa reload halaman.
- **2026-10-29:** **Fitur Perizinan Presensi & Dispensasi Kehadiran (Izin Terlambat, Dinas Luar, Tidak Absen Pulang):**
  - **Tabel & Model:** `attendance_excuses` & `App\Models\AttendanceExcuse.php`.
  - **Controller & Routes:** `App\Http\Controllers\AttendanceExcuseController.php` (`attendance/excuses/*`).
  - **Cakupan Pengajuan:** Seluruh karyawan dan manager dari semua departemen dapat mengajukan permohonan ke Tim HR:
    1. *Izin Terlambat (`late_permission`):* Permohonan dispensasi keterlambatan masuk kantor (karena kendala perjalanan, dsb). Jika disetujui HR, status presensi late otomatis diubah menjadi `on_time` dengan catatan dispensasi resmi.
    2. *Izin Dinas Luar (`duty_trip`):* Permohonan penugasan operasional lapangan / dinas luar kantor. Jika disetujui HR, otomatis dibuatkan atau diperbarui catatan presensi dengan `work_mode = 'Dinas Luar'` dan status `on_time`.
    3. *Keterangan Tidak Absen Pulang (`missed_clock_out`):* Permohonan klarifikasi kendala lupa scan pulang atau mesin error sore hari. Jika disetujui HR, kolom `clock_out` presensi karyawan otomatis diisi sesuai jam pulang yang dilaporkan (atau jam cut-off 18:00 WIB).
  - **Hak Akses & Alur Approval:**
    - Hanya Tim HR (`canApproveLeaves()`) dan Direktur Utama yang memiliki wewenang untuk menyetujui (*Approve*) atau menolak (*Reject* dengan wajib menyertakan alasan penolakan).
    - Menerapkan prinsip **Anti Self-Approval** ketat: staf HR/Manager HR tidak dapat menyetujui pengajuan izin presensi milik dirinya sendiri.
    - Notifikasi in-app real-time otomatis terkirim ke Tim HR saat ada pengajuan baru, dan notifikasi konfirmasi otomatis terkirim ke pemohon saat disetujui/ditolak.
- **2026-10-29:** **Perbaikan Logika Kalkulasi Keterlambatan Presensi & Sinkronisasi Timezone:**
  - Mengatasi bug status "Terlambat" pada presensi yang datang di bawah jam 09:00 WIB (seperti Mia 07:57:06 WIB, Yuni 07:56:25 WIB, dan Risma 07:46:28 WIB).
  - **Penyebab Utama:**
    1. Pengaturan `config/app.php` sebelumnya hardcoded `'timezone' => 'UTC'` (tertinggal 7 jam dari WIB).
    2. Pembuatan batas waktu menggunakan `Carbon::createFromTimeString('09:00:00')` mengadopsi tanggal hari ini berdasarkan jam server. Saat scan datang pagi hari WIB (antara pukul 07:00–08:59 WIB), waktu server UTC masih berada di tanggal kemarin malam (pukul 00:00–01:59 UTC). Akibatnya objek threshold terbentuk dengan tanggal kemarin, sehingga perbandingan `$dateTime->greaterThan($threshold)` menghasilkan nilai `TRUE` (dianggap terlambat karena tanggal scan lebih baru dari tanggal threshold).
  - **Solusi Komprehensif:**
    1. Menghubungkan `config/app.php` timezone ke `env('APP_TIMEZONE', 'Asia/Jakarta')` (WIB).
    2. Mengubah penentuan status keterlambatan di [AdmsAttendanceController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/Api/AdmsAttendanceController.php) dan [AttendanceController.php](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/app/Http/Controllers/AttendanceController.php) menjadi perbandingan waktu murni: `($time > '09:00:00') ? 'late' : 'on_time'`. Jam scan &le; 09:00:00 WIB dijamin 100% selalu berstatus **Tepat Waktu** (*on_time*), dan > 09:00:00 WIB berstatus **Terlambat** (*late*).
    3. Menyediakan artisan command `php artisan attendance:recalculate-status` untuk menghitung dan memperbarui status pada data log historis.
- **2026-10-29:** **Perbaikan Ketat (Strict Matching) PIN Mesin Finger ke NIP Karyawan:**
  - Mengatasi kendala salah identifikasi pada `AdmsAttendanceController@receiveLog` (kasus Risma NIP 51 terbaca PIN 41 dan Rahmat NIP 44444 terbaca PIN 40).
  - **Penyebab:** Sebelumnya terdapat query fallback berbahaya `orWhere('id', $pin)` dan `orWhere('nip', 'like', "%{$pin}")` yang menyebabkan sistem mencocokkan nomor PIN mesin finger dengan `id` primary key record karyawan lain di tabel database.
  - **Solusi:** Menghapus pencarian berbasis `id` dan `like` parsial. Logika pencocokan kini **murni 100% strictly NIP** (hanya memeriksa `$employee->nip == $pin` atau format padding standar `NEX-000XX`/`NEX-00XX`).
  - **Validasi Integritas:** Apabila PIN dari mesin finger tidak cocok dengan NIP karyawan manapun di sistem, data scan tersebut **otomatis diabaikan dan tidak dimunculkan di log presensi**, sehingga data kehadiran terjamin valid dan akurat.
- **2026-10-28:** **HRIS Audit Report Remediation (P0, P1, P2):**
  1. *Rate Limiting Login:* Menambahkan middleware `throttle:5,1` pada route `POST /login` guna mencegah brute-force login attack.
  2. *Eliminasi Fallback Berbahaya:* Menghapus 10 lokasi insecure fallback `Employee::first()` di berbagai controller (Dashboard, Attendance, Leave, Calendar, Employee, Report, GA, BizDev, Finance) untuk mencegah akses data lintas pengguna.
  3. *Validasi Kuota & Overlap Cuti:* Menambahkan validasi sisa kuota cuti tahunan dan deteksi permohonan tanggal cuti yang saling bertabrakan (overlap) pada `LeaveRequestController@store`.
  4. *Atomic Transaction & ADMS Security:* Membungkus penerbitan slip gaji massal dalam `DB::transaction()` pada `FinanceController` dan menambahkan validasi whitelist Serial Number (SN) mesin finger pada `AdmsAttendanceController@receiveLog`.
  5. *Soft Deletes & Indexing Migration:* Menyiapkan skema migrasi soft deletes & composite indexes pada tabel kritis (`employees`, `leave_requests`, `reimbursements`, `salary_slips`, `attendances`, `tasks`) serta menyertakan trait `SoftDeletes` dan relasi lengkap pada model `Employee`.
  6. *Halaman Error Kustom Branded:* Membuat view error elegan bertema dark GYOKAI HR untuk `403.blade.php`, `404.blade.php`, dan `500.blade.php`.
  7. *Logging Notifikasi Terbuka:* Mengganti komentar silent fail pada blok pengiriman notifikasi di `LeaveRequestController`, `FinanceController`, dan `TaskController` dengan `Log::warning`.
  8. *Slip Gaji Print PDF & Panduan Reset Password:* Menyempurnakan CSS cetak bersih (clean printable media) pada slip gaji detail dan menambahkan petunjuk reset kata sandi modal informatif pada form login.

- **2026-11-02:** Implementasi Fitur Presensi Mandiri Biometrik Wajah (Face ID) & Geolocation GPS:
  1. *Migrasi Database:* Membuat migrasi `2026_11_02_210000_add_face_id_and_location_to_attendances_table.php` yang menambahkan kolom `latitude`, `longitude`, `photo_in`, `photo_out`, `distance_meters`, `verification_method`, `device_info` pada tabel `attendances`.
  2. *Controller & Logika Bisnis:* Penambahan method `AttendanceController@faceRecord` yang memproses selfie base64 terenkode, menyimpan file fisik foto ke `storage/app/public/attendance_photos/{Y-m}/`, menghitung jarak geofencing GPS ke kantor pusat dengan formula Haversine, mencatat status kedisiplinan, serta mengembalikan JSON response terstruktur.
  3. *Model Eloquent:* Penambahan accessor `photo_in_url`, `photo_out_url`, `google_maps_url` pada `Attendance.php` serta helper `canFaceClock()` pada `User.php`.
  4. *Komponen Antarmuka Web:* Pembuatan komponen Blade modern `<x-face-attendance-modal />` (`resources/views/components/face-attendance-modal.blade.php`) dengan viewfinder pemindaian biometrik beranimasi radar, watermark otomatis, audio feedback Web Audio API, serta integrasi tombol presensi di Topbar, Dashboard, dan Halaman Presensi.
  5. *Audit Trail & Inspeksi HR:* Menambahkan kolom *Verifikasi & Foto* dan *Lokasi & GPS* pada tabel presensi dengan popup modal preview foto resolusi tinggi dan tautan Google Maps langsung.
  6. *Automated Testing:* Pembuatan unit & feature test `tests/Feature/FaceAttendanceTest.php` yang memvalidasi keberhasilan presensi biometrik (lulus 100%).
  7. *Optimasi Lensa Kamera & Viewfinder Bersih (Zero Overlay Obstruction):* Mengeliminasi seluruh teks overlay ('POSISIKAN WAJAH ANDA' / 'WAJAH TERDETEKSI') dan animasi garis radar yang melintang di tengah kamera agar tampilan viewfinder bersih total tanpa halangan visual. Mengganti `<template x-if>` dengan `<div x-show>` untuk menjaga eksistensi elemen `<video>` di DOM secara stabil, mengintegrasikan trigger `video.play()` otomatis, tombol flip kamera depan/belakang, serta frame oval tipis rounded emerald yang elegan.
  8. *Direct Photo Stream & cPanel Hosting Resilience:* Menambahkan endpoint controller `AttendanceController@showPhoto` (`GET /attendance/photo/{attendance}/{type}`) yang mengalirkan file fisik snapshot wajah langsung melalui PHP response stream. Hal ini menyelesaikan masalah gambar rusak (*broken image*) pada cPanel hosting yang terjadi akibat ketiadaan symbolic link `public/storage` atau restriksi symlink Apache/LiteSpeed. Menambahkan tombol 'Resolusi Penuh' dan graceful fallback icon broken-image pada modal preview.
  9. *Resolusi Konflik Event Alpine vs Blade Directive:* Mengganti atribut Alpine `@error` dan `@load` pada tag `<img />` modal preview menjadi `x-on:error` dan `x-on:load` untuk mencegah compiler Blade salah menafsirkan `@error` sebagai Blade validation directive `@error ... @enderror` yang memicu parse error `unexpected end of file`.

- **2026-11-02:** Implementasi Akun & Peran Sekretaris Direktur, Gatekeeping Jadwal Direksi & Filter Chat Manager:
  1. *Pembuatan Akun & Model Role:* Membuat migrasi `2026_11_02_200000_create_sekretaris_direktur_account.php` dan data akun `Sekretaris Direktur` (`sekretaris@gyokai.com`, role: `sekretaris`, department: `Executive`, sub_department: `Sekretariat`, NIP: `NEX-00021`). Menambahkan helper otorisasi `isSekretaris()` di `User.php`.
  2. *Hak Akses Read-Only KPI & Kinerja (Tanpa Pengambil Keputusan):* Sekretaris Direktur memiliki akses penuh melihat grafik indeks KPI harian/mingguan/bulanan dan metrik analitis performa seluruh 4 departemen perusahaan (HR, Finance, Bisnis Development, GA) di Dashboard dan Laporan PDF (`DashboardController` & `ReportExportController`), namun **sama sekali tidak memiliki wewenang pengambilan keputusan operasional** (`canApproveLeaves`, `canApproveReimbursements`, `canApproveInventoryRequests`, `canManageEmployees` bernilai `false`, antrean pending approval cuti/izin dinonaktifkan).
  3. *Protokol Gatekeeping Jadwal Direktur:* Karyawan maupun Manager dilarang keras langsung menjadwalkan agenda ke Direktur Utama. Seluruh permohonan jadwal ke Direksi (`target_departments = ['Executive']`) berstatus `pending` dan wajib diverifikasi serta disetujui terlebih dahulu oleh **Sekretaris Direktur** sebelum resmi masuk dan tampil di kalender Direktur (`CalendarController`).
  4. *Restriksi Fitur Chat Manager:* Saluran chat komunikasi eksekutif pada akun Manager dialihkan secara ketat **HANYA ke Sekretaris Direktur** (`tab=direksi`), memblokir pesan langsung ke Direktur. Sekretaris Direktur bertindak sebagai jembatan eksekutif (*Executive Communication Hub* - `chat.sekretaris`) yang menghubungkan para Manager dan Direktur Utama secara privat dan terstruktur (`DepartmentChatController`).
- **2026-11-05:** **Dinamisasi Identitas Direktur di Seluruh Template Surat, Sertifikat & Laporan PDF:**
  1. *Helper Sentralisasi Model User (`User.php`):* Menambahkan static helper method `User::getDirector(): ?User` dan `User::getDirectorName(string $fallback = 'Direktur Utama'): string` untuk mendeteksi instance akun Direktur aktif (`role = direktur`) secara real-time dari database, menghilangkan seluruh ketergantungan nama hardcoded.
  2. *Sertifikat Paklaring Kerja (`PaklaringController.php` & `paklaring/index.blade.php`):* Nama dan jabatan penandatangan sertifikat paklaring kini otomatis mengambil nama Direktur aktual dari database.
  3. *Manajemen Surat Keluar & Lembar Ekspedisi (`OutgoingLetterController.php` & `secretary/letters/index.blade.php`):* Nama penandatangan surat keluar resmi dan penerima arsip ekspedisi otomatis diselaraskan dengan nama Direktur aktual.
  4. *Laporan PDF Eksekutif KPI & Presensi (`ReportExportController.php`, `reports/kpi_pdf.blade.php`, `reports/attendance_pdf.blade.php`):* Blok tanda tangan "Mengetahui & Menyetujui" pada footer laporan KPI dan laporan absensi kini membaca langsung nama Direktur dan Head of HR aktual dari database.
  5. *Laporan PDF Master Data Karyawan (`EmployeeController.php` & `reports/employees_pdf.blade.php`):* Mengganti nama hardcoded tanda tangan direktur dengan `$director?->name ?? 'Direktur Utama'`.
  6. *Laporan PDF Tugas & KPI Departemen (`TaskController.php` & `reports/tasks_pdf.blade.php`):* Mengganti nama hardcoded tanda tangan persetujuan dengan `$directorName`.
  7. *Executive Private Chat & Master Layout (`chat/manager_direksi.blade.php` & `layouts/app.blade.php`):* Menghilangkan seluruh fallback nama statis agar selalu merefleksikan nama Direktur dan nama user login secara dinamis.

- **2026-11-05:** **Penyelarasan Role & Departemen Sekretaris Direktur di Seluruh Formulir Manajemen Karyawan:**
  1. *Opsi Tingkat Hak Akses (Level Akun):* Menambahkan pilihan role `sekretaris` ("📋 Sekretaris Direktur (Sekretariat Eksekutif)") pada modal Kelola Akun Login & Tingkat Akses (`openManageAccountModal`), modal Tambah Pegawai Baru (`openAddEmployeeModal`), dan modal Pendaftaran Akun Massal (`openBatchRegisterModal`).
  2. *Opsi Departemen / Posisi Akun:* Menambahkan pilihan departemen `Executive` ("Executive (Sekretariat Direksi)") pada seluruh form departemen karyawan (modal Akun, modal Tambah Pegawai, modal Edit Data Pokok, dan Onboarding Rekrutmen HR).
  3. *Controller & Validasi Backend (`EmployeeController.php`):*
     - Memperbarui validasi `updateAccount`, `store`, dan `batchStore` agar menerima `role: in:karyawan,asisten_manager,sekretaris,manager,direktur`.
     - Otomatisasi penetapan `sub_department = 'Sekretariat'` saat memilih departemen `Executive` dengan role `sekretaris`.
     - Menyelaraskan teks notifikasi in-app untuk promosi/penugasan role Sekretaris Direktur.
  4. *Tampilan Visual & Badge Tabel:* Menambahkan styling badge amber elegan (`bg-amber-950/60 text-amber-400 border border-amber-500/20`) berlabel `SEKRETARIS` pada kartu direktori pegawai dan header topbar layout master.
  5. *Automated Testing:* Membuat test suite `tests/Feature/EmployeeRoleSecretaryTest.php` untuk memvalidasi ketersediaan opsi formulir dan pembaruan akun ke Sekretaris Direktur.

- **2026-11-04:** Penyelarasan REST API XAMPP & Redesain UI Mobile App (Tema Merah & Silver):
  1. *Sinkronisasi Apache XAMPP:* Memperbaiki perizinan write permission folder `storage/logs/laravel.log` dan `storage/framework/cache/` untuk user `daemon` Apache XAMPP (`chmod -R 777`), serta menyesuaikan URL default mobile app ke endpoint Apache XAMPP (`/hris_app/public/api`) pada port 80.
  2. *Redesain Visual Mobile App (Red & Silver Executive):* Mengubah seluruh sistem warna aplikasi Android dari hijau-putih menjadi palet premium **Crimson/Ruby Red (`#C81E1E`, `#881337`, `#EF4444`) dan Metallic Brushed Silver (`#CBD5E1`, `#E2E8F0`, `#F8FAFC`, `#64748B`)**. Pembaruan mencakup Splash Screen, Login Screen, Hero Card Presensi Dashboard, Tab Navigation, Riwayat, Form Cuti, dan Notifikasi.

- **2026-11-03:** Pengembangan Fondasi Backend REST API (Laravel Sanctum) & Arsitektur Mobile App Android (Flutter):
  1. *Arsitektur Backend REST API:* Membuat 4 API Controller mandiri di `app/Http/Controllers/Api/` terproteksi middleware `auth:sanctum`:
     - `AuthApiController.php`: Login via Bearer Token, verifikasi profil pengguna (`GET /api/auth/me`), pembaruan telepon/alamat (`POST /api/auth/update-profile`), dan pencabutan sesi token (`POST /api/auth/logout`).
     - `AttendanceApiController.php`: Pengecekan status kehadiran hari ini (`GET /api/attendance/today`), presensi biometrik Face ID kamera depan & GPS (`POST /api/attendance/face-record`), riwayat kehadiran bulanan (`GET /api/attendance/history`), dan konfigurasi geofencing kantor (`GET /api/attendance/config`).
     - `LeaveApiController.php`: Manajemen pengajuan cuti/izin/sakit, validasi kuota sisa efektif, pencegahan tanggal overlap, dan persetujuan bertingkat (*Two-Tier Approval*) dengan prinsip ketat *Anti Self-Approval*.
     - `DashboardApiController.php`: Ringkasan metrik kehadiran harian, statistik bulanan, sisa cuti, badge antrean persetujuan, dan notifikasi sistem (`GET /api/dashboard/summary` & `GET /api/dashboard/notifications`).
  2. *Fitur Keamanan Khusus Mobile (Anti Fake GPS):* Menerapkan validasi keamanan mobile `is_mocked`. Jika karyawan menggunakan aplikasi pemalsu koordinat (Fake GPS), request presensi langsung ditolak dengan kode `422 Unprocessable Entity`.
  3. *Inisialisasi Project Mobile App:* Membangun arsitektur lengkap aplikasi Flutter Android di direktori `/Applications/XAMPP/xamppfiles/htdocs/mobile app hris/` lengkap dengan konfigurasi `AndroidManifest.xml` (izin Kamera & GPS), integrasi `flutter_secure_storage` (Android Keystore), kompresi gambar lokal (Edge Image Compression < 100 KB), state management `Provider`, serta panduan kompilasi APK mandiri (`GUIDE.md` & `README.md`).
  4. *Automated Feature Tests:* Menambahkan test suite `tests/Feature/MobileApiTest.php` yang menguji 5 skenario inti (Login/Logout, Today/Config, Anti-Mock GPS, Leave & Anti Self-Approval, Dashboard). Seluruh 19 test suite Laravel lolos 100% (`19 passed, 122 assertions`).
  5. *Sinkronisasi Git:* Seluruh kode backend REST API, controller, route, dan automated feature test mobile telah di-push ke GitHub repository `main`.

- **2026-11-02:** QA & Code Health Audit:
  1. *Perbaikan Sintaks Controller:* Memperbaiki error sintaks namespace declaration pada `AttendanceExcuseController.php` akibat leading whitespace.
  2. *Audit Suite Test Laravel:* Memperbarui pengujian `tests/Feature/ExampleTest.php` untuk memvalidasi unauthenticated redirect (`302` ke `/login`). Semua unit & feature test kini lolos (`2 passed`).
  3. *Verifikasi Build & View Cache:* Verifikasi kelancaran kompilasi Vite (`npm run build`) dan pembersihan view cache (`php artisan view:clear`).
  4. *Audit Aturan UI & Database:* Memastikan integritas migrasi database (35 batch migrasi telah dijalankan) dan kepatuhan prinsip modal tanpa `backdrop-blur` render lag.
- **2026-11-02:** Pembersihan Aset Krusial & Keamanan Publikasi GitHub:
  1. *Pembersihan Tanda Tangan:* Menghapus seluruh file dan goresan tanda tangan basah Direktur (`signature_2.png`, `stamp_signature_combined.png`) serta meregenerasi template paklaring `exact_base_template.png` dan Base64 tertanam di `PaklaringController.php` dengan stempel bulat resmi bersih tanpa tanda tangan (`stamp_official_gyokai.png`). Hal ini mencegah penyalahgunaan tanda tangan pejabat saat repository dijadikan open source / publik.
  2. *Sanitasi Password Default & Environment:* Mengganti hardcoded password akun pada `EmployeeController.php` dan `resources/views/employees/index.blade.php` dengan pembacaan dinamis `env('DEFAULT_USER_PASSWORD', 'password123')`.
  3. *Sanitasi `.env.example`:* Mengubah placeholder koneksi database sekunder (`DB_GYOKAI_DATABASE=database_fingerprint`) dan menyertakan default key `DEFAULT_USER_PASSWORD=password123`.
- **2026-11-02:** Pembenahan Layout Live Visual Preview dan Cetak Sertifikat Paklaring (`resources/views/paklaring/index.blade.php` & `print.blade.php`). Mengganti class utility dinamis Tailwind (misal: `top-[40.2%]`, `left-[5%]`) dengan **inline styles persis dan class CSS statis terdefinisi** (`preview-recipient-name`, `preview-body-program`, `preview-body-period`, `preview-body-company`, `preview-signature-header`) dengan ukuran font responsif `clamp(...)`. Hal ini mengeliminasi masalah teks menumpuk di pojok kiri atas akibat tidak adanya compile Tailwind JIT di server hosting cPanel.
- **2026-11-02:** Pembuatan modul Mandiri Sertifikat / Paklaring Kerja Resmi LPK Gyokai Indonesia (`PaklaringController`, `paklaring/index.blade.php`, `paklaring/print.blade.php`) dengan resolusi tinggi 2480x1754 px Base64 tertanam mandiri, layout A4 Landscape pixel-perfect, watermark stempel resmi Direktur, serta alur penyimpanan arsip dan cetak langsung.

- **2026-10-28:** Penyesuaian hak akses feed & metrik kartu "Persetujuan Menunggu" pada Dashboard (`DashboardController@index` & `dashboard/index.blade.php`). Kini hanya Manager HR, Karyawan HR, dan Direktur yang dapat melihat antrean permohonan secara global seluruh perusahaan. Sementara Manager maupun Karyawan dari departemen lain (Finance, Bisnis Development, GA, Resepsionis, Trainer) HANYA melihat pengajuan izin & cuti milik diri sendiri.
- **2026-10-28:** Penambahan fitur Penerbitan Slip Gaji Masal (Lebih dari 1 orang sekaligus) pada modul Finance (`FinanceController@storeSalarySlip` & `resources/views/departments/finance/index.blade.php`). Mendukung toggle mode Satuan vs Masal, fitur filter divisi, pencarian nama/NIP, checkbox multi-select dengan tombol *Pilih Semua*, dan pembuatan slip gaji otomatis per karyawan yang dipilih.
- **2026-10-28:** Penyelarasan pemetaan PIN mesin finger dengan NIP karyawan (`AdmsAttendanceController`) yang mendukung pencocokan multi-format (angka murni, prefix `NEX-000XX`, `NEX-00XX`), perbaikan deteksi otomatis Jam Pulang (Clock Out) untuk jam &ge; 18:00 WIB (maupun &ge; 15:00 WIB), serta penanganan scan perdana sore hari agar langsung dicatat sebagai Clock Out.
- **2026-10-27:** Konfigurasi batas jam kerja presensi kehadiran: Jam Masuk disetel menjadi **09:00 WIB** (clock in &le; 09:00 WIB otomatis *Tepat Waktu*, > 09:00 WIB otomatis *Terlambat*) dan Jam Pulang disetel menjadi **18:00 WIB** (berlaku untuk clock in web manual maupun auto-push mesin finger Solution X609 via ADMS protocol).
- **2026-10-26:** Implementasi optimasi pendaftaran akun massal (`EmployeeController@batchStore`) untuk mencegah timeout CPU Bcrypt pada shared hosting LiteSpeed (`set_time_limit(180)` & hashing password default 1 kali sebelum loop).
- **2026-10-26:** Implementasi fitur Pendaftaran Akun Massal (*Batch Registration*) pada menu Karyawan (`/employees`) khusus Manager HRD & Direktur yang mendukung copy-paste tabel langsung dari spreadsheet/Excel (NO ID Mesin, NAMA, DEPARTEMEN, JABATAN, IKATAN KERJA, STATUS, LOKASI, TELEPON) dengan otomatisasi pembuatan email `(nama)@gyokai.com` dan password default.
- **2026-10-26:** Pendaftaran dan pembuatan massal 19 akun pegawai baru (Fida, Prakoso, Agustiadi, Aan, Risma, Yuni, Asep, Harlan, Mia, Asim, Galang, Jaka, Budi, Danu, Viola, Disa, Novitasari, Wahyu, Rahmat) beserta profil Employee (NIP disesuaikan dengan ID mesin finger: `NEX-00002` s/d `NEX-00020`) dengan password terenkripsi.
- **2026-10-26:** Implementasi fitur Pengajuan & Persetujuan Reset Kehadiran Seluruh Karyawan & Manager (`attendance_reset_requests`) dengan alur bilateral approval (Manager HRD diapprove Direktur, atau Direktur diapprove Manager HRD), prinsip anti self-approval ketat, opsi cakupan reset (semua waktu atau rentang tanggal), serta broadcast notifikasi pengumuman resmi ke seluruh akun karyawan & manager setelah dieksekusi.
- **2026-10-23:** Implementasi sistem keamanan akun komprehensif: auto-logout saat browser/tab ditutup (`expire_on_close = true`), batas maksimal 3 perangkat login bersamaan (`EnsureDeviceSessionIsValid`), pencatatan log login & log aktivitas, serta UI monitoring perangkat aktif pada halaman Pengaturan Profil (`/profile`).
- **2026-10-23:** Implementasi modul Kirim Slip Gaji (Email Dispatch) pada akun Finance (`/finance/dispatch`) yang tersinkronisasi 100% dua arah real-time dengan modul `Gyokai02` (`/finance/payslips`) menggunakan multi-SMTP Gmail pool rotasi, live batch progress, parser teks PDF, dan pembatasan hak akses ketat Finance & Direktur.
- **2026-10-22:** Implementasi halaman Data Member khusus akun Resepsionis (`/receptionist/members`) yang terhubung langsung dan tersinkronisasi dua arah real-time dengan portal Company pada project `Gyokai02`.
- **2026-10-22:** Implementasi alur approval berjenjang perubahan status role akun (Karyawan &harr; Manager) oleh Manager HRD dengan persetujuan Direktur Utama, serta kemampuan Direktur mengubah status secara langsung dengan notifikasi otomatis ke Manager HRD.
- **2026-10-22:** Penambahan pilihan departemen `Resepsionis` dan `Trainer` pada pilihan Departemen Tujuan jadwal cakupan Antar-Departemen di modul Kalender & Jadwal Kerja.
- **2026-10-22:** Implementasi sistem pengingat otomatis (Reminder Jadwal H-1, Reminder Task berkala tiap 7 hari jika durasi > 7 hari, serta H-3 dan H-1 jika mendekati deadline).



