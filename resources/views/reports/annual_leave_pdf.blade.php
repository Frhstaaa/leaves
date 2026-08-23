<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Cuti Karyawan Tahun {{ $year }} - {{ $companyName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
            font-size: 10.5px;
            line-height: 1.3;
        }

        .no-print {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 14px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
            color: white;
            box-shadow: 0 2px 6px rgba(5, 150, 105, 0.3);
        }
        .btn-primary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .report-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* HEADER SECTION */
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 14px;
        }

        .logo-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-img {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 10px;
        }

        .logo-placeholder {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #059669, #0d9488);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 900;
            font-size: 20px;
        }

        .title-box {
            text-align: center;
            flex-grow: 1;
        }

        .company-title {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            margin: 0;
            text-transform: uppercase;
        }

        .report-subtitle {
            font-size: 14px;
            font-weight: 800;
            color: #059669;
            margin: 3px 0 0 0;
            letter-spacing: 0.3px;
        }

        .meta-info {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 14px;
            margin-bottom: 14px;
            font-size: 10.5px;
            font-weight: 600;
            color: #475569;
        }

        /* MATRIX TABLE */
        table.matrix-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #64748b;
            font-size: 10px;
            margin-bottom: 20px;
        }

        table.matrix-table th,
        table.matrix-table td {
            border: 1px solid #94a3b8;
            padding: 4.5px 4px;
            text-align: center;
        }

        table.matrix-table thead tr:first-child th {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.3px;
        }

        table.matrix-table th.th-bulan-group {
            background-color: #bbf7d0 !important;
            color: #14532d !important;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        table.matrix-table thead tr:nth-child(2) th {
            background-color: #dbeafe;
            color: #1e3a8a;
            font-weight: 800;
            font-size: 9px;
        }

        table.matrix-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table.matrix-table tbody tr:hover {
            background-color: #f1f5f9;
        }

        .text-left {
            text-align: left !important;
            padding-left: 8px !important;
        }

        .col-nama {
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
        }

        .col-nik {
            font-family: monospace;
            font-weight: 600;
            color: #334155;
            font-size: 9.5px;
        }

        .col-month {
            background-color: #fcfdfe;
            font-weight: 600;
            color: #334155;
            width: 38px;
        }

        .has-value {
            font-weight: 800;
            color: #0369a1;
            background-color: #e0f2fe !important;
        }

        .sisa-positive {
            font-weight: 800;
            color: #047857;
            background-color: #ecfdf5;
        }

        .sisa-zero {
            background-color: #dc2626 !important;
            color: #ffffff !important;
            font-weight: 900;
        }

        .sisa-negative {
            background-color: #fee2e2 !important;
            color: #b91c1c !important;
            font-weight: 800;
        }

        /* GRAND TOTAL FOOTER */
        table.matrix-table tfoot tr td {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 10.5px;
            border-top: 2px solid #0f172a;
        }

        table.matrix-table tfoot td.gt-label {
            text-align: right;
            padding-right: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* SIGNATURE AREA */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding: 0 30px;
            page-break-inside: avoid;
        }

        .sign-box {
            text-align: center;
            width: 200px;
        }

        .sign-role {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 50px;
        }

        .sign-name {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            border-top: 1px solid #64748b;
            padding-top: 4px;
        }

        .sign-title {
            font-size: 9px;
            color: #64748b;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }
            .no-print {
                display: none !important;
            }
            .report-card {
                border: none;
                box-shadow: none;
                padding: 0;
            }
            table.matrix-table {
                page-break-inside: auto;
            }
            table.matrix-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR (Hidden when printed) -->
    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="javascript:history.back()" class="btn btn-secondary">
                ← Kembali ke Monitoring
            </a>
            <span style="font-size: 12px; font-weight: 700; color: #64748b;">
                Tahun: <strong style="color: #0f172a;">{{ $year }}</strong> &bull; Departemen: <strong style="color: #0f172a;">{{ $departmentName }}</strong>
            </span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="{{ route('monitoring.annual-report.export', ['year' => $year, 'department_id' => request('department_id', 'all'), 'search' => request('search')]) }}" class="btn btn-secondary">
                📊 Download CSV/Excel
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- MAIN REPORT CARD -->
    <div class="report-card">
        <!-- HEADER -->
        <div class="report-header">
            <div class="logo-box">
                @if($appLogo)
                    <img src="{{ asset('storage/' . $appLogo) }}" alt="Logo" class="logo-img" onerror="this.style.display='none'">
                @else
                    <div class="logo-placeholder">SG</div>
                @endif
                <div style="text-align: left;">
                    <div style="font-size: 11px; font-weight: 800; color: #059669;">COLD FORGING SUGIYAMA</div>
                    <div style="font-size: 8.5px; color: #64748b; font-weight: 600;">Sistem Manajemen Cuti Terpadu</div>
                </div>
            </div>
            <div class="title-box">
                <h1 class="company-title">{{ $companyName }}</h1>
                <h2 class="report-subtitle">LAPORAN CUTI KARYAWAN TAHUN {{ $year }}</h2>
            </div>
            <div style="width: 140px; text-align: right; font-size: 9px; color: #64748b; font-weight: 600;">
                <div>Dokumen: <strong>FORM-HRD-024</strong></div>
                <div>Status: <strong>Official Report</strong></div>
            </div>
        </div>

        <!-- META BAR -->
        <div class="meta-info">
            <div>Departemen / Divisi: <strong style="color: #0f172a;">{{ $departmentName }}</strong></div>
            <div>Periode: <strong style="color: #0f172a;">01 Jan {{ $year }} - 31 Des {{ $year }}</strong></div>
            <div>Total Karyawan: <strong style="color: #059669;">{{ count($rows) }} Orang</strong></div>
            <div>Waktu Cetak: <span style="color: #0f172a;">{{ $generatedAt }}</span> (oleh: {{ $printedBy }})</div>
        </div>

        <!-- MATRIX DATA TABLE -->
        <table class="matrix-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 26px;">NO</th>
                    <th rowspan="2" style="min-width: 150px;">NAMA</th>
                    <th rowspan="2" style="width: 75px;">NIK</th>
                    <th rowspan="2" style="width: 75px;">Tanggal Masuk</th>
                    <th rowspan="2" style="width: 32px;">Jenis Kela</th>
                    <th rowspan="2" style="width: 36px;">Statu</th>
                    <th rowspan="2" style="width: 44px;">Hak C</th>
                    <th colspan="12" class="th-bulan-group">Bulan</th>
                    <th rowspan="2" style="width: 48px;">Sisa C</th>
                </tr>
                <tr>
                    <th style="width: 32px;">Ja</th>
                    <th style="width: 32px;">Fe</th>
                    <th style="width: 32px;">M</th>
                    <th style="width: 32px;">A</th>
                    <th style="width: 32px;">M</th>
                    <th style="width: 32px;">Ju</th>
                    <th style="width: 32px;">J</th>
                    <th style="width: 32px;">A</th>
                    <th style="width: 32px;">S</th>
                    <th style="width: 32px;">O</th>
                    <th style="width: 32px;">N</th>
                    <th style="width: 32px;">D</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r['no'] }}</td>
                        <td class="text-left col-nama">{{ $r['name'] }}</td>
                        <td class="col-nik">{{ $r['nik'] }}</td>
                        <td>{{ $r['join_date'] }}</td>
                        <td>{{ $r['gender'] }}</td>
                        <td>{{ $r['status'] }}</td>
                        <td style="font-weight: 700; color: #1e293b;">{{ number_format($r['hak_cuti'], 1) }}</td>
                        
                        <!-- 12 BULAN (Jan - Des) -->
                        @for($m = 1; $m <= 12; $m++)
                            @php
                                $val = $r['months'][$m] ?? 0;
                            @endphp
                            <td class="col-month {{ $val > 0 ? 'has-value' : '' }}">
                                {{ $val > 0 ? number_format($val, 1) : '-' }}
                            </td>
                        @endfor

                        <!-- SISA CUTI -->
                        @php
                            $sisa = $r['sisa_cuti'];
                        @endphp
                        <td class="{{ $sisa < 0 ? 'sisa-negative' : ($sisa == 0 ? 'sisa-zero' : 'sisa-positive') }}">
                            @if($sisa < 0)
                                ({{ number_format(abs($sisa), 2) }})
                            @else
                                {{ number_format($sisa, 2) }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="20" style="padding: 24px; text-align: center; color: #64748b; font-weight: 600;">
                            Tidak ada data karyawan / cuti pada filter tahun dan departemen ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="gt-label">Grand Total</td>
                    <td>{{ number_format($grandTotal['total_hak_cuti'], 1) }}</td>
                    @for($m = 1; $m <= 12; $m++)
                        @php
                            $mTotal = $grandTotal['months'][$m] ?? 0;
                        @endphp
                        <td>{{ $mTotal > 0 ? number_format($mTotal, 1) : '-' }}</td>
                    @endfor
                    <td style="background-color: #047857; color: #ffffff;">
                        {{ number_format($grandTotal['total_sisa_cuti'], 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</body>
</html>
