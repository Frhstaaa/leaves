<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Riwayat Tidak Bekerja - {{ $user->name }} ({{ $year }})</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Inter', 'Plus Jakarta Sans', Arial, sans-serif;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.4;
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
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .report-card {
            background: #ffffff;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            max-width: 900px;
            margin: 0 auto;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header-logo {
            width: 60px;
            height: 60px;
            object-contain: fit;
        }

        .header-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin: 0;
            text-transform: uppercase;
        }

        .header-subtitle {
            font-size: 11px;
            color: #0d9488;
            font-weight: 700;
            margin: 2px 0 0 0;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .header-desc {
            font-size: 9.5px;
            color: #64748b;
            margin: 3px 0 0 0;
        }

        .employee-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 24px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 18px;
        }

        .info-item {
            display: flex;
            align-items: baseline;
        }

        .info-label {
            width: 120px;
            font-size: 10.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .info-value {
            font-size: 11.5px;
            font-weight: 700;
            color: #0f172a;
        }

        /* Stat Metric Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
        }

        .stat-card.emerald {
            background: #f0fdf4;
            border-color: #bbf7d0;
        }

        .stat-card.amber {
            background: #fffbeb;
            border-color: #fde68a;
        }

        .stat-card.blue {
            background: #eff6ff;
            border-color: #bfdbfe;
        }

        .stat-label {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        .stat-sub {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
            font-weight: 500;
        }

        /* Data Tables */
        .section-title {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin: 16px 0 8px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 18px;
        }

        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: left;
        }

        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            font-size: 9px;
        }

        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .badge-success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .badge-warning {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-danger {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-quota {
            background: #ccfbf1;
            color: #0f766e;
            border: 1px solid #99f6e4;
        }

        .badge-no-quota {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        /* Monthly Matrix Mini Table */
        table.matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
            text-align: center;
            margin-bottom: 18px;
        }

        table.matrix-table th, table.matrix-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 3px;
        }

        table.matrix-table th {
            background-color: #0f766e;
            color: white;
            font-weight: 700;
            font-size: 8.5px;
        }

        /* Signature block */
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 24px;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 10px;
            background: #f8fafc;
        }

        .signature-role {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 45px;
        }

        .signature-name {
            font-size: 11px;
            font-weight: 800;
            color: #0f172a;
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            display: inline-block;
            min-width: 140px;
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .report-card {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- ACTION TOOLBAR (NO PRINT) -->
    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                ✕ Tutup
            </button>
        </div>
        <div style="font-size: 11px; color: #64748b; font-weight: 600;">
            Laporan Riwayat Tidak Bekerja Periode: <strong>{{ $year }}</strong>
        </div>
    </div>

    <!-- MAIN REPORT CARD -->
    <div class="report-card">
        <!-- HEADER KOP SURAT -->
        <table class="header-table">
            <tr>
                <td style="width: 70px; vertical-align: middle;">
                    @if(!empty($companyLogo))
                        <img src="{{ $companyLogo }}" alt="Logo" class="header-logo">
                    @else
                        <div style="width: 50px; height: 50px; border-radius: 10px; background: #0f766e; color: white; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 18px;">SG</div>
                    @endif
                </td>
                <td style="vertical-align: middle; padding-left: 12px;">
                    <h1 class="header-title">{{ $companyName }}</h1>
                    <p class="header-subtitle">LAPORAN RIWAYAT TIDAK BEKERJA & CUTI KARYAWAN</p>
                    <p class="header-desc">Dokumen Rekapitulasi Ketidakhadiran, Izin, Sakit, dan Pemakaian Hak Cuti Tahunan Periode {{ $year }}</p>
                </td>
                <td style="vertical-align: top; text-align: right; width: 160px; font-size: 9.5px; color: #64748b;">
                    <div>Tanggal Cetak:</div>
                    <div style="font-weight: 700; color: #0f172a;">{{ date('d F Y, H:i') }} WIB</div>
                </td>
            </tr>
        </table>

        <!-- EMPLOYEE INFO -->
        <div class="employee-info-grid">
            <div class="info-item">
                <span class="info-label">Nama Karyawan</span>
                <span class="info-value">: {{ $user->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">NIK</span>
                <span class="info-value">: {{ $user->nik ?? 'EMP-' . str_pad($user->id, 3, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Departemen</span>
                <span class="info-value">: {{ $user->department?->name ?? 'Departemen General' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Jabatan / Role</span>
                <span class="info-value">: {{ strtoupper($user->role) }}</span>
            </div>
        </div>

        <!-- STAT METRIC CARDS -->
        <div class="stats-grid">
            <div class="stat-card emerald">
                <div class="stat-label">Sisa Kuota Cuti</div>
                <div class="stat-value" style="color: #059669;">{{ $quota->remaining_quota ?? 0 }} <span style="font-size: 11px;">Hari</span></div>
                <div class="stat-sub">Dari total {{ $quota->total_quota ?? 12 }} hari ({{ $year }})</div>
            </div>
            <div class="stat-card amber">
                <div class="stat-label">Cuti Potong Kuota</div>
                <div class="stat-value" style="color: #d97706;">{{ $stats['total_quota_deducted_days'] ?? 0 }} <span style="font-size: 11px;">Hari</span></div>
                <div class="stat-sub">Cuti Tahunan + Cuti Haid</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-label">Sakit & Izin</div>
                <div class="stat-value" style="color: #2563eb;">{{ ($stats['total_sick_days'] ?? 0) + ($stats['total_permission_days'] ?? 0) }} <span style="font-size: 11px;">Hari</span></div>
                <div class="stat-sub">Tidak memotong kuota cuti</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Tidak Bekerja</div>
                <div class="stat-value">{{ $stats['total_not_working_days'] ?? 0 }} <span style="font-size: 11px;">Hari</span></div>
                <div class="stat-sub">Akumulasi seluruh kategori</div>
            </div>
        </div>

        <!-- MONTHLY MATRIX TABLE -->
        <div class="section-title">
            <span>Rekapitulasi Ketidakhadiran Bulanan (Januari - Desember {{ $year }})</span>
            <span style="font-size: 9.5px; color: #64748b; font-weight: normal;">Satuan: Hari Kerja</span>
        </div>
        <table class="matrix-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th>Jan</th><th>Feb</th><th>Mar</th><th>Apr</th><th>Mei</th><th>Jun</th>
                    <th>Jul</th><th>Agu</th><th>Sep</th><th>Okt</th><th>Nov</th><th>Des</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: left; font-weight: 700; background: #f0fdf4;">Cuti Potong Kuota (Tahunan & Haid)</td>
                    @php $sumDeduct = 0; @endphp
                    @for($m=1; $m<=12; $m++)
                        @php 
                            $val = $stats['monthly_deducted'][$m] ?? 0;
                            $sumDeduct += $val;
                        @endphp
                        <td>{{ $val > 0 ? $val : '-' }}</td>
                    @endfor
                    <td style="font-weight: 800; background: #dcfce7; color: #15803d;">{{ $sumDeduct > 0 ? $sumDeduct : '0' }}</td>
                </tr>
                <tr>
                    <td style="text-align: left; font-weight: 600;">Sakit & Izin (Tanpa Potong Kuota)</td>
                    @php $sumOther = 0; @endphp
                    @for($m=1; $m<=12; $m++)
                        @php 
                            $val = $stats['monthly_other'][$m] ?? 0;
                            $sumOther += $val;
                        @endphp
                        <td>{{ $val > 0 ? $val : '-' }}</td>
                    @endfor
                    <td style="font-weight: 700; background: #f1f5f9;">{{ $sumOther > 0 ? $sumOther : '0' }}</td>
                </tr>
                <tr style="font-weight: 800; background: #f8fafc;">
                    <td style="text-align: left; background: #e2e8f0;">Total Ketidakhadiran</td>
                    @php $sumAll = 0; @endphp
                    @for($m=1; $m<=12; $m++)
                        @php 
                            $val = ($stats['monthly_deducted'][$m] ?? 0) + ($stats['monthly_other'][$m] ?? 0);
                            $sumAll += $val;
                        @endphp
                        <td style="font-weight: 700;">{{ $val > 0 ? $val : '-' }}</td>
                    @endfor
                    <td style="font-weight: 900; background: #cbd5e1; color: #0f172a;">{{ $sumAll > 0 ? $sumAll : '0' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- ITEM DETAIL TABLE -->
        <div class="section-title">
            <span>Daftar Riwayat Pengajuan Tidak Bekerja (Tahun {{ $year }})</span>
            <span style="font-size: 9.5px; color: #64748b; font-weight: normal;">Total {{ count($requests) }} Permohonan</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25px; text-align: center;">No</th>
                    <th style="width: 95px;">No. Pengajuan</th>
                    <th>Kategori Tidak Bekerja</th>
                    <th style="width: 100px; text-align: center;">Dampak Kuota</th>
                    <th style="width: 130px;">Tanggal Pelaksanaan</th>
                    <th style="width: 60px; text-align: center;">Durasi</th>
                    <th>Alasan / Keperluan</th>
                    <th style="width: 75px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $idx => $req)
                <tr>
                    <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace; font-weight: 700; color: #0f172a;">{{ $req->request_number }}</td>
                    <td style="font-weight: 600;">
                        {{ $req->category?->name ?? 'Cuti' }}
                    </td>
                    <td style="text-align: center;">
                        @if($req->category?->isQuotaDeductible())
                            <span class="badge badge-quota">Potong Kuota</span>
                        @else
                            <span class="badge badge-no-quota">Tanpa Potong</span>
                        @endif
                    </td>
                    <td>
                        {{ date('d/m/Y', strtotime($req->start_date)) }} 
                        @if($req->start_date !== $req->end_date)
                            s/d {{ date('d/m/Y', strtotime($req->end_date)) }}
                        @endif
                    </td>
                    <td style="text-align: center; font-weight: 700;">
                        {{ $req->amount }} {{ $req->unit }}
                    </td>
                    <td style="color: #475569;">
                        {{ $req->reason ?? '-' }}
                    </td>
                    <td style="text-align: center;">
                        @if($req->status === 'approved')
                            <span class="badge badge-success">Disetujui</span>
                        @elseif($req->status === 'rejected')
                            <span class="badge badge-danger">Ditolak</span>
                        @else
                            <span class="badge badge-warning">Menunggu</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 20px;">
                        Belum ada riwayat pengajuan tidak bekerja pada tahun {{ $year }}.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- SIGNATURES -->
        <div class="signature-grid">
            <div class="signature-box">
                <div class="signature-role">Karyawan Pemohon</div>
                <div class="signature-name">{{ $user->name }}</div>
            </div>
            <div class="signature-box">
                <div class="signature-role">Atasan Langsung / Manager</div>
                <div class="signature-name">{{ $user->manager?->name ?? $user->department?->manager?->name ?? 'Manager Departemen' }}</div>
            </div>
            <div class="signature-box">
                <div class="signature-role">HRD & PGA Department</div>
                <div class="signature-name">PGA Admin / HRD SGIN</div>
            </div>
        </div>

    </div>

</body>
</html>
