<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\InventoryRequest;
use App\Models\OfficeSupply;
use App\Models\OfficeSupplyUsage;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class GeneralAffairsController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');
        $condition = $request->query('condition');
        $activeTab = $request->query('tab', 'supplies'); // default to supplies, or inventory, or requests
        if (!$request->has('tab') && ($request->has('category') || $request->has('condition'))) {
            $activeTab = 'inventory';
        } elseif (!$request->has('tab') && $request->has('req_page')) {
            $activeTab = 'requests';
        }

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

        // Query & Filter Master ATK Kantor (Office Supplies)
        $atkCategory = $request->query('atk_category');
        $atkStatus = $request->query('atk_status');
        $atkSearch = $request->query('atk_search');

        $suppliesQuery = OfficeSupply::orderBy('name');
        if ($atkCategory) {
            $suppliesQuery->where('category', $atkCategory);
        }
        if ($atkStatus === 'low_stock') {
            $suppliesQuery->whereRaw('stock_qty <= min_stock AND stock_qty > 0');
        } elseif ($atkStatus === 'out_of_stock') {
            $suppliesQuery->where('stock_qty', '<=', 0);
        } elseif ($atkStatus === 'available') {
            $suppliesQuery->whereRaw('stock_qty > min_stock');
        }
        if ($atkSearch) {
            $suppliesQuery->where(function ($q) use ($atkSearch) {
                $q->where('name', 'like', "%{$atkSearch}%")
                  ->orWhere('item_code', 'like', "%{$atkSearch}%")
                  ->orWhere('location', 'like', "%{$atkSearch}%");
            });
        }
        $supplies = $suppliesQuery->paginate(12, ['*'], 'atk_page')->appends($request->except('atk_page'));
        $allSupplies = OfficeSupply::where('stock_qty', '>', 0)->orderBy('name')->get();
        $allSuppliesComplete = OfficeSupply::orderBy('name')->get();

        // Query & Filter Riwayat Pemakaian ATK
        $usageDept = $request->query('usage_dept');
        $usageSearch = $request->query('usage_search');
        $usagesQuery = OfficeSupplyUsage::with(['officeSupply', 'employee', 'recorder'])->orderBy('usage_date', 'desc')->orderBy('created_at', 'desc');
        if ($usageDept) {
            $usagesQuery->where('department', $usageDept);
        }
        if ($usageSearch) {
            $usagesQuery->where(function ($q) use ($usageSearch) {
                $q->whereHas('employee', function ($eq) use ($usageSearch) {
                    $eq->where('name', 'like', "%{$usageSearch}%");
                })->orWhereHas('officeSupply', function ($sq) use ($usageSearch) {
                    $sq->where('name', 'like', "%{$usageSearch}%");
                })->orWhere('purpose', 'like', "%{$usageSearch}%");
            });
        }
        $usages = $usagesQuery->paginate(12, ['*'], 'usage_page')->appends($request->except('usage_page'));

        // Metrik Ringkasan Khusus ATK
        $totalAtkItems = OfficeSupply::count();
        $totalAtkStock = (int) OfficeSupply::sum('stock_qty');
        $lowStockAtkCount = OfficeSupply::whereRaw('stock_qty <= min_stock AND stock_qty > 0')->count();
        $outOfStockAtkCount = OfficeSupply::where('stock_qty', '<=', 0)->count();
        $totalAtkUsageThisMonth = (int) OfficeSupplyUsage::whereMonth('usage_date', now()->month)
            ->whereYear('usage_date', now()->year)
            ->sum('quantity');

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
            'totalEstimatedCost',
            'supplies',
            'allSupplies',
            'allSuppliesComplete',
            'usages',
            'atkCategory',
            'atkStatus',
            'atkSearch',
            'usageDept',
            'usageSearch',
            'totalAtkItems',
            'totalAtkStock',
            'lowStockAtkCount',
            'outOfStockAtkCount',
            'totalAtkUsageThisMonth'
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

    /**
     * Tambah Master Data Barang ATK Baru
     */
    public function storeSupply(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menambah master ATK kantor.');
        }

        $validated = $request->validate([
            'item_code' => 'required|string|max:30|unique:office_supplies,item_code',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:30',
            'stock_qty' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'location' => 'required|string|max:150',
            'notes' => 'nullable|string|max:500',
        ]);

        OfficeSupply::create($validated);

        return redirect()->route('ga.index', ['tab' => 'supplies'])->with('success', "Barang ATK baru ({$validated['name']}) berhasil ditambahkan ke katalog stok.");
    }

    /**
     * Perbarui Informasi Barang Master ATK
     */
    public function updateSupply(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengubah data ATK kantor.');
        }

        $supply = OfficeSupply::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:30',
            'min_stock' => 'required|integer|min:0',
            'location' => 'required|string|max:150',
            'notes' => 'nullable|string|max:500',
        ]);

        $supply->update($validated);

        return redirect()->route('ga.index', ['tab' => 'supplies'])->with('success', "Informasi ATK ({$supply->name}) berhasil diperbarui.");
    }

    /**
     * Restock / Tambah Jumlah Stok Masuk ATK
     */
    public function restockSupply(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk melakukan restock ATK.');
        }

        $supply = OfficeSupply::findOrFail($id);

        $request->validate([
            'add_qty' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        $addQty = (int) $request->add_qty;
        $supply->increment('stock_qty', $addQty);

        $notesLog = $request->notes ? " (Ket: {$request->notes})" : "";

        return redirect()->route('ga.index', ['tab' => 'supplies'])->with('success', "Stok {$supply->name} berhasil ditambah sebanyak {$addQty} {$supply->unit}. Stok terkini: {$supply->stock_qty} {$supply->unit}.{$notesLog}");
    }

    /**
     * Hapus Master Data ATK
     */
    public function destroySupply($id)
    {
        $authUser = Auth::user();
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus barang ATK.');
        }

        $supply = OfficeSupply::findOrFail($id);
        $name = $supply->name;
        $supply->delete();

        return redirect()->route('ga.index', ['tab' => 'supplies'])->with('success', "Barang ATK ({$name}) berhasil dihapus dari sistem.");
    }

    /**
     * Catat Pengambilan / Pemakaian ATK Kantor ke Karyawan / Departemen
     * Otomatis mengurangi kuantitas stok barang di gudang ATK
     */
    public function recordSupplyUsage(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mencatat pemakaian ATK.');
        }

        $validated = $request->validate([
            'office_supply_id' => 'required|exists:office_supplies,id',
            'employee_id' => 'required|exists:employees,id',
            'quantity' => 'required|integer|min:1',
            'usage_date' => 'required|date',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $supply = OfficeSupply::findOrFail($validated['office_supply_id']);

        // Validasi ketersediaan stok fisik
        if ($supply->stock_qty < $validated['quantity']) {
            return back()->with('error', "Gagal mencatat pemakaian! Stok {$supply->name} tidak mencukupi (Sisa stok: {$supply->stock_qty} {$supply->unit}, Diminta: {$validated['quantity']} {$supply->unit}). Silakan lakukan restock terlebih dahulu.");
        }

        $employee = Employee::findOrFail($validated['employee_id']);

        // Catat mutasi pemakaian
        OfficeSupplyUsage::create([
            'office_supply_id' => $supply->id,
            'employee_id' => $employee->id,
            'department' => $employee->department ?? 'General',
            'quantity' => $validated['quantity'],
            'usage_date' => $validated['usage_date'],
            'purpose' => $validated['purpose'],
            'recorded_by' => $authUser->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Kurangi stok barang secara otomatis
        $supply->decrement('stock_qty', $validated['quantity']);

        return redirect()->route('ga.index', ['tab' => 'supplies'])->with('success', "Pemakaian {$validated['quantity']} {$supply->unit} {$supply->name} untuk {$employee->name} ({$employee->department}) berhasil dicatat. Sisa stok: {$supply->stock_qty} {$supply->unit}.");
    }

    /**
     * Batalkan / Hapus Catatan Pemakaian ATK
     * Otomatis mengembalikan (rollback) jumlah stok barang ke gudang ATK
     */
    public function destroySupplyUsage($id)
    {
        $authUser = Auth::user();
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk membatalkan catatan pemakaian ATK.');
        }

        $usage = OfficeSupplyUsage::with('officeSupply')->findOrFail($id);
        $qty = $usage->quantity;
        $supply = $usage->officeSupply;

        // Rollback stok yang sebelumnya terpakai
        if ($supply) {
            $supply->increment('stock_qty', $qty);
        }

        $usage->delete();

        return redirect()->route('ga.index', ['tab' => 'supplies'])->with('success', "Catatan pemakaian ATK berhasil dibatalkan. Stok {$supply?->name} telah dikembalikan sebanyak {$qty} {$supply?->unit} (Stok sekarang: {$supply?->stock_qty} {$supply?->unit}).");
    }
}
