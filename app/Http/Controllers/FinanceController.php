<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\SalarySlip;
use App\Traits\ValidatesFileUpload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;
use ZipArchive;

class FinanceController extends Controller
{
    use ValidatesFileUpload;
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'slips'); // Default ke tab slip gaji / reimbursements
        $status = $request->query('status');
        $period = $request->query('period');
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        // 1. DATA REIMBURSEMENTS
        $query = Reimbursement::with(['employee', 'reviewer'])->orderBy('created_at', 'desc');

        // Jika BUKAN tim Finance dan BUKAN Direktur, hanya tampilkan klaim milik sendiri
        if (!$authUser->canApproveReimbursements() && $currentUser) {
            $query->where('employee_id', $currentUser->id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $reimbursements = $query->paginate(10);

        $pendingQuery = Reimbursement::where('status', 'pending');
        $approvedQuery = Reimbursement::where('status', 'approved');
        $paidQuery = Reimbursement::where('status', 'paid');

        if (!$authUser->canApproveReimbursements() && $currentUser) {
            $pendingQuery->where('employee_id', $currentUser->id);
            $approvedQuery->where('employee_id', $currentUser->id);
            $paidQuery->where('employee_id', $currentUser->id);
        }

        $totalPending = $pendingQuery->sum('amount');
        $totalApproved = $approvedQuery->sum('amount');
        $totalPaid = $paidQuery->sum('amount');

        // 2. DATA SLIP GAJI (SALARY SLIPS)
        $slipsQuery = SalarySlip::with('employee')->orderBy('payment_date', 'desc');

        // Hak akses: Jika bukan Finance dan bukan Direktur, hanya bisa lihat slip gaji miliknya sendiri
        if (!$authUser->canApproveReimbursements() && $currentUser) {
            $slipsQuery->where('employee_id', $currentUser->id);
        }

        if ($period) {
            $slipsQuery->where('period_month', $period);
        }

        $salarySlips = $slipsQuery->paginate(10, ['*'], 'slips_page');

        // Metrik Ringkasan Pengeluaran Gaji / Payroll
        $payrollQuery = SalarySlip::query();
        if (!$authUser->canApproveReimbursements() && $currentUser) {
            $payrollQuery->where('employee_id', $currentUser->id);
        }

        $totalPayrollDisbursed = (clone $payrollQuery)->where('status', 'paid')->sum('net_salary');
        $totalSlipsCount = (clone $payrollQuery)->count();
        $availablePeriods = SalarySlip::select('period_month')->distinct()->pluck('period_month');

        $employees = $authUser->canApproveReimbursements()
            ? Employee::orderBy('name')->get()
            : ($currentUser ? Employee::where('id', $currentUser->id)->get() : Employee::all());

        return view('departments.finance.index', compact(
            'tab',
            'reimbursements',
            'salarySlips',
            'employees',
            'authUser',
            'currentUser',
            'status',
            'period',
            'availablePeriods',
            'totalPending',
            'totalApproved',
            'totalPaid',
            'totalPayrollDisbursed',
            'totalSlipsCount'
        ));
    }

    public function showSalarySlip($id)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        $slip = SalarySlip::with('employee')->findOrFail($id);

        // Keamanan: Karyawan hanya bisa melihat slip miliknya sendiri
        if (!$authUser->canApproveReimbursements() && $currentUser && $slip->employee_id !== $currentUser->id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk melihat slip gaji karyawan lain.');
        }

        return view('departments.finance.salary_slip_detail', compact('slip', 'authUser'));
    }

    public function storeSalarySlip(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canApproveReimbursements()) {
            abort(403, 'Akses Ditolak: Hanya Departemen Finance atau Direktur yang dapat menerbitkan slip gaji.');
        }

        // Mendukung penerbitan untuk 1 orang (employee_id) atau banyak orang sekaligus (employee_ids[])
        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'employee_ids' => 'nullable|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'period_month' => 'required|string',
            'payment_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance_position' => 'nullable|numeric|min:0',
            'allowance_transport' => 'nullable|numeric|min:0',
            'allowance_meal' => 'nullable|numeric|min:0',
            'overtime_pay' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deduction_bpjs_tk' => 'nullable|numeric|min:0',
            'deduction_bpjs_kes' => 'nullable|numeric|min:0',
            'deduction_tax_pph21' => 'nullable|numeric|min:0',
            'deduction_absence' => 'nullable|numeric|min:0',
            'deduction_other' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $employeeIds = $request->input('employee_ids');
        if (empty($employeeIds) && $request->filled('employee_id')) {
            $employeeIds = [$request->input('employee_id')];
        }

        if (empty($employeeIds)) {
            return back()->with('error', 'Silakan pilih minimal satu karyawan atau manager untuk menerbitkan slip gaji.');
        }

        $basic = (float) $request->basic_salary;
        $allowPos = (float) ($request->allowance_position ?? 0);
        $allowTrans = (float) ($request->allowance_transport ?? 0);
        $allowMeal = (float) ($request->allowance_meal ?? 0);
        $overtime = (float) ($request->overtime_pay ?? 0);
        $bonus = (float) ($request->bonus ?? 0);
        $totalEarnings = $basic + $allowPos + $allowTrans + $allowMeal + $overtime + $bonus;

        $bpjsTk = (float) ($request->deduction_bpjs_tk ?? 0);
        $bpjsKes = (float) ($request->deduction_bpjs_kes ?? 0);
        $pph21 = (float) ($request->deduction_tax_pph21 ?? 0);
        $absence = (float) ($request->deduction_absence ?? 0);
        $otherDeduct = (float) ($request->deduction_other ?? 0);
        $totalDeductions = $bpjsTk + $bpjsKes + $pph21 + $absence + $otherDeduct;

        $netSalary = $totalEarnings - $totalDeductions;
        $count = 0;

        DB::transaction(function () use ($employeeIds, $request, $basic, $allowPos, $allowTrans, $allowMeal, $overtime, $bonus, $bpjsTk, $bpjsKes, $pph21, $absence, $otherDeduct, $totalEarnings, $totalDeductions, $netSalary, $authUser, &$count) {
            foreach ($employeeIds as $empId) {
                $slipNumber = 'SLIP-' . date('Ym', strtotime($request->payment_date)) . '-' . str_pad($empId, 4, '0', STR_PAD_LEFT) . '-' . rand(10, 99);

                SalarySlip::create([
                    'slip_number' => $slipNumber,
                    'employee_id' => $empId,
                    'period_month' => $request->period_month,
                    'payment_date' => $request->payment_date,
                    'basic_salary' => $basic,
                    'allowance_position' => $allowPos,
                    'allowance_transport' => $allowTrans,
                    'allowance_meal' => $allowMeal,
                    'overtime_pay' => $overtime,
                    'bonus' => $bonus,
                    'deduction_bpjs_tk' => $bpjsTk,
                    'deduction_bpjs_kes' => $bpjsKes,
                    'deduction_tax_pph21' => $pph21,
                    'deduction_absence' => $absence,
                    'deduction_other' => $otherDeduct,
                    'total_earnings' => $totalEarnings,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'status' => 'paid',
                    'payment_method' => $request->payment_method,
                    'bank_name' => $request->bank_name,
                    'bank_account_number' => $request->bank_account_number,
                    'notes' => $request->notes,
                    'created_by' => $authUser->id,
                ]);

                $count++;
            }
        });

        $message = ($count > 1) 
            ? "Berhasil menerbitkan {$count} slip gaji karyawan & manager secara massal untuk periode {$request->period_month}."
            : "Slip gaji baru berhasil diterbitkan oleh Departemen Finance.";

        return redirect()->route('finance.index', ['tab' => 'slips'])->with('success', $message);
    }

    /**
     * Hapus Slip Gaji (Khusus Akun Manager Finance & Direktur Utama)
     */
    public function destroySalarySlip($id)
    {
        $authUser = Auth::user();
        if (!$authUser->canDeleteSalarySlips()) {
            abort(403, 'Akses Ditolak: Hanya Manager Finance atau Direktur yang memiliki wewenang untuk menghapus slip gaji.');
        }

        $slip = SalarySlip::with('employee')->findOrFail($id);
        $slipNumber = $slip->slip_number;
        $employeeName = $slip->employee?->name ?? 'Karyawan';
        $period = $slip->period_month;

        $slip->delete();

        return redirect()->route('finance.index', ['tab' => 'slips'])
            ->with('success', "Slip gaji {$slipNumber} ({$employeeName} - Periode {$period}) berhasil dihapus dari sistem.");
    }

    /**
     * Hapus Banyak Slip Gaji Sekaligus / Mass Delete (Khusus Akun Manager Finance & Direktur Utama)
     */
    public function bulkDestroySalarySlips(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canDeleteSalarySlips()) {
            abort(403, 'Akses Ditolak: Hanya Manager Finance atau Direktur yang memiliki wewenang untuk menghapus slip gaji.');
        }

        $request->validate([
            'slip_ids' => 'required|array|min:1',
            'slip_ids.*' => 'exists:salary_slips,id',
        ]);

        $count = SalarySlip::whereIn('id', $request->slip_ids)->delete();

        return redirect()->route('finance.index', ['tab' => 'slips'])
            ->with('success', "Berhasil menghapus {$count} slip gaji yang dipilih dari sistem.");
    }

    /**
     * Terbitkan Slip Gaji melalui Upload File PDF Langsung atau Arsip ZIP Berisi PDF
     */
    public function uploadPdfOrZipSalarySlips(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canApproveReimbursements()) {
            abort(403, 'Akses Ditolak: Hanya Departemen Finance atau Direktur yang dapat menerbitkan slip gaji.');
        }

        set_time_limit(300);

        $request->validate([
            'slip_file' => 'required|file|mimes:pdf,zip|max:102400', // Maksimal 100MB
            'period_month' => 'required|string',
            'payment_date' => 'required|date',
            'employee_id' => 'nullable|exists:employees,id', // Opsi jika upload PDF satuan untuk 1 karyawan tertentu
            'payment_method' => 'nullable|string',
            'net_salary' => 'nullable|numeric|min:0',
        ]);

        $file = $request->file('slip_file');
        $securityCheck = $this->validateUploadSecurity($file, 'any', 100);
        if (!$securityCheck['safe']) {
            return back()->with('error', $securityCheck['message']);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $periodMonth = $request->input('period_month');
        $paymentDate = $request->input('payment_date');

        // Pastikan direktori storage ada dan terlindungi dari akses HTTP langsung
        if (!Storage::disk('public')->exists('salary_slips')) {
            Storage::disk('public')->makeDirectory('salary_slips');
        }
        $htaccessPath = storage_path('app/public/salary_slips/.htaccess');
        if (!file_exists($htaccessPath)) {
            @file_put_contents($htaccessPath, "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n");
        }

        $hasPdfColumn = Schema::hasColumn('salary_slips', 'pdf_path');
        $hasOriginalFilenameColumn = Schema::hasColumn('salary_slips', 'original_filename');

        // KASUS 1: File PDF Satuan & User memilih karyawan spesifik dari form
        if ($extension === 'pdf' && $request->filled('employee_id')) {
            $emp = Employee::findOrFail($request->input('employee_id'));
            $originalName = $file->getClientOriginalName();
            $storedFilename = 'slip_' . date('Ymd_His') . '_' . $emp->id . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.pdf';
            $storedPath = 'salary_slips/' . $storedFilename;

            // Simpan file ke storage public
            Storage::disk('public')->put($storedPath, file_get_contents($file->getRealPath()));

            // Coba ekstrak nominal gaji dari PDF jika tidak diisi manual
            $netSalary = (float) $request->input('net_salary', 0);
            if ($netSalary <= 0) {
                try {
                    $parser = new Parser();
                    $parsed = $parser->parseFile($file->getRealPath());
                    $netSalary = $this->parseSalaryFromText($parsed->getText());
                } catch (\Exception $e) {
                    Log::warning("PdfParser error for {$originalName}: " . $e->getMessage());
                }
                if ($netSalary <= 0) {
                    $netSalary = (float) ($emp->basic_salary ?? 0);
                }
            }

            $slipNumber = 'SLIP-' . date('Ym', strtotime($paymentDate)) . '-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT) . '-' . rand(10, 99);

            $data = [
                'slip_number' => $slipNumber,
                'employee_id' => $emp->id,
                'period_month' => $periodMonth,
                'payment_date' => $paymentDate,
                'basic_salary' => $netSalary,
                'allowance_position' => 0,
                'allowance_transport' => 0,
                'allowance_meal' => 0,
                'overtime_pay' => 0,
                'bonus' => 0,
                'deduction_bpjs_tk' => 0,
                'deduction_bpjs_kes' => 0,
                'deduction_tax_pph21' => 0,
                'deduction_absence' => 0,
                'deduction_other' => 0,
                'total_earnings' => $netSalary,
                'total_deductions' => 0,
                'net_salary' => $netSalary,
                'status' => 'paid',
                'payment_method' => $request->input('payment_method', 'Bank Transfer'),
                'notes' => 'Diterbitkan melalui upload dokumen PDF resmi: ' . $originalName,
                'created_by' => $authUser->id,
            ];

            if ($hasPdfColumn) {
                $data['pdf_path'] = $storedPath;
            }
            if ($hasOriginalFilenameColumn) {
                $data['original_filename'] = $originalName;
            }

            SalarySlip::create($data);

            return redirect()->route('finance.index', ['tab' => 'slips'])
                ->with('success', "Slip gaji untuk {$emp->name} berhasil diterbitkan dengan dokumen PDF '{$originalName}'.");
        }

        // KASUS 2: Upload ZIP atau Upload PDF Massal/Auto-Match
        $tempDir = storage_path('app/temp/slip_pkg_' . time() . '_' . Str::random(8));
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        try {
            if ($extension === 'zip') {
                $zip = new ZipArchive;
                if ($zip->open($file->getRealPath()) === true) {
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entryName = $zip->getNameIndex($i);
                        if (str_ends_with($entryName, '/') || str_ends_with($entryName, '\\')) {
                            continue;
                        }
                        $baseName = basename($entryName);
                        if (strtolower(pathinfo($baseName, PATHINFO_EXTENSION)) !== 'pdf') {
                            continue;
                        }
                        // Abaikan file metadata macOS
                        if (str_contains($entryName, '__MACOSX') || str_starts_with($baseName, '._')) {
                            continue;
                        }
                        // Cegah Zip Slip & sanitasi nama file
                        $safeBaseName = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $baseName);
                        if (empty($safeBaseName) || strtolower(pathinfo($safeBaseName, PATHINFO_EXTENSION)) !== 'pdf') {
                            continue;
                        }

                        $stream = $zip->getStream($entryName);
                        if ($stream) {
                            file_put_contents($tempDir . '/' . $safeBaseName, stream_get_contents($stream));
                            fclose($stream);
                        }
                    }
                    $zip->close();
                } else {
                    return back()->with('error', 'Gagal mengekstrak arsip ZIP. Pastikan file ZIP tidak terenkripsi atau rusak.');
                }
            } else {
                // PDF tunggal tanpa spesifik karyawan -> dicocokkan otomatis
                $safeName = preg_replace('/[^a-zA-Z0-9_\.\-]/', '_', $file->getClientOriginalName());
                $file->move($tempDir, $safeName);
            }

            // Temukan seluruh file PDF di direktori sementara secara rekursif
            $pdfFiles = [];
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempDir));
            foreach ($iterator as $item) {
                if ($item->isFile() && strtolower($item->getExtension()) === 'pdf') {
                    // Hindari file metadata MacOS __MACOSX / ._
                    if (!str_contains($item->getPathname(), '__MACOSX') && !str_starts_with($item->getFilename(), '._')) {
                        $pdfFiles[] = $item->getPathname();
                    }
                }
            }

            if (empty($pdfFiles)) {
                $this->cleanupDirectory($tempDir);
                return back()->with('error', 'Tidak ditemukan file PDF valid di dalam file upload/ZIP.');
            }

            $allEmployees = Employee::with('user')->get();
            $successCount = 0;
            $unmatchedFiles = [];
            $parser = new Parser();

            foreach ($pdfFiles as $pdfPath) {
                $originalName = basename($pdfPath);
                $extractedText = '';

                try {
                    $parsed = $parser->parseFile($pdfPath);
                    $extractedText = $parsed->getText();
                } catch (\Exception $e) {
                    Log::warning("PdfParser failed for {$originalName}: " . $e->getMessage());
                }

                // 1. Pencocokan Cerdas (Smart Matching)
                $matchedEmployee = $this->matchEmployeeForPdf($originalName, $extractedText, $allEmployees);

                if (!$matchedEmployee) {
                    $unmatchedFiles[] = $originalName;
                    continue;
                }

                // Ekstrak gaji bersih dari teks PDF (jika ditemukan angka Take Home Pay / Gaji Bersih)
                $netSalary = $this->parseSalaryFromText($extractedText);
                if ($netSalary <= 0) {
                    $netSalary = (float) ($matchedEmployee->basic_salary ?? 0);
                }

                // Simpan file PDF ke direktori public storage
                $cleanSlug = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
                $storedFilename = 'slip_' . date('Ymd_His') . '_' . $matchedEmployee->id . '_' . $cleanSlug . '.pdf';
                $storedPath = 'salary_slips/' . $storedFilename;
                Storage::disk('public')->put($storedPath, file_get_contents($pdfPath));

                $slipNumber = 'SLIP-' . date('Ym', strtotime($paymentDate)) . '-' . str_pad($matchedEmployee->id, 4, '0', STR_PAD_LEFT) . '-' . rand(10, 99);

                $slipData = [
                    'slip_number' => $slipNumber,
                    'employee_id' => $matchedEmployee->id,
                    'period_month' => $periodMonth,
                    'payment_date' => $paymentDate,
                    'basic_salary' => $netSalary,
                    'allowance_position' => 0,
                    'allowance_transport' => 0,
                    'allowance_meal' => 0,
                    'overtime_pay' => 0,
                    'bonus' => 0,
                    'deduction_bpjs_tk' => 0,
                    'deduction_bpjs_kes' => 0,
                    'deduction_tax_pph21' => 0,
                    'deduction_absence' => 0,
                    'deduction_other' => 0,
                    'total_earnings' => $netSalary,
                    'total_deductions' => 0,
                    'net_salary' => $netSalary,
                    'status' => 'paid',
                    'payment_method' => $request->input('payment_method', 'Bank Transfer'),
                    'notes' => 'Diterbitkan otomatis dari arsip: ' . $originalName,
                    'created_by' => $authUser->id,
                ];

                if ($hasPdfColumn) {
                    $slipData['pdf_path'] = $storedPath;
                }
                if ($hasOriginalFilenameColumn) {
                    $slipData['original_filename'] = $originalName;
                }

                SalarySlip::create($slipData);
                $successCount++;
            }

            $this->cleanupDirectory($tempDir);

            if ($successCount === 0) {
                $unmatchedList = implode(', ', array_slice($unmatchedFiles, 0, 5));
                return back()->with('error', "Gagal menerbitkan slip gaji: Tidak ada file PDF yang cocok dengan data karyawan (NIP/Nama/Email). Contoh file: {$unmatchedList}");
            }

            $msg = "Berhasil menerbitkan {$successCount} slip gaji untuk periode {$periodMonth}.";
            if (!empty($unmatchedFiles)) {
                $unmatchedCount = count($unmatchedFiles);
                $unmatchedSample = implode(', ', array_slice($unmatchedFiles, 0, 3));
                $msg .= " ({$unmatchedCount} file tidak dapat dicocokkan otomatis: {$unmatchedSample}" . ($unmatchedCount > 3 ? ' dll.' : '') . ")";
            }

            return redirect()->route('finance.index', ['tab' => 'slips'])->with('success', $msg);

        } catch (\Exception $e) {
            $this->cleanupDirectory($tempDir);
            Log::error("Zip/PDF Slip Upload Error: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memproses slip gaji: ' . $e->getMessage());
        }
    }

    /**
     * Download atau Tampilkan File PDF Asli Slip Gaji
     */
    public function downloadSalarySlipPdf($id)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;
        $slip = SalarySlip::findOrFail($id);

        // Otorisasi: Karyawan biasa hanya bisa mengunduh slip miliknya sendiri
        if (!$authUser->canApproveReimbursements() && $currentUser && $slip->employee_id !== $currentUser->id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengunduh slip gaji karyawan lain.');
        }

        if (empty($slip->pdf_path) || !Storage::disk('public')->exists($slip->pdf_path)) {
            return back()->with('error', 'File dokumen PDF asli tidak ditemukan pada penyimpanan server.');
        }

        $fullPath = storage_path('app/public/' . $slip->pdf_path);
        $downloadName = $slip->original_filename ?: ($slip->slip_number . '.pdf');

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
        ]);
    }

    /**
     * Pencocokan Cerdas Karyawan berdasarkan NIP, Email, atau Nama
     */
    protected function matchEmployeeForPdf(string $filename, string $text, $allEmployees): ?Employee
    {
        $cleanFilename = strtolower(pathinfo($filename, PATHINFO_FILENAME));
        $cleanText = strtolower($text);

        // 1. Cocokkan berdasarkan NIP (contoh: NEX-00001, 00001, dsb.)
        foreach ($allEmployees as $emp) {
            if ($emp->nip) {
                $cleanNip = strtolower($emp->nip);
                $numericPart = preg_replace('/\D/', '', $emp->nip);

                // Cek NIP di nama file
                if (str_contains($cleanFilename, $cleanNip)) {
                    return $emp;
                }
                if ($numericPart && strlen($numericPart) >= 3 && str_contains($cleanFilename, $numericPart)) {
                    return $emp;
                }
                // Cek NIP di teks PDF
                if (str_contains($cleanText, $cleanNip)) {
                    return $emp;
                }
            }
        }

        // 2. Cocokkan berdasarkan Email
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            $detectedEmail = strtolower($matches[0]);
            foreach ($allEmployees as $emp) {
                if (strtolower($emp->email ?? '') === $detectedEmail) {
                    return $emp;
                }
                if ($emp->user && strtolower($emp->user->email ?? '') === $detectedEmail) {
                    return $emp;
                }
            }
        }

        // 3. Cocokkan berdasarkan Nama Lengkap di Nama File
        foreach ($allEmployees as $emp) {
            $empName = strtolower(trim($emp->name));
            if (empty($empName)) continue;

            $slugName = Str::slug($empName, '_');

            if (str_contains($cleanFilename, $slugName) || str_contains($cleanFilename, str_replace('_', '', $slugName))) {
                return $emp;
            }
            if (str_contains($cleanFilename, strtolower(str_replace(' ', '', $empName)))) {
                return $emp;
            }
        }

        // 4. Cocokkan berdasarkan Nama Lengkap di Teks PDF
        foreach ($allEmployees as $emp) {
            $empName = strtolower(trim($emp->name));
            if (strlen($empName) > 3 && str_contains($cleanText, $empName)) {
                return $emp;
            }
        }

        return null;
    }

    /**
     * Ekstraksi Nominal Gaji Bersih dari Teks PDF
     */
    protected function parseSalaryFromText(string $text): float
    {
        if (empty($text)) return 0;

        $patterns = [
            '/(?:Take\s*Home\s*Pay|Gaji\s*Bersih|Total\s*Diterima|THP)\s*[:=]?\s*(?:Rp\.?|IDR)?\s*([\d\.,]+)/iu',
            '/(?:Total\s*Pendapatan|Total\s*Gaji)\s*[:=]?\s*(?:Rp\.?|IDR)?\s*([\d\.,]+)/iu',
            '/(?:Gaji\s*Pokok|Basic\s*Salary)\s*[:=]?\s*(?:Rp\.?|IDR)?\s*([\d\.,]+)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $raw = trim($m[1]);
                // Bersihkan titik ribuan Indonesia
                $clean = str_replace('.', '', $raw);
                $clean = str_replace(',', '.', $clean);
                $val = (float) $clean;
                if ($val > 10000) {
                    return $val;
                }
            }
        }

        return 0;
    }

    /**
     * Hapus direktori sementara secara rekursif
     */
    protected function cleanupDirectory(string $dir): void
    {
        if (!file_exists($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->cleanupDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        // Karyawan/manager biasa hanya bisa mengajukan untuk dirinya sendiri
        $employeeId = $authUser->canApproveReimbursements()
            ? ($request->employee_id ?? $authUser->employee_id)
            : ($authUser->employee_id ?? $request->employee_id);

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:1000',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $reimbursement = Reimbursement::create([
            'employee_id' => $employeeId,
            'title' => $request->title,
            'category' => $request->category,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        // Notifikasi ke Tim Finance & Direktur
        try {
            $applicantName = $authUser->name;
            $formattedAmount = 'Rp ' . number_format($reimbursement->amount, 0, ',', '.');
            $financeApprovers = \App\Models\User::where(function ($q) {
                $q->where('department', 'Finance')->where('role', 'manager');
            })->orWhere('role', 'direktur')->get();

            foreach ($financeApprovers as $approver) {
                if ($approver->id !== $authUser->id) {
                    $approver->notify(new \App\Notifications\GeneralNotification(
                        'Klaim Reimbursement Baru',
                        "{$applicantName} mengajukan klaim '{$reimbursement->title}' sebesar {$formattedAmount}.",
                        route('finance.index'),
                        'payments',
                        'emerald'
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi pengajuan reimbursement: ' . $e->getMessage());
        }

        return back()->with('success', 'Pengajuan reimbursement berhasil diajukan dan menunggu verifikasi Departemen Finance.');
    }

    public function updateStatus(Request $request, $id)
    {
        $authUser = Auth::user();

        // Hanya Departemen Finance atau Direktur yang dapat approve/reject/paid klaim
        if (!$authUser->canApproveReimbursements()) {
            abort(403, 'Akses Ditolak: Hanya tim Departemen Finance atau Direktur yang berhak menyetujui klaim reimbursement.');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected,paid',
            'notes' => 'required_if:status,rejected|nullable|string|max:1000',
        ], [
            'notes.required_if' => 'Alasan penolakan wajib diisi ketika menolak klaim reimbursement.',
        ]);

        $item = Reimbursement::findOrFail($id);

        // Aturan Anti-Self-Approval: Pengaju tidak boleh menyetujui klaim diri sendiri
        $currentEmpId = $authUser->employee_id ?? ($authUser->employee?->id);
        if ($currentEmpId && (int)$item->employee_id === (int)$currentEmpId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat memproses persetujuan atas klaim reimbursement diri Anda sendiri. Otorisasi harus dilakukan oleh pihak verifikator lain.');
        }

        $item->update([
            'status' => $request->status,
            'reviewed_by' => $currentEmpId,
            'reviewed_at' => Carbon::now(),
            'notes' => $request->notes,
        ]);

        // Kirim notifikasi ke pemohon klaim
        try {
            $claimantUser = \App\Models\User::where('employee_id', $item->employee_id)->first();
            if ($claimantUser) {
                $statusMap = [
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'paid' => 'Sudah Dibayarkan (Cair)',
                ];
                $statusLabel = $statusMap[$request->status] ?? $request->status;
                $color = $request->status === 'rejected' ? 'rose' : 'emerald';
                $icon = $request->status === 'rejected' ? 'cancel' : 'check_circle';
                $reasonMsg = $request->status === 'rejected' && $request->notes ? " Alasan: {$request->notes}" : "";

                $claimantUser->notify(new \App\Notifications\GeneralNotification(
                    "Reimbursement {$statusLabel}",
                    "Klaim '{$item->title}' Anda telah {$statusLabel} oleh Finance.{$reasonMsg}",
                    route('finance.index'),
                    $icon,
                    $color
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi update status reimbursement: ' . $e->getMessage());
        }

        return back()->with('success', 'Status klaim reimbursement berhasil diubah oleh Finance menjadi ' . strtoupper($request->status) . '.');
    }
}
