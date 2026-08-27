import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { motion, AnimatePresence } from 'framer-motion';
import {
  FileText,
  Plus,
  Search,
  Filter,
  CheckCircle,
  XCircle,
  Clock,
  Paperclip,
  Trash2,
  Calendar,
  History,
  X,
  ChevronRight,
  Eye,
  MoreVertical,
  Copy,
  Printer,
  BarChart3,
  ListFilter,
  CalendarRange,
  ShieldCheck,
  HeartPulse,
  Sparkles,
  Info
} from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import InstantPagination from "@/components/ui/instant-pagination";
import { showConfirm, showToast } from '@/Utils/swal';

const MONTH_NAMES = [
  'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
  'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
];

export default function LeaveRequestsIndex({
  user: propUser,
  requests,
  filters,
  quota,
  categories = [],
  currentYear = new Date().getFullYear(),
  availableYears = [],
  statistics = {}
}) {
  const { auth } = usePage().props;
  const currentUser = propUser || auth?.user || {};

  const [activeTab, setActiveTab] = useState('list'); // 'list' or 'report'
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [search, setSearch] = useState(filters?.search || '');
  const [status, setStatus] = useState(filters?.status || '');
  const [categoryFilter, setCategoryFilter] = useState(filters?.category_id || '');
  const [selectedYear, setSelectedYear] = useState(String(currentYear));

  // Instant Client-Side Pagination States
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);

  const rawRequests = React.useMemo(() => {
    return Array.isArray(requests) ? requests : (requests?.data || []);
  }, [requests]);

  const paginatedRequests = React.useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return rawRequests.slice(start, start + pageSize);
  }, [rawRequests, currentPage, pageSize]);

  React.useEffect(() => {
    setCurrentPage(1);
  }, [rawRequests.length]);

  const handleFilter = (e) => {
    if (e) e.preventDefault();
    router.get(
      route('leave-requests.index'),
      {
        search,
        status,
        category_id: categoryFilter,
        year: selectedYear,
      },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleYearChange = (val) => {
    setSelectedYear(val);
    router.get(
      route('leave-requests.index'),
      {
        search,
        status,
        category_id: categoryFilter,
        year: val,
      },
      { preserveState: true, preserveScroll: true }
    );
  };

  const handleCancel = async (id) => {
    const confirmed = await showConfirm({
      title: 'Batalkan Pengajuan Cuti?',
      text: 'Pengajuan cuti yang masih berstatus pending akan dibatalkan dan dihapus.',
      icon: 'warning',
      confirmText: 'Ya, Batalkan',
      cancelText: 'Kembali',
    });

    if (confirmed) {
      router.delete(route('leave-requests.destroy', id), {
        onSuccess: () => showToast('Pengajuan cuti berhasil dibatalkan.'),
      });
    }
  };

  const openPrintReport = () => {
    const url = route('leave-requests.report.print', { year: selectedYear });
    window.open(url, '_blank');
  };

  const totalQuota = quota?.total_quota || 12;
  const usedQuota = quota?.used_quota || 0;
  const remainingQuota = quota?.remaining_quota || Math.max(0, totalQuota - usedQuota);

  return (
    <AuthenticatedLayout title="Riwayat & Laporan Tidak Bekerja">
      <div className="space-y-6">

        {/* Page Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 sm:p-5 md:p-6 bg-white border border-slate-200/90 rounded-2xl md:rounded-3xl shadow-xs">
          <div className="space-y-1 min-w-0">
            <div className="flex items-center space-x-2.5">
              <h2 className="text-lg sm:text-xl md:text-2xl font-bold text-slate-900 tracking-tight truncate">
                Riwayat & Laporan Tidak Bekerja
              </h2>
              <Badge variant="success" className="font-semibold text-[10px] sm:text-xs">
                Tahun {selectedYear}
              </Badge>
            </div>
            <p className="text-xs text-slate-500 font-normal">
              Kelola riwayat permohonan, pantau sisa kuota cuti tahunan, dan akses laporan ketidakhadiran resmi Anda.
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-2 shrink-0">
            <Button
              type="button"
              variant="outline"
              onClick={openPrintReport}
              className="rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-xs space-x-1.5 shadow-xs"
            >
              <Printer size={15} className="text-emerald-600" />
              <span>Cetak Laporan (PDF)</span>
            </Button>

            <Link href={route('leave-requests.create')}>
              <Button
                variant="default"
                className="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs space-x-1.5 shadow-sm shadow-emerald-600/20"
              >
                <Plus size={16} />
                <span>Buat Pengajuan Baru</span>
              </Button>
            </Link>
          </div>
        </div>

        {/* Top Summary Quota & Absence Overview Cards */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
          {/* Sisa Kuota */}
          <Card className="border-emerald-200/90 bg-emerald-50/30 hover:shadow-md transition-all">
            <CardContent className="p-4 sm:p-5">
              <span className="text-[10px] sm:text-xs font-semibold text-emerald-800 uppercase tracking-wider block">
                Sisa Kuota Cuti
              </span>
              <div className="flex items-baseline space-x-1 mt-1">
                <span className="text-xl sm:text-2xl md:text-3xl font-bold text-emerald-700 tracking-tight">
                  {remainingQuota}
                </span>
                <span className="text-xs font-medium text-slate-500">/ {totalQuota} Hari ({selectedYear})</span>
              </div>
              <p className="text-[11px] text-emerald-900/80 font-normal mt-0.5">
                Cuti tahunan yang masih dapat digunakan.
              </p>
            </CardContent>
          </Card>

          {/* Cuti Potong Kuota */}
          <Card className="border-amber-200/90 bg-amber-50/30 hover:shadow-md transition-all">
            <CardContent className="p-4 sm:p-5">
              <span className="text-[10px] sm:text-xs font-semibold text-amber-800 uppercase tracking-wider block">
                Cuti Potong Kuota
              </span>
              <div className="flex items-baseline space-x-1 mt-1">
                <span className="text-xl sm:text-2xl md:text-3xl font-bold text-amber-800 tracking-tight">
                  {statistics.total_quota_deducted_days ?? usedQuota}
                </span>
                <span className="text-xs font-medium text-slate-500">Hari Terpakai</span>
              </div>
              <p className="text-[11px] text-amber-900/80 font-normal mt-0.5">
                Cuti Tahunan + Cuti Haid disetujui.
              </p>
            </CardContent>
          </Card>

          {/* Sakit & Izin Bebas Kuota */}
          <Card className="border-blue-200/90 bg-blue-50/30 hover:shadow-md transition-all">
            <CardContent className="p-4 sm:p-5">
              <span className="text-[10px] sm:text-xs font-semibold text-blue-800 uppercase tracking-wider block">
                Sakit & Izin Khusus
              </span>
              <div className="flex items-baseline space-x-1 mt-1">
                <span className="text-xl sm:text-2xl md:text-3xl font-bold text-blue-800 tracking-tight">
                  {((statistics.total_sick_days || 0) + (statistics.total_permission_days || 0))}
                </span>
                <span className="text-xs font-medium text-slate-500">Hari</span>
              </div>
              <p className="text-[11px] text-blue-900/80 font-normal mt-0.5">
                Tidak memotong saldo kuota cuti.
              </p>
            </CardContent>
          </Card>

          {/* Total Tidak Bekerja */}
          <Card className="border-slate-200/90 bg-white hover:shadow-md transition-all">
            <CardContent className="p-4 sm:p-5">
              <span className="text-[10px] sm:text-xs font-semibold text-slate-500 uppercase tracking-wider block">
                Total Tidak Bekerja
              </span>
              <div className="flex items-baseline space-x-1 mt-1">
                <span className="text-xl sm:text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">
                  {statistics.total_not_working_days ?? 0}
                </span>
                <span className="text-xs font-medium text-slate-500">Hari Kerja</span>
              </div>
              <p className="text-[11px] text-slate-500 font-normal mt-0.5">
                Akumulasi seluruh permohonan disetujui.
              </p>
            </CardContent>
          </Card>
        </div>

        {/* View Navigation Tabs */}
        <div className="flex items-center justify-between border-b border-slate-200">
          <div className="flex items-center space-x-2 sm:space-x-4">
            <button
              type="button"
              onClick={() => setActiveTab('list')}
              className={`pb-3 px-2 text-xs sm:text-sm font-semibold flex items-center space-x-2 border-b-2 transition-all cursor-pointer ${
                activeTab === 'list'
                  ? 'border-emerald-600 text-emerald-700'
                  : 'border-transparent text-slate-500 hover:text-slate-900'
              }`}
            >
              <ListFilter size={16} />
              <span>Daftar Permohonan ({requests?.total || requests?.data?.length || 0})</span>
            </button>

            <button
              type="button"
              onClick={() => setActiveTab('report')}
              className={`pb-3 px-2 text-xs sm:text-sm font-semibold flex items-center space-x-2 border-b-2 transition-all cursor-pointer ${
                activeTab === 'report'
                  ? 'border-emerald-600 text-emerald-700'
                  : 'border-transparent text-slate-500 hover:text-slate-900'
              }`}
            >
              <BarChart3 size={16} />
              <span>Laporan & Matrix Tahunan</span>
            </button>
          </div>

          <div className="flex items-center space-x-2 pb-2">
            <span className="text-xs font-semibold text-slate-500 hidden sm:inline-block">Tahun:</span>
            <Select value={selectedYear} onValueChange={handleYearChange}>
              <SelectTrigger className="w-[100px] h-8 text-xs font-semibold bg-white border-slate-200">
                <SelectValue placeholder="Tahun" />
              </SelectTrigger>
              <SelectContent>
                {(availableYears.length > 0 ? availableYears : [2026, 2025, 2024]).map((yr) => (
                  <SelectItem key={yr} value={String(yr)}>
                    {yr}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* ========================================================================= */}
        {/* TAB 1: DAFTAR PENGIRIMAN PERMOHONAN (LIST & FILTERS)                      */}
        {/* ========================================================================= */}
        {activeTab === 'list' && (
          <div className="space-y-4">
            {/* Filters Bar */}
            <div className="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-xs">
              <form onSubmit={handleFilter} className="grid grid-cols-1 sm:grid-cols-12 gap-3">
                <div className="sm:col-span-5 relative">
                  <Search size={16} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                  <input
                    type="text"
                    placeholder="Cari nomor request, alasan permohonan..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-normal focus:bg-white focus:border-emerald-500 outline-none"
                  />
                </div>

                <div className="sm:col-span-3">
                  <Select value={categoryFilter || 'all'} onValueChange={(val) => setCategoryFilter(val === 'all' ? '' : val)}>
                    <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-xs font-normal rounded-xl h-9">
                      <SelectValue placeholder="Semua Kategori" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Semua Kategori</SelectItem>
                      {categories.map((cat) => (
                        <SelectItem key={cat.id} value={String(cat.id)}>
                          {cat.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="sm:col-span-2">
                  <Select value={status || 'all'} onValueChange={(val) => setStatus(val === 'all' ? '' : val)}>
                    <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-xs font-normal rounded-xl h-9">
                      <SelectValue placeholder="Semua Status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Semua Status</SelectItem>
                      <SelectItem value="pending">⏳ Pending</SelectItem>
                      <SelectItem value="approved">✅ Disetujui</SelectItem>
                      <SelectItem value="rejected">❌ Ditolak</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="sm:col-span-2">
                  <Button
                    type="submit"
                    variant="default"
                    className="w-full h-9 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs space-x-1"
                  >
                    <Filter size={14} />
                    <span>Filter</span>
                  </Button>
                </div>
              </form>
            </div>

            {/* Requests List */}
            <div className="space-y-3">
              {rawRequests.length > 0 ? (
                <div className="space-y-3">
                  {paginatedRequests.map((req, index) => {
                    const isDeductible = req.category && (
                      req.category.deducts_quota ||
                      ['cuti tahunan', 'cuti haid'].includes(req.category.name?.toLowerCase())
                    );

                    return (
                      <motion.div
                        key={req.id}
                        initial={{ opacity: 0, y: 8 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.2, delay: Math.min(index * 0.03, 0.25) }}
                        className="p-4 sm:p-5 rounded-2xl md:rounded-3xl bg-white border border-slate-200/90 shadow-xs hover:shadow-md transition-all space-y-3"
                      >
                        <div className="flex items-start justify-between gap-2">
                          <div className="space-y-1 min-w-0 pr-2">
                            <div className="flex flex-wrap items-center gap-2">
                              <span className="font-mono text-xs font-bold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200/80">
                                {req.request_number}
                              </span>
                              <span className="text-xs sm:text-sm font-bold text-slate-900 truncate">
                                {req.category?.name}
                              </span>
                              {isDeductible ? (
                                <Badge variant="success" className="font-semibold text-[10px]">
                                  ✂️ Potong Kuota
                                </Badge>
                              ) : (
                                <Badge variant="outline" className="font-medium text-[10px] text-slate-500 bg-slate-50">
                                  🛡️ Bebas Kuota
                                </Badge>
                              )}
                            </div>
                            <p className="text-[11px] text-slate-500 font-normal">
                              Sifat: <strong className="text-slate-800">{req.submission_type}</strong> &bull; {req.start_date} s/d {req.end_date} (<strong className="text-slate-800">{req.amount} {req.unit}</strong>)
                            </p>
                          </div>

                          <div className="flex items-center space-x-1.5 shrink-0">
                            {req.status === 'pending' && (
                              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold border ${
                                req.current_stage === 'approval_1' ? 'bg-blue-50 text-blue-800 border-blue-200' :
                                req.current_stage === 'approval_2' ? 'bg-purple-50 text-purple-800 border-purple-200' :
                                'bg-amber-50 text-amber-800 border-amber-200'
                              }`}>
                                {req.current_stage === 'approval_1' ? 'Menunggu Atasan 1' :
                                 req.current_stage === 'approval_2' ? 'Menunggu Atasan 2' :
                                 'Menunggu HRD'}
                              </span>
                            )}
                            <Badge
                              variant={
                                req.status === 'approved' ? 'success' :
                                req.status === 'rejected' ? 'destructive' :
                                'warning'
                              }
                              className="font-semibold text-[10px]"
                            >
                              {req.status === 'approved' ? 'Disetujui' : req.status === 'rejected' ? 'Ditolak' : 'Pending'}
                            </Badge>
                          </div>
                        </div>

                        <p className="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 font-normal line-clamp-2">
                          {req.reason}
                        </p>

                        <div className="flex items-center justify-between pt-1 text-xs">
                          <div className="flex items-center space-x-2 text-[11px] text-slate-400 font-normal">
                            <Clock size={13} />
                            <span>Diajukan: {new Date(req.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                          </div>

                          <div className="flex items-center space-x-2">
                            <Button
                              variant="secondary"
                              size="sm"
                              onClick={() => setSelectedRequest(req)}
                              className="rounded-xl space-x-1 font-semibold text-xs h-8"
                            >
                              <Eye size={14} />
                              <span>Detail</span>
                            </Button>

                            <DropdownMenu>
                              <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="h-8 w-8 rounded-xl text-slate-500 hover:text-slate-900">
                                  <MoreVertical size={15} />
                                </Button>
                              </DropdownMenuTrigger>
                              <DropdownMenuContent align="end" className="w-48">
                                <DropdownMenuLabel>Opsi Permohonan</DropdownMenuLabel>
                                <DropdownMenuItem onClick={() => setSelectedRequest(req)}>
                                  <Eye className="mr-2 h-4 w-4 text-emerald-600" />
                                  <span>Buka Detail Lengkap</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => {
                                  navigator.clipboard.writeText(req.request_number);
                                  showToast(`Nomor ${req.request_number} berhasil disalin!`);
                                }}>
                                  <Copy className="mr-2 h-4 w-4 text-blue-600" />
                                  <span>Salin No. Permohonan</span>
                                </DropdownMenuItem>
                                {req.status === 'pending' && (
                                  <>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                      onClick={() => handleCancel(req.id)}
                                      className="text-rose-600 focus:bg-rose-50 focus:text-rose-700"
                                    >
                                      <Trash2 className="mr-2 h-4 w-4" />
                                      <span>Batalkan Permohonan</span>
                                    </DropdownMenuItem>
                                  </>
                                )}
                              </DropdownMenuContent>
                            </DropdownMenu>
                          </div>
                        </div>
                      </motion.div>
                    );
                  })}

                  {/* Instant Pagination Control */}
                  <div className="rounded-2xl overflow-hidden border border-slate-200/80 shadow-xs">
                    <InstantPagination
                      currentPage={currentPage}
                      totalItems={rawRequests.length}
                      pageSize={pageSize}
                      onPageChange={setCurrentPage}
                      onPageSizeChange={(newSize) => {
                        setPageSize(newSize);
                        setCurrentPage(1);
                      }}
                      itemName="pengajuan"
                    />
                  </div>
                </div>
              ) : (
                <div className="p-12 rounded-3xl bg-white border border-slate-200 text-center text-slate-400 space-y-2">
                  <History size={40} className="mx-auto opacity-40 text-slate-400" />
                  <p className="text-xs font-semibold">Tidak ada pengajuan cuti yang ditemukan pada filter ini.</p>
                </div>
              )}
            </div>
          </div>
        )}

        {/* ========================================================================= */}
        {/* TAB 2: LAPORAN & MATRIX KETIDAKHADIRAN TAHUNAN                           */}
        {/* ========================================================================= */}
        {activeTab === 'report' && (
          <div className="space-y-5">
            {/* Matrix Card */}
            <Card className="border-slate-200/90 bg-white shadow-xs">
              <CardHeader className="p-4 sm:p-6 pb-3">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div>
                    <CardTitle className="text-base sm:text-lg font-bold text-slate-900">
                      Matrix Ketidakhadiran Bulanan ({selectedYear})
                    </CardTitle>
                    <CardDescription className="text-xs font-normal">
                      Rekapitulasi hari tidak bekerja yang telah disetujui (Januari s/d Desember {selectedYear}).
                    </CardDescription>
                  </div>
                  <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    onClick={openPrintReport}
                    className="rounded-xl border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-xs space-x-1.5 self-start sm:self-auto"
                  >
                    <Printer size={14} className="text-emerald-600" />
                    <span>Cetak PDF Laporan</span>
                  </Button>
                </div>
              </CardHeader>

              <CardContent className="p-4 sm:p-6 pt-0 overflow-x-auto">
                <table className="w-full text-xs border-collapse min-w-[700px]">
                  <thead>
                    <tr className="bg-slate-100 text-slate-700 border-y border-slate-200 text-[11px] uppercase tracking-wider">
                      <th className="py-2.5 px-3 text-left font-bold">Kategori / Kelompok</th>
                      {MONTH_NAMES.map((m) => (
                        <th key={m} className="py-2.5 px-2 text-center font-bold">{m}</th>
                      ))}
                      <th className="py-2.5 px-3 text-center font-bold bg-slate-200/80">Total</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 font-medium text-slate-800">
                    {/* Cuti Potong Kuota */}
                    <tr className="hover:bg-emerald-50/40 transition-colors">
                      <td className="py-3 px-3 text-left">
                        <div className="flex items-center space-x-1.5">
                          <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                          <span className="font-bold text-slate-900">Cuti Potong Kuota (Tahunan & Haid)</span>
                        </div>
                      </td>
                      {MONTH_NAMES.map((_, idx) => {
                        const val = statistics.monthly_deducted?.[idx + 1] || 0;
                        return (
                          <td key={idx} className="py-3 px-2 text-center font-semibold">
                            {val > 0 ? <span className="text-emerald-700 font-bold">{val}</span> : <span className="text-slate-300">-</span>}
                          </td>
                        );
                      })}
                      <td className="py-3 px-3 text-center font-bold text-emerald-800 bg-emerald-50/80">
                        {statistics.total_quota_deducted_days ?? 0} Hari
                      </td>
                    </tr>

                    {/* Sakit & Izin Bebas Kuota */}
                    <tr className="hover:bg-blue-50/40 transition-colors">
                      <td className="py-3 px-3 text-left">
                        <div className="flex items-center space-x-1.5">
                          <span className="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></span>
                          <span className="font-bold text-slate-900">Sakit & Izin (Tanpa Potong Kuota)</span>
                        </div>
                      </td>
                      {MONTH_NAMES.map((_, idx) => {
                        const val = statistics.monthly_other?.[idx + 1] || 0;
                        return (
                          <td key={idx} className="py-3 px-2 text-center font-semibold">
                            {val > 0 ? <span className="text-blue-700 font-bold">{val}</span> : <span className="text-slate-300">-</span>}
                          </td>
                        );
                      })}
                      <td className="py-3 px-3 text-center font-bold text-blue-800 bg-blue-50/80">
                        {((statistics.total_sick_days || 0) + (statistics.total_permission_days || 0))} Hari
                      </td>
                    </tr>

                    {/* Total Keseluruhan */}
                    <tr className="bg-slate-50 font-bold text-slate-900">
                      <td className="py-3 px-3 text-left uppercase text-[11px] tracking-wider text-slate-700">
                        Total Hari Tidak Bekerja
                      </td>
                      {MONTH_NAMES.map((_, idx) => {
                        const val = statistics.monthly_total?.[idx + 1] || 0;
                        return (
                          <td key={idx} className="py-3 px-2 text-center font-bold">
                            {val > 0 ? <span className="text-slate-900">{val}</span> : <span className="text-slate-300">-</span>}
                          </td>
                        );
                      })}
                      <td className="py-3 px-3 text-center font-bold text-slate-900 bg-slate-200">
                        {statistics.total_not_working_days ?? 0} Hari
                      </td>
                    </tr>
                  </tbody>
                </table>
              </CardContent>
            </Card>

            {/* Category Breakdown Cards Grid */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                  Rincian Riwayat Per Kategori Tidak Bekerja ({selectedYear})
                </h3>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                {(statistics.category_summary || []).map((cat) => (
                  <Card key={cat.id} className="border-slate-200/90 bg-white shadow-2xs hover:shadow-sm transition-all">
                    <CardContent className="p-4 space-y-2">
                      <div className="flex items-start justify-between gap-2">
                        <h4 className="text-xs font-bold text-slate-900 leading-snug">{cat.name}</h4>
                        {cat.deducts_quota ? (
                          <Badge variant="success" className="font-semibold text-[9px] shrink-0">
                            Potong Kuota
                          </Badge>
                        ) : (
                          <Badge variant="outline" className="font-medium text-[9px] text-slate-500 bg-slate-50 shrink-0">
                            Bebas Kuota
                          </Badge>
                        )}
                      </div>

                      <div className="flex items-baseline justify-between pt-1 border-t border-slate-100 text-xs">
                        <span className="text-slate-500 font-normal">Realisasi Disetujui:</span>
                        <span className="font-bold text-slate-900">
                          {cat.total_amount} {cat.unit_type} ({cat.approved_count}x)
                        </span>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Modal Detail Request */}
        <AnimatePresence>
          {selectedRequest && (
            <div className="fixed inset-0 z-[100] flex items-end sm:items-center justify-center sm:p-4">
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                transition={{ duration: 0.15 }}
                className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm"
                onClick={() => setSelectedRequest(null)}
              />

              <motion.div
                initial={{ opacity: 0, y: 30, scale: 0.96 }}
                animate={{ opacity: 1, y: 0, scale: 1 }}
                exit={{ opacity: 0, y: 30, scale: 0.96 }}
                transition={{ type: 'spring', stiffness: 380, damping: 30 }}
                className="relative z-10 w-full max-w-lg p-5 sm:p-6 rounded-t-3xl sm:rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 max-h-[85vh] flex flex-col"
              >
                {/* Header */}
                <div className="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                  <div className="flex items-center space-x-2.5">
                    <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                      <FileText size={20} />
                    </div>
                    <div>
                      <h3 className="text-base font-bold text-slate-900 leading-tight">Detail Permohonan</h3>
                      <p className="text-[11px] text-slate-400 font-normal">Informasi lengkap permohonan ketidakhadiran</p>
                    </div>
                  </div>
                  <button
                    type="button"
                    onClick={() => setSelectedRequest(null)}
                    className="p-2 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800 transition-colors"
                  >
                    <X size={18} />
                  </button>
                </div>

                {/* Body Content */}
                <div className="space-y-3 overflow-y-auto flex-1 pr-1 text-xs">
                  {/* Status Header Banner */}
                  <div className={`p-4 rounded-2xl border flex items-center justify-between ${
                    selectedRequest.status === 'approved' ? 'bg-emerald-50/80 border-emerald-200 text-emerald-950' :
                    selectedRequest.status === 'rejected' ? 'bg-rose-50/80 border-rose-200 text-rose-950' :
                    'bg-amber-50/80 border-amber-200 text-amber-950'
                  }`}>
                    <div>
                      <span className="text-[10px] font-semibold uppercase tracking-wider block opacity-70">Status Permohonan</span>
                      <p className="text-sm font-bold mt-0.5">
                        {selectedRequest.status === 'approved' ? '✅ Disetujui (Approved)' :
                         selectedRequest.status === 'rejected' ? '❌ Ditolak (Rejected)' :
                         `⏳ Pending (${
                            selectedRequest.current_stage === 'approval_1' ? 'Menunggu Approval 1 - Supervisor' :
                            selectedRequest.current_stage === 'approval_2' ? 'Menunggu Approval 2 - Manager' :
                            'Menunggu Approval Akhir HRD'
                         })`}
                      </p>
                    </div>
                    <span className="font-mono text-xs font-bold px-3 py-1 rounded-full bg-white/90 border shadow-xs">
                      {selectedRequest.request_number}
                    </span>
                  </div>

                  {/* Multi-Tier Approval Progress Stepper */}
                  <div className="p-4 rounded-2xl border border-slate-200 bg-slate-50 space-y-2.5">
                    <span className="text-[10px] font-bold uppercase text-slate-500 tracking-wider block">
                      Progress Persetujuan Bertingkat (Approval Timeline)
                    </span>

                    <div className="space-y-2">
                      {/* Tier 1 */}
                      <div className="flex items-start space-x-2.5">
                        <div className={`w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5 ${
                          selectedRequest.approved_by_1 ? 'bg-emerald-600 text-white' :
                          selectedRequest.current_stage === 'approval_1' && selectedRequest.status === 'pending' ? 'bg-amber-500 text-white' :
                          'bg-slate-200 text-slate-500'
                        }`}>
                          {selectedRequest.approved_by_1 ? '✓' : '1'}
                        </div>
                        <div className="text-[11px] min-w-0 flex-1">
                          <p className="font-semibold text-slate-900">
                            Tingkat 1: Atasan 1 (Supervisor / Lead)
                            {selectedRequest.approver1 && <span className="text-emerald-700 font-bold ml-1">&bull; {selectedRequest.approver1.name} (Disetujui)</span>}
                          </p>
                          {selectedRequest.approval_1_note && (
                            <p className="text-slate-600 italic mt-0.5">&ldquo;{selectedRequest.approval_1_note}&rdquo;</p>
                          )}
                        </div>
                      </div>

                      {/* Tier 2 */}
                      <div className="flex items-start space-x-2.5">
                        <div className={`w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5 ${
                          selectedRequest.approved_by_2 ? 'bg-emerald-600 text-white' :
                          selectedRequest.current_stage === 'approval_2' && selectedRequest.status === 'pending' ? 'bg-amber-500 text-white' :
                          'bg-slate-200 text-slate-500'
                        }`}>
                          {selectedRequest.approved_by_2 ? '✓' : '2'}
                        </div>
                        <div className="text-[11px] min-w-0 flex-1">
                          <p className="font-semibold text-slate-900">
                            Tingkat 2: Atasan 2 (Manager / Dept Head)
                            {selectedRequest.approver2 && <span className="text-emerald-700 font-bold ml-1">&bull; {selectedRequest.approver2.name} (Disetujui)</span>}
                          </p>
                          {selectedRequest.approval_2_note && (
                            <p className="text-slate-600 italic mt-0.5">&ldquo;{selectedRequest.approval_2_note}&rdquo;</p>
                          )}
                        </div>
                      </div>

                      {/* Tier 3: HRD */}
                      <div className="flex items-start space-x-2.5">
                        <div className={`w-5 h-5 rounded-full flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5 ${
                          selectedRequest.approved_by_hrd || (selectedRequest.status === 'approved' && !selectedRequest.approved_by_1 && !selectedRequest.approved_by_2) ? 'bg-emerald-600 text-white' :
                          selectedRequest.current_stage === 'hrd' && selectedRequest.status === 'pending' ? 'bg-amber-500 text-white' :
                          'bg-slate-200 text-slate-500'
                        }`}>
                          {selectedRequest.status === 'approved' ? '✓' : '3'}
                        </div>
                        <div className="text-[11px] min-w-0 flex-1">
                          <p className="font-semibold text-slate-900">
                            Tingkat 3: HRD / PGA Admin (Final)
                            {selectedRequest.status === 'approved' && <span className="text-emerald-700 font-bold ml-1">&bull; Disetujui Final</span>}
                          </p>
                          {selectedRequest.approval_note && (
                            <p className="text-slate-600 italic mt-0.5">&ldquo;{selectedRequest.approval_note}&rdquo;</p>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Data Cards Grid */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50">
                      <span className="text-[10px] font-semibold uppercase text-slate-400 block mb-0.5">Pemohon / NIK</span>
                      <p className="font-bold text-slate-900">{selectedRequest.user?.name || currentUser.name || 'Pemohon'}</p>
                      <p className="text-[11px] text-slate-500 font-medium">{selectedRequest.user?.nik || currentUser.nik || '-'}</p>
                    </div>

                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50">
                      <span className="text-[10px] font-semibold uppercase text-slate-400 block mb-0.5">Sifat & Kategori</span>
                      <p className="font-bold text-slate-900">{selectedRequest.category?.name}</p>
                      <p className="text-[11px] text-emerald-700 font-semibold">{selectedRequest.submission_type}</p>
                    </div>

                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50">
                      <span className="text-[10px] font-semibold uppercase text-slate-400 block mb-0.5">Periode Cuti</span>
                      <p className="font-bold text-slate-900">{selectedRequest.start_date} s/d {selectedRequest.end_date}</p>
                    </div>

                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50">
                      <span className="text-[10px] font-semibold uppercase text-slate-400 block mb-0.5">Total Durasi</span>
                      <p className="font-bold text-slate-900">{selectedRequest.amount} {selectedRequest.unit}</p>
                    </div>
                  </div>

                  {/* Alasan Permohonan */}
                  <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50 space-y-1">
                    <span className="text-[10px] font-semibold uppercase text-slate-400 block">Detail Alasan Permohonan:</span>
                    <p className="font-normal text-slate-800 bg-white p-3 rounded-xl border border-slate-200 leading-relaxed text-xs">
                      {selectedRequest.reason}
                    </p>
                  </div>

                  {/* Catatan Peninjau / Manager */}
                  {selectedRequest.approval_note && (
                    <div className="p-3.5 rounded-2xl border border-amber-200 bg-amber-50/50 space-y-1">
                      <span className="text-[10px] font-bold uppercase text-amber-800 block">Catatan Persetujuan Manager / HRD:</span>
                      <p className="font-semibold text-slate-900 bg-white p-3 rounded-xl border border-amber-200 leading-relaxed text-xs">
                        {selectedRequest.approval_note}
                      </p>
                    </div>
                  )}

                  {/* File Lampiran */}
                  {selectedRequest.attachment_path && (
                    <div className="p-3.5 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-between">
                      <div>
                        <span className="text-[10px] font-semibold uppercase text-slate-400 block">File Lampiran Dokumen</span>
                        <p className="text-xs font-bold text-slate-700">Surat Keterangan / Bukti Lampiran</p>
                      </div>
                      <a
                        href={`/storage/${selectedRequest.attachment_path}`}
                        target="_blank"
                        rel="noreferrer"
                        className="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs flex items-center space-x-1.5 shadow-xs transition-colors"
                      >
                        <Paperclip size={14} />
                        <span>Buka File</span>
                      </a>
                    </div>
                  )}
                </div>

                {/* Footer Buttons */}
                <div className="pt-3 border-t border-slate-100 shrink-0">
                  <button
                    type="button"
                    onClick={() => setSelectedRequest(null)}
                    className="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-sm transition-all"
                  >
                    Tutup Detail
                  </button>
                </div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>

      </div>
    </AuthenticatedLayout>
  );
}
