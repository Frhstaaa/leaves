import React, { useState } from 'react';
import { useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout, { UserAvatar } from '@/Layouts/AuthenticatedLayout';
import {
  Users,
  UserPlus,
  Edit3,
  Trash2,
  Building,
  ShieldCheck,
  Calendar,
  Search,
  Filter,
  X,
  Check,
  Lock,
  Mail,
  User,
  Camera,
  AlertTriangle,
  RotateCcw,
  KeyRound,
  Download,
  UploadCloud,
  FileSpreadsheet,
  MoreVertical,
  Sliders,
  FileText,
  Printer,
  Info
} from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';
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
import InstantPagination from "@/components/ui/instant-pagination";
import { showAlert, showToast, showConfirm } from '@/Utils/swal';

export default function HrdEmployees({ employees = [], departments = [], managers = [], roles = [], stats = {}, filters = {} }) {
  // Filter States
  const [searchQuery, setSearchQuery] = useState(filters.search || '');
  const [selectedDept, setSelectedDept] = useState(filters.department_id || '');
  const [selectedRole, setSelectedRole] = useState(filters.role || '');

  // Pagination States (Instant Client-Side Zero-Lag)
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);

  // Default and Dynamic Roles Mapping
  const defaultRoleOptions = [
    { value: 'employee', label: 'Karyawan (Staf)' },
    { value: 'manager', label: 'Manager / Supervisor' },
    { value: 'admin', label: 'HRD / Admin System' },
    { value: 'superadmin', label: 'Super Admin / Direksi' },
  ];

  const allRoles = React.useMemo(() => {
    const map = new Map();
    defaultRoleOptions.forEach(r => map.set(r.value.toLowerCase(), r.label));
    (roles || []).forEach(r => {
      const key = (r.name || '').toLowerCase();
      if (key && !map.has(key)) {
        map.set(key, r.display_name || (r.name.charAt(0).toUpperCase() + r.name.slice(1).replace(/[_-]/g, ' ')));
      }
    });
    return Array.from(map.entries()).map(([value, label]) => ({ value, label }));
  }, [roles]);

  // Sliced Employees for Zero-Lag Instant Pagination
  const paginatedEmployees = React.useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return (employees || []).slice(start, start + pageSize);
  }, [employees, currentPage, pageSize]);

  // Reset page to 1 if dataset length changes
  React.useEffect(() => {
    setCurrentPage(1);
  }, [employees.length]);

  const getRoleBadge = (roleName) => {
    const r = (roleName || '').toLowerCase();
    const found = allRoles.find(item => item.value === r);
    const label = found ? found.label : (roleName || 'Karyawan');
    if (r === 'superadmin') return { label, cls: 'bg-amber-100 text-amber-800 border border-amber-200' };
    if (r === 'admin') return { label, cls: 'bg-purple-100 text-purple-800 border border-purple-200' };
    if (r === 'manager') return { label, cls: 'bg-blue-100 text-blue-800 border border-blue-200' };
    return { label, cls: 'bg-emerald-100 text-emerald-800 border border-emerald-200' };
  };

  // Modal States
  const [isAddOpen, setIsAddOpen] = useState(false);
  const [isImportOpen, setIsImportOpen] = useState(false);
  const [editingEmployee, setEditingEmployee] = useState(null);
  const [quotaEmployee, setQuotaEmployee] = useState(null);
  const [deletingEmployee, setDeletingEmployee] = useState(null);

  // Approval Mode: 'inherit' (automatic from department) vs 'custom' (manual per employee)
  const [addApprovalMode, setAddApprovalMode] = useState('inherit');
  const [editApprovalMode, setEditApprovalMode] = useState('inherit');

  // Avatar Previews
  const [addAvatarPreview, setAddAvatarPreview] = useState(null);
  const [editAvatarPreview, setEditAvatarPreview] = useState(null);

  // Form for Importing Excel/CSV
  const importForm = useForm({
    file: null,
  });

  const handleImportSubmit = (e) => {
    e.preventDefault();
    if (!importForm.data.file) {
      showAlert({
        title: 'File Belum Dipilih',
        text: 'Silakan pilih file CSV atau Excel terlebih dahulu sebelum melanjutkan.',
        icon: 'warning'
      });
      return;
    }
    importForm.post(route('hrd.employees.import'), {
      forceFormData: true,
      onSuccess: () => {
        setIsImportOpen(false);
        importForm.reset();
        showToast('Proses import data karyawan berhasil dijalankan.');
      },
    });
  };

  // Form for Adding Employee
  const addForm = useForm({
    nik: '',
    name: '',
    email: '',
    password: 'password123',
    role: 'employee',
    department_id: departments[0]?.id || '',
    approver_1_id: '',
    approver_2_id: '',
    manager_id: '',
    total_quota: 12,
    avatar: null,
  });

  // Form for Editing Employee
  const editForm = useForm({
    id: null,
    nik: '',
    name: '',
    email: '',
    password: '',
    role: 'employee',
    department_id: '',
    approver_1_id: '',
    approver_2_id: '',
    manager_id: '',
    total_quota: 12,
    avatar: null,
  });

  // Form for Quick Quota Edit
  const quotaForm = useForm({
    total_quota: 12,
  });

  // Filter Actions
  const handleApplyFilter = (e) => {
    e?.preventDefault();
    router.get(route('hrd.employees'), {
      search: searchQuery,
      department_id: selectedDept,
      role: selectedRole,
    }, { preserveState: true });
  };

  const handleResetFilter = () => {
    setSearchQuery('');
    setSelectedDept('');
    setSelectedRole('');
    router.get(route('hrd.employees'));
  };

  // NIK Generator Helper
  const generateNIK = () => {
    const year = new Date().getFullYear();
    const randomNum = Math.floor(100 + Math.random() * 900);
    return `EMP-${year}-${randomNum}`;
  };

  const handleOpenAddModal = () => {
    addForm.reset();
    setAddApprovalMode('inherit');
    addForm.setData({
      nik: generateNIK(),
      name: '',
      email: '',
      password: 'password123',
      role: 'employee',
      department_id: departments[0]?.id || '',
      approver_1_id: '',
      approver_2_id: '',
      manager_id: '',
      total_quota: 12,
      avatar: null,
    });
    setAddAvatarPreview(null);
    setIsAddOpen(true);
  };

  const handleAddSubmit = (e) => {
    e.preventDefault();
    if (addApprovalMode === 'inherit') {
      addForm.data.approver_1_id = '';
      addForm.data.approver_2_id = '';
      addForm.data.manager_id = '';
    }
    addForm.post(route('hrd.employees.store'), {
      onSuccess: () => {
        setIsAddOpen(false);
        addForm.reset();
        setAddAvatarPreview(null);
        showToast('Karyawan baru berhasil ditambahkan!');
      },
    });
  };

  const handleOpenEditModal = (emp) => {
    setEditingEmployee(emp);
    const hasCustom = Boolean(emp.approver_1_id || emp.approver_2_id);
    setEditApprovalMode(hasCustom ? 'custom' : 'inherit');
    editForm.setData({
      id: emp.id,
      nik: emp.nik || '',
      name: emp.name || '',
      email: emp.email || '',
      password: '',
      role: emp.role || 'employee',
      department_id: emp.department_id || '',
      approver_1_id: emp.approver_1_id || '',
      approver_2_id: emp.approver_2_id || emp.manager_id || '',
      manager_id: emp.approver_2_id || emp.manager_id || '',
      total_quota: emp.current_quota?.total_quota || 12,
      avatar: null,
    });
    setEditAvatarPreview(null);
  };

  const handleEditSubmit = (e) => {
    e.preventDefault();
    if (editApprovalMode === 'inherit') {
      editForm.data.approver_1_id = '';
      editForm.data.approver_2_id = '';
      editForm.data.manager_id = '';
    }
    editForm.post(route('hrd.employees.update', editingEmployee.id), {
      onSuccess: () => {
        setEditingEmployee(null);
        editForm.reset();
        setEditAvatarPreview(null);
        showToast('Data karyawan berhasil diperbarui!');
      },
    });
  };

  const handleOpenQuotaModal = (emp) => {
    setQuotaEmployee(emp);
    quotaForm.setData({
      total_quota: emp.current_quota?.total_quota || 12,
    });
  };

  const handleQuotaSubmit = (e) => {
    e.preventDefault();
    quotaForm.post(route('hrd.update-quota', quotaEmployee.id), {
      onSuccess: () => {
        setQuotaEmployee(null);
      },
    });
  };

  const handleDeleteSubmit = () => {
    if (!deletingEmployee) return;
    router.delete(route('hrd.employees.destroy', deletingEmployee.id), {
      onSuccess: () => {
        setDeletingEmployee(null);
      },
    });
  };

  return (
    <AuthenticatedLayout title="Kelola Data Karyawan & Kuota Cuti">
      <div className="space-y-6">

        {/* Page Header */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-black text-slate-900 tracking-tight">Kelola Master Data Karyawan</h2>
            <p className="text-xs text-slate-500">Manajemen data staf, departemen, role, dan pengaturan kuota cuti tahunan</p>
          </div>

          <div className="flex flex-wrap items-center gap-2 self-start md:self-auto">
            <a
              href={route('hrd.employees.export-biodata')}
              download
              className="px-3.5 py-2.5 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 font-extrabold text-xs shadow-sm flex items-center space-x-2 transition-all duration-200"
            >
              <Download size={16} className="text-blue-600" />
              <span>Ekspor Biodata (CSV)</span>
            </a>

            <button
              onClick={() => setIsImportOpen(true)}
              className="px-4 py-2.5 rounded-2xl bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 font-extrabold text-xs shadow-sm flex items-center space-x-2 transition-all duration-200"
            >
              <FileSpreadsheet size={16} className="text-emerald-600" />
              <span>Import Excel / CSV</span>
            </button>

            <button
              onClick={handleOpenAddModal}
              className="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-xs shadow-lg shadow-emerald-600/20 flex items-center space-x-2 transition-all duration-200"
            >
              <UserPlus size={16} />
              <span>+ Tambah Karyawan Baru</span>
            </button>
          </div>
        </div>

        {/* Summary Statistics Cards */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
          <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">
              <Users size={20} />
            </div>
            <div>
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Karyawan</span>
              <span className="text-lg font-black text-slate-900">{stats.total_employees || employees.length}</span>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold shrink-0">
              <Building size={20} />
            </div>
            <div>
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Departemen</span>
              <span className="text-lg font-black text-slate-900">{stats.total_departments || departments.length}</span>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold shrink-0">
              <ShieldCheck size={20} />
            </div>
            <div>
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Atasan / Manager</span>
              <span className="text-lg font-black text-slate-900">{stats.total_managers || managers.length}</span>
            </div>
          </div>

          <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex items-center space-x-3">
            <div className="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold shrink-0">
              <Calendar size={20} />
            </div>
            <div>
              <span className="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Sisa Kuota Cuti</span>
              <span className="text-lg font-black text-teal-700">{stats.active_quotas || 0} <span className="text-xs font-semibold text-slate-500">Hari</span></span>
            </div>
          </div>
        </div>

        {/* Search & Filter Bar */}
        <div className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
          <form onSubmit={handleApplyFilter} className="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
              <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cari Karyawan</label>
              <div className="relative">
                <Search size={16} className="absolute left-3 top-2.5 text-slate-400" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Nama, NIK, atau Email..."
                  className="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 text-xs font-medium focus:bg-white focus:border-emerald-500 outline-none transition-all"
                />
              </div>
            </div>

            <div>
              <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Departemen</label>
              <Select
                value={selectedDept ? String(selectedDept) : 'all'}
                onValueChange={(val) => setSelectedDept(val === 'all' ? '' : val)}
              >
                <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-slate-800 text-xs font-semibold rounded-xl">
                  <SelectValue placeholder="Semua Departemen" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Departemen</SelectItem>
                  {departments.map((dept) => (
                    <SelectItem key={dept.id} value={String(dept.id)}>{dept.name}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <label className="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Role</label>
              <Select value={selectedRole || 'all'} onValueChange={(val) => setSelectedRole(val === 'all' ? '' : val)}>
                <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-slate-800 text-xs font-semibold rounded-xl">
                  <SelectValue placeholder="Semua Role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Semua Role</SelectItem>
                  {allRoles.map((r) => (
                    <SelectItem key={r.value} value={r.value}>{r.label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="flex items-end space-x-2">
              <button
                type="submit"
                className="flex-1 py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition-all"
              >
                Terapkan Filter
              </button>
              <button
                type="button"
                onClick={handleResetFilter}
                className="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition-all"
              >
                Reset
              </button>
            </div>
          </form>
        </div>

        {/* Master Table Card */}
        <div className="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
          {employees && employees.length > 0 ? (
            <div>
              {/* MOBILE CARD VIEW (< md) */}
              <div className="block md:hidden divide-y divide-slate-100">
                {paginatedEmployees.map((emp) => {
                  const quota = emp.current_quota;
                  const remaining = quota?.remaining_quota ?? 12;
                  const total = quota?.total_quota ?? 12;
                  const used = quota?.used_quota ?? 0;

                  const isInherited = !emp.approver_1_id && !emp.approver_2_id && emp.department_id;
                  const effApprover1 = emp.approver1 || (emp.department?.approver1);
                  const effApprover2 = emp.approver2 || emp.manager || (emp.department?.approver2 || emp.department?.manager);

                  return (
                    <div key={emp.id} className="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs space-y-3">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                          <UserAvatar user={emp} size="w-10 h-10" textSize="text-xs" />
                          <div>
                            <h4 className="font-extrabold text-slate-900 text-sm truncate">{emp.name}</h4>
                            <span className="font-mono text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 inline-block mt-0.5">
                              {emp.nik || 'EMP-???'}
                            </span>
                          </div>
                        </div>
                        {(() => {
                          const rBadge = getRoleBadge(emp.role);
                          return (
                            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider shrink-0 ${rBadge.cls}`}>
                              {rBadge.label}
                            </span>
                          );
                        })()}
                      </div>

                      <div className="grid grid-cols-2 gap-2 text-xs pt-1">
                        <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 min-w-0">
                          <span className="text-[10px] text-slate-400 font-bold uppercase block">Email</span>
                          <span className="font-semibold text-slate-800 truncate block">{emp.email}</span>
                        </div>

                        <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 min-w-0">
                          <span className="text-[10px] text-slate-400 font-bold uppercase block">Departemen</span>
                          <span className="font-bold text-slate-900 truncate block">{emp.department?.name || 'General'}</span>
                        </div>

                        <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 min-w-0 col-span-2">
                          <div className="flex items-center justify-between mb-1">
                            <span className="text-[10px] text-slate-400 font-bold uppercase">Alur Approval Cuti</span>
                            {isInherited ? (
                              <span className="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase">
                                🛡️ Ikut Dept ({emp.department?.name})
                              </span>
                            ) : (emp.approver_1_id || emp.approver_2_id) ? (
                              <span className="px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 text-[9px] font-black uppercase">
                                ⚙️ Kustom
                              </span>
                            ) : null}
                          </div>
                          <div className="text-[11px] font-semibold text-slate-700 flex flex-wrap gap-x-3 gap-y-0.5">
                            {effApprover1 && (
                              <span className="text-blue-700">T1: {effApprover1.name}</span>
                            )}
                            {effApprover2 && (
                              <span className="text-purple-700">T2: {effApprover2.name}</span>
                            )}
                            {!effApprover1 && !effApprover2 && (
                              <span className="text-slate-400 italic">Direct HRD</span>
                            )}
                          </div>
                        </div>

                        <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 min-w-0 col-span-2">
                          <span className="text-[10px] text-slate-400 font-bold uppercase block">Sisa Kuota Cuti</span>
                          <span className="font-black text-emerald-600 block">{remaining} / {total} Hari</span>
                        </div>
                      </div>

                      <div className="flex items-center justify-end space-x-2 pt-1 border-t border-slate-100">
                        <button
                          onClick={() => handleOpenEditModal(emp)}
                          className="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold text-xs flex items-center space-x-1 border border-slate-200"
                        >
                          <Edit3 size={14} />
                          <span>Edit</span>
                        </button>
                        <button
                          onClick={() => handleOpenQuotaModal(emp)}
                          className="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-bold text-xs flex items-center space-x-1 border border-slate-200"
                        >
                          <Calendar size={14} />
                          <span>Kuota</span>
                        </button>
                        <button
                          onClick={() => setDeletingEmployee(emp)}
                          className="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-xs flex items-center space-x-1 border border-rose-200"
                        >
                          <Trash2 size={14} />
                          <span>Hapus</span>
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>

              {/* DESKTOP TABLE VIEW (>= md) */}
              <div className="hidden md:block overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead>
                    <tr className="bg-slate-50 border-b border-slate-200/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                      <th className="py-3.5 px-4">Karyawan & NIK</th>
                      <th className="py-3.5 px-4">Email</th>
                      <th className="py-3.5 px-4">Departemen</th>
                      <th className="py-3.5 px-4">Role System</th>
                      <th className="py-3.5 px-4">Data Diri</th>
                      <th className="py-3.5 px-4">Alur Approval (Atasan 1 & 2)</th>
                      <th className="py-3.5 px-4">Kuota Cuti ({new Date().getFullYear()})</th>
                      <th className="py-3.5 px-4 text-right">Aksi HRD</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-slate-700">
                    {paginatedEmployees.map((emp) => {
                      const quota = emp.current_quota;
                      const remaining = quota?.remaining_quota ?? 12;
                      const total = quota?.total_quota ?? 12;
                      const used = quota?.used_quota ?? 0;

                      const isInherited = !emp.approver_1_id && !emp.approver_2_id && emp.department_id;
                      const effApprover1 = emp.approver1 || (emp.department?.approver1);
                      const effApprover2 = emp.approver2 || emp.manager || (emp.department?.approver2 || emp.department?.manager);

                      return (
                        <tr key={emp.id} className="hover:bg-slate-50/80 transition-colors">
                          <td className="py-3.5 px-4">
                            <div className="flex items-center space-x-3">
                              <UserAvatar user={emp} size="w-9 h-9" textSize="text-xs" />
                              <div>
                                <span className="font-extrabold text-slate-900 text-xs block">{emp.name}</span>
                                <span className="font-mono text-[10px] font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 inline-block mt-0.5">
                                  {emp.nik || 'EMP-???'}
                                </span>
                              </div>
                            </div>
                          </td>

                          <td className="py-3.5 px-4 text-slate-600 font-medium">
                            {emp.email}
                          </td>

                          <td className="py-3.5 px-4 font-semibold text-slate-800">
                            {emp.department?.name || 'General'}
                          </td>

                          <td className="py-3.5 px-4">
                            {(() => {
                              const rBadge = getRoleBadge(emp.role);
                              return (
                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider ${rBadge.cls}`}>
                                  {rBadge.label}
                                </span>
                              );
                            })()}
                          </td>

                          <td className="py-3.5 px-4">
                            <div className="flex flex-col space-y-1">
                              <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ${
                                emp.is_profile_completed ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'
                              }`}>
                                {emp.is_profile_completed ? '✓ Lengkap' : 'Belum Lengkap'}
                              </span>
                              <div className="w-16 bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                <div
                                  className="bg-emerald-500 h-full rounded-full"
                                  style={{ width: `${Math.min(100, emp.profile_completeness || 0)}%` }}
                                />
                              </div>
                            </div>
                          </td>

                          <td className="py-3.5 px-4 text-slate-600 font-medium">
                            <div className="text-xs space-y-1">
                              <div className="flex items-center space-x-1.5">
                                {isInherited ? (
                                  <span className="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-extrabold">
                                    🛡️ Ikut {emp.department?.name}
                                  </span>
                                ) : (emp.approver_1_id || emp.approver_2_id) ? (
                                  <span className="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-extrabold">
                                    ⚙️ Kustom
                                  </span>
                                ) : (
                                  <span className="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-extrabold">
                                    Direct HRD
                                  </span>
                                )}
                              </div>
                              <div className="space-y-0.5 text-[11px]">
                                {effApprover1 && (
                                  <div className="text-blue-800 font-semibold truncate">
                                    T1: {effApprover1.name}
                                  </div>
                                )}
                                {effApprover2 && (
                                  <div className="text-purple-800 font-semibold truncate">
                                    T2: {effApprover2.name}
                                  </div>
                                )}
                              </div>
                            </div>
                          </td>

                          <td className="py-3.5 px-4">
                            <div className="font-extrabold text-slate-900">
                              Sisa: <span className="text-emerald-600 font-black">{remaining}</span> / {total} Hari
                            </div>
                            <div className="w-24 bg-slate-100 h-1.5 rounded-full overflow-hidden mt-1">
                              <div
                                className="bg-emerald-500 h-full rounded-full"
                                style={{ width: `${Math.min(100, (remaining / total) * 100)}%` }}
                              />
                            </div>
                          </td>

                          <td className="py-3.5 px-4 text-right">
                            <div className="flex items-center justify-end space-x-1">
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => handleOpenEditModal(emp)}
                                className="h-8 px-2.5 rounded-xl text-xs font-bold space-x-1 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50"
                              >
                                <Edit3 size={14} />
                                <span className="hidden sm:inline">Edit</span>
                              </Button>

                              <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                  <Button variant="ghost" size="icon" className="h-8 w-8 rounded-xl text-slate-500 hover:text-slate-900">
                                    <MoreVertical size={15} />
                                  </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-52">
                                  <DropdownMenuLabel>Aksi Karyawan</DropdownMenuLabel>
                                  <DropdownMenuItem onClick={() => handleOpenEditModal(emp)}>
                                    <Edit3 className="mr-2 h-4 w-4 text-emerald-600" />
                                    <span>Edit Profil & Approval</span>
                                  </DropdownMenuItem>
                                  <DropdownMenuItem asChild>
                                    <Link href={route('hrd.employees.biodata', emp.id)} className="flex items-center w-full">
                                      <FileText className="mr-2 h-4 w-4 text-blue-600" />
                                      <span>Form Data Diri (Detail)</span>
                                    </Link>
                                  </DropdownMenuItem>
                                  <DropdownMenuItem asChild>
                                    <a href={route('hrd.employees.biodata.print', emp.id)} target="_blank" rel="noopener noreferrer" className="flex items-center w-full">
                                      <Printer className="mr-2 h-4 w-4 text-purple-600" />
                                      <span>Cetak Form Data Diri</span>
                                    </a>
                                  </DropdownMenuItem>
                                  <DropdownMenuItem onClick={() => handleOpenQuotaModal(emp)}>
                                    <Calendar className="mr-2 h-4 w-4 text-teal-600" />
                                    <span>Atur Kuota Cuti</span>
                                  </DropdownMenuItem>
                                  <DropdownMenuSeparator />
                                  <DropdownMenuItem
                                    onClick={() => setDeletingEmployee(emp)}
                                    className="text-rose-600 focus:bg-rose-50 focus:text-rose-700"
                                  >
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    <span>Hapus Karyawan</span>
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

              {/* Zero-Lag Instant Pagination */}
              <InstantPagination
                currentPage={currentPage}
                totalItems={employees.length}
                pageSize={pageSize}
                onPageChange={setCurrentPage}
                onPageSizeChange={(newSize) => {
                  setPageSize(newSize);
                  setCurrentPage(1);
                }}
                itemName="karyawan"
              />
            </div>
          ) : (
            <div className="py-16 text-center text-slate-400">
              <Users size={40} className="mx-auto mb-3 opacity-30 text-slate-500" />
              <p className="text-xs font-bold text-slate-600">Tidak ada data karyawan yang sesuai kriteria.</p>
            </div>
          )}
        </div>

        {/* MODAL 1: TAMBAH KARYAWAN BARU */}
        {isAddOpen && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" onClick={() => setIsAddOpen(false)} />
            <div className="relative z-10 w-full max-w-lg rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xl overflow-hidden my-6 max-h-[92vh] flex flex-col transform-gpu animate-fade-in">
              <div className="flex items-center justify-between border-b border-slate-100 p-4 sm:p-5 shrink-0 bg-white">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <UserPlus size={20} />
                  </div>
                  <div>
                    <h3 className="text-sm sm:text-base font-black text-slate-900 leading-tight truncate">Tambah Karyawan Baru</h3>
                  </div>
                </div>
                <button onClick={() => setIsAddOpen(false)} className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800 transition-colors shrink-0">
                  <X size={18} />
                </button>
              </div>

              <form onSubmit={handleAddSubmit} className="flex flex-col flex-1 overflow-hidden">
                <div className="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1 text-xs">
                {/* Avatar File Upload */}
                <div className="flex items-center space-x-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                  <div className="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-lg overflow-hidden shrink-0">
                    {addAvatarPreview ? (
                      <img src={addAvatarPreview} alt="Preview" className="w-full h-full object-cover" />
                    ) : (
                      addForm.data.name ? addForm.data.name.charAt(0).toUpperCase() : 'U'
                    )}
                  </div>
                  <div className="min-w-0 flex-1">
                    <label className="block text-[11px] font-bold text-slate-700 mb-1">Foto Profil (Opsional &bull; Auto WebP)</label>
                    <input
                      type="file"
                      accept="image/*"
                      onChange={(e) => {
                        const file = e.target.files[0];
                        if (file) {
                          addForm.setData('avatar', file);
                          setAddAvatarPreview(URL.createObjectURL(file));
                        }
                      }}
                      className="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                    />
                  </div>
                </div>

                {/* NIK & Nama Lengkap */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">No Induk Karyawan (NIK) *</label>
                    <input
                      type="text"
                      required
                      value={addForm.data.nik}
                      onChange={(e) => addForm.setData('nik', e.target.value)}
                      placeholder="Contoh: EMP-2026-001"
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    />
                  </div>

                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Nama Lengkap *</label>
                    <input
                      type="text"
                      required
                      value={addForm.data.name}
                      onChange={(e) => addForm.setData('name', e.target.value)}
                      placeholder="Nama lengkap karyawan..."
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    />
                  </div>
                </div>

                {/* Email & Password */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Email Terdaftar *</label>
                    <input
                      type="email"
                      required
                      value={addForm.data.email}
                      onChange={(e) => addForm.setData('email', e.target.value)}
                      placeholder="email@perusahaan.com"
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-medium focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    />
                  </div>

                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Password Awal *</label>
                    <input
                      type="text"
                      required
                      value={addForm.data.password}
                      onChange={(e) => addForm.setData('password', e.target.value)}
                      placeholder="Minimal 6 karakter"
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-mono font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    />
                  </div>
                </div>

                {/* Role & Departemen */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Role Sistem *</label>
                    <select
                      value={addForm.data.role}
                      onChange={(e) => addForm.setData('role', e.target.value)}
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    >
                      {allRoles.map((r) => (
                        <option key={r.value} value={r.value}>{r.label}</option>
                      ))}
                    </select>
                  </div>

                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Departemen / Divisi</label>
                    <select
                      value={addForm.data.department_id}
                      onChange={(e) => addForm.setData('department_id', e.target.value)}
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    >
                      <option value="">Pilih Departemen</option>
                      {departments.map((dept) => (
                        <option key={dept.id} value={dept.id}>{dept.name}</option>
                      ))}
                    </select>
                  </div>
                </div>

                {/* Approval Flow Configuration */}
                <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <label className="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                      Pengaturan Alur Approval
                    </label>
                    <div className="inline-flex rounded-xl bg-slate-200/80 p-0.5 text-[10px] font-bold self-start sm:self-auto shrink-0">
                      <button
                        type="button"
                        onClick={() => {
                          setAddApprovalMode('inherit');
                          addForm.setData((prev) => ({ ...prev, approver_1_id: '', approver_2_id: '', manager_id: '' }));
                        }}
                        className={`px-2.5 py-1 rounded-lg transition-all ${
                          addApprovalMode === 'inherit'
                            ? 'bg-white text-emerald-800 shadow-xs font-black'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        🛡️ Ikut Departemen
                      </button>
                      <button
                        type="button"
                        onClick={() => setAddApprovalMode('custom')}
                        className={`px-2.5 py-1 rounded-lg transition-all ${
                          addApprovalMode === 'custom'
                            ? 'bg-white text-purple-800 shadow-xs font-black'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        ⚙️ Kustom Atasan
                      </button>
                    </div>
                  </div>

                  {addApprovalMode === 'inherit' ? (
                    <div className="p-3 rounded-xl bg-emerald-50/80 border border-emerald-200/80 text-xs space-y-2">
                      {(() => {
                        const curDept = departments.find(d => String(d.id) === String(addForm.data.department_id));
                        const flowLabel = curDept?.approval_type === '3_tier'
                          ? '3 Tingkat (Supervisor -> Manager -> HRD)'
                          : curDept?.approval_type === '2_tier'
                          ? '2 Tingkat (Manager -> HRD)'
                          : curDept?.approval_type === '1_tier'
                          ? '1 Tingkat (Langsung HRD)'
                          : '3 Tingkat Standar';
                        return (
                          <>
                            <div className="flex items-center justify-between text-[11px]">
                              <span className="font-extrabold text-emerald-950">
                                Alur: {flowLabel}
                              </span>
                              <span className="text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full">
                                Otomatis
                              </span>
                            </div>
                            <div className="space-y-1 text-[11px] pt-1 border-t border-emerald-200/60">
                              <div className="flex items-center justify-between">
                                <span className="text-slate-600 font-medium">Atasan 1 (Supervisor):</span>
                                <span className="font-bold text-slate-900">
                                  {curDept?.approver1?.name || (curDept?.approval_type === '2_tier' || curDept?.approval_type === '1_tier' ? <span className="text-slate-400 italic">Dilewati</span> : <span className="text-amber-700 italic">Belum Diatur di Departemen</span>)}
                                </span>
                              </div>
                              <div className="flex items-center justify-between">
                                <span className="text-slate-600 font-medium">Atasan 2 (Manager):</span>
                                <span className="font-bold text-slate-900">
                                  {curDept?.approver2?.name || curDept?.manager?.name || (curDept?.approval_type === '1_tier' ? <span className="text-slate-400 italic">Dilewati</span> : <span className="text-amber-700 italic">Belum Diatur di Departemen</span>)}
                                </span>
                              </div>
                              <div className="flex items-center justify-between">
                                <span className="text-slate-600 font-medium">Persetujuan Akhir:</span>
                                <span className="font-bold text-emerald-900">HRD / PGA Admin</span>
                              </div>
                            </div>
                            <p className="text-[10px] text-emerald-700/90 italic pt-0.5 leading-relaxed">
                              💡 Karyawan ini otomatis mengikuti aturan approval departemen. Ketika atasan departemen diubah di menu Setup Departemen, persetujuan cuti karyawan ini otomatis menyesuaikan.
                            </p>
                          </>
                        );
                      })()}
                    </div>
                  ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                      <div className="min-w-0">
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Approval 1 (Supervisor / Lead)</label>
                        <select
                          value={addForm.data.approver_1_id}
                          onChange={(e) => addForm.setData('approver_1_id', e.target.value)}
                          className="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-900 font-semibold focus:border-emerald-500 outline-none text-xs"
                        >
                          <option value="">Tidak ada (Langsung Approval 2 / HRD)</option>
                          {managers.map((m) => (
                            <option key={m.id} value={m.id}>{m.name} ({m.department?.name || m.role})</option>
                          ))}
                        </select>
                      </div>

                      <div className="min-w-0">
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Approval 2 (Manager / Dept Head)</label>
                        <select
                          value={addForm.data.approver_2_id}
                          onChange={(e) => addForm.setData('approver_2_id', e.target.value)}
                          className="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-900 font-semibold focus:border-emerald-500 outline-none text-xs"
                        >
                          <option value="">Tidak ada (Langsung HRD)</option>
                          {managers.map((m) => (
                            <option key={m.id} value={m.id}>{m.name} ({m.department?.name || m.role})</option>
                          ))}
                        </select>
                      </div>
                    </div>
                  )}
                </div>

                <div className="min-w-0">
                  <label className="block text-[11px] font-bold text-slate-600 mb-1">Jatah Kuota Cuti (Hari / Thn)</label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    value={addForm.data.total_quota}
                    onChange={(e) => addForm.setData('total_quota', e.target.value)}
                    className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                  />
                </div>

                </div>
                {/* Modal Footer */}
                <div className="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2.5 shrink-0">
                  <button
                    type="button"
                    onClick={() => setIsAddOpen(false)}
                    className="px-4 py-2 rounded-xl bg-slate-200 text-slate-600 font-bold hover:bg-slate-300 transition-colors"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={addForm.processing}
                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold shadow-md shadow-emerald-600/20 disabled:opacity-50 transition-all flex items-center space-x-1.5"
                  >
                    <span>{addForm.processing ? 'Menyimpan...' : 'Simpan Karyawan'}</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* MODAL 2: EDIT DATA KARYAWAN */}
        {editingEmployee && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" onClick={() => setEditingEmployee(null)} />
            <div className="relative z-10 w-full max-w-lg rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xl overflow-hidden my-6 max-h-[92vh] flex flex-col transform-gpu animate-fade-in">
              <div className="flex items-center justify-between border-b border-slate-100 p-4 sm:p-5 shrink-0 bg-white">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <Edit3 size={20} />
                  </div>
                  <div>
                    <h3 className="text-sm sm:text-base font-black text-slate-900 leading-tight truncate">Edit Data Karyawan</h3>
                  </div>
                </div>
                <button onClick={() => setEditingEmployee(null)} className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800 transition-colors shrink-0">
                  <X size={18} />
                </button>
              </div>

              <form onSubmit={handleEditSubmit} className="flex flex-col flex-1 overflow-hidden">
                <div className="p-4 sm:p-5 space-y-3.5 overflow-y-auto flex-1 text-xs">
                {/* Avatar Preview & File Input */}
                <div className="flex items-center space-x-3 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                  <div className="shrink-0">
                    {editAvatarPreview ? (
                      <img src={editAvatarPreview} alt="Preview" className="w-12 h-12 rounded-full object-cover ring-2 ring-emerald-500" />
                    ) : (
                      <UserAvatar user={editingEmployee} size="w-12 h-12" textSize="text-base" />
                    )}
                  </div>
                  <div className="min-w-0 flex-1">
                    <label className="block text-[11px] font-bold text-slate-700 mb-1">Ganti Foto Profil (Auto WebP)</label>
                    <input
                      type="file"
                      accept="image/*"
                      onChange={(e) => {
                        const file = e.target.files[0];
                        if (file) {
                          editForm.setData('avatar', file);
                          setEditAvatarPreview(URL.createObjectURL(file));
                        }
                      }}
                      className="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-xl file:border-0 file:text-[11px] file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">NIK Karyawan *</label>
                    <input
                      type="text"
                      required
                      value={editForm.data.nik}
                      onChange={(e) => editForm.setData('nik', e.target.value)}
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    />
                  </div>

                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Nama Lengkap *</label>
                    <input
                      type="text"
                      required
                      value={editForm.data.name}
                      onChange={(e) => editForm.setData('name', e.target.value)}
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Email Terdaftar *</label>
                    <input
                      type="email"
                      required
                      value={editForm.data.email}
                      onChange={(e) => editForm.setData('email', e.target.value)}
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-medium focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    />
                  </div>

                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Ubah Password (Opsional)</label>
                    <input
                      type="password"
                      value={editForm.data.password}
                      onChange={(e) => editForm.setData('password', e.target.value)}
                      placeholder="Kosongkan jika tidak diubah"
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-mono focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Role Sistem *</label>
                    <select
                      value={editForm.data.role}
                      onChange={(e) => editForm.setData('role', e.target.value)}
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    >
                      {allRoles.map((r) => (
                        <option key={r.value} value={r.value}>{r.label}</option>
                      ))}
                    </select>
                  </div>

                  <div className="min-w-0">
                    <label className="block text-[11px] font-bold text-slate-600 mb-1">Departemen / Divisi</label>
                    <select
                      value={editForm.data.department_id}
                      onChange={(e) => editForm.setData('department_id', e.target.value)}
                      className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                    >
                      <option value="">Pilih Departemen</option>
                      {departments.map((dept) => (
                        <option key={dept.id} value={dept.id}>{dept.name}</option>
                      ))}
                    </select>
                  </div>
                </div>

                {/* Approval Flow Configuration */}
                <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2.5">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <label className="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                      Pengaturan Alur Approval
                    </label>
                    <div className="inline-flex rounded-xl bg-slate-200/80 p-0.5 text-[10px] font-bold self-start sm:self-auto shrink-0">
                      <button
                        type="button"
                        onClick={() => {
                          setEditApprovalMode('inherit');
                          editForm.setData((prev) => ({ ...prev, approver_1_id: '', approver_2_id: '', manager_id: '' }));
                        }}
                        className={`px-2.5 py-1 rounded-lg transition-all ${
                          editApprovalMode === 'inherit'
                            ? 'bg-white text-emerald-800 shadow-xs font-black'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        🛡️ Ikut Departemen
                      </button>
                      <button
                        type="button"
                        onClick={() => setEditApprovalMode('custom')}
                        className={`px-2.5 py-1 rounded-lg transition-all ${
                          editApprovalMode === 'custom'
                            ? 'bg-white text-purple-800 shadow-xs font-black'
                            : 'text-slate-600 hover:text-slate-900'
                        }`}
                      >
                        ⚙️ Kustom Atasan
                      </button>
                    </div>
                  </div>

                  {editApprovalMode === 'inherit' ? (
                    <div className="p-3 rounded-xl bg-emerald-50/80 border border-emerald-200/80 text-xs space-y-2">
                      {(() => {
                        const curDept = departments.find(d => String(d.id) === String(editForm.data.department_id));
                        const flowLabel = curDept?.approval_type === '3_tier'
                          ? '3 Tingkat (Supervisor -> Manager -> HRD)'
                          : curDept?.approval_type === '2_tier'
                          ? '2 Tingkat (Manager -> HRD)'
                          : curDept?.approval_type === '1_tier'
                          ? '1 Tingkat (Langsung HRD)'
                          : '3 Tingkat Standar';
                        return (
                          <>
                            <div className="flex items-center justify-between text-[11px]">
                              <span className="font-extrabold text-emerald-950">
                                Alur: {flowLabel}
                              </span>
                              <span className="text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full">
                                Otomatis
                              </span>
                            </div>
                            <div className="space-y-1 text-[11px] pt-1 border-t border-emerald-200/60">
                              <div className="flex items-center justify-between">
                                <span className="text-slate-600 font-medium">Atasan 1 (Supervisor):</span>
                                <span className="font-bold text-slate-900">
                                  {curDept?.approver1?.name || (curDept?.approval_type === '2_tier' || curDept?.approval_type === '1_tier' ? <span className="text-slate-400 italic">Dilewati</span> : <span className="text-amber-700 italic">Belum Diatur di Departemen</span>)}
                                </span>
                              </div>
                              <div className="flex items-center justify-between">
                                <span className="text-slate-600 font-medium">Atasan 2 (Manager):</span>
                                <span className="font-bold text-slate-900">
                                  {curDept?.approver2?.name || curDept?.manager?.name || (curDept?.approval_type === '1_tier' ? <span className="text-slate-400 italic">Dilewati</span> : <span className="text-amber-700 italic">Belum Diatur di Departemen</span>)}
                                </span>
                              </div>
                              <div className="flex items-center justify-between">
                                <span className="text-slate-600 font-medium">Persetujuan Akhir:</span>
                                <span className="font-bold text-emerald-900">HRD / PGA Admin</span>
                              </div>
                            </div>
                            <p className="text-[10px] text-emerald-700/90 italic pt-0.5 leading-relaxed">
                              💡 Karyawan ini otomatis mengikuti aturan approval departemen. Ketika atasan departemen diubah di menu Setup Departemen, persetujuan cuti karyawan ini otomatis menyesuaikan.
                            </p>
                          </>
                        );
                      })()}
                    </div>
                  ) : (
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                      <div className="min-w-0">
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Approval 1 (Supervisor / Lead)</label>
                        <select
                          value={editForm.data.approver_1_id}
                          onChange={(e) => editForm.setData('approver_1_id', e.target.value)}
                          className="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-900 font-semibold focus:border-emerald-500 outline-none text-xs"
                        >
                          <option value="">Tidak ada (Langsung Approval 2 / HRD)</option>
                          {managers.filter(m => m.id !== editingEmployee?.id).map((m) => (
                            <option key={m.id} value={m.id}>{m.name} ({m.department?.name || m.role})</option>
                          ))}
                        </select>
                      </div>

                      <div className="min-w-0">
                        <label className="block text-[11px] font-bold text-slate-600 mb-1">Approval 2 (Manager / Dept Head)</label>
                        <select
                          value={editForm.data.approver_2_id}
                          onChange={(e) => editForm.setData('approver_2_id', e.target.value)}
                          className="w-full px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-900 font-semibold focus:border-emerald-500 outline-none text-xs"
                        >
                          <option value="">Tidak ada (Langsung HRD)</option>
                          {managers.filter(m => m.id !== editingEmployee?.id).map((m) => (
                            <option key={m.id} value={m.id}>{m.name} ({m.department?.name || m.role})</option>
                          ))}
                        </select>
                      </div>
                    </div>
                  )}
                </div>

                <div className="min-w-0">
                  <label className="block text-[11px] font-bold text-slate-600 mb-1">Total Jatah Kuota Cuti</label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    value={editForm.data.total_quota}
                    onChange={(e) => editForm.setData('total_quota', e.target.value)}
                    className="w-full px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-bold focus:bg-white focus:border-emerald-500 outline-none text-xs"
                  />
                </div>

                </div>
                {/* Modal Footer */}
                <div className="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-2.5 shrink-0">
                  <button
                    type="button"
                    onClick={() => setEditingEmployee(null)}
                    className="px-4 py-2 rounded-xl bg-slate-200 text-slate-600 font-bold hover:bg-slate-300 transition-colors"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={editForm.processing}
                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold shadow-md shadow-emerald-600/20 disabled:opacity-50 transition-all flex items-center space-x-1.5"
                  >
                    <span>{editForm.processing ? 'Memperbarui...' : 'Simpan Perubahan'}</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* MODAL 3: EDIT KUOTA CUTI TAHUNAN */}
        {quotaEmployee && (
          <div className="fixed inset-0 z-[100] overflow-y-auto overflow-x-hidden p-3 sm:p-4 flex min-h-full items-center justify-center animate-fade-in">
            <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" onClick={() => setQuotaEmployee(null)} />
            <div className="relative z-10 w-full max-w-md p-4 sm:p-6 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-4 my-auto max-h-[90vh] overflow-y-auto overflow-x-hidden">
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 className="text-sm sm:text-base font-extrabold text-slate-900 truncate">Update Kuota Cuti Tahunan</h3>
                <button onClick={() => setQuotaEmployee(null)} className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 shrink-0">
                  <X size={18} />
                </button>
              </div>

              <div className="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs text-slate-700 space-y-1">
                <p className="font-extrabold text-slate-900 truncate">{quotaEmployee.name} ({quotaEmployee.nik})</p>
                <p className="text-slate-600 truncate">Departemen: {quotaEmployee.department?.name || 'General'}</p>
                <p className="text-emerald-800 font-bold mt-1">Sisa Kuota Saat Ini: {quotaEmployee.current_quota?.remaining_quota ?? 12} Hari</p>
              </div>

              <form onSubmit={handleQuotaSubmit} className="space-y-4">
                <div className="min-w-0">
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Total Jatah Kuota Cuti (Hari / Tahun {new Date().getFullYear()})
                  </label>
                  <input
                    type="number"
                    min="0"
                    max="100"
                    value={quotaForm.data.total_quota}
                    onChange={(e) => quotaForm.setData('total_quota', e.target.value)}
                    className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-black text-sm sm:text-base focus:bg-white focus:border-emerald-500 outline-none"
                    required
                  />
                  <p className="text-[11px] text-slate-500 mt-1">Sisa kuota akan otomatis dihitung berdasarkan jumlah cuti yang sudah terpakai.</p>
                </div>

                <div className="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setQuotaEmployee(null)}
                    className="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200 transition-colors"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition-all"
                  >
                    Simpan Kuota
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* MODAL 4: KONFIRMASI HAPUS KARYAWAN */}
        {deletingEmployee && (
          <div className="fixed inset-0 z-[100] overflow-y-auto overflow-x-hidden p-3 sm:p-4 flex min-h-full items-center justify-center animate-fade-in">
            <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" onClick={() => setDeletingEmployee(null)} />
            <div className="relative z-10 w-full max-w-sm p-5 sm:p-6 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-4 text-center my-auto">
              <div className="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
                <AlertTriangle size={24} />
              </div>
              <div>
                <h3 className="text-sm sm:text-base font-extrabold text-slate-900">Hapus Data Karyawan?</h3>
                <p className="text-xs text-slate-500 mt-1.5 leading-relaxed">
                  Apakah Anda yakin ingin menghapus karyawan <strong className="text-slate-900">{deletingEmployee.name}</strong> ({deletingEmployee.nik})? Data yang dihapus tidak dapat dikembalikan.
                </p>
              </div>
              <div className="flex items-center justify-center space-x-2 pt-2">
                <button
                  type="button"
                  onClick={() => setDeletingEmployee(null)}
                  className="w-full py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={handleDeleteSubmit}
                  className="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-black text-xs shadow-md shadow-rose-600/20 transition-all"
                >
                  Ya, Hapus
                </button>
              </div>
            </div>
          </div>
        )}

        {/* MODAL 5: IMPORT EXCEL / CSV KARYAWAN */}
        {isImportOpen && (
          <div className="fixed inset-0 z-[100] overflow-y-auto overflow-x-hidden p-3 sm:p-4 flex min-h-full items-center justify-center animate-fade-in">
            <div className="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" onClick={() => setIsImportOpen(false)} />
            <div className="relative z-10 w-full max-w-lg p-4 sm:p-6 sm:p-8 rounded-2xl sm:rounded-3xl bg-white border border-slate-200 shadow-2xl space-y-5 my-auto max-h-[90vh] overflow-y-auto overflow-x-hidden">
              <div className="flex items-center justify-between border-b border-slate-200 pb-3 sm:pb-4">
                <div className="flex items-center space-x-3 min-w-0">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <FileSpreadsheet size={20} />
                  </div>
                  <div className="min-w-0">
                    <h3 className="text-sm sm:text-base font-extrabold text-slate-900 truncate">Import Data Karyawan</h3>
                    <p className="text-[11px] sm:text-xs text-slate-500 truncate">Migrasi massal data staf dari template Excel / CSV</p>
                  </div>
                </div>
                <button onClick={() => setIsImportOpen(false)} className="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 shrink-0">
                  <X size={18} />
                </button>
              </div>

              {/* Step 1: Download Template */}
              <div className="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3.5">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                  <div>
                    <span className="text-xs font-black text-slate-900 uppercase tracking-wider block">Langkah 1: Format Template CSV (9 Kolom)</span>
                    <span className="text-[11px] text-slate-500">Bisa untuk tambah karyawan baru ATAU update massal Jabatan & Jenis Kelamin karyawan lama.</span>
                  </div>
                  <a
                    href={route('hrd.employees.template')}
                    download
                    className="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black flex items-center justify-center space-x-1.5 shadow-md shadow-emerald-600/20 transition-all self-start sm:self-auto shrink-0"
                  >
                    <Download size={14} />
                    <span>Download Template CSV</span>
                  </a>
                </div>

                {/* 9 Columns Guide with distinct colors */}
                <div className="space-y-2.5 text-xs">
                  <div className="p-3 rounded-xl bg-white border border-slate-200 shadow-2xs space-y-2">
                    <div className="flex items-center justify-between">
                      <span className="text-[11px] font-black uppercase tracking-wider text-slate-700">Struktur 9 Kolom Template:</span>
                      <span className="text-[10px] font-bold text-slate-400">Urutan Kolom</span>
                    </div>
                    <div className="flex flex-wrap gap-1.5">
                      <span className="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-bold">1. NIK SGIN (Opsional)</span>
                      <span className="px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-extrabold">2. Nama Lengkap (Wajib) *</span>
                      <span className="px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-extrabold">3. Email Login (Wajib) *</span>
                      <span className="px-2 py-0.5 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 text-[10px] font-bold">4. Password (Opsional)</span>
                      <span className="px-2 py-0.5 rounded-lg bg-purple-50 text-purple-800 border border-purple-200 text-[10px] font-bold">5. Role (Opsional)</span>
                      <span className="px-2 py-0.5 rounded-lg bg-teal-50 text-teal-800 border border-teal-200 text-[10px] font-bold">6. Departemen (Opsional)</span>
                      <span className="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-900 border border-indigo-300 text-[10px] font-black">7. Jabatan / Posisi (Opsional) 💼</span>
                      <span className="px-2 py-0.5 rounded-lg bg-pink-50 text-pink-900 border border-pink-300 text-[10px] font-black">8. Jenis Kelamin (L/P) 👤</span>
                      <span className="px-2 py-0.5 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-[10px] font-bold">9. Status Karyawan (Opsional)</span>
                    </div>
                  </div>

                  {/* Batch Update Tip */}
                  <div className="p-2.5 rounded-xl bg-blue-50/80 border border-blue-200 text-[11px] text-blue-900 space-y-1">
                    <span className="font-extrabold flex items-center space-x-1">
                      <span>💡 Tips Update Massal Karyawan Lama:</span>
                    </span>
                    <p className="text-[11px] text-blue-800 leading-relaxed">
                      Untuk update Jabatan & Jenis Kelamin sekaligus, masukkan <strong>Email</strong> atau <strong>NIK</strong> karyawan yang sudah ada di Excel/CSV lalu isi kolom <strong>Jabatan</strong> & <strong>Jenis Kelamin</strong>. Password lama karyawan <strong>tidak akan berubah/reset</strong> jika kolom password dikosongkan.
                    </p>
                  </div>

                  {/* Dynamic Department List */}
                  <div className="p-2.5 rounded-xl bg-teal-50/70 border border-teal-200/80 text-[11px]">
                    <span className="font-extrabold text-teal-950 block mb-1">🏢 Kode Departemen yang Terdaftar:</span>
                    <div className="flex flex-wrap gap-1">
                      {departments.map((d) => (
                        <span key={d.id} className="px-2 py-0.5 rounded-md bg-white border border-teal-200 text-teal-900 font-mono font-bold text-[10px]">
                          {d.code}: {d.name}
                        </span>
                      ))}
                    </div>
                  </div>

                  {/* Dynamic Role List */}
                  <div className="p-2.5 rounded-xl bg-purple-50/70 border border-purple-200/80 text-[11px]">
                    <span className="font-extrabold text-purple-950 block mb-1">🛡️ Role yang Tersedia di Sistem:</span>
                    <div className="flex flex-wrap gap-1">
                      {allRoles.map((r) => (
                        <span key={r.value} className="px-2 py-0.5 rounded-md bg-white border border-purple-200 text-purple-900 font-mono font-bold text-[10px]">
                          {r.value}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              </div>

              {/* Step 2: Upload File */}
              <form onSubmit={handleImportSubmit} className="space-y-4 sm:space-y-5">
                <div className="min-w-0">
                  <label className="block text-[11px] sm:text-xs font-black text-slate-900 uppercase tracking-wider mb-1.5">
                    Langkah 2: Upload File CSV / Excel
                  </label>
                  <div className="p-4 sm:p-6 rounded-2xl bg-slate-50 border-2 border-dashed border-slate-300 hover:border-emerald-500 text-center transition-all">
                    <UploadCloud size={32} className="mx-auto text-emerald-600 mb-1.5" />
                    <input
                      type="file"
                      accept=".csv, .txt, .xlsx"
                      onChange={(e) => importForm.setData('file', e.target.files[0])}
                      className="hidden"
                      id="employee-import-input"
                      required
                    />
                    <label
                      htmlFor="employee-import-input"
                      className="cursor-pointer text-xs font-bold text-emerald-600 hover:underline block truncate px-2"
                    >
                      {importForm.data.file ? importForm.data.file.name : 'Klik di sini untuk memilih file CSV / Excel'}
                    </label>
                    <span className="text-[10px] sm:text-[11px] text-slate-400 block mt-1">Format: .csv, .xlsx (maks 5MB)</span>
                  </div>
                  {importForm.errors.file && (
                    <p className="mt-1 text-xs text-rose-600 font-bold">{importForm.errors.file}</p>
                  )}
                </div>

                <div className="flex items-center justify-end space-x-2 pt-2 border-t border-slate-200">
                  <button
                    type="button"
                    onClick={() => setIsImportOpen(false)}
                    className="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={importForm.processing || !importForm.data.file}
                    className="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 disabled:opacity-50 transition-all"
                  >
                    Proses Import Data
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

      </div>
    </AuthenticatedLayout>
  );
}
