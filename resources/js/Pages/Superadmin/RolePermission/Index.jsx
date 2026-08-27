import React, { useState, useMemo } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { motion, AnimatePresence } from 'framer-motion';
import { showAlert, showConfirm, showToast } from '@/Utils/swal';
import {
  ShieldCheck,
  Plus,
  Edit2,
  Trash2,
  UserCheck,
  CheckCircle2,
  Lock,
  Search,
  Key,
  ShieldAlert,
  Users,
  Sliders,
  Check,
  X,
  Sparkles,
  Info,
  LayoutDashboard,
  FileText,
  CheckSquare,
  ChevronDown,
  ChevronRight,
  Eye,
  Settings,
  Filter,
  Grid,
  AlertTriangle,
  User,
  MoreVertical,
  Receipt,
  Activity,
  Building,
  FileSpreadsheet
} from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import InstantPagination from "@/components/ui/instant-pagination";

// Icon mapper for categories
const categoryIcons = {
  general: LayoutDashboard,
  leave: FileText,
  payslips: Receipt,
  approval: CheckSquare,
  monitoring: Activity,
  hrd: Users,
  departments: Building,
  hrd_payslips: Receipt,
  superadmin: ShieldCheck,
};

export default function RolePermissionIndex({
  roles = [],
  permissions = [],
  employees = [],
  stats = {},
  permission_catalog = {},
  role_presets = {}
}) {
  const [activeTab, setActiveTab] = useState('roles'); // 'roles' | 'matrix' | 'employees' | 'permissions'
  const [searchEmployee, setSearchEmployee] = useState('');
  const [filterRole, setFilterRole] = useState('all');
  const [searchPermission, setSearchPermission] = useState('');

  // Modals
  const [isCreateRoleOpen, setIsCreateRoleOpen] = useState(false);
  const [editingRole, setEditingRole] = useState(null);
  const [assigningUser, setAssigningUser] = useState(null);
  const [isCreatePermissionOpen, setIsCreatePermissionOpen] = useState(false);
  const [viewRoleDetail, setViewRoleDetail] = useState(null);

  // Form for Creating Role
  const createRoleForm = useForm({
    name: '',
    permissions: [],
  });

  // Form for Editing Role
  const editRoleForm = useForm({
    name: '',
    permissions: [],
  });

  // Form for Assigning User Role
  const assignUserForm = useForm({
    role: '',
    permissions: [],
  });

  // Form for Creating Permission
  const createPermForm = useForm({
    name: '',
  });

  const isSystemRole = (roleName) => ['superadmin', 'admin', 'manager', 'employee'].includes(roleName?.toLowerCase());

  // Flattened permission lookup map with human-friendly label and description
  const permissionMeta = useMemo(() => {
    const map = {};
    Object.values(permission_catalog).forEach((cat) => {
      cat.permissions?.forEach((p) => {
        map[p.name] = {
          ...p,
          category_name: cat.category_name,
          category_desc: cat.category_desc,
          badge_color: cat.badge_color,
          icon: cat.icon,
        };
      });
    });
    return map;
  }, [permission_catalog]);

  // Apply Role Preset Template to form
  const applyPreset = (formInstance, presetKey) => {
    const preset = role_presets[presetKey];
    if (preset) {
      formInstance.setData('permissions', [...preset.permissions]);
      showToast(`Template '${preset.name}' diterapkan!`, 'success');
    }
  };

  // Select all or clear all permissions in a specific category
  const toggleCategoryPermissions = (formInstance, categoryKey) => {
    const cat = permission_catalog[categoryKey];
    if (!cat) return;
    const catPermNames = cat.permissions.map((p) => p.name);
    const currentPerms = formInstance.data.permissions || [];
    const allSelected = catPermNames.every((p) => currentPerms.includes(p));

    if (allSelected) {
      // Uncheck all in this category
      formInstance.setData(
        'permissions',
        currentPerms.filter((p) => !catPermNames.includes(p))
      );
    } else {
      // Check all in this category
      const merged = Array.from(new Set([...currentPerms, ...catPermNames]));
      formInstance.setData('permissions', merged);
    }
  };

  // Toggle all permissions across the whole system
  const toggleAllPermissions = (formInstance) => {
    const allPermNames = permissions.map((p) => p.name);
    const currentPerms = formInstance.data.permissions || [];
    if (currentPerms.length === allPermNames.length) {
      formInstance.setData('permissions', []);
    } else {
      formInstance.setData('permissions', allPermNames);
    }
  };

  // Handlers
  const handleCreateRole = async (e) => {
    e.preventDefault();
    createRoleForm.post(route('superadmin.roles.store'), {
      onSuccess: () => {
        setIsCreateRoleOpen(false);
        createRoleForm.reset();
        showToast('Role baru berhasil dibuat!', 'success');
      },
      onError: (errs) => {
        showAlert({ title: 'Gagal Membuat Role', text: Object.values(errs)[0] || 'Periksa kembali data Anda.', icon: 'error' });
      }
    });
  };

  const openEditRoleModal = (role) => {
    setEditingRole(role);
    editRoleForm.setData({
      name: role.name,
      permissions: role.permissions || [],
    });
  };

  const handleUpdateRole = async (e) => {
    e.preventDefault();
    if (!editingRole) return;
    editRoleForm.put(route('superadmin.roles.update', editingRole.id), {
      onSuccess: () => {
        setEditingRole(null);
        editRoleForm.reset();
        showToast(`Hak akses role '${editingRole.name}' berhasil diperbarui!`, 'success');
      },
      onError: (errs) => {
        showAlert({ title: 'Gagal Memperbarui Role', text: Object.values(errs)[0] || 'Periksa kembali data Anda.', icon: 'error' });
      }
    });
  };

  const handleDeleteRole = async (role) => {
    const confirmed = await showConfirm({
      title: `Hapus Role '${role.name}'?`,
      text: `Apakah Anda yakin ingin menghapus role '${role.name}'? Karyawan yang menggunakan role ini akan dikembalikan ke role standar.`,
      icon: 'warning',
      confirmText: 'Ya, Hapus Role',
      cancelText: 'Batal'
    });

    if (confirmed) {
      router.delete(route('superadmin.roles.destroy', role.id), {
        onSuccess: () => showToast(`Role '${role.name}' berhasil dihapus.`, 'success'),
      });
    }
  };

  const openAssignUserModal = (emp) => {
    setAssigningUser(emp);
    assignUserForm.setData({
      role: emp.role || 'employee',
      permissions: emp.permissions || [],
    });
  };

  const handleAssignUserRole = (e) => {
    e.preventDefault();
    if (!assigningUser) return;
    assignUserForm.post(route('superadmin.users.assign-role', assigningUser.id), {
      onSuccess: () => {
        setAssigningUser(null);
        assignUserForm.reset();
        showToast(`Hak akses untuk ${assigningUser.name} berhasil diperbarui!`, 'success');
      },
      onError: (errs) => {
        showAlert({ title: 'Gagal Mengubah Akses', text: Object.values(errs)[0] || 'Terjadi kesalahan.', icon: 'error' });
      }
    });
  };

  const handleCreatePermission = (e) => {
    e.preventDefault();
    createPermForm.post(route('superadmin.permissions.store'), {
      onSuccess: () => {
        setIsCreatePermissionOpen(false);
        createPermForm.reset();
        showToast('Permission baru berhasil ditambahkan!', 'success');
      },
      onError: (errs) => {
        showAlert({ title: 'Gagal Menambah Permission', text: Object.values(errs)[0] || 'Gagal menyimpan.', icon: 'error' });
      }
    });
  };

  // Filtered employees list
  const filteredEmployees = employees.filter((emp) => {
    const matchSearch =
      emp.name.toLowerCase().includes(searchEmployee.toLowerCase()) ||
      emp.nik.toLowerCase().includes(searchEmployee.toLowerCase()) ||
      emp.email.toLowerCase().includes(searchEmployee.toLowerCase()) ||
      emp.department_name.toLowerCase().includes(searchEmployee.toLowerCase());

    const matchRole = filterRole === 'all' || emp.role === filterRole || emp.roles?.includes(filterRole);

    return matchSearch && matchRole;
  });

  // Instant Client-Side Pagination for Employee Role Assignment
  const [employeePage, setEmployeePage] = useState(1);
  const [employeePageSize, setEmployeePageSize] = useState(10);

  const paginatedEmployees = useMemo(() => {
    const start = (employeePage - 1) * employeePageSize;
    return filteredEmployees.slice(start, start + employeePageSize);
  }, [filteredEmployees, employeePage, employeePageSize]);

  React.useEffect(() => {
    setEmployeePage(1);
  }, [searchEmployee, filterRole]);

  return (
    <AuthenticatedLayout title="Manajemen Role & Hak Akses">
      <Head title="Superadmin - Spatie Role & Permission" />

      <div className="w-full space-y-6">

        {/* Page Header Hero Banner */}
        <div className="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#0FA172] to-[#1CB67C] text-white shadow-lg shadow-emerald-600/20 relative overflow-hidden">
          <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none" />

          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/20 border border-white/30 text-white text-xs font-bold uppercase tracking-wider mb-3 backdrop-blur-md">
                <Sparkles size={14} className="text-emerald-100" />
                <span>Superadmin Control Center</span>
              </div>
              <h1 className="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Manajemen Role & Hak Akses
              </h1>
              <p className="text-sm text-emerald-50 mt-1 max-w-3xl font-medium leading-relaxed">
                Kelola hak akses menu, kewenangan persetujuan, dan konfigurasi wewenang setiap peran karyawan secara mudah dan interaktif.
              </p>
            </div>

            <div className="flex flex-wrap gap-2.5 shrink-0">
              <button
                type="button"
                onClick={() => {
                  createRoleForm.reset();
                  setIsCreateRoleOpen(true);
                }}
                className="px-4 py-2.5 rounded-2xl bg-white text-emerald-800 hover:bg-emerald-50 font-black text-xs sm:text-sm shadow-md flex items-center space-x-2 transition-all active:scale-95"
              >
                <Plus size={18} className="text-emerald-700" />
                <span>Tambah Role Baru</span>
              </button>

              <button
                type="button"
                onClick={() => setIsCreatePermissionOpen(true)}
                className="px-4 py-2.5 rounded-2xl bg-white/20 hover:bg-white/30 text-white font-bold text-xs sm:text-sm border border-white/30 flex items-center space-x-2 transition-all backdrop-blur-md active:scale-95"
              >
                <Key size={18} />
                <span>Permission Baru</span>
              </button>
            </div>
          </div>
        </div>

        {/* Stats Row */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
            <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
              <ShieldCheck size={24} />
            </div>
            <div>
              <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Roles</p>
              <h3 className="text-2xl font-black text-slate-900">{stats.total_roles || roles.length} Role</h3>
            </div>
          </div>

          <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
            <div className="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
              <Key size={24} />
            </div>
            <div>
              <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Permissions</p>
              <h3 className="text-2xl font-black text-slate-900">{stats.total_permissions || permissions.length} Hak Akses</h3>
            </div>
          </div>

          <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center space-x-4">
            <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
              <Users size={24} />
            </div>
            <div>
              <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Karyawan</p>
              <h3 className="text-2xl font-black text-slate-900">{stats.total_employees || employees.length} Pengguna</h3>
            </div>
          </div>
        </div>

        {/* Main Tab Navigation */}
        <div className="w-full border-b border-slate-200 flex items-center space-x-4 overflow-x-auto no-scrollbar scroll-smooth -mx-4 px-4 sm:mx-0 sm:px-0">
          <button
            type="button"
            onClick={() => setActiveTab('roles')}
            className={`pb-3 pt-1 px-4 text-sm font-extrabold flex items-center space-x-2 border-b-2 transition-all whitespace-nowrap ${
              activeTab === 'roles'
                ? 'border-emerald-600 text-emerald-600'
                : 'border-transparent text-slate-500 hover:text-slate-900'
            }`}
          >
            <Grid size={18} />
            <span>Kartu Role & Hak Akses ({roles.length})</span>
          </button>

          <button
            type="button"
            onClick={() => setActiveTab('matrix')}
            className={`pb-3 pt-1 px-4 text-sm font-extrabold flex items-center space-x-2 border-b-2 transition-all whitespace-nowrap ${
              activeTab === 'matrix'
                ? 'border-emerald-600 text-emerald-600'
                : 'border-transparent text-slate-500 hover:text-slate-900'
            }`}
          >
            <Sliders size={18} />
            <span>Matriks Perbandingan</span>
          </button>

          <button
            type="button"
            onClick={() => setActiveTab('employees')}
            className={`pb-3 pt-1 px-4 text-sm font-extrabold flex items-center space-x-2 border-b-2 transition-all whitespace-nowrap ${
              activeTab === 'employees'
                ? 'border-emerald-600 text-emerald-600'
                : 'border-transparent text-slate-500 hover:text-slate-900'
            }`}
          >
            <UserCheck size={18} />
            <span>Akses Per Karyawan ({employees.length})</span>
          </button>

          <button
            type="button"
            onClick={() => setActiveTab('permissions')}
            className={`pb-3 pt-1 px-4 text-sm font-extrabold flex items-center space-x-2 border-b-2 transition-all whitespace-nowrap ${
              activeTab === 'permissions'
                ? 'border-emerald-600 text-emerald-600'
                : 'border-transparent text-slate-500 hover:text-slate-900'
            }`}
          >
            <Key size={18} />
            <span>Katalog Permissions ({permissions.length})</span>
          </button>
        </div>

        {/* TAB 1: ROLES CARDS VIEW */}
        {activeTab === 'roles' && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-base font-extrabold text-slate-900">Daftar Peran (Roles)</h3>
                <p className="text-xs text-slate-500">Klik "Atur Hak Akses" pada role untuk menyesuaikan izin menu yang diizinkan.</p>
              </div>
              <button
                type="button"
                onClick={() => {
                  createRoleForm.reset();
                  setIsCreateRoleOpen(true);
                }}
                className="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm flex items-center space-x-1.5 transition-all"
              >
                <Plus size={16} />
                <span>Buat Role Baru</span>
              </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
              {roles.map((role) => {
                const isCore = isSystemRole(role.name);
                const roleUserCount = role.users_count || 0;
                const permCount = role.permissions.length;

                return (
                  <motion.div
                    key={role.id}
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.2 }}
                    className="rounded-3xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-4"
                  >
                    <div>
                      {/* Header Role */}
                      <div className="flex items-start justify-between">
                        <div className="flex items-center space-x-3">
                          <div className={`w-11 h-11 rounded-2xl flex items-center justify-center font-black text-base uppercase shadow-sm ${
                            role.name === 'superadmin' ? 'bg-amber-100 text-amber-800' :
                            role.name === 'admin' ? 'bg-purple-100 text-purple-800' :
                            role.name === 'manager' ? 'bg-blue-100 text-blue-800' :
                            'bg-emerald-100 text-emerald-800'
                          }`}>
                            {role.name.substring(0, 2)}
                          </div>
                          <div>
                            <div className="flex items-center space-x-2">
                              <h3 className="text-base font-black text-slate-900 capitalize">{role.name}</h3>
                              {isCore ? (
                                <span className="px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-[10px] font-extrabold text-slate-600 uppercase">
                                  System Core
                                </span>
                              ) : (
                                <span className="px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-[10px] font-extrabold text-emerald-700 uppercase">
                                  Custom
                                </span>
                              )}
                            </div>
                            <p className="text-xs text-slate-500 font-semibold mt-0.5 flex items-center space-x-1">
                              <Users size={13} className="text-slate-400" />
                              <span>{roleUserCount} Karyawan Terdaftar</span>
                            </p>
                          </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center space-x-1">
                          <button
                            type="button"
                            onClick={() => openEditRoleModal(role)}
                            className="p-2 rounded-xl text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 transition-colors"
                            title="Edit Hak Akses Role"
                          >
                            <Edit2 size={16} />
                          </button>
                          {!isCore && (
                            <button
                              type="button"
                              onClick={() => handleDeleteRole(role)}
                              className="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                              title="Hapus Role"
                            >
                              <Trash2 size={16} />
                            </button>
                          )}
                        </div>
                      </div>

                      {/* Active Permissions Summary */}
                      <div className="mt-4 space-y-2">
                        <div className="flex items-center justify-between text-xs font-bold text-slate-600">
                          <span>Hak Akses Menu ({permCount} Izin Aktif)</span>
                          <span className="text-[11px] text-emerald-700">
                            {Math.round((permCount / (permissions.length || 1)) * 100)}% Akses
                          </span>
                        </div>

                        <div className="flex flex-wrap gap-1.5 p-3 rounded-2xl bg-slate-50 border border-slate-200/70 max-h-36 overflow-y-auto">
                          {permCount === 0 ? (
                            <span className="text-xs text-slate-400 italic">Belum ada hak akses yang diaktifkan.</span>
                          ) : (
                            role.permissions.map((permName) => {
                              const meta = permissionMeta[permName];
                              return (
                                <span
                                  key={permName}
                                  className="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-white text-slate-800 border border-slate-200/80 text-[11px] font-bold shadow-2xs"
                                  title={meta?.description || permName}
                                >
                                  <Check size={12} className="text-emerald-600 shrink-0" />
                                  <span>{meta?.label || permName}</span>
                                </span>
                              );
                            })
                          )}
                        </div>
                      </div>
                    </div>

                    {/* Card Footer */}
                    <div className="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                      <span className="text-slate-400 font-medium">Guard: <strong className="text-slate-700">{role.guard_name}</strong></span>
                      <button
                        type="button"
                        onClick={() => openEditRoleModal(role)}
                        className="px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-extrabold text-xs flex items-center space-x-1 transition-colors"
                      >
                        <Sliders size={13} />
                        <span>Atur Hak Akses</span>
                      </button>
                    </div>
                  </motion.div>
                );
              })}
            </div>
          </div>
        )}

        {/* TAB 2: PERMISSION MATRIX VIEW */}
        {activeTab === 'matrix' && (
          <div className="p-4 sm:p-6 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-4">
            <div>
              <h3 className="text-base font-extrabold text-slate-900">Matriks Perbandingan Hak Akses</h3>
              <p className="text-xs text-slate-500">Tabel perbandingan menyeluruh antara peran dan hak akses fitur sistem.</p>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  <tr className="border-b border-slate-200 bg-slate-50">
                    <th className="py-3.5 px-4 font-bold text-slate-600 uppercase">Fitur & Hak Akses</th>
                    {roles.map((r) => (
                      <th key={r.id} className="py-3.5 px-3 font-extrabold text-slate-900 text-center uppercase whitespace-nowrap">
                        <div className="flex flex-col items-center">
                          <span>{r.name}</span>
                          <span className="text-[10px] text-slate-400 font-normal lowercase">({r.permissions.length} izin)</span>
                        </div>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {Object.entries(permission_catalog).map(([categoryKey, cat]) => (
                    <React.Fragment key={categoryKey}>
                      <tr className="bg-slate-100/60 font-black text-slate-800">
                        <td colSpan={roles.length + 1} className="py-2.5 px-4 text-xs">
                          {cat.category_name} &bull; <span className="font-normal text-slate-500">{cat.category_desc}</span>
                        </td>
                      </tr>
                      {cat.permissions.map((perm) => (
                        <tr key={perm.name} className="hover:bg-slate-50/80 transition-colors">
                          <td className="py-3 px-4">
                            <div className="font-bold text-slate-900">{perm.label}</div>
                            <div className="text-[11px] text-slate-500">{perm.description}</div>
                          </td>
                          {roles.map((r) => {
                            const hasPerm = r.permissions.includes(perm.name);
                            return (
                              <td key={r.id} className="py-3 px-3 text-center">
                                {hasPerm ? (
                                  <div className="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto shadow-2xs">
                                    <Check size={14} />
                                  </div>
                                ) : (
                                  <div className="w-6 h-6 rounded-full bg-slate-100 text-slate-300 flex items-center justify-center mx-auto">
                                    <X size={13} />
                                  </div>
                                )}
                              </td>
                            );
                          })}
                        </tr>
                      ))}
                    </React.Fragment>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* TAB 3: EMPLOYEE ACCESS CONTROL */}
        {activeTab === 'employees' && (
          <div className="space-y-4">
            {/* Filter Bar */}
            <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
              <div className="relative w-full sm:w-80">
                <Search size={18} className="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  value={searchEmployee}
                  onChange={(e) => setSearchEmployee(e.target.value)}
                  placeholder="Cari nama, NIK, atau Departemen..."
                  className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 outline-none"
                />
              </div>

              <div className="flex items-center space-x-2 w-full sm:w-auto">
                <Sliders size={16} className="text-slate-500 shrink-0" />
                <span className="text-xs font-bold text-slate-600 shrink-0">Filter Role:</span>
                <div className="w-full sm:w-[190px]">
                  <Select
                    value={filterRole}
                    onValueChange={(val) => setFilterRole(val)}
                  >
                    <SelectTrigger className="w-full bg-slate-50 border-slate-200 text-xs font-bold text-slate-800 rounded-xl h-9">
                      <SelectValue placeholder="Semua Role" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Semua Role ({employees.length})</SelectItem>
                      {roles.map((r) => (
                        <SelectItem key={r.id} value={r.name}>
                          Role {r.name.toUpperCase()}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            {/* Employee Access Control List */}
            <div className="rounded-3xl bg-white border border-slate-200 shadow-xs overflow-hidden">
              {filteredEmployees.length === 0 ? (
                <div className="py-12 text-center text-slate-400 font-semibold">
                  Tidak ada data karyawan yang cocok dengan pencarian.
                </div>
              ) : (
                <div>
                  {/* MOBILE CARD VIEW (< md) */}
                  <div className="block md:hidden divide-y divide-slate-100">
                    {paginatedEmployees.map((emp) => (
                      <div key={emp.id} className="p-4 space-y-3">
                        <div className="flex items-center justify-between">
                          <div className="flex items-center space-x-3 min-w-0">
                            <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold flex items-center justify-center text-sm shadow-sm shrink-0">
                              {emp.name.charAt(0)}
                            </div>
                            <div className="min-w-0">
                              <h4 className="font-extrabold text-slate-900 text-sm truncate">{emp.name}</h4>
                              <p className="text-[11px] text-slate-500 font-medium truncate">{emp.nik || 'NIK: -'} &bull; {emp.email}</p>
                            </div>
                          </div>
                          <span className={`inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-black capitalize shrink-0 ${
                            emp.role === 'superadmin' ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                            emp.role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-300' :
                            emp.role === 'manager' ? 'bg-blue-100 text-blue-800 border border-blue-300' :
                            'bg-emerald-100 text-emerald-800 border border-emerald-300'
                          }`}>
                            <ShieldCheck size={12} />
                            <span>{emp.role || 'employee'}</span>
                          </span>
                        </div>

                        <div className="grid grid-cols-2 gap-2 text-xs pt-1">
                          <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 min-w-0">
                            <span className="text-[10px] text-slate-400 font-bold uppercase block">Departemen</span>
                            <span className="font-bold text-slate-900 truncate block">{emp.department_name}</span>
                          </div>

                          <div className="bg-slate-50 p-2.5 rounded-xl border border-slate-200/60 min-w-0">
                            <span className="text-[10px] text-slate-400 font-bold uppercase block">Hak Akses Khusus</span>
                            <span className="font-semibold text-slate-700 truncate block">
                              {emp.permissions.length === 0 ? 'Sesuai Role' : `${emp.permissions.length} Custom`}
                            </span>
                          </div>
                        </div>

                        <div className="flex items-center justify-end pt-1 border-t border-slate-100">
                          <button
                            type="button"
                            onClick={() => openAssignUserModal(emp)}
                            className="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold text-xs border border-slate-200 hover:border-emerald-300 transition-all inline-flex items-center space-x-1.5"
                          >
                            <Edit2 size={14} />
                            <span>Ubah Role / Hak Akses</span>
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>

                  {/* DESKTOP TABLE VIEW (>= md) */}
                  <div className="hidden md:block overflow-x-auto">
                    <table className="w-full text-left text-sm">
                      <thead className="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                          <th className="py-3.5 px-6">Karyawan</th>
                          <th className="py-3.5 px-6">Departemen</th>
                          <th className="py-3.5 px-6">Role Aktif</th>
                          <th className="py-3.5 px-6">Hak Akses Khusus (Direct)</th>
                          <th className="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-200">
                        {paginatedEmployees.map((emp) => (
                          <tr key={emp.id} className="hover:bg-slate-50/80 transition-colors">
                            <td className="py-4 px-6">
                              <div className="flex items-center space-x-3">
                                <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                                  {emp.name.charAt(0)}
                                </div>
                                <div>
                                  <h4 className="font-bold text-slate-900">{emp.name}</h4>
                                  <p className="text-xs text-slate-500">{emp.nik || 'NIK: -'} &bull; {emp.email}</p>
                                </div>
                              </div>
                            </td>

                            <td className="py-4 px-6 font-semibold text-slate-700">
                              {emp.department_name}
                            </td>

                            <td className="py-4 px-6">
                              <span className={`inline-flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-black capitalize ${
                                emp.role === 'superadmin' ? 'bg-amber-100 text-amber-800 border border-amber-300' :
                                emp.role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-300' :
                                emp.role === 'manager' ? 'bg-blue-100 text-blue-800 border border-blue-300' :
                                'bg-emerald-100 text-emerald-800 border border-emerald-300'
                              }`}>
                                <ShieldCheck size={14} />
                                <span>{emp.role || 'employee'}</span>
                              </span>
                            </td>

                            <td className="py-4 px-6">
                              <div className="flex flex-wrap gap-1 max-w-md">
                                {emp.permissions.length === 0 ? (
                                  <span className="text-xs text-slate-400 italic">Mengikuti izin role standar</span>
                                ) : (
                                  emp.permissions.slice(0, 3).map((p) => {
                                    const meta = permissionMeta[p];
                                    return (
                                      <span key={p} className="px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 text-[11px] font-bold border border-emerald-200">
                                        {meta?.label || p}
                                      </span>
                                    );
                                  })
                                )}
                                {emp.permissions.length > 3 && (
                                  <span className="px-1.5 py-0.5 rounded bg-slate-200 text-slate-800 text-[10px] font-black">
                                    +{emp.permissions.length - 3} lainnya
                                  </span>
                                )}
                              </div>
                            </td>

                            <td className="py-4 px-6 text-right">
                              <button
                                type="button"
                                onClick={() => openAssignUserModal(emp)}
                                className="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold text-xs border border-slate-200 hover:border-emerald-300 transition-all inline-flex items-center space-x-1.5"
                              >
                                <Edit2 size={14} />
                                <span>Ubah Akses</span>
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>

                  {/* Instant Zero-Lag Pagination */}
                  <InstantPagination
                    currentPage={employeePage}
                    totalItems={filteredEmployees.length}
                    pageSize={employeePageSize}
                    onPageChange={setEmployeePage}
                    onPageSizeChange={(newSize) => {
                      setEmployeePageSize(newSize);
                      setEmployeePage(1);
                    }}
                    itemName="karyawan"
                  />
                </div>
              )}
            </div>
          </div>
        )}

        {/* TAB 4: PERMISSIONS CATALOG */}
        {activeTab === 'permissions' && (
          <div className="p-6 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-6">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
              <div>
                <h3 className="text-lg font-black text-slate-900">Katalog Hak Akses (Permissions) Sistem</h3>
                <p className="text-xs text-slate-500">Daftar lengkap fitur dan izin akses yang terdaftar di database Spatie.</p>
              </div>

              <button
                type="button"
                onClick={() => setIsCreatePermissionOpen(true)}
                className="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm flex items-center space-x-1.5"
              >
                <Plus size={16} />
                <span>Permission Baru</span>
              </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {Object.entries(permission_catalog).map(([categoryKey, cat]) => {
                const IconComponent = categoryIcons[categoryKey] || Key;

                return (
                  <div key={categoryKey} className="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                    <div className="flex items-center space-x-2.5">
                      <div className="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                        <IconComponent size={18} />
                      </div>
                      <div>
                        <h4 className="font-extrabold text-slate-900 text-sm">{cat.category_name}</h4>
                        <p className="text-[11px] text-slate-500">{cat.category_desc}</p>
                      </div>
                    </div>

                    <div className="space-y-2 pt-1">
                      {cat.permissions.map((perm) => (
                        <div
                          key={perm.name}
                          className="p-3 rounded-xl bg-white border border-slate-200/80 flex items-start justify-between gap-3 text-xs"
                        >
                          <div>
                            <span className="font-bold text-slate-900 block">{perm.label}</span>
                            <span className="text-[11px] text-slate-500 block mt-0.5">{perm.description}</span>
                            <span className="font-mono text-[10px] text-slate-400 block mt-1">Slug: {perm.name}</span>
                          </div>
                          <span className="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 shrink-0">
                            Aktif
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}

      </div>

      {/* ========================================================================= */}
      {/* MODAL 1: CREATE / EDIT ROLE (USER FRIENDLY WIZARD WITH PRESETS & TOGGLES) */}
      {/* ========================================================================= */}
      <AnimatePresence>
        {(isCreateRoleOpen || editingRole) && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-slate-950/60"
              onClick={() => {
                setIsCreateRoleOpen(false);
                setEditingRole(null);
              }}
            />

            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              className="relative z-10 w-full max-w-3xl p-5 sm:p-7 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-5 my-6 max-h-[90vh] flex flex-col transform-gpu"
            >
              {/* Header */}
              <div className="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                    <ShieldCheck size={22} />
                  </div>
                  <div>
                    <h3 className="text-base sm:text-lg font-black text-slate-900">
                      {editingRole ? `Atur Hak Akses Role: ${editingRole.name}` : 'Buat Role Baru'}
                    </h3>
                    <p className="text-xs text-slate-500">
                      {editingRole
                        ? 'Pilih atau sesuaikan hak akses menu untuk role ini dengan template cepat atau centang manual.'
                        : 'Beri nama role dan pilih hak akses fitur yang diizinkan.'}
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    setIsCreateRoleOpen(false);
                    setEditingRole(null);
                  }}
                  className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800"
                >
                  <X size={18} />
                </button>
              </div>

              {/* Form Body with Scroll */}
              <div className="space-y-5 overflow-y-auto flex-1 pr-1">
                {/* Role Name Input */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Nama Role <span className="text-rose-600">*</span>
                  </label>
                  <input
                    type="text"
                    value={editingRole ? editRoleForm.data.name : createRoleForm.data.name}
                    disabled={editingRole && isSystemRole(editingRole.name)}
                    onChange={(e) => {
                      if (editingRole) {
                        editRoleForm.setData('name', e.target.value);
                      } else {
                        createRoleForm.setData('name', e.target.value);
                      }
                    }}
                    placeholder="contoh: supervisor_lapangan, hrd_staff, finance_admin"
                    className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-bold text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none disabled:opacity-60"
                    required
                  />
                  {editingRole && isSystemRole(editingRole.name) && (
                    <p className="text-[11px] text-slate-400 mt-1">Nama role sistem bawaan tidak dapat diubah, namun izinnya dapat disesuaikan.</p>
                  )}
                </div>

                {/* Quick Presets Buttons (1-Click Template) */}
                <div className="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200/80 space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-extrabold text-emerald-950 flex items-center space-x-1.5">
                      <Sparkles size={14} className="text-emerald-600" />
                      <span>Template Cepat (1-Klik Terapkan):</span>
                    </span>
                    <button
                      type="button"
                      onClick={() => toggleAllPermissions(editingRole ? editRoleForm : createRoleForm)}
                      className="text-[11px] font-bold text-emerald-700 hover:underline"
                    >
                      Pilih / Batal Semua
                    </button>
                  </div>

                  <div className="flex flex-wrap gap-2 pt-1">
                    <button
                      type="button"
                      onClick={() => applyPreset(editingRole ? editRoleForm : createRoleForm, 'employee')}
                      className="px-3 py-1.5 rounded-xl bg-white hover:bg-emerald-100 text-emerald-900 font-bold text-xs border border-emerald-200 shadow-2xs transition-all active:scale-95 flex items-center space-x-1"
                    >
                      <User size={13} className="text-emerald-600" />
                      <span>Karyawan Biasa</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => applyPreset(editingRole ? editRoleForm : createRoleForm, 'manager')}
                      className="px-3 py-1.5 rounded-xl bg-white hover:bg-blue-100 text-blue-900 font-bold text-xs border border-blue-200 shadow-2xs transition-all active:scale-95 flex items-center space-x-1"
                    >
                      <CheckSquare size={13} className="text-blue-600" />
                      <span>Atasan / Manager</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => applyPreset(editingRole ? editRoleForm : createRoleForm, 'admin')}
                      className="px-3 py-1.5 rounded-xl bg-white hover:bg-purple-100 text-purple-900 font-bold text-xs border border-purple-200 shadow-2xs transition-all active:scale-95 flex items-center space-x-1"
                    >
                      <Users size={13} className="text-purple-600" />
                      <span>HRD / PGA Admin</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => applyPreset(editingRole ? editRoleForm : createRoleForm, 'superadmin')}
                      className="px-3 py-1.5 rounded-xl bg-white hover:bg-amber-100 text-amber-900 font-bold text-xs border border-amber-200 shadow-2xs transition-all active:scale-95 flex items-center space-x-1"
                    >
                      <ShieldCheck size={13} className="text-amber-600" />
                      <span>Superadmin (Full)</span>
                    </button>

                    <button
                      type="button"
                      onClick={() => {
                        const targetForm = editingRole ? editRoleForm : createRoleForm;
                        targetForm.setData('permissions', []);
                        showToast('Semua izin dikosongkan.', 'info');
                      }}
                      className="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-200 shadow-2xs transition-all active:scale-95 flex items-center space-x-1 ml-auto"
                    >
                      <X size={13} />
                      <span>Kosongkan</span>
                    </button>
                  </div>
                </div>

                {/* Categorized Permissions Accordions */}
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <label className="text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                      Pilih Hak Akses Fitur yang Diizinkan:
                    </label>
                    <span className="text-xs font-bold text-emerald-700">
                      {(editingRole ? editRoleForm.data.permissions : createRoleForm.data.permissions).length} / {permissions.length} Terpilih
                    </span>
                  </div>

                  {Object.entries(permission_catalog).map(([categoryKey, cat]) => {
                    const targetForm = editingRole ? editRoleForm : createRoleForm;
                    const selectedPerms = targetForm.data.permissions || [];
                    const catPermNames = cat.permissions.map((p) => p.name);
                    const selectedCount = catPermNames.filter((name) => selectedPerms.includes(name)).length;
                    const isAllSelected = selectedCount === catPermNames.length;
                    const IconComponent = categoryIcons[categoryKey] || Key;

                    return (
                      <div key={categoryKey} className="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-2xs">
                        {/* Category Header */}
                        <div className="p-3.5 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between">
                          <div className="flex items-center space-x-2.5">
                            <div className="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                              <IconComponent size={15} />
                            </div>
                            <div>
                              <h4 className="font-extrabold text-xs text-slate-900">{cat.category_name}</h4>
                              <p className="text-[10px] text-slate-500">{cat.category_desc}</p>
                            </div>
                          </div>

                          <div className="flex items-center space-x-2">
                            <span className="text-[11px] font-bold text-slate-600 bg-white px-2 py-0.5 rounded-full border border-slate-200">
                              {selectedCount} / {catPermNames.length}
                            </span>
                            <button
                              type="button"
                              onClick={() => toggleCategoryPermissions(targetForm, categoryKey)}
                              className="text-xs font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1 rounded-lg border border-emerald-200 transition-colors"
                            >
                              {isAllSelected ? 'Batal Semua' : 'Pilih Semua'}
                            </button>
                          </div>
                        </div>

                        {/* Permission Items in this Category */}
                        <div className="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                          {cat.permissions.map((perm) => {
                            const isChecked = selectedPerms.includes(perm.name);

                            return (
                              <div
                                key={perm.name}
                                onClick={() => {
                                  if (isChecked) {
                                    targetForm.setData(
                                      'permissions',
                                      selectedPerms.filter((p) => p !== perm.name)
                                    );
                                  } else {
                                    targetForm.setData('permissions', [...selectedPerms, perm.name]);
                                  }
                                }}
                                className={`p-3 rounded-xl border text-left flex items-start justify-between gap-3 cursor-pointer transition-all ${
                                  isChecked
                                    ? 'bg-emerald-50/80 border-emerald-400 shadow-2xs ring-1 ring-emerald-500/20'
                                    : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100'
                                }`}
                              >
                                <div className="min-w-0 flex-1">
                                  <span className="font-bold text-xs text-slate-900 block">{perm.label}</span>
                                  <span className="text-[11px] text-slate-500 font-normal block mt-0.5 leading-snug">
                                    {perm.description}
                                  </span>
                                </div>

                                <div className={`w-5 h-5 rounded-lg flex items-center justify-center shrink-0 mt-0.5 transition-colors ${
                                  isChecked ? 'bg-emerald-600 text-white' : 'border border-slate-300 bg-white'
                                }`}>
                                  {isChecked && <Check size={13} />}
                                </div>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Modal Footer */}
              <div className="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2 shrink-0">
                <button
                  type="button"
                  onClick={() => {
                    setIsCreateRoleOpen(false);
                    setEditingRole(null);
                  }}
                  className="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={editingRole ? handleUpdateRole : handleCreateRole}
                  disabled={editingRole ? editRoleForm.processing : createRoleForm.processing}
                  className="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition-all active:scale-95 disabled:opacity-50"
                >
                  {editingRole ? 'Simpan Perubahan Hak Akses' : 'Simpan Role Baru'}
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ========================================================================= */}
      {/* MODAL 2: ASSIGN USER ROLE & DIRECT PERMISSIONS                            */}
      {/* ========================================================================= */}
      <AnimatePresence>
        {assigningUser && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-slate-950/60"
              onClick={() => setAssigningUser(null)}
            />

            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              className="relative z-10 w-full max-w-lg p-5 sm:p-6 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 my-6 max-h-[88vh] flex flex-col transform-gpu"
            >
              <div className="flex items-center justify-between border-b border-slate-100 pb-3 shrink-0">
                <div className="flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                    <UserCheck size={22} />
                  </div>
                  <div>
                    <h3 className="text-base font-black text-slate-900">Ubah Role & Hak Akses Karyawan</h3>
                    <p className="text-xs text-slate-500">Tentukan role utama karyawan dan izin akses tambahannya.</p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setAssigningUser(null)}
                  className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800"
                >
                  <X size={18} />
                </button>
              </div>

              <div className="space-y-4 overflow-y-auto flex-1 pr-1">
                {/* Employee Info Card */}
                <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center space-x-3">
                  <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                    {assigningUser.name.charAt(0)}
                  </div>
                  <div>
                    <h4 className="font-extrabold text-sm text-slate-900">{assigningUser.name}</h4>
                    <p className="text-xs text-slate-500">{assigningUser.nik} &bull; {assigningUser.department_name}</p>
                  </div>
                </div>

                {/* Role Selector Cards */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Pilih Role Utama Karyawan <span className="text-rose-600">*</span>
                  </label>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    {roles.map((r) => {
                      const isSelected = assignUserForm.data.role === r.name;
                      return (
                        <div
                          key={r.id}
                          onClick={() => assignUserForm.setData('role', r.name)}
                          className={`p-3 rounded-2xl border text-left cursor-pointer transition-all ${
                            isSelected
                              ? 'bg-emerald-50 border-emerald-500 ring-2 ring-emerald-500/20 shadow-xs'
                              : 'bg-slate-50 border-slate-200 hover:bg-slate-100'
                          }`}
                        >
                          <div className="flex items-center justify-between">
                            <span className="font-extrabold text-xs text-slate-900 capitalize">{r.name}</span>
                            {isSelected && (
                              <div className="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center">
                                <Check size={12} />
                              </div>
                            )}
                          </div>
                          <p className="text-[10px] text-slate-500 mt-1">{r.permissions.length} Hak Akses Terpasang</p>
                        </div>
                      );
                    })}
                  </div>
                </div>

                {/* Additional Direct Permissions */}
                <div className="pt-2 border-t border-slate-100">
                  <div className="flex items-center justify-between mb-2">
                    <label className="text-xs font-bold text-slate-700 uppercase tracking-wider">
                      Hak Akses Khusus Tambahan (Opsional)
                    </label>
                    <span className="text-[11px] text-slate-400">Di luar role utama</span>
                  </div>

                  <div className="space-y-1.5 max-h-48 overflow-y-auto p-2.5 rounded-2xl bg-slate-50 border border-slate-200">
                    {permissions.map((perm) => {
                      const isChecked = assignUserForm.data.permissions.includes(perm.name);
                      const meta = permissionMeta[perm.name];

                      return (
                        <label
                          key={perm.id}
                          className="flex items-center space-x-2.5 p-2 rounded-xl hover:bg-white cursor-pointer transition-colors"
                        >
                          <input
                            type="checkbox"
                            checked={isChecked}
                            onChange={(e) => {
                              if (e.target.checked) {
                                assignUserForm.setData('permissions', [...assignUserForm.data.permissions, perm.name]);
                              } else {
                                assignUserForm.setData(
                                  'permissions',
                                  assignUserForm.data.permissions.filter((p) => p !== perm.name)
                                );
                              }
                            }}
                            className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 w-4 h-4"
                          />
                          <div className="min-w-0 flex-1">
                            <span className="text-xs font-bold text-slate-800 block">{meta?.label || perm.name}</span>
                            <span className="text-[10px] text-slate-400 block">{meta?.description || perm.name}</span>
                          </div>
                        </label>
                      );
                    })}
                  </div>
                </div>
              </div>

              {/* Modal Footer */}
              <div className="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2 shrink-0">
                <button
                  type="button"
                  onClick={() => setAssigningUser(null)}
                  className="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors"
                >
                  Batal
                </button>
                <button
                  type="button"
                  onClick={handleAssignUserRole}
                  disabled={assignUserForm.processing}
                  className="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition-all active:scale-95 disabled:opacity-50"
                >
                  Simpan Akses Karyawan
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* ========================================================================= */}
      {/* MODAL 3: CREATE PERMISSION                                                */}
      {/* ========================================================================= */}
      <AnimatePresence>
        {isCreatePermissionOpen && (
          <div className="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.15 }}
              className="fixed inset-0 bg-slate-950/60"
              onClick={() => setIsCreatePermissionOpen(false)}
            />

            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              transition={{ type: 'spring', stiffness: 380, damping: 30 }}
              className="relative z-10 w-full max-w-md p-6 rounded-3xl bg-white border border-slate-200 text-slate-900 shadow-2xl space-y-4 transform-gpu"
            >
              <div className="flex items-center justify-between border-b border-slate-100 pb-3">
                <div className="flex items-center space-x-2.5">
                  <div className="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                    <Key size={18} />
                  </div>
                  <div>
                    <h3 className="text-base font-extrabold text-slate-900">Tambah Permission Baru</h3>
                    <p className="text-xs text-slate-500">Daftarkan izin menu baru ke sistem Spatie.</p>
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setIsCreatePermissionOpen(false)}
                  className="p-1.5 rounded-xl bg-slate-100 text-slate-400 hover:text-slate-800"
                >
                  <X size={18} />
                </button>
              </div>

              <form onSubmit={handleCreatePermission} className="space-y-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Nama Permission (Slug) <span className="text-rose-600">*</span>
                  </label>
                  <input
                    type="text"
                    value={createPermForm.data.name}
                    onChange={(e) => createPermForm.setData('name', e.target.value)}
                    placeholder="contoh: export-payroll-reports, manage-vehicles"
                    className="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-mono text-xs focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 outline-none"
                    required
                  />
                  {createPermForm.errors.name && (
                    <p className="text-xs text-rose-600 font-bold mt-1">{createPermForm.errors.name}</p>
                  )}
                </div>

                <div className="pt-2 flex items-center justify-end space-x-2">
                  <button
                    type="button"
                    onClick={() => setIsCreatePermissionOpen(false)}
                    className="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs"
                  >
                    Batal
                  </button>
                  <button
                    type="submit"
                    disabled={createPermForm.processing}
                    className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 active:scale-95"
                  >
                    Simpan Permission
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </AuthenticatedLayout>
  );
}
