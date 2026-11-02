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
     * - Direktur: Komunikasi langsung dengan Sekretaris Direktur.
     * - Sekretaris Direktur: Pusat koordinasi eksekutif (berkomunikasi dengan Para Manager Departemen & Direktur Utama).
     * - Manager: Memiliki 2 tab saluran:
     *     1. Chat Internal Departemen (bersama staf/karyawan departemennya).
     *     2. Chat Sekretaris Direktur (Jalur komunikasi eksekutif ke Sekretaris Direktur, tidak langsung ke Direktur).
     * - Karyawan: Khusus chat internal tim departemennya sendiri.
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();

        // 1. JIKA USER ADALAH DIREKTUR:
        if ($authUser->isDirektur()) {
            return $this->directorSecretaryChatView($request, $authUser);
        }

        // 2. JIKA USER ADALAH SEKRETARIS DIREKTUR:
        if ($authUser->isSekretaris()) {
            return $this->secretaryChatView($request, $authUser);
        }

        // 3. JIKA USER ADALAH MANAGER:
        $activeDepartment = $authUser->department ?? 'HR';

        // Jika Manager memilih tab komunikasi eksekutif ('tab' => 'direksi'):
        // Sesuai aturan: Manager hanya bisa chat ke Sekretaris Direktur, tidak langsung ke Direktur.
        if ($authUser->isManager() && $request->query('tab') === 'direksi') {
            return $this->managerSecretaryChatView($request, $authUser);
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

        // Hitung unread direct message dari Sekretaris Direktur jika user adalah Manager
        $directorUnreadCount = 0;
        if ($authUser->isManager()) {
            $sekretaris = User::whereIn('role', ['sekretaris', 'sekretaris_direktur'])->first();
            if ($sekretaris) {
                $directorUnreadCount = DirectMessage::where('sender_id', $sekretaris->id)
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
     * Tampilan Khusus Direktur: Komunikasi Langsung dengan Sekretaris Direktur.
     */
    private function directorSecretaryChatView(Request $request, User $authUser)
    {
        $sekretaris = User::whereIn('role', ['sekretaris', 'sekretaris_direktur'])->first();

        $messages = collect();
        if ($sekretaris) {
            $messages = DirectMessage::where(function ($q) use ($authUser, $sekretaris) {
                    $q->where('sender_id', $authUser->id)->where('receiver_id', $sekretaris->id);
                })
                ->orWhere(function ($q) use ($authUser, $sekretaris) {
                    $q->where('sender_id', $sekretaris->id)->where('receiver_id', $authUser->id);
                })
                ->with(['sender.employee', 'receiver.employee'])
                ->orderBy('created_at', 'asc')
                ->take(80)
                ->get();

            DirectMessage::where('sender_id', $sekretaris->id)
                ->where('receiver_id', $authUser->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('chat.direktur_sekretaris', compact(
            'authUser',
            'sekretaris',
            'messages'
        ));
    }

    /**
     * Tampilan Khusus Sekretaris Direktur: Pusat Komunikasi Eksekutif
     * (Dapat berkomunikasi dengan seluruh Manager Departemen dan Direktur Utama).
     */
    private function secretaryChatView(Request $request, User $authUser)
    {
        // Kontak yang dapat dihubungi oleh Sekretaris: Direktur Utama & Seluruh Manager Departemen
        $contacts = User::where(function ($q) {
                $q->where('role', 'direktur')->orWhere('role', 'manager');
            })
            ->with('employee')
            ->orderByRaw("FIELD(role, 'direktur', 'manager')")
            ->orderBy('department', 'asc')
            ->get();

        $selectedUserId = (int) ($request->query('user_id') ?: $request->query('manager_id', 0));
        $activeContact = $contacts->firstWhere('id', $selectedUserId) ?: $contacts->first();

        $messages = collect();
        if ($activeContact) {
            $messages = DirectMessage::where(function ($q) use ($authUser, $activeContact) {
                    $q->where('sender_id', $authUser->id)->where('receiver_id', $activeContact->id);
                })
                ->orWhere(function ($q) use ($authUser, $activeContact) {
                    $q->where('sender_id', $activeContact->id)->where('receiver_id', $authUser->id);
                })
                ->with(['sender.employee', 'receiver.employee'])
                ->orderBy('created_at', 'asc')
                ->take(80)
                ->get();

            DirectMessage::where('sender_id', $activeContact->id)
                ->where('receiver_id', $authUser->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('chat.sekretaris', compact(
            'authUser',
            'contacts',
            'activeContact',
            'messages'
        ));
    }

    /**
     * Tampilan Manager saat membuka tab Komunikasi dengan Sekretaris Direktur.
     */
    private function managerSecretaryChatView(Request $request, User $authUser)
    {
        $sekretaris = User::whereIn('role', ['sekretaris', 'sekretaris_direktur'])->first();

        $messages = collect();
        if ($sekretaris) {
            $messages = DirectMessage::where(function ($q) use ($authUser, $sekretaris) {
                    $q->where('sender_id', $authUser->id)->where('receiver_id', $sekretaris->id);
                })
                ->orWhere(function ($q) use ($authUser, $sekretaris) {
                    $q->where('sender_id', $sekretaris->id)->where('receiver_id', $authUser->id);
                })
                ->with(['sender.employee', 'receiver.employee'])
                ->orderBy('created_at', 'asc')
                ->take(80)
                ->get();

            DirectMessage::where('sender_id', $sekretaris->id)
                ->where('receiver_id', $authUser->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return view('chat.manager_direksi', compact(
            'authUser',
            'sekretaris',
            'messages'
        ));
    }

    /**
     * Kirim pesan: Otomatis mendeteksi saluran obrolan.
     */
    public function sendMessage(Request $request)
    {
        $authUser = Auth::user();

        $request->validate([
            'message' => 'required|string|max:2000',
            'target_manager_id' => 'nullable|integer',
            'target_user_id' => 'nullable|integer',
            'is_direct' => 'nullable|boolean',
        ]);

        $messageContent = trim($request->message);

        // ==========================================
        // KASUS 1: DIREKTUR KIRIM PESAN KE SEKRETARIS DIREKTUR
        // ==========================================
        if ($authUser->isDirektur()) {
            $sekretaris = User::whereIn('role', ['sekretaris', 'sekretaris_direktur'])->firstOrFail();

            $msg = DirectMessage::create([
                'sender_id' => $authUser->id,
                'receiver_id' => $sekretaris->id,
                'message' => $messageContent,
            ]);

            try {
                $shortMessage = mb_strimwidth($msg->message, 0, 80, '...');
                $sekretaris->notify(new GeneralNotification(
                    "Pesan Langsung dari Direktur Utama",
                    "{$authUser->name}: {$shortMessage}",
                    route('chat.index', ['user_id' => $authUser->id]),
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

            return redirect()->route('chat.index');
        }

        // ==========================================
        // KASUS 2: SEKRETARIS DIREKTUR KIRIM PESAN KE KONTAK (MANAGER / DIREKTUR)
        // ==========================================
        if ($authUser->isSekretaris()) {
            $targetId = (int) ($request->input('target_user_id') ?: $request->input('target_manager_id'));
            $receiver = User::findOrFail($targetId);

            $msg = DirectMessage::create([
                'sender_id' => $authUser->id,
                'receiver_id' => $receiver->id,
                'message' => $messageContent,
            ]);

            try {
                $shortMessage = mb_strimwidth($msg->message, 0, 80, '...');
                $redirectUrl = $receiver->isManager() ? route('chat.index', ['tab' => 'direksi']) : route('chat.index');
                $receiver->notify(new GeneralNotification(
                    "Pesan dari Sekretaris Direktur",
                    "{$authUser->name}: {$shortMessage}",
                    $redirectUrl,
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
                        'sender_avatar' => $authUser->employee->avatar_url ?? 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150',
                        'created_at_human' => $msg->created_at->timezone('Asia/Jakarta')->format('H:i'),
                        'is_me' => true,
                    ]
                ]);
            }

            return redirect()->route('chat.index', ['user_id' => $receiver->id]);
        }

        // ==========================================
        // KASUS 3: MANAGER KIRIM PESAN KE SEKRETARIS DIREKTUR (TAB DIREKSI)
        // Aturan: Manager hanya bisa chat ke Sekretaris Direktur, DILARANG langsung ke Direktur.
        // ==========================================
        if ($authUser->isManager() && $request->boolean('is_direct')) {
            $sekretaris = User::whereIn('role', ['sekretaris', 'sekretaris_direktur'])->firstOrFail();

            $msg = DirectMessage::create([
                'sender_id' => $authUser->id,
                'receiver_id' => $sekretaris->id,
                'message' => $messageContent,
            ]);

            try {
                $shortMessage = mb_strimwidth($msg->message, 0, 80, '...');
                $sekretaris->notify(new GeneralNotification(
                    "Pesan dari Manager {$authUser->department}",
                    "{$authUser->name}: {$shortMessage}",
                    route('chat.index', ['user_id' => $authUser->id]),
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
        // KASUS 4: CHAT INTERNAL DEPARTEMEN
        // (Hanya untuk Karyawan & Manager di departemennya sendiri)
        // ==========================================
        $department = $authUser->department ?? 'HR';

        $msg = DepartmentMessage::create([
            'department' => $department,
            'user_id' => $authUser->id,
            'message' => $messageContent,
        ]);

        try {
            $recipients = User::where('department', $department)
                ->where('id', '!=', $authUser->id)
                ->where('role', '!=', 'direktur')
                ->where('role', '!=', 'sekretaris')
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
     * Polling JSON fetch pesan baru.
     */
    public function fetchMessages(Request $request)
    {
        $authUser = Auth::user();
        $afterId = (int) $request->query('after_id', 0);

        // KASUS 1: DIREKTUR POLLING DENGAN SEKRETARIS DIREKTUR
        if ($authUser->isDirektur()) {
            $sekretaris = User::whereIn('role', ['sekretaris', 'sekretaris_direktur'])->first();
            if (!$sekretaris) {
                return response()->json(['success' => true, 'messages' => []]);
            }

            $messages = DirectMessage::where('id', '>', $afterId)
                ->where(function ($q) use ($authUser, $sekretaris) {
                    $q->where(function ($sub) use ($authUser, $sekretaris) {
                        $sub->where('sender_id', $authUser->id)->where('receiver_id', $sekretaris->id);
                    })->orWhere(function ($sub) use ($authUser, $sekretaris) {
                        $sub->where('sender_id', $sekretaris->id)->where('receiver_id', $authUser->id);
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
                        'sender_avatar' => $item->sender->employee->avatar_url ?? 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150',
                        'created_at_human' => $item->created_at->timezone('Asia/Jakarta')->format('H:i'),
                        'is_me' => $item->sender_id === $authUser->id,
                    ];
                });

            return response()->json(['success' => true, 'messages' => $messages]);
        }

        // KASUS 2: SEKRETARIS DIREKTUR POLLING DENGAN KONTAK TERPILIH
        if ($authUser->isSekretaris()) {
            $targetUserId = (int) ($request->query('user_id') ?: $request->query('manager_id', 0));
            if (!$targetUserId) {
                return response()->json(['success' => true, 'messages' => []]);
            }

            $messages = DirectMessage::where('id', '>', $afterId)
                ->where(function ($q) use ($authUser, $targetUserId) {
                    $q->where(function ($sub) use ($authUser, $targetUserId) {
                        $sub->where('sender_id', $authUser->id)->where('receiver_id', $targetUserId);
                    })->orWhere(function ($sub) use ($authUser, $targetUserId) {
                        $sub->where('sender_id', $targetUserId)->where('receiver_id', $authUser->id);
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

        // KASUS 3: MANAGER POLLING DI TAB DIREKSI (MENGHUBUNGI SEKRETARIS)
        if ($authUser->isManager() && $request->query('tab') === 'direksi') {
            $sekretaris = User::whereIn('role', ['sekretaris', 'sekretaris_direktur'])->first();
            if (!$sekretaris) {
                return response()->json(['success' => true, 'messages' => []]);
            }

            $messages = DirectMessage::where('id', '>', $afterId)
                ->where(function ($q) use ($authUser, $sekretaris) {
                    $q->where(function ($sub) use ($authUser, $sekretaris) {
                        $sub->where('sender_id', $authUser->id)->where('receiver_id', $sekretaris->id);
                    })->orWhere(function ($sub) use ($authUser, $sekretaris) {
                        $sub->where('sender_id', $sekretaris->id)->where('receiver_id', $authUser->id);
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
                        'sender_avatar' => $item->sender->employee->avatar_url ?? 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150',
                        'created_at_human' => $item->created_at->timezone('Asia/Jakarta')->format('H:i'),
                        'is_me' => $item->sender_id === $authUser->id,
                    ];
                });

            return response()->json(['success' => true, 'messages' => $messages]);
        }

        // KASUS 4: CHAT INTERNAL DEPARTEMEN
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
