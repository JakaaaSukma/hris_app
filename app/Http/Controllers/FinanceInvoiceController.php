<?php

namespace App\Http\Controllers;

use App\Models\ClientContract;
use App\Models\FinanceInvoice;
use App\Models\FinanceInvoiceItem;
use App\Models\FinanceInvoicePayment;
use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Traits\ValidatesFileUpload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FinanceInvoiceController extends Controller
{
    use ValidatesFileUpload;

    /**
     * Pastikan hanya Tim Finance atau Direktur Utama yang dapat mengakses modul Invoice.
     */
    private function authorizeInvoiceAccess(): User
    {
        $authUser = Auth::user();
        if (!$authUser || !$authUser->canManageInvoices()) {
            abort(403, 'Akses Ditolak: Fitur manajemen invoice & pembayaran hanya tersedia untuk Departemen Finance dan Direktur Utama.');
        }
        return $authUser;
    }

    /**
     * Halaman Utama Manajemen & Monitoring Invoice Pembayaran (AR & AP).
     */
    public function index(Request $request)
    {
        $authUser = $this->authorizeInvoiceAccess();

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $type = $request->query('type');
        $currency = $request->query('currency');
        $month = $request->query('month');

        $today = now()->toDateString();
        $in7Days = now()->addDays(7)->toDateString();

        $query = FinanceInvoice::with(['items', 'payments', 'issuer', 'creator', 'contract'])
            ->orderByRaw("CASE WHEN status IN ('issued','partial') AND due_date < ? THEN 0 WHEN status IN ('issued','partial') THEN 1 WHEN status = 'draft' THEN 2 WHEN status = 'paid' THEN 3 ELSE 4 END ASC", [$today])
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc');

        if ($type && in_array($type, ['receivable', 'payable'], true)) {
            $query->where('invoice_type', $type);
        }

        if ($currency && in_array($currency, ['IDR', 'JPY', 'USD'], true)) {
            $query->where('currency', $currency);
        }

        if ($month) {
            try {
                $parsedMonth = Carbon::createFromFormat('Y-m', $month);
                $query->whereYear('issue_date', $parsedMonth->year)
                      ->whereMonth('issue_date', $parsedMonth->month);
            } catch (\Exception $e) {
                // Abaikan format bulan yang tidak valid
            }
        }

        if ($status) {
            if ($status === 'overdue') {
                $query->whereIn('status', ['issued', 'partial'])
                      ->whereDate('due_date', '<', $today);
            } elseif ($status === 'due_soon') {
                $query->whereIn('status', ['issued', 'partial'])
                      ->whereBetween('due_date', [$today, $in7Days]);
            } elseif ($status === 'issued') {
                $query->where('status', 'issued')
                      ->whereDate('due_date', '>=', $today);
            } else {
                $query->where('status', $status);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('counterparty_name', 'like', "%{$search}%")
                  ->orWhere('counterparty_email', 'like', "%{$search}%");
            });
        }

        $invoices = $query->paginate(12)->appends($request->query());

        // Metrik Statistik Invoice (berdasarkan nominal IDR & agregat count semua mata uang)
        $openInvoices = FinanceInvoice::whereIn('status', ['issued', 'partial'])->get();
        $totalOutstandingIdr = $openInvoices->where('currency', 'IDR')->sum(fn($inv) => $inv->remaining_amount);
        $overdueInvoices = $openInvoices->filter(fn($inv) => $inv->due_date && $inv->due_date->lt(now()->startOfDay()));
        $dueSoonInvoices = $openInvoices->filter(fn($inv) => $inv->due_date && $inv->days_until_due >= 0 && $inv->days_until_due <= 7);

        $totalOverdueCount = $overdueInvoices->count();
        $totalOverdueIdr = $overdueInvoices->where('currency', 'IDR')->sum(fn($inv) => $inv->remaining_amount);
        $totalDueSoonCount = $dueSoonInvoices->count();
        $totalPaidIdr = FinanceInvoice::where('currency', 'IDR')->whereIn('status', ['paid', 'partial'])->sum('paid_amount');
        $totalIssuedCount = FinanceInvoice::where('status', '!=', 'cancelled')->count();
        $totalDraftCount = FinanceInvoice::where('status', 'draft')->count();

        $contracts = Schema::hasTable('client_contracts')
            ? ClientContract::orderBy('client_name')->get(['id', 'contract_number', 'client_name', 'title'])
            : collect();

        $nextInvoiceNumber = FinanceInvoice::generateNextInvoiceNumber();
        $types = FinanceInvoice::typeOptions();
        $statuses = FinanceInvoice::statusOptions();
        $paymentMethods = FinanceInvoice::paymentMethodOptions();
        $currencies = FinanceInvoice::currencyOptions();

        return view('departments.finance.invoices.index', compact(
            'invoices',
            'authUser',
            'search',
            'status',
            'type',
            'currency',
            'month',
            'totalOutstandingIdr',
            'totalOverdueCount',
            'totalOverdueIdr',
            'totalDueSoonCount',
            'totalPaidIdr',
            'totalIssuedCount',
            'totalDraftCount',
            'contracts',
            'nextInvoiceNumber',
            'types',
            'statuses',
            'paymentMethods',
            'currencies'
        ));
    }

    /**
     * Endpoint JSON untuk Mengambil Nomor Invoice Otomatis Berikutnya.
     */
    public function nextNumber(Request $request)
    {
        $this->authorizeInvoiceAccess();
        $date = $request->query('issue_date');

        return response()->json([
            'invoice_number' => FinanceInvoice::generateNextInvoiceNumber($date),
        ]);
    }

    /**
     * Terbitkan / Simpan Invoice Baru (Draft atau Langsung Terbit).
     */
    public function store(Request $request)
    {
        $authUser = $this->authorizeInvoiceAccess();

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:100|unique:finance_invoices,invoice_number',
            'invoice_type' => 'required|in:receivable,payable',
            'title' => 'required|string|max:255',
            'counterparty_name' => 'required|string|max:255',
            'counterparty_email' => 'nullable|email|max:150',
            'counterparty_phone' => 'nullable|string|max:50',
            'counterparty_address' => 'nullable|string|max:1000',
            'client_contract_id' => 'nullable|exists:client_contracts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|in:IDR,JPY,USD',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'action_mode' => 'required|in:draft,issue',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_holder' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'invoice_number.unique' => 'Nomor Invoice ini sudah terdaftar sebelumnya.',
            'due_date.after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal terbit invoice.',
            'items.required' => 'Minimal harus terdapat 1 rincian item tagihan pada invoice.',
            'items.min' => 'Minimal harus terdapat 1 rincian item tagihan pada invoice.',
            'attachment.mimes' => 'Lampiran invoice harus berformat PDF, JPG, atau PNG.',
            'attachment.max' => 'Ukuran lampiran maksimal 10 MB.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $secCheck = $this->validateUploadSecurity($file, 'document', 10);
            if (!$secCheck['safe']) {
                return back()->withInput()->withErrors(['attachment' => $secCheck['message']]);
            }
            $cleanName = time() . '_inv_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $attachmentPath = $file->storeAs('finance_invoices/attachments', $cleanName, 'public');
        }

        $calc = $this->calculateInvoiceTotals(
            $validated['items'],
            (float) ($validated['tax_percentage'] ?? 0),
            (float) ($validated['discount_amount'] ?? 0)
        );

        $isIssuing = $validated['action_mode'] === 'issue';

        $invoice = DB::transaction(function () use ($validated, $calc, $attachmentPath, $authUser, $isIssuing) {
            $invoice = FinanceInvoice::create([
                'invoice_number' => trim($validated['invoice_number']),
                'invoice_type' => $validated['invoice_type'],
                'title' => $validated['title'],
                'counterparty_name' => $validated['counterparty_name'],
                'counterparty_email' => $validated['counterparty_email'] ?? null,
                'counterparty_phone' => $validated['counterparty_phone'] ?? null,
                'counterparty_address' => $validated['counterparty_address'] ?? null,
                'client_contract_id' => $validated['client_contract_id'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'currency' => $validated['currency'],
                'subtotal' => $calc['subtotal'],
                'tax_percentage' => $calc['tax_percentage'],
                'tax_amount' => $calc['tax_amount'],
                'discount_amount' => $calc['discount_amount'],
                'total_amount' => $calc['total_amount'],
                'paid_amount' => 0,
                'status' => $isIssuing ? 'issued' : 'draft',
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_holder' => $validated['bank_account_holder'] ?? null,
                'attachment_path' => $attachmentPath,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $authUser->id,
                'issued_by' => $isIssuing ? $authUser->id : null,
                'issued_at' => $isIssuing ? now() : null,
            ]);

            foreach ($calc['items'] as $item) {
                $invoice->items()->create($item);
            }

            return $invoice;
        });

        if ($isIssuing) {
            $this->notifyFinanceAndDirector(
                "Invoice Terbit: {$invoice->invoice_number}",
                "{$authUser->name} menerbitkan invoice '{$invoice->title}' kepada {$invoice->counterparty_name} sebesar {$invoice->formatCurrency((float) $invoice->total_amount)} (Jatuh tempo: " . Carbon::parse($invoice->due_date)->translatedFormat('d M Y') . ").",
                route('finance.invoices.show', $invoice->id),
                'receipt_long',
                'emerald',
                $authUser->id
            );
        }

        $msg = $isIssuing
            ? "Invoice {$invoice->invoice_number} berhasil diterbitkan dengan jatuh tempo " . Carbon::parse($invoice->due_date)->format('d M Y') . "."
            : "Draft Invoice {$invoice->invoice_number} berhasil disimpan.";

        return redirect()->route('finance.invoices.index')->with('success', $msg);
    }

    /**
     * Halaman Detail & Cetak Resmi Invoice.
     */
    public function show($id)
    {
        $authUser = $this->authorizeInvoiceAccess();

        $invoice = FinanceInvoice::with(['items', 'payments.recorder', 'creator', 'issuer', 'canceller', 'contract'])
            ->findOrFail($id);

        $paymentMethods = FinanceInvoice::paymentMethodOptions();

        return view('departments.finance.invoices.show', compact('invoice', 'authUser', 'paymentMethods'));
    }

    /**
     * Terbitkan Resmi Invoice yang Sebelumnya Berstatus Draft.
     */
    public function issue($id)
    {
        $authUser = $this->authorizeInvoiceAccess();
        $invoice = FinanceInvoice::findOrFail($id);

        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Hanya invoice berstatus Draft yang dapat diterbitkan.');
        }

        $invoice->update([
            'status' => 'issued',
            'issued_by' => $authUser->id,
            'issued_at' => now(),
        ]);

        $this->notifyFinanceAndDirector(
            "Invoice Terbit: {$invoice->invoice_number}",
            "{$authUser->name} menerbitkan invoice '{$invoice->title}' ({$invoice->counterparty_name}) sebesar {$invoice->formatCurrency((float) $invoice->total_amount)}.",
            route('finance.invoices.show', $invoice->id),
            'receipt_long',
            'emerald',
            $authUser->id
        );

        return back()->with('success', "Invoice {$invoice->invoice_number} resmi diterbitkan.");
    }

    /**
     * Perbarui Data & Item Invoice (Selama Belum Lunas atau Dibatalkan).
     */
    public function update(Request $request, $id)
    {
        $this->authorizeInvoiceAccess();
        $invoice = FinanceInvoice::findOrFail($id);

        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            return back()->with('error', 'Invoice yang sudah Lunas atau Dibatalkan tidak dapat diubah lagi.');
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:100|unique:finance_invoices,invoice_number,' . $invoice->id,
            'invoice_type' => 'required|in:receivable,payable',
            'title' => 'required|string|max:255',
            'counterparty_name' => 'required|string|max:255',
            'counterparty_email' => 'nullable|email|max:150',
            'counterparty_phone' => 'nullable|string|max:50',
            'counterparty_address' => 'nullable|string|max:1000',
            'client_contract_id' => 'nullable|exists:client_contracts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|in:IDR,JPY,USD',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_holder' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $attachmentPath = $invoice->attachment_path;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $secCheck = $this->validateUploadSecurity($file, 'document', 10);
            if (!$secCheck['safe']) {
                return back()->withInput()->withErrors(['attachment' => $secCheck['message']]);
            }
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }
            $cleanName = time() . '_inv_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $attachmentPath = $file->storeAs('finance_invoices/attachments', $cleanName, 'public');
        }

        $calc = $this->calculateInvoiceTotals(
            $validated['items'],
            (float) ($validated['tax_percentage'] ?? 0),
            (float) ($validated['discount_amount'] ?? 0)
        );

        if ($calc['total_amount'] < (float) $invoice->paid_amount) {
            return back()->with('error', 'Total tagihan baru tidak boleh lebih kecil dari nominal pembayaran yang sudah masuk.');
        }

        DB::transaction(function () use ($invoice, $validated, $calc, $attachmentPath) {
            $newStatus = $invoice->status;
            if ($invoice->status !== 'draft') {
                if ((float) $invoice->paid_amount >= $calc['total_amount'] && $calc['total_amount'] > 0) {
                    $newStatus = 'paid';
                } elseif ((float) $invoice->paid_amount > 0) {
                    $newStatus = 'partial';
                } else {
                    $newStatus = 'issued';
                }
            }

            $invoice->update([
                'invoice_number' => trim($validated['invoice_number']),
                'invoice_type' => $validated['invoice_type'],
                'title' => $validated['title'],
                'counterparty_name' => $validated['counterparty_name'],
                'counterparty_email' => $validated['counterparty_email'] ?? null,
                'counterparty_phone' => $validated['counterparty_phone'] ?? null,
                'counterparty_address' => $validated['counterparty_address'] ?? null,
                'client_contract_id' => $validated['client_contract_id'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'currency' => $validated['currency'],
                'subtotal' => $calc['subtotal'],
                'tax_percentage' => $calc['tax_percentage'],
                'tax_amount' => $calc['tax_amount'],
                'discount_amount' => $calc['discount_amount'],
                'total_amount' => $calc['total_amount'],
                'status' => $newStatus,
                'paid_at' => $newStatus === 'paid' ? ($invoice->paid_at ?? now()) : null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_holder' => $validated['bank_account_holder'] ?? null,
                'attachment_path' => $attachmentPath,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->items()->delete();
            foreach ($calc['items'] as $item) {
                $invoice->items()->create($item);
            }
        });

        return back()->with('success', "Invoice {$invoice->invoice_number} berhasil diperbarui.");
    }

    /**
     * Catat Pembayaran Invoice (Cicilan / Sebagian atau Pelunasan Penuh).
     */
    public function recordPayment(Request $request, $id)
    {
        $authUser = $this->authorizeInvoiceAccess();
        $invoice = FinanceInvoice::findOrFail($id);

        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            return back()->with('error', 'Invoice ini sudah Lunas atau Dibatalkan.');
        }

        $remaining = $invoice->remaining_amount;

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
            'proof_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'amount.min' => 'Nominal pembayaran harus lebih dari 0.',
            'proof_file.mimes' => 'Bukti transfer harus berformat PDF, JPG, atau PNG.',
            'proof_file.max' => 'Ukuran bukti transfer maksimal 10 MB.',
        ]);

        if ((float) $validated['amount'] > ($remaining + 0.01)) {
            return back()->with('error', "Nominal pembayaran melebihi sisa tagihan ({$invoice->formatCurrency($remaining)}).");
        }

        $proofPath = null;
        if ($request->hasFile('proof_file')) {
            $file = $request->file('proof_file');
            $secCheck = $this->validateUploadSecurity($file, 'document', 10);
            if (!$secCheck['safe']) {
                return back()->withErrors(['proof_file' => $secCheck['message']]);
            }
            $cleanName = time() . '_pay_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file->getClientOriginalName());
            $proofPath = $file->storeAs('finance_invoices/payments', $cleanName, 'public');
        }

        DB::transaction(function () use ($invoice, $validated, $proofPath, $authUser) {
            FinanceInvoicePayment::create([
                'invoice_id' => $invoice->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'proof_file_path' => $proofPath,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $authUser->id,
            ]);

            $newPaidAmount = round((float) $invoice->paid_amount + (float) $validated['amount'], 2);
            $isFullyPaid = $newPaidAmount >= ((float) $invoice->total_amount - 0.01);

            $invoice->update([
                'paid_amount' => min($newPaidAmount, (float) $invoice->total_amount),
                'status' => $isFullyPaid ? 'paid' : 'partial',
                'issued_by' => $invoice->issued_by ?? $authUser->id,
                'issued_at' => $invoice->issued_at ?? now(),
                'paid_at' => $isFullyPaid ? now() : null,
            ]);
        });

        $invoice->refresh();
        $statusLabel = $invoice->status === 'paid' ? 'LUNAS' : 'Dibayar Sebagian';

        $this->notifyFinanceAndDirector(
            "Pembayaran Invoice ({$statusLabel}): {$invoice->invoice_number}",
            "{$authUser->name} mencatat pembayaran sebesar {$invoice->formatCurrency((float) $validated['amount'])} via {$validated['payment_method']} untuk {$invoice->counterparty_name}. Sisa tagihan: {$invoice->formatCurrency($invoice->remaining_amount)}.",
            route('finance.invoices.show', $invoice->id),
            'payments',
            $invoice->status === 'paid' ? 'emerald' : 'amber',
            $authUser->id
        );

        return back()->with('success', "Pembayaran sebesar {$invoice->formatCurrency((float) $validated['amount'])} berhasil dicatat. Status invoice: {$statusLabel}.");
    }

    /**
     * Batalkan Invoice (Wajib Menyertakan Alasan Pembatalan).
     */
    public function cancel(Request $request, $id)
    {
        $authUser = $this->authorizeInvoiceAccess();
        $invoice = FinanceInvoice::findOrFail($id);

        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice yang sudah Lunas tidak dapat dibatalkan.');
        }

        $validated = $request->validate([
            'cancel_reason' => 'required|string|min:5|max:1000',
        ], [
            'cancel_reason.required' => 'Alasan pembatalan invoice wajib diisi.',
            'cancel_reason.min' => 'Alasan pembatalan minimal 5 karakter.',
        ]);

        $invoice->update([
            'status' => 'cancelled',
            'cancelled_by' => $authUser->id,
            'cancel_reason' => $validated['cancel_reason'],
        ]);

        return back()->with('success', "Invoice {$invoice->invoice_number} telah dibatalkan.");
    }

    /**
     * Hapus Invoice (Khusus Manager Finance & Direktur Utama).
     */
    public function destroy($id)
    {
        $authUser = $this->authorizeInvoiceAccess();
        if (!$authUser->canDeleteInvoices()) {
            abort(403, 'Akses Ditolak: Hanya Manager Finance atau Direktur Utama yang berwenang menghapus data invoice.');
        }

        $invoice = FinanceInvoice::with('payments')->findOrFail($id);
        $number = $invoice->invoice_number;

        if ($invoice->attachment_path && Storage::disk('public')->exists($invoice->attachment_path)) {
            Storage::disk('public')->delete($invoice->attachment_path);
        }
        foreach ($invoice->payments as $payment) {
            if ($payment->proof_file_path && Storage::disk('public')->exists($payment->proof_file_path)) {
                Storage::disk('public')->delete($payment->proof_file_path);
            }
        }

        $invoice->delete();

        return redirect()->route('finance.invoices.index')->with('success', "Invoice {$number} berhasil dihapus dari sistem.");
    }

    /**
     * Stream Berkas Lampiran Invoice (Bypass cPanel Symlink).
     */
    public function downloadAttachment($id)
    {
        $this->authorizeInvoiceAccess();
        $invoice = FinanceInvoice::findOrFail($id);

        return $this->streamPublicStorageFile($invoice->attachment_path, 'Lampiran invoice tidak ditemukan.');
    }

    /**
     * Stream Bukti Pembayaran / Transfer Invoice (Bypass cPanel Symlink).
     */
    public function downloadPaymentProof($paymentId)
    {
        $this->authorizeInvoiceAccess();
        $payment = FinanceInvoicePayment::findOrFail($paymentId);

        return $this->streamPublicStorageFile($payment->proof_file_path, 'Bukti pembayaran tidak ditemukan.');
    }

    /**
     * Hitung Subtotal, Pajak, Diskon & Total Invoice dari Daftar Item.
     */
    private function calculateInvoiceTotals(array $rawItems, float $taxPercentage, float $discountAmount): array
    {
        $items = [];
        $subtotal = 0.0;

        foreach ($rawItems as $raw) {
            $qty = round((float) $raw['quantity'], 2);
            $unitPrice = round((float) $raw['unit_price'], 2);
            $lineTotal = round($qty * $unitPrice, 2);
            $subtotal += $lineTotal;

            $items[] = [
                'description' => trim((string) $raw['description']),
                'quantity' => $qty,
                'unit' => !empty($raw['unit']) ? trim((string) $raw['unit']) : 'unit',
                'unit_price' => $unitPrice,
                'total_price' => $lineTotal,
            ];
        }

        $subtotal = round($subtotal, 2);
        $taxPct = max(0.0, min(100.0, round($taxPercentage, 2)));
        $discount = max(0.0, round($discountAmount, 2));
        $taxableBase = max(0.0, $subtotal - $discount);
        $taxAmount = round($taxableBase * ($taxPct / 100), 2);
        $totalAmount = max(0.0, round($taxableBase + $taxAmount, 2));

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'tax_percentage' => $taxPct,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Stream File Aman dari Storage Disk Public (Mendukung Shared Hosting cPanel Tanpa Symlink).
     */
    private function streamPublicStorageFile(?string $filePath, string $notFoundMessage)
    {
        if (!$filePath) {
            abort(404, $notFoundMessage);
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
            $mime = function_exists('mime_content_type') ? (mime_content_type($fullPath) ?: 'application/octet-stream') : 'application/octet-stream';
            return response($content, 200, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            ]);
        }

        abort(404, $notFoundMessage);
    }

    /**
     * Kirim Notifikasi In-App ke Seluruh Tim Finance & Direktur Utama.
     */
    private function notifyFinanceAndDirector(string $title, string $message, string $url, string $icon, string $color, int $actorId): void
    {
        $recipients = User::where(function ($q) {
            $q->whereRaw('LOWER(department) = ?', ['finance'])
              ->orWhere('role', 'direktur');
        })->where('id', '!=', $actorId)->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new GeneralNotification($title, $message, $url, $icon, $color));
        }
    }
}
