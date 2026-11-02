@extends('layouts.app')

@section('title', 'Komunikasi Direksi & Manager Departemen')

@section('content')
<div class="w-full max-w-full flex flex-col gap-4" 
     x-data="{
        activeManagerId: {{ $activeManager ? $activeManager->id : 0 }},
        messageText: '',
        searchQuery: '',
        managerSearchQuery: '',
        isSending: false,
        isSearchOpen: false,
        replyingTo: null,
        soundEnabled: true,
        showEmojiPicker: false,
        quickEmojis: ['👍', '❤️', '🔥', '👏', '🎯', '🚀', '✅', '😂'],
        messages: {{ json_encode($messages->map(fn($m) => [
            'id' => $m->id,
            'text' => $m->message,
            'sender_id' => $m->sender_id,
            'sender_name' => $m->sender->name,
            'sender_role' => $m->sender->role,
            'sender_title' => $m->sender->employee->role_title ?? ($m->sender->role === 'manager' ? 'Head of ' . $m->sender->department : 'Direktur Utama'),
            'sender_avatar' => $m->sender->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120',
            'created_at_human' => $m->created_at->timezone('Asia/Jakarta')->format('H:i'),
            'is_me' => $m->sender_id === auth()->id()
        ])) }},
        lastId: {{ $messages->last() ? $messages->last()->id : 0 }},
        pollingInterval: null,

        get filteredMessages() {
            if (!this.searchQuery.trim()) return this.messages;
            const q = this.searchQuery.toLowerCase();
            return this.messages.filter(m => 
                (m.text && m.text.toLowerCase().includes(q)) || 
                (m.sender_name && m.sender_name.toLowerCase().includes(q))
            );
        },
        
        scrollToBottom(smooth = false) {
            this.$nextTick(() => {
                const container = this.$refs.chatContainer;
                if (container) {
                    if (smooth) {
                        container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                    } else {
                        container.scrollTop = container.scrollHeight;
                    }
                }
            });
        },

        setReply(msg) {
            this.replyingTo = {
                id: msg.id,
                sender_name: msg.sender_name,
                snippet: msg.text.length > 60 ? msg.text.substring(0, 60) + '...' : msg.text
            };
            this.$nextTick(() => {
                if (this.$refs.messageInput) {
                    this.$refs.messageInput.focus();
                }
            });
        },

        cancelReply() {
            this.replyingTo = null;
        },

        insertEmoji(emoji) {
            this.messageText += emoji;
            this.showEmojiPicker = false;
            this.$nextTick(() => {
                if (this.$refs.messageInput) {
                    this.$refs.messageInput.focus();
                }
            });
        },

        quickReact(msg, emoji) {
            this.messageText = emoji + ' ' + (msg.is_me ? '' : '@' + msg.sender_name + ' ');
            this.sendMessage();
        },

        copyMessage(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text);
            }
        },

        playNotificationSound() {
            if (!this.soundEnabled) return;
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.25);
            } catch (e) {}
        },
        
        init() {
            this.scrollToBottom();
            // Polling interval setiap 3 detik untuk pesan baru dari manager aktif
            this.pollingInterval = setInterval(() => {
                this.fetchNewMessages();
            }, 3000);
        },
        
        destroy() {
            if (this.pollingInterval) clearInterval(this.pollingInterval);
        },
        
        fetchNewMessages() {
            if (!this.activeManagerId) return;
            fetch('{{ route('chat.fetch') }}?manager_id=' + this.activeManagerId + '&after_id=' + this.lastId, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    let hasIncomingFromOthers = false;
                    data.messages.forEach(msg => {
                        this.messages.push(msg);
                        this.lastId = Math.max(this.lastId, msg.id);
                        if (!msg.is_me) hasIncomingFromOthers = true;
                    });
                    if (hasIncomingFromOthers) {
                        this.playNotificationSound();
                    }
                    this.scrollToBottom(true);
                }
            })
            .catch(() => {});
        },
        
        sendMessage() {
            if (!this.messageText.trim() || this.isSending || !this.activeManagerId) return;
            this.isSending = true;
            
            let finalContent = this.messageText.trim();
            if (this.replyingTo) {
                finalContent = `> [Balas ${this.replyingTo.sender_name}]: ${this.replyingTo.snippet}\n\n` + finalContent;
            }

            const payloadText = finalContent;
            this.messageText = '';
            this.replyingTo = null;
            
            fetch('{{ route('chat.send') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    target_manager_id: this.activeManagerId,
                    message: payloadText
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isSending = false;
                if (data.success && data.message) {
                    this.messages.push(data.message);
                    this.lastId = Math.max(this.lastId, data.message.id);
                    this.scrollToBottom(true);
                }
            })
            .catch(err => {
                this.isSending = false;
                alert('Gagal mengirim pesan, silakan coba lagi.');
            });
        }
     }">

    <!-- ==================== TOP HERO / DIREKSI HEADER ==================== -->
    <div class="w-full bg-[#141822] border border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5 min-w-0">
            <div class="relative shrink-0">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500/20 via-orange-500/10 to-rose-500/10 text-amber-400 border border-amber-500/30 flex items-center justify-center shadow-lg shadow-amber-950/40">
                    <span class="material-symbols-outlined text-2xl">supervisor_account</span>
                </span>
                <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-[#141822]"></span>
            </div>
            
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="font-headline font-extrabold text-base sm:text-lg text-white tracking-tight flex items-center gap-2">
                        <span>Saluran Komunikasi Eksekutif Direksi</span>
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30 flex items-center gap-1.5 shadow-xs">
                        <span class="material-symbols-outlined text-xs">verified_user</span>
                        <span>Jalur Khusus Direktur & Manager</span>
                    </span>
                </div>
                <p class="text-xs text-slate-400 truncate mt-0.5 font-normal">
                    Komunikasi langsung, privat, dan terenkripsi antara Direktur dengan Kepala Departemen (HR, Finance, BizDev, GA).
                </p>
            </div>
        </div>

        <!-- RIGHT HEADER CONTROLS -->
        <div class="flex items-center gap-2.5 shrink-0">
            <button @click="soundEnabled = !soundEnabled"
                    type="button"
                    class="p-2 rounded-xl border border-slate-700/60 bg-slate-900/50 text-slate-400 hover:text-white hover:bg-slate-800 transition-colors flex items-center gap-1.5 text-xs font-mono"
                    :title="soundEnabled ? 'Notifikasi Suara: Aktif' : 'Notifikasi Suara: Hening'">
                <span class="material-symbols-outlined text-base" :class="soundEnabled ? 'text-emerald-400' : 'text-slate-500'" x-text="soundEnabled ? 'volume_up' : 'volume_off'"></span>
                <span class="hidden md:inline" x-text="soundEnabled ? 'Audio On' : 'Mute'"></span>
            </button>
            
            <div class="flex items-center gap-2 text-xs font-mono text-slate-300 bg-slate-900/80 px-3 py-1.5 rounded-xl border border-slate-700/60 shadow-inner">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="font-semibold text-emerald-400">Direksi Terhubung</span>
            </div>
        </div>
    </div>

    <!-- ==================== MAIN CHAT STAGE (MANAGER SELECTOR + CHAT STREAM) ==================== -->
    <div class="w-full flex flex-col lg:flex-row gap-5 items-stretch" style="min-height: 640px;">

        <!-- LEFT SIDEBAR: DAFTAR MANAGER DEPARTEMEN -->
        <div class="w-full lg:w-80 shrink-0 bg-[#12151d] rounded-2xl border border-slate-800/80 p-5 shadow-2xl flex flex-col gap-4"
             style="width: 320px; flex-shrink: 0;"
             x-data="{ queryManager: '' }">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-slate-800 text-slate-300 border border-slate-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">manage_accounts</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-xs sm:text-sm text-white">Daftar Manager</h3>
                        <p class="text-[10px] font-mono text-slate-400">{{ $managers->count() }} Kepala Departemen</p>
                    </div>
                </div>

                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/20 font-bold">
                    1-on-1
                </span>
            </div>

            <!-- Search input manager -->
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-500 text-sm">search</span>
                <input type="text" 
                       x-model="queryManager" 
                       placeholder="Cari nama manager / divisi..." 
                       class="w-full bg-[#0d1017] border border-slate-800 focus:border-amber-500/80 rounded-xl pl-8 pr-3 py-1.5 text-xs text-white placeholder:text-slate-500 focus:outline-none transition-colors shadow-inner" />
            </div>

            <!-- List Manager Cards -->
            <div class="flex flex-col gap-2 overflow-y-auto max-h-[460px] pr-1">
                @foreach($managers as $mgr)
                    @php
                        $isSelected = $activeManager && $activeManager->id === $mgr->id;
                        $mgrNameLower = strtolower($mgr->name . ' ' . $mgr->department);
                    @endphp
                    <a href="{{ route('chat.index', ['manager_id' => $mgr->id]) }}"
                       x-show="!queryManager.trim() || '{{ $mgrNameLower }}'.includes(queryManager.toLowerCase())"
                       class="p-3 rounded-xl border transition-all flex items-center justify-between gap-2.5 group {{ $isSelected ? 'bg-gradient-to-r from-amber-950/40 to-slate-900 border-amber-500/60 ring-1 ring-amber-500/40 shadow-lg' : 'bg-[#151924] border-slate-800/90 hover:border-slate-700 hover:bg-slate-800/40' }}">
                        
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="relative shrink-0">
                                <img src="{{ $mgr->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}" 
                                     alt="{{ $mgr->name }}" 
                                     class="w-9 h-9 rounded-xl object-cover border {{ $isSelected ? 'border-amber-500/60' : 'border-slate-700' }} shadow-sm"/>
                                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-[#151924]"></span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <h5 class="font-headline font-bold text-xs truncate {{ $isSelected ? 'text-amber-300' : 'text-slate-200 group-hover:text-white' }}">
                                        {{ $mgr->name }}
                                    </h5>
                                </div>
                                <p class="text-[10px] font-mono text-slate-400 truncate">
                                    Manager {{ $mgr->department }}
                                </p>
                            </div>
                        </div>

                        <span class="px-2 py-0.5 rounded text-[8px] font-mono font-bold uppercase shrink-0 border {{ $isSelected ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-slate-800 text-slate-400 border-slate-700' }}">
                            {{ $mgr->department }}
                        </span>
                    </a>
                @endforeach
            </div>

            <!-- Kebijakan Privasi Direksi -->
            <div class="p-3 rounded-xl bg-gradient-to-br from-slate-900 to-[#121622] border border-slate-800 text-[11px] font-mono text-slate-400 space-y-1.5 mt-auto shadow-inner">
                <div class="flex items-center gap-1.5 text-amber-400 font-semibold">
                    <span class="material-symbols-outlined text-sm">lock</span>
                    <span>Privasi Percakapan</span>
                </div>
                <p class="leading-relaxed text-[10px] text-slate-400">
                    Sesuai kebijakan perusahaan, percakapan internal karyawan di setiap departemen bersifat terisolasi dan tidak dapat diakses oleh Direktur.
                </p>
            </div>

        </div>

        <!-- RIGHT: ACTIVE CHAT PANEL WITH SELECTED MANAGER -->
        <div class="flex-1 min-w-0 bg-[#12151d] rounded-2xl border border-slate-800/80 shadow-2xl flex flex-col overflow-hidden" 
             style="height: 680px; flex: 1 1 0%; min-width: 0;">
            
            @if($activeManager)
                <!-- Active Manager Header -->
                <div class="px-5 py-3.5 bg-[#151924] border-b border-slate-800 flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="relative shrink-0">
                            <img src="{{ $activeManager->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}" 
                                 alt="{{ $activeManager->name }}" 
                                 class="w-8 h-8 rounded-xl object-cover border border-amber-500/40 shadow-sm"/>
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border border-[#151924]"></span>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-headline font-bold text-xs sm:text-sm text-white truncate">
                                    {{ $activeManager->name }}
                                </h3>
                                <span class="px-2 py-0.2 rounded text-[9px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                    Manager {{ $activeManager->department }}
                                </span>
                            </div>
                            <p class="text-[10px] font-mono text-slate-400">
                                Saluran Langsung Direksi &bull; {{ $activeManager->email }}
                            </p>
                        </div>
                    </div>

                    <!-- Header Actions (Search & Scroll) -->
                    <div class="flex items-center gap-2">
                        <div class="relative flex items-center">
                            <template x-if="isSearchOpen">
                                <div class="flex items-center gap-1.5" x-transition>
                                    <input type="text" 
                                           x-model="searchQuery" 
                                           placeholder="Cari riwayat percakapan..." 
                                           class="bg-[#0b0d13] border border-slate-700 focus:border-amber-500 rounded-lg px-2.5 py-1 text-xs text-white placeholder:text-slate-500 focus:outline-none w-44 sm:w-60 shadow-inner"
                                           @keydown.escape="isSearchOpen = false; searchQuery = ''" />
                                    <button @click="isSearchOpen = false; searchQuery = ''" 
                                            type="button" 
                                            class="p-1 text-slate-400 hover:text-white rounded-md">
                                        <span class="material-symbols-outlined text-sm">close</span>
                                    </button>
                                </div>
                            </template>

                            <template x-if="!isSearchOpen">
                                <button @click="isSearchOpen = true; $nextTick(() => $el.parentNode.querySelector('input')?.focus())" 
                                        type="button"
                                        class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800/60 rounded-lg text-xs flex items-center gap-1 transition-colors border border-transparent hover:border-slate-700/60"
                                        title="Cari riwayat percakapan">
                                    <span class="material-symbols-outlined text-base">search</span>
                                    <span class="hidden sm:inline text-[11px] font-mono">Cari</span>
                                </button>
                            </template>
                        </div>

                        <button @click="scrollToBottom(true)"
                                type="button"
                                class="p-1.5 text-slate-400 hover:text-amber-400 hover:bg-slate-800/60 rounded-lg text-xs flex items-center transition-colors"
                                title="Gulir ke Pesan Terbaru">
                            <span class="material-symbols-outlined text-base">arrow_downward</span>
                        </button>
                    </div>
                </div>

                <!-- CHAT STREAM SCROLL CONTAINER -->
                <div class="flex-1 p-4 sm:p-6 overflow-y-auto space-y-4 bg-gradient-to-b from-[#11131a] via-[#12151d] to-[#0f1118]" 
                     x-ref="chatContainer">
                    
                    <!-- Welcome Banner -->
                    <div class="p-4 rounded-2xl bg-gradient-to-br from-[#1e1b24] to-[#14161f] border border-amber-500/20 text-center max-w-lg mx-auto shadow-xl">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/30 mx-auto flex items-center justify-center mb-2 shadow-inner">
                            <span class="material-symbols-outlined text-xl">encrypted</span>
                        </div>
                        <h5 class="font-headline font-bold text-xs sm:text-sm text-white">Saluran Komunikasi Langsung Direksi</h5>
                        <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                            Pesan yang dikirimkan di sini hanya dapat dibaca oleh Anda (Direktur) dan <strong class="text-amber-300">{{ $activeManager->name }}</strong> (Manager {{ $activeManager->department }}).
                        </p>
                    </div>

                    <!-- Empty State jika belum ada percakapan -->
                    <template x-if="filteredMessages.length === 0">
                        <div class="py-16 text-center flex flex-col items-center justify-center">
                            <div class="w-16 h-16 rounded-2xl bg-slate-800/40 border border-slate-700/60 flex items-center justify-center mb-3">
                                <span class="material-symbols-outlined text-3xl text-slate-500">chat</span>
                            </div>
                            <h4 class="font-headline font-bold text-sm text-slate-300" x-text="searchQuery.trim() ? 'Pesan tidak ditemukan' : 'Belum ada percakapan langsung'"></h4>
                            <p class="text-xs text-slate-500 mt-1 max-w-sm" x-text="searchQuery.trim() ? 'Coba kata kunci lain.' : 'Mulai diskusi atau berikan instruksi strategis kepada Manager {{ $activeManager->department }} sekarang.'"></p>
                        </div>
                    </template>

                    <!-- LIST OF MESSAGES (BUBBLES) -->
                    <template x-for="item in filteredMessages" :key="item.id">
                        <div class="group relative flex items-start gap-3 transition-all"
                             :class="item.is_me ? 'flex-row-reverse' : 'flex-row'">
                            
                            <!-- Avatar Pengirim -->
                            <div class="relative shrink-0 pt-0.5">
                                <img :src="item.sender_avatar" 
                                     :alt="item.sender_name" 
                                     class="w-9 h-9 rounded-xl object-cover border shadow-sm"
                                     :class="item.is_me ? 'border-amber-500/50 ring-2 ring-amber-500/20' : 'border-rose-500/50 ring-2 ring-rose-500/20'" />
                            </div>

                            <!-- Bubble & Info -->
                            <div class="max-w-[85%] sm:max-w-[75%] space-y-1"
                                 :class="item.is_me ? 'items-end text-right' : 'items-start text-left'">
                                
                                <div class="flex items-center gap-1.5 px-1"
                                     :class="item.is_me ? 'justify-end' : 'justify-start'">
                                    <span class="font-headline font-bold text-xs text-slate-200" x-text="item.sender_name"></span>
                                    
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold uppercase tracking-wider"
                                          :class="item.is_me ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'"
                                          x-text="item.sender_role">
                                    </span>

                                    <span class="text-[10px] font-mono text-slate-500" x-text="item.created_at_human"></span>
                                </div>

                                <div class="relative rounded-2xl p-3.5 text-xs sm:text-[13px] leading-relaxed break-words shadow-md transition-all group-hover:shadow-lg"
                                     :class="item.is_me 
                                        ? 'bg-gradient-to-br from-amber-600 via-amber-600 to-orange-600 text-white rounded-tr-xs shadow-amber-950/50' 
                                        : 'bg-[#191e2b] border border-slate-700/80 text-slate-100 rounded-tl-xs shadow-black/40'">
                                    
                                    <p class="whitespace-pre-wrap selection:bg-black/30 font-normal" x-text="item.text"></p>
                                    
                                    <div class="mt-1.5 flex items-center gap-1 justify-end text-[9px] font-mono opacity-70"
                                         :class="item.is_me ? 'text-amber-100' : 'text-slate-400'">
                                        <span x-text="item.created_at_human"></span>
                                        <template x-if="item.is_me">
                                            <span class="material-symbols-outlined text-[11px] text-white">done_all</span>
                                        </template>
                                    </div>

                                    <!-- Action toolbar -->
                                    <div class="absolute -top-3.5 opacity-0 group-hover:opacity-100 transition-all duration-100 flex items-center gap-1 bg-[#1c2233] border border-slate-700 rounded-xl px-1.5 py-1 shadow-xl z-10"
                                         :class="item.is_me ? 'left-2' : 'right-2'">
                                        <button @click="quickReact(item, '👍')" type="button" class="text-xs hover:scale-125 transition-transform p-0.5">👍</button>
                                        <button @click="quickReact(item, '❤️')" type="button" class="text-xs hover:scale-125 transition-transform p-0.5">❤️</button>
                                        <button @click="quickReact(item, '🔥')" type="button" class="text-xs hover:scale-125 transition-transform p-0.5">🔥</button>
                                        <span class="w-px h-3 bg-slate-700 mx-0.5"></span>
                                        <button @click="setReply(item)" type="button" class="p-1 text-slate-400 hover:text-white rounded"><span class="material-symbols-outlined text-xs">reply</span></button>
                                        <button @click="copyMessage(item.text)" type="button" class="p-1 text-slate-400 hover:text-white rounded"><span class="material-symbols-outlined text-xs">content_copy</span></button>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </template>

                </div>

                <!-- REPLY BANNER -->
                <template x-if="replyingTo">
                    <div class="px-5 py-2.5 bg-[#171d2c] border-t border-amber-500/30 flex items-center justify-between gap-3 text-xs shrink-0">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="material-symbols-outlined text-sm text-amber-400">reply</span>
                            <div class="min-w-0">
                                <span class="font-bold text-slate-200">Membalas <span class="text-amber-400" x-text="replyingTo.sender_name"></span>:</span>
                                <span class="text-slate-400 truncate italic ml-1" x-text="'&ldquo;' + replyingTo.snippet + '&rdquo;'"></span>
                            </div>
                        </div>
                        <button @click="cancelReply()" type="button" class="p-1 text-slate-400 hover:text-white rounded-lg">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </button>
                    </div>
                </template>

                <!-- INPUT BOX & CONTROLS FOOTER -->
                <div class="p-4 bg-[#141822] border-t border-slate-800/80 shrink-0 relative">
                    <div x-show="showEmojiPicker" 
                         @click.away="showEmojiPicker = false"
                         x-cloak
                         class="absolute bottom-full left-6 mb-2 p-2 bg-[#1a2030] border border-slate-700 rounded-2xl shadow-2xl flex items-center gap-1.5 z-20">
                        <template x-for="emo in quickEmojis" :key="emo">
                            <button @click="insertEmoji(emo)" type="button" class="w-8 h-8 rounded-xl hover:bg-slate-700/60 flex items-center justify-center text-base hover:scale-125 transition-transform" x-text="emo"></button>
                        </template>
                    </div>

                    <form @submit.prevent="sendMessage()" class="flex flex-col gap-2">
                        <div class="flex items-end gap-2 bg-[#0d1017] border border-slate-700/80 focus-within:border-amber-500/80 rounded-2xl p-2 transition-all shadow-inner focus-within:ring-2 focus-within:ring-amber-500/20">
                            
                            <button @click="showEmojiPicker = !showEmojiPicker"
                                    type="button"
                                    class="p-2 text-slate-400 hover:text-amber-400 hover:bg-slate-800/60 rounded-xl transition-colors shrink-0"
                                    title="Pilih Emoji Cepat">
                                <span class="material-symbols-outlined text-xl">mood</span>
                            </button>

                            <textarea x-ref="messageInput"
                                      x-model="messageText" 
                                      @keydown.enter.prevent="if(!$event.shiftKey) { sendMessage(); }"
                                      rows="2"
                                      placeholder="Tulis instruksi atau pesan untuk Manager {{ $activeManager->name }} ({{ $activeManager->department }})..." 
                                      class="flex-1 bg-transparent border-0 text-xs sm:text-[13px] text-white placeholder:text-slate-500 focus:outline-none resize-none leading-relaxed py-1.5 px-1"></textarea>

                            <button type="submit" 
                                    :disabled="isSending || !messageText.trim()"
                                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold flex items-center gap-1.5 transition-all shadow-lg shadow-amber-950/50 active:scale-95 shrink-0">
                                <template x-if="isSending">
                                    <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                                </template>
                                <template x-if="!isSending">
                                    <span class="material-symbols-outlined text-sm">send</span>
                                </template>
                                <span class="hidden sm:inline">Kirim</span>
                            </button>
                        </div>

                        <div class="flex items-center justify-between px-1 text-[10px] font-mono text-slate-500">
                            <div class="flex items-center gap-2">
                                <span>Tekan <kbd class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 border border-slate-700 font-sans">Enter</kbd> untuk mengirim</span>
                                <span>•</span>
                                <span><kbd class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 border border-slate-700 font-sans">Shift + Enter</kbd> baris baru</span>
                            </div>
                            <span x-text="messageText.length + '/2000'"></span>
                        </div>
                    </form>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center text-slate-400">
                    <span class="material-symbols-outlined text-5xl text-slate-600 mb-3">person_off</span>
                    <h3 class="font-headline font-bold text-white text-base">Belum Ada Manager Terpilih</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">Pilih salah satu manager di sebelah kiri untuk memulai obrolan langsung.</p>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection
