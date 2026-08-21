import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout, { UserAvatar } from '@/Layouts/AuthenticatedLayout';
import { motion, AnimatePresence } from 'framer-motion';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Receipt,
  UploadCloud,
  FilePlus,
  Trash2,
  Eye,
  Download,
  Search,
  Filter,
  Users,
  CheckCircle2,
  Clock,
  Building,
  AlertCircle,
  FileText,
  Check,
  Send,
  MoreVertical,
  X,
  Sparkles,
  Archive
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
import { showAlert, showConfirm, showToast } from '@/Utils/swal';

const containerVariants = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: {
      staggerChildren: 0.05
    }
  }
};

const itemVariants = {
  hidden: { opacity: 0, y: 12 },
  show: { opacity: 1, y: 0, transition: { duration: 0.2, ease: 'easeOut' } }
};

const MONTHS = [
  { value: 1, name: 'Januari' },
  { value: 2, name: 'Februari' },
  { value: 3, name: 'Maret' },
  { value: 4, name: 'April' },
  { value: 5, name: 'Mei' },
  { value: 6, name: 'Juni' },
  { value: 7, name: 'Juli' },
  { value: 8, name: 'Agustus' },
  { value: 9, name: 'September' },
  { value: 10, name: 'Oktober' },
  { value: 11, name: 'November' },
  { value: 12, name: 'Desember' },
];

export default function HrdPayslips({
  payslips = [],
  employees = [],
  departments = [],
  stats = {},
  filters = {}
}) {
  const currentMonth = filters.month ? parseInt(filters.month) : new Date().getMonth() + 1;
  const currentYear = filters.year ? parseInt(filters.year) : new Date().getFullYear();

  const [selectedMonth, setSelectedMonth] = useState(currentMonth);
  const [selectedYear, setSelectedYear] = useState(currentYear);
  const [selectedDept, setSelectedDept] = useState(filters.department_id || '');
  const [search, setSearch] = useState(filters.search || '');

  // Modals
  const [isBulkOpen, setIsBulkOpen] = useState(false);
  const [isSingleOpen, setIsSingleOpen] = useState(false);
  const [previewPayslip, setPreviewPayslip] = useState(null);

  // Bulk Upload Form
  const bulkForm = useForm({
    month: currentMonth,
    year: currentYear,
    files: [],
    zip_file: null,
  });

  // Single Upload Form
  const singleForm = useForm({
    user_id: '',
    month: currentMonth,
    year: currentYear,
    file: null,
    notes: '',
  });

  const handleFilterSubmit = (e) => {
    e?.preventDefault();
    router.get(route('hrd.payslips'), {
      month: selectedMonth,
      year: selectedYear,
      department_id: selectedDept,
      search,
    }, { preserveState: true });
  };

  const handleOpenBulkModal = () => {
    bulkForm.reset();
    bulkForm.setData({
      month: selectedMonth,
      year: selectedYear,
      files: [],
      zip_file: null,
    });
    setIsBulkOpen(true);
  };

  const handleOpenSingleModal = () => {
    singleForm.reset();
    singleForm.setData({
      user_id: employees[0]?.id ? String(employees[0].id) : '',
      month: selectedMonth,
      year: selectedYear,
      file: null,
      notes: '',
    });
    setIsSingleOpen(true);
  };

  const handleBulkSubmit = (e) => {
    e.preventDefault();

    if ((!bulkForm.data.files || bulkForm.data.files.length === 0) && !bulkForm.data.zip_file) {
      showAlert({
        title: 'File Belum Dipilih',
        text: 'Silakan pilih beberapa file PDF atau 1 file ZIP slip gaji.',
        icon: 'warning'
      });
      return;
    }

    bulkForm.post(route('hrd.payslips.bulk-upload'), {
      forceFormData: true,
      onSuccess: () => {
        setIsBulkOpen(false);
        bulkForm.reset();
      },
      onError: (errs) => {
        showAlert({
          title: 'Gagal Upload Slip Gaji',
          text: Object.values(errs)[0] || 'Terjadi kesalahan saat memproses file.',
          icon: 'error'
        });
      }
    });
  };

  const handleSingleSubmit = (e) => {
    e.preventDefault();
    if (!singleForm.data.file) {
      showAlert({
        title: 'File PDF Belum Dipilih',
        text: 'Silakan pilih file PDF slip gaji.',
        icon: 'warning'
      });
      return;
    }

    singleForm.post(route('hrd.payslips.single-upload'), {
      forceFormData: true,
      onSuccess: () => {
        setIsSingleOpen(false);
        singleForm.reset();
        showToast('Slip gaji karyawan berhasil diunggah!');
      },
      onError: (errs) => {
        showAlert({
          title: 'Gagal Upload Slip Gaji',
          text: Object.values(errs)[0] || 'Terjadi kesalahan saat memproses file.',
          icon: 'error'
        });
      }
    });
  };

  const handleDelete = async (payslip) => {
    const confirmed = await showConfirm({
      title: 'Hapus Slip Gaji?',
      text: `Hapus slip gaji milik ${payslip.user?.name} periode ${payslip.period_label}? File fisik di server juga akan dihapus.`,
      icon: 'warning',
      confirmText: 'Ya, Hapus',
      cancelText: 'Batal'
    });

    if (confirmed) {
      router.delete(route('hrd.payslips.destroy', payslip.id), {
        onSuccess: () => showToast('Slip gaji berhasil dihapus.'),
      });
    }
  };

  const currentMonthObj = MONTHS.find(m => m.value === selectedMonth);

  return (
    <AuthenticatedLayout title="Kelola & Distribusi Slip Gaji">
      <Head title="Kelola Slip Gaji - HRD SGIN" />

      <motion.div
        variants={containerVariants}
        initial="hidden"
        animate="show"
        className="space-y-6"
      >
        {/* ========================================================================= */}
        {/* 1. HERO BANNER                                                            */}
        {/* ========================================================================= */}
        <motion.div
          variants={itemVariants}
          className="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#0FA172] to-[#1CB67C] text-white shadow-lg shadow-emerald-600/20 relative overflow-hidden"
        >
          <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none" />

          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/20 border border-white/30 text-white text-xs font-bold uppercase tracking-wider mb-3 backdrop-blur-md">
                <Receipt size={14} className="text-emerald-100" />
                <span>HRD Payroll Center</span>
              </div>
              <h1 className="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Kelola & Distribusi Slip Gaji
              </h1>
              <p className="text-sm text-emerald-50 mt-1 max-w-3xl font-medium leading-relaxed">
                Kirim slip gaji ke seluruh karyawan sekaligus secara otomatis (Bulk Auto-Match berdasarkan NIK/Nama) tanpa perlu kirim satu per satu.
              </p>
            </div>

            <div className="flex flex-wrap gap-2.5 shrink-0">
              <Button
                onClick={handleOpenBulkModal}
                size="lg"
                className="bg-white text-emerald-800 hover:bg-emerald-50 font-black shadow-md space-x-1.5"
              >
                <UploadCloud size={18} className="text-emerald-700" />
                <span>Bulk Upload (Banyak PDF / ZIP)</span>
              </Button>

              <Button
                onClick={handleOpenSingleModal}
                size="lg"
                variant="secondary"
                className="bg-emerald-800/80 hover:bg-emerald-800 text-white font-bold border border-white/20 backdrop-blur-md space-x-1.5"
              >
                <FilePlus size={18} />
                <span>Upload 1 Karyawan</span>
              </Button>
            </div>
          </div>
        </motion.div>

        {/* ========================================================================= */}
        {/* 2. STATS ROW                                                              */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <Users size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Total Karyawan</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.total_employees || employees.length}</h3>
              </div>
            </div>
          </Card>

          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <Receipt size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Terkirim ({currentMonthObj?.name})</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.distributed_count || payslips.length}</h3>
              </div>
            </div>
          </Card>

          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                <CheckCircle2 size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Sudah Dilihat</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.viewed_count || 0} ({stats.viewed_percentage || 0}%)</h3>
              </div>
            </div>
          </Card>

          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                <Clock size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Belum Punya Slip</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.pending_count || 0}</h3>
              </div>
            </div>
          </Card>
        </motion.div>

        {/* ========================================================================= */}
        {/* 3. FILTER BAR                                                             */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="p-4 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-3">
          <form onSubmit={handleFilterSubmit} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-center">
            {/* Month Selector */}
            <div>
              <label className="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">
                Bulan Periode
              </label>
              <Select
                value={String(selectedMonth)}
                onValueChange={(val) => setSelectedMonth(parseInt(val))}
              >
                <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-xs font-bold text-slate-900 rounded-xl">
                  <SelectValue placeholder="Pilih Bulan" />
                </SelectTrigger>
                <SelectContent>
                  {MONTHS.map((m) => (
                    <SelectItem key={m.value} value={String(m.value)}>
                      {m.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Year Selector */}
            <div>
              <label className="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">
                Tahun Periode
              </label>
              <Select
                value={String(selectedYear)}
                onValueChange={(val) => setSelectedYear(parseInt(val))}
              >
                <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-xs font-bold text-slate-900 rounded-xl">
                  <SelectValue placeholder="Pilih Tahun" />
                </SelectTrigger>
                <SelectContent>
                  {[currentYear - 2, currentYear - 1, currentYear, currentYear + 1].map((yr) => (
                    <SelectItem key={yr} value={String(yr)}>
                      Tahun {yr}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Department Filter */}
            <div>
              <label className="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">
                Departemen
              </label>
              <Select
                value={selectedDept ? String(selectedDept) : 'all'}
                onValueChange={(val) => setSelectedDept(val === 'all' ? '' : val)}
              >
                <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-xs font-semibold text-slate-900 rounded-xl">
                  <SelectValue placeholder="Semua Departemen" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Departemen</SelectItem>
                  {departments.map((dept) => (
                    <SelectItem key={dept.id} value={String(dept.id)}>
                      {dept.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Search */}
            <div>
              <label className="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">
                Cari NIK / Nama
              </label>
              <div className="relative">
                <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder="Ketik nama atau NIK..."
                  className="w-full pl-8 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500/20 outline-none"
                />
              </div>
            </div>

            {/* Submit Filter Button */}
            <div className="flex items-end">
              <Button
                type="submit"
                variant="emerald"
                className="w-full rounded-xl text-xs font-bold space-x-1 py-2"
              >
                <Filter size={14} />
                <span>Terapkan Filter</span>
              </Button>
            </div>
          </form>
        </motion.div>

        {/* ========================================================================= */}
        {/* 4. PAYSLIP TABLE / CARD LIST                                              */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="text-base font-extrabold text-slate-900 flex items-center space-x-2">
              <Receipt size={18} className="text-emerald-600" />
              <span>Daftar Slip Gaji Periode {currentMonthObj?.name} {selectedYear} ({payslips.length} Terdistribusi)</span>
            </h2>
          </div>

          <div className="rounded-3xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
            {payslips && payslips.length > 0 ? (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead>
                    <tr className="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                      <th className="py-3.5 px-4">Karyawan & NIK</th>
                      <th className="py-3.5 px-4">Departemen</th>
                      <th className="py-3.5 px-4">Periode</th>
                      <th className="py-3.5 px-4">File Slip</th>
                      <th className="py-3.5 px-4">Status Pembacaan</th>
                      <th className="py-3.5 px-4">Waktu Terdistribusi</th>
                      <th className="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-slate-700">
                    {payslips.map((p) => {
                      const isViewed = p.is_viewed || Boolean(p.viewed_at);

                      return (
                        <tr key={p.id} className="hover:bg-slate-50/80 transition-colors">
                          <td className="py-3.5 px-4">
                            <div className="flex items-center space-x-3">
                              <UserAvatar user={p.user} size="w-9 h-9" textSize="text-xs" />
                              <div>
                                <span className="font-extrabold text-slate-900 text-xs block">{p.user?.name || 'Karyawan'}</span>
                                <span className="font-mono text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 inline-block mt-0.5">
                                  {p.user?.nik || 'EMP-???'}
                                </span>
                              </div>
                            </div>
                          </td>

                          <td className="py-3.5 px-4 font-semibold text-slate-800">
                            {p.user?.department?.name || 'General'}
                          </td>

                          <td className="py-3.5 px-4 font-extrabold text-slate-900">
                            {p.period_label || `${p.month_name} ${p.year}`}
                          </td>

                          <td className="py-3.5 px-4">
                            <div className="space-y-0.5">
                              <span className="font-medium text-slate-900 block truncate max-w-[180px]" title={p.original_filename}>
                                {p.original_filename}
                              </span>
                              <span className="text-[10px] text-slate-400 font-bold">
                                {p.formatted_file_size} &bull; PDF
                              </span>
                            </div>
                          </td>

                          <td className="py-3.5 px-4">
                            {isViewed ? (
                              <div className="space-y-0.5">
                                <Badge variant="success" className="text-[10px] font-bold space-x-1">
                                  <CheckCircle2 size={11} />
                                  <span>Sudah Dilihat</span>
                                </Badge>
                                {p.viewed_at && (
                                  <span className="text-[10px] text-slate-400 block">
                                    {new Date(p.viewed_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}
                                  </span>
                                )}
                              </div>
                            ) : (
                              <Badge variant="secondary" className="text-[10px] font-semibold text-slate-500 bg-slate-100">
                                Belum Dibuka
                              </Badge>
                            )}
                          </td>

                          <td className="py-3.5 px-4 text-slate-500 text-[11px]">
                            {new Date(p.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
                          </td>

                          <td className="py-3.5 px-4 text-right">
                            <div className="flex items-center justify-end space-x-1">
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setPreviewPayslip(p)}
                                className="h-8 px-2.5 rounded-xl text-xs font-bold space-x-1 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50"
                              >
                                <Eye size={14} />
                                <span className="hidden sm:inline">Lihat</span>
                              </Button>

                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <Button variant="ghost" size="icon" className="h-8 w-8 rounded-xl text-slate-500 hover:text-slate-900">
                                    <MoreVertical size={15} />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-48">
                                  <DropdownMenuLabel>Aksi Slip Gaji</DropdownMenuLabel>
                                  <DropdownMenuItem onClick={() => setPreviewPayslip(p)}>
                                    <Eye className="mr-2 h-4 w-4 text-emerald-600" />
                                    <span>Lihat Pratinjau PDF</span>
                                  </DropdownMenuItem>
                                  <DropdownMenuItem asChild>
                                    <a href={route('payslips.download', p.id)} download className="flex items-center w-full">
                                      <Download className="mr-2 h-4 w-4 text-blue-600" />
                                      <span>Unduh File PDF</span>
                                    </a>
                                  </DropdownMenuItem>
                                  <DropdownMenuSeparator />
                                  <DropdownMenuItem
                                    onClick={() => handleDelete(p)}
                                    className="text-rose-600 focus:bg-rose-50 focus:text-rose-700"
                                  >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    <span>Hapus Slip Gaji</span>
                                  </DropdownMenuItem>
                                </DropdownMenuContent>
                              </DropdownMenu>
                            </div>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="py-16 text-center text-slate-400 space-y-3">
                <Receipt size={42} className="mx-auto opacity-30 text-slate-400" />
                <div>
                  <h3 className="text-sm font-extrabold text-slate-700">Belum Ada Slip Gaji Periode Ini</h3>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Silakan gunakan tombol <strong>Bulk Upload</strong> untuk mendistribusikan slip gaji bulan {currentMonthObj?.name} {selectedYear}.
                  </p>
                </div>
                <Button
                  onClick={handleOpenBulkModal}
                  variant="emerald"
                  size="sm"
                  className="rounded-xl space-x-1 font-bold"
                >
                  <UploadCloud size={15} />
                  <span>Mulai Upload Slip Gaji</span>
                </Button>
              </div>
            )}
          </div>
        </motion.div>
      </motion.div>

      {/* ========================================================================= */}
      {/* MODAL 1: BULK AUTO-MATCH UPLOAD                                           */}
      {/* ========================================================================= */}
      <AnimatePresence>
        {isBulkOpen && (
          <div className="fixed inset-0 z-[100] flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs"
              onClick={() => setIsBulkOpen(false)}
            />

            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative z-10 w-full max-w-xl rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl overflow-hidden my-6 max-h-[92vh] flex flex-col transform-gpu my-auto sm:my-auto"
            >
              {/* Modal Header */}
              <div className="p-5 sm:p-6 pb-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <UploadCloud size={20} />
                  </div>
                  <div>
                    <h3 className="text-base font-black text-slate-900 leading-tight">
                      Bulk Upload & Auto-Distribusi Slip Gaji
                    </h3>
                    <p className="text-xs text-slate-500 font-medium mt-0.5">
                      Upload banyak PDF atau 1 file ZIP sekaligus &mdash; sistem mencocokkan otomatis ke NIK/Nama karyawan.
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setIsBulkOpen(false)}
                  className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800 transition-colors"
                >
                  <X size={18} />
                </button>
              </div>

              <form onSubmit={handleBulkSubmit} className="flex flex-col flex-1 overflow-hidden">
                <div className="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                  {/* Period Selection */}
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Bulan Penggajian <span className="text-rose-600">*</span>
                      </label>
                      <Select
                        value={String(bulkForm.data.month)}
                        onValueChange={(val) => bulkForm.setData('month', parseInt(val))}
                      >
                        <SelectTrigger className="w-full bg-slate-50 border-slate-300 text-slate-900 font-bold text-xs rounded-xl">
                          <SelectValue placeholder="Pilih Bulan" />
                        </SelectTrigger>
                        <SelectContent>
                          {MONTHS.map((m) => (
                            <SelectItem key={m.value} value={String(m.value)}>
                              {m.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Tahun Penggajian <span className="text-rose-600">*</span>
                      </label>
                      <Select
                        value={String(bulkForm.data.year)}
                        onValueChange={(val) => bulkForm.setData('year', parseInt(val))}
                      >
                        <SelectTrigger className="w-full bg-slate-50 border-slate-300 text-slate-900 font-bold text-xs rounded-xl">
                          <SelectValue placeholder="Pilih Tahun" />
                        </SelectTrigger>
                        <SelectContent>
                          {[currentYear - 1, currentYear, currentYear + 1].map((yr) => (
                            <SelectItem key={yr} value={String(yr)}>
                              Tahun {yr}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  {/* Smart Auto-Matcher Guide Box */}
                  <div className="p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200 text-xs space-y-2">
                    <div className="flex items-center space-x-2 text-emerald-900 font-black">
                      <Sparkles size={16} className="text-emerald-600" />
                      <span>Cara Kerja Auto-Matcher Otomatis:</span>
                    </div>
                    <ul className="space-y-1 text-slate-700 text-[11px] leading-relaxed list-disc list-inside">
                      <li>Beri nama file PDF dengan <strong>NIK Karyawan</strong> (contoh: <code className="bg-white px-1.5 py-0.5 rounded border text-emerald-800 font-bold">EMP-2026-001.pdf</code> atau <code className="bg-white px-1.5 py-0.5 rounded border text-emerald-800 font-bold">EMP2026001_SlipGaji.pdf</code>).</li>
                      <li>Atau gunakan <strong>Nama Karyawan</strong> (contoh: <code className="bg-white px-1.5 py-0.5 rounded border text-emerald-800 font-bold">Budi_Santoso.pdf</code>).</li>
                      <li>Anda dapat memilih <strong>banyak file PDF sekaligus</strong> (Ctrl+A / drag all) atau mengemasnya menjadi <strong>1 file ZIP</strong>.</li>
                    </ul>
                  </div>

                  {/* Multiple PDF File Dropzone */}
                  <div className="space-y-2">
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                      Pilihan 1: Pilih Banyak File PDF Sekaligus
                    </label>
                    <div className="p-4 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100/80 transition-colors text-center cursor-pointer relative">
                      <input
                        type="file"
                        multiple
                        accept="application/pdf"
                        onChange={(e) => {
                          const fileList = Array.from(e.target.files);
                          bulkForm.setData('files', fileList);
                          bulkForm.setData('zip_file', null);
                        }}
                        className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                      />
                      <FileText size={32} className="mx-auto text-slate-400 mb-2" />
                      <p className="text-xs font-bold text-slate-800">
                        {bulkForm.data.files && bulkForm.data.files.length > 0
                          ? `✅ ${bulkForm.data.files.length} file PDF telah dipilih`
                          : 'Klik untuk memilih banyak file PDF slip gaji (Multi-select)'}
                      </p>
                      <p className="text-[10px] text-slate-400 mt-0.5">
                        Maksimal 10MB per file PDF
                      </p>
                    </div>
                  </div>

                  {/* Or Single ZIP File */}
                  <div className="space-y-2">
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                      Pilihan 2: Atau Upload 1 File ZIP (Isi PDF)
                    </label>
                    <div className="p-4 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100/80 transition-colors text-center cursor-pointer relative">
                      <input
                        type="file"
                        accept=".zip,application/zip"
                        onChange={(e) => {
                          const file = e.target.files[0];
                          if (file) {
                            bulkForm.setData('zip_file', file);
                            bulkForm.setData('files', []);
                          }
                        }}
                        className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                      />
                      <Archive size={32} className="mx-auto text-slate-400 mb-2" />
                      <p className="text-xs font-bold text-slate-800">
                        {bulkForm.data.zip_file
                          ? `✅ File ZIP dipilih: ${bulkForm.data.zip_file.name}`
                          : 'Klik untuk memilih file .ZIP yang berisi file-file PDF'}
                      </p>
                      <p className="text-[10px] text-slate-400 mt-0.5">
                        Maksimal 50MB per file ZIP
                      </p>
                    </div>
                  </div>
                </div>

                {/* Modal Footer */}
                <div className="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2.5 shrink-0">
                  <Button
                    type="button"
                    variant="secondary"
                    className="rounded-xl px-4"
                    onClick={() => setIsBulkOpen(false)}
                  >
                    Batal
                  </Button>
                  <Button
                    type="submit"
                    variant="emerald"
                    className="rounded-xl px-5 font-extrabold space-x-1.5"
                    disabled={bulkForm.processing}
                  >
                    <Send size={15} />
                    <span>{bulkForm.processing ? 'Memproses Auto-Match...' : 'Kirim & Distribusikan Otomatis'}</span>
                  </Button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ========================================================================= */}
      {/* MODAL 2: SINGLE EMPLOYEE UPLOAD                                           */}
      {/* ========================================================================= */}
      <AnimatePresence>
        {isSingleOpen && (
          <div className="fixed inset-0 z-[100] flex items-start sm:items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-slate-950/70 backdrop-blur-xs"
              onClick={() => setIsSingleOpen(false)}
            />

            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative z-10 w-full max-w-lg rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl overflow-hidden my-6 max-h-[92vh] flex flex-col transform-gpu my-auto sm:my-auto"
            >
              <div className="p-5 sm:p-6 pb-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <FilePlus size={20} />
                  </div>
                  <div>
                    <h3 className="text-base font-black text-slate-900 leading-tight">
                      Upload Slip Gaji 1 Karyawan
                    </h3>
                    <p className="text-xs text-slate-500 font-medium mt-0.5">
                      Unggah manual slip gaji untuk karyawan tertentu.
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setIsSingleOpen(false)}
                  className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800 transition-colors"
                >
                  <X size={18} />
                </button>
              </div>

              <form onSubmit={handleSingleSubmit} className="flex flex-col flex-1 overflow-hidden">
                <div className="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                  {/* Select Employee */}
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                      Pilih Karyawan <span className="text-rose-600">*</span>
                    </label>
                    <Select
                      value={singleForm.data.user_id ? String(singleForm.data.user_id) : ''}
                      onValueChange={(val) => singleForm.setData('user_id', val)}
                    >
                      <SelectTrigger className="w-full bg-slate-50 border-slate-300 text-slate-900 font-bold text-xs rounded-xl">
                        <SelectValue placeholder="-- Pilih Karyawan --" />
                      </SelectTrigger>
                      <SelectContent>
                        {employees.map((emp) => (
                          <SelectItem key={emp.id} value={String(emp.id)}>
                            {emp.name} ({emp.nik}) - {emp.department?.name || 'General'}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  {/* Period */}
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Bulan <span className="text-rose-600">*</span>
                      </label>
                      <Select
                        value={String(singleForm.data.month)}
                        onValueChange={(val) => singleForm.setData('month', parseInt(val))}
                      >
                        <SelectTrigger className="w-full bg-slate-50 border-slate-300 text-slate-900 font-bold text-xs rounded-xl">
                          <SelectValue placeholder="Pilih Bulan" />
                        </SelectTrigger>
                        <SelectContent>
                          {MONTHS.map((m) => (
                            <SelectItem key={m.value} value={String(m.value)}>
                              {m.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Tahun <span className="text-rose-600">*</span>
                      </label>
                      <Select
                        value={String(singleForm.data.year)}
                        onValueChange={(val) => singleForm.setData('year', parseInt(val))}
                      >
                        <SelectTrigger className="w-full bg-slate-50 border-slate-300 text-slate-900 font-bold text-xs rounded-xl">
                          <SelectValue placeholder="Pilih Tahun" />
                        </SelectTrigger>
                        <SelectContent>
                          {[currentYear - 1, currentYear, currentYear + 1].map((yr) => (
                            <SelectItem key={yr} value={String(yr)}>
                              Tahun {yr}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  {/* PDF File Input */}
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                      File Slip Gaji (PDF) <span className="text-rose-600">*</span>
                    </label>
                    <input
                      type="file"
                      accept="application/pdf"
                      required
                      onChange={(e) => singleForm.setData('file', e.target.files[0])}
                      className="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                    />
                  </div>

                  {/* Notes */}
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                      Catatan Tambahan (Opsional)
                    </label>
                    <textarea
                      rows={2}
                      value={singleForm.data.notes}
                      onChange={(e) => singleForm.setData('notes', e.target.value)}
                      placeholder="Contoh: Gaji Pokok + Insentif Project..."
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-xs font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500/20 outline-none"
                    />
                  </div>
                </div>

                <div className="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2.5 shrink-0">
                  <Button
                    type="button"
                    variant="secondary"
                    className="rounded-xl px-4"
                    onClick={() => setIsSingleOpen(false)}
                  >
                    Batal
                  </Button>
                  <Button
                    type="submit"
                    variant="emerald"
                    className="rounded-xl px-5 font-bold"
                    disabled={singleForm.processing}
                  >
                    {singleForm.processing ? 'Mengunggah...' : 'Unggah Slip Gaji'}
                  </Button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ========================================================================= */}
      {/* MODAL 3: PDF PREVIEW MODAL                                                */}
      {/* ========================================================================= */}
      <AnimatePresence>
        {previewPayslip && (
          <div className="fixed inset-0 z-[100] flex items-start sm:items-center justify-center p-2 sm:p-4 overflow-hidden">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              className="fixed inset-0 bg-slate-950/75 backdrop-blur-xs"
              onClick={() => setPreviewPayslip(null)}
            />

            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative z-10 w-full max-w-4xl h-[92vh] rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl overflow-hidden flex flex-col transform-gpu my-auto sm:my-auto"
            >
              <div className="p-4 sm:p-5 border-b border-slate-200 flex items-center justify-between shrink-0 bg-slate-50">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                    <Receipt size={20} />
                  </div>
                  <div>
                    <h3 className="text-base font-black text-slate-900 leading-tight">
                      Slip Gaji - {previewPayslip.user?.name} ({previewPayslip.user?.nik})
                    </h3>
                    <p className="text-xs text-slate-500 font-medium mt-0.5">
                      Periode: {previewPayslip.period_label || `${previewPayslip.month_name} ${previewPayslip.year}`} &bull; {previewPayslip.original_filename}
                    </p>
                  </div>
                </div>

                <div className="flex items-center space-x-2">
                  <a
                    href={route('payslips.download', previewPayslip.id)}
                    download
                  >
                    <Button variant="emerald" size="sm" className="rounded-xl space-x-1">
                      <Download size={14} />
                      <span className="hidden sm:inline">Download PDF</span>
                    </Button>
                  </a>
                  <button
                    type="button"
                    onClick={() => setPreviewPayslip(null)}
                    className="p-2 rounded-xl bg-slate-200/70 text-slate-600 hover:bg-slate-300 transition-colors"
                  >
                    <X size={18} />
                  </button>
                </div>
              </div>

              <div className="flex-1 bg-slate-100 relative overflow-hidden">
                <iframe
                  src={route('payslips.preview', previewPayslip.id)}
                  title={`Slip Gaji ${previewPayslip.period_label}`}
                  className="w-full h-full border-0"
                />
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </AuthenticatedLayout>
  );
}
