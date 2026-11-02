<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Reimbursement;
use App\Models\LeadProject;
use App\Models\InventoryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ==========================================
        // 1. LEVEL DIREKTUR (Akses Seluruh Departemen)
        // ==========================================
        $direktur = Employee::create([
            'nip' => 'NEX-00001',
            'name' => 'Hendra Wijaya',
            'email' => 'hendra.wijaya@gyokai.com',
            'phone' => '+62 811-9988-7711',
            'role_title' => 'Chief Executive Officer (Direktur Utama)',
            'department' => 'Executive Board',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2019-01-01',
            'avatar_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
            'location' => 'Executive Office (Jakarta)',
            'annual_leave_quota' => 20,
            'annual_leave_used' => 1,
        ]);

        User::create([
            'name' => $direktur->name,
            'email' => $direktur->email,
            'password' => Hash::make('password123'),
            'role' => 'direktur',
            'department' => 'Executive',
            'employee_id' => $direktur->id,
        ]);

        // ==========================================
        // 2. DEPARTEMEN HR (Human Resources)
        // ==========================================
        $mgrHR = Employee::create([
            'nip' => 'NEX-10101',
            'name' => 'Siti Rahmawati',
            'email' => 'siti.hr@gyokai.com',
            'phone' => '+62 812-3456-7890',
            'role_title' => 'HR Manager',
            'department' => 'HR',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2021-03-15',
            'avatar_url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDxTYxNLs6MiFmAPoiZdKWkCjHbLbRKsutMQIHTkTHY0-D1k0fOb3E6zOXLQW4nPOKyiJ3ZOw0CUc3Q2fUNE3fkvWt_s_h0ilhal8GQyI2NbPRhGIeDTlr-ZYda51rEQ9XcDrJPIoz1kGqHrQSLQJln81v-b1qwxA-lX5-YOnrUWmyvs3_i6ghUatQPEUVtp7RDvgqkyFSczK2oEIgDaNnrTwlBZ4eKvGT0QhRV2ctD7i-A-URN2IPL1g',
            'location' => 'Headquarters (Jakarta)',
            'annual_leave_quota' => 14,
            'annual_leave_used' => 2,
        ]);

        User::create([
            'name' => $mgrHR->name,
            'email' => $mgrHR->email,
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'department' => 'HR',
            'employee_id' => $mgrHR->id,
        ]);

        $stfHR = Employee::create([
            'nip' => 'NEX-10102',
            'name' => 'Clarissa Putri',
            'email' => 'clarissa.hr@gyokai.com',
            'phone' => '+62 878-9988-7766',
            'role_title' => 'Talent Acquisition Specialist',
            'department' => 'HR',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2023-08-01',
            'avatar_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150',
            'location' => 'Headquarters (Jakarta)',
            'annual_leave_quota' => 12,
            'annual_leave_used' => 1,
        ]);

        User::create([
            'name' => $stfHR->name,
            'email' => $stfHR->email,
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'department' => 'HR',
            'employee_id' => $stfHR->id,
        ]);

        // ==========================================
        // 3. DEPARTEMEN FINANCE
        // ==========================================
        $mgrFinance = Employee::create([
            'nip' => 'NEX-20101',
            'name' => 'Bambang Soediro',
            'email' => 'bambang.finance@gyokai.com',
            'phone' => '+62 813-1122-3344',
            'role_title' => 'Finance & Accounting Manager',
            'department' => 'Finance',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2020-05-10',
            'avatar_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150',
            'location' => 'Headquarters (Jakarta)',
            'annual_leave_quota' => 14,
            'annual_leave_used' => 0,
        ]);

        User::create([
            'name' => $mgrFinance->name,
            'email' => $mgrFinance->email,
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'department' => 'Finance',
            'employee_id' => $mgrFinance->id,
        ]);

        $stfFinance = Employee::create([
            'nip' => 'NEX-20102',
            'name' => 'Mega Lestari',
            'email' => 'mega.finance@gyokai.com',
            'phone' => '+62 815-5566-7788',
            'role_title' => 'Finance & Treasury Staff',
            'department' => 'Finance',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2022-11-01',
            'avatar_url' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150',
            'location' => 'Headquarters (Jakarta)',
            'annual_leave_quota' => 12,
            'annual_leave_used' => 3,
        ]);

        User::create([
            'name' => $stfFinance->name,
            'email' => $stfFinance->email,
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'department' => 'Finance',
            'employee_id' => $stfFinance->id,
        ]);

        // ==========================================
        // 4. DEPARTEMEN BISNIS DEVELOPMENT
        // ==========================================
        $mgrBizdev = Employee::create([
            'nip' => 'NEX-30101',
            'name' => 'Dimas Pratama',
            'email' => 'dimas.bizdev@gyokai.com',
            'phone' => '+62 817-8899-0011',
            'role_title' => 'Business Development Manager',
            'department' => 'Bisnis Development',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2021-08-20',
            'avatar_url' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150',
            'location' => 'Headquarters (Jakarta)',
            'annual_leave_quota' => 14,
            'annual_leave_used' => 2,
        ]);

        User::create([
            'name' => $mgrBizdev->name,
            'email' => $mgrBizdev->email,
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'department' => 'Bisnis Development',
            'employee_id' => $mgrBizdev->id,
        ]);

        $stfBizdev = Employee::create([
            'nip' => 'NEX-30102',
            'name' => 'Fadhil Ihsan',
            'email' => 'fadhil.bizdev@gyokai.com',
            'phone' => '+62 819-3344-5566',
            'role_title' => 'Key Account Executive',
            'department' => 'Bisnis Development',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2023-02-15',
            'avatar_url' => 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150',
            'location' => 'Headquarters (Jakarta)',
            'annual_leave_quota' => 12,
            'annual_leave_used' => 0,
        ]);

        User::create([
            'name' => $stfBizdev->name,
            'email' => $stfBizdev->email,
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'department' => 'Bisnis Development',
            'employee_id' => $stfBizdev->id,
        ]);

        // ==========================================
        // 5. DEPARTEMEN GENERAL AFFAIRS (GA)
        // ==========================================
        $mgrGA = Employee::create([
            'nip' => 'NEX-40101',
            'name' => 'Joko Susanto',
            'email' => 'joko.ga@gyokai.com',
            'phone' => '+62 821-2233-4455',
            'role_title' => 'General Affairs Manager',
            'department' => 'GA',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2020-02-01',
            'avatar_url' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150',
            'location' => 'Headquarters (Jakarta)',
            'annual_leave_quota' => 14,
            'annual_leave_used' => 1,
        ]);

        User::create([
            'name' => $mgrGA->name,
            'email' => $mgrGA->email,
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'department' => 'GA',
            'employee_id' => $mgrGA->id,
        ]);

        $stfGA = Employee::create([
            'nip' => 'NEX-40102',
            'name' => 'Rudy Hermanto',
            'email' => 'rudy.ga@gyokai.com',
            'phone' => '+62 822-4455-6677',
            'role_title' => 'Facility & Asset Officer',
            'department' => 'GA',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2022-04-10',
            'avatar_url' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150',
            'location' => 'Headquarters (Jakarta)',
            'annual_leave_quota' => 12,
            'annual_leave_used' => 2,
        ]);

        User::create([
            'name' => $stfGA->name,
            'email' => $stfGA->email,
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'department' => 'GA',
            'employee_id' => $stfGA->id,
        ]);

        // ==========================================
        // 6. POSISI MANDIRI (TANPA DEPARTEMEN)
        // ==========================================
        $empReceptionist = Employee::create([
            'nip' => 'NEX-50101',
            'name' => 'Nadia Safitri',
            'email' => 'nadia.receptionist@gyokai.com',
            'phone' => '+62 821-9900-1122',
            'role_title' => 'Resepsionis',
            'department' => 'Resepsionis',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2023-01-15',
            'avatar_url' => 'https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=150',
            'location' => 'Lobby Headquarters (Jakarta)',
            'annual_leave_quota' => 12,
            'annual_leave_used' => 0,
        ]);

        User::create([
            'name' => $empReceptionist->name,
            'email' => $empReceptionist->email,
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'department' => 'Resepsionis',
            'employee_id' => $empReceptionist->id,
        ]);

        $empTrainer = Employee::create([
            'nip' => 'NEX-60101',
            'name' => 'Bagus Wicaksono',
            'email' => 'bagus.trainer@gyokai.com',
            'phone' => '+62 813-8877-6655',
            'role_title' => 'Corporate Trainer',
            'department' => 'Trainer',
            'employment_type' => 'Tetap (FTE)',
            'status' => 'active',
            'join_date' => '2022-09-01',
            'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
            'location' => 'Training Center (Jakarta)',
            'annual_leave_quota' => 12,
            'annual_leave_used' => 0,
        ]);

        User::create([
            'name' => $empTrainer->name,
            'email' => $empTrainer->email,
            'password' => Hash::make('password123'),
            'role' => 'karyawan',
            'department' => 'Trainer',
            'employee_id' => $empTrainer->id,
        ]);

        // ==========================================
        // 7. SEED DATA TRANSAKSI DEPARTEMEN
        // ==========================================
        $today = Carbon::today();

        // Presensi
        foreach ([$direktur, $mgrHR, $mgrFinance, $mgrBizdev, $mgrGA] as $emp) {
            Attendance::create([
                'employee_id' => $emp->id,
                'date' => $today->toDateString(),
                'clock_in' => '08:10:00',
                'clock_out' => null,
                'status' => 'on_time',
                'work_mode' => 'WFO',
                'location_name' => 'HQ Jakarta',
            ]);
        }

        // Cuti (HR)
        LeaveRequest::create([
            'employee_id' => $stfHR->id,
            'type' => 'Cuti Tahunan',
            'start_date' => Carbon::now()->addDays(2)->toDateString(),
            'end_date' => Carbon::now()->addDays(4)->toDateString(),
            'duration_days' => 3,
            'reason' => 'Keperluan urusan keluarga',
            'status' => 'pending',
        ]);

        // Reimbursements (Finance)
        Reimbursement::create([
            'employee_id' => $stfBizdev->id,
            'title' => 'Jamuan Meeting Klien PT Telkom',
            'category' => 'Client Meeting / Makan',
            'amount' => 450000,
            'expense_date' => Carbon::now()->subDays(1)->toDateString(),
            'description' => 'Makan siang & negosiasi proposal deal',
            'status' => 'pending',
        ]);

        Reimbursement::create([
            'employee_id' => $stfGA->id,
            'title' => 'Pembelian Kabel HDMI & Extension Kantor',
            'category' => 'Operasional Kantor',
            'amount' => 280000,
            'expense_date' => Carbon::now()->subDays(2)->toDateString(),
            'description' => 'Penggantian kabel meeting room lt. 5',
            'status' => 'approved',
            'reviewed_by' => $mgrFinance->id,
            'reviewed_at' => Carbon::now()->subDays(1),
        ]);

        // Leads Pipeline (Bisnis Development)
        LeadProject::create([
            'created_by' => $mgrBizdev->id,
            'client_name' => 'PT Astra Mega International',
            'project_title' => 'Pengadaan Lisensi Enterprise & Custom Integrasi',
            'deal_value' => 350000000,
            'stage' => 'negotiation',
            'target_close_date' => Carbon::now()->addDays(15)->toDateString(),
            'notes' => 'Telah lolos tahap presentasi technical architecture.',
        ]);

        LeadProject::create([
            'created_by' => $stfBizdev->id,
            'client_name' => 'Bank Mandiri Syariah',
            'project_title' => 'POC Modul Presensi & Geo-fencing Karyawan',
            'deal_value' => 180000000,
            'stage' => 'proposal',
            'target_close_date' => Carbon::now()->addDays(20)->toDateString(),
            'notes' => 'Menunggu verifikasi compliance tim IT Bank.',
        ]);

        // Inventory Items (General Affairs)
        InventoryItem::create([
            'item_code' => 'AST-IT-001',
            'name' => 'MacBook Pro M2 14-inch Space Gray',
            'category' => 'Perangkat IT / Laptop',
            'stock_qty' => 1,
            'location' => 'HQ Lt. 5',
            'condition' => 'good',
            'assigned_to' => $stfBizdev->id,
            'notes' => 'SN: C02G9988H12, RAM 16GB, SSD 512GB',
        ]);

        InventoryItem::create([
            'item_code' => 'AST-FAS-012',
            'name' => 'Proyektor Epson EB-E01 HDMI',
            'category' => 'Fasilitas Gedung',
            'stock_qty' => 2,
            'location' => 'Meeting Room Utama Lt. 5',
            'condition' => 'good',
            'assigned_to' => null,
            'notes' => 'Fasilitas bersama ruang meeting',
        ]);

        InventoryItem::create([
            'item_code' => 'AST-FAS-025',
            'name' => 'Kursi Ergonomis Kantor Sihoo M57',
            'category' => 'Furniture & Meja',
            'stock_qty' => 1,
            'location' => 'Ruang GA Lt. 2',
            'condition' => 'maintenance',
            'assigned_to' => null,
            'notes' => 'Pneumatik silinder perlu diganti',
        ]);

        // ==========================================
        // 7. SEED DATA TUGAS (TASKS): Skala Tim & Individu
        // ==========================================
        // HR Tasks
        \App\Models\Task::create([
            'title' => 'Penyusunan Program Pelatihan & Onboarding Q4',
            'description' => 'Merancang kurikulum pelatihan soft-skills dan evaluasi 90 hari pegawai baru.',
            'department' => 'HR',
            'scope' => 'department',
            'created_by' => $mgrHR->id,
            'assigned_to' => null,
            'priority' => 'high',
            'status' => 'in_progress',
            'progress_percentage' => 65,
            'due_date' => Carbon::now()->addDays(10)->toDateString(),
        ]);

        \App\Models\Task::create([
            'title' => 'Screening & Interview Kandidat Senior Frontend',
            'description' => 'Review 25 berkas portofolio pelamar dan jadwalkan sesi technical test.',
            'department' => 'HR',
            'scope' => 'individual',
            'created_by' => $mgrHR->id,
            'assigned_to' => $stfHR->id,
            'priority' => 'medium',
            'status' => 'in_progress',
            'progress_percentage' => 40,
            'due_date' => Carbon::now()->addDays(5)->toDateString(),
        ]);

        // Finance Tasks
        \App\Models\Task::create([
            'title' => 'Tutup Buku & Rekonsiliasi Fiskal Pajak Bulanan',
            'description' => 'Pengecekan SPT Masa PPh 21, PPh 23, dan PPN bersama konsultan pajak eksternal.',
            'department' => 'Finance',
            'scope' => 'department',
            'created_by' => $mgrFinance->id,
            'assigned_to' => null,
            'priority' => 'urgent',
            'status' => 'in_progress',
            'progress_percentage' => 80,
            'due_date' => Carbon::now()->addDays(3)->toDateString(),
        ]);

        \App\Models\Task::create([
            'title' => 'Verifikasi Invoice & Bukti Potong Reimbursement',
            'description' => 'Audit 15 berkas klaim pengeluaran operasional minggu ke-3.',
            'department' => 'Finance',
            'scope' => 'individual',
            'created_by' => $mgrFinance->id,
            'assigned_to' => $stfFinance->id,
            'priority' => 'medium',
            'status' => 'completed',
            'progress_percentage' => 100,
            'due_date' => Carbon::now()->subDays(1)->toDateString(),
            'completed_at' => Carbon::now()->subDays(1)->toDateString(),
        ]);

        // Bisnis Development Tasks
        \App\Models\Task::create([
            'title' => 'Penyusunan Proposal Tender Bank Mandiri Syariah',
            'description' => 'Menyelesaikan dokumen BAFO, proposal solusi teknis, dan timeline implementasi.',
            'department' => 'Bisnis Development',
            'scope' => 'department',
            'created_by' => $mgrBizdev->id,
            'assigned_to' => null,
            'priority' => 'urgent',
            'status' => 'in_progress',
            'progress_percentage' => 70,
            'due_date' => Carbon::now()->addDays(6)->toDateString(),
        ]);

        \App\Models\Task::create([
            'title' => 'Follow-up Klien Astra Mega International',
            'description' => 'Konfirmasi penyesuaian klausul NDA dan jadwal demo prototype tahap 2.',
            'department' => 'Bisnis Development',
            'scope' => 'individual',
            'created_by' => $mgrBizdev->id,
            'assigned_to' => $stfBizdev->id,
            'priority' => 'high',
            'status' => 'in_progress',
            'progress_percentage' => 50,
            'due_date' => Carbon::now()->addDays(4)->toDateString(),
        ]);

        // General Affairs Tasks
        \App\Models\Task::create([
            'title' => 'Audit Fisik Seluruh Aset Laptop & Inventaris IT',
            'description' => 'Pengecekan nomor seri, penempelan barcode aset baru, dan update database.',
            'department' => 'GA',
            'scope' => 'department',
            'created_by' => $mgrGA->id,
            'assigned_to' => null,
            'priority' => 'high',
            'status' => 'in_progress',
            'progress_percentage' => 45,
            'due_date' => Carbon::now()->addDays(12)->toDateString(),
        ]);

        \App\Models\Task::create([
            'title' => 'Perbaikan Silinder Pneumatik Kursi Kantor Lt. 2',
            'description' => 'Pemesanan sparepart hidrolik dan perbaikan kursi teknisi.',
            'department' => 'GA',
            'scope' => 'individual',
            'created_by' => $mgrGA->id,
            'assigned_to' => $stfGA->id,
            'priority' => 'low',
            'status' => 'todo',
            'progress_percentage' => 15,
            'due_date' => Carbon::now()->addDays(8)->toDateString(),
        ]);
    }
}
