@extends('layouts.app')

@section('title', 'Chat Departemen - ' . $activeDepartment)

@section('content')
<div class="w-full max-w-full flex flex-col gap-4" 
     x-data="{
        activeDepartment: '{{ $activeDepartment }}',
        messageText: '',
        searchQuery: '',
        memberSearchQuery: '',
        filterRole: 'all',
        isSending: false,
        isSearchOpen: false,
        replyingTo: null,
        soundEnabled: true,
        showEmojiPicker: false,
        quickEmojis: ['👍', '❤️', '🔥', '👏', '🎯', '🚀', '✅', '😂'],
        messages: {{ json_encode($messages->map(fn($m) => [
            'id' => $m->id,
            'text' => $m->message,
            'sender_id' => $m->user_id,
            'sender_name' => $m->user->name,
            'sender_role' => $m->user->role,
            'sender_title' => $m->user->employee->role_title ?? ($m->user->role === 'manager' ? 'Head of Department' : 'Staff'),
            'sender_avatar' => $m->user->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120',
            'created_at_human' => $m->created_at->timezone('Asia/Jakarta')->format('H:i'),
            'created_at_full' => $m->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i'),
            'is_me' => $m->user_id === auth()->id()
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
            // Langsung kirim emoji sebagai balasan cepat
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
                osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
                osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08); // A5
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
            // Polling interval setiap 3 detik untuk pesan baru
            this.pollingInterval = setInterval(() => {
                this.fetchNewMessages();
            }, 3000);
        },
        
        destroy() {
            if (this.pollingInterval) clearInterval(this.pollingInterval);
        },
        
        fetchNewMessages() {
            fetch('{{ route('chat.fetch') }}?department=' + encodeURIComponent(this.activeDepartment) + '&after_id=' + this.lastId, {
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
            if (!this.messageText.trim() || this.isSending) return;
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
                    department: this.activeDepartment,
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

    <!-- ==================== TOP HERO / CHANNEL HEADER ==================== -->
    <div class="w-full bg-[#141822] border border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5 min-w-0">
            <div class="relative shrink-0">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-600/20 via-pink-600/10 to-amber-500/10 text-rose-400 border border-rose-500/30 flex items-center justify-center shadow-lg shadow-rose-950/40">
                    <span class="material-symbols-outlined text-2xl">forum</span>
                </span>
                <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-[#141822]"></span>
            </div>
            
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="font-headline font-extrabold text-base sm:text-lg text-white tracking-tight flex items-center gap-2">
                        <span># {{ $activeDepartment }}</span>
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 flex items-center gap-1.5 shadow-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                        <span>{{ $members->count() }} Anggota Tim</span>
                    </span>
                    <span class="hidden sm:inline-flex px-2 py-0.5 rounded-full text-[10px] font-mono bg-slate-800/80 text-slate-400 border border-slate-700/60">
                        Ruang Resmi Divisi
                    </span>
                </div>
                <p class="text-xs text-slate-400 truncate mt-0.5 font-normal">
                    Saluran koordinasi operasional & komunikasi cepat internal Departemen {{ $activeDepartment }}.
                </p>
            </div>
        </div>

        <!-- RIGHT HEADER CONTROLS (Tab Komunikasi Direksi jika Manager, Audio toggle, Status) -->
        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            @if(auth()->user()->isManager())
                <!-- Dropdown Pilihan Chat untuk Manager -->
                <div class="relative" x-data="{ openDropdown: false }">
                    <button @click="openDropdown = !openDropdown" 
                            type="button"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold font-mono bg-gradient-to-r from-rose-600/20 to-orange-600/20 hover:from-rose-600/30 hover:to-orange-600/30 text-rose-300 border border-rose-500/40 transition-all flex items-center gap-2 shadow-sm active:scale-95">
                        <span class="material-symbols-outlined text-sm text-cyan-400">groups</span>
                        <span>Chat Departemen</span>
                        <span class="material-symbols-outlined text-sm transition-transform duration-100" :class="openDropdown ? 'rotate-180' : ''">arrow_drop_down</span>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="openDropdown" 
                         @click.away="openDropdown = false" 
                         x-cloak
                         class="absolute right-0 mt-2 w-56 rounded-2xl bg-[#141822] border border-slate-700 shadow-2xl z-50 overflow-hidden py-1.5">
                        <div class="px-3 py-1.5 text-[10px] font-mono text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-800">
                            Pilih Saluran Obrolan
                        </div>
                        
                        <!-- 1. Chat Departemen (Aktif) -->
                        <a href="{{ route('chat.index') }}" 
                           class="flex items-center justify-between px-3.5 py-2.5 text-xs text-white bg-rose-500/15 font-semibold transition-colors">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-sm text-cyan-400">groups</span>
                                <span>Chat Departemen</span>
                            </div>
                            <span class="material-symbols-outlined text-xs text-rose-400">check</span>
                        </a>

                        <!-- 2. Chat Direksi -->
                        <a href="{{ route('chat.index', ['tab' => 'direksi']) }}" 
                           class="flex items-center justify-between px-3.5 py-2.5 text-xs text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                            <div class="flex items-center gap-2.5">
                                <span class="material-symbols-outlined text-sm text-amber-400">shield_person</span>
                                <span>Chat Direksi</span>
                            </div>
                            @if(isset($directorUnreadCount) && $directorUnreadCount > 0)
                                <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-500 text-black">
                                    {{ $directorUnreadCount }}
                                </span>
                            @endif
                        </a>
                    </div>
                </div>
            @endif

            <button @click="soundEnabled = !soundEnabled"
                    type="button"
                    class="p-2 rounded-xl border border-slate-700/60 bg-slate-900/50 text-slate-400 hover:text-white hover:bg-slate-800 transition-colors flex items-center gap-1.5 text-xs font-mono"
                    :title="soundEnabled ? 'Notifikasi Suara: Aktif' : 'Notifikasi Suara: Hening'">
                <span class="material-symbols-outlined text-base" :class="soundEnabled ? 'text-emerald-400' : 'text-slate-500'" x-text="soundEnabled ? 'volume_up' : 'volume_off'"></span>
                <span class="hidden md:inline" x-text="soundEnabled ? 'Audio On' : 'Mute'"></span>
            </button>
            
            <div class="flex items-center gap-2 text-xs font-mono text-slate-300 bg-slate-900/80 px-3 py-1.5 rounded-xl border border-slate-700/60 shadow-inner">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="font-semibold text-emerald-400">Live Terhubung</span>
            </div>
        </div>
    </div>

    <!-- ==================== MAIN CHAT STAGE (FULL WIDTH FLEX DESKTOP) ==================== -->
    <div class="w-full flex flex-col lg:flex-row gap-5 items-stretch" style="min-height: 640px;">

        <!-- LEFT/CENTER: MAIN CHAT PANEL (Stretches across all available space) -->
        <div class="flex-1 min-w-0 bg-[#12151d] rounded-2xl border border-slate-800/80 shadow-2xl flex flex-col overflow-hidden" 
             style="height: 680px; flex: 1 1 0%; min-width: 0;">
            
            <!-- Chat Action Bar (Search Toggle, Status, Messages Counter) -->
            <div class="px-5 py-3 bg-[#151924] border-b border-slate-800 flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/80"></span>
                        <span class="font-headline font-bold text-xs text-slate-200 tracking-wide">
                            Pesan Tim Terenkripsi
                        </span>
                    </div>

                    <span class="text-slate-600">|</span>

                    <span class="text-[11px] font-mono text-slate-400 hidden sm:inline" x-text="messages.length + ' pesan tersimpan'"></span>
                </div>

                <!-- Chat Header Actions (Search & Clear) -->
                <div class="flex items-center gap-2">
                    <!-- Search Input Toggle -->
                    <div class="relative flex items-center">
                        <template x-if="isSearchOpen">
                            <div class="flex items-center gap-1.5" x-transition>
                                <input type="text" 
                                       x-model="searchQuery" 
                                       placeholder="Cari pesan atau nama..." 
                                       class="bg-[#0b0d13] border border-slate-700 focus:border-rose-500 rounded-lg px-2.5 py-1 text-xs text-white placeholder:text-slate-500 focus:outline-none w-44 sm:w-60 shadow-inner"
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
                                <span class="hidden sm:inline text-[11px] font-mono">Cari Pesan</span>
                            </button>
                        </template>
                    </div>

                    <button @click="scrollToBottom(true)"
                            type="button"
                            class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800/60 rounded-lg text-xs flex items-center transition-colors"
                            title="Gulir ke Pesan Terbaru">
                        <span class="material-symbols-outlined text-base">arrow_downward</span>
                    </button>
                </div>
            </div>

            <!-- SEARCH ACTIVE INDICATOR -->
            <template x-if="searchQuery.trim()">
                <div class="px-5 py-2 bg-rose-950/30 border-b border-rose-500/20 flex items-center justify-between text-xs text-rose-300">
                    <span class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">filter_alt</span>
                        <span>Menampilkan hasil pencarian untuk: "<strong x-text="searchQuery"></strong>" (<span x-text="filteredMessages.length"></span> ditemukan)</span>
                    </span>
                    <button @click="searchQuery = ''" class="text-[10px] font-mono uppercase underline text-rose-400 hover:text-rose-200">Reset Filter</button>
                </div>
            </template>

            <!-- CHAT STREAM SCROLL CONTAINER -->
            <div class="flex-1 p-4 sm:p-6 overflow-y-auto space-y-4 bg-gradient-to-b from-[#11131a] via-[#12151d] to-[#0f1118]" 
                 x-ref="chatContainer">
                
                <!-- Welcome Banner -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-[#161b28] to-[#12151e] border border-cyan-500/20 text-center max-w-lg mx-auto shadow-xl">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/30 mx-auto flex items-center justify-center mb-2 shadow-inner">
                        <span class="material-symbols-outlined text-xl">shield_lock</span>
                    </div>
                    <h5 class="font-headline font-bold text-xs sm:text-sm text-white">Saluran Diskusi Terenkripsi & Terpusat</h5>
                    <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                        Pesan di ruang ini khusus dapat diakses oleh anggota resmi Departemen <strong class="text-cyan-300">{{ $activeDepartment }}</strong>. Seluruh riwayat percakapan tersinkronisasi secara real-time.
                    </p>
                </div>

                <!-- Empty State jika belum ada pesan sama sekali -->
                <template x-if="filteredMessages.length === 0">
                    <div class="py-16 text-center flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-2xl bg-slate-800/40 border border-slate-700/60 flex items-center justify-center mb-3">
                            <span class="material-symbols-outlined text-3xl text-slate-500">mark_chat_unread</span>
                        </div>
                        <h4 class="font-headline font-bold text-sm text-slate-300" x-text="searchQuery.trim() ? 'Pesan tidak ditemukan' : 'Belum ada percakapan'"></h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm" x-text="searchQuery.trim() ? 'Coba gunakan kata kunci pencarian yang lain.' : 'Jadilah yang pertama membuka diskusi atau memberikan briefing tim hari ini!'"></p>
                    </div>
                </template>

                <!-- LIST OF MESSAGES (BUBBLES) -->
                <template x-for="(item, index) in filteredMessages" :key="item.id">
                    <div class="group relative flex items-start gap-3 transition-all"
                         :class="item.is_me ? 'flex-row-reverse' : 'flex-row'">
                        
                        <!-- AVATAR PENGIRIM -->
                        <div class="relative shrink-0 pt-0.5">
                            <img :src="item.sender_avatar" 
                                 :alt="item.sender_name" 
                                 class="w-9 h-9 rounded-xl object-cover border shadow-sm"
                                 :class="item.is_me ? 'border-rose-500/50 ring-2 ring-rose-500/20' : 'border-slate-700 ring-2 ring-slate-800'" />
                            <template x-if="item.sender_role === 'manager'">
                                <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-rose-600 border border-[#141822] flex items-center justify-center text-[7px] text-white font-bold" title="Manager">
                                    ★
                                </span>
                            </template>
                        </div>

                        <!-- BUBBLE & SENDER INFO -->
                        <div class="max-w-[85%] sm:max-w-[75%] space-y-1"
                             :class="item.is_me ? 'items-end text-right' : 'items-start text-left'">
                            
                            <!-- Header Nama & Role Pengirim -->
                            <div class="flex items-center gap-1.5 px-1"
                                 :class="item.is_me ? 'justify-end' : 'justify-start'">
                                <span class="font-headline font-bold text-xs text-slate-200" x-text="item.sender_name"></span>
                                
                                <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold uppercase tracking-wider"
                                      :class="{
                                        'bg-rose-500/20 text-rose-300 border border-rose-500/30': item.sender_role === 'manager',
                                        'bg-amber-500/20 text-amber-300 border border-amber-500/30': item.sender_role === 'direktur',
                                        'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': item.sender_role === 'karyawan'
                                      }"
                                      x-text="item.sender_role">
                                </span>

                                <span class="text-[10px] font-mono text-slate-500" x-text="item.created_at_human"></span>
                            </div>

                            <!-- Bubble Pesan -->
                            <div class="relative rounded-2xl p-3.5 text-xs sm:text-[13px] leading-relaxed break-words shadow-md transition-all group-hover:shadow-lg"
                                 :class="item.is_me 
                                    ? 'bg-gradient-to-br from-rose-600 via-rose-600 to-rose-700 text-white rounded-tr-xs shadow-rose-950/50' 
                                    : 'bg-[#191e2b] border border-slate-700/80 text-slate-100 rounded-tl-xs shadow-black/40'">
                                
                                <!-- Content Text (Preserves Line breaks and quotes) -->
                                <p class="whitespace-pre-wrap selection:bg-black/30 font-normal" x-text="item.text"></p>
                                
                                <!-- Footer Bubble (Waktu detail & Status) -->
                                <div class="mt-1.5 flex items-center gap-1 justify-end text-[9px] font-mono opacity-70"
                                     :class="item.is_me ? 'text-rose-100' : 'text-slate-400'">
                                    <span x-text="item.created_at_human"></span>
                                    <template x-if="item.is_me">
                                        <span class="material-symbols-outlined text-[11px] text-white">done_all</span>
                                    </template>
                                </div>

                                <!-- HOVER ACTION POPUP TOOLBAR (Quick Reply, Emoji, Copy) -->
                                <div class="absolute -top-3.5 opacity-0 group-hover:opacity-100 transition-all duration-100 flex items-center gap-1 bg-[#1c2233] border border-slate-700 rounded-xl px-1.5 py-1 shadow-xl z-10"
                                     :class="item.is_me ? 'left-2' : 'right-2'">
                                    
                                    <!-- Quick React Emojis -->
                                    <button @click="quickReact(item, '👍')" type="button" class="text-xs hover:scale-125 transition-transform p-0.5" title="Acungkan jempol">👍</button>
                                    <button @click="quickReact(item, '❤️')" type="button" class="text-xs hover:scale-125 transition-transform p-0.5" title="Suka">❤️</button>
                                    <button @click="quickReact(item, '🔥')" type="button" class="text-xs hover:scale-125 transition-transform p-0.5" title="Keren">🔥</button>
                                    
                                    <span class="w-px h-3 bg-slate-700 mx-0.5"></span>

                                    <!-- Reply Button -->
                                    <button @click="setReply(item)" type="button" class="p-1 text-slate-400 hover:text-white hover:bg-slate-700/50 rounded flex items-center" title="Balas pesan ini">
                                        <span class="material-symbols-outlined text-xs">reply</span>
                                    </button>

                                    <!-- Copy Button -->
                                    <button @click="copyMessage(item.text)" type="button" class="p-1 text-slate-400 hover:text-white hover:bg-slate-700/50 rounded flex items-center" title="Salin teks">
                                        <span class="material-symbols-outlined text-xs">content_copy</span>
                                    </button>
                                </div>

                            </div>
                        </div>

                    </div>
                </template>

            </div>

            <!-- REPLY CONTEXT PREVIEW BANNER -->
            <template x-if="replyingTo">
                <div class="px-5 py-2.5 bg-[#171d2c] border-t border-rose-500/30 flex items-center justify-between gap-3 text-xs shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="material-symbols-outlined text-sm text-rose-400">reply</span>
                        <div class="min-w-0">
                            <span class="font-bold text-slate-200">Membalas <span class="text-rose-400" x-text="replyingTo.sender_name"></span>:</span>
                            <span class="text-slate-400 truncate italic ml-1" x-text="'&ldquo;' + replyingTo.snippet + '&rdquo;'"></span>
                        </div>
                    </div>
                    <button @click="cancelReply()" type="button" class="p-1 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg shrink-0">
                        <span class="material-symbols-outlined text-sm">close</span>
                    </button>
                </div>
            </template>

            <!-- INPUT BOX & CONTROLS FOOTER -->
            <div class="p-4 bg-[#141822] border-t border-slate-800/80 shrink-0 relative">
                
                <!-- QUICK EMOJI BAR POPUP -->
                <div x-show="showEmojiPicker" 
                     @click.away="showEmojiPicker = false"
                     x-cloak
                     class="absolute bottom-full left-6 mb-2 p-2 bg-[#1a2030] border border-slate-700 rounded-2xl shadow-2xl flex items-center gap-1.5 z-20">
                    <template x-for="emo in quickEmojis" :key="emo">
                        <button @click="insertEmoji(emo)" type="button" class="w-8 h-8 rounded-xl hover:bg-slate-700/60 flex items-center justify-center text-base hover:scale-125 transition-transform" x-text="emo"></button>
                    </template>
                </div>

                <form @submit.prevent="sendMessage()" class="flex flex-col gap-2">
                    <div class="flex items-end gap-2 bg-[#0d1017] border border-slate-700/80 focus-within:border-rose-500/80 rounded-2xl p-2 transition-all shadow-inner focus-within:ring-2 focus-within:ring-rose-500/20">
                        
                        <!-- Emoji Picker Button -->
                        <button @click="showEmojiPicker = !showEmojiPicker"
                                type="button"
                                class="p-2 text-slate-400 hover:text-amber-400 hover:bg-slate-800/60 rounded-xl transition-colors shrink-0"
                                title="Pilih Emoji Cepat">
                            <span class="material-symbols-outlined text-xl">mood</span>
                        </button>

                        <!-- Textarea Message Input -->
                        <textarea x-ref="messageInput"
                                  x-model="messageText" 
                                  @keydown.enter.prevent="if(!$event.shiftKey) { sendMessage(); }"
                                  rows="2"
                                  placeholder="Tulis pesan ke rekan departemen {{ $activeDepartment }}... (Enter kirim, Shift+Enter baris baru)" 
                                  class="flex-1 bg-transparent border-0 text-xs sm:text-[13px] text-white placeholder:text-slate-500 focus:outline-none resize-none leading-relaxed py-1.5 px-1"></textarea>

                        <!-- Send Button -->
                        <button type="submit" 
                                :disabled="isSending || !messageText.trim()"
                                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold flex items-center gap-1.5 transition-all shadow-lg shadow-rose-950/50 active:scale-95 shrink-0">
                            <template x-if="isSending">
                                <span class="material-symbols-outlined text-sm animate-spin">progress_activity</span>
                            </template>
                            <template x-if="!isSending">
                                <span class="material-symbols-outlined text-sm">send</span>
                            </template>
                            <span class="hidden sm:inline">Kirim</span>
                        </button>
                    </div>

                    <!-- Input Tips Footer -->
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

        </div>

        <!-- ==================== RIGHT SIDEBAR: ANGGOTA DEPARTEMEN ==================== -->
        <div class="w-full lg:w-80 shrink-0 bg-[#12151d] rounded-2xl border border-slate-800/80 p-5 shadow-2xl flex flex-col gap-4"
             style="width: 320px; flex-shrink: 0;"
             x-data="{ memberQuery: '' }">
            
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-slate-800 text-slate-300 border border-slate-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">groups</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-xs sm:text-sm text-white">Anggota Tim</h3>
                        <p class="text-[10px] font-mono text-slate-400">{{ $members->count() }} Terdaftar</p>
                    </div>
                </div>

                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-300 border border-rose-500/20 font-bold">
                    {{ $activeDepartment }}
                </span>
            </div>

            <!-- Member Search Input -->
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-500 text-sm">search</span>
                <input type="text" 
                       x-model="memberQuery" 
                       placeholder="Cari anggota tim..." 
                       class="w-full bg-[#0d1017] border border-slate-800 focus:border-rose-500/80 rounded-xl pl-8 pr-3 py-1.5 text-xs text-white placeholder:text-slate-500 focus:outline-none transition-colors shadow-inner" />
            </div>

            <!-- List Members with Role Indicators -->
            <div class="flex flex-col gap-2 overflow-y-auto max-h-[460px] pr-1">
                @foreach($members as $m)
                    @php
                        $isCurrent = $m->id === auth()->id();
                        $roleBadge = match($m->role) {
                            'manager' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                            'direktur' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                            default => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30'
                        };
                        $memberNameLower = strtolower($m->name);
                    @endphp
                    <div x-show="!memberQuery.trim() || '{{ $memberNameLower }}'.includes(memberQuery.toLowerCase())"
                         class="p-2.5 rounded-xl bg-[#151924] border border-slate-800/90 hover:border-slate-700 transition-all flex items-center justify-between gap-2.5 group {{ $isCurrent ? 'ring-1 ring-rose-500/50 bg-rose-950/10' : '' }}">
                        
                        <div class="flex items-center gap-2.5 min-w-0">
                            <!-- Avatar -->
                            <div class="relative shrink-0">
                                <img src="{{ $m->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}" 
                                     alt="{{ $m->name }}" 
                                     class="w-8 h-8 rounded-xl object-cover border border-slate-700 shadow-sm"/>
                                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-[#151924]"></span>
                            </div>

                            <!-- Info -->
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <h5 class="font-headline font-bold text-xs text-slate-200 truncate group-hover:text-white transition-colors">
                                        {{ $m->name }}
                                    </h5>
                                    @if($isCurrent)
                                        <span class="text-[8px] font-mono text-rose-400 font-bold uppercase">(Saya)</span>
                                    @endif
                                </div>
                                <p class="text-[10px] font-mono text-slate-400 truncate">
                                    {{ $m->employee->role_title ?? ($m->role === 'manager' ? 'Head of Department' : 'Staff') }}
                                </p>
                            </div>
                        </div>

                        <!-- Role Badge -->
                        <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold uppercase shrink-0 border {{ $roleBadge }}">
                            {{ $m->role }}
                        </span>
                    </div>
                @endforeach
            </div>

            <!-- Department Info Card -->
            <div class="p-3 rounded-xl bg-gradient-to-br from-slate-900 to-[#121622] border border-slate-800 text-[11px] font-mono text-slate-400 space-y-1.5 mt-auto shadow-inner">
                <div class="flex items-center gap-1.5 text-cyan-400 font-semibold">
                    <span class="material-symbols-outlined text-sm">notifications_active</span>
                    <span>Push Notifikasi Otomatis</span>
                </div>
                <p class="leading-relaxed text-[10px] text-slate-400">
                    Setiap pesan yang Anda kirim akan langsung memicu notifikasi sistem ke seluruh manager & karyawan pada divisi {{ $activeDepartment }}.
                </p>
            </div>

        </div>

    </div>

</div>
@endsection
