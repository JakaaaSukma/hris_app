<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use App\Models\PayslipLog;
use App\Models\PayslipSendSession;
use App\Models\SmtpMailer;
use Smalot\PdfParser\Parser;
use App\Traits\ValidatesFileUpload;

class FinanceDispatchController extends Controller
{
    use ValidatesFileUpload;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || (!$user->isFinance() && !$user->isDirektur())) {
                abort(403, 'Akses Ditolak: Halaman Pengiriman Slip Gaji (Email Dispatch) ini hanya dapat diakses oleh Akun Finance atau Direktur.');
            }
            return $next($request);
        });
    }

    /**
     * Halaman Utama Pengiriman Slip Gaji & Riwayat Sesi
     */
    public function index(Request $request)
    {
        $this->ensureSmtpMailersAvailable();

        $query = PayslipLog::with(['sendSession', 'smtpMailer']);
        $smtpMailerOptions = SmtpMailer::query()
            ->orderBy('priority')
            ->get(['id', 'mailer_name', 'from_address']);

        // Filter Tanggal
        if ($request->filled('filter_date')) {
            $query->whereDate('created_at', $request->filter_date);
        }

        // Filter Status
        if ($request->filled('filter_status') && $request->filter_status !== 'all') {
            $query->where('status', $request->filter_status);
        }

        // Filter Mailer
        if ($request->filled('filter_mailer') && $request->filter_mailer !== 'all') {
            $query->where('smtp_mailer_id', $request->filter_mailer);
        }

        $sortBy = $request->input('sort_by', 'time_desc');
        if ($sortBy === 'time_asc') {
            $query->orderBy('created_at', 'asc')->orderBy('filename', 'asc');
        } else {
            $query->orderBy('created_at', 'desc')->orderBy('filename', 'asc');
        }

        $logs = $query->paginate(50, ['*'], 'logs_page')->appends($request->all());

        // Riwayat Sesi Pengiriman (Dilihat bersama oleh Finance Gyokai & HRIS)
        $sendSessions = PayslipSendSession::orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'sessions_page')
            ->appends($request->all());

        return view('departments.finance.dispatch.index', compact('logs', 'sendSessions', 'smtpMailerOptions'));
    }

    /**
     * Upload File Slip Gaji (Single PDF atau ZIP)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'payslip_file' => 'required|file|mimes:pdf,zip|max:102400', // Max 100MB
        ]);

        $sessionId = session()->getId();
        $timestamp = time();
        $tempDirName = "payslips_{$timestamp}_{$sessionId}";
        $storagePath = storage_path('app/temp/' . $tempDirName);

        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        try {
            if ($request->hasFile('payslip_file')) {
                $file = $request->file('payslip_file');

                $securityCheck = $this->validateUploadSecurity($file, 'any', 100);
                if (!$securityCheck['safe']) {
                    return back()->with('error', $securityCheck['message']);
                }

                $extension = strtolower($file->getClientOriginalExtension());

                if ($extension === 'pdf') {
                    $filename = $file->getClientOriginalName();
                    $file->move($storagePath, $filename);
                } elseif ($extension === 'zip') {
                    $zipPath = $file->storeAs('temp', 'upload.zip');
                    $zip = new \ZipArchive;
                    if ($zip->open(storage_path('app/' . $zipPath)) === TRUE) {
                        $zip->extractTo($storagePath);
                        $zip->close();
                        Storage::delete($zipPath);
                    } else {
                        return back()->with('error', 'Gagal mengekstrak file ZIP.');
                    }
                }

                session(['payslip_temp_dir' => $storagePath]);

                $parser = new Parser();
                $files = [];
                $emailRegex = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

                $pdfFiles = glob($storagePath . '/*.pdf');

                foreach ($pdfFiles as $pdfFile) {
                    $filename = basename($pdfFile);

                    try {
                        $pdf = $parser->parseFile($pdfFile);
                        $text = $pdf->getText();
                        $period = $this->extractPayslipPeriodFromText($text);

                        $email = '';
                        if (preg_match($emailRegex, $text, $matches)) {
                            $email = $matches[0];
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to parse PDF $filename: " . $e->getMessage());
                        $email = "";
                        $period = null;
                    }

                    $namePart = pathinfo($filename, PATHINFO_FILENAME);
                    $name = preg_replace('/^[\d\W_]+/', '', $namePart);
                    $name = trim($name) ?: "Karyawan";

                    $files[] = [
                        "filename" => $filename,
                        "filepath" => $pdfFile,
                        "email" => $email,
                        "period" => $period,
                        "name" => $name,
                        "status" => $email ? "valid" : "invalid",
                        "message" => $email ? "Email terdeteksi" : "Email tidak ditemukan di PDF"
                    ];
                }

                if (empty($files)) {
                    return back()->with('error', 'Tidak ada file PDF ditemukan dalam upload.');
                }

                return view('departments.finance.dispatch.preview', compact('files'));
            }
        } catch (\Exception $e) {
            Log::error('Payslip Preview Error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
        }

        return back()->with('error', 'Gagal mengupload file.');
    }

    /**
     * Memulai Sesi Pengiriman Batch
     */
    public function startSending(Request $request)
    {
        $tempDir = session('payslip_temp_dir');
        if (!$tempDir || !file_exists($tempDir)) {
            return redirect()->route('finance.dispatch.index')->with('error', 'Sesi kadaluarsa atau file tidak ditemukan. Silakan upload ulang.');
        }

        $globalCc = $request->input('global_cc');
        $globalBcc = $request->input('global_bcc');
        $incoming = $request->input('selected_files', []);
        $queue = [];

        foreach ($incoming as $fileData) {
            if (isset($fileData['filename']) && !empty($fileData['email'])) {
                $queue[] = [
                    'filename' => $fileData['filename'],
                    'email' => $fileData['email'],
                    'cc' => $globalCc,
                    'bcc' => $globalBcc,
                    'period' => $fileData['period'] ?? null,
                ];
            }
        }

        if (empty($queue)) {
            return back()->with('warning', 'Tidak ada antrean pengiriman valid yang dipilih.');
        }

        $sendSession = PayslipSendSession::create([
            'user_id' => Auth::id(),
            'session_code' => 'PS-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(5)),
            'total_files' => count($queue),
            'success_count' => 0,
            'failed_count' => 0,
            'status' => 'processing',
            'temp_dir_path' => $tempDir,
            'pending_queue' => array_values($queue),
            'started_at' => now(),
        ]);

        session([
            'sending_queue' => array_values($queue),
            'sending_total' => count($queue),
            'sending_stats' => ['success' => 0, 'failed' => 0],
            'current_payslip_send_session_id' => $sendSession->id,
            'last_smtp_mailer_id' => null,
        ]);

        return redirect()->route('finance.dispatch.sending', ['send_session_id' => $sendSession->id]);
    }

    /**
     * Tampilan Progress Bar Pengiriman Real-Time
     */
    public function showSending(Request $request)
    {
        $requestedSendSessionId = (int) $request->query('send_session_id');
        $tempDir = session('payslip_temp_dir');
        $queue = session('sending_queue', []);
        $total = session('sending_total');
        $sendSessionId = session('current_payslip_send_session_id');

        if ($requestedSendSessionId && $requestedSendSessionId !== (int) $sendSessionId) {
            $sendSession = PayslipSendSession::find($requestedSendSessionId);
            if ($sendSession) {
                $this->restorePayslipSendingSessionState($sendSession);
                $tempDir = session('payslip_temp_dir');
                $queue = session('sending_queue', []);
                $total = session('sending_total');
                $sendSessionId = session('current_payslip_send_session_id');
            }
        }

        if (!$tempDir || !file_exists($tempDir) || $total === null) {
            $sendSession = $requestedSendSessionId ? PayslipSendSession::find($requestedSendSessionId) : null;
            if ($sendSession && $sendSession->status === 'processing') {
                return view('departments.finance.dispatch.sending', array_merge(
                    $this->getPayslipStoredProgressData($sendSession),
                    [
                        'canCancelSending' => !empty($sendSession->pending_queue),
                        'activeSendSessionId' => $sendSession->id,
                        'reportUrl' => route('finance.dispatch.report', $sendSession),
                        'autoProcessEnabled' => false,
                        'sendingUnavailableReason' => 'Sesi proses pengiriman dapat dibuka kembali, namun state browser terputus. Anda dapat membatalkan atau mengecek laporannya.',
                    ]
                ));
            }
            return redirect()->route('finance.dispatch.index')->with('error', 'Sesi pengiriman tidak ditemukan. Silakan upload ulang berkas.');
        }

        return view('departments.finance.dispatch.sending', array_merge(
            $this->getPayslipSendingProgressData($queue, $total),
            [
                'canCancelSending' => !empty($queue) && !empty($sendSessionId),
                'activeSendSessionId' => $sendSessionId,
                'reportUrl' => $sendSessionId ? route('finance.dispatch.report', $sendSessionId) : route('finance.dispatch.index'),
                'autoProcessEnabled' => true,
                'sendingUnavailableReason' => null,
            ]
        ));
    }

    /**
     * Eksekusi Batch Pengiriman Email (AJAX / Loop)
     */
    public function sendBatch(Request $request)
    {
        try {
            set_time_limit(3600);
            ini_set('memory_limit', '512M');

            $tempDir = session('payslip_temp_dir');
            $sendSessionId = session('current_payslip_send_session_id');
            $requestedSendSessionId = (int) $request->input('send_session_id');

            if (($requestedSendSessionId && $requestedSendSessionId !== (int) $sendSessionId) || !$tempDir || !file_exists($tempDir) || !$sendSessionId) {
                $sendSession = $requestedSendSessionId ? PayslipSendSession::find($requestedSendSessionId) : null;
                if ($sendSession) {
                    $this->restorePayslipSendingSessionState($sendSession);
                    $tempDir = session('payslip_temp_dir');
                    $sendSessionId = session('current_payslip_send_session_id');
                }
            }

            if (!$tempDir || !file_exists($tempDir) || !$sendSessionId) {
                return response()->json([
                    'message' => 'Sesi kadaluarsa atau file tidak ditemukan.',
                    'redirectUrl' => route('finance.dispatch.index'),
                ], 422);
            }

            $queue = session('sending_queue', []);
            $total = session('sending_total', 0);

            if (empty($queue)) {
                if ($total > 0) {
                    $this->updatePayslipSendSessionSummary($sendSessionId, session('sending_stats', ['success' => 0, 'failed' => 0]), 0);
                    $this->clearPayslipSendingSession($tempDir, $sendSessionId);
                    return response()->json([
                        'message' => 'Antrean pengiriman sudah selesai.',
                        'redirectUrl' => route('finance.dispatch.report', $sendSessionId),
                        'completed' => true
                    ], 200);
                }
                return response()->json([
                    'message' => 'Tidak ada antrean pengiriman ditemukan.',
                    'redirectUrl' => route('finance.dispatch.index'),
                ], 422);
            }

            $batchSize = 10;
            $maxBatchExecutionSeconds = 25;
            $currentBatch = array_slice($queue, 0, $batchSize);
            $remainingQueue = array_slice($queue, $batchSize);

            $stats = session('sending_stats', ['success' => 0, 'failed' => 0]);
            $batchStartedAt = microtime(true);
            $unprocessedCurrentBatch = [];
            $dailyLimitReached = false;
            $dailyLimitMessage = null;

            foreach ($currentBatch as $index => $file) {
                if ($index > 0 && (microtime(true) - $batchStartedAt) >= $maxBatchExecutionSeconds) {
                    $unprocessedCurrentBatch = array_slice($currentBatch, $index);
                    break;
                }

                $filename = $file['filename'];
                $email = $file['email'];
                $cc = $file['cc'] ?? null;
                $bcc = $file['bcc'] ?? null;
                $filePath = $tempDir . '/' . $filename;
                $period = trim($file['period'] ?? '');

                if (!file_exists($filePath)) {
                    $stats['failed']++;
                    $this->createPayslipLogEntry([
                        'user_id' => Auth::id(),
                        'payslip_send_session_id' => $sendSessionId,
                        'filename' => $filename,
                        'recipient_email' => $email ?: 'N/A',
                        'recipient_name' => null,
                        'period' => $period ?: null,
                        'status' => 'failed',
                        'error_message' => 'Berkas PDF lampiran tidak ditemukan di storage server.',
                    ]);
                    continue;
                }

                try {
                    $namePart = pathinfo($filename, PATHINFO_FILENAME);
                    $name = preg_replace('/^[\d\W_]+/', '', $namePart);
                    $name = trim($name) ?: "Karyawan";
                    $period = $period ?: ($this->extractPayslipPeriodFromFile($filePath) ?: date('F Y'));

                    $fileContent = @file_get_contents($filePath);
                    if ($fileContent === false) {
                        throw new \RuntimeException("Tidak dapat membaca berkas lampiran: $filename");
                    }

                    $mailSendResult = $this->sendPayslipViaRotatingMailers(
                        $email,
                        $cc,
                        $bcc,
                        $name,
                        $period,
                        $fileContent,
                        $filename
                    );

                    if (!$mailSendResult['success']) {
                        throw new \RuntimeException($mailSendResult['message'] ?? 'Pengiriman email gagal diproses.');
                    }

                    $stats['success']++;

                    $this->createPayslipLogEntry([
                        'user_id' => Auth::id(),
                        'payslip_send_session_id' => $sendSessionId,
                        'smtp_mailer_id' => $mailSendResult['smtp_mailer_id'] ?? null,
                        'smtp_mailer_name' => $mailSendResult['mailer_name'] ?? null,
                        'smtp_mailer_address' => $mailSendResult['mailer_address'] ?? null,
                        'filename' => $filename,
                        'recipient_email' => $email,
                        'recipient_name' => $name,
                        'period' => $period,
                        'status' => 'success',
                    ]);
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    if ($this->isAllMailersUnavailableMessage($errorMessage)) {
                        $dailyLimitReached = true;
                        $dailyLimitMessage = $errorMessage;
                    }

                    $stats['failed']++;
                    $this->createPayslipLogEntry([
                        'user_id' => Auth::id(),
                        'payslip_send_session_id' => $sendSessionId,
                        'smtp_mailer_id' => $mailSendResult['smtp_mailer_id'] ?? null,
                        'smtp_mailer_name' => $mailSendResult['mailer_name'] ?? null,
                        'smtp_mailer_address' => $mailSendResult['mailer_address'] ?? null,
                        'filename' => $filename,
                        'recipient_email' => $email,
                        'recipient_name' => $name ?? null,
                        'period' => $period ?? null,
                        'status' => 'failed',
                        'error_message' => $errorMessage,
                    ]);

                    if ($dailyLimitReached) {
                        $unprocessedCurrentBatch = array_slice($currentBatch, $index + 1);
                        break;
                    }
                }
            }

            $remainingQueue = array_values(array_merge($unprocessedCurrentBatch, $remainingQueue));

            if ($dailyLimitReached && !empty($remainingQueue)) {
                foreach ($remainingQueue as $pendingFile) {
                    $stats['failed']++;
                    $this->createPayslipLogEntry([
                        'user_id' => Auth::id(),
                        'payslip_send_session_id' => $sendSessionId,
                        'filename' => $pendingFile['filename'] ?? 'N/A',
                        'recipient_email' => $pendingFile['email'] ?? 'N/A',
                        'recipient_name' => null,
                        'period' => !empty($pendingFile['period']) ? trim($pendingFile['period']) : null,
                        'status' => 'failed',
                        'error_message' => $dailyLimitMessage ?: 'Semua akun SMTP aktif sedang cooldown/mencapai limit harian.',
                    ]);
                }
                $remainingQueue = [];
            }

            session([
                'sending_queue' => $remainingQueue,
                'sending_stats' => $stats,
            ]);

            $this->syncPayslipSendSessionState($sendSessionId, $tempDir, $remainingQueue);
            $this->updatePayslipSendSessionSummary($sendSessionId, $stats, count($remainingQueue));

            $processed = $stats['success'] + $stats['failed'];
            $remaining = count($remainingQueue);
            $total = $total ?: ($processed + $remaining);
            $progressData = $this->getPayslipSendingProgressData($remainingQueue, $total);

            if ($remaining > 0) {
                return response()->json(array_merge($progressData, [
                    'completed' => false,
                    'message' => "Batch selesai. {$processed} dari {$total} email telah diproses.",
                ]));
            }

            $this->clearPayslipSendingSession($tempDir, $sendSessionId);

            return response()->json([
                'processed' => $processed,
                'remaining' => 0,
                'total' => $total,
                'success' => $stats['success'],
                'failed' => $stats['failed'],
                'progressPercent' => 100,
                'nextBatchCount' => 0,
                'completed' => true,
                'redirectUrl' => route('finance.dispatch.report', $sendSessionId),
                'message' => "Pengiriman selesai. Sukses: {$stats['success']}, Gagal: {$stats['failed']}.",
            ]);
        } catch (\Throwable $e) {
            Log::error('HRIS Payslip dispatch error: ' . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Batalkan Sesi Pengiriman
     */
    public function cancelSending(Request $request)
    {
        $tempDir = session('payslip_temp_dir');
        $sendSessionId = session('current_payslip_send_session_id');
        $requestedSessionId = (int) $request->input('send_session_id');
        $targetSendSessionId = $requestedSessionId ?: (int) $sendSessionId;

        $sendSession = PayslipSendSession::find($targetSendSessionId);
        if (!$sendSession) {
            $this->clearPayslipSendingSession($tempDir);
            return redirect()->route('finance.dispatch.index')->with('warning', 'Sesi tidak ditemukan.');
        }

        $queue = session('sending_queue', $sendSession->pending_queue ?? []);
        $stats = session('sending_stats', [
            'success' => $sendSession->success_count,
            'failed' => $sendSession->failed_count,
        ]);

        foreach ($queue as $pendingFile) {
            $stats['failed']++;
            $this->createPayslipLogEntry([
                'user_id' => Auth::id(),
                'payslip_send_session_id' => $sendSession->id,
                'filename' => $pendingFile['filename'] ?? 'N/A',
                'recipient_email' => $pendingFile['email'] ?? 'N/A',
                'recipient_name' => $this->getPayslipRecipientNameFromFilename($pendingFile['filename'] ?? null),
                'period' => !empty($pendingFile['period']) ? trim($pendingFile['period']) : null,
                'status' => 'failed',
                'error_message' => 'Pengiriman dibatalkan oleh pengguna Finance.',
            ]);
        }

        $sendSession->update([
            'success_count' => $stats['success'] ?? 0,
            'failed_count' => $stats['failed'] ?? 0,
            'status' => 'cancelled',
            'pending_queue' => null,
            'temp_dir_path' => null,
            'finished_at' => now(),
        ]);

        $this->clearPayslipSendingSession($tempDir, $sendSession->id);

        return redirect()->route('finance.dispatch.report', $sendSession)->with('warning', 'Proses pengiriman slip gaji dibatalkan.');
    }

    /**
     * Laporan Detail Sesi Pengiriman
     */
    public function report(Request $request, PayslipSendSession $sendSession)
    {
        $logs = $sendSession->logs()->with('smtpMailer')->paginate(100);
        return view('departments.finance.dispatch.report', compact('sendSession', 'logs'));
    }

    // -------------------------------------------------------------
    // Helper Multi-SMTP Mailer & Rotating Mechanism
    // -------------------------------------------------------------

    private function sendPayslipViaRotatingMailers($email, $cc, $bcc, $name, $period, $fileContent, $filename)
    {
        $attemptedMailerIds = [];
        $dailyLimitTriggered = false;

        while ($smtpMailer = $this->getNextAvailableSmtpMailer($attemptedMailerIds)) {
            try {
                $this->sendPayslipEmailUsingMailer($smtpMailer, $email, $cc, $bcc, $name, $period, $fileContent, $filename);
                $this->markSmtpMailerAsUsed($smtpMailer);
                session(['last_smtp_mailer_id' => $smtpMailer->id]);

                return [
                    'success' => true,
                    'mailer_name' => $smtpMailer->mailer_name,
                    'mailer_address' => $smtpMailer->from_address,
                    'smtp_mailer_id' => $smtpMailer->id,
                    'message' => null,
                ];
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();

                if ($this->isDailySendingLimitExceeded($errorMessage)) {
                    $dailyLimitTriggered = true;
                    $this->markSmtpMailerDailyLimitReached($smtpMailer);
                    $attemptedMailerIds[] = $smtpMailer->id;
                    Log::warning("SMTP mailer {$smtpMailer->mailer_name} reached daily limit.");
                    continue;
                }

                if ($this->isSmtpTransportIssue($errorMessage)) {
                    $this->markSmtpMailerTemporarilyUnavailable($smtpMailer);
                    $attemptedMailerIds[] = $smtpMailer->id;
                    Log::warning("SMTP mailer {$smtpMailer->mailer_name} transport issue: {$errorMessage}");
                    continue;
                }

                return [
                    'success' => false,
                    'mailer_name' => $smtpMailer->mailer_name,
                    'mailer_address' => $smtpMailer->from_address,
                    'smtp_mailer_id' => $smtpMailer->id,
                    'message' => $errorMessage,
                ];
            }
        }

        return [
            'success' => false,
            'mailer_name' => null,
            'mailer_address' => null,
            'smtp_mailer_id' => null,
            'message' => $dailyLimitTriggered
                ? 'Semua akun SMTP aktif mencapai kuota limit harian Gmail.'
                : 'Tidak ada akun SMTP aktif yang tersedia.',
        ];
    }

    private function getNextAvailableSmtpMailer(array $excludedMailerIds = [])
    {
        $this->ensureSmtpMailersAvailable();
        $this->releaseExpiredSmtpMailerCooldowns();

        $availableMailers = SmtpMailer::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('cooldown_until')->orWhere('cooldown_until', '<=', now());
            })
            ->whereColumn('daily_sent_count', '<', 'daily_limit')
            ->orderBy('priority')
            ->get()
            ->reject(fn ($mailer) => in_array($mailer->id, $excludedMailerIds, true))
            ->values();

        if ($availableMailers->isEmpty()) {
            return null;
        }

        $lastMailerId = session('last_smtp_mailer_id');
        if (!$lastMailerId) {
            return $availableMailers->first();
        }

        $lastIndex = $availableMailers->search(fn ($mailer) => $mailer->id === $lastMailerId);
        if ($lastIndex === false) {
            return $availableMailers->first();
        }

        $nextIndex = ($lastIndex + 1) % $availableMailers->count();
        return $availableMailers->get($nextIndex) ?: $availableMailers->first();
    }

    private function ensureSmtpMailersAvailable()
    {
        $this->syncSmtpMailerDailySentCounts();
    }

    private function syncSmtpMailerDailySentCounts()
    {
        $sentCounts = PayslipLog::query()
            ->selectRaw('smtp_mailer_id, COUNT(*) as total_sent')
            ->whereNotNull('smtp_mailer_id')
            ->where('status', 'success')
            ->whereDate('created_at', today())
            ->groupBy('smtp_mailer_id')
            ->pluck('total_sent', 'smtp_mailer_id');

        SmtpMailer::query()->get()->each(function ($mailer) use ($sentCounts) {
            $actualSentCount = (int) ($sentCounts[$mailer->id] ?? 0);
            if ((int) $mailer->daily_sent_count !== $actualSentCount) {
                $mailer->update(['daily_sent_count' => $actualSentCount]);
            }
        });
    }

    private function sendPayslipEmailUsingMailer(SmtpMailer $smtpMailer, $email, $cc, $bcc, $name, $period, $fileContent, $filename)
    {
        $runtimeMailerName = 'runtime_' . $smtpMailer->mailer_name;

        Config::set("mail.mailers.{$runtimeMailerName}", [
            'transport' => 'smtp',
            'host' => $smtpMailer->smtp_host,
            'port' => $smtpMailer->smtp_port,
            'encryption' => $smtpMailer->smtp_encryption,
            'username' => $smtpMailer->smtp_username,
            'password' => $smtpMailer->smtp_password,
            'timeout' => null,
            'local_domain' => config('mail.mailers.smtp.local_domain'),
        ]);

        app('mail.manager')->purge($runtimeMailerName);

        Mail::mailer($runtimeMailerName)->send([], [], function ($message) use ($smtpMailer, $email, $cc, $bcc, $name, $period, $fileContent, $filename) {
            $message->from($smtpMailer->from_address, $smtpMailer->from_name ?? 'Finance Gyokai')
                ->to($email);

            if (!empty($cc)) {
                $message->cc($cc);
            }
            if (!empty($bcc)) {
                $message->bcc($bcc);
            }

            $message->subject("Slip Gaji - $period")
                ->html("Yth. $name,<br><br>Berikut kami lampirkan dokumen slip gaji Anda untuk periode $period.<br>Mohon diperiksa kembali.<br><br>Terima kasih,<br><strong>Finance Department - PT Gyokai Indonesia</strong>")
                ->attachData($fileContent, $filename, [
                    'mime' => 'application/pdf',
                ]);
        });

        app('mail.manager')->purge($runtimeMailerName);
    }

    private function markSmtpMailerAsUsed(SmtpMailer $smtpMailer)
    {
        $smtpMailer->update([
            'daily_sent_count' => $smtpMailer->daily_sent_count + 1,
            'last_used_at' => now(),
        ]);
    }

    private function markSmtpMailerDailyLimitReached(SmtpMailer $smtpMailer)
    {
        $smtpMailer->update([
            'last_used_at' => now(),
            'last_limit_reached_at' => now(),
            'cooldown_until' => now()->addDay(),
        ]);
    }

    private function markSmtpMailerTemporarilyUnavailable(SmtpMailer $smtpMailer)
    {
        $smtpMailer->update([
            'last_used_at' => now(),
            'cooldown_until' => now()->addMinutes(30),
        ]);
    }

    private function releaseExpiredSmtpMailerCooldowns()
    {
        SmtpMailer::query()
            ->whereNotNull('cooldown_until')
            ->where('cooldown_until', '<=', now())
            ->get()
            ->each(function ($smtpMailer) {
                $updates = ['cooldown_until' => null];
                if ($smtpMailer->last_limit_reached_at) {
                    $updates['daily_sent_count'] = 0;
                    $updates['last_limit_reached_at'] = null;
                }
                $smtpMailer->update($updates);
            });
    }

    private function isDailySendingLimitExceeded($message)
    {
        $normalized = strtolower($message);
        return Str::contains($normalized, ['daily user sending limit', 'gmail sending limit', '550-5.4.5', '550 5.4.5']);
    }

    private function isSmtpTransportIssue($message)
    {
        $normalized = strtolower($message);
        return Str::contains($normalized, ['failed to authenticate', 'expected response code "235"', 'connection could not be established', 'connection timed out', 'authentication failed']);
    }

    private function isAllMailersUnavailableMessage($message)
    {
        return Str::contains($message, 'kuota limit harian Gmail') || Str::contains($message, 'Tidak ada akun SMTP');
    }

    private function clearPayslipSendingSession($tempDir = null, $sendSessionId = null)
    {
        if ($tempDir && file_exists($tempDir)) {
            $this->deleteDirectory($tempDir);
        }

        $targetSendSessionId = $sendSessionId ?: session('current_payslip_send_session_id');
        if ($targetSendSessionId) {
            PayslipSendSession::whereKey($targetSendSessionId)->update([
                'temp_dir_path' => null,
                'pending_queue' => null,
            ]);
        }

        session()->forget([
            'payslip_temp_dir',
            'sending_queue',
            'sending_stats',
            'sending_total',
            'current_payslip_send_session_id',
            'last_smtp_mailer_id'
        ]);
    }

    private function updatePayslipSendSessionSummary($sendSessionId, $stats, $remaining)
    {
        $sendSession = PayslipSendSession::find($sendSessionId);
        if (!$sendSession) return;

        $status = 'processing';
        $finishedAt = null;

        if ($remaining === 0) {
            if (($stats['success'] ?? 0) > 0 && ($stats['failed'] ?? 0) > 0) {
                $status = 'completed_with_errors';
            } elseif (($stats['failed'] ?? 0) > 0) {
                $status = 'failed';
            } else {
                $status = 'completed';
            }
            $finishedAt = now();
        }

        $sendSession->update([
            'success_count' => $stats['success'] ?? 0,
            'failed_count' => $stats['failed'] ?? 0,
            'status' => $status,
            'finished_at' => $finishedAt,
        ]);
    }

    private function syncPayslipSendSessionState($sendSessionId, $tempDir, array $queue)
    {
        if (!$sendSessionId) return;
        PayslipSendSession::whereKey($sendSessionId)->update([
            'temp_dir_path' => $tempDir,
            'pending_queue' => array_values($queue),
        ]);
    }

    private function restorePayslipSendingSessionState(PayslipSendSession $sendSession)
    {
        $tempDir = $sendSession->temp_dir_path;
        $queue = $sendSession->pending_queue ?? [];
        $processed = (int) $sendSession->success_count + (int) $sendSession->failed_count;
        $total = max((int) $sendSession->total_files, $processed + count($queue));

        if (!$tempDir || !file_exists($tempDir)) {
            return false;
        }

        session([
            'payslip_temp_dir' => $tempDir,
            'sending_queue' => array_values($queue),
            'sending_total' => $total,
            'sending_stats' => [
                'success' => (int) $sendSession->success_count,
                'failed' => (int) $sendSession->failed_count,
            ],
            'current_payslip_send_session_id' => $sendSession->id,
        ]);

        return true;
    }

    private function createPayslipLogEntry(array $data)
    {
        PayslipLog::create($data);
    }

    private function getPayslipRecipientNameFromFilename($filename)
    {
        if (!$filename) return null;
        $namePart = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/^[\d\W_]+/', '', $namePart);
        return trim($name) ?: null;
    }

    private function extractPayslipPeriodFromFile($filePath)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            return $this->extractPayslipPeriodFromText($pdf->getText());
        } catch (\Exception $e) {
            return null;
        }
    }

    private function extractPayslipPeriodFromText($text)
    {
        if (empty($text)) return null;
        $lines = preg_split('/\R+/u', $text);
        foreach ($lines as $index => $line) {
            $norm = trim(preg_replace('/\s+/u', ' ', $line));
            if ($norm === '') continue;
            if (preg_match('/^PERIODE\s*[:\-]?\s*(.+)$/iu', $norm, $m)) {
                return trim(preg_replace('/^(?:PERIODE|PERIOD)\s*[:\-]?\s*/iu', '', $m[1]));
            }
        }
        return null;
    }

    private function getPayslipSendingProgressData($queue, $total)
    {
        $stats = session('sending_stats', ['success' => 0, 'failed' => 0]);
        $processed = $stats['success'] + $stats['failed'];
        $remaining = count($queue);
        $batchSize = 10;
        $sendSessionId = session('current_payslip_send_session_id');
        $sendSession = $sendSessionId ? PayslipSendSession::find($sendSessionId) : null;

        return [
            'processed' => $processed,
            'remaining' => $remaining,
            'total' => $total,
            'success' => $stats['success'],
            'failed' => $stats['failed'],
            'progressPercent' => $total > 0 ? floor(($processed / $total) * 100) : 0,
            'nextBatchCount' => min($batchSize, $remaining),
            'sessionCode' => optional($sendSession)->session_code,
        ];
    }

    private function getPayslipStoredProgressData(PayslipSendSession $sendSession)
    {
        $processed = (int) $sendSession->success_count + (int) $sendSession->failed_count;
        $remaining = max(0, (int) $sendSession->total_files - $processed);
        $batchSize = 10;

        return [
            'processed' => $processed,
            'remaining' => $remaining,
            'total' => (int) $sendSession->total_files,
            'success' => (int) $sendSession->success_count,
            'failed' => (int) $sendSession->failed_count,
            'progressPercent' => $sendSession->total_files > 0 ? floor(($processed / $sendSession->total_files) * 100) : 0,
            'nextBatchCount' => min($batchSize, $remaining),
            'sessionCode' => $sendSession->session_code,
        ];
    }

    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }
}
