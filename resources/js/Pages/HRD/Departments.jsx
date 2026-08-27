import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
  Building,
  Plus,
  Edit2,
  Trash2,
  Users,
  ShieldCheck,
  Search,
  CheckCircle2,
  X,
  Sparkles,
  UserCheck,
  Sliders,
  Check,
  HelpCircle,
  Briefcase,
  Layers,
  ArrowRight,
  AlertCircle
} from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
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
  hidden: { opacity: 0, y: 10 },
  show: { opacity: 1, y: 0, transition: { duration: 0.2, ease: 'easeOut' } }
};

export default function DepartmentsIndex({ departments = [], employees = [], stats = {}, filters = {} }) {
  const [search, setSearch] = useState(filters.search || '');
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [editingDept, setEditingDept] = useState(null);

  // Instant Client-Side Pagination States
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(9);

  const rawDepartments = React.useMemo(() => {
    return Array.isArray(departments) ? departments : (departments?.data || []);
  }, [departments]);

  const paginatedDepartments = React.useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return rawDepartments.slice(start, start + pageSize);
  }, [rawDepartments, currentPage, pageSize]);

  React.useEffect(() => {
    setCurrentPage(1);
  }, [rawDepartments.length]);

  const createForm = useForm({
    name: '',
    code: '',
    manager_id: '',
    approver_1_id: '',
    approver_2_id: '',
    approval_type: '3_tier',
    description: '',
  });

  const editForm = useForm({
    name: '',
    code: '',
    manager_id: '',
    approver_1_id: '',
    approver_2_id: '',
    approval_type: '3_tier',
    description: '',
  });

  const handleSearch = (e) => {
    e.preventDefault();
    router.get(route('hrd.departments'), { search }, { preserveState: true, replace: true });
  };

  const handleOpenCreate = () => {
    createForm.reset();
    createForm.clearErrors();
    setIsCreateOpen(true);
  };

  const handleOpenEdit = (dept) => {
    setEditingDept(dept);
    editForm.clearErrors();
    editForm.setData({
      name: dept.name || '',
      code: dept.code || '',
      manager_id: dept.manager_id ? String(dept.manager_id) : '',
      approver_1_id: dept.approver_1_id ? String(dept.approver_1_id) : '',
      approver_2_id: dept.approver_2_id ? String(dept.approver_2_id) : (dept.manager_id ? String(dept.manager_id) : ''),
      approval_type: dept.approval_type || '3_tier',
      description: dept.description || '',
    });
  };

  const handleCreateSubmit = (e) => {
    e.preventDefault();
    createForm.post(route('hrd.departments.store'), {
      preserveScroll: true,
      onSuccess: () => {
        setIsCreateOpen(false);
        createForm.reset();
        showToast('Departemen baru berhasil ditambahkan!', 'success');
      },
      onError: (errs) => {
        showAlert({
          title: 'Gagal Menambah Departemen',
          text: Object.values(errs)[0] || 'Periksa kembali data yang dimasukkan.',
          icon: 'error'
        });
      }
    });
  };

  const handleEditSubmit = (e) => {
    e.preventDefault();
    if (!editingDept) return;

    editForm.post(route('hrd.departments.update', editingDept.id), {
      preserveScroll: true,
      onSuccess: () => {
        setEditingDept(null);
        editForm.reset();
        showToast('Pengaturan departemen & alur approval berhasil disimpan!', 'success');
      },
      onError: (errs) => {
        showAlert({
          title: 'Gagal Memperbarui Departemen',
          text: Object.values(errs)[0] || 'Periksa kembali data yang dimasukkan.',
          icon: 'error'
        });
      }
    });
  };

  const handleDelete = async (dept) => {
    if (dept.employees_count > 0) {
      showAlert({
        title: 'Tidak Dapat Dihapus',
        text: `Departemen '${dept.name}' masih memiliki ${dept.employees_count} karyawan aktif. Pindahkan karyawan ke departemen lain terlebih dahulu.`,
        icon: 'warning'
      });
      return;
    }

    const confirmed = await showConfirm({
      title: `Hapus Departemen '${dept.name}'?`,
      text: 'Tindakan ini tidak dapat dibatalkan.',
      icon: 'warning',
      confirmText: 'Ya, Hapus',
      cancelText: 'Batal'
    });

    if (confirmed) {
      router.delete(route('hrd.departments.destroy', dept.id), {
        onSuccess: () => showToast(`Departemen '${dept.name}' berhasil dihapus.`, 'success'),
      });
    }
  };

  return (
    <AuthenticatedLayout title="Setup Departemen & Alur Approval">
      <Head title="Setup Departemen & Alur Approval - HRD SGIN" />

      <motion.div
        variants={containerVariants}
        initial="hidden"
        animate="show"
        className="space-y-6"
      >
        {/* ========================================================================= */}
        {/* 1. HERO HEADER BANNER                                                     */}
        {/* ========================================================================= */}
        <motion.div
          variants={itemVariants}
          className="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#0FA172] to-[#1CB67C] text-white shadow-lg shadow-emerald-600/20 relative overflow-hidden"
        >
          <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none" />

          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/20 border border-white/30 text-white text-xs font-bold uppercase tracking-wider mb-3 backdrop-blur-md">
                <Building size={14} className="text-emerald-100" />
                <span>HRD Department Control</span>
              </div>
              <h1 className="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Setup Departemen & Alur Approval
              </h1>
              <p className="text-sm text-emerald-50 mt-1 max-w-3xl font-medium leading-relaxed">
                Konfigurasikan struktur divisi, kepala departemen, dan alur persetujuan cuti berjenjang (Approval 1 & 2) secara fleksibel per departemen.
              </p>
            </div>

            <div className="flex flex-wrap gap-2.5 shrink-0">
              <Button
                onClick={handleOpenCreate}
                size="lg"
                className="bg-white text-emerald-800 hover:bg-emerald-50 font-black shadow-md"
              >
                <Plus size={18} className="mr-1.5 text-emerald-700" />
                <span>Tambah Departemen Baru</span>
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
                <Building size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Total Divisi</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.total_departments || departments.length}</h3>
              </div>
            </div>
          </Card>

          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <Users size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Total Karyawan</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.total_employees || 0}</h3>
              </div>
            </div>
          </Card>

          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                <UserCheck size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Kepala Divisi</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.with_manager || 0}</h3>
              </div>
            </div>
          </Card>

          <Card className="p-4 sm:p-5 border-slate-200">
            <div className="flex items-center space-x-3">
              <div className="w-11 h-11 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                <ShieldCheck size={22} />
              </div>
              <div>
                <p className="text-[10px] sm:text-xs font-extrabold text-slate-400 uppercase">Alur 3 Tingkat</p>
                <h3 className="text-xl sm:text-2xl font-black text-slate-900">{stats.multi_tier_count || 0}</h3>
              </div>
            </div>
          </Card>
        </motion.div>

        {/* ========================================================================= */}
        {/* 3. SEARCH & ACTIONS BAR                                                   */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="flex flex-col sm:flex-row items-center justify-between gap-3">
          <form onSubmit={handleSearch} className="relative w-full sm:w-80">
            <Search size={18} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari nama atau kode departemen..."
              className="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-white border border-slate-200 text-xs font-semibold focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 outline-none shadow-xs"
            />
          </form>

          <div className="flex items-center space-x-2 w-full sm:w-auto justify-end">
            <Link href={route('hrd.employees')}>
              <Button variant="outline" size="sm" className="rounded-2xl space-x-1">
                <Users size={14} />
                <span>Daftar Karyawan</span>
              </Button>
            </Link>
          </div>
        </motion.div>

        {/* ========================================================================= */}
        {/* 4. DEPARTMENTS GRID (RESPONSIVE FOR MOBILE & DESKTOP)                     */}
        {/* ========================================================================= */}
        <motion.div variants={itemVariants} className="space-y-5">
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            {rawDepartments.length > 0 ? (
              paginatedDepartments.map((dept) => {
                const approvalBadge = dept.approval_type === '3_tier'
                  ? { label: '3 Tingkat (Supervisor -> Manager -> HRD)', variant: 'success' }
                  : dept.approval_type === '2_tier'
                  ? { label: '2 Tingkat (Manager -> HRD)', variant: 'purple' }
                  : dept.approval_type === '1_tier'
                  ? { label: '1 Tingkat (HRD Langsung)', variant: 'warning' }
                  : { label: 'Custom Per Karyawan', variant: 'outline' };

                return (
                  <Card key={dept.id} className="border-slate-200 shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                    <div>
                      {/* Header Card */}
                      <CardHeader className="p-5 pb-3 flex flex-row items-start justify-between space-y-0">
                        <div className="flex items-center space-x-3 min-w-0">
                          <div className="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-sm shrink-0 uppercase shadow-2xs">
                            {dept.code}
                          </div>
                          <div className="min-w-0">
                            <CardTitle className="text-base truncate">{dept.name}</CardTitle>
                            <p className="text-[11px] text-slate-500 font-semibold mt-0.5 flex items-center space-x-1">
                              <Users size={12} className="text-slate-400" />
                              <span>{dept.employees_count || 0} Karyawan Terdaftar</span>
                            </p>
                          </div>
                        </div>

                        <div className="flex items-center space-x-1 shrink-0">
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 rounded-xl text-slate-500 hover:text-emerald-700 hover:bg-emerald-50"
                            onClick={() => handleOpenEdit(dept)}
                            title="Edit Pengaturan Departemen"
                          >
                            <Edit2 size={15} />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-8 w-8 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50"
                            onClick={() => handleDelete(dept)}
                            title="Hapus Departemen"
                          >
                            <Trash2 size={15} />
                          </Button>
                        </div>
                      </CardHeader>

                      <CardContent className="p-5 pt-0 space-y-3">
                        {/* Description */}
                        {dept.description && (
                          <p className="text-xs text-slate-600 leading-relaxed line-clamp-2">
                            {dept.description}
                          </p>
                        )}

                        {/* Approval Flow Badge */}
                        <div className="pt-1">
                          <span className="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider block mb-1">
                            Alur Persetujuan (Approval Flow):
                          </span>
                          <Badge variant={approvalBadge.variant} className="text-[10px] font-extrabold">
                            {approvalBadge.label}
                          </Badge>
                        </div>

                        {/* Approver Details Grid */}
                        <div className="space-y-1.5 p-3 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs">
                          <div className="flex items-center justify-between">
                            <span className="text-slate-500 font-medium flex items-center space-x-1">
                              <span className="w-1.5 h-1.5 rounded-full bg-blue-600" />
                              <span>Atasan 1 (Supervisor):</span>
                            </span>
                            <span className="font-bold text-slate-900 truncate max-w-[140px]">
                              {dept.approver1?.name || <span className="text-slate-400 italic">Belum Diatur</span>}
                            </span>
                          </div>

                          <div className="flex items-center justify-between">
                            <span className="text-slate-500 font-medium flex items-center space-x-1">
                              <span className="w-1.5 h-1.5 rounded-full bg-purple-600" />
                              <span>Atasan 2 (Manager):</span>
                            </span>
                            <span className="font-bold text-slate-900 truncate max-w-[140px]">
                              {dept.approver2?.name || dept.manager?.name || <span className="text-slate-400 italic">Belum Diatur</span>}
                            </span>
                          </div>

                          <div className="flex items-center justify-between pt-1 border-t border-slate-200/60">
                            <span className="text-slate-500 font-medium flex items-center space-x-1">
                              <span className="w-1.5 h-1.5 rounded-full bg-amber-600" />
                              <span>Persetujuan Akhir:</span>
                            </span>
                            <span className="font-bold text-amber-900">HRD / PGA Admin</span>
                          </div>
                        </div>
                      </CardContent>
                    </div>

                    <CardFooter className="p-5 pt-0 border-t border-slate-100 flex items-center justify-between text-xs">
                      <span className="text-slate-400 font-medium">Kode: <strong className="text-slate-800">{dept.code}</strong></span>
                      <Button
                        variant="emerald"
                        size="sm"
                        className="rounded-xl space-x-1"
                        onClick={() => handleOpenEdit(dept)}
                      >
                        <Sliders size={13} />
                        <span>Atur Alur Approval</span>
                      </Button>
                    </CardFooter>
                  </Card>
                );
              })
            ) : (
              <div className="col-span-full py-16 text-center text-slate-400 space-y-2">
                <Building size={42} className="mx-auto opacity-30 text-slate-400" />
                <p className="text-xs font-semibold">Tidak ada departemen yang cocok dengan pencarian.</p>
              </div>
            )}
          </div>

          {/* Instant Zero-Lag Pagination */}
          {rawDepartments.length > 0 && (
            <div className="rounded-2xl overflow-hidden border border-slate-200/80 shadow-xs">
              <InstantPagination
                currentPage={currentPage}
                totalItems={rawDepartments.length}
                pageSize={pageSize}
                pageSizeOptions={[6, 9, 15, 30]}
                onPageChange={setCurrentPage}
                onPageSizeChange={(newSize) => {
                  setPageSize(newSize);
                  setCurrentPage(1);
                }}
                itemName="departemen"
              />
            </div>
          )}
        </motion.div>
      </motion.div>

      {/* ========================================================================= */}
      {/* MODAL: CREATE / EDIT DEPARTMENT & APPROVAL SETTINGS                       */}
      {/* ========================================================================= */}
      <AnimatePresence>
        {(isCreateOpen || editingDept) && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-slate-950/60"
              onClick={() => {
                setIsCreateOpen(false);
                setEditingDept(null);
              }}
            />

            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              className="relative z-10 w-full max-w-lg rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl overflow-hidden my-6 max-h-[90vh] flex flex-col transform-gpu"
            >
              {/* Modal Header */}
              <div className="p-5 sm:p-6 pb-4 border-b border-slate-100 flex items-center justify-between shrink-0 bg-white">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <Building size={20} />
                  </div>
                  <div>
                    <h3 className="text-base font-black text-slate-900 leading-tight">
                      {editingDept ? `Pengaturan Departemen: ${editingDept.name}` : 'Tambah Departemen Baru'}
                    </h3>
                    <p className="text-xs text-slate-500 font-medium mt-0.5">
                      Tentukan nama divisi dan alur persetujuan default bagi karyawannya.
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    setIsCreateOpen(false);
                    setEditingDept(null);
                  }}
                  className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800 transition-colors"
                >
                  <X size={18} />
                </button>
              </div>

              {/* Modal Form Body */}
              <form
                onSubmit={editingDept ? handleEditSubmit : handleCreateSubmit}
                className="flex flex-col flex-1 overflow-hidden"
              >
                <div className="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1">
                  {/* Name & Code */}
                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div className="sm:col-span-2">
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Nama Departemen <span className="text-rose-600">*</span>
                      </label>
                      <input
                        type="text"
                        value={editingDept ? editForm.data.name : createForm.data.name}
                        onChange={(e) => {
                          if (editingDept) editForm.setData('name', e.target.value);
                          else createForm.setData('name', e.target.value);
                        }}
                        placeholder="contoh: Information Technology"
                        className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                        required
                      />
                      {(editingDept ? editForm.errors.name : createForm.errors.name) && (
                        <p className="text-[11px] text-rose-600 font-bold mt-1">
                          {editingDept ? editForm.errors.name : createForm.errors.name}
                        </p>
                      )}
                    </div>

                    <div>
                      <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Kode <span className="text-rose-600">*</span>
                      </label>
                      <input
                        type="text"
                        value={editingDept ? editForm.data.code : createForm.data.code}
                        onChange={(e) => {
                          const val = e.target.value.toUpperCase();
                          if (editingDept) editForm.setData('code', val);
                          else createForm.setData('code', val);
                        }}
                        placeholder="IT / HRD"
                        className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs uppercase focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                        required
                      />
                      {(editingDept ? editForm.errors.code : createForm.errors.code) && (
                        <p className="text-[11px] text-rose-600 font-bold mt-1">
                          {editingDept ? editForm.errors.code : createForm.errors.code}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Approval Type Radio Buttons */}
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                      Pilih Skema Alur Persetujuan (Approval Flow) <span className="text-rose-600">*</span>
                    </label>

                    <div className="space-y-2">
                      {/* 3 Tier */}
                      <div
                        onClick={() => {
                          if (editingDept) editForm.setData('approval_type', '3_tier');
                          else createForm.setData('approval_type', '3_tier');
                        }}
                        className={`p-3.5 rounded-2xl border cursor-pointer transition-all ${
                          (editingDept ? editForm.data.approval_type : createForm.data.approval_type) === '3_tier'
                            ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/20'
                            : 'bg-slate-50 border-slate-200 hover:bg-slate-100'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-extrabold text-xs text-slate-900">3 Tingkat (Supervisor &rarr; Manager &rarr; HRD)</span>
                          {(editingDept ? editForm.data.approval_type : createForm.data.approval_type) === '3_tier' && (
                            <div className="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center shrink-0">
                              <Check size={12} />
                            </div>
                          )}
                        </div>
                        <p className="text-[11px] text-slate-500 mt-1 leading-relaxed">
                          Permohonan melewati persetujuan Supervisor (Atasan 1), kemudian Manager (Atasan 2), lalu HRD.
                        </p>
                      </div>

                      {/* 2 Tier */}
                      <div
                        onClick={() => {
                          if (editingDept) editForm.setData('approval_type', '2_tier');
                          else createForm.setData('approval_type', '2_tier');
                        }}
                        className={`p-3.5 rounded-2xl border cursor-pointer transition-all ${
                          (editingDept ? editForm.data.approval_type : createForm.data.approval_type) === '2_tier'
                            ? 'bg-purple-50 border-purple-500 ring-2 ring-purple-500/20'
                            : 'bg-slate-50 border-slate-200 hover:bg-slate-100'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-extrabold text-xs text-slate-900">2 Tingkat (Manager &rarr; HRD)</span>
                          {(editingDept ? editForm.data.approval_type : createForm.data.approval_type) === '2_tier' && (
                            <div className="w-5 h-5 rounded-full bg-purple-600 text-white flex items-center justify-center shrink-0">
                              <Check size={12} />
                            </div>
                          )}
                        </div>
                        <p className="text-[11px] text-slate-500 mt-1 leading-relaxed">
                          Permohonan langsung ke Manager (Atasan 2) tanpa melalui Supervisor, lalu persetujuan akhir HRD.
                        </p>
                      </div>

                      {/* 1 Tier */}
                      <div
                        onClick={() => {
                          if (editingDept) editForm.setData('approval_type', '1_tier');
                          else createForm.setData('approval_type', '1_tier');
                        }}
                        className={`p-3.5 rounded-2xl border cursor-pointer transition-all ${
                          (editingDept ? editForm.data.approval_type : createForm.data.approval_type) === '1_tier'
                            ? 'bg-amber-50 border-amber-500 ring-2 ring-amber-500/20'
                            : 'bg-slate-50 border-slate-200 hover:bg-slate-100'
                        }`}
                      >
                        <div className="flex items-center justify-between">
                          <span className="font-extrabold text-xs text-slate-900">1 Tingkat (Langsung HRD)</span>
                          {(editingDept ? editForm.data.approval_type : createForm.data.approval_type) === '1_tier' && (
                            <div className="w-5 h-5 rounded-full bg-amber-600 text-white flex items-center justify-center shrink-0">
                              <Check size={12} />
                            </div>
                          )}
                        </div>
                        <p className="text-[11px] text-slate-500 mt-1 leading-relaxed">
                          Permohonan langsung masuk ke antrean persetujuan HRD / PGA Admin.
                        </p>
                      </div>
                    </div>
                  </div>

                  {/* Default Approver Selectors */}
                  <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div className="flex items-center space-x-2">
                      <ShieldCheck size={16} className="text-emerald-700" />
                      <span className="text-xs font-extrabold text-slate-900">Pilih Atasan / Approver Default Departemen</span>
                    </div>

                    {/* Approver 1 (Supervisor) */}
                    {(editingDept ? editForm.data.approval_type : createForm.data.approval_type) === '3_tier' && (
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1">
                          Atasan 1 (Supervisor Departemen)
                        </label>
                        <Select
                          value={editingDept ? (editForm.data.approver_1_id ? String(editForm.data.approver_1_id) : 'none') : (createForm.data.approver_1_id ? String(createForm.data.approver_1_id) : 'none')}
                          onValueChange={(val) => {
                            const finalVal = val === 'none' ? '' : val;
                            if (editingDept) editForm.setData('approver_1_id', finalVal);
                            else createForm.setData('approver_1_id', finalVal);
                          }}
                        >
                          <SelectTrigger className="w-full bg-white border-slate-300 text-xs font-semibold text-slate-900 rounded-xl">
                            <SelectValue placeholder="-- Pilih Supervisor / Atasan 1 --" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="none">-- Tanpa Atasan 1 (Kosong) --</SelectItem>
                            {employees.map((emp) => (
                              <SelectItem key={emp.id} value={String(emp.id)}>
                                {emp.name} ({emp.nik}) - {emp.role.toUpperCase()}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    )}

                    {/* Approver 2 (Manager / Kepala Departemen) */}
                    {(editingDept ? editForm.data.approval_type : createForm.data.approval_type) !== '1_tier' && (
                      <div>
                        <label className="block text-xs font-bold text-slate-700 mb-1">
                          Atasan 2 / Manager Departemen
                        </label>
                        <Select
                          value={
                            editingDept
                              ? (editForm.data.approver_2_id || editForm.data.manager_id ? String(editForm.data.approver_2_id || editForm.data.manager_id) : 'none')
                              : (createForm.data.approver_2_id || createForm.data.manager_id ? String(createForm.data.approver_2_id || createForm.data.manager_id) : 'none')
                          }
                          onValueChange={(val) => {
                            const finalVal = val === 'none' ? '' : val;
                            if (editingDept) {
                              editForm.setData((prev) => ({ ...prev, approver_2_id: finalVal, manager_id: finalVal }));
                            } else {
                              createForm.setData((prev) => ({ ...prev, approver_2_id: finalVal, manager_id: finalVal }));
                            }
                          }}
                        >
                          <SelectTrigger className="w-full bg-white border-slate-300 text-xs font-semibold text-slate-900 rounded-xl">
                            <SelectValue placeholder="-- Pilih Manager Departemen --" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="none">-- Tanpa Atasan 2 (Kosong) --</SelectItem>
                            {employees.map((emp) => (
                              <SelectItem key={emp.id} value={String(emp.id)}>
                                {emp.name} ({emp.nik}) - {emp.role.toUpperCase()}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    )}
                  </div>

                  {/* Description */}
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                      Deskripsi / Catatan Departemen (Opsional)
                    </label>
                    <textarea
                      rows={2}
                      value={editingDept ? editForm.data.description : createForm.data.description}
                      onChange={(e) => {
                        if (editingDept) editForm.setData('description', e.target.value);
                        else createForm.setData('description', e.target.value);
                      }}
                      placeholder="Deskripsi singkat fungsi departemen..."
                      className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-xs font-medium text-slate-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                    />
                  </div>
                </div>

                {/* Modal Footer Buttons */}
                <div className="p-4 sm:p-5 bg-slate-50 border-t border-slate-200/80 flex items-center justify-end space-x-2.5 shrink-0">
                  <Button
                    type="button"
                    variant="secondary"
                    className="rounded-xl px-4"
                    onClick={() => {
                      setIsCreateOpen(false);
                      setEditingDept(null);
                    }}
                  >
                    Batal
                  </Button>
                  <Button
                    type="submit"
                    variant="default"
                    className="rounded-xl px-5"
                    disabled={editingDept ? editForm.processing : createForm.processing}
                  >
                    {editingDept ? (editForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan') : (createForm.processing ? 'Menambahkan...' : 'Tambah Departemen')}
                  </Button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </AuthenticatedLayout>
  );
}
