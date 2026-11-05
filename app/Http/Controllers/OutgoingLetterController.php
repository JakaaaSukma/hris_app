<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OutgoingLetter;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OutgoingLetterController extends Controller
{
    /**
     * Tampilkan Halaman Utama Pengelolaan Surat Keluar & Agenda Persuratan
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canManageExecutiveLetters()) {
            abort(403, 'Akses Ditolak: Modul tata persuratan kantor dan penomoran surat resmi hanya diperuntukkan bagi Sekretaris Direktur dan Direktur Utama.');
        }

        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');
        $urgency = $request->query('urgency');
        $year = $request->query('year', date('Y'));

        $query = OutgoingLetter::with('creator')->orderBy('sequence_number', 'desc')->orderBy('letter_date', 'desc');

        if ($year && $year !== 'all') {
            $query->where('year', $year);
        }

        if ($category) {
            $query->where('letter_category', $category);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($urgency) {
            $query->where('urgency', $urgency);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('letter_number', 'like', "%{$search}%")
                  ->orWhere('recipient', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('regarding_summary', 'like', "%{$search}%")
                  ->orWhere('signer_name', 'like', "%{$search}%");
            });
        }

        $letters = $query->paginate(15)->appends($request->query());

        // Metrik Statistik Persuratan
        $currentYear = (int) date('Y');
        $totalLettersYear = OutgoingLetter::where('year', $currentYear)->count();
        $totalSent = OutgoingLetter::where('year', $currentYear)->whereIn('status', ['sent', 'archived'])->count();
        $totalPending = OutgoingLetter::where('year', $currentYear)->whereIn('status', ['draft', 'reserved'])->count();
        $totalThisMonth = OutgoingLetter::where('year', $currentYear)
            ->whereMonth('letter_date', date('m'))
            ->count();

        // Data opsi untuk form
        $categories = OutgoingLetter::categoryOptions();
        $organizations = OutgoingLetter::organizationOptions();
        $nextSequence = OutgoingLetter::getNextSequence($currentYear);
        $currentMonthRoman = OutgoingLetter::convertMonthToRoman((int) date('n'));
        $defaultLetterNumber = OutgoingLetter::formatLetterNumber($nextSequence, 'SKet', 'LPK-GYOKAI', $currentMonthRoman, $currentYear);

        // Daftar tahun yang ada di database untuk filter
        $availableYears = OutgoingLetter::select('year')->distinct()->orderBy('year', 'desc')->pluck('year')->toArray();
        if (!in_array($currentYear, $availableYears)) {
            array_unshift($availableYears, $currentYear);
        }

        $director = User::getDirector();
        $directorName = User::getDirectorName('Direktur Utama');

        return view('secretary.letters.index', compact(
            'letters',
            'authUser',
            'director',
            'directorName',
            'search',
            'category',
            'status',
            'urgency',
            'year',
            'totalLettersYear',
            'totalSent',
            'totalPending',
            'totalThisMonth',
            'categories',
            'organizations',
            'nextSequence',
            'currentMonthRoman',
            'defaultLetterNumber',
            'availableYears'
        ));
    }

    /**
     * Endpoint API JSON untuk Mendapatkan Nomor Surat Otomatis secara Real-time
     */
    public function getNextNumber(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canManageExecutiveLetters()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $category = $request->input('category', 'SKet');
        $orgCode = $request->input('organization_code', 'LPK-GYOKAI');
        $letterDateStr = $request->input('letter_date', date('Y-m-d'));
        
        $date = Carbon::parse($letterDateStr);
        $year = (int) $date->year;
        $monthRoman = OutgoingLetter::convertMonthToRoman((int) $date->month);

        $customSeq = $request->input('sequence_number');
        if ($customSeq && is_numeric($customSeq)) {
            $seq = (int) $customSeq;
        } else {
            $seq = OutgoingLetter::getNextSequence($year);
        }

        $formattedNumber = OutgoingLetter::formatLetterNumber($seq, $category, $orgCode, $monthRoman, $year);
        $exists = OutgoingLetter::where('letter_number', $formattedNumber)->exists();

        return response()->json([
            'success' => true,
            'letter_number' => $formattedNumber,
            'sequence_number' => $seq,
            'month_roman' => $monthRoman,
            'year' => $year,
            'exists' => $exists,
        ]);
    }

    /**
     * Simpan Surat Keluar & Daftarkan Nomor Surat Resmi Baru
     */
    public function store(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canManageExecutiveLetters()) {
            abort(403, 'Akses Ditolak.');
        }

        $dateInput = $request->input('letter_date', now()->format('Y-m-d'));
        $date = Carbon::parse($dateInput);
        $year = (int) $date->year;
        $monthRoman = OutgoingLetter::convertMonthToRoman((int) $date->month);

        if (!$request->filled('sequence_number')) {
            $request->merge(['sequence_number' => OutgoingLetter::getNextSequence($year)]);
        }

        if (!$request->filled('letter_number')) {
            $cat = $request->input('letter_category', 'SK');
            $org = $request->input('organization_code', 'DIR-GYOKAI');
            $request->merge(['letter_number' => OutgoingLetter::formatLetterNumber($request->sequence_number, $cat, $org, $monthRoman, $year)]);
        }

        if (!$request->filled('signer_name')) {
            $request->merge(['signer_name' => User::getDirectorName('Direktur Utama')]);
        }
        if (!$request->filled('signer_title')) {
            $request->merge(['signer_title' => 'Direktur Utama']);
        }

        $validated = $request->validate([
            'letter_number' => 'required|string|max:100|unique:outgoing_letters,letter_number',
            'sequence_number' => 'required|integer|min:1',
            'letter_category' => 'required|string|max:50',
            'organization_code' => 'required|string|max:50',
            'letter_date' => 'required|date',
            'recipient' => 'required|string|max:255',
            'recipient_address' => 'nullable|string|max:500',
            'subject' => 'required|string|max:255',
            'regarding_summary' => 'nullable|string',
            'urgency' => 'required|in:biasa,penting,segera,rahasia',
            'status' => 'required|in:draft,reserved,signed,sent,archived',
            'signer_name' => 'required|string|max:150',
            'signer_title' => 'required|string|max:150',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'attachment_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:500',
        ], [
            'letter_number.unique' => 'Nomor surat ini sudah pernah digunakan atau terdaftar di sistem. Gunakan nomor urut lain.',
            'file.max' => 'Ukuran berkas surat maksimal 10 MB.',
            'attachment_file.max' => 'Ukuran berkas surat maksimal 10 MB.',
        ]);

        $filePath = null;
        $uploadedFile = $request->file('file') ?? $request->file('attachment_file');
        if ($uploadedFile) {
            $fileName = 'letter_' . $year . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $uploadedFile->getClientOriginalName());
            $filePath = $uploadedFile->storeAs('outgoing_letters/' . $year, $fileName, 'public');
        }

        $signedAt = null;
        $sentAt = null;
        if (in_array($validated['status'], ['signed', 'sent', 'archived'])) {
            $signedAt = now();
        }
        if (in_array($validated['status'], ['sent', 'archived'])) {
            $sentAt = now();
        }

        $letter = OutgoingLetter::create([
            'letter_number' => $validated['letter_number'],
            'sequence_number' => $validated['sequence_number'],
            'letter_category' => $validated['letter_category'],
            'organization_code' => $validated['organization_code'],
            'month_roman' => $monthRoman,
            'year' => $year,
            'letter_date' => $validated['letter_date'],
            'recipient' => $validated['recipient'],
            'recipient_address' => $validated['recipient_address'] ?? null,
            'subject' => $validated['subject'],
            'regarding_summary' => $validated['regarding_summary'] ?? null,
            'urgency' => $validated['urgency'],
            'status' => $validated['status'],
            'signer_name' => $validated['signer_name'],
            'signer_title' => $validated['signer_title'],
            'file_path' => $filePath,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $authUser->id,
            'signed_at' => $signedAt,
            'sent_at' => $sentAt,
        ]);

        return redirect()->route('secretary.letters.index')->with('success', "Nomor surat resmi {$letter->letter_number} berhasil didaftarkan ke buku agenda surat keluar.");
    }

    /**
     * Perbarui Data Surat Keluar & Status Pengiriman
     */
    public function update(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser->canManageExecutiveLetters()) {
            abort(403, 'Akses Ditolak.');
        }

        $letter = OutgoingLetter::findOrFail($id);

        if (!$request->filled('letter_number')) {
            $request->merge(['letter_number' => $letter->letter_number]);
        }
        if (!$request->filled('letter_date')) {
            $request->merge(['letter_date' => $letter->letter_date ? $letter->letter_date->format('Y-m-d') : now()->format('Y-m-d')]);
        }
        if (!$request->filled('recipient')) {
            $request->merge(['recipient' => $letter->recipient]);
        }
        if (!$request->filled('subject')) {
            $request->merge(['subject' => $letter->subject]);
        }
        if (!$request->filled('urgency')) {
            $request->merge(['urgency' => $letter->urgency]);
        }
        if (!$request->filled('signer_name')) {
            $request->merge(['signer_name' => $letter->signer_name]);
        }
        if (!$request->filled('signer_title')) {
            $request->merge(['signer_title' => $letter->signer_title]);
        }

        $validated = $request->validate([
            'letter_number' => 'required|string|max:100|unique:outgoing_letters,letter_number,' . $letter->id,
            'letter_date' => 'required|date',
            'recipient' => 'required|string|max:255',
            'recipient_address' => 'nullable|string|max:500',
            'subject' => 'required|string|max:255',
            'regarding_summary' => 'nullable|string',
            'urgency' => 'required|in:biasa,penting,segera,rahasia',
            'status' => 'required|in:draft,reserved,signed,sent,archived',
            'signer_name' => 'required|string|max:150',
            'signer_title' => 'required|string|max:150',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'attachment_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string|max:500',
            'sent_at' => 'nullable|date',
        ]);

        $filePath = $letter->file_path;
        $uploadedFile = $request->file('file') ?? $request->file('attachment_file');
        if ($uploadedFile) {
            // Hapus file lama jika ada
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $year = Carbon::parse($validated['letter_date'])->year;
            $fileName = 'letter_' . $year . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $uploadedFile->getClientOriginalName());
            $filePath = $uploadedFile->storeAs('outgoing_letters/' . $year, $fileName, 'public');
        }

        $signedAt = $letter->signed_at;
        $sentAt = $request->filled('sent_at') ? Carbon::parse($request->sent_at) : $letter->sent_at;

        if (in_array($validated['status'], ['signed', 'sent', 'archived']) && !$signedAt) {
            $signedAt = now();
        }
        if (in_array($validated['status'], ['sent', 'archived']) && !$sentAt) {
            $sentAt = now();
        }

        $letter->update([
            'letter_number' => $validated['letter_number'],
            'letter_date' => $validated['letter_date'],
            'recipient' => $validated['recipient'],
            'recipient_address' => $validated['recipient_address'] ?? $letter->recipient_address,
            'subject' => $validated['subject'],
            'regarding_summary' => $validated['regarding_summary'] ?? $letter->regarding_summary,
            'urgency' => $validated['urgency'],
            'status' => $validated['status'],
            'signer_name' => $validated['signer_name'],
            'signer_title' => $validated['signer_title'],
            'file_path' => $filePath,
            'notes' => $validated['notes'] ?? $letter->notes,
            'signed_at' => $signedAt,
            'sent_at' => $sentAt,
        ]);

        return redirect()->route('secretary.letters.index')->with('success', "Data surat {$letter->letter_number} berhasil diperbarui.");
    }

    /**
     * Hapus Data Surat Keluar dari Buku Agenda
     */
    public function destroy($id)
    {
        $authUser = Auth::user();
        if (!$authUser->canManageExecutiveLetters()) {
            abort(403, 'Akses Ditolak.');
        }

        $letter = OutgoingLetter::findOrFail($id);
        $letterNum = $letter->letter_number;

        if ($letter->file_path && Storage::disk('public')->exists($letter->file_path)) {
            Storage::disk('public')->delete($letter->file_path);
        }

        $letter->delete();

        return redirect()->route('secretary.letters.index')->with('success', "Surat resmi {$letterNum} berhasil dihapus dari buku agenda.");
    }

    /**
     * Tampilkan / Unduh File Scan Dokumen Surat secara Aman (Bypass cPanel Symlink)
     */
    public function downloadFile($id)
    {
        $authUser = Auth::user();
        if (!$authUser) {
            abort(403, 'Akses Ditolak.');
        }

        $letter = OutgoingLetter::findOrFail($id);

        if (!$letter->file_path) {
            abort(404, 'Berkas fisik surat belum diunggah.');
        }

        $disk = Storage::disk('public');
        if ($disk->exists($letter->file_path)) {
            $content = $disk->get($letter->file_path);
            $mime = $disk->mimeType($letter->file_path) ?: 'application/pdf';
            return response($content, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($letter->file_path) . '"',
            ]);
        }

        $fullPath = storage_path('app/public/' . $letter->file_path);
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            $mime = mime_content_type($fullPath) ?: 'application/pdf';
            return response($content, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            ]);
        }

        abort(404, 'Berkas surat tidak ditemukan di storage server.');
    }
}
