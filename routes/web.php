    <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\DashboardController;
    use App\Http\Controllers\AttendanceController;
    use App\Http\Controllers\LeaveRequestController;
    use App\Http\Controllers\EmployeeController;
    use App\Http\Controllers\FinanceController;
    use App\Http\Controllers\BisnisDevelopmentController;
    use App\Http\Controllers\GeneralAffairsController;
    use App\Http\Controllers\TaskController;
    use App\Http\Controllers\RecruitmentController;
    use App\Http\Controllers\OffboardingController;
    use App\Http\Controllers\ProfileController;
    use App\Http\Controllers\FacilityTicketController;
    use App\Http\Controllers\ReportExportController;
    use App\Http\Controllers\AttendanceExcuseController;

    // Authentication Routes (Guest only)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
    });

    // Route Download Mobile App Android (.APK)
    Route::get('/download-app', function () {
        $path = public_path('downloads/hris-gyokai.apk');
        if (file_exists($path)) {
            return response()->download($path, 'hris-gyokai.apk', [
                'Content-Type' => 'application/vnd.android.package-archive',
            ]);
        }
        return abort(404, 'File APK belum tersedia.');
    })->name('mobile.download');

    Route::get('/app', function () {
        return redirect('/downloads/');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

    // Protected Routes (Wajib Login)
    Route::middleware('auth')->group(function () {
        
        // Core Modul Umum (Bisa diakses seluruh karyawan & atasan)
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Pengaturan Profil Akun Sendiri (Password, Nama, Email, Foto Profil, Sesi & Log Keamanan)
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile/devices/{id}', [ProfileController::class, 'terminateDevice'])->name('profile.terminateDevice');

        // Panduan Penggunaan & Diagram Alur Sistem (Tersedia untuk Seluruh Akun)
        Route::get('/guide', [\App\Http\Controllers\GuideController::class, 'index'])->name('guide.index');

        // Kehadiran (Attendance)
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clockIn');
            Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clockOut');
            Route::post('/face-record', [AttendanceController::class, 'faceRecord'])->name('faceRecord');
            Route::get('/photo/{attendance}/{type?}', [AttendanceController::class, 'showPhoto'])->name('photo');

            // Pengajuan & Persetujuan Reset Kehadiran Seluruh Karyawan (Manager HR <-> Direktur)
            Route::post('/reset-request', [AttendanceController::class, 'requestReset'])->name('resetRequest');
            Route::post('/reset-requests/{id}/approve', [AttendanceController::class, 'approveReset'])->name('approveReset');
            Route::post('/reset-requests/{id}/reject', [AttendanceController::class, 'rejectReset'])->name('rejectReset');

            // Modul Perizinan Kehadiran (Izin Terlambat, Izin Dinas Luar, Keterangan Tidak Absen Pulang)
            // Diajukan oleh seluruh karyawan & manager -> Disetujui oleh Tim HR / Direktur
            Route::prefix('excuses')->name('excuses.')->group(function () {
                Route::get('/', [AttendanceExcuseController::class, 'index'])->name('index');
                Route::post('/', [AttendanceExcuseController::class, 'store'])->name('store');
                Route::patch('/{id}/status', [AttendanceExcuseController::class, 'updateStatus'])->name('updateStatus');
            });
        });

        // Modul Ekspor Laporan Resmi & Template PDF
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/kpi/pdf', [ReportExportController::class, 'exportKpi'])->name('kpi.pdf');
            Route::get('/attendance/pdf', [ReportExportController::class, 'exportAttendance'])->name('attendance.pdf');
        });

        // Modul Manajemen Tugas (Tasks): Skala Tim Departemen vs Individu, serta Laporan Direktur
        Route::prefix('tasks')->name('tasks.')->group(function () {
            Route::get('/', [TaskController::class, 'index'])->name('index');
            Route::post('/store', [TaskController::class, 'store'])->name('store');
            Route::patch('/{id}/progress', [TaskController::class, 'updateProgress'])->name('updateProgress');
            Route::get('/export/excel', [TaskController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export/pdf', [TaskController::class, 'exportPdf'])->name('export.pdf');
        });

        // Modul Kalender & Catatan/Jadwal: Tersedia untuk SEMUA akun
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/', [\App\Http\Controllers\CalendarController::class, 'index'])->name('index');
            Route::post('/store', [\App\Http\Controllers\CalendarController::class, 'store'])->name('store');
            Route::put('/{id}', [\App\Http\Controllers\CalendarController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\CalendarController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/approve', [\App\Http\Controllers\CalendarController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\App\Http\Controllers\CalendarController::class, 'reject'])->name('reject');
        });

        // Modul Cuti & Izin: Seluruh karyawan & manager dapat mengajukan, approval khusus HR & Direktur
        Route::prefix('leave')->name('leave.')->group(function () {
            Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
            Route::post('/store', [LeaveRequestController::class, 'store'])->name('store');
            Route::patch('/{id}/status', [LeaveRequestController::class, 'updateStatus'])
                ->name('updateStatus');
            Route::patch('/employees/{id}/quota', [LeaveRequestController::class, 'updateQuota'])
                ->name('updateQuota');
        });

        // Modul Finance: Reimbursement & Pelaporan Slip Gaji Karyawan/Manager
        Route::prefix('finance')->name('finance.')->group(function () {
            Route::get('/', [FinanceController::class, 'index'])->name('index');
            Route::post('/reimbursement', [FinanceController::class, 'store'])->name('store');
            Route::patch('/reimbursement/{id}/status', [FinanceController::class, 'updateStatus'])
                ->name('updateStatus');

            // Pelaporan & Penerbitan Slip Gaji (Salary Slips)
            Route::get('/salary-slips/{id}', [FinanceController::class, 'showSalarySlip'])->name('salarySlips.show');
            Route::post('/salary-slips', [FinanceController::class, 'storeSalarySlip'])->name('salarySlips.store');

            // Pengiriman Slip Gaji Massal (Email Dispatch & Multi-SMTP Sync Gyokai02)
            // Hanya dapat diakses oleh Departemen Finance & Direktur
            Route::middleware('department:Finance')->prefix('dispatch')->name('dispatch.')->group(function () {
                Route::get('/', [\App\Http\Controllers\FinanceDispatchController::class, 'index'])->name('index');
                Route::post('/upload', [\App\Http\Controllers\FinanceDispatchController::class, 'upload'])->name('upload');
                Route::post('/send/start', [\App\Http\Controllers\FinanceDispatchController::class, 'startSending'])->name('send.start');
                Route::get('/sending', [\App\Http\Controllers\FinanceDispatchController::class, 'showSending'])->name('sending');
                Route::post('/send/batch', [\App\Http\Controllers\FinanceDispatchController::class, 'sendBatch'])->name('send.batch');
                Route::post('/send/cancel', [\App\Http\Controllers\FinanceDispatchController::class, 'cancelSending'])->name('send.cancel');
                Route::get('/reports/{sendSession}', [\App\Http\Controllers\FinanceDispatchController::class, 'report'])->name('report');
            });
        });

        // Modul Layanan Fasilitas & Kendala Kerusakan (GA Helpdesk)
        // Seluruh karyawan & manager dapat melapor, Tim GA / Direktur menerima & update progres
        Route::prefix('facilities')->name('facilities.')->group(function () {
            Route::get('/', [FacilityTicketController::class, 'index'])->name('index');
            Route::post('/store', [FacilityTicketController::class, 'store'])->name('store');
            Route::patch('/{id}/progress', [FacilityTicketController::class, 'updateProgress'])->name('updateProgress');
        });

        // Modul Direktori Karyawan (HR & Direktur)
        Route::middleware('department:HR')->prefix('employees')->name('employees.')->group(function () {
            Route::get('/', [EmployeeController::class, 'index'])->name('index');

            // Fitur Menarik / Ekspor Data Karyawan (Khusus Direktur & Manager HR)
            Route::get('/export/excel', [EmployeeController::class, 'exportExcel'])->name('export.excel');
            Route::get('/export/csv', [EmployeeController::class, 'exportCsv'])->name('export.csv');
            Route::get('/export/pdf', [EmployeeController::class, 'exportPdf'])->name('export.pdf');

            Route::get('/{id}', [EmployeeController::class, 'show'])->name('show');
            Route::post('/store', [EmployeeController::class, 'store'])->name('store');
            Route::post('/batch-store', [EmployeeController::class, 'batchStore'])->name('batchStore');
            Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
            Route::patch('/{id}/account', [EmployeeController::class, 'updateAccount'])->name('updateAccount');
            Route::delete('/{id}', [EmployeeController::class, 'destroy'])->name('destroy');

            // Pengajuan & Persetujuan Perubahan Status Akun (Karyawan <-> Manager)
            Route::post('/role-requests/{id}/approve', [EmployeeController::class, 'approveRoleRequest'])->name('roleRequests.approve');
            Route::post('/role-requests/{id}/reject', [EmployeeController::class, 'rejectRoleRequest'])->name('roleRequests.reject');
        });

        // Modul Khusus HR: Rekrutmen, Onboarding, dan Offboarding (HR & Direktur)
        Route::middleware('department:HR')->prefix('hr')->name('hr.')->group(function () {
            // 1. Rekrutmen & Onboarding
            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('/', [RecruitmentController::class, 'index'])->name('index');
                Route::post('/vacancy', [RecruitmentController::class, 'storeVacancy'])->name('storeVacancy');
                Route::post('/candidate', [RecruitmentController::class, 'storeCandidate'])->name('storeCandidate');
                Route::patch('/candidate/{id}/stage', [RecruitmentController::class, 'updateCandidateStage'])->name('updateStage');
                Route::patch('/candidate/{id}/onboarding', [RecruitmentController::class, 'updateOnboardingChecklist'])->name('updateOnboarding');
            });

            // 2. Offboarding
            Route::prefix('offboarding')->name('offboarding.')->group(function () {
                Route::get('/', [OffboardingController::class, 'index'])->name('index');
                Route::post('/store', [OffboardingController::class, 'store'])->name('store');
                Route::patch('/{id}/checklist', [OffboardingController::class, 'updateChecklist'])->name('updateChecklist');
            });

            // 3. Penerbitan Surat Keterangan / Paklaring (LPK Gyokai Indonesia)
            Route::prefix('paklaring')->name('paklaring.')->group(function () {
                Route::get('/', [\App\Http\Controllers\PaklaringController::class, 'index'])->name('index');
                Route::post('/store', [\App\Http\Controllers\PaklaringController::class, 'store'])->name('store');
                Route::get('/print-direct', [\App\Http\Controllers\PaklaringController::class, 'directPrint'])->name('directPrint');
                Route::get('/{id}/print', [\App\Http\Controllers\PaklaringController::class, 'print'])->name('print');
                Route::get('/template-image', [\App\Http\Controllers\PaklaringController::class, 'templateImage'])->name('templateImage');
                Route::delete('/{id}', [\App\Http\Controllers\PaklaringController::class, 'destroy'])->name('destroy');
            });
        });

        // 3. MODUL KHUSUS DEPARTEMEN BISNIS DEVELOPMENT & Direktur
        Route::middleware('department:Bisnis Development')->prefix('bizdev')->name('bizdev.')->group(function () {
            Route::get('/', [BisnisDevelopmentController::class, 'index'])->name('index');
            Route::post('/deals', [BisnisDevelopmentController::class, 'store'])->name('store');
            Route::patch('/deals/{id}/stage', [BisnisDevelopmentController::class, 'updateStage'])->name('updateStage');
        });

        // 4. MODUL KHUSUS DEPARTEMEN GENERAL AFFAIRS (GA), FINANCE & Direktur
        Route::middleware('department:GA,Finance')->prefix('ga')->name('ga.')->group(function () {
            Route::get('/', [GeneralAffairsController::class, 'index'])->name('index');
            Route::post('/inventory', [GeneralAffairsController::class, 'store'])->name('store');
            Route::patch('/inventory/{id}', [GeneralAffairsController::class, 'update'])->name('update');
            Route::post('/request', [GeneralAffairsController::class, 'storeRequest'])->name('storeRequest');
            Route::patch('/request/{id}/status', [GeneralAffairsController::class, 'updateRequestStatus'])->name('updateRequestStatus');

            // Fitur Manajemen ATK Kantor (Office Supplies) & Pemakaian
            Route::post('/supplies', [GeneralAffairsController::class, 'storeSupply'])->name('supplies.store');
            Route::patch('/supplies/{id}', [GeneralAffairsController::class, 'updateSupply'])->name('supplies.update');
            Route::delete('/supplies/{id}', [GeneralAffairsController::class, 'destroySupply'])->name('supplies.destroy');
            Route::post('/supplies/{id}/restock', [GeneralAffairsController::class, 'restockSupply'])->name('supplies.restock');
            Route::post('/supplies/usage', [GeneralAffairsController::class, 'recordSupplyUsage'])->name('supplies.recordUsage');
            Route::delete('/supplies/usage/{id}', [GeneralAffairsController::class, 'destroySupplyUsage'])->name('supplies.destroyUsage');
        });

        // Modul Notifikasi: Tersedia untuk seluruh akun (Karyawan, Manager, Direktur)
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
            Route::get('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('read');
            Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('markAllRead');
            Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
        });

        // Modul Chat Departemen: Komunikasi tim manager dan karyawan departemen
        Route::prefix('chat')->name('chat.')->group(function () {
            Route::get('/', [\App\Http\Controllers\DepartmentChatController::class, 'index'])->name('index');
            Route::post('/send', [\App\Http\Controllers\DepartmentChatController::class, 'sendMessage'])->name('send');
            Route::get('/fetch', [\App\Http\Controllers\DepartmentChatController::class, 'fetchMessages'])->name('fetch');
        });

        // 5. MODUL KHUSUS DATA MEMBER (KHUSUS RESEPSIONIS & DIREKTUR)
        Route::middleware('department:Resepsionis')->prefix('receptionist/members')->name('receptionist.members.')->group(function () {
            Route::get('/', [\App\Http\Controllers\MemberController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\MemberController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\MemberController::class, 'store'])->name('store');
            Route::get('/export', [\App\Http\Controllers\MemberController::class, 'export'])->name('export');
            Route::get('/{id}/edit', [\App\Http\Controllers\MemberController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\MemberController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\MemberController::class, 'destroy'])->name('destroy');
            Route::put('/{id}/update-status', [\App\Http\Controllers\MemberController::class, 'updateSingleMemberStatus'])->name('updateStatus');
            Route::post('/bulk-update-status', [\App\Http\Controllers\MemberController::class, 'bulkUpdateMemberStatus'])->name('bulkUpdateStatus');
        });

        // 6. MODUL TATA PERSURATAN RESMI & PENOMORAN SURAT (SEKRETARIS DIREKTUR & DIREKTUR)
        Route::prefix('secretary/letters')->name('secretary.letters.')->group(function () {
            Route::get('/', [\App\Http\Controllers\OutgoingLetterController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\OutgoingLetterController::class, 'store'])->name('store');
            Route::get('/next-number', [\App\Http\Controllers\OutgoingLetterController::class, 'getNextNumber'])->name('nextNumber');
            Route::patch('/{id}', [\App\Http\Controllers\OutgoingLetterController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\OutgoingLetterController::class, 'destroy'])->name('destroy');
            Route::get('/{id}/download', [\App\Http\Controllers\OutgoingLetterController::class, 'downloadFile'])->name('download');
        });

    });

    // =========================================================================
    // PROTOKOL CLOUD SERVER / ADMS MESIN FINGER SOLUTION X609 (Real-time Push)
    // =========================================================================
    Route::prefix('iclock')->group(function () {
        Route::get('/cdata', [\App\Http\Controllers\Api\AdmsAttendanceController::class, 'handshake']);
        Route::post('/cdata', [\App\Http\Controllers\Api\AdmsAttendanceController::class, 'receiveLog']);
        Route::get('/ping', [\App\Http\Controllers\Api\AdmsAttendanceController::class, 'ping']);
        Route::post('/ping', [\App\Http\Controllers\Api\AdmsAttendanceController::class, 'ping']);
        Route::get('/getrequest', [\App\Http\Controllers\Api\AdmsAttendanceController::class, 'getRequest']);
        Route::post('/devicecmd', [\App\Http\Controllers\Api\AdmsAttendanceController::class, 'deviceCmd']);
    });
