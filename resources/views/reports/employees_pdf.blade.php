<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} - {{ date('d-m-Y') }}</title>
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
        .container { max-width: 1100px; margin: 0 auto; }
        
        .action-bar {
            display: flex; align-items: center; justify-content: space-between;
            background: #151922; padding: 14px 20px; border-radius: 14px;
            border: 1px solid #1e293b; margin-bottom: 24px;
        }
        .btn-back { color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .btn-back:hover { color: #fff; }
        .btn-group { display: flex; align-items: center; gap: 10px; }
        .btn-csv {
            background: #064e3b; color: #34d399; border: 1px solid #059669;
            padding: 9px 16px; border-radius: 10px; font-size: 12px; font-weight: 700;
            cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;
            transition: all 0.2s;
        }
        .btn-csv:hover { background: #047857; color: #fff; }
        .btn-print {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: #fff; border: none; padding: 9px 18px; border-radius: 10px;
            font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35); transition: all 0.2s;
        }
        .btn-print:hover { filter: brightness(1.1); transform: translateY(-1px); }

        .report-paper {
            background: #11151f; border: 1px solid #1e293b; border-radius: 18px; padding: 36px 40px;
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
        .company-title { font-size: 18px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em; }
        .company-subtitle { font-size: 11px; color: #94a3b8; font-family: 'JetBrains Mono', monospace; margin-top: 2px; }
        .report-meta { text-align: right; font-family: 'JetBrains Mono', monospace; font-size: 11px; }
        .badge-doc {
            display: inline-block; background: rgba(225, 29, 72, 0.15); color: #fb7185;
            border: 1px solid rgba(225, 29, 72, 0.3); padding: 3px 10px; border-radius: 20px;
            font-weight: 700; font-size: 10px; margin-bottom: 6px;
        }

        .filter-tags { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; font-family: 'JetBrains Mono', monospace; font-size: 11px; }
        .filter-tag { background: #1a2232; border: 1px solid #283548; padding: 4px 10px; border-radius: 8px; color: #94a3b8; }
        .filter-tag strong { color: #f1f5f9; }

        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 24px; }
        .card { background: #161b26; border: 1px solid #232d3f; border-radius: 12px; padding: 14px 16px; }
        .card-label { font-size: 10px; font-weight: 600; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px; font-family: 'JetBrains Mono', monospace; }
        .card-val { font-size: 22px; font-weight: 800; color: #ffffff; }

        .table-custom { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 28px; }
        .table-custom th {
            background: #1a2232; color: #94a3b8; font-family: 'JetBrains Mono', monospace;
            font-size: 10px; text-transform: uppercase; padding: 10px 12px; text-align: left;
            border-bottom: 1px solid #243046;
        }
        .table-custom td { padding: 10px 12px; border-bottom: 1px solid #1c2434; color: #cbd5e1; vertical-align: top; }
        .table-custom tr:hover td { background: rgba(255, 255, 255, 0.02); }

        .pill {
            font-family: 'JetBrains Mono', monospace; font-size: 9px; font-weight: 700;
            padding: 2px 7px; border-radius: 6px; text-transform: uppercase; display: inline-block;
        }
        .pill-active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .pill-inactive { background: rgba(244, 63, 94, 0.15); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.3); }
        .pill-role { background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); }
        .pill-dept { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }

        .signatures {
            display: grid; grid-template-columns: 1fr 1fr; gap: 40px; padding-top: 30px;
            margin-top: 20px; border-top: 1px dashed #243046; text-align: center;
            font-family: 'JetBrains Mono', monospace; font-size: 11px;
        }
        .sign-title { color: #64748b; margin-bottom: 60px; }
        .sign-name { color: #ffffff; font-weight: 700; border-bottom: 1px solid #334155; display: inline-block; padding-bottom: 4px; min-width: 190px; }
        .sign-role { color: #94a3b8; font-size: 10px; margin-top: 4px; }
        .watermark { margin-top: 24px; text-align: center; font-size: 10px; color: #475569; font-family: 'JetBrains Mono', monospace; }

        @media print {
            body { background: #fff !important; color: #000 !important; padding: 0 !important; }
            .action-bar { display: none !important; }
            .report-paper { background: #fff !important; border: none !important; box-shadow: none !important; padding: 0 !important; }
            .company-title, .card-val, .sign-name, .table-custom td strong { color: #000 !important; }
            .company-subtitle, .card-label, .table-custom th, .sign-title, .sign-role, .watermark { color: #555 !important; }
            .table-custom th { background: #f1f5f9 !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
            .table-custom td { color: #000 !important; border-bottom: 1px solid #cbd5e1 !important; }
            .card { background: #f8fafc !important; border: 1px solid #cbd5e1 !important; }
            .sign-name { border-bottom: 1px solid #000 !important; }
            .filter-tag { background: #f1f5f9 !important; border: 1px solid #cbd5e1 !important; color: #333 !important; }
            .filter-tag strong { color: #000 !important; }
            .pill { border: 1px solid #888 !important; color: #000 !important; background: #eee !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Action Bar (Hidden on Print) -->
    <div class="action-bar">
        <a href="{{ route('employees.index') }}" class="btn-back">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            <span>Kembali ke Direktori</span>
        </a>
        <div class="btn-group">
            <a href="{{ route('employees.export.excel', request()->all()) }}" class="btn-csv" title="Unduh File Spreadsheet Excel (.xlsx)">
                <span class="material-symbols-outlined text-base">file_download</span>
                <span>Unduh Excel (.xlsx)</span>
            </a>
            <button onclick="window.print()" class="btn-print" title="Cetak atau Simpan sebagai PDF">
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    <!-- Printable Paper Area -->
    <div class="report-paper">
        <!-- Header -->
        <div class="header-section">
            <div class="brand">
                <div class="logo-box">
                    <img src="{{ asset('images/logo.png') }}" onerror="this.src='https://ui-avatars.com/api/?name=GY&background=e11d48&color=fff'" alt="Logo Gyokai">
                </div>
                <div>
                    <h1 class="company-title">PT GYOKAI GLOBAL INDONESIA</h1>
                    <p class="company-subtitle">LPK GYOKAI INDONESIA &bull; HR & PEOPLE OPERATIONS DIVISION</p>
                </div>
            </div>
            <div class="report-meta">
                <div class="badge-doc">DOKUMEN RESMI HR</div>
                <div><strong>KODE:</strong> {{ $reportCode }}</div>
                <div><strong>DICETAK:</strong> {{ $generatedAt }}</div>
                <div style="color: #64748b; font-size: 10px; margin-top: 2px;">
                    Oleh: {{ $authUser->name }} ({{ strtoupper($authUser->role) }})
                </div>
            </div>
        </div>

        <!-- Filter Parameters Information -->
        <div class="filter-tags">
            <div class="filter-tag">Divisi / Dept: <strong>{{ $selectedDept ?: 'Semua Departemen' }}</strong></div>
            <div class="filter-tag">Status Kepegawaian: <strong>{{ $selectedStatus ?: 'Semua Status' }}</strong></div>
            <div class="filter-tag">Ikatan Kerja: <strong>{{ $selectedType ?: 'Semua Tipe' }}</strong></div>
            <div class="filter-tag">Total Data Ditemukan: <strong>{{ $totalEmployees }} Orang</strong></div>
        </div>

        <!-- 4-Metric Grid Summary -->
        <div class="grid-4">
            <div class="card">
                <div class="card-label">Total Karyawan</div>
                <div class="card-val">{{ $totalEmployees }} <span style="font-size: 11px; font-weight: normal; color: #94a3b8;">Orang</span></div>
            </div>
            <div class="card">
                <div class="card-label">Status Aktif</div>
                <div class="card-val" style="color: #34d399;">{{ $totalActive }} <span style="font-size: 11px; font-weight: normal; color: #94a3b8;">Orang</span></div>
            </div>
            <div class="card">
                <div class="card-label">Karyawan Tetap</div>
                <div class="card-val" style="color: #60a5fa;">{{ $totalPermanent }} <span style="font-size: 11px; font-weight: normal; color: #94a3b8;">Orang</span></div>
            </div>
            <div class="card">
                <div class="card-label">Kontrak / Magang</div>
                <div class="card-val" style="color: #f59e0b;">{{ $totalContract }} <span style="font-size: 11px; font-weight: normal; color: #94a3b8;">Orang</span></div>
            </div>
        </div>

        <!-- Main Employee Data Table -->
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th>NIP</th>
                    <th>Nama Karyawan</th>
                    <th>Departemen</th>
                    <th>Jabatan & Akses</th>
                    <th>Ikatan Kerja</th>
                    <th>Status</th>
                    <th>Kontak (Email / Telp)</th>
                    <th>Tgl Masuk</th>
                    <th style="text-align: center;">Sisa Cuti</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $index => $emp)
                    @php
                        $remaining = max(0, ($emp->annual_leave_quota ?? 12) - ($emp->annual_leave_used ?? 0));
                        $roleName = $emp->user ? strtoupper($emp->user->role) : 'NON-AKUN';
                    @endphp
                    <tr>
                        <td style="text-align: center;" class="mono">{{ $index + 1 }}</td>
                        <td class="mono" style="font-weight: 700; color: #f1f5f9;">{{ $emp->nip ?? '-' }}</td>
                        <td>
                            <strong style="color: #ffffff; font-size: 12px; display: block;">{{ $emp->name }}</strong>
                            <span class="mono" style="font-size: 10px; color: #94a3b8;">{{ $emp->location ?? 'Kantor Pusat' }}</span>
                        </td>
                        <td>
                            <span class="pill pill-dept">{{ $emp->department ?? '-' }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #e2e8f0;">{{ $emp->role_title ?? '-' }}</div>
                            <span class="pill pill-role" style="margin-top: 2px;">{{ $roleName }}</span>
                        </td>
                        <td>{{ $emp->employment_type ?? 'Tetap' }}</td>
                        <td>
                            <span class="pill {{ strtolower($emp->status ?? '') === 'active' ? 'pill-active' : 'pill-inactive' }}">
                                {{ $emp->status ?? 'Active' }}
                            </span>
                        </td>
                        <td class="mono" style="font-size: 10px;">
                            <div>{{ $emp->email ?? '-' }}</div>
                            <div style="color: #94a3b8;">{{ $emp->phone ?? '-' }}</div>
                        </td>
                        <td class="mono" style="font-size: 10px; white-space: nowrap;">
                            {{ $emp->join_date ? \Carbon\Carbon::parse($emp->join_date)->format('d/m/Y') : '-' }}
                        </td>
                        <td style="text-align: center;" class="mono font-bold">
                            <span style="color: {{ $remaining > 3 ? '#34d399' : '#fb7185' }};">
                                {{ $remaining }}
                            </span> / {{ $emp->annual_leave_quota ?? 12 }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 30px; color: #94a3b8;" class="mono">
                            Tidak ada data karyawan yang sesuai dengan kriteria filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Signatures Section -->
        <div class="signatures">
            <div>
                <div class="sign-title">Diterbitkan & Diverifikasi Oleh:</div>
                <div class="sign-name">{{ $authUser->name }}</div>
                <div class="sign-role">Manager HR / Penanggung Jawab Personalia</div>
            </div>
            <div>
                <div class="sign-title">Mengetahui & Menyetujui:</div>
                <div class="sign-name">{{ $director?->name ?? 'Direktur Utama' }}</div>
                <div class="sign-role">Direktur Utama PT GYOKAI GLOBAL INDONESIA</div>
            </div>
        </div>

        <!-- Watermark / Footer -->
        <div class="watermark">
            Dokumen ini dihasilkan secara otomatis oleh Sistem HRIS GYOKAI Cloud &bull; Rahasia & Khusus Internal Perusahaan &bull; Halaman 1 dari 1
        </div>
    </div>
</div>

</body>
</html>
