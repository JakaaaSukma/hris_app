<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} - {{ $reportCode }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #0c0f17;
            color: #e2e8f0;
            padding: 24px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .mono {
            font-family: 'JetBrains Mono', monospace;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #151922;
            padding: 14px 20px;
            border-radius: 12px;
            border: 1px solid #1e293b;
            margin-bottom: 24px;
        }
        .btn-back {
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-back:hover {
            color: #fff;
        }
        .btn-print {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35);
        }
        .btn-print:hover {
            opacity: 0.95;
        }
        .report-paper {
            background: #11151f;
            border: 1px solid #1e293b;
            border-radius: 18px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 24px;
            border-bottom: 2px solid #243046;
            margin-bottom: 28px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .logo-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #334155;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .company-title {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .company-subtitle {
            font-size: 11px;
            color: #94a3b8;
            font-family: 'JetBrains Mono', monospace;
            margin-top: 2px;
        }
        .report-meta {
            text-align: right;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
        }
        .badge-kpi {
            display: inline-block;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 10px;
            margin-bottom: 6px;
        }
        .report-code {
            font-weight: 700;
            color: #f1f5f9;
        }
        .report-date {
            color: #64748b;
            margin-top: 2px;
        }
        .report-title-box {
            background: linear-gradient(135deg, rgba(225, 29, 72, 0.08), rgba(99, 102, 241, 0.08));
            border: 1px solid #293548;
            border-radius: 14px;
            padding: 20px 24px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .main-heading {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
        }
        .sub-heading {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #f43f5e;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }
        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 28px;
        }
        .card {
            background: #161b26;
            border: 1px solid #232d3f;
            border-radius: 12px;
            padding: 16px;
        }
        .card-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 6px;
            font-family: 'JetBrains Mono', monospace;
        }
        .card-value {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
        }
        .card-sub {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
            font-family: 'JetBrains Mono', monospace;
        }
        .progress-bar-bg {
            background: #232d3f;
            height: 6px;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 4px;
        }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 28px;
        }
        .table-custom th {
            background: #1a2232;
            color: #94a3b8;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            text-transform: uppercase;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #243046;
        }
        .table-custom td {
            padding: 14px 16px;
            border-bottom: 1px solid #1c2434;
            color: #cbd5e1;
        }
        .table-custom tr:hover td {
            background: #141924;
        }
        .score-pill {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 6px;
        }
        .score-high {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .score-med {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding-top: 36px;
            margin-top: 20px;
            border-top: 1px dashed #243046;
            text-align: center;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
        }
        .sign-title {
            color: #64748b;
            margin-bottom: 60px;
        }
        .sign-name {
            color: #ffffff;
            font-weight: 700;
            border-bottom: 1px solid #334155;
            display: inline-block;
            padding-bottom: 4px;
            min-width: 200px;
        }
        .sign-nip {
            color: #64748b;
            font-size: 10px;
            margin-top: 4px;
        }
        .watermark-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 10px;
            color: #475569;
            font-family: 'JetBrains Mono', monospace;
        }

        /* PRINT STYLES FOR CLEAN PDF / INK SAVING */
        @media print {
            body {
                background-color: #ffffff !important;
                color: #0f172a !important;
                padding: 0 !important;
            }
            .action-bar {
                display: none !important;
            }
            .report-paper {
                background: #ffffff !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .header-section {
                border-bottom: 2px solid #0f172a !important;
            }
            .company-title, .main-heading, .card-value, .sign-name {
                color: #0f172a !important;
            }
            .company-subtitle, .report-date, .sub-heading, .card-sub, .sign-title {
                color: #475569 !important;
            }
            .report-title-box {
                background: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
            }
            .card {
                background: #f8fafc !important;
                border: 1px solid #e2e8f0 !important;
            }
            .card-label {
                color: #475569 !important;
            }
            .table-custom th {
                background: #f1f5f9 !important;
                color: #334155 !important;
                border-bottom: 2px solid #cbd5e1 !important;
            }
            .table-custom td {
                border-bottom: 1px solid #e2e8f0 !important;
                color: #1e293b !important;
            }
            .progress-bar-bg {
                background: #e2e8f0 !important;
            }
            .signatures {
                border-top: 1px dashed #94a3b8 !important;
            }
            .sign-name {
                border-bottom: 1px solid #0f172a !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Action Bar -->
    <div class="action-bar">
        <a href="{{ route('dashboard') }}" class="btn-back">
            <span class="material-symbols-outlined" style="font-size: 18px;">arrow_back</span>
            <span>Kembali ke Dashboard Utama</span>
        </a>
        <button onclick="window.print()" class="btn-print">
            <span class="material-symbols-outlined" style="font-size: 18px;">print</span>
            <span>Cetak / Unduh PDF Laporan</span>
        </button>
    </div>

    <!-- Official Report Document -->
    <div class="report-paper">
        <!-- Header -->
        <div class="header-section">
            <div class="brand">
                <div class="logo-box">
                    <img src="{{ asset('images/gyokai_logo.png') }}" alt="Gyokai Logo"/>
                </div>
                <div>
                    <h1 class="company-title">PT. GYOKAI INDONESIA</h1>
                    <p class="company-subtitle">HUMAN CAPITAL & PERFORMANCE MANAGEMENT SYSTEM</p>
                </div>
            </div>
            <div class="report-meta">
                <span class="badge-kpi">OFFICIAL AUDIT REPORT</span>
                <div class="report-code">{{ $reportCode }}</div>
                <div class="report-date">Diterbitkan: {{ now()->translatedFormat('d F Y - H:i') }} WIB</div>
                <div class="report-date">Oleh: {{ $authUser->name }} ({{ $authUser->role }})</div>
            </div>
        </div>

        <!-- Title Box -->
        <div class="report-title-box">
            <div>
                <h2 class="main-heading">{{ $reportTitle }}</h2>
                <p class="sub-heading">Evaluasi pencapaian target kinerja periode {{ strtoupper($period) }} • Tahun Buku 2026</p>
            </div>
            <div class="mono" style="text-align: right; font-size: 11px;">
                <span style="color: #64748b;">Periode Aktif:</span><br>
                <strong style="color: #f43f5e;">{{ \Carbon\Carbon::parse($startOfWeek)->format('d M') }} - {{ \Carbon\Carbon::parse($endOfWeek)->format('d M Y') }}</strong>
            </div>
        </div>

        @if(!empty($individualKpi))
            <!-- SECTION INDIVIDUAL KPI -->
            <div class="section-title">
                <span class="material-symbols-outlined" style="font-size: 18px;">person</span>
                <span>1. Evaluasi & Indeks Capaian Individu Pegawai</span>
            </div>

            <div class="grid-4">
                <div class="card">
                    <div class="card-label">Skor Akhir KPI</div>
                    <div class="card-value" style="color: #38bdf8;">{{ $individualKpi['overallScore'] }} <span style="font-size: 13px; font-weight: 500;">/ 100</span></div>
                    <div class="card-sub">Indeks Gabungan</div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: {{ $individualKpi['overallScore'] }}%; background: linear-gradient(90deg, #38bdf8, #0ea5e9);"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-label">KPI Daily (Harian)</div>
                    <div class="card-value" style="color: #f43f5e;">{{ $individualKpi['daily']['score'] }} pts</div>
                    <div class="card-sub">{{ $individualKpi['daily']['status'] }}</div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: {{ $individualKpi['daily']['score'] }}%; background: #f43f5e;"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-label">KPI Weekly (Mingguan)</div>
                    <div class="card-value" style="color: #a855f7;">{{ $individualKpi['weekly']['score'] }} pts</div>
                    <div class="card-sub">{{ $individualKpi['weekly']['attendedDays'] }} hari hadir minggu ini</div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: {{ $individualKpi['weekly']['score'] }}%; background: #a855f7;"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-label">Task Completion</div>
                    <div class="card-value" style="color: #34d399;">{{ $individualKpi['taskRate'] }}%</div>
                    <div class="card-sub">{{ $individualKpi['completedTasks'] }} dari {{ $individualKpi['totalTasks'] }} tugas</div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: {{ $individualKpi['taskRate'] }}%; background: #34d399;"></div>
                    </div>
                </div>
            </div>
        @endif

        <!-- SECTION DEPARTMENT KPIS -->
        <div class="section-title" style="margin-top: 10px;">
            <span class="material-symbols-outlined" style="font-size: 18px;">corporate_fare</span>
            <span>2. Indeks Kinerja Kunci Departemen Perusahaan (Key Performance Indicators)</span>
        </div>

        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 25%;">Departemen / Divisi</th>
                    <th style="width: 15%;">KPI Daily</th>
                    <th style="width: 15%;">KPI Weekly</th>
                    <th style="width: 15%;">Akumulasi Skor</th>
                    <th style="width: 30%;">Indikator & Realisasi Utama</th>
                </tr>
            </thead>
            <tbody>
                @foreach($departmentKpis as $code => $dept)
                    <tr>
                        <td>
                            <strong style="color: #ffffff; font-size: 13px;">{{ $dept['name'] }}</strong>
                            <div style="font-size: 10px; color: #64748b; margin-top: 2px;">{{ $dept['description'] ?? 'Divisi Operasional' }}</div>
                        </td>
                        <td class="mono">
                            <span class="score-pill {{ $dept['dailyScore'] >= 80 ? 'score-high' : 'score-med' }}">
                                {{ $dept['dailyScore'] }}%
                            </span>
                        </td>
                        <td class="mono">
                            <span class="score-pill {{ $dept['weeklyScore'] >= 80 ? 'score-high' : 'score-med' }}">
                                {{ $dept['weeklyScore'] }}%
                            </span>
                        </td>
                        <td class="mono">
                            <span class="score-pill {{ $dept['score'] >= 80 ? 'score-high' : 'score-med' }}" style="font-size: 12px; font-weight: 800;">
                                {{ $dept['score'] }} / 100
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 4px; font-size: 11px;">
                                @foreach($dept['metrics'] as $label => $val)
                                    <div style="display: flex; justify-content: space-between; border-bottom: 1px dotted rgba(255,255,255,0.06); padding-bottom: 2px;">
                                        <span style="color: #94a3b8;">{{ $label }}:</span>
                                        <strong class="mono" style="color: #ffffff;">{{ $val }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signatures & Verification -->
        <div class="signatures">
            <div>
                <div class="sign-title">Disusun & Diajukan Oleh:</div>
                <div class="sign-name">{{ $authUser->name }}</div>
                <div class="sign-nip">{{ strtoupper($authUser->role) }} • {{ $authUser->department ?? 'General' }}</div>
            </div>
            <div>
                <div class="sign-title">Divalidasi & Disahkan Oleh:</div>
                <div class="sign-name">{{ $director?->name ?? 'Direktur Utama' }}</div>
                <div class="sign-nip">Direktur Utama • PT. Gyokai Indonesia</div>
            </div>
        </div>

        <div class="watermark-footer">
            Dokumen ini dihasilkan secara otomatis oleh Gyokai HR Core Intelligence System. Dicetak pada {{ now()->format('Y-m-d H:i:s') }}. Kode Keamanan: {{ sha1($reportCode) }}.
        </div>
    </div>
</div>

</body>
</html>
