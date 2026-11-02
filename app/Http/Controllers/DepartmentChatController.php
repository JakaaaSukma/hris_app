<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DepartmentMessage;
use App\Models\DirectMessage;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Auth;

class DepartmentChatController extends Controller
{
    /**
     * Tampilkan antarmuka Chat:
     * - Direktur: KHUSUS Saluran Komunikasi Langsung Direksi ke Para Manager Departemen (Direktur TIDAK BISA melihat chat internal departemen).
     * - Manager: Memiliki 2 tab saluran:
     *     1. Chat Internal Departemen (bersama staf/karyawan departemennya).
     *     2. Chat Direksi (jalur langsung private bersama Direktur).
     * - Karyawan: Khusus chat internal tim departemennya sendiri.
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();

        // JIKA USER ADALAH DIREKTUR:
        if ($authUser->isDirektur()) {
            return $this->directorChatView($request, $authUser);
        }

        // JIKA USER ADALAH MANAGER ATAU KARYAWAN:
        $activeDepartment = $authUser->department ?? 'HR';

        // Jika Manager memilih tab chat direksi ('mode' => 'direksi'):
        if ($authUser->isManager() && $request->query('tab') === 'direksi') {
            return $this->managerDirectorChatView($request, $authUser);
        }

        // Default: Chat Internal Departemen (Manager & Karyawan)
        $members = User::where('department', $activeDepartment)
            ->with('employee')
            ->orderByRaw("FIELD(role, 'manager', 'karyawan', 'direktur')")
            ->orderBy('name', 'asc')
            ->get();

        $messages = DepartmentMessage::where('department', $activeDepartment)
            ->with(['user.employee'])
            ->orderBy('created_at', 'asc')
            ->take(60)
            ->get();

        // Hitung unread direct message dari Direktur jika user adalah Manager
        $directorUnreadCount = 0;
        if ($authUser->isManager()) {
            $director = User::where('role', 'direktur')->first();
            if ($director) {
                $directorUnreadCount = DirectMessage::where('sender_id', $director->id)
                    ->where('receiver_id', $authUser->id)
                    ->whereNull('read_at')
                    ->count();
            }
        }

        return view('chat.index', compact(
            'authUser',
            'activeDepartment',
            'members',
            'messages',
            'directorUnreadCount'
        ));
    }

    /**
     * Tampilan Khusus Direktur: Komunikasi Langsung ke Para Manager Departemen.
     */
    private function directorChatView(Request $request, User $authUser)
    {
        // Ambil seluruh Manager Departemen (HR, Finance, BizDev, GA)
        $managers = User::where('role', 'manager')
            ->with('employee')
            ->orderBy('department', 'asc')
            ->get();

        // Manager yang sedang dipilih untuk diajak chat
        $selectedManagerId = (int) $request->query('manager_id', 0);
        $activeManager = $managers->firstWhere('id', $selectedManagerId) ?: $managers->first();

        $messages = collect();
        if ($activeManager) {
            // Ambil percakapan direct antara Direktur & Manager terpilih
            $messages = DirectMessage::where(function ($q) use ($authUser, $activeManager) {
                    $q->where('sender_id', $authUser->id)->where('receiver_id', $activeManager->id);
                })
                ->orWhere(function ($q) use ($authUser, $activeManager) {
                    $q->where('sender_id', $activeManager->id)->where('receiver_id', $authUser->id);
                })
                ->with(['sender.employee', 'receiver.employee'])
                ->orderBy('created_at', 'asc')
                ->take(80)
                ->get();

            // Tandai pesan dari manager ini sebagai terbaca
            DirectMessage::where('sender_id', $activeManager->id)
                ->where('receiver_id', $authUser->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('chat.direktur', compact(
            'authUser',
            'managers',
            'activeManager',
            'messages'
        ));
    }

    /**
     * Tampilan Manager saat membuka tab Komunikasi dengan Direktur.
     */
    private function managerDirectorChatView(Request $request, User $authUser)
    {
        $director = User::where('role', 'direktur')->first();

        $messages = collect();
        if ($director) {
            $messages = DirectMessage::where(function ($q) use ($authUser, $director) {
                    $q->where('sender_id', $authUser->id)->where('receiver_id', $director->id);
                })
                ->orWhere(function ($q) use ($authUser, $director) {
                    $q->where('sender_id', $director->id)->where('receiver_id', $authUser->id);
                })
                ->with(['sender.employee', 'receiver.employee'])
                ->orderBy('created_at', 'asc')
                ->take(80)
                ->get();

            // Tandai pesan dari direktur sebagai terbaca
            DirectMessage::where('sender_id', $director->id)
                ->where('receiver_id', $authUser->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('chat.manager_direksi', compact(
            'authUser',
            'director',
            'messages'
        ));
    }

    /**
     * Kirim pesan: Otomatis mendeteksi apakah Chat Internal Departemen atau Direct Message Direksi/Manager.
     */
    public function sendMessage(Request $request)
    {
        $authUser = Auth::user();

        $request->validate([
            'message' => 'required|string|max:2000',
            'target_manager_id' => 'nullable|integer',
            'is_direct' => 'nullable|boolean',
        ]);

        $messageContent = trim($request->message);

        // ==========================================
        // KASUS 1: DIREKTUR KIRIM PESAN KE MANAGER
        // ==========================================
        if ($authUser->isDirektur()) {
            $managerId = (int) $request->input('target_manager_id');
            $receiver = User::where('id', $managerId)->where('role', 'manager')->firstOrFail();

            $msg = DirectMessage::create([
                'sender_id' => $authUser->id,
                'receiver_id' => $receiver->id,
                'message' => $messageContent,
            ]);

            // Kirim notifikasi ke Manager
            try {
                $shortMessage = mb_strimwidth($msg->message, 0, 80, '...');
                $receiver->notify(new GeneralNotification(
                    "Pesan Langsung dari Direktur",
                    "{$authUser->name}: {$shortMessage}",
                    route('chat.index', ['tab' => 'direksi']),
                    'chat',
                    'amber'
                ));
            } catch (\Throwable $e) {}

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => [
                        'id' => $msg->id,
                        'text' => $msg->message,
                        'sender_name' => $authUser->name,
                        'sender_role' => $authUser->role,
                        'sender_avatar' => $authUser->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120',
                        'created_at_human' => $msg->created_at->timezone('Asia/Jakarta')->format('H:i'),
                        'is_me' => true,
                    ]
                ]);
            }

            return redirect()->route('chat.index', ['manager_id' => $receiver->id]);
        }

        // ==========================================
        // KASUS 2: MANAGER KIRIM PESAN KE DIREKTUR
        // ==========================================
        if ($authUser->isManager() && $request->boolean('is_direct')) {
            $director = User::where('role', 'direktur')->firstOrFail();

            $msg = DirectMessage::create([
                'sender_id' => $authUser->id,
                'receiver_id' => $director->id,
                'message' => $messageContent,
            ]);

            // Kirim notifikasi ke Direktur
            try {
                $shortMessage = mb_strimwidth($msg->message, 0, 80, '...');
                $director->notify(new GeneralNotification(
                    "Pesan dari Manager {$authUser->department}",
                    "{$authUser->name}: {$shortMessage}",
                    route('chat.index', ['manager_id' => $authUser->id]),
                    'chat',
                    'rose'
                ));
            } catch (\Throwable $e) {}

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => [
                        'id' => $msg->id,
                        'text' => $msg->message,
                        'sender_name' => $authUser->name,
                        'sender_role' => $authUser->role,
                        'sender_avatar' => $authUser->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120',
                        'created_at_human' => $msg->created_at->timezone('Asia/Jakarta')->format('H:i'),
                        'is_me' => true,
                    ]
                ]);
            }

            return redirect()->route('chat.index', ['tab' => 'direksi']);
        }

        // ==========================================
        // KASUS 3: CHAT INTERNAL DEPARTEMEN
        // (Hanya untuk Karyawan & Manager di departemennya sendiri)
        // ==========================================
        $department = $authUser->department ?? 'HR';

        $msg = DepartmentMessage::create([
            'department' => $department,
            'user_id' => $authUser->id,
            'message' => $messageContent,
        ]);

        // Kirim notifikasi ke sesama anggota tim di departemen (TIDAK ke Direktur)
        try {
            $recipients = User::where('department', $department)
                ->where('id', '!=', $authUser->id)
                ->where('role', '!=', 'direktur')
                ->get();

            $shortMessage = mb_strimwidth($msg->message, 0, 80, '...');

            foreach ($recipients as $recipient) {
                $recipient->notify(new GeneralNotification(
                    "Pesan Baru Tim {$department}",
                    "{$authUser->name}: {$shortMessage}",
                    route('chat.index'),
                    'chat',
                    'cyan'
                ));
            }
        } catch (\Throwable $e) {}

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $msg->id,
                    'text' => $msg->message,
                    'sender_name' => $authUser->name,
                    'sender_role' => $authUser->role,
                    'sender_avatar' => $authUser->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120',
                    'created_at_human' => $msg->created_at->timezone('Asia/Jakarta')->format('H:i'),
                    'is_me' => true,
                ]
            ]);
        }

        return redirect()->route('chat.index');
    }

    /**
     * Polling JSON fetch pesan baru:
     * - Direktur: Polling percakapan direct dengan Manager terpilih.
     * - Manager (jika mode direksi): Polling percakapan direct dengan Direktur.
     * - Karyawan/Manager: Polling percakapan internal tim departemen.
     */
    public function fetchMessages(Request $request)
    {
        $authUser = Auth::user();
        $afterId = (int) $request->query('after_id', 0);

        // KASUS 1: DIREKTUR POLLING DENGAN MANAGER TERPILIH
        if ($authUser->isDirektur()) {
            $managerId = (int) $request->query('manager_id', 0);
            if (!$managerId) {
                return response()->json(['success' => true, 'messages' => []]);
            }

            $messages = DirectMessage::where('id', '>', $afterId)
                ->where(function ($q) use ($authUser, $managerId) {
                    $q->where(function ($sub) use ($authUser, $managerId) {
                        $sub->where('sender_id', $authUser->id)->where('receiver_id', $managerId);
                    })->orWhere(function ($sub) use ($authUser, $managerId) {
                        $sub->where('sender_id', $managerId)->where('receiver_id', $authUser->id);
                    });
                })
                ->with(['sender.employee'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($item) use ($authUser) {
                    return [
                        'id' => $item->id,
                        'text' => $item->message,
                        'sender_name' => $item->sender->name,
                        'sender_role' => $item->sender->role,
                        'sender_avatar' => $item->sender->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120',
                        'created_at_human' => $item->created_at->timezone('Asia/Jakarta')->format('H:i'),
                        'is_me' => $item->sender_id === $authUser->id,
                    ];
                });

            return response()->json(['success' => true, 'messages' => $messages]);
        }

        // KASUS 2: MANAGER POLLING DI TAB DIREKSI
        if ($authUser->isManager() && $request->query('tab') === 'direksi') {
            $director = User::where('role', 'direktur')->first();
            if (!$director) {
                return response()->json(['success' => true, 'messages' => []]);
            }

            $messages = DirectMessage::where('id', '>', $afterId)
                ->where(function ($q) use ($authUser, $director) {
                    $q->where(function ($sub) use ($authUser, $director) {
                        $sub->where('sender_id', $authUser->id)->where('receiver_id', $director->id);
                    })->orWhere(function ($sub) use ($authUser, $director) {
                        $sub->where('sender_id', $director->id)->where('receiver_id', $authUser->id);
                    });
                })
                ->with(['sender.employee'])
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($item) use ($authUser) {
                    return [
                        'id' => $item->id,
                        'text' => $item->message,
                        'sender_name' => $item->sender->name,
                        'sender_role' => $item->sender->role,
                        'sender_avatar' => $item->sender->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120',
                        'created_at_human' => $item->created_at->timezone('Asia/Jakarta')->format('H:i'),
                        'is_me' => $item->sender_id === $authUser->id,
                    ];
                });

            return response()->json(['success' => true, 'messages' => $messages]);
        }

        // KASUS 3: CHAT INTERNAL DEPARTEMEN
        $department = $authUser->department ?? 'HR';

        $messages = DepartmentMessage::where('department', $department)
            ->where('id', '>', $afterId)
            ->with(['user.employee'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($item) use ($authUser) {
                return [
                    'id' => $item->id,
                    'text' => $item->message,
                    'sender_name' => $item->user->name,
                    'sender_role' => $item->user->role,
                    'sender_avatar' => $item->user->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120',
                    'created_at_human' => $item->created_at->timezone('Asia/Jakarta')->format('H:i'),
                    'is_me' => $item->user_id === $authUser->id,
                ];
            });

        return response()->json(['success' => true, 'messages' => $messages]);
    }
}
