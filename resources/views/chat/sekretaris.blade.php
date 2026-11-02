@extends('layouts.app')

@section('title', 'Pusat Komunikasi Eksekutif')

@section('content')
<div class="w-full max-w-full flex flex-col gap-4" 
     x-data="{
        activeUserId: {{ $activeContact ? $activeContact->id : 0 }},
        messageText: '',
        searchQuery: '',
        contactSearchQuery: '',
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
            'sender_title' => $m->sender->employee->role_title ?? ($m->sender->isDirektur() ? 'Direktur Utama' : 'Head of ' . $m->sender->department),
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
            if (!this.activeUserId) return;
            fetch('{{ route('chat.fetch') }}?user_id=' + this.activeUserId + '&after_id=' + this.lastId, {
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
            if (!this.messageText.trim() || this.isSending || !this.activeUserId) return;
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
                    target_user_id: this.activeUserId,
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

    <!-- ==================== TOP HERO / CHANNEL HEADER (SEKRETARIS DIREKTUR) ==================== -->
    <div class="w-full bg-[#141822] border border-slate-800/80 rounded-2xl p-4 sm:p-5 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5 min-w-0">
            <div class="relative shrink-0">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500/20 via-orange-500/10 to-rose-500/10 text-amber-400 border border-amber-500/30 flex items-center justify-center shadow-lg shadow-amber-950/40">
                    <span class="material-symbols-outlined text-2xl">hub</span>
                </span>
                <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-[#141822]"></span>
            </div>
            
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="font-headline font-extrabold text-base sm:text-lg text-white tracking-tight flex items-center gap-2">
                        <span>Pusat Koordinasi Eksekutif</span>
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30 flex items-center gap-1.5 shadow-xs">
                        <span class="material-symbols-outlined text-xs">verified_user</span>
                        <span>Sekretaris Direktur Utama</span>
                    </span>
                </div>
                <p class="text-xs text-slate-400 truncate mt-0.5 font-normal">
                    Pusat koordinasi komunikasi eksekutif antara Direktur Utama dengan seluruh Kepala Departemen perusahaan.
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
                <span class="font-semibold text-emerald-400">Pusat Eksekutif Aktif</span>
            </div>
        </div>
    </div>

    <!-- ==================== MAIN CHAT STAGE (CONTACT SELECTOR + CHAT STREAM) ==================== -->
    <div class="w-full flex flex-col lg:flex-row gap-5 items-stretch" style="min-height: 640px;">

        <!-- LEFT SIDEBAR: DAFTAR KONTAK (DIREKTUR & MANAGER) -->
        <div class="w-full lg:w-80 shrink-0 bg-[#12151d] rounded-2xl border border-slate-800/80 p-5 shadow-2xl flex flex-col gap-4"
             style="width: 320px; flex-shrink: 0;"
             x-data="{ queryContact: '' }">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-slate-800 text-slate-300 border border-slate-700 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">contacts</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-xs sm:text-sm text-white">Daftar Kontak</h3>
                        <p class="text-[10px] font-mono text-slate-400">{{ $contacts->count() }} Pimpinan & Manager</p>
                    </div>
                </div>

                <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/20 font-bold">
                    Eksekutif
                </span>
            </div>

            <!-- Search input contact -->
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-500 text-sm">search</span>
                <input type="text" 
                       x-model="queryContact" 
                       placeholder="Cari nama pimpinan / divisi..." 
                       class="w-full bg-[#0d1017] border border-slate-800 focus:border-amber-500/80 rounded-xl pl-8 pr-3 py-1.5 text-xs text-white placeholder:text-slate-500 focus:outline-none transition-colors shadow-inner" />
            </div>

            <!-- List Contact Cards -->
            <div class="flex flex-col gap-2 overflow-y-auto max-h-[460px] pr-1">
                @foreach($contacts as $contact)
                    @php
                        $isSelected = $activeContact && $activeContact->id === $contact->id;
                        $contactNameLower = strtolower($contact->name . ' ' . $contact->department . ' ' . $contact->role);
                        $isDirector = $contact->isDirektur();
                    @endphp
                    <a href="{{ route('chat.index', ['user_id' => $contact->id]) }}"
                       x-show="!queryContact.trim() || '{{ $contactNameLower }}'.includes(queryContact.toLowerCase())"
                       class="p-3 rounded-xl border transition-all flex items-center justify-between gap-2.5 group {{ $isSelected ? ($isDirector ? 'bg-gradient-to-r from-amber-950/60 to-rose-950/40 border-amber-500 ring-1 ring-amber-500/60 shadow-lg' : 'bg-gradient-to-r from-slate-800/80 to-slate-900 border-amber-500/60 ring-1 ring-amber-500/40 shadow-lg') : ($isDirector ? 'bg-amber-950/20 border-amber-500/30 hover:border-amber-500/60' : 'bg-[#151924] border-slate-800/90 hover:border-slate-700 hover:bg-slate-800/40') }}">
                        
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="relative shrink-0">
                                <img src="{{ $contact->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}" 
                                     alt="{{ $contact->name }}" 
                                     class="w-9 h-9 rounded-xl object-cover border {{ $isDirector ? 'border-amber-400' : ($isSelected ? 'border-amber-500/60' : 'border-slate-700') }} shadow-sm"/>
                                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full {{ $isDirector ? 'bg-amber-400' : 'bg-emerald-500' }} border-2 border-[#151924]"></span>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <h5 class="font-headline font-bold text-xs truncate {{ $isDirector ? 'text-amber-300' : ($isSelected ? 'text-white' : 'text-slate-200 group-hover:text-white') }}">
                                        {{ $contact->name }}
                                    </h5>
                                    @if($isDirector)
                                        <span class="material-symbols-outlined text-[13px] text-amber-400 shrink-0">star</span>
                                    @endif
                                </div>
                                <p class="text-[10px] font-mono {{ $isDirector ? 'text-amber-400/80 font-bold' : 'text-slate-400' }} truncate">
                                    {{ $isDirector ? 'Direktur Utama' : 'Manager ' . $contact->department }}
                                </p>
                            </div>
                        </div>

                        <span class="px-2 py-0.5 rounded text-[8px] font-mono font-bold uppercase shrink-0 border {{ $isDirector ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 font-extrabold' : ($isSelected ? 'bg-amber-500/20 text-amber-300 border-amber-500/30' : 'bg-slate-800 text-slate-400 border-slate-700') }}">
                            {{ $isDirector ? 'DIREKSI' : $contact->department }}
                        </span>
                    </a>
                @endforeach
            </div>

        </div>

        <!-- RIGHT PANEL: ACTIVE CHAT STREAM WITH SELECTED CONTACT -->
        <div class="flex-1 min-w-0 bg-[#12151d] rounded-2xl border border-slate-800/80 shadow-2xl flex flex-col overflow-hidden">
            
            @if($activeContact)
                <!-- TOP STREAM TOOLBAR -->
                <div class="px-5 py-3.5 bg-[#151924] border-b border-slate-800/80 flex items-center justify-between gap-4 shrink-0">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="relative shrink-0">
                            <img src="{{ $activeContact->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}" 
                                 alt="{{ $activeContact->name }}" 
                                 class="w-10 h-10 rounded-xl object-cover border {{ $activeContact->isDirektur() ? 'border-amber-400' : 'border-slate-700' }} shadow-sm"/>
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full {{ $activeContact->isDirektur() ? 'bg-amber-400' : 'bg-emerald-500' }} border-2 border-[#151924]"></span>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-headline font-bold text-sm text-white truncate">
                                    {{ $activeContact->name }}
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold {{ $activeContact->isDirektur() ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' }}">
                                    {{ $activeContact->isDirektur() ? 'Direktur Utama' : 'Manager ' . $activeContact->department }}
                                </span>
                            </div>
                            <p class="text-[11px] font-mono text-slate-400 truncate">
                                {{ $activeContact->email }} &bull; {{ $activeContact->employee->phone ?? '-' }}
                            </p>
                        </div>
                    </div>

                    <!-- SEARCH INSIDE CONVERSATION -->
                    <div class="flex items-center gap-2 shrink-0">
                        <div x-show="isSearchOpen" x-cloak class="relative">
                            <input type="text" 
                                   x-model="searchQuery" 
                                   placeholder="Cari pesan percakapan..." 
                                   class="bg-[#0e1017] border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-amber-500/80 w-36 sm:w-56" />
                            <button @click="searchQuery = ''; isSearchOpen = false;" class="absolute right-2 top-2 text-slate-400 hover:text-white">
                                <span class="material-symbols-outlined text-xs">close</span>
                            </button>
                        </div>
                        <button @click="isSearchOpen = !isSearchOpen" type="button" class="p-2 text-slate-400 hover:text-white rounded-xl hover:bg-slate-800 transition-colors">
                            <span class="material-symbols-outlined text-base">search</span>
                        </button>
                    </div>
                </div>

                <!-- MESSAGE STREAM BODY -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-5 flex flex-col gap-3 min-h-0" x-ref="chatContainer">
                    
                    <template x-if="filteredMessages.length === 0">
                        <div class="flex flex-col items-center justify-center my-auto py-12 text-center text-slate-500 font-mono text-xs">
                            <span class="material-symbols-outlined text-4xl mb-2 text-slate-600">forum</span>
                            <span>Belum ada pesan dalam percakapan ini. Mulai komunikasi Anda sekarang.</span>
                        </div>
                    </template>

                    <template x-for="(item, idx) in filteredMessages" :key="item.id">
                        <div class="flex gap-3 group relative transition-all"
                             :class="item.is_me ? 'flex-row-reverse' : 'flex-row'">
                            
                            <img :src="item.sender_avatar" 
                                 :alt="item.sender_name" 
                                 class="w-8 h-8 rounded-xl object-cover border border-slate-700 shrink-0 mt-0.5 shadow-sm" />

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

                            <textarea x-model="messageText" 
                                      x-ref="messageInput"
                                      rows="1" 
                                      @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                                      placeholder="Tulis pesan ke {{ $activeContact->name }}... (Enter kirim, Shift+Enter baris baru)" 
                                      class="w-full bg-transparent border-0 text-xs sm:text-sm text-white placeholder:text-slate-500 focus:outline-none focus:ring-0 resize-none py-2 px-1 max-h-32"></textarea>

                            <button type="submit" 
                                    :disabled="isSending || !messageText.trim()" 
                                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all shadow-md active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed shrink-0">
                                <span class="material-symbols-outlined text-base">send</span>
                                <span class="hidden sm:inline">Kirim</span>
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="flex flex-col items-center justify-center m-auto text-center text-slate-500 font-mono text-xs py-12">
                    <span class="material-symbols-outlined text-4xl mb-2 text-slate-600">contact_support</span>
                    <span>Pilih salah satu kontak di panel kiri untuk membuka percakapan eksekutif.</span>
                </div>
            @endif

        </div>

    </div>

</div>
@endsection

