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
import InstantPagination from "@/components/ui/instant-pagination";
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
  const currentMonth = filters.month === 'all' ? 'all' : (filters.month ? parseInt(filters.month) : 'all');
  const currentYear = filters.year ? parseInt(filters.year) : new Date().getFullYear();

  const [selectedMonth, setSelectedMonth] = useState(currentMonth);
  const [selectedYear, setSelectedYear] = useState(currentYear);
  const [selectedDept, setSelectedDept] = useState(filters.department_id || 'all');
  const [search, setSearch] = useState(filters.search || '');

  // Instant Client-Side Pagination States
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);

  const rawPayslips = React.useMemo(() => {
    return Array.isArray(payslips) ? payslips : (payslips?.data || []);
  }, [payslips]);

  const paginatedPayslips = React.useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return rawPayslips.slice(start, start + pageSize);
  }, [rawPayslips, currentPage, pageSize]);

  React.useEffect(() => {
    setCurrentPage(1);
  }, [rawPayslips.length]);

  // Modals
  const [isBulkOpen, setIsBulkOpen] = useState(false);
  const [isSingleOpen, setIsSingleOpen] = useState(false);
  const [previewPayslip, setPreviewPayslip] = useState(null);

  // Bulk Upload Local State for Instant UI Rendering
  const [selectedFiles, setSelectedFiles] = useState([]);
  const [selectedZip, setSelectedZip] = useState(null);
  const [isSubmittingBulk, setIsSubmittingBulk] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(null);

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

  // Client-side Scored Matcher for Instant Preview
  const findMatchingEmployee = (filename) => {
    const cleanFilename = filename.toLowerCase();
    const normFilename = cleanFilename.replace(/[^a-z0-9]/g, '');

    let bestEmp = null;
    let bestScore = 0;

    for (const emp of employees) {
      let score = 0;
      const cleanNik = (emp.nik || '').toLowerCase();
      const normNik = cleanNik.replace(/[^a-z0-9]/g, '');

      const cleanName = (emp.name || '').toLowerCase();
      const normName = cleanName.replace(/[^a-z0-9]/g, '');

      const emailPrefix = (emp.email || '').split('@')[0].toLowerCase();

      // 1. Exact NIK (100 pts)
      if (cleanNik && cleanFilename.includes(cleanNik)) {
        score = Math.max(score, 100);
      }

      // 2. Normalized NIK (90 pts)
      if (normNik && normFilename.includes(normNik)) {
        score = Math.max(score, 90);
      }

      // 3. Exact Full Name (80 pts)
      if (normName && normName.length >= 3 && normFilename.includes(normName)) {
        score = Math.max(score, 80);
      }

      // 4. Name parts matching (50-75 pts)
      const nameWords = cleanName.replace(/[^a-z0-9\s]/g, '').split(' ').filter(w => w.length >= 3);
      if (nameWords.length > 0) {
        let matchedWords = 0;
        for (const w of nameWords) {
          if (cleanFilename.includes(w) || normFilename.includes(w)) {
            matchedWords++;
          }
        }
        if (matchedWords === nameWords.length && nameWords.length > 1) {
          score = Math.max(score, 75);
        } else if (matchedWords > 0) {
          score = Math.max(score, 50 + (matchedWords * 5));
        }
      }

      // 5. Email Prefix match (40 pts)
      if (emailPrefix && emailPrefix.length >= 3) {
        if (cleanFilename.includes(emailPrefix) || normFilename.includes(emailPrefix)) {
          score = Math.max(score, 40);
        }
      }

      if (score > bestScore) {
        bestScore = score;
        bestEmp = emp;
      }
    }

    return bestScore >= 40 ? bestEmp : null;
  };

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
    setSelectedFiles([]);
    setSelectedZip(null);
    bulkForm.reset();
    bulkForm.setData({
      month: selectedMonth,
      year: selectedYear,
      files: [],
      zip_file: null,
    });
    setIsBulkOpen(true);
  };

  const handlePdfFilesChange = (newFiles) => {
    const fileArray = Array.from(newFiles);
    setSelectedFiles((prev) => {
      const existingNames = new Set(prev.map(item => item.file.name));
      const newItems = fileArray
        .filter(f => !existingNames.has(f.name))
        .map(f => {
          const matched = findMatchingEmployee(f.name);
          return {
            id: `${f.name}-${Date.now()}-${Math.random()}`,
            file: f,
            userId: matched ? String(matched.id) : '',
          };
        });

      return [...prev, ...newItems];
    });
    setSelectedZip(null);
  };

  const handleFileUserChange = (index, newUserId) => {
    setSelectedFiles((prev) => {
      const copy = [...prev];
      copy[index] = { ...copy[index], userId: newUserId };
      return copy;
    });
  };

  const handleRemoveFile = (indexToRemove) => {
    setSelectedFiles((prev) => prev.filter((_, idx) => idx !== indexToRemove));
  };

  const handleZipChange = (file) => {
    if (file) {
      setSelectedZip(file);
      setSelectedFiles([]);
      bulkForm.setData('zip_file', file);
      bulkForm.setData('files', []);
    }
  };

  const handleRemoveZip = () => {
    setSelectedZip(null);
    bulkForm.setData('zip_file', null);
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

  const getXsrfToken = () => {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (metaToken) return metaToken;
    const match = document.cookie.match(new RegExp('(^|;\\s*)XSRF-TOKEN=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : '';
  };

  const handleBulkSubmit = async (e) => {
    e.preventDefault();

    if (selectedFiles.length === 0 && !selectedZip) {
      showAlert({
        title: 'File Belum Dipilih',
        text: 'Silakan pilih beberapa file PDF atau 1 file ZIP slip gaji terlebih dahulu.',
        icon: 'warning'
      });
      return;
    }

    setIsSubmittingBulk(true);
    const token = getXsrfToken();

    // =========================================================================
    // CASE 1: ZIP FILE UPLOAD (Single Payload)
    // =========================================================================
    if (selectedZip) {
      setUploadProgress({ current: 1, total: 1, currentBatch: 1, totalBatches: 1, percent: 50 });
      const formData = new FormData();
      formData.append('month', String(bulkForm.data.month));
      formData.append('year', String(bulkForm.data.year));
      formData.append('zip_file', selectedZip);
      if (token) formData.append('_token', token);

      try {
        const headers = {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        };
        if (token) {
          headers['X-CSRF-TOKEN'] = token;
          headers['X-XSRF-TOKEN'] = token;
        }

        const response = await fetch(route('hrd.payslips.bulk-upload'), {
          method: 'POST',
          credentials: 'same-origin',
          headers,
          body: formData,
        });

        if (!response.ok) {
          if (response.status === 413) {
            throw new Error('Ukuran file ZIP terlalu besar untuk server. Anda dapat memilih beberapa file PDF langsung.');
          }
          const errData = await response.json().catch(() => ({}));
          throw new Error(errData.message || `Server error (${response.status})`);
        }

        const data = await response.json();
        setIsSubmittingBulk(false);
        setIsBulkOpen(false);
        setSelectedFiles([]);
        setSelectedZip(null);
        bulkForm.reset();
        setUploadProgress(null);

        showAlert({
          title: 'Distribusi Selesai!',
          text: data.message || 'File ZIP berhasil diekstrak dan didistribusikan ke akun karyawan.',
          icon: 'success'
        });

        router.reload({ preserveScroll: true });
      } catch (err) {
        setIsSubmittingBulk(false);
        setUploadProgress(null);
        showAlert({
          title: 'Gagal Upload File ZIP',
          text: err.message || 'Terjadi kesalahan saat mengunggah file ZIP.',
          icon: 'error'
        });
      }
      return;
    }

    // =========================================================================
    // CASE 2: MULTIPLE PDF FILES (Chunked Batch Upload - 8 PDFs per Batch)
    // Prevents HTTP 413 Payload Too Large and handles hundreds of files smoothly
    // =========================================================================
    const CHUNK_SIZE = 8;
    const totalFiles = selectedFiles.length;
    const chunks = [];
    for (let i = 0; i < totalFiles; i += CHUNK_SIZE) {
      chunks.push(selectedFiles.slice(i, i + CHUNK_SIZE));
    }

    const totalBatches = chunks.length;
    let uploadedCount = 0;
    const allMatched = [];
    const allUnmatched = [];

    setUploadProgress({
      current: 0,
      total: totalFiles,
      currentBatch: 1,
      totalBatches,
      percent: 0,
    });

    try {
      for (let bIndex = 0; bIndex < totalBatches; bIndex++) {
        const currentChunk = chunks[bIndex];
        const batchNum = bIndex + 1;

        setUploadProgress({
          current: uploadedCount,
          total: totalFiles,
          currentBatch: batchNum,
          totalBatches,
          percent: Math.round((uploadedCount / totalFiles) * 100),
        });

        const formData = new FormData();
        formData.append('month', String(bulkForm.data.month));
        formData.append('year', String(bulkForm.data.year));
        formData.append('is_chunk', '1');
        if (token) formData.append('_token', token);

        currentChunk.forEach((item, idx) => {
          formData.append(`files[${idx}]`, item.file);
          if (item.userId) {
            formData.append(`user_ids[${idx}]`, item.userId);
          }
        });

        const headers = {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        };
        if (token) {
          headers['X-CSRF-TOKEN'] = token;
          headers['X-XSRF-TOKEN'] = token;
        }

        const response = await fetch(route('hrd.payslips.bulk-upload'), {
          method: 'POST',
          credentials: 'same-origin',
          headers,
          body: formData,
        });

        if (!response.ok) {
          if (response.status === 413) {
            throw new Error(`Ukuran batch ke-${batchNum} terlalu besar untuk server (413).`);
          }
          const errData = await response.json().catch(() => ({}));
          throw new Error(errData.message || `Error pada batch ${batchNum} (Status: ${response.status})`);
        }

        const data = await response.json();
        if (data.matched) allMatched.push(...data.matched);
        if (data.unmatched) allUnmatched.push(...data.unmatched);

        uploadedCount += currentChunk.length;
        setUploadProgress({
          current: uploadedCount,
          total: totalFiles,
          currentBatch: batchNum,
          totalBatches,
          percent: Math.round((uploadedCount / totalFiles) * 100),
        });
      }

      // Selesai seluruh batch
      setIsSubmittingBulk(false);
      setIsBulkOpen(false);
      setSelectedFiles([]);
      setSelectedZip(null);
      bulkForm.reset();
      setUploadProgress(null);

      const matchedCount = allMatched.length;
      const unmatchedCount = allUnmatched.length;

      if (matchedCount > 0 && unmatchedCount === 0) {
        showAlert({
          title: 'Distribusi Sukses!',
          text: `Semua ${matchedCount} slip gaji berhasil diunggah & otomatis terdistribusi ke akun masing-masing karyawan!`,
          icon: 'success'
        });
      } else if (matchedCount > 0 && unmatchedCount > 0) {
        showAlert({
          title: 'Sebagian Terdistribusi',
          text: `${matchedCount} slip gaji berhasil dikirim. Terdapat ${unmatchedCount} file tidak teridentifikasi (${allUnmatched.slice(0, 3).join(', ')}...).`,
          icon: 'warning'
        });
      } else {
        showAlert({
          title: 'Periksa Nama File',
          text: `Gagal mencocokkan ${unmatchedCount} file slip gaji dengan data NIK atau nama karyawan di sistem.`,
          icon: 'error'
        });
      }

      router.reload({ preserveScroll: true });
    } catch (err) {
      console.error('Batch upload error:', err);
      setIsSubmittingBulk(false);
      setUploadProgress(null);
    }
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
              <select
                value={String(selectedMonth)}
                onChange={(e) => setSelectedMonth(e.target.value === 'all' ? 'all' : parseInt(e.target.value))}
                className="w-full bg-slate-50 border border-slate-200 text-xs font-bold text-slate-900 rounded-xl px-3.5 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer"
              >
                <option value="all">Semua Bulan (Januari - Desember)</option>
                {MONTHS.map((m) => (
                  <option key={m.value} value={String(m.value)}>
                    {m.name}
                  </option>
                ))}
              </select>
            </div>

            {/* Year Selector */}
            <div>
              <label className="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">
                Tahun Periode
              </label>
              <select
                value={selectedYear}
                onChange={(e) => setSelectedYear(parseInt(e.target.value))}
                className="w-full bg-slate-50 border border-slate-200 text-xs font-bold text-slate-900 rounded-xl px-3.5 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer"
              >
                {[currentYear - 2, currentYear - 1, currentYear, currentYear + 1, currentYear + 2].map((yr) => (
                  <option key={yr} value={yr}>
                    Tahun {yr}
                  </option>
                ))}
              </select>
            </div>

            {/* Department Filter */}
            <div>
              <label className="block text-[10px] font-extrabold text-slate-400 uppercase tracking-wider mb-1">
                Departemen
              </label>
              <select
                value={selectedDept || 'all'}
                onChange={(e) => setSelectedDept(e.target.value === 'all' ? '' : e.target.value)}
                className="w-full bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-900 rounded-xl px-3.5 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 cursor-pointer"
              >
                <option value="all">Semua Departemen</option>
                {departments.map((dept) => (
                  <option key={dept.id} value={String(dept.id)}>
                    {dept.name}
                  </option>
                ))}
              </select>
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
                  className="w-full pl-8 pr-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500/20 outline-none"
                />
              </div>
            </div>

            {/* Submit Filter Button */}
            <div className="flex items-end">
              <Button
                type="submit"
                variant="emerald"
                className="w-full rounded-xl text-xs font-bold space-x-1 py-2.5"
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
              <span>
                Daftar Slip Gaji {selectedMonth === 'all' || !selectedMonth ? `Tahun ${selectedYear}` : `Periode ${currentMonthObj?.name} ${selectedYear}`} ({payslips.length} Terdistribusi)
              </span>
            </h2>
          </div>

          <div className="rounded-3xl bg-white border border-slate-200/80 shadow-xs overflow-hidden">
            {rawPayslips.length > 0 ? (
              <div>
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
                    {paginatedPayslips.map((p) => {
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
                            <span className="bg-slate-100 text-slate-800 px-2 py-1 rounded-md border border-slate-200 font-bold inline-block">
                              {p.month_name} {p.year}
                            </span>
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
                                <Badge variant="success" className="text-[10px] font-bold space-x-1 bg-emerald-100 text-emerald-800 border-emerald-300">
                                  <CheckCircle2 size={11} className="text-emerald-700" />
                                  <span>Sudah Dibuka</span>
                                </Badge>
                                {p.viewed_at && (
                                  <span className="text-[10px] text-slate-500 font-semibold block">
                                    {new Date(p.viewed_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })} WIB
                                  </span>
                                )}
                              </div>
                            ) : (
                              <Badge variant="secondary" className="text-[10px] font-semibold text-slate-500 bg-slate-100 border border-slate-200">
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

              {/* Instant Zero-Lag Pagination */}
              <InstantPagination
                currentPage={currentPage}
                totalItems={rawPayslips.length}
                pageSize={pageSize}
                onPageChange={setCurrentPage}
                onPageSizeChange={(newSize) => {
                  setPageSize(newSize);
                  setCurrentPage(1);
                }}
                itemName="slip gaji"
              />
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
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
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
              className="relative z-10 w-full max-w-xl rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl overflow-hidden my-6 max-h-[92vh] flex flex-col transform-gpu"
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
                      <select
                        value={bulkForm.data.month}
                        onChange={(e) => bulkForm.setData('month', parseInt(e.target.value))}
                        className="w-full bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs rounded-xl px-3.5 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer"
                      >
                        {MONTHS.map((m) => (
                          <option key={m.value} value={m.value}>
                            {m.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Tahun Penggajian <span className="text-rose-600">*</span>
                      </label>
                      <select
                        value={bulkForm.data.year}
                        onChange={(e) => bulkForm.setData('year', parseInt(e.target.value))}
                        className="w-full bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs rounded-xl px-3.5 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer"
                      >
                        {[currentYear - 2, currentYear - 1, currentYear, currentYear + 1, currentYear + 2].map((yr) => (
                          <option key={yr} value={yr}>
                            Tahun {yr}
                          </option>
                        ))}
                      </select>
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
                    <div className="flex items-center justify-between">
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Pilihan 1: Pilih Banyak File PDF Sekaligus
                      </label>
                      {selectedFiles.length > 0 && (
                        <button
                          type="button"
                          onClick={() => {
                            setSelectedFiles([]);
                            bulkForm.setData('files', []);
                          }}
                          className="text-[11px] text-rose-600 hover:text-rose-700 font-bold"
                        >
                          Hapus Semua ({selectedFiles.length})
                        </button>
                      )}
                    </div>

                    <div className={`p-4 rounded-2xl border-2 border-dashed transition-all text-center relative ${
                      selectedFiles.length > 0
                        ? 'border-emerald-500 bg-emerald-50/40'
                        : 'border-slate-300 bg-slate-50 hover:bg-slate-100/80'
                    }`}>
                      <input
                        type="file"
                        multiple
                        accept=".pdf,application/pdf"
                        onChange={(e) => {
                          if (e.target.files && e.target.files.length > 0) {
                            handlePdfFilesChange(e.target.files);
                            e.target.value = ''; // Reset input so same file can be re-selected if removed
                          }
                        }}
                        className="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                      />
                      <FileText size={32} className={`mx-auto mb-2 ${
                        selectedFiles.length > 0 ? 'text-emerald-600' : 'text-slate-400'
                      }`} />
                      <p className="text-xs font-bold text-slate-800">
                        {selectedFiles.length > 0
                          ? `✅ ${selectedFiles.length} file PDF siap dikirim (Klik untuk tambah lagi)`
                          : 'Klik atau Tarik (Drag & Drop) banyak file PDF slip gaji di sini'}
                      </p>
                      <p className="text-[10px] text-slate-400 mt-0.5">
                        Bisa pilih sekaligus banyak file PDF (Ctrl + A / Blok Semua File)
                      </p>
                    </div>

                    {/* LIVE LIST OF SELECTED PDF FILES */}
                    {selectedFiles.length > 0 && (
                      <div className="mt-3 p-3 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                        <div className="flex items-center justify-between pb-1.5 border-b border-slate-200">
                          <span className="text-[11px] font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <FileText size={14} className="text-emerald-600" />
                            Daftar File PDF Terpilih ({selectedFiles.length})
                          </span>
                          <span className="text-[10px] font-bold text-slate-500">
                            Total: {(selectedFiles.reduce((acc, f) => acc + f.size, 0) / 1024 / 1024).toFixed(2)} MB
                          </span>
                        </div>

                        <div className="max-h-60 overflow-y-auto space-y-2 pr-1 divide-y divide-slate-100">
                          {selectedFiles.map((item, idx) => {
                            const matchedEmp = item.userId ? employees.find(e => String(e.id) === String(item.userId)) : null;
                            return (
                              <div key={item.id || `${item.file.name}-${idx}`} className="pt-2 first:pt-0 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                <div className="min-w-0 flex-1">
                                  <div className="flex items-center space-x-1.5">
                                    <span className="text-[10px] font-mono font-black text-slate-400">#{idx + 1}</span>
                                    <span className="font-bold text-slate-900 truncate block max-w-[200px] sm:max-w-[260px]" title={item.file.name}>
                                      {item.file.name}
                                    </span>
                                    <span className="text-[10px] text-slate-400 font-medium shrink-0">
                                      ({(item.file.size / 1024).toFixed(1)} KB)
                                    </span>
                                  </div>

                                  <div className="flex items-center gap-1.5 mt-1">
                                    <span className="text-[10px] font-extrabold text-slate-400 uppercase">Tujuan:</span>
                                    <select
                                      value={item.userId || ''}
                                      onChange={(e) => handleFileUserChange(idx, e.target.value)}
                                      className={`text-[11px] font-bold rounded-lg px-2 py-1 border transition-colors outline-none max-w-[260px] truncate ${
                                        item.userId
                                          ? 'bg-emerald-50 text-emerald-900 border-emerald-300 focus:ring-1 focus:ring-emerald-500'
                                          : 'bg-amber-50 text-amber-900 border-amber-300 focus:ring-1 focus:ring-amber-500'
                                      }`}
                                    >
                                      <option value="">-- Pilih Karyawan Manual --</option>
                                      {employees.map((emp) => (
                                        <option key={emp.id} value={String(emp.id)}>
                                          {emp.name} ({emp.nik || 'No NIK'})
                                        </option>
                                      ))}
                                    </select>
                                    {matchedEmp && (
                                      <span className="text-[10px] font-bold text-emerald-600 hidden md:inline">
                                        ✓ Siap
                                      </span>
                                    )}
                                  </div>
                                </div>

                                <button
                                  type="button"
                                  onClick={() => handleRemoveFile(idx)}
                                  className="self-end sm:self-center p-1.5 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors shrink-0"
                                  title="Hapus file ini"
                                >
                                  <Trash2 size={15} />
                                </button>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Or Single ZIP File */}
                  <div className="space-y-2">
                    <div className="flex items-center justify-between">
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Pilihan 2: Atau Upload 1 File ZIP (Isi PDF)
                      </label>
                      {selectedZip && (
                        <button
                          type="button"
                          onClick={handleRemoveZip}
                          className="text-[11px] text-rose-600 hover:text-rose-700 font-bold"
                        >
                          Hapus ZIP
                        </button>
                      )}
                    </div>

                    <div className={`p-4 rounded-2xl border-2 border-dashed transition-all text-center relative ${
                      selectedZip
                        ? 'border-emerald-500 bg-emerald-50/40'
                        : 'border-slate-300 bg-slate-50 hover:bg-slate-100/80'
                    }`}>
                      <input
                        type="file"
                        accept=".zip,application/zip,application/x-zip-compressed,application/octet-stream,multipart/x-zip"
                        onChange={(e) => {
                          const file = e.target.files[0];
                          if (file) {
                            handleZipChange(file);
                            e.target.value = '';
                          }
                        }}
                        className="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                      />
                      <Archive size={32} className={`mx-auto mb-2 ${
                        selectedZip ? 'text-emerald-600' : 'text-slate-400'
                      }`} />
                      <p className="text-xs font-bold text-slate-800">
                        {selectedZip
                          ? `✅ File ZIP terpilih: ${selectedZip.name}`
                          : 'Klik atau Tarik file .ZIP yang berisi slip gaji PDF'}
                      </p>
                      <p className="text-[10px] text-slate-400 mt-0.5">
                        {selectedZip
                          ? `${(selectedZip.size / 1024 / 1024).toFixed(2)} MB • Klik lagi jika ingin mengganti`
                          : 'Sistem akan otomatis mengekstrak & mencocokkan PDF di dalam ZIP ke karyawan'}
                      </p>
                    </div>

                    {/* ZIP PREVIEW CARD */}
                    {selectedZip && (
                      <div className="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200 flex items-center justify-between">
                        <div className="flex items-center space-x-2.5 min-w-0">
                          <div className="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold shrink-0">
                            <Archive size={16} />
                          </div>
                          <div className="min-w-0">
                            <span className="text-xs font-bold text-slate-900 block truncate max-w-[240px]" title={selectedZip.name}>
                              {selectedZip.name}
                            </span>
                            <span className="text-[10px] text-emerald-800 font-bold">
                              {(selectedZip.size / 1024 / 1024).toFixed(2)} MB • Siap diekstrak otomatis
                            </span>
                          </div>
                        </div>

                        <button
                          type="button"
                          onClick={handleRemoveZip}
                          className="p-1.5 rounded-xl text-rose-600 hover:bg-rose-100 transition-colors"
                          title="Batal pilih ZIP"
                        >
                          <Trash2 size={15} />
                        </button>
                      </div>
                    )}
                  </div>

                  {/* Real-time Chunked Upload Progress Bar */}
                  {isSubmittingBulk && uploadProgress && (
                    <div className="p-4 rounded-2xl bg-emerald-50/90 border border-emerald-200 space-y-2.5 animate-in fade-in duration-200">
                      <div className="flex items-center justify-between text-xs font-extrabold text-emerald-950">
                        <div className="flex items-center space-x-2 min-w-0">
                          <span className="relative flex h-2.5 w-2.5">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-600"></span>
                          </span>
                          <span className="truncate">
                            Mengunggah Batch {uploadProgress.currentBatch} dari {uploadProgress.totalBatches} ({uploadProgress.current}/{uploadProgress.total} Slip Gaji)...
                          </span>
                        </div>
                        <span className="font-mono text-emerald-800 font-black text-sm shrink-0 ml-2">
                          {uploadProgress.percent}%
                        </span>
                      </div>
                      <div className="w-full bg-emerald-200/60 h-2.5 rounded-full overflow-hidden">
                        <div
                          className="bg-emerald-600 h-full rounded-full transition-all duration-300 ease-out"
                          style={{ width: `${uploadProgress.percent}%` }}
                        />
                      </div>
                      <p className="text-[11px] text-emerald-700 font-medium">
                        🛡️ Sistem otomatis membagi unggahan per 5 file agar anti-gagal, mulus, dan bebas error 413 (Payload Too Large).
                      </p>
                    </div>
                  )}
                </div>

                {/* Modal Footer */}
                <div className="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2.5 shrink-0">
                  <Button
                    type="button"
                    variant="secondary"
                    className="rounded-xl px-4"
                    onClick={() => setIsBulkOpen(false)}
                    disabled={isSubmittingBulk}
                  >
                    Batal
                  </Button>
                  <Button
                    type="submit"
                    variant="emerald"
                    className="rounded-xl px-5 font-extrabold space-x-1.5 shadow-md shadow-emerald-600/20"
                    disabled={isSubmittingBulk || (selectedFiles.length === 0 && !selectedZip)}
                  >
                    <Send size={15} />
                    <span>{isSubmittingBulk ? "Memproses & Mengirim..." : `Kirim & Distribusikan (${selectedFiles.length > 0 ? `${selectedFiles.length} PDF` : selectedZip ? '1 ZIP' : 'Otomatis'})`}</span>
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
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
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
              className="relative z-10 w-full max-w-lg rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl overflow-hidden my-6 max-h-[92vh] flex flex-col transform-gpu"
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
                    <select
                      value={singleForm.data.user_id ? String(singleForm.data.user_id) : ''}
                      onChange={(e) => singleForm.setData('user_id', e.target.value)}
                      className="w-full bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs rounded-xl px-3.5 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer"
                    >
                      <option value="">-- Pilih Karyawan --</option>
                      {employees.map((emp) => (
                        <option key={emp.id} value={String(emp.id)}>
                          {emp.name} ({emp.nik || 'No NIK'}) - {emp.department?.name || 'General'}
                        </option>
                      ))}
                    </select>
                  </div>

                  {/* Period */}
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Bulan <span className="text-rose-600">*</span>
                      </label>
                      <select
                        value={singleForm.data.month}
                        onChange={(e) => singleForm.setData('month', parseInt(e.target.value))}
                        className="w-full bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs rounded-xl px-3.5 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer"
                      >
                        {MONTHS.map((m) => (
                          <option key={m.value} value={m.value}>
                            {m.name}
                          </option>
                        ))}
                      </select>
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Tahun <span className="text-rose-600">*</span>
                      </label>
                      <select
                        value={singleForm.data.year}
                        onChange={(e) => singleForm.setData('year', parseInt(e.target.value))}
                        className="w-full bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs rounded-xl px-3.5 py-2.5 outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 cursor-pointer"
                      >
                        {[currentYear - 2, currentYear - 1, currentYear, currentYear + 1, currentYear + 2].map((yr) => (
                          <option key={yr} value={yr}>
                            Tahun {yr}
                          </option>
                        ))}
                      </select>
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
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-2 sm:p-4 overflow-hidden">
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
              className="relative z-10 w-full max-w-4xl h-[92vh] rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl overflow-hidden flex flex-col transform-gpu"
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
