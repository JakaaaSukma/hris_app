<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class GeneralAffairsController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');
        $condition = $request->query('condition');
        $activeTab = $request->query('tab', 'inventory'); // inventory or requests
        $authUser = Auth::user();

        // Karyawan HR murni TIDAK BISA mengakses modul/fitur General Affairs (GA)
        if ($authUser->isKaryawan() && $authUser->isPureHR()) {
            abort(403, 'Akses Ditolak: Karyawan HR tidak memiliki izin untuk mengakses fitur-fitur General Affairs (GA).');
        }

        // Query Inventaris Aset
        $query = InventoryItem::with('assignedEmployee')->orderBy('name');
        if ($category) {
            $query->where('category', $category);
        }
        if ($condition) {
            $query->where('condition', $condition);
        }
        $items = $query->paginate(10);
        $employees = Employee::orderBy('name')->get();

        // Query Pengajuan Penambahan & Perbaikan
        $requestsQuery = InventoryRequest::with(['inventoryItem', 'requester', 'approver'])->orderBy('created_at', 'desc');
        $requests = $requestsQuery->paginate(10, ['*'], 'req_page');

        $totalItems = InventoryItem::sum('stock_qty');
        $maintenanceCount = InventoryItem::where('condition', 'maintenance')->count();
        $assignedCount = InventoryItem::whereNotNull('assigned_to')->count();

        // Metrik Pengajuan
        $pendingRequestsCount = InventoryRequest::where('status', 'pending')->count();
        $approvedRequestsCount = InventoryRequest::where('status', 'approved')->count();
        $totalEstimatedCost = InventoryRequest::where('status', 'pending')->sum('estimated_cost');

        return view('departments.ga.index', compact(
            'items',
            'requests',
            'employees',
            'authUser',
            'category',
            'condition',
            'activeTab',
            'totalItems',
            'maintenanceCount',
            'assignedCount',
            'pendingRequestsCount',
            'approvedRequestsCount',
            'totalEstimatedCost'
        ));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menambahkan inventaris kantor.');
        }

        $validated = $request->validate([
            'item_code' => 'required|unique:inventory_items,item_code|max:30',
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'stock_qty' => 'required|integer|min:1',
            'location' => 'required|string',
            'condition' => 'required|in:good,maintenance,damaged',
            'assigned_to' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        InventoryItem::create($validated);

        return back()->with('success', 'Aset & Inventaris kantor baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk memperbarui status inventaris kantor.');
        }

        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'condition' => 'required|in:good,maintenance,damaged',
            'assigned_to' => 'nullable|exists:employees,id',
            'notes' => 'nullable|string',
        ]);

        $item->update($validated);

        return back()->with('success', 'Status inventaris kantor berhasil diperbarui.');
    }

    // Buat Pengajuan Penambahan atau Perbaikan Inventaris
    public function storeRequest(Request $request)
    {
        $authUser = Auth::user();
        $currentEmployee = $authUser->employee;

        $request->validate([
            'request_type' => 'required|in:penambahan,perbaikan',
            'inventory_item_id' => 'nullable|exists:inventory_items,id',
            'item_name' => 'required|string|max:255',
            'category' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'estimated_cost' => 'required|numeric|min:0',
            'reason' => 'required|string',
            'urgency' => 'required|in:normal,urgent',
        ]);

        InventoryRequest::create([
            'request_type' => $request->request_type,
            'inventory_item_id' => $request->request_type === 'perbaikan' ? $request->inventory_item_id : null,
            'item_name' => $request->item_name,
            'category' => $request->category,
            'quantity' => $request->quantity,
            'estimated_cost' => $request->estimated_cost,
            'reason' => $request->reason,
            'urgency' => $request->urgency,
            'requested_by' => $currentEmployee?->id ?? 1,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan ' . ($request->request_type === 'penambahan' ? 'penambahan inventaris baru' : 'perbaikan inventaris') . ' berhasil diajukan dan menunggu persetujuan Tim Finance / Direktur.');
    }

    // Approval / Reject Pengajuan Inventaris (Hanya oleh Tim Finance atau Direktur)
    public function updateRequestStatus(Request $request, $id)
    {
        $authUser = Auth::user();

        // Validasi hak otorisasi: hanya Finance atau Direktur
        if (!$authUser->canApproveInventoryRequests()) {
            abort(403, 'Akses Ditolak: Hanya Tim Finance atau Direktur yang berwenang memberikan approval pengajuan inventaris.');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'approval_notes' => 'required_if:status,rejected|nullable|string|max:1000',
        ], [
            'approval_notes.required_if' => 'Alasan penolakan wajib disertakan ketika menolak pengajuan inventaris.',
        ]);

        $invRequest = InventoryRequest::findOrFail($id);

        // Aturan Anti-Self-Approval: Pengaju tidak boleh menyetujui pengajuan inventaris dirinya sendiri
        $currentEmpId = $authUser->employee_id ?? ($authUser->employee?->id);
        if ($currentEmpId && (int)$invRequest->requested_by === (int)$currentEmpId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menyetujui atau menolak pengajuan inventaris yang Anda buat sendiri. Persetujuan harus dilakukan oleh verifikator lain.');
        }

        $invRequest->update([
            'status' => $request->status,
            'approved_by' => $authUser->id,
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        // Jika pengajuan penambahan inventaris di-approve, kita bisa otomatis tambahkan ke inventaris jika perbaikan/penambahan
        if ($request->status === 'approved' && $invRequest->request_type === 'perbaikan' && $invRequest->inventory_item_id) {
            $invRequest->inventoryItem?->update([
                'condition' => 'maintenance',
                'notes' => 'Dalam proses perbaikan sesuai persetujuan Finance/Direktur (Req #' . $invRequest->id . '): ' . $invRequest->reason,
            ]);
        }

        $statusLabel = $request->status === 'approved' ? 'DISETUJUI' : 'DITOLAK';
        return back()->with('success', "Pengajuan inventaris #{$invRequest->id} ({$invRequest->item_name}) berhasil {$statusLabel}.");
    }
}
