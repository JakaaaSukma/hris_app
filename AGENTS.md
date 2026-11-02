# Project Rules & Agent Guidelines

## 1. Primary Knowledge Source
- **Selalu baca dan jadikan file [PROJECT_ANALYSIS_GUIDE.md](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/PROJECT_ANALYSIS_GUIDE.md) sebagai acuan analisa utama** setiap kali akan membuat fitur baru, memodifikasi kode, memperbaiki bug, atau melakukan investigasi dalam project HRIS ini.
- Jangan melakukan analisis redundan atau pencarian file secara membabi-buta jika informasi arsitektur, role, hak akses, model, atau alur bisnis sudah tercatat di [PROJECT_ANALYSIS_GUIDE.md](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/PROJECT_ANALYSIS_GUIDE.md).

## 2. Mandatory Post-Task Update Rule
- **Setiap kali selesai mengerjakan atau memodifikasi sesuatu** (fitur baru, perubahan route, penambahan model/tabel, perubahan alur approval, optimasi UI/styling, dll.), **agent WAJIB memperbarui file [PROJECT_ANALYSIS_GUIDE.md](file:///Applications/XAMPP/xamppfiles/htdocs/hris_app/PROJECT_ANALYSIS_GUIDE.md)**.
- Hal yang wajib di-update mencakup:
  1. Penambahan/perubahan route & controller pada daftar sitemap file kunci.
  2. Perubahan pada skema model atau migrasi database.
  3. Perubahan aturan bisnis atau alur approval.
  4. Perubahan komponen desain atau optimasi performa.

## 3. Strict Development Principles
- **Anti Self-Approval:** Akun pembuat pengajuan dilarang keras dapat menyetujui pengajuannya sendiri. Setiap approval harus mencatat identitas penyetuju (`approved_by`) dan waktu (`approved_at`).
- **Role Verification:** Gunakan helper method `auth()->user()->isDirektur()`, `isManager()`, atau `isKaryawan()`.
- **Performa Modal / Pop-up:** Dilarang menggunakan CSS `backdrop-blur` pada fixed modal pop-up agar tidak menimbulkan lag rendering GPU.
- **Cache Clearing:** Selalu bersihkan view (`php artisan view:clear`) dan compile asset jika memodifikasi file CSS/JS (`npm run build`).
