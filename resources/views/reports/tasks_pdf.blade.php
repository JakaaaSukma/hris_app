<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportTitle }} - {{ $department }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 15mm 15mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            background: #ffffff;
            font-size: 11px;
            line-height: 1.4;
        }

        .header-table {
            width: 100%;
            border-bottom: 2.5px solid #881337;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .company-logo-text {
            font-size: 18px;
            font-weight: 800;
            color: #881337;
            letter-spacing: -0.5px;
        }

        .company-subtext {
            font-size: 9.5px;
            color: #475569;
            margin-top: 1px;
        }

        .doc-badge {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: ui-monospace, monospace;
            font-size: 9px;
            color: #334155;
            text-align: right;
            display: inline-block;
        }

        .title-block {
            text-align: center;
            margin-bottom: 12px;
        }

        .title-block h1 {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .title-block p {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Metadata Strip */
        .meta-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 14px;
            display: table;
            width: 100%;
        }

        .meta-col {
            display: table-cell;
            width: 25%;
            vertical-align: top;
        }

        .meta-label {
            font-size: 8.5px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            font-family: ui-monospace, monospace;
        }

        .meta-val {
            font-size: 10.5px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 1px;
        }

        /* KPI Metric Cards */
        .kpi-grid {
            display: table;
            width: 100%;
            margin-bottom: 16px;
        }

        .kpi-card {
            display: table-cell;
            width: 25%;
            padding: 8px 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
        }

        .kpi-card:not(:last-child) {
            border-right: none;
        }

        .kpi-card-title {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            font-family: ui-monospace, monospace;
        }

        .kpi-card-val {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }

        .kpi-card-sub {
            font-size: 8.5px;
            color: #475569;
            margin-top: 1px;
        }

        /* Section Headings */
        .section-heading {
            font-size: 11px;
            font-weight: 800;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 6px;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            margin-bottom: 16px;
        }

        table.data-table th {
            background: #1e293b;
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8.5px;
            padding: 6px 7px;
            border: 1px solid #0f172a;
            text-align: left;
            vertical-align: middle;
        }

        table.data-table td {
            padding: 5px 7px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 700;
            font-family: ui-monospace, monospace;
            text-transform: uppercase;
        }

        .badge-completed { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-progress { background: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }
        .badge-review { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .badge-todo { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .badge-urgent { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
        .badge-high { background: #ffedd5; color: #c2410c; border: 1px solid #fdba74; }
        .badge-medium { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
        .badge-low { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }

        /* Progress Bar */
        .prog-container {
            width: 100%;
            background: #e2e8f0;
            border-radius: 4px;
            height: 6px;
            overflow: hidden;
            margin-top: 3px;
        }

        .prog-bar {
            height: 100%;
            border-radius: 4px;
        }

        /* Signatures */
        .sig-table {
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }

        .sig-box {
            width: 45%;
            text-align: center;
            vertical-align: top;
        }

        .sig-line {
            width: 180px;
            margin: 45px auto 4px auto;
            border-bottom: 1.5px solid #0f172a;
        }

        /* Print Controls */
        .no-print {
            background: #0f172a;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .no-print button {
            background: #059669;
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 11px;
        }

        @media print {
            .no-print { display: none !important; }
            body { font-size: 9.5px; }
            @page { margin: 10mm 12mm; }
        }
    </style>
</head>
<body>

    <!-- Non-printable Toolbar -->
    <div class="no-print">
        <div>
            <strong>Preview Laporan Task & KPI:</strong> Divisi {{ $department }} • Periode {{ $periodText }}
        </div>
        <button onclick="window.print()">
            🖨️ Cetak / Simpan sebagai PDF
        </button>
    </div>

    <!-- Official Header -->
    <table class="header-table">
        <tr>
            <td>
                <div class="company-logo-text">PT GYOKAI GLOBAL INDONESIA</div>
                <div class="company-subtext">People Operations, Talent Management & Performance Analytics</div>
                <div class="company-subtext">Dokumen Laporan Resmi Internal Perusahaan</div>
            </td>
            <td style="text-align: right;">
                <div class="doc-badge">
                    <strong>KODE:</strong> RPT-TSK-{{ strtoupper(date('Ymd-His')) }}<br>
                    <strong>TGL:</strong> {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB
                </div>
            </td>
        </tr>
    </table>

    <!-- Title Block -->
    <div class="title-block">
        <h1>Laporan Tugas, Progres Kerja & Key Performance Indicator (KPI)</h1>
        <p>Evaluasi Pencapaian Target Kerja Tim Divisi {{ $department }} • Periode {{ $periodText }}</p>
    </div>

    <!-- Metadata Strip -->
    <div class="meta-box">
        <div class="meta-col">
            <div class="meta-label">Departemen:</div>
            <div class="meta-val" style="color: #0284c7;">{{ $department }}</div>
        </div>
        <div class="meta-col">
            <div class="meta-label">Periode Waktu:</div>
            <div class="meta-val">{{ $period === '1_month' ? '1 Bulan Terakhir' : '1 Minggu Terakhir' }}</div>
        </div>
        <div class="meta-col">
            <div class="meta-label">Pencetak / Penanggung Jawab:</div>
            <div class="meta-val">{{ $authUser->name }} ({{ strtoupper($authUser->role) }})</div>
        </div>
        <div class="meta-col">
            <div class="meta-label">Skor Indeks KPI Divisi:</div>
            <div class="meta-val" style="color: {{ $kpiScore >= 75 ? '#059669' : ($kpiScore >= 60 ? '#d97706' : '#dc2626') }};">
                {{ $kpiScore }} / 100 <span style="font-size: 8.5px; font-weight: normal;">({{ $kpiGrade }})</span>
            </div>
        </div>
    </div>

    <!-- Executive KPI Metric Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-card-title">Total Beban Tugas</div>
            <div class="kpi-card-val" style="color: #0284c7;">{{ $totalTasks }}</div>
            <div class="kpi-card-sub">{{ $tasks->where('is_daily', true)->count() }} Daily Task • {{ $tasks->where('scope', 'department')->count() }} Tim</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-title">Tugas Selesai (100%)</div>
            <div class="kpi-card-val" style="color: #059669;">{{ $completedTasks }}</div>
            <div class="kpi-card-sub">Tingkat Penyelesaian: <strong>{{ $completionRate }}%</strong></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-title">Rata-rata Progres Tim</div>
            <div class="kpi-card-val" style="color: #7c3aed;">{{ $avgProgress }}%</div>
            <div class="kpi-card-sub">{{ $inProgressTasks }} Berjalan • {{ $reviewTasks }} Review</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-title">Ketepatan Waktu (On-Time)</div>
            <div class="kpi-card-val" style="color: #0d9488;">{{ $onTimeRate }}%</div>
            <div class="kpi-card-sub">{{ $onTimeCount }} Selesai Tepat / Lebih Cepat</div>
        </div>
    </div>

    <!-- 1. Ringkasan Performa & KPI Per PIC -->
    <div class="section-heading">
        <span>1. Ringkasan Performa & KPI Per PIC (Anggota Tim)</span>
        <span style="font-size: 9px; font-weight: normal; color: #64748b;">Total {{ count($picStats) }} Anggota Terdaftar</span>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 26px; text-align: center;">No</th>
                <th style="width: 140px;">Nama PIC</th>
                <th style="width: 70px; text-align: center;">NIP</th>
                <th style="width: 90px;">Jabatan</th>
                <th style="width: 50px; text-align: center;">Total Task</th>
                <th style="width: 50px; text-align: center;">Selesai</th>
                <th style="width: 50px; text-align: center;">Berjalan</th>
                <th style="width: 90px; text-align: center;">Rata Progres</th>
                <th style="width: 80px; text-align: center;">Skor KPI</th>
            </tr>
        </thead>
        <tbody>
            @forelse($picStats as $idx => $pic)
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $idx + 1 }}</td>
                    <td><strong>{{ $pic['name'] }}</strong></td>
                    <td style="text-align: center; font-family: monospace;">{{ $pic['nip'] }}</td>
                    <td>{{ $pic['role_title'] }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ $pic['total_tasks'] }}</td>
                    <td style="text-align: center; color: #059669; font-weight: bold;">{{ $pic['completed'] }}</td>
                    <td style="text-align: center; color: #2563eb;">{{ $pic['in_progress'] }}</td>
                    <td style="text-align: center;">
                        <span style="font-weight: bold;">{{ $pic['avg_progress'] }}%</span>
                        <div class="prog-container">
                            <div class="prog-bar" style="width: {{ $pic['avg_progress'] }}%; background: {{ $pic['avg_progress'] >= 75 ? '#10b981' : ($pic['avg_progress'] >= 40 ? '#3b82f6' : '#f59e0b') }};"></div>
                        </div>
                    </td>
                    <td style="text-align: center; font-weight: bold; color: {{ $pic['kpi_score'] >= 75 ? '#059669' : ($pic['kpi_score'] >= 50 ? '#d97706' : '#64748b') }};">
                        {{ $pic['kpi_score'] }} pts
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #64748b; padding: 10px;">Tidak ada data anggota tim pada departemen ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 2. Detail Daftar Tugas & Progres Kerja -->
    <div class="section-heading" style="margin-top: 10px;">
        <span>2. Detail Daftar Tugas & Progres Kerja</span>
        <span style="font-size: 9px; font-weight: normal; color: #64748b;">Total {{ $tasks->count() }} Tugas Ditemukan</span>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 22px; text-align: center;">No</th>
                <th style="width: 130px;">Judul Tugas</th>
                <th>Detail / Deskripsi</th>
                <th style="width: 65px; text-align: center;">Skala</th>
                <th style="width: 85px;">PIC</th>
                <th style="width: 45px; text-align: center;">Prioritas</th>
                <th style="width: 55px; text-align: center;">Tenggat</th>
                <th style="width: 60px; text-align: center;">Status</th>
                <th style="width: 65px; text-align: center;">Progres</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tasks as $i => $task)
                @php
                    $isDone = $task->status === 'completed';
                    $isOverdue = !$isDone && $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast();
                    $picName = $task->assignedEmployee->name ?? ($task->scope === 'department' ? 'Seluruh Tim' : '-');
                @endphp
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $i + 1 }}</td>
                    <td>
                        <strong style="color: #0f172a;">{{ $task->title }}</strong>
                        @if($task->is_daily)
                            <div style="font-size: 8px; color: #7c3aed; font-weight: bold;">(Daily Task {{ $task->task_date ? \Carbon\Carbon::parse($task->task_date)->format('d/m') : '' }})</div>
                        @endif
                    </td>
                    <td style="color: #475569; font-size: 8.5px;">
                        {{ $task->description ? \Illuminate\Support\Str::limit($task->description, 100) : '—' }}
                    </td>
                    <td style="text-align: center;">
                        <span style="font-size: 8px; font-weight: bold; text-transform: uppercase;">
                            {{ $task->scope === 'department' ? 'TIM DEPT' : 'INDIVIDU' }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $picName }}</strong>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $task->priority }}">{{ $task->priority }}</span>
                    </td>
                    <td style="text-align: center; font-size: 8.5px; {{ $isOverdue ? 'color: #dc2626; font-weight: bold;' : '' }}">
                        {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : '—' }}
                        @if($isOverdue)
                            <div style="font-size: 7.5px;">(Terlambat)</div>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $task->status }}">
                            {{ $task->status === 'completed' ? 'SELESAI' : ($task->status === 'in_progress' ? 'BERJALAN' : ($task->status === 'review' ? 'REVIEW' : 'TODO')) }}
                        </span>
                    </td>
                    <td style="text-align: center;">
                        <strong style="color: {{ $task->progress_percentage == 100 ? '#059669' : '#1e293b' }};">{{ $task->progress_percentage }}%</strong>
                        <div class="prog-container">
                            <div class="prog-bar" style="width: {{ $task->progress_percentage }}%; background: {{ $task->progress_percentage == 100 ? '#10b981' : ($task->progress_percentage >= 50 ? '#3b82f6' : '#f59e0b') }};"></div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #64748b; padding: 12px;">Tidak ada catatan tugas pada rentang periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Signature / Approval Block -->
    <table class="sig-table">
        <tr>
            <td class="sig-box">
                <div style="font-size: 9.5px; color: #475569;">Dibuat / Dilaporkan Oleh:</div>
                <div style="font-size: 10px; font-weight: bold; margin-top: 1px;">Manager Departemen {{ $department }}</div>
                <div class="sig-line"></div>
                <div style="font-weight: bold; font-size: 10.5px;">{{ $authUser->name }}</div>
                <div style="font-size: 8.5px; color: #64748b;">NIP: {{ $authUser->employee->nip ?? '-' }}</div>
            </td>
            <td style="width: 10%;"></td>
            <td class="sig-box">
                <div style="font-size: 9.5px; color: #475569;">Mengetahui & Menyetujui:</div>
                <div style="font-size: 10px; font-weight: bold; margin-top: 1px;">Direktur Utama Perusahaan</div>
                <div class="sig-line"></div>
                <div style="font-weight: bold; font-size: 10.5px;">Prakoso S.T., M.M.</div>
                <div style="font-size: 8.5px; color: #64748b;">PT GYOKAI GLOBAL INDONESIA</div>
            </td>
        </tr>
    </table>

</body>
</html>
