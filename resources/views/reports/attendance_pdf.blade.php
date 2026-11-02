<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} - {{ $date }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0c0f17;
            color: #e2e8f0;
            padding: 24px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .container { max-width: 960px; margin: 0 auto; }
        .action-bar {
            display: flex; align-items: center; justify-content: space-between;
            background: #151922; padding: 14px 20px; border-radius: 12px;
            border: 1px solid #1e293b; margin-bottom: 24px;
        }
        .btn-back { color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back:hover { color: #fff; }
        .btn-print {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #fff; border: none; padding: 10px 20px; border-radius: 10px;
            font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35);
        }
        .report-paper {
            background: #11151f; border: 1px solid #1e293b; border-radius: 18px; padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .header-section {
            display: flex; justify-content: space-between; align-items: flex-start;
            padding-bottom: 20px; border-bottom: 2px solid #243046; margin-bottom: 24px;
        }
        .brand { display: flex; align-items: center; gap: 14px; }
        .logo-box {
            width: 48px; height: 48px; border-radius: 12px; background: rgba(255, 255, 255, 0.05);
            border: 1px solid #334155; padding: 6px; display: flex; align-items: center; justify-content: center;
        }
        .logo-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .company-title { font-size: 18px; font-weight: 800; color: #ffffff; }
        .company-subtitle { font-size: 11px; color: #94a3b8; font-family: 'JetBrains Mono', monospace; margin-top: 2px; }
        .report-meta { text-align: right; font-family: 'JetBrains Mono', monospace; font-size: 11px; }
        .badge-att {
            display: inline-block; background: rgba(56, 189, 248, 0.15); color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3); padding: 3px 10px; border-radius: 20px;
            font-weight: 700; font-size: 10px; margin-bottom: 6px;
        }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 24px; }
        .card { background: #161b26; border: 1px solid #232d3f; border-radius: 12px; padding: 16px; }
        .card-label { font-size: 10px; font-weight: 600; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px; font-family: 'JetBrains Mono', monospace; }
        .card-val { font-size: 24px; font-weight: 800; color: #ffffff; }
        .table-custom { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 24px; }
        .table-custom th {
            background: #1a2232; color: #94a3b8; font-family: 'JetBrains Mono', monospace;
            font-size: 10px; text-transform: uppercase; padding: 10px 14px; text-align: left;
            border-bottom: 1px solid #243046;
        }
        .table-custom td { padding: 10px 14px; border-bottom: 1px solid #1c2434; color: #cbd5e1; }
        .status-pill {
            font-family: 'JetBrains Mono', monospace; font-size: 10px; font-weight: 700;
            padding: 2px 8px; border-radius: 6px; text-transform: uppercase;
        }
        .status-on-time { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .status-late { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .status-absent { background: rgba(244, 63, 94, 0.2); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.4); }
        .status-leave { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .status-permit { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .signatures {
            display: grid; grid-template-columns: 1fr 1fr; gap: 40px; padding-top: 30px;
            margin-top: 20px; border-top: 1px dashed #243046; text-align: center;
            font-family: 'JetBrains Mono', monospace; font-size: 11px;
        }
        .sign-title { color: #64748b; margin-bottom: 50px; }
        .sign-name { color: #ffffff; font-weight: 700; border-bottom: 1px solid #334155; display: inline-block; padding-bottom: 4px; min-width: 180px; }
        .watermark { margin-top: 20px; text-align: center; font-size: 10px; color: #475569; font-family: 'JetBrains Mono', monospace; }

        @media print {
            body { background: #fff !important; color: #000 !important; padding: 0 !important; }
            .action-bar { display: none !important; }
            .report-paper { background: #fff !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
            .company-title, .card-val, .sign-name { color: #000 !important; }
            .table-custom th { background: #f1f5f9 !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
            .table-custom td { color: #000 !important; border-bottom: 1px solid #cbd5e1 !important; }
            .card { background: #f8fafc !important; border: 1px solid #cbd5e1 !important; }
            .sign-name { border-bottom: 1px solid #000 !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="action-bar">
        <a href="{{ route('attendance.index', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn-back">
            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
            <span>Kembali ke Halaman Presensi</span>
        </a>
        <button onclick="window.print()" class="btn-print">
            <span class="material-symbols-outlined" style="font-size: 18px;">print</span>
            <span>Cetak / Unduh PDF Presensi</span>
        </button>
    </div>

    <div class="report-paper">
        <div class="header-section">
            <div class="brand">
                <div class="logo-box">
                    <img src="{{ asset('images/gyokai_logo.png') }}" alt="Gyokai Logo"/>
                </div>
                <div>
                    <h1 class="company-title">PT. GYOKAI INDONESIA</h1>
                    <p class="company-subtitle">HUMAN RESOURCES & ATTENDANCE AUDIT LOG</p>
                </div>
            </div>
            <div class="report-meta">
                <span class="badge-att">ATTENDANCE LOG REPORT</span>
                <div class="mono" style="font-weight: 700; color: #fff;">{{ $reportCode }}</div>
                <div class="mono" style="color: #64748b;">
                    @if($isRange)
                        Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}
                    @else
                        Tanggal: {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Metric Grid -->
        <div class="grid-3">
            <div class="card">
                <div class="card-label">Total Kehadiran</div>
                <div class="card-val" style="color: #38bdf8;">{{ $totalPresent }} Orang</div>
                <div class="mono" style="font-size: 10px; color: #64748b; margin-top: 4px;">Pegawai Aktif Hadir</div>
            </div>
            <div class="card">
                <div class="card-label">Tepat Waktu (On Time)</div>
                <div class="card-val" style="color: #34d399;">{{ $totalOnTime }}</div>
                <div class="mono" style="font-size: 10px; color: #64748b; margin-top: 4px;">
                    {{ $totalPresent > 0 ? round(($totalOnTime/$totalPresent)*100, 1) : 0 }}% Kepatuhan
                </div>
            </div>
            <div class="card">
                <div class="card-label">Terlambat (Late)</div>
                <div class="card-val" style="color: #fb7185;">{{ $totalLate }}</div>
                <div class="mono" style="font-size: 10px; color: #64748b; margin-top: 4px;">
                    {{ $totalPresent > 0 ? round(($totalLate/$totalPresent)*100, 1) : 0 }}% Keterlambatan
                </div>
            </div>
        </div>

        <!-- Table -->
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Nama Karyawan / NIP</th>
                    <th style="width: 15%;">Departemen</th>
                    @if($isRange)
                        <th style="width: 12%;">Tanggal</th>
                    @endif
                    <th style="width: {{ $isRange ? '10%' : '12%' }};">Clock In</th>
                    <th style="width: {{ $isRange ? '10%' : '12%' }};">Clock Out</th>
                    <th style="width: {{ $isRange ? '9%' : '10%' }};">Status</th>
                    <th style="width: {{ $isRange ? '8%' : '10%' }};">Mode</th>
                    <th style="width: {{ $isRange ? '16%' : '16%' }};">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $idx => $att)
                    <tr>
                        <td class="mono">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #fff;">{{ $att->employee->name }}</strong>
                            <div class="mono" style="font-size: 10px; color: #64748b;">{{ $att->employee->nip }}</div>
                        </td>
                        <td class="mono">{{ $att->employee->department }}</td>
                        @if($isRange)
                            <td class="mono" style="color: #38bdf8;">{{ \Carbon\Carbon::parse($att->date)->format('d/m/Y') }}</td>
                        @endif
                        <td class="mono" style="font-weight: 700; color: #fff;">{{ $att->clock_in ? substr($att->clock_in, 0, 8) . ' WIB' : '—' }}</td>
                        <td class="mono">{{ $att->clock_out ? substr($att->clock_out, 0, 8) . ' WIB' : '—' }}</td>
                        <td>
                            @php
                                $statusClass = match($att->status) {
                                    'on_time' => 'status-on-time',
                                    'late' => 'status-late',
                                    'absent' => 'status-absent',
                                    'leave' => 'status-leave',
                                    'permit' => 'status-permit',
                                    default => 'status-on-time',
                                };
                                $statusLabel = match($att->status) {
                                    'on_time' => 'Tepat Waktu',
                                    'late' => 'Terlambat',
                                    'absent' => 'Alpa',
                                    'leave' => 'Cuti',
                                    'permit' => 'Izin/Sakit',
                                    default => strtoupper($att->status),
                                };
                            @endphp
                            <span class="status-pill {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td>{{ $att->work_mode }}</td>
                        <td style="font-size: 10px; color: #94a3b8; font-family: 'JetBrains Mono', monospace;">
                            @if($att->verification_method === 'face_location')
                                <div style="color: #34d399; font-weight: bold; margin-bottom: 2px;">[Face ID + GPS]</div>
                            @endif
                            {{ $att->notes ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isRange ? 8 : 7 }}" style="text-align: center; padding: 30px; color: #64748b;" class="mono">
                            Tidak ada log presensi tercatat pada {{ $isRange ? 'rentang periode ini' : 'tanggal ' . $date }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="signatures">
            <div>
                <div class="sign-title">Petugas Pengawas Presensi (HR):</div>
                <div class="sign-name">{{ $authUser->name }}</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 4px;">{{ $authUser->role }} • HR Department</div>
            </div>
            <div>
                <div class="sign-title">Mengetahui (Pimpinan Divisi):</div>
                <div class="sign-name">Siti Rahmawati, M.Psi.</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 4px;">Head of Human Capital • Gyokai Corp</div>
            </div>
        </div>

        <div class="watermark">
            Gyokai Attendance System Automated Verification • Verifikasi Digital Hash: {{ sha1($reportCode) }}
        </div>
    </div>
</div>

</body>
</html>
