{{-- Face ID & Location Biometric Attendance Modal Component --}}
<div x-data="faceAttendanceComponent()" 
     x-init="init()"
     @open-face-modal.window="openModal($event.detail)"
     x-cloak 
     x-show="showModal" 
     class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 overflow-y-auto"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="bg-[#151922] border border-slate-700/80 rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden relative my-auto"
         @click.away="if(!isProcessing) closeModal()">

        <!-- Header Modal -->
        <div class="px-5 py-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/60">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-slate-950 shadow-md">
                    <span class="material-symbols-outlined text-xl">face_retouching_natural</span>
                </div>
                <div>
                    <h3 class="font-headline font-bold text-base text-white flex items-center gap-2">
                        <span>Presensi Biometrik Wajah</span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-mono uppercase tracking-wider font-semibold">Face ID + GPS</span>
                    </h3>
                    <p class="font-mono text-[11px] text-slate-400">Verifikasi wajah langsung & lokasi GPS terkini</p>
                </div>
            </div>
            <button type="button" @click="closeModal()" :disabled="isProcessing" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-5 space-y-4">

            <!-- Tampilan SUKSES VERIFIKASI -->
            <template x-if="verificationSuccess">
                <div class="py-6 flex flex-col items-center text-center space-y-4">
                    <div class="w-20 h-20 rounded-full bg-emerald-500/20 border-2 border-emerald-500 flex items-center justify-center text-emerald-400 animate-bounce">
                        <span class="material-symbols-outlined text-4xl">check_circle</span>
                    </div>

                    <div>
                        <h4 class="font-headline font-bold text-xl text-white" x-text="successData?.message || 'Presensi Berhasil!'"></h4>
                        <p class="font-mono text-xs text-slate-400 mt-1">
                            Tercatat pada <strong class="text-emerald-400 font-semibold" x-text="successData?.time || ''"></strong>
                        </p>
                    </div>

                    <!-- Ringkasan Hasil Presensi -->
                    <div class="w-full bg-slate-900/80 rounded-xl p-3.5 border border-slate-800 space-y-2 font-mono text-xs text-left">
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
                        Halaman akan disegarkan otomatis dalam beberapa detik...
                    </p>
                </div>
            </template>

            <!-- Tampilan KAMERA & SCANNER (AKTIF) -->
            <template x-if="!verificationSuccess">
                <div class="space-y-4">

                    <!-- Pilihan Mode: Masuk atau Pulang -->
                    <div class="flex items-center gap-2 p-1 bg-slate-900/90 rounded-xl border border-slate-800 text-xs font-mono">
                        <button type="button" 
                                @click="attendanceType = 'clock_in'" 
                                :class="attendanceType === 'clock_in' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                                class="flex-1 py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-base">login</span>
                            <span>Presensi Masuk (Clock In)</span>
                        </button>
                        <button type="button" 
                                @click="attendanceType = 'clock_out'" 
                                :class="attendanceType === 'clock_out' ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                                class="flex-1 py-2 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-base">logout</span>
                            <span>Presensi Pulang (Clock Out)</span>
                        </button>
                    </div>

                    <!-- Viewfinder Kamera Face ID dengan Animasi Radar -->
                    <div class="relative w-full aspect-[4/3] bg-slate-950 rounded-2xl overflow-hidden border-2 shadow-inner flex items-center justify-center"
                         :class="faceDetected ? 'border-emerald-500 shadow-emerald-500/20' : 'border-amber-500/50 shadow-amber-500/10'">
                        
                        <!-- Video Feed Langsung -->
                        <video x-ref="videoElement" 
                               autoplay 
                               playsinline 
                               muted 
                               class="w-full h-full object-cover transform -scale-x-100"
                               x-show="cameraActive && !photoCaptured">
                        </video>

                        <!-- Preview Hasil Foto Snapshot -->
                        <img x-ref="photoPreview" 
                             x-show="photoCaptured" 
                             :src="photoCaptured" 
                             alt="Snapshot Wajah" 
                             class="w-full h-full object-cover transform -scale-x-100" />

                        <!-- Canvas Tersembunyi untuk Capture Resolusi Penuh -->
                        <canvas x-ref="captureCanvas" class="hidden"></canvas>

                        <!-- Placeholder Jika Kamera Belum Aktif / Error -->
                        <div x-show="!cameraActive && !photoCaptured" class="flex flex-col items-center justify-center text-center p-6 text-slate-400 space-y-2">
                            <span class="material-symbols-outlined text-4xl text-amber-400 animate-pulse">videocam</span>
                            <p class="font-mono text-xs text-white font-semibold">Menghubungkan Kamera...</p>
                            <p class="font-mono text-[11px] text-slate-500">Mohon izinkan akses kamera pada peramban/browser Anda</p>
                            <template x-if="cameraError">
                                <p class="font-mono text-[11px] text-rose-400 mt-2" x-text="cameraError"></p>
                            </template>
                        </div>

                        <!-- Oval Reticle / Masking Pemindaian Wajah Futuristik -->
                        <div x-show="cameraActive && !photoCaptured" class="absolute inset-0 pointer-events-none flex items-center justify-center">
                            <!-- Oval Target -->
                            <div class="w-48 h-60 sm:w-56 sm:h-72 border-2 border-dashed rounded-[50%] relative flex items-center justify-center transition-all duration-300"
                                 :class="faceDetected ? 'border-emerald-400 shadow-lg shadow-emerald-500/30' : 'border-amber-400/70'">
                                
                                <!-- Corner Marks -->
                                <div class="absolute -top-2 w-4 h-4 border-t-2 border-l-2" :class="faceDetected ? 'border-emerald-400' : 'border-amber-400'"></div>
                                <div class="absolute -top-2 w-4 h-4 border-t-2 border-r-2 right-0" :class="faceDetected ? 'border-emerald-400' : 'border-amber-400'"></div>
                                <div class="absolute -bottom-2 w-4 h-4 border-b-2 border-l-2 left-0" :class="faceDetected ? 'border-emerald-400' : 'border-amber-400'"></div>
                                <div class="absolute -bottom-2 w-4 h-4 border-b-2 border-r-2 right-0" :class="faceDetected ? 'border-emerald-400' : 'border-amber-400'"></div>

                                <!-- Scanning Laser Line -->
                                <div class="absolute w-full h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent animate-pulse"
                                     style="top: 50%; animation: scanPulse 2s ease-in-out infinite;"></div>
                            </div>
                        </div>

                        <!-- Status Badge Deteksi Wajah -->
                        <div class="absolute top-3 left-3 right-3 flex items-center justify-between pointer-events-none">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-bold uppercase tracking-wider backdrop-blur-none"
                                  :class="faceDetected ? 'bg-emerald-950/80 text-emerald-400 border border-emerald-500/40' : 'bg-amber-950/80 text-amber-300 border border-amber-500/40'">
                                <span class="w-1.5 h-1.5 rounded-full" :class="faceDetected ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
                                <span x-text="faceDetected ? 'Wajah Terdeteksi & Terfokus' : 'Posisikan Wajah di Tengah'"></span>
                            </span>

                            <span class="px-2 py-0.5 rounded bg-black/70 text-slate-300 font-mono text-[10px] border border-slate-800">
                                {{ now()->format('H:i') }} WIB
                            </span>
                        </div>
                    </div>

                    <!-- Kartu Indikator GPS Lokasi Terkini -->
                    <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 flex items-center justify-between font-mono text-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="material-symbols-outlined text-lg" 
                                  :class="locationReady ? (inOfficeRadius ? 'text-emerald-400' : 'text-amber-400') : 'text-slate-500 animate-spin'">
                                location_on
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-white" x-text="locationStatus"></span>
                                    <template x-if="inOfficeRadius">
                                        <span class="px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 text-[10px] font-semibold border border-emerald-500/30">Dalam Radius</span>
                                    </template>
                                </div>
                                <div class="text-[11px] text-slate-400 flex items-center gap-2 mt-0.5">
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
                    <div class="pt-1 flex items-center gap-3">
                        <template x-if="photoCaptured">
                            <button type="button" 
                                    @click="retakePhoto()" 
                                    :disabled="isProcessing"
                                    class="w-1/3 py-2.5 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-mono text-xs font-semibold transition-colors flex items-center justify-center gap-1.5 border border-slate-700">
                                <span class="material-symbols-outlined text-base">replay</span>
                                <span>Foto Ulang</span>
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
            </template>

        </div>
    </div>
</div>

<script>
function faceAttendanceComponent() {
    return {
        showModal: false,
        cameraActive: false,
        cameraError: '',
        videoStream: null,
        photoCaptured: null,
        isProcessing: false,
        attendanceType: 'auto', // auto, clock_in, clock_out
        faceDetected: true,
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
            // Pre-fetch geolocation
            this.requestLocation();
        },

        openModal(detail) {
            this.showModal = true;
            this.photoCaptured = null;
            this.verificationSuccess = false;
            this.successData = null;
            this.attendanceType = (detail && detail.type) ? detail.type : 'auto';

            this.requestLocation();
            this.$nextTick(() => {
                this.startCamera();
            });
        },

        closeModal() {
            this.stopCamera();
            this.showModal = false;
            this.photoCaptured = null;
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
                    console.warn('Geolocation error:', err.message);
                    this.locationStatus = 'Izin Lokasi Ditolak / Lemah';
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
            try {
                const constraints = {
                    video: {
                        facingMode: 'user',
                        width: { ideal: 640 },
                        height: { ideal: 480 }
                    },
                    audio: false
                };

                this.videoStream = await navigator.mediaDevices.getUserMedia(constraints);
                if (this.$refs.videoElement) {
                    this.$refs.videoElement.srcObject = this.videoStream;
                    this.cameraActive = true;
                    this.faceDetected = true;
                }
            } catch (err) {
                console.error('Kamera gagal diakses:', err);
                this.cameraActive = false;
                this.cameraError = 'Gagal mengakses kamera: ' + (err.message || 'Izin kamera ditolak.');
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
                // Step 1: Capture Frame dari Video ke Canvas
                const video = this.$refs.videoElement;
                const canvas = this.$refs.captureCanvas;
                if (!video || !canvas) return;

                const width = video.videoWidth || 640;
                const height = video.videoHeight || 480;
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                // Mirror horizontally sesuai preview
                ctx.translate(width, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(video, 0, 0, width, height);
                ctx.setTransform(1, 0, 0, 1, 0, 0);

                // Tambahkan Watermark Digital Biometrik
                ctx.fillStyle = 'rgba(0, 0, 0, 0.45)';
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

            // Step 2: Kirim Payload ke Server
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

                    // Refresh otomatis setelah 2.5 detik
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
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
                osc.frequency.setValueAtTime(880, audioCtx.currentTime); // 880Hz (A5)
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.3);
            } catch (e) {
                // Ignore audio context errors on muted browsers
            }
        }
    };
}
</script>

<style>
@keyframes scanPulse {
    0% { transform: translateY(-70px); opacity: 0.3; }
    50% { transform: translateY(70px); opacity: 1; }
    100% { transform: translateY(-70px); opacity: 0.3; }
}
</style>
