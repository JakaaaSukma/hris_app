<?php

namespace App\Http\Controllers;

use App\Models\FacilityTicket;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FacilityTicketController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $currentEmployee = $authUser->employee;
        $isGA = $authUser->canControlGA();

        $statusFilter = $request->get('status');
        $priorityFilter = $request->get('priority');

        $query = FacilityTicket::with(['reporter', 'handler']);

        // Jika bukan GA atau Direktur, hanya bisa melihat tiket kendala yang dilaporkan oleh diri sendiri
        if (!$isGA) {
            $query->where('reporter_id', $currentEmployee?->id);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($priorityFilter) {
            $query->where('priority', $priorityFilter);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(12);

        // Anggota Tim GA untuk penugasan penanganan kendala
        $gaStaff = Employee::where(function ($q) {
            $q->where('sub_department', 'GA')
              ->orWhere('department', 'GA');
        })->orderBy('name')->get();

        // Metrik
        $totalTickets = $isGA ? FacilityTicket::count() : FacilityTicket::where('reporter_id', $currentEmployee?->id)->count();
        $openTickets = $isGA 
            ? FacilityTicket::where('status', 'open')->count() 
            : FacilityTicket::where('reporter_id', $currentEmployee?->id)->where('status', 'open')->count();
        $inProgressTickets = $isGA 
            ? FacilityTicket::where('status', 'in_progress')->count() 
            : FacilityTicket::where('reporter_id', $currentEmployee?->id)->where('status', 'in_progress')->count();
        $resolvedTickets = $isGA 
            ? FacilityTicket::whereIn('status', ['resolved', 'closed'])->count() 
            : FacilityTicket::where('reporter_id', $currentEmployee?->id)->whereIn('status', ['resolved', 'closed'])->count();

        return view('facilities.index', compact(
            'tickets',
            'gaStaff',
            'authUser',
            'currentEmployee',
            'isGA',
            'statusFilter',
            'priorityFilter',
            'totalTickets',
            'openTickets',
            'inProgressTickets',
            'resolvedTickets'
        ));
    }

    // Buat Laporan Kerusakan Baru (Bisa dilakukan seluruh Karyawan & Manager)
    public function store(Request $request)
    {
        $authUser = Auth::user();
        $currentEmployee = $authUser->employee;

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'location' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high,urgent',
            'description' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120', // Maksimal 5MB
        ]);

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('facilities', 'public');
            $photoUrl = asset('storage/' . $path);
        }

        $ticketCount = FacilityTicket::count() + 1;
        $ticketNumber = 'TKT-GA-' . date('Ym') . '-' . str_pad($ticketCount, 4, '0', STR_PAD_LEFT);

        FacilityTicket::create([
            'ticket_number' => $ticketNumber,
            'reporter_id' => $currentEmployee?->id ?? 1,
            'title' => $request->title,
            'category' => $request->category,
            'location' => $request->location,
            'priority' => $request->priority,
            'description' => $request->description,
            'photo' => $photoUrl,
            'status' => 'open',
            'progress_percentage' => 0,
        ]);

        return back()->with('success', "Laporan kerusakan fasilitas #{$ticketNumber} berhasil dikirim ke Tim GA.");
    }

    // Update Progres & Status Penanganan Laporan oleh Tim GA (atau Direktur)
    public function updateProgress(Request $request, $id)
    {
        $authUser = Auth::user();

        // Karyawan HR murni dilarang mengelola tiket fasilitas GA
        if ($authUser->isKaryawan() && $authUser->isPureHR()) {
            abort(403, 'Akses Ditolak: Karyawan HR tidak memiliki izin untuk mengelola atau memperbarui tiket fasilitas GA.');
        }

        // Hanya Tim GA, Asisten Manager GA, Manager HR, atau Direktur yang berhak mengupdate status pengerjaan tiket kendala
        if (!$authUser->canControlGA()) {
            abort(403, 'Akses Ditolak: Hanya Tim GA, Asisten Manager GA, Manager HR, atau Direktur yang dapat memperbarui progres penanganan laporan.');
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'progress_percentage' => 'required|integer|min:0|max:100',
            'handled_by' => 'nullable|exists:employees,id',
            'resolution_notes' => 'nullable|string',
        ]);

        $ticket = FacilityTicket::findOrFail($id);

        $updateData = [
            'status' => $request->status,
            'progress_percentage' => $request->progress_percentage,
            'handled_by' => $request->handled_by ?: $ticket->handled_by,
            'resolution_notes' => $request->resolution_notes,
        ];

        if ($request->status === 'resolved' || $request->status === 'closed') {
            $updateData['resolved_at'] = now();
            if ($request->progress_percentage < 100) {
                $updateData['progress_percentage'] = 100;
            }
        }

        $ticket->update($updateData);

        return back()->with('success', "Progres penanganan tiket #{$ticket->ticket_number} berhasil diperbarui ({$ticket->progress_percentage}% - " . strtoupper(str_replace('_', ' ', $ticket->status)) . ").");
    }
}
