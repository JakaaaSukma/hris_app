<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientContract;
use App\Models\User;
use App\Traits\ValidatesFileUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ClientContractController extends Controller
{
    use ValidatesFileUpload;

    /**
     * Tampilkan Halaman Utama Pengelolaan & Monitoring Kontrak / MoU Klien
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser || !$authUser->canManageClientContracts()) {
            abort(403, 'Akses Ditolak: Modul pemantauan kontrak dan MoU kerjasama klien hanya diperuntukkan bagi Sekretaris Direktur dan Direktur Utama.');
        }

        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');
        $type = $request->query('type');
        $urgency = $request->query('urgency');

        $query = ClientContract::with('creator')->orderBy('end_date', 'asc');

        if ($category) {
            $query->where('client_category', $category);
        }

        if ($type) {
            $query->where('contract_type', $type);
        }

        // Filter status khusus
        if ($status) {
            if ($status === 'active') {
                $query->where('status', 'active')->where('end_date', '>=', now()->toDateString());
            } elseif ($status === 'expiring_soon') {
                $query->where('status', 'active')
                      ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()]);
            } elseif ($status === 'expired') {
                $query->where(function ($q) {
                    $q->where('status', 'expired')
                      ->orWhere(function ($sq) {
                          $sq->where('status', 'active')->where('end_date', '<', now()->toDateString());
                      });
                });
            } else {
                $query->where('status', $status);
            }
        }

        // Filter urgensi masa berlaku
        if ($urgency) {
            if ($urgency === 'critical') {
                $query->where('status', 'active')
                      ->whereBetween('end_date', [now()->toDateString(), now()->addDays(14)->toDateString()]);
            } elseif ($urgency === 'warning') {
                $query->where('status', 'active')
                      ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()]);
            } elseif ($urgency === 'expired') {
                $query->where('end_date', '<', now()->toDateString());
            }
        }

        // Filter pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('client_pic_name', 'like', "%{$search}%")
                  ->orWhere('client_pic_email', 'like', "%{$search}%");
            });
        }

        $contracts = $query->paginate(15)->appends($request->query());

        // Metrik Statistik Kontrak & MoU
        $today = now()->toDateString();
        $in30Days = now()->addDays(30)->toDateString();
        $in14Days = now()->addDays(14)->toDateString();

        $totalContracts = ClientContract::count();
        $totalActive = ClientContract::where('status', 'active')
            ->where('end_date', '>=', $today)
            ->count();

        $totalExpiringSoon = ClientContract::where('status', 'active')
            ->whereBetween('end_date', [$today, $in30Days])
            ->count();

        $totalCritical = ClientContract::where('status', 'active')
            ->whereBetween('end_date', [$today, $in14Days])
            ->count();

        $totalExpired = ClientContract::where(function ($q) use ($today) {
            $q->where('status', 'expired')
              ->orWhere(function ($sq) use ($today) {
                  $sq->where('status', 'active')->where('end_date', '<', $today);
              });
        })->count();

        $totalDraft = ClientContract::whereIn('status', ['draft', 'review_direktur'])->count();

        // Opsi pilihan untuk form
        $categories = ClientContract::categoryOptions();
        $types = ClientContract::contractTypeOptions();
        $statuses = ClientContract::statusOptions();

        return view('secretary.contracts.index', compact(
            'contracts',
            'totalContracts',
            'totalActive',
            'totalExpiringSoon',
            'totalCritical',
            'totalExpired',
            'totalDraft',
            'categories',
            'types',
            'statuses',
            'search',
            'status',
            'category',
            'type',
            'urgency'
        ));
    }

    /**
     * Daftarkan Kontrak / MoU Kerjasama Baru
     */
    public function store(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser || !$authUser->canManageClientContracts()) {
            abort(403, 'Akses Ditolak.');
        }

        $validated = $request->validate([
            'contract_number' => 'required|string|max:100|unique:client_contracts,contract_number',
            'title' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'client_category' => 'required|string|max:50',
            'contract_type' => 'required|string|max:50',
            'client_pic_name' => 'nullable|string|max:150',
            'client_pic_phone' => 'nullable|string|max:50',
            'client_pic_email' => 'nullable|email|max:150',
            'client_address' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|string|max:50',
            'auto_renewal' => 'nullable|boolean',
            'contract_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:IDR,JPY,USD',
            'notes' => 'nullable|string',
            'signed_at' => 'nullable|date',
            'draft_file' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'signed_file' => 'nullable|file|mimes:pdf|max:20480',
        ], [
            'contract_number.unique' => 'Nomor Kontrak / MoU ini sudah terdaftar sebelumnya.',
            'end_date.after_or_equal' => 'Tanggal berakhir kontrak harus sama atau setelah tanggal mulai.',
            'draft_file.mimes' => 'Berkas draft MoU harus berformat PDF, DOC, atau DOCX.',
            'signed_file.mimes' => 'Berkas kontrak bertanda tangan harus berformat PDF.',
            'draft_file.max' => 'Ukuran berkas draft maksimal 20 MB.',
            'signed_file.max' => 'Ukuran berkas bertanda tangan maksimal 20 MB.',
        ]);

        $draftPath = null;
        if ($request->hasFile('draft_file')) {
            $draftFile = $request->file('draft_file');
            $secCheck = $this->validateUploadSecurity($draftFile, 'document', 25);
            if (!$secCheck['safe']) {
                return back()->withInput()->withErrors(['draft_file' => $secCheck['message']]);
            }
            $cleanName = time() . '_draft_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $draftFile->getClientOriginalName());
            $draftPath = $draftFile->storeAs('client_contracts/drafts', $cleanName, 'public');
        }

        $signedPath = null;
        if ($request->hasFile('signed_file')) {
            $signedFile = $request->file('signed_file');
            $secCheck = $this->validateUploadSecurity($signedFile, 'pdf', 25);
            if (!$secCheck['safe']) {
                return back()->withInput()->withErrors(['signed_file' => $secCheck['message']]);
            }
            $cleanName = time() . '_signed_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $signedFile->getClientOriginalName());
            $signedPath = $signedFile->storeAs('client_contracts/signed', $cleanName, 'public');
        }

        ClientContract::create([
            'contract_number' => trim($validated['contract_number']),
            'title' => trim($validated['title']),
            'client_name' => trim($validated['client_name']),
            'client_category' => $validated['client_category'],
            'contract_type' => $validated['contract_type'],
            'client_pic_name' => $validated['client_pic_name'] ?? null,
            'client_pic_phone' => $validated['client_pic_phone'] ?? null,
            'client_pic_email' => $validated['client_pic_email'] ?? null,
            'client_address' => $validated['client_address'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'auto_renewal' => $request->boolean('auto_renewal'),
            'contract_value' => $validated['contract_value'] ?? null,
            'currency' => $validated['currency'] ?? 'IDR',
            'draft_file_path' => $draftPath,
            'signed_file_path' => $signedPath,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $authUser->id,
            'signed_at' => $validated['signed_at'] ?? null,
        ]);

        return redirect()->route('secretary.contracts.index')->with('success', "Kontrak / MoU {$validated['contract_number']} bersama {$validated['client_name']} berhasil didaftarkan ke sistem.");
    }

    /**
     * Perbarui Data Kontrak / MoU
     */
    public function update(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser || !$authUser->canManageClientContracts()) {
            abort(403, 'Akses Ditolak.');
        }

        $contract = ClientContract::findOrFail($id);

        $validated = $request->validate([
            'contract_number' => "required|string|max:100|unique:client_contracts,contract_number,{$contract->id}",
            'title' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'client_category' => 'required|string|max:50',
            'contract_type' => 'required|string|max:50',
            'client_pic_name' => 'nullable|string|max:150',
            'client_pic_phone' => 'nullable|string|max:50',
            'client_pic_email' => 'nullable|email|max:150',
            'client_address' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|string|max:50',
            'auto_renewal' => 'nullable|boolean',
            'contract_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|in:IDR,JPY,USD',
            'notes' => 'nullable|string',
            'signed_at' => 'nullable|date',
            'draft_file' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'signed_file' => 'nullable|file|mimes:pdf|max:20480',
        ]);

        $draftPath = $contract->draft_file_path;
        if ($request->hasFile('draft_file')) {
            $draftFile = $request->file('draft_file');
            $secCheck = $this->validateUploadSecurity($draftFile, 'document', 25);
            if (!$secCheck['safe']) {
                return back()->withInput()->withErrors(['draft_file' => $secCheck['message']]);
            }
            if ($draftPath && Storage::disk('public')->exists($draftPath)) {
                Storage::disk('public')->delete($draftPath);
            }
            $cleanName = time() . '_draft_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $draftFile->getClientOriginalName());
            $draftPath = $draftFile->storeAs('client_contracts/drafts', $cleanName, 'public');
        }

        $signedPath = $contract->signed_file_path;
        if ($request->hasFile('signed_file')) {
            $signedFile = $request->file('signed_file');
            $secCheck = $this->validateUploadSecurity($signedFile, 'pdf', 25);
            if (!$secCheck['safe']) {
                return back()->withInput()->withErrors(['signed_file' => $secCheck['message']]);
            }
            if ($signedPath && Storage::disk('public')->exists($signedPath)) {
                Storage::disk('public')->delete($signedPath);
            }
            $cleanName = time() . '_signed_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $signedFile->getClientOriginalName());
            $signedPath = $signedFile->storeAs('client_contracts/signed', $cleanName, 'public');
        }

        $contract->update([
            'contract_number' => trim($validated['contract_number']),
            'title' => trim($validated['title']),
            'client_name' => trim($validated['client_name']),
            'client_category' => $validated['client_category'],
            'contract_type' => $validated['contract_type'],
            'client_pic_name' => $validated['client_pic_name'] ?? $contract->client_pic_name,
            'client_pic_phone' => $validated['client_pic_phone'] ?? $contract->client_pic_phone,
            'client_pic_email' => $validated['client_pic_email'] ?? $contract->client_pic_email,
            'client_address' => $validated['client_address'] ?? $contract->client_address,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'auto_renewal' => $request->boolean('auto_renewal'),
            'contract_value' => $validated['contract_value'] ?? $contract->contract_value,
            'currency' => $validated['currency'] ?? $contract->currency,
            'draft_file_path' => $draftPath,
            'signed_file_path' => $signedPath,
            'notes' => $validated['notes'] ?? $contract->notes,
            'signed_at' => $validated['signed_at'] ?? $contract->signed_at,
        ]);

        return redirect()->route('secretary.contracts.index')->with('success', "Data Kontrak / MoU {$contract->contract_number} berhasil diperbarui.");
    }

    /**
     * Aksi Cepat Perpanjangan Kontrak (Renewal / Addendum Masa Berlaku)
     */
    public function renew(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser || !$authUser->canManageClientContracts()) {
            abort(403, 'Akses Ditolak.');
        }

        $contract = ClientContract::findOrFail($id);

        $validated = $request->validate([
            'new_end_date' => 'required|date|after:' . $contract->end_date->toDateString(),
            'renewal_notes' => 'nullable|string',
            'signed_file' => 'nullable|file|mimes:pdf|max:20480',
        ], [
            'new_end_date.after' => 'Tanggal baru perpanjangan harus lebih lanjut dari tanggal berakhir sebelumnya (' . $contract->end_date->format('d/m/Y') . ').',
            'signed_file.mimes' => 'Berkas addendum/perpanjangan bertanda tangan harus berformat PDF.',
        ]);

        $signedPath = $contract->signed_file_path;
        if ($request->hasFile('signed_file')) {
            $signedFile = $request->file('signed_file');
            $secCheck = $this->validateUploadSecurity($signedFile, 'pdf', 25);
            if (!$secCheck['safe']) {
                return back()->withInput()->withErrors(['signed_file' => $secCheck['message']]);
            }
            $cleanName = time() . '_addendum_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $signedFile->getClientOriginalName());
            $signedPath = $signedFile->storeAs('client_contracts/signed', $cleanName, 'public');
        }

        $oldEndFormatted = $contract->end_date->format('d/m/Y');
        $newEndFormatted = Carbon::parse($validated['new_end_date'])->format('d/m/Y');
        $renewalLog = "\n[Perpanjangan Kontrak oleh {$authUser->name} pada " . now()->format('d/m/Y H:i') . ": Masa berlaku diperpanjang dari {$oldEndFormatted} s/d {$newEndFormatted}. Catatan: " . ($validated['renewal_notes'] ?? '-') . "]";

        $contract->update([
            'end_date' => $validated['new_end_date'],
            'status' => 'active',
            'signed_file_path' => $signedPath,
            'notes' => trim(($contract->notes ?? '') . $renewalLog),
        ]);

        return redirect()->route('secretary.contracts.index')->with('success', "Kontrak / MoU {$contract->contract_number} berhasil diperpanjang hingga {$newEndFormatted}.");
    }

    /**
     * Hapus Data Kontrak / MoU
     */
    public function destroy($id)
    {
        $authUser = Auth::user();
        if (!$authUser || !$authUser->canManageClientContracts()) {
            abort(403, 'Akses Ditolak.');
        }

        $contract = ClientContract::findOrFail($id);
        $num = $contract->contract_number;

        if ($contract->draft_file_path && Storage::disk('public')->exists($contract->draft_file_path)) {
            Storage::disk('public')->delete($contract->draft_file_path);
        }
        if ($contract->signed_file_path && Storage::disk('public')->exists($contract->signed_file_path)) {
            Storage::disk('public')->delete($contract->signed_file_path);
        }

        $contract->delete();

        return redirect()->route('secretary.contracts.index')->with('success', "Kontrak / MoU {$num} berhasil dihapus dari sistem.");
    }

    /**
     * Unduh / Tampilkan Berkas Dokumen (Draft atau Signed) secara Aman (Bypass cPanel Symlink)
     */
    public function downloadFile($id, $type)
    {
        $authUser = Auth::user();
        if (!$authUser || !$authUser->canManageClientContracts()) {
            abort(403, 'Akses Ditolak: Dokumen kontrak klien hanya dapat diakses oleh Sekretaris Direktur dan Direktur Utama.');
        }

        $contract = ClientContract::findOrFail($id);

        $filePath = ($type === 'draft') ? $contract->draft_file_path : $contract->signed_file_path;

        if (!$filePath) {
            abort(404, 'Berkas fisik dokumen ini belum diunggah.');
        }

        $disk = Storage::disk('public');
        if ($disk->exists($filePath)) {
            $content = $disk->get($filePath);
            $mime = $disk->mimeType($filePath) ?: 'application/octet-stream';
            return response($content, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            ]);
        }

        $fullPath = storage_path('app/public/' . $filePath);
        if (file_exists($fullPath)) {
            $content = file_get_contents($fullPath);
            $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
            return response($content, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            ]);
        }

        abort(404, 'Berkas dokumen tidak ditemukan di server.');
    }
}

