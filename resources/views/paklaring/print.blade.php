<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keterangan - {{ $paklaring->recipient_name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}"/>

    <!-- Google Fonts untuk script name persis seperti template -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Playfair+Display:ital,wght@1,700&display=swap" rel="stylesheet">

    <style>
        @font-face {
            font-family: 'Apple Chancery';
            src: url('{{ asset('fonts/AppleChancery.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #1a1e29;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-height: 100vh;
            padding: 20px 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Action Topbar (Hidden when printing) */
        .no-print-bar {
            width: 297mm;
            max-width: 95vw;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #11151f;
            border: 1px solid #2d3343;
            border-radius: 12px;
            padding: 12px 20px;
            margin-bottom: 20px;
            color: #fff;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: white;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #f43f5e, #e11d48);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #252b3b;
            color: #cbd5e1;
            border: 1px solid #3b4459;
        }

        .btn-secondary:hover {
            background: #333b50;
            color: #fff;
        }

        /* Exact Certificate Sheet (A4 Landscape: 297mm x 210mm) */
        @php
            $bgUrl = $templateUrl ?? (method_exists(\App\Http\Controllers\PaklaringController::class, 'getTemplateUrl') 
                ? \App\Http\Controllers\PaklaringController::getTemplateUrl() 
                : (file_exists(public_path('images/paklaring/exact_base_template.png'))
                    ? asset('images/paklaring/exact_base_template.png') . '?v=' . filemtime(public_path('images/paklaring/exact_base_template.png'))
                    : route('hr.paklaring.templateImage')));
        @endphp
        .certificate-sheet {
            position: relative;
            width: 297mm;
            height: 210mm;
            background-color: #ffffff !important;
            background-image: url('{!! $bgUrl !!}');
            background-size: 100% 100%;
            background-repeat: no-repeat;
            background-position: center center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            overflow: hidden;
        }

        /* Dynamic Text Overlays at Exact Coordinates */
        
        /* 1. Recipient Name: Apple Chancery Cursive Calligraphy */
        .field-recipient-name {
            position: absolute;
            top: 40.2%;
            left: 5%;
            width: 90%;
            text-align: center;
            font-family: 'Apple Chancery', cursive;
            font-size: 38px;
            font-weight: 600;
            color: #000000;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        /* 2. Body Text Lines: Arial Bold, Clean Black */
        .field-body-program {
            position: absolute;
            top: 49.8%;
            left: 8%;
            width: 84%;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 15px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.2px;
            line-height: 1.4;
        }

        .field-body-period {
            position: absolute;
            top: 53.6%;
            left: 8%;
            width: 84%;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 15px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.2px;
            line-height: 1.4;
        }

        .field-body-company {
            position: absolute;
            top: 57.3%;
            left: 8%;
            width: 84%;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 15px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.2px;
            line-height: 1.4;
        }

        /* 3. Tempat, Tanggal & Instansi: Posisi Presisi di atas Stempel & TTD */
        .field-signature-header {
            position: absolute;
            top: 66.0%;
            left: 66.8%;
            width: 30%;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .field-city-date {
            font-family: Arial, sans-serif;
            font-size: 13.5px;
            font-weight: normal;
            color: #000000;
            letter-spacing: 0.2px;
            line-height: 1.2;
        }

        .field-instansi {
            font-family: Arial, sans-serif;
            font-size: 13.5px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.2px;
            line-height: 1.2;
        }

        /* PRINT STYLES */
        @page {
            size: A4 landscape;
            margin: 0;
        }

        @media print {
            html, body {
                width: 297mm;
                height: 210mm;
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden;
            }

            .no-print-bar {
                display: none !important;
            }

            .certificate-sheet {
                box-shadow: none !important;
                margin: 0 !important;
                width: 297mm !important;
                height: 210mm !important;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar (Hidden during Print) -->
    <div class="no-print-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 36px; height: 36px; border-radius: 8px; background: rgba(225,29,72,0.15); display: flex; align-items: center; justify-content: center; border: 1px solid rgba(225,29,72,0.3);">
                <img src="{{ asset('images/gyokai_logo.png') }}" style="width: 22px; height: 22px; object-fit: contain;">
            </div>
            <div>
                <h2 style="font-size: 14px; font-weight: 700; margin: 0; color: #fff;">Pratinjau Surat Keterangan / Paklaring</h2>
                <p style="font-size: 11px; color: #94a3b8; margin: 0;">Template Resmi LPK Gyokai Indonesia • Format A4 Landscape Siap Cetak</p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('hr.paklaring.index') }}" class="btn btn-secondary">
                &larr; Kembali ke Form Input
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- The 100% Exact Certificate Sheet -->
    <div class="certificate-sheet" style="position: relative; width: 297mm; height: 210mm; background-color: #ffffff; background-image: url('{!! $bgUrl !!}'); background-size: 100% 100%; background-repeat: no-repeat; background-position: center center; overflow: hidden;">
        <!-- 1. Recipient Name -->
        <div class="field-recipient-name" style="position: absolute; top: 40.2%; left: 5%; width: 90%; text-align: center; font-family: 'Apple Chancery', cursive; font-size: 38px; font-weight: 600; color: #000000; letter-spacing: 0.5px; line-height: 1.1;">
            {{ $paklaring->recipient_name }}
        </div>

        <!-- 2. Body Text: Program / Jabatan -->
        <div class="field-body-program" style="position: absolute; top: 49.8%; left: 8%; width: 84%; text-align: center; font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; color: #000000; letter-spacing: 0.2px; line-height: 1.4;">
            {{ str_starts_with($paklaring->program_or_position, 'Telah') ? $paklaring->program_or_position : 'Telah Mengikuti ' . $paklaring->program_or_position }}
        </div>

        <!-- 3. Body Text: Periode -->
        <div class="field-body-period" style="position: absolute; top: 53.6%; left: 8%; width: 84%; text-align: center; font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; color: #000000; letter-spacing: 0.2px; line-height: 1.4;">
            {{ str_starts_with($paklaring->period_text, 'Periode') ? $paklaring->period_text : 'Periode Tanggal ' . $paklaring->period_text }}
        </div>

        <!-- 4. Body Text: Unit Perusahaan -->
        <div class="field-body-company" style="position: absolute; top: 57.3%; left: 8%; width: 84%; text-align: center; font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; color: #000000; letter-spacing: 0.2px; line-height: 1.4;">
            {{ str_starts_with($paklaring->company_unit, 'di Unit') ? $paklaring->company_unit : 'di Unit Perusahaan ' . $paklaring->company_unit }}
        </div>

        <!-- 5. Tempat & Tanggal serta Instansi LPK Gyokai -->
        <div class="field-signature-header" style="position: absolute; top: 66.0%; left: 66.8%; width: 30%; text-align: left; display: flex; flex-direction: column; gap: 4px;">
            <div class="field-city-date" style="font-family: Arial, sans-serif; font-size: 13.5px; font-weight: normal; color: #000000; letter-spacing: 0.2px; line-height: 1.2;">{{ $paklaring->city }}, {{ $issueDateFormatted }}</div>
            <div class="field-instansi" style="font-family: Arial, sans-serif; font-size: 13.5px; font-weight: bold; color: #000000; letter-spacing: 0.2px; line-height: 1.2;">LPK Gyokai Indonesia</div>
        </div>
    </div>

</body>
</html>
