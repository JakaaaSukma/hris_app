<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>500 - Kesalahan Server | GYOKAI HR</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#0b0f19] text-slate-200 min-h-screen flex items-center justify-center p-6 selection:bg-rose-500 selection:text-white">
    <div class="max-w-md w-full text-center relative">
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-64 h-64 bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative bg-[#121622] border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-2xl">
            <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400">
                <span class="material-symbols-outlined text-4xl">warning</span>
            </div>

            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 font-mono text-xs font-bold mb-4">
                ERROR 500 • INTERNAL SERVER ERROR
            </div>

            <h1 class="text-2xl font-bold text-white mb-2 tracking-tight">Terjadi Kesalahan Server</h1>
            <p class="text-slate-400 text-sm leading-relaxed mb-8">
                Sistem mendeteksi kendala tak terduga saat memproses permintaan Anda. Tim IT telah mencatat masalah ini untuk segera diperbaiki.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <button onclick="window.location.reload()" class="w-full sm:w-auto px-5 py-2.5 rounded-xl border border-slate-700 hover:bg-slate-800 text-slate-300 text-xs font-semibold transition-all">
                    Muat Ulang Halaman
                </button>
                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white text-xs font-bold transition-all shadow-lg shadow-rose-900/30">
                    <span class="material-symbols-outlined text-sm">dashboard</span>
                    <span>Ke Dashboard</span>
                </a>
            </div>
        </div>

        <div class="mt-8 text-xs text-slate-600 font-mono">
            PT. GYOKAI INDONESIA HRIS SYSTEM
        </div>
    </div>
</body>
</html>
