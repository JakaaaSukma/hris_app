@extends('layouts.app')

@section('title', 'Notifikasi Sistem')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl bg-[#141822] border border-slate-800 shadow-xl">
        <div class="flex items-center gap-3.5">
            <span class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">notifications_active</span>
            </span>
            <div>
                <h1 class="font-headline font-bold text-lg sm:text-xl text-white">Notifikasi Saya</h1>
                <p class="text-xs text-slate-400">Pemberitahuan aktivitas, tugas, cuti, jadwal, dan pengajuan operasional</p>
            </div>
        </div>

        @if($unreadCount > 0)
            <form action="{{ route('notifications.markAllRead') }}" method="POST">
                @csrf
                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 flex items-center gap-2 transition-all shadow-sm active:scale-95">
                    <span class="material-symbols-outlined text-base text-rose-400">mark_email_read</span>
                    <span>Tandai Semua Dibaca ({{ $unreadCount }})</span>
                </button>
            </form>
        @endif
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Notifications List Card -->
    <div class="bg-[#141822] rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="p-4 border-b border-slate-800/80 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-semibold uppercase tracking-wider text-slate-400">Riwayat Notifikasi</span>
                @if($unreadCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                        {{ $unreadCount }} Belum Dibaca
                    </span>
                @endif
            </div>
            <span class="text-xs font-mono text-slate-500">Total: {{ $notifications->total() }}</span>
        </div>

        @if($notifications->isEmpty())
            <div class="py-16 px-4 text-center flex flex-col items-center justify-center">
                <div class="w-16 h-16 rounded-2xl bg-slate-800/50 text-slate-600 flex items-center justify-center mb-3">
                    <span class="material-symbols-outlined text-3xl">notifications_off</span>
                </div>
                <h3 class="font-headline font-bold text-sm text-slate-300">Belum Ada Notifikasi</h3>
                <p class="text-xs text-slate-500 max-w-sm mt-1">Anda belum menerima notifikasi baru. Pemberitahuan tugas atau pengajuan akan muncul di sini.</p>
            </div>
        @else
            <div class="divide-y divide-slate-800/60">
                @foreach($notifications as $item)
                    @php
                        $data = $item->data;
                        $isUnread = is_null($item->read_at);
                        $color = $data['color'] ?? 'rose';
                        $icon = $data['icon'] ?? 'notifications';
                        $url = $data['url'] ?? null;

                        $iconColor = match($color) {
                            'emerald' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
                            'amber' => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
                            'blue' => 'bg-blue-500/15 text-blue-400 border-blue-500/30',
                            'purple' => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
                            default => 'bg-rose-500/15 text-rose-400 border-rose-500/30'
                        };
                    @endphp
                    <div class="p-4 sm:p-5 flex items-start justify-between gap-4 transition-colors {{ $isUnread ? 'bg-[#181d2a]' : 'hover:bg-slate-800/30' }}">
                        <div class="flex items-start gap-3.5 flex-1 min-w-0">
                            <span class="w-10 h-10 rounded-xl border flex items-center justify-center shrink-0 {{ $iconColor }}">
                                <span class="material-symbols-outlined text-lg">{{ $icon }}</span>
                            </span>

                            <div class="flex-1 min-w-0 space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-headline font-bold text-sm {{ $isUnread ? 'text-white' : 'text-slate-300' }}">
                                        {{ $data['title'] ?? 'Pemberitahuan Sistem' }}
                                    </h4>
                                    @if($isUnread)
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30">Baru</span>
                                    @endif
                                </div>

                                <p class="text-xs text-slate-400 leading-relaxed break-words">
                                    {{ $data['message'] ?? '-' }}
                                </p>

                                <div class="flex items-center gap-3 pt-1 text-[11px] font-mono text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px]">schedule</span>
                                        <span>{{ $item->created_at->diffForHumans() }}</span>
                                    </span>
                                    <span>·</span>
                                    <span>{{ $item->created_at->translatedFormat('d M Y, H:i') }} WIB</span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 shrink-0 self-center">
                            @if($url)
                                <a href="{{ route('notifications.read', $item->id) }}" 
                                   class="px-3 py-1.5 rounded-lg bg-rose-500/15 hover:bg-rose-500 text-rose-300 hover:text-white border border-rose-500/30 text-xs font-semibold transition-all flex items-center gap-1 shadow-sm">
                                    <span>Buka</span>
                                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                </a>
                            @elseif($isUnread)
                                <a href="{{ route('notifications.read', $item->id) }}" 
                                   class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium border border-slate-700 transition-all" title="Tandai Dibaca">
                                    <span class="material-symbols-outlined text-sm">done</span>
                                </a>
                            @endif

                            <form action="{{ route('notifications.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus notifikasi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-slate-800/60 transition-all" 
                                        title="Hapus Notifikasi">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($notifications->hasPages())
                <div class="p-4 border-t border-slate-800 bg-[#12151d]">
                    {{ $notifications->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
