{{-- Face ID & Location Biometric Attendance Modal Component --}}
<div x-data="faceAttendanceComponent()" 
     x-init="init()"
     @open-face-modal.window="openModal($event.detail)"
     x-cloak 
     x-show="showModal" 
     class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/85 overflow-y-auto"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-[#151922] border border-slate-700/80 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden relative my-auto"
         @click.away="if(!isProcessing) closeModal()">

        <!-- Header Modal -->
        <div class="px-5 py-3.5 border-b border-slate-800 flex items-center justify-between bg-slate-900/80">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-slate-950 shadow-md">
                    <span class="material-symbols-outlined text-xl">face_retouching_natural</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-base text-white flex items-center gap-2">
                        <span>Presensi Biometrik Wajah</span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-mono uppercase tracking-wider font-semibold">Face ID + GPS</span>
                    </h3>
                    <p class="font-mono text-[11px] text-slate-400">Verifikasi kamera wajah & lokasi terkini</p>
                </div>
            </div>
            <button type="button" @click="closeModal()" :disabled="isProcessing" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-4 sm:p-5 space-y-3.5">

            <!-- Tampilan SUKSES VERIFIKASI -->
            <div x-show="verificationSuccess" class="py-6 flex flex-col items-center text-center space-y-4">
                <div class="w-20 h-20 rounded-full bg-emerald-500/20 border-2 border-emerald-500 flex items-center justify-center text-emerald-400 animate-bounce shadow-lg shadow-emerald-500/30">
                    <span class="material-symbols-outlined text-4xl">check_circle</span>
                </div>

                <div>
                    <h4 class="font-headline font-bold text-xl text-white" x-text="successData?.message || 'Presensi Berhasil!'"></h4>
                    <p class="font-mono text-xs text-slate-400 mt-1">
                        Tercatat pada <strong class="text-emerald-400 font-semibold" x-text="successData?.time || ''"></strong>
                    </p>
                </div>

                <!-- Ringkasan Hasil Presensi -->
                <div class="w-full bg-slate-900/90 rounded-xl p-3.5 border border-slate-800 space-y-2 font-mono text-xs text-left">
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Status Kedisiplinan:</span>
                        <span class="px-2 py-0.5 rounded text-[11px] font-bold"
                              :class="successData?.status === 'on_time' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30'"
                              x-text="successData?.status_label || 'Tercatat'">
                        </span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Jarak ke Kantor:</span>
                        <span class="font-semibold text-white" x-text="successData?.distance !== null ? successData.distance + ' meter' : 'Tercatat'"></span>
                    </div>
                    <div class="flex justify-between items-center text-slate-300">
                        <span class="text-slate-400">Lokasi:</span>
                        <span class="font-semibold text-slate-200 truncate max-w-[200px]" x-text="successData?.location || 'Terekam'"></span>
                    </div>
                </div>

                <p class="font-mono text-[11px] text-slate-500 animate-pulse">
                    Halaman akan disegarkan otomatis dalam 2 detik...
                </p>
            </div>

            <!-- Tampilan KAMERA & SCANNER (AKTIF) -->
            <div x-show="!verificationSuccess" class="space-y-3.5">

                <!-- Pilihan Mode: Masuk atau Pulang -->
                <div class="flex items-center gap-2 p-1 bg-slate-900/90 rounded-xl border border-slate-800 text-xs font-mono">
                    <button type="button" 
                            @click="attendanceType = 'clock_in'" 
                            :class="attendanceType === 'clock_in' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                            class="flex-1 py-1.5 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base">login</span>
                        <span>Presensi Masuk</span>
                    </button>
                    <button type="button" 
                            @click="attendanceType = 'clock_out'" 
                            :class="attendanceType === 'clock_out' ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                            class="flex-1 py-1.5 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base">logout</span>
                        <span>Presensi Pulang</span>
                    </button>
                </div>

                <!-- Viewfinder Kamera Face ID (BERSIH TANPA TEKS DI TENGAH) -->
                <div class="relative w-full rounded-2xl overflow-hidden border border-slate-700 bg-black flex items-center justify-center shadow-lg"
                     style="height: 310px;">
                    
                    <!-- Video Feed Langsung -->
                    <video x-ref="videoElement" 
                           autoplay 
                           playsinline 
                           muted 
                           style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: block;"
                           x-show="!photoCaptured">
                    </video>

                    <!-- Preview Hasil Foto Snapshot Setelah Dipotret -->
                    <img x-ref="photoPreview" 
                         x-show="photoCaptured" 
                         :src="photoCaptured" 
                         alt="Snapshot Wajah" 
                         style="width: 100%; height: 100%; object-fit: cover;" />

                    <!-- Canvas Tersembunyi untuk Capture Resolusi Penuh -->
                    <canvas x-ref="captureCanvas" class="hidden"></canvas>

                    <!-- Placeholder Jika Kamera Sedang Menghubungkan atau Izin Ditolak -->
                    <div x-show="!cameraActive && !photoCaptured" 
                         class="absolute inset-0 bg-slate-950 flex flex-col items-center justify-center text-center p-6 text-slate-400 space-y-2 z-10">
                        
                        <div x-show="cameraLoading" class="flex flex-col items-center space-y-2">
                            <span class="material-symbols-outlined text-4xl text-emerald-400 animate-spin">sync</span>
                            <p class="font-mono text-xs text-white font-semibold">Mengaktifkan Lensa Kamera...</p>
                            <p class="font-mono text-[11px] text-slate-500">Mohon izinkan akses kamera pada peramban Anda</p>
                        </div>

                        <div x-show="cameraError && !cameraLoading" class="flex flex-col items-center space-y-2">
                            <span class="material-symbols-outlined text-4xl text-rose-400">videocam_off</span>
                            <p class="font-mono text-xs text-rose-400 font-semibold" x-text="cameraError"></p>
                            <button type="button" @click="startCamera()" class="mt-2 px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-mono text-xs font-bold transition-all shadow-md">
                                Coba Aktifkan Kembali
                            </button>
                        </div>
                    </div>

                    <!-- Tombol Ganti Kamera (Hanya icon kecil di pojok kanan atas) -->
                    <button type="button" 
                            @click="toggleCamera()" 
                            class="absolute top-3 right-3 z-20 p-2 rounded-full bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-700 shadow-md transition-all flex items-center justify-center"
                            title="Ganti Kamera Depan/Belakang">
                        <span class="material-symbols-outlined text-sm">flip_camera_ios</span>
                    </button>

                    <!-- Frame Oval Panduan Wajah Bersih (Tanpa Teks Apapun) -->
                    <div x-show="cameraActive && !photoCaptured" class="pointer-events-none absolute inset-0 flex items-center justify-center z-10">
                        <div style="width: 195px; height: 250px; border-radius: 50%; border: 2px solid rgba(52, 211, 153, 0.6); box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);"></div>
                    </div>
                </div>

                <!-- Kartu Indikator GPS Lokasi Terkini (Di Luar Kamera) -->
                <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 flex items-center justify-between font-mono text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-lg" 
                              :class="locationReady ? (inOfficeRadius ? 'text-emerald-400' : 'text-amber-400') : 'text-slate-500 animate-spin'">
                            location_on
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-white text-[11px]" x-text="locationStatus"></span>
                                <template x-if="inOfficeRadius">
                                    <span class="px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 text-[9px] font-semibold border border-emerald-500/30">Dalam Radius</span>
                                </template>
                            </div>
                            <div class="text-[10px] text-slate-400 flex items-center gap-1.5 mt-0.5">
                                <span x-text="latitude ? (latitude.toFixed(5) + ', ' + longitude.toFixed(5)) : 'Mencari sinyal GPS...'"></span>
                                <template x-if="distanceMeters !== null">
                                    <span>• Jarak: <strong class="text-slate-200" x-text="distanceMeters + ' m'"></strong></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <template x-if="latitude && longitude">
                        <a :href="'https://www.google.com/maps?q=' + latitude + ',' + longitude" target="_blank" class="text-primary-light hover:underline text-[11px] flex items-center gap-0.5 shrink-0">
                            <span>Peta</span>
                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                        </a>
                    </template>
                </div>

                <!-- Tombol Aksi Verifikasi & Absen -->
                <div class="pt-1 flex items-center gap-2.5">
                    <template x-if="photoCaptured">
                        <button type="button" 
                                @click="retakePhoto()" 
                                :disabled="isProcessing"
                                class="w-1/3 py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-mono text-xs font-semibold transition-colors flex items-center justify-center gap-1.5 border border-slate-700">
                            <span class="material-symbols-outlined text-base">replay</span>
                            <span>Ulang</span>
                        </button>
                    </template>

                    <button type="button" 
                            @click="takePhotoAndSubmit()" 
                            :disabled="!cameraActive || isProcessing"
                            class="flex-1 py-3 px-4 rounded-xl text-white font-bold text-xs font-mono transition-all flex items-center justify-center gap-2 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="attendanceType === 'clock_in' ? 'bg-gradient-to-r from-emerald-600 via-emerald-500 to-teal-600 hover:from-emerald-500 hover:to-teal-500 shadow-emerald-500/25 ring-1 ring-emerald-400/30' : 'bg-gradient-to-r from-amber-600 via-orange-500 to-amber-700 hover:from-amber-500 hover:to-amber-600 shadow-orange-500/25 ring-1 ring-amber-400/30'">
                        
                        <template x-if="isProcessing">
                            <span class="material-symbols-outlined text-base animate-spin">progress_activity</span>
                        </template>
                        <template x-if="!isProcessing">
                            <span class="material-symbols-outlined text-lg">camera</span>
                        </template>

                        <span x-text="isProcessing ? 'Memverifikasi Biometrik...' : (photoCaptured ? 'Konfirmasi & Kirim Presensi' : (attendanceType === 'clock_in' ? 'Ambil Foto & Clock In' : 'Ambil Foto & Clock Out'))"></span>
                    </button>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
function faceAttendanceComponent() {
    return {
        showModal: false,
        cameraActive: false,
        cameraLoading: false,
        cameraError: '',
        videoStream: null,
        photoCaptured: null,
        isProcessing: false,
        attendanceType: 'auto', // auto, clock_in, clock_out
        facingMode: 'user', // user (front) or environment (back)
        verificationSuccess: false,
        successData: null,

        // Geolocation Data
        latitude: null,
        longitude: null,
        accuracy: null,
        distanceMeters: null,
        inOfficeRadius: false,
        locationStatus: 'Mendeteksi Lokasi GPS...',
        locationReady: false,

        // Default Office Coordinate (Gyokai Headquarters)
        officeLat: {{ config('app.office_latitude', env('OFFICE_LATITUDE', -6.2088)) }},
        officeLng: {{ config('app.office_longitude', env('OFFICE_LONGITUDE', 106.8456)) }},
        allowedRadius: {{ config('app.office_radius_meters', env('OFFICE_RADIUS_METERS', 500)) }},

        init() {
            this.requestLocation();
        },

        openModal(detail) {
            this.showModal = true;
            this.photoCaptured = null;
            this.verificationSuccess = false;
            this.successData = null;
            this.attendanceType = (detail && detail.type) ? detail.type : 'auto';

            this.requestLocation();
            setTimeout(() => {
                this.startCamera();
            }, 100);
        },

        closeModal() {
            this.stopCamera();
            this.showModal = false;
            this.photoCaptured = null;
        },

        toggleCamera() {
            this.facingMode = (this.facingMode === 'user') ? 'environment' : 'user';
            this.startCamera();
        },

        requestLocation() {
            if (!navigator.geolocation) {
                this.locationStatus = 'Geolocation tidak didukung browser';
                return;
            }

            this.locationStatus = 'Mengunci Koordinat Satelit...';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.latitude = pos.coords.latitude;
                    this.longitude = pos.coords.longitude;
                    this.accuracy = Math.round(pos.coords.accuracy);
                    this.locationReady = true;

                    // Hitung Jarak ke Kantor
                    this.distanceMeters = this.calculateDistance(this.latitude, this.longitude, this.officeLat, this.officeLng);
                    this.inOfficeRadius = (this.distanceMeters <= this.allowedRadius);
                    this.locationStatus = this.inOfficeRadius 
                        ? 'Lokasi Valid (Kantor Gyokai)' 
                        : 'Lokasi Terbaca (Di Luar Kantor)';
                },
                (err) => {
                    console.warn('Geolocation warning:', err.message);
                    this.locationStatus = 'Sinyal Lokasi Lemah (Gunakan GPS)';
                    this.locationReady = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        },

        calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return Math.round(R * c);
        },

        async startCamera() {
            this.cameraError = '';
            this.cameraLoading = true;
            this.cameraActive = false;

            // Hentikan stream lama jika ada
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(t => t.stop());
                this.videoStream = null;
            }

            let stream = null;
            try {
                // Percobaan 1: Kamera dengan resolusi ideal
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: this.facingMode,
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    },
                    audio: false
                });
            } catch (err1) {
                console.warn('Attempt 1 failed, trying fallback...', err1);
                try {
                    // Percobaan 2: Fallback kamera standar
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: false
                    });
                } catch (err2) {
                    console.error('Semua akses kamera gagal:', err2);
                    this.cameraError = 'Izin kamera ditolak. Silakan izinkan akses kamera di setelan browser.';
                    this.cameraLoading = false;
                    return;
                }
            }

            this.videoStream = stream;
            const video = this.$refs.videoElement;
            if (video) {
                video.srcObject = stream;
                video.setAttribute('playsinline', 'true');
                video.setAttribute('autoplay', 'true');
                video.muted = true;
                video.playsInline = true;

                // Langsung jalankan play()
                try {
                    await video.play();
                } catch (e) {
                    console.warn('Play attempt error:', e);
                }

                this.cameraActive = true;
                this.cameraLoading = false;
            }
        },

        stopCamera() {
            if (this.videoStream) {
                this.videoStream.getTracks().forEach(track => track.stop());
                this.videoStream = null;
            }
            this.cameraActive = false;
        },

        retakePhoto() {
            this.photoCaptured = null;
            this.startCamera();
        },

        takePhotoAndSubmit() {
            if (!this.photoCaptured) {
                // Capture Frame dari Video ke Canvas
                const video = this.$refs.videoElement;
                const canvas = this.$refs.captureCanvas;
                if (!video || !canvas) return;

                const width = video.videoWidth || 640;
                const height = video.videoHeight || 480;
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                // Mirror horizontally jika kamera depan
                if (this.facingMode === 'user') {
                    ctx.translate(width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(video, 0, 0, width, height);
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                } else {
                    ctx.drawImage(video, 0, 0, width, height);
                }

                // Tambahkan Watermark Digital Biometrik
                ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
                ctx.fillRect(10, height - 45, width - 20, 35);
                ctx.fillStyle = '#10b981';
                ctx.font = 'bold 12px monospace';
                const nowStr = new Date().toLocaleString('id-ID');
                ctx.fillText('LPK GYOKAI BIOMETRIC VERIFIED • ' + nowStr, 20, height - 28);
                if (this.latitude && this.longitude) {
                    ctx.fillStyle = '#ffffff';
                    ctx.font = '10px monospace';
                    ctx.fillText('GPS: ' + this.latitude.toFixed(5) + ', ' + this.longitude.toFixed(5) + ' (±' + (this.accuracy || 10) + 'm)', 20, height - 15);
                }

                this.photoCaptured = canvas.toDataURL('image/jpeg', 0.85);
                this.stopCamera();
            }

            // Kirim Payload ke Server
            this.submitAttendance();
        },

        async submitAttendance() {
            this.isProcessing = true;

            const payload = {
                photo: this.photoCaptured,
                latitude: this.latitude,
                longitude: this.longitude,
                type: this.attendanceType,
                work_mode: this.inOfficeRadius ? 'WFO' : 'WFH / Dinas Luar',
                location_name: this.locationStatus
            };

            try {
                const response = await fetch("{{ route('attendance.faceRecord') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.verificationSuccess = true;
                    this.successData = data;
                    this.playBeepSound();

                    setTimeout(() => {
                        window.location.reload();
                    }, 2200);
                } else {
                    alert(data.message || 'Gagal memverifikasi presensi.');
                    this.retakePhoto();
                }
            } catch (err) {
                console.error('Error kirim presensi:', err);
                alert('Terjadi kendala jaringan saat mengirim presensi. Silakan coba kembali.');
                this.retakePhoto();
            } finally {
                this.isProcessing = false;
            }
        },

        playBeepSound() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, audioCtx.currentTime);
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.3);
            } catch (e) {}
        }
    };
}
</script>
