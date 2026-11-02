@extends('layouts.app')

@section('title', 'Saluran Langsung Direksi')

@section('content')
<div class="w-full max-w-full flex flex-col gap-4" 
     x-data="{
        messageText: '',
        searchQuery: '',
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
            this.pollingInterval = setInterval(() => {
                this.fetchNewMessages();
            }, 3000);
        },
        
        destroy() {
            if (this.pollingInterval) clearInterval(this.pollingInterval);
        },
        
        fetchNewMessages() {
            fetch('{{ route('chat.fetch') }}?tab=direksi&after_id=' + this.lastId, {
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
                    is_direct: true,
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

    <!-- ==================== TOP HERO / CHANNEL HEADER (MANAGER KE SEKRETARIS DIREKTUR) ==================== -->
    <div class="w-full bg-[#141822] border border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5 min-w-0">
            <div class="relative shrink-0">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500/20 via-orange-500/10 to-rose-500/10 text-amber-400 border border-amber-500/30 flex items-center justify-center shadow-lg shadow-amber-950/40">
                    <span class="material-symbols-outlined text-2xl">support_agent</span>
                </span>
                <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-[#141822]"></span>
            </div>
            
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="font-headline font-extrabold text-base sm:text-lg text-white tracking-tight flex items-center gap-2">
                        <span>Jalur Eksekutif Sekretaris Direktur</span>
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30 flex items-center gap-1.5 shadow-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>Manager {{ auth()->user()->department }} &bull; Sekretaris Direktur</span>
                    </span>
                    <span class="hidden sm:inline-flex px-2 py-0.5 rounded-full text-[10px] font-mono bg-slate-800/80 text-slate-400 border border-slate-700/60">
                        Privat & Terverifikasi
                    </span>
                </div>
                <p class="text-xs text-slate-400 truncate mt-0.5 font-normal">
                    Saluran koordinasi eksekutif antara Anda selaku Manager {{ auth()->user()->department }} dengan Sekretaris Direktur Utama (Seluruh komunikasi dan pengajuan eksekutif diteruskan melalui Sekretaris).
                </p>
            </div>
        </div>

        <!-- RIGHT HEADER CONTROLS (Dropdown Saluran Chat & Audio status) -->
        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <!-- Dropdown Pilihan Chat untuk Manager -->
            <div class="relative" x-data="{ openDropdown: false }">
                <button @click="openDropdown = !openDropdown" 
                        type="button"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold font-mono bg-gradient-to-r from-amber-600/20 to-orange-600/20 hover:from-amber-600/30 hover:to-orange-600/30 text-amber-300 border border-amber-500/40 transition-all flex items-center gap-2 shadow-sm active:scale-95">
                    <span class="material-symbols-outlined text-sm text-amber-400">support_agent</span>
                    <span>Chat Sekretaris</span>
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
                    
                    <!-- 1. Chat Departemen -->
                    <a href="{{ route('chat.index') }}" 
                       class="flex items-center justify-between px-3.5 py-2.5 text-xs text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-sm text-cyan-400">groups</span>
                            <span>Chat Departemen</span>
                        </div>
                    </a>

                    <!-- 2. Chat Sekretaris Direktur (Aktif) -->
                    <a href="{{ route('chat.index', ['tab' => 'direksi']) }}" 
                       class="flex items-center justify-between px-3.5 py-2.5 text-xs text-white bg-amber-500/15 font-semibold transition-colors">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-sm text-amber-400">support_agent</span>
                            <span>Sekretaris Direktur</span>
                        </div>
                        <span class="material-symbols-outlined text-xs text-amber-400">check</span>
                    </a>
                </div>
            </div>

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

    <!-- ==================== MAIN CHAT STAGE ==================== -->
    <div class="w-full bg-[#12151d] rounded-2xl border border-slate-800/80 shadow-2xl flex flex-col overflow-hidden" 
         style="height: 680px;">
        
        <!-- Header -->
        <div class="px-5 py-3.5 bg-[#151924] border-b border-slate-800 flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="relative shrink-0">
                    <img src="{{ $director->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}" 
                         alt="{{ $director->name ?? 'Direktur' }}" 
                         class="w-9 h-9 rounded-xl object-cover border border-amber-500/50 shadow-sm"/>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-[#151924]"></span>
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-headline font-bold text-xs sm:text-sm text-white truncate">
                            {{ $director->name ?? 'Hendra Wijaya (Direktur)' }}
                        </h3>
                        <span class="px-2 py-0.2 rounded text-[9px] font-mono font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                            Direktur Utama
                        </span>
                    </div>
                    <p class="text-[10px] font-mono text-slate-400">
                        Saluran Komunikasi Privat Khusus Anda & Direktur
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <div class="relative flex items-center">
                    <template x-if="isSearchOpen">
                        <div class="flex items-center gap-1.5" x-transition>
                            <input type="text" 
                                   x-model="searchQuery" 
                                   placeholder="Cari pesan..." 
                                   class="bg-[#0b0d13] border border-slate-700 focus:border-amber-500 rounded-lg px-2.5 py-1 text-xs text-white placeholder:text-slate-500 focus:outline-none w-44 sm:w-60 shadow-inner"
                                   @keydown.escape="isSearchOpen = false; searchQuery = ''" />
                            <button @click="isSearchOpen = false; searchQuery = ''" type="button" class="p-1 text-slate-400 hover:text-white"><span class="material-symbols-outlined text-sm">close</span></button>
                        </div>
                    </template>
                    <template x-if="!isSearchOpen">
                        <button @click="isSearchOpen = true" type="button" class="p-1.5 text-slate-400 hover:text-white rounded-lg text-xs flex items-center gap-1">
                            <span class="material-symbols-outlined text-base">search</span>
                            <span class="hidden sm:inline text-[11px] font-mono">Cari</span>
                        </button>
                    </template>
                </div>

                <button @click="scrollToBottom(true)" type="button" class="p-1.5 text-slate-400 hover:text-amber-400 rounded-lg">
                    <span class="material-symbols-outlined text-base">arrow_downward</span>
                </button>
            </div>
        </div>

        <!-- CHAT STREAM SCROLL CONTAINER -->
        <div class="flex-1 p-4 sm:p-6 overflow-y-auto space-y-4 bg-gradient-to-b from-[#11131a] via-[#12151d] to-[#0f1118]" 
             x-ref="chatContainer">
            
            <div class="p-4 rounded-2xl bg-gradient-to-br from-[#1e1b24] to-[#14161f] border border-amber-500/20 text-center max-w-lg mx-auto shadow-xl">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/30 mx-auto flex items-center justify-center mb-2 shadow-inner">
                    <span class="material-symbols-outlined text-xl">shield_lock</span>
                </div>
                <h5 class="font-headline font-bold text-xs sm:text-sm text-white">Saluran Komunikasi Privat Direksi</h5>
                <p class="text-[11px] text-slate-400 mt-1 leading-relaxed">
                    Ruang obrolan ini hanya melibatkan Anda selaku Manager Departemen {{ auth()->user()->department }} dan Direktur. Staf dan divisi lain tidak dapat mengakses pesan ini.
                </p>
            </div>

            <template x-if="filteredMessages.length === 0">
                <div class="py-16 text-center flex flex-col items-center justify-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800/40 border border-slate-700/60 flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-3xl text-slate-500">forum</span>
                    </div>
                    <h4 class="font-headline font-bold text-sm text-slate-300">Belum ada obrolan dengan Direktur</h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm">Kirimkan update kinerja, eskalasi divisi, atau konsultasi strategis kepada Direktur di sini.</p>
                </div>
            </template>

            <!-- MESSAGES BUBBLES -->
            <template x-for="item in filteredMessages" :key="item.id">
                <div class="group relative flex items-start gap-3 transition-all"
                     :class="item.is_me ? 'flex-row-reverse' : 'flex-row'">
                    
                    <div class="relative shrink-0 pt-0.5">
                        <img :src="item.sender_avatar" 
                             :alt="item.sender_name" 
                             class="w-9 h-9 rounded-xl object-cover border shadow-sm"
                             :class="item.is_me ? 'border-rose-500/50 ring-2 ring-rose-500/20' : 'border-amber-500/50 ring-2 ring-amber-500/20'" />
                    </div>

                    <div class="max-w-[85%] sm:max-w-[75%] space-y-1"
                         :class="item.is_me ? 'items-end text-right' : 'items-start text-left'">
                        
                        <div class="flex items-center gap-1.5 px-1"
                             :class="item.is_me ? 'justify-end' : 'justify-start'">
                            <span class="font-headline font-bold text-xs text-slate-200" x-text="item.sender_name"></span>
                            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold uppercase tracking-wider"
                                  :class="item.is_me ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'"
                                  x-text="item.sender_role">
                            </span>
                            <span class="text-[10px] font-mono text-slate-500" x-text="item.created_at_human"></span>
                        </div>

                        <div class="relative rounded-2xl p-3.5 text-xs sm:text-[13px] leading-relaxed break-words shadow-md transition-all group-hover:shadow-lg"
                             :class="item.is_me 
                                ? 'bg-gradient-to-br from-rose-600 via-rose-600 to-rose-700 text-white rounded-tr-xs shadow-rose-950/50' 
                                : 'bg-[#191e2b] border border-slate-700/80 text-slate-100 rounded-tl-xs shadow-black/40'">
                            
                            <p class="whitespace-pre-wrap selection:bg-black/30 font-normal" x-text="item.text"></p>
                            
                            <div class="mt-1.5 flex items-center gap-1 justify-end text-[9px] font-mono opacity-70"
                                 :class="item.is_me ? 'text-rose-100' : 'text-slate-400'">
                                <span x-text="item.created_at_human"></span>
                                <template x-if="item.is_me">
                                    <span class="material-symbols-outlined text-[11px] text-white">done_all</span>
                                </template>
                            </div>

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
            <div class="px-5 py-2.5 bg-[#171d2c] border-t border-rose-500/30 flex items-center justify-between gap-3 text-xs shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="material-symbols-outlined text-sm text-rose-400">reply</span>
                    <div class="min-w-0">
                        <span class="font-bold text-slate-200">Membalas <span class="text-rose-400" x-text="replyingTo.sender_name"></span>:</span>
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
                              placeholder="Tulis pesan/laporan langsung ke Direktur... (Enter kirim, Shift+Enter baris baru)" 
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

    </div>

</div>
@endsection
