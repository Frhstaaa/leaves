import React, { useState, useTransition } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
  CalendarRange,
  Users,
  Clock,
  CheckCircle2,
  AlertCircle,
  Download,
  Printer,
  Search,
  Building2,
  Calendar as CalendarIcon,
  Filter,
  BarChart3,
  ArrowRight,
  TrendingDown,
  Sparkles,
  HelpCircle
} from 'lucide-react';
import { motion } from 'framer-motion';

const MONTH_NAMES = [
  { num: 1, short: 'Jan', full: 'Januari' },
  { num: 2, short: 'Feb', full: 'Februari' },
  { num: 3, short: 'Mar', full: 'Maret' },
  { num: 4, short: 'Apr', full: 'April' },
  { num: 5, short: 'Mei', full: 'Mei' },
  { num: 6, short: 'Jun', full: 'Juni' },
  { num: 7, short: 'Jul', full: 'Juli' },
  { num: 8, short: 'Agu', full: 'Agustus' },
  { num: 9, short: 'Sep', full: 'September' },
  { num: 10, short: 'Okt', full: 'Oktober' },
  { num: 11, short: 'Nov', full: 'November' },
  { num: 12, short: 'Des', full: 'Desember' },
];

export default function AnnualReport({
  reportData = [],
  grandTotal = {},
  departments = [],
  currentYear = new Date().getFullYear(),
  selectedDepartment = 'all',
  searchQuery = '',
  availableYears = [],
  companySettings = {},
}) {
  const [year, setYear] = useState(currentYear);
  const [dept, setDept] = useState(selectedDepartment);
  const [search, setSearch] = useState(searchQuery);
  const [isPending, startTransition] = useTransition();

  const applyFilters = (newYear, newDept, newSearch) => {
    router.get(
      route('monitoring.annual-report'),
      {
        year: newYear !== undefined ? newYear : year,
        department_id: newDept !== undefined ? newDept : dept,
        search: newSearch !== undefined ? newSearch : search,
      },
      {
        preserveState: true,
        preserveScroll: true,
      }
    );
  };

  const handleYearChange = (e) => {
    const val = parseInt(e.target.value, 10);
    setYear(val);
    applyFilters(val, dept, search);
  };

  const handleDeptChange = (e) => {
    const val = e.target.value;
    setDept(val);
    applyFilters(year, val, search);
  };

  const handleSearchChange = (e) => {
    const val = e.target.value;
    setSearch(val);
  };

  const handleSearchKeyDown = (e) => {
    if (e.key === 'Enter') {
      applyFilters(year, dept, search);
    }
  };

  const openPdfReport = () => {
    const params = new URLSearchParams({
      year: String(year),
      department_id: String(dept || 'all'),
      search: String(search || ''),
    });
    const url = `${route('monitoring.annual-report.pdf')}?${params.toString()}`;
    window.open(url, '_blank');
  };

  const downloadCsv = () => {
    const params = new URLSearchParams({
      year: String(year),
      department_id: String(dept || 'all'),
      search: String(search || ''),
    });
    window.location.href = `${route('monitoring.annual-report.export')}?${params.toString()}`;
  };

  const companyName = companySettings.company_name || 'PT. SUGIYAMA INDONESIA';

  return (
    <AuthenticatedLayout title={`Laporan Cuti Karyawan Tahun ${year}`}>
      <Head title={`Laporan Cuti Tahunan ${year} - ${companyName}`} />

      <div className="space-y-6 pb-12">
        {/* TOP BANNER & ACTION BAR */}
        <div className="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 p-6 sm:p-8 rounded-3xl text-white shadow-xl relative overflow-hidden">
          <div className="absolute right-0 top-0 w-96 h-96 bg-white/10 rounded-full blur-3xl -mr-20 -mt-20 pointer-events-none" />

          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div className="space-y-2">
              <div className="flex items-center space-x-2">
                <span className="px-3 py-1 rounded-full bg-white/20 text-white font-black text-[11px] uppercase tracking-wider border border-white/30 backdrop-blur-sm">
                  Laporan Resmi 1 Tahun
                </span>
                <span className="text-xs text-emerald-100 font-bold">{companyName}</span>
              </div>
              <h1 className="text-2xl sm:text-3xl font-black tracking-tight text-white flex items-center gap-3">
                <CalendarRange className="text-emerald-200 shrink-0" size={32} />
                LAPORAN CUTI KARYAWAN TAHUN {year}
              </h1>
              <p className="text-emerald-50 text-xs sm:text-sm max-w-2xl font-medium leading-relaxed">
                Rekapitulasi hak cuti tahunan, realisasi pengambilan per bulan (Januari - Desember), serta perhitungan sisa saldo cuti seluruh karyawan secara otomatis.
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-3 shrink-0">
              <Link
                href={route('monitoring.index')}
                className="px-4 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-bold transition-all flex items-center gap-2 border border-white/20 backdrop-blur-sm"
              >
                <BarChart3 size={15} />
                <span>Dashboard Analytics</span>
              </Link>
              <button
                onClick={downloadCsv}
                className="px-4 py-2.5 rounded-xl bg-white hover:bg-emerald-50 text-emerald-800 text-xs font-black transition-all flex items-center gap-2 border border-white/60 shadow-sm"
              >
                <Download size={15} />
                <span>Export CSV/Excel</span>
              </button>
              <button
                onClick={openPdfReport}
                className="px-5 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black transition-all flex items-center gap-2 shadow-lg shadow-amber-500/25 active:scale-95"
              >
                <Printer size={16} />
                <span>Cetak / Export PDF</span>
              </button>
            </div>
          </div>
        </div>

        {/* 4 EXECUTIVE KPI SUMMARY CARDS */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-emerald-200 transition-all">
            <div className="flex items-center space-x-3 mb-2">
              <div className="p-2 rounded-xl bg-blue-50 text-blue-600">
                <Users size={18} />
              </div>
              <span className="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Total Karyawan</span>
            </div>
            <div className="flex items-baseline gap-2">
              <h3 className="text-2xl sm:text-3xl font-black text-slate-900">{grandTotal.total_employees || 0}</h3>
              <span className="text-xs font-bold text-slate-400">Orang</span>
            </div>
            <p className="text-[10px] text-slate-400 mt-1">Dalam filter departemen terpilih</p>
          </div>

          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-emerald-200 transition-all">
            <div className="flex items-center space-x-3 mb-2">
              <div className="p-2 rounded-xl bg-purple-50 text-purple-600">
                <CalendarRange size={18} />
              </div>
              <span className="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Total Hak Cuti</span>
            </div>
            <div className="flex items-baseline gap-2">
              <h3 className="text-2xl sm:text-3xl font-black text-slate-900">
                {(grandTotal.total_hak_cuti || 0).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}
              </h3>
              <span className="text-xs font-bold text-slate-400">Hari</span>
            </div>
            <p className="text-[10px] text-slate-400 mt-1">Alokasi kuota tahun {year}</p>
          </div>

          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-emerald-200 transition-all">
            <div className="flex items-center space-x-3 mb-2">
              <div className="p-2 rounded-xl bg-amber-50 text-amber-600">
                <Clock size={18} />
              </div>
              <span className="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Cuti Terpakai</span>
            </div>
            <div className="flex items-baseline gap-2">
              <h3 className="text-2xl sm:text-3xl font-black text-amber-600">
                {(grandTotal.total_diambil || 0).toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}
              </h3>
              <span className="text-xs font-bold text-slate-400">Hari</span>
            </div>
            <p className="text-[10px] text-slate-400 mt-1">Disetujui Jan - Des {year}</p>
          </div>

          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs hover:border-emerald-200 transition-all">
            <div className="flex items-center space-x-3 mb-2">
              <div className="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                <CheckCircle2 size={18} />
              </div>
              <span className="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Sisa Saldo Cuti</span>
            </div>
            <div className="flex items-baseline gap-2">
              <h3 className="text-2xl sm:text-3xl font-black text-emerald-600">
                {(grandTotal.total_sisa_cuti || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
              </h3>
              <span className="text-xs font-bold text-slate-400">Hari</span>
            </div>
            <p className="text-[10px] text-slate-400 mt-1">Saldo akhir tersisa</p>
          </div>
        </div>

        {/* FILTER CONTROL BAR */}
        <div className="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
            {/* Year Selector */}
            <div className="relative min-w-[130px]">
              <CalendarIcon className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
              <select
                value={year}
                onChange={handleYearChange}
                className="w-full pl-9 pr-8 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 shadow-xs focus:ring-2 focus:ring-emerald-500 outline-none"
              >
                {availableYears.map((yr) => (
                  <option key={yr} value={yr}>
                    Tahun {yr}
                  </option>
                ))}
              </select>
            </div>

            {/* Department Selector */}
            <div className="relative min-w-[180px]">
              <Building2 className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
              <select
                value={dept}
                onChange={handleDeptChange}
                className="w-full pl-9 pr-8 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 shadow-xs focus:ring-2 focus:ring-emerald-500 outline-none"
              >
                <option value="all">Semua Departemen</option>
                {departments.map((d) => (
                  <option key={d.id} value={d.id}>
                    {d.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Search Input */}
            <div className="relative flex-1 min-w-[200px]">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
              <input
                type="text"
                placeholder="Cari Nama / NIK karyawan..."
                value={search}
                onChange={handleSearchChange}
                onKeyDown={handleSearchKeyDown}
                className="w-full pl-9 pr-20 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-emerald-500 outline-none"
              />
              <button
                onClick={() => applyFilters(year, dept, search)}
                className="absolute right-1.5 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold transition-colors"
              >
                Cari
              </button>
            </div>
          </div>

          <div className="flex items-center space-x-3 text-xs text-slate-500 font-medium">
            <span className="flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Cuti Terpenuhi
            </span>
            <span className="flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Kuota Habis / Minus
            </span>
          </div>
        </div>

        {/* MOBILE SCROLL HINT */}
        <div className="md:hidden flex items-center justify-between px-3.5 py-2.5 bg-emerald-50 text-emerald-800 text-[11px] font-bold rounded-xl border border-emerald-200 shadow-xs">
          <span className="flex items-center gap-1.5">
            👉 <span>Geser tabel ke samping untuk melihat rincian bulan <strong>Januari – Desember</strong></span>
          </span>
        </div>

        {/* MATRIX TABLE CONTAINER */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="overflow-x-auto w-full" style={{ WebkitOverflowScrolling: 'touch' }}>
            <table className="w-full text-xs text-center border-collapse min-w-[900px] md:min-w-full">
              <thead>
                {/* FIRST HEADER ROW */}
                <tr className="bg-slate-100 text-slate-800 uppercase font-extrabold text-[11px] border-b border-slate-300">
                  <th rowSpan={2} className="py-3 px-2 border-r border-slate-300 w-12 md:sticky md:left-0 bg-slate-100 z-10">
                    NO
                  </th>
                  <th rowSpan={2} className="py-3 px-4 border-r border-slate-300 text-left min-w-[180px] md:sticky md:left-12 bg-slate-100 z-10">
                    NAMA KARYAWAN
                  </th>
                  <th rowSpan={2} className="py-3 px-3 border-r border-slate-300 min-w-[95px]">
                    NIK
                  </th>
                  <th rowSpan={2} className="py-3 px-3 border-r border-slate-300 min-w-[95px]">
                    Tanggal Masuk
                  </th>
                  <th rowSpan={2} className="py-3 px-2 border-r border-slate-300 w-12" title="Jenis Kelamin">
                    JK
                  </th>
                  <th rowSpan={2} className="py-3 px-2 border-r border-slate-300 w-14" title="Status PTKP / Pernikahan">
                    Status
                  </th>
                  <th rowSpan={2} className="py-3 px-3 border-r border-slate-300 bg-slate-200/80 text-slate-900 font-black min-w-[70px]">
                    Hak Cuti
                  </th>
                  <th colSpan={12} className="py-2 px-4 border-r border-slate-300 bg-emerald-100 text-emerald-950 font-black tracking-wider text-xs">
                    BULAN (TAHUN {year})
                  </th>
                  <th rowSpan={2} className="py-3 px-3 bg-slate-200/80 text-slate-900 font-black min-w-[80px]">
                    Sisa Cuti
                  </th>
                </tr>

                {/* SECOND HEADER ROW (12 MONTHS) */}
                <tr className="bg-sky-50 text-sky-900 font-extrabold text-[10px] border-b-2 border-slate-300">
                  {MONTH_NAMES.map((m) => (
                    <th key={m.num} className="py-1.5 px-2 border-r border-sky-200 min-w-[42px]" title={m.full}>
                      {m.short}
                    </th>
                  ))}
                </tr>
              </thead>

              <tbody className="divide-y divide-slate-200 text-[11px]">
                {reportData.length > 0 ? (
                  reportData.map((row, idx) => {
                    const isEven = idx % 2 === 0;
                    const sisa = row.sisa_cuti;
                    const isZero = sisa === 0;
                    const isNegative = sisa < 0;

                    return (
                      <tr
                        key={row.id || idx}
                        className={`hover:bg-emerald-50/50 transition-colors ${
                          isEven ? 'bg-white' : 'bg-slate-50/60'
                        }`}
                      >
                        {/* NO */}
                        <td className={`py-2 px-2 border-r border-slate-200 font-bold text-slate-500 md:sticky md:left-0 z-10 ${
                          isEven ? 'bg-white' : 'bg-slate-50'
                        }`}>
                          {row.no}
                        </td>

                        {/* NAMA */}
                        <td className={`py-2 px-4 border-r border-slate-200 text-left font-bold text-slate-900 whitespace-nowrap md:sticky md:left-12 z-10 ${
                          isEven ? 'bg-white' : 'bg-slate-50'
                        }`}>
                          <div className="flex items-center space-x-2">
                            <span>{row.name}</span>
                            {row.department_name && (
                              <span className="text-[9px] font-semibold text-slate-400 px-1.5 py-0.5 rounded bg-slate-100">
                                {row.department_name}
                              </span>
                            )}
                          </div>
                        </td>

                        {/* NIK */}
                        <td className="py-2 px-3 border-r border-slate-200 font-mono text-slate-600 font-semibold">
                          {row.nik}
                        </td>

                        {/* TANGGAL MASUK */}
                        <td className="py-2 px-3 border-r border-slate-200 text-slate-600 whitespace-nowrap">
                          {row.join_date}
                        </td>

                        {/* JK */}
                        <td className="py-2 px-2 border-r border-slate-200 font-bold text-slate-700">
                          {row.gender}
                        </td>

                        {/* STATUS */}
                        <td className="py-2 px-2 border-r border-slate-200 text-slate-600 font-medium">
                          {row.status}
                        </td>

                        {/* HAK CUTI */}
                        <td className="py-2 px-3 border-r border-slate-200 font-bold text-slate-900 bg-slate-100/50">
                          {row.hak_cuti.toFixed(1)}
                        </td>

                        {/* 12 MONTH COLUMNS */}
                        {MONTH_NAMES.map((m) => {
                          const val = row.months[m.num] || 0;
                          const hasVal = val > 0;
                          return (
                            <td
                              key={m.num}
                              className={`py-2 px-1 border-r border-slate-200 text-center ${
                                hasVal
                                  ? 'bg-sky-100 font-extrabold text-sky-900'
                                  : 'text-slate-300 font-light'
                              }`}
                            >
                              {hasVal ? val.toFixed(1) : '-'}
                            </td>
                          );
                        })}

                        {/* SISA CUTI */}
                        <td className={`py-2 px-3 font-extrabold text-center border-l border-slate-200 ${
                          isNegative
                            ? 'bg-rose-100 text-rose-700 font-black'
                            : isZero
                            ? 'bg-rose-600 text-white font-black'
                            : 'bg-emerald-50 text-emerald-800'
                        }`}>
                          {isNegative ? `(${Math.abs(sisa).toFixed(2)})` : sisa.toFixed(2)}
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr>
                    <td colSpan={20} className="py-12 text-center text-slate-400 font-medium">
                      <AlertCircle className="mx-auto text-slate-300 mb-2" size={32} />
                      Tidak ada data karyawan yang cocok dengan filter.
                    </td>
                  </tr>
                )}
              </tbody>

              {/* GRAND TOTAL FOOTER ROW */}
              <tfoot>
                <tr className="bg-slate-900 text-white font-black text-[12px] border-t-2 border-slate-900">
                  <td colSpan={6} className="py-3 px-4 text-right pr-6 tracking-wider uppercase md:sticky md:left-0 z-10 bg-slate-900">
                    Grand Total
                  </td>
                  <td className="py-3 px-3 text-emerald-300 border-r border-slate-700">
                    {(grandTotal.total_hak_cuti || 0).toFixed(1)}
                  </td>
                  {MONTH_NAMES.map((m) => {
                    const mTotal = grandTotal.months?.[m.num] || 0;
                    return (
                      <td key={m.num} className="py-3 px-1 border-r border-slate-700 text-sky-300">
                        {mTotal > 0 ? mTotal.toFixed(1) : '-'}
                      </td>
                    );
                  })}
                  <td className="py-3 px-3 bg-emerald-600 text-white font-black text-center">
                    {(grandTotal.total_sisa_cuti || 0).toFixed(2)}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div className="p-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <span>
              Menampilkan <strong>{reportData.length}</strong> karyawan terdaftar pada periode Tahun <strong>{year}</strong>.
            </span>
            <div className="flex items-center space-x-2">
              <button
                onClick={openPdfReport}
                className="px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition-colors flex items-center gap-1.5"
              >
                <Printer size={13} />
                <span>Buka Versi Cetak / PDF</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
