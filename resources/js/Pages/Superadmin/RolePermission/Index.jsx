import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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
  Info
} from 'lucide-react';
import { showConfirm, showToast } from '@/Utils/swal';

export default function RolePermissionIndex({ roles = [], permissions = [], employees = [], stats = {} }) {
  const [activeTab, setActiveTab] = useState('roles'); // 'roles' | 'employees' | 'permissions'
  const [searchEmployee, setSearchEmployee] = useState('');
  const [filterRole, setFilterRole] = useState('all');

  // Modals
  const [isCreateRoleOpen, setIsCreateRoleOpen] = useState(false);
  const [editingRole, setEditingRole] = useState(null);
  const [assigningUser, setAssigningUser] = useState(null);
  const [isCreatePermissionOpen, setIsCreatePermissionOpen] = useState(false);

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

  // Helper permission category groups
  const permissionCategories = {
    'General & Dashboard': ['view-dashboard'],
    'Pengajuan & Riwayat Cuti': ['create-leave-request', 'view-leave-history'],
    'Manager & Approval': ['manage-approvals'],
    'HRD & Data Karyawan': ['manage-employees', 'view-hrd-rekap', 'export-hrd-reports'],
    'Superadmin & Sistem': ['manage-roles', 'manage-system-settings'],
  };

  const getUncategorizedPermissions = () => {
    const known = Object.values(permissionCategories).flat();
    return permissions.filter(p => !known.includes(p.name));
  };

  // Handlers
  const handleCreateRole = (e) => {
    e.preventDefault();
    createRoleForm.post(route('superadmin.roles.store'), {
      onSuccess: () => {
        setIsCreateRoleOpen(false);
        createRoleForm.reset();
      },
    });
  };

  const openEditRoleModal = (role) => {
    setEditingRole(role);
    editRoleForm.setData({
      name: role.name,
      permissions: role.permissions || [],
    });
  };

  const handleUpdateRole = (e) => {
    e.preventDefault();
    if (!editingRole) return;
    editRoleForm.put(route('superadmin.roles.update', editingRole.id), {
      onSuccess: () => {
        setEditingRole(null);
        editRoleForm.reset();
      },
    });
  };

  const handleDeleteRole = (role) => {
    if (confirm(`Apakah Anda yakin ingin menghapus role '${role.name}'?`)) {
      router.delete(route('superadmin.roles.destroy', role.id));
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
      },
    });
  };

  const handleCreatePermission = (e) => {
    e.preventDefault();
    createPermForm.post(route('superadmin.permissions.store'), {
      onSuccess: () => {
        setIsCreatePermissionOpen(false);
        createPermForm.reset();
      },
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

  const isSystemRole = (roleName) => ['superadmin', 'admin', 'manager', 'employee'].includes(roleName);

  return (
    <AuthenticatedLayout title="Manajemen Role & Hak Akses">
      <Head title="Superadmin - Spatie Role & Permission" />

      <div className="w-full space-y-6">

        {/* Page Header matching Form SGIN Emerald Theme */}
        <div className="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#0FA172] to-[#1CB67C] text-white shadow-lg shadow-emerald-600/20 relative overflow-hidden">
          <div className="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-white/10 rounded-full blur-3xl pointer-events-none" />
          
          <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-white/20 border border-white/30 text-white text-xs font-bold uppercase tracking-wider mb-3 backdrop-blur-md">
                <Sparkles size={14} className="text-emerald-100" />
                <span>Superadmin Control Center</span>
              </div>
              <h1 className="text-2xl sm:text-3xl font-black tracking-tight text-white">
                Manajemen Role & Hak Akses Karyawan
              </h1>
              <p className="text-sm text-emerald-50 mt-1 max-w-4xl font-medium">
                Atur granular hak akses menu, role Spatie permission, dan kontrol kewenangan setiap karyawan dalam sistem Form SGIN.
              </p>
            </div>

            <div className="flex flex-wrap gap-2.5 shrink-0">
              <button
                onClick={() => setIsCreateRoleOpen(true)}
                className="px-4 py-2.5 rounded-2xl bg-white text-emerald-800 hover:bg-emerald-50 font-black text-xs sm:text-sm shadow-md flex items-center space-x-2 transition-all hover:scale-105"
              >
                <Plus size={18} className="text-emerald-700" />
                <span>Tambah Role Baru</span>
              </button>

              <button
                onClick={() => setIsCreatePermissionOpen(true)}
                className="px-4 py-2.5 rounded-2xl bg-white/20 hover:bg-white/30 text-white font-bold text-xs sm:text-sm border border-white/30 flex items-center space-x-2 transition-all backdrop-blur-md"
              >
                <Key size={18} />
                <span>Tambah Permission</span>
              </button>
            </div>
          </div>
        </div>

        {/* Stats Row */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center space-x-4">
            <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
              <ShieldCheck size={24} />
            </div>
            <div>
              <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Roles</p>
              <h3 className="text-2xl font-black text-slate-900">{stats.total_roles || roles.length}</h3>
            </div>
          </div>

          <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center space-x-4">
            <div className="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
              <Key size={24} />
            </div>
            <div>
              <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Permissions</p>
              <h3 className="text-2xl font-black text-slate-900">{stats.total_permissions || permissions.length}</h3>
            </div>
          </div>

          <div className="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center space-x-4">
            <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
              <Users size={24} />
            </div>
            <div>
              <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Karyawan</p>
              <h3 className="text-2xl font-black text-slate-900">{stats.total_employees || employees.length}</h3>
            </div>
          </div>
        </div>

        {/* Main Tab Navigation */}
        <div className="w-full border-b border-slate-200 flex items-center space-x-4 overflow-x-auto no-scrollbar scroll-smooth -mx-4 px-4 sm:mx-0 sm:px-0">
          <button
            onClick={() => setActiveTab('roles')}
            className={`pb-3 pt-1 px-4 text-sm font-extrabold flex items-center space-x-2 border-b-2 transition-all whitespace-nowrap ${
              activeTab === 'roles'
                ? 'border-emerald-600 text-emerald-600'
                : 'border-transparent text-slate-500 hover:text-slate-900'
            }`}
          >
            <ShieldCheck size={18} />
            <span>Matriks Role & Permissions ({roles.length})</span>
          </button>

          <button
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
            onClick={() => setActiveTab('permissions')}
            className={`pb-3 pt-1 px-4 text-sm font-extrabold flex items-center space-x-2 border-b-2 transition-all whitespace-nowrap ${
              activeTab === 'permissions'
                ? 'border-emerald-600 text-emerald-600'
                : 'border-transparent text-slate-500 hover:text-slate-900'
            }`}
          >
            <Key size={18} />
            <span>Daftar Permissions ({permissions.length})</span>
          </button>
        </div>

        {/* TAB 1: ROLES MATRIX */}
        {activeTab === 'roles' && (
          <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
            {roles.map((role) => (
              <div
                key={role.id}
                className="rounded-3xl bg-white border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center space-x-3">
                      <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 font-black flex items-center justify-center text-base uppercase">
                        {role.name.substring(0, 2)}
                      </div>
                      <div>
                        <div className="flex items-center space-x-2">
                          <h3 className="text-lg font-black text-slate-900 capitalize">{role.name}</h3>
                          {isSystemRole(role.name) && (
                            <span className="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-[10px] font-bold text-slate-600 uppercase">
                              System Core
                            </span>
                          )}
                        </div>
                        <p className="text-xs text-slate-500 font-semibold mt-0.5">
                          {role.users_count} karyawan memiliki role ini
                        </p>
                      </div>
                    </div>

                    <div className="flex items-center space-x-1">
                      <button
                        onClick={() => openEditRoleModal(role)}
                        className="p-2 rounded-xl text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition-all"
                        title="Edit Hak Akses Role"
                      >
                        <Edit2 size={18} />
                      </button>

                      {!isSystemRole(role.name) && (
                        <button
                          onClick={() => handleDeleteRole(role)}
                          className="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all"
                          title="Hapus Role Custom"
                        >
                          <Trash2 size={18} />
                        </button>
                      )}
                    </div>
                  </div>

                  <div className="space-y-2 mt-4">
                    <p className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                      Hak Akses Menu ({role.permissions.length} Permission)
                    </p>
                    <div className="flex flex-wrap gap-1.5 min-h-[60px] p-3 rounded-2xl bg-slate-50 border border-slate-200/80">
                      {role.permissions.length === 0 ? (
                        <span className="text-xs text-slate-400 italic">Belum ada permission yang ditempelkan</span>
                      ) : (
                        role.permissions.map((perm) => (
                          <span
                            key={perm}
                            className="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold"
                          >
                            <CheckCircle2 size={12} className="text-emerald-600" />
                            <span>{perm}</span>
                          </span>
                        ))
                      )}
                    </div>
                  </div>
                </div>

                <div className="pt-4 mt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                  <span>Guard: <strong className="text-slate-800">{role.guard_name}</strong></span>
                  <button
                    onClick={() => openEditRoleModal(role)}
                    className="text-emerald-600 font-bold hover:underline"
                  >
                    Atur Permission &rarr;
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* TAB 2: EMPLOYEE ACCESS CONTROL */}
        {activeTab === 'employees' && (
          <div className="space-y-4">
            {/* Filter Bar */}
            <div className="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
              <div className="relative w-full sm:w-80">
                <Search size={18} className="absolute left-3.5 top-3 text-slate-400" />
                <input
                  type="text"
                  value={searchEmployee}
                  onChange={(e) => setSearchEmployee(e.target.value)}
                  placeholder="Cari karyawan, NIK, atau Dept..."
                  className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm font-semibold focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 outline-none"
                />
              </div>

              <div className="flex items-center space-x-2 w-full sm:w-auto">
                <Sliders size={16} className="text-slate-500" />
                <span className="text-xs font-bold text-slate-600">Filter Role:</span>
                <select
                  value={filterRole}
                  onChange={(e) => setFilterRole(e.target.value)}
                  className="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 outline-none"
                >
                  <option value="all">Semua Role ({employees.length})</option>
                  {roles.map((r) => (
                    <option key={r.id} value={r.name}>
                      Role {r.name.toUpperCase()}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Employee Access Control List */}
            <div className="rounded-3xl bg-white border border-slate-200 shadow-sm overflow-hidden">
              {filteredEmployees.length === 0 ? (
                <div className="py-12 text-center text-slate-400 font-semibold">
                  Tidak ada data karyawan yang cocok dengan pencarian.
                </div>
              ) : (
                <div>
                  {/* MOBILE CARD VIEW (< md) */}
                  <div className="block md:hidden divide-y divide-slate-100">
                    {filteredEmployees.map((emp) => (
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
                            emp.role === 'superadmin'
                              ? 'bg-amber-100 text-amber-800 border border-amber-300'
                              : emp.role === 'admin'
                              ? 'bg-purple-100 text-purple-800 border border-purple-300'
                              : emp.role === 'manager'
                              ? 'bg-blue-100 text-blue-800 border border-blue-300'
                              : 'bg-emerald-100 text-emerald-800 border border-emerald-300'
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
                            <span className="text-[10px] text-slate-400 font-bold uppercase block">Direct Permissions</span>
                            <span className="font-semibold text-slate-700 truncate block">
                              {emp.permissions.length === 0 ? 'Ikut Role' : `${emp.permissions.length} Khusus`}
                            </span>
                          </div>
                        </div>

                        <div className="flex items-center justify-end pt-1 border-t border-slate-100">
                          <button
                            onClick={() => openAssignUserModal(emp)}
                            className="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold text-xs border border-slate-200 hover:border-emerald-300 transition-all inline-flex items-center space-x-1.5"
                          >
                            <Edit2 size={14} />
                            <span>Ubah Hak Akses</span>
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
                          <th className="py-3.5 px-6">Role Spatie Active</th>
                          <th className="py-3.5 px-6">Direct Permissions</th>
                          <th className="py-3.5 px-6 text-right">Aksi</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-200">
                        {filteredEmployees.map((emp) => (
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
                                emp.role === 'superadmin'
                                  ? 'bg-amber-100 text-amber-800 border border-amber-300'
                                  : emp.role === 'admin'
                                  ? 'bg-purple-100 text-purple-800 border border-purple-300'
                                  : emp.role === 'manager'
                                  ? 'bg-blue-100 text-blue-800 border border-blue-300'
                                  : 'bg-emerald-100 text-emerald-800 border border-emerald-300'
                              }`}>
                                <ShieldCheck size={14} />
                                <span>{emp.role || 'employee'}</span>
                              </span>
                            </td>

                            <td className="py-4 px-6">
                              <div className="flex flex-wrap gap-1 max-w-md">
                                {emp.permissions.length === 0 ? (
                                  <span className="text-xs text-slate-400 italic">Ikut permission role</span>
                                ) : (
                                  emp.permissions.slice(0, 3).map((p) => (
                                    <span key={p} className="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-bold">
                                      {p}
                                    </span>
                                  ))
                                )}
                                {emp.permissions.length > 3 && (
                                  <span className="px-1.5 py-0.5 rounded bg-slate-200 text-slate-800 text-[10px] font-black">
                                    +{emp.permissions.length - 3} lagi
                                  </span>
                                )}
                              </div>
                            </td>

                            <td className="py-4 px-6 text-right">
                              <button
                                onClick={() => openAssignUserModal(emp)}
                                className="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 font-bold text-xs border border-slate-200 hover:border-emerald-300 transition-all inline-flex items-center space-x-1.5"
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
                </div>
              )}
            </div>
          </div>
        )}

        {/* TAB 3: PERMISSIONS LIST */}
        {activeTab === 'permissions' && (
          <div className="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-6">
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 pb-4">
              <div>
                <h3 className="text-lg font-black text-slate-900">Daftar Hak Akses (Permissions) Sistem</h3>
                <p className="text-xs text-slate-500">Semua permission yang tersedia di database Spatie Permission.</p>
              </div>

              <button
                onClick={() => setIsCreatePermissionOpen(true)}
                className="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 flex items-center space-x-1.5"
              >
                <Plus size={16} />
                <span>Permission Baru</span>
              </button>
            </div>

            {/* Categorized Permissions Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-6">
              {Object.entries(permissionCategories).map(([category, itemNames]) => {
                const categoryPerms = permissions.filter((p) => itemNames.includes(p.name));
                return (
                  <div key={category} className="p-5 rounded-2xl bg-slate-50 border border-slate-200">
                    <h4 className="font-extrabold text-slate-900 text-sm mb-3 flex items-center space-x-2">
                      <Key size={16} className="text-emerald-600" />
                      <span>{category}</span>
                    </h4>
                    <div className="space-y-2">
                      {categoryPerms.map((perm) => (
                        <div
                          key={perm.id}
                          className="p-2.5 rounded-xl bg-white border border-slate-200/80 flex items-center justify-between text-xs"
                        >
                          <span className="font-mono font-bold text-slate-800">{perm.name}</span>
                          <span className="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                            Active
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })}

              {getUncategorizedPermissions().length > 0 && (
                <div className="p-5 rounded-2xl bg-slate-50 border border-slate-200">
                  <h4 className="font-extrabold text-slate-900 text-sm mb-3 flex items-center space-x-2">
                    <Info size={16} className="text-teal-600" />
                    <span>Lainnya (Custom Permissions)</span>
                  </h4>
                  <div className="space-y-2">
                    {getUncategorizedPermissions().map((perm) => (
                      <div
                        key={perm.id}
                        className="p-2.5 rounded-xl bg-white border border-slate-200/80 flex items-center justify-between text-xs"
                      >
                        <span className="font-mono font-bold text-slate-800">{perm.name}</span>
                        <span className="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                          Active
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>
        )}

      </div>

      {/* MODAL 1: CREATE ROLE */}
      {isCreateRoleOpen && (
        <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-white rounded-3xl border border-slate-200 max-w-xl w-full p-6 shadow-2xl space-y-5 my-8">
            <div className="flex items-center justify-between border-b border-slate-200 pb-4">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                  <ShieldCheck size={20} />
                </div>
                <div>
                  <h3 className="text-lg font-black text-slate-900">Buat Role Baru</h3>
                  <p className="text-xs text-slate-500">Tambahkan role custom dan tentukan permission menu-nya.</p>
                </div>
              </div>
              <button onClick={() => setIsCreateRoleOpen(false)} className="text-slate-400 hover:text-slate-700">
                <X size={20} />
              </button>
            </div>

            <form onSubmit={handleCreateRole} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase mb-2">Nama Role</label>
                <input
                  type="text"
                  value={createRoleForm.data.name}
                  onChange={(e) => createRoleForm.setData('name', e.target.value)}
                  placeholder="contoh: supervisor_ops, finance_admin, hrd_staff"
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-semibold text-sm focus:border-emerald-600 outline-none"
                  required
                />
                {createRoleForm.errors.name && (
                  <p className="mt-1 text-xs text-rose-600 font-bold">{createRoleForm.errors.name}</p>
                )}
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase mb-2">Pilih Hak Akses / Permission Menu</label>
                <div className="max-h-60 overflow-y-auto space-y-2 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                  {permissions.map((perm) => {
                    const isChecked = createRoleForm.data.permissions.includes(perm.name);
                    return (
                      <label
                        key={perm.id}
                        className="flex items-center space-x-3 p-2 rounded-xl hover:bg-white cursor-pointer transition-colors"
                      >
                        <input
                          type="checkbox"
                          checked={isChecked}
                          onChange={(e) => {
                            if (e.target.checked) {
                              createRoleForm.setData('permissions', [...createRoleForm.data.permissions, perm.name]);
                            } else {
                              createRoleForm.setData(
                                'permissions',
                                createRoleForm.data.permissions.filter((p) => p !== perm.name)
                              );
                            }
                          }}
                          className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 w-4 h-4"
                        />
                        <span className="text-xs font-bold text-slate-800">{perm.name}</span>
                      </label>
                    );
                  })}
                </div>
              </div>

              <div className="pt-3 border-t border-slate-200 flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setIsCreateRoleOpen(false)}
                  className="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={createRoleForm.processing}
                  className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md"
                >
                  Simpan Role
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 2: EDIT ROLE PERMISSIONS */}
      {editingRole && (
        <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-white rounded-3xl border border-slate-200 max-w-xl w-full p-6 shadow-2xl space-y-5 my-8">
            <div className="flex items-center justify-between border-b border-slate-200 pb-4">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold">
                  <Sliders size={20} />
                </div>
                <div>
                  <h3 className="text-lg font-black text-slate-900 capitalize">
                    Edit Role: {editingRole.name}
                  </h3>
                  <p className="text-xs text-slate-500">Centang atau hilangkan centang hak akses menu role ini.</p>
                </div>
              </div>
              <button onClick={() => setEditingRole(null)} className="text-slate-400 hover:text-slate-700">
                <X size={20} />
              </button>
            </div>

            <form onSubmit={handleUpdateRole} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase mb-2">Nama Role</label>
                <input
                  type="text"
                  value={editRoleForm.data.name}
                  disabled={isSystemRole(editingRole.name)}
                  onChange={(e) => editRoleForm.setData('name', e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-semibold text-sm focus:border-emerald-600 outline-none disabled:opacity-60"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase mb-2">Hak Akses Menu Allowed</label>
                <div className="max-h-64 overflow-y-auto space-y-2 p-3 rounded-2xl bg-slate-50 border border-slate-200">
                  {permissions.map((perm) => {
                    const isChecked = editRoleForm.data.permissions.includes(perm.name);
                    return (
                      <label
                        key={perm.id}
                        className="flex items-center space-x-3 p-2.5 rounded-xl hover:bg-white cursor-pointer transition-colors"
                      >
                        <input
                          type="checkbox"
                          checked={isChecked}
                          onChange={(e) => {
                            if (e.target.checked) {
                              editRoleForm.setData('permissions', [...editRoleForm.data.permissions, perm.name]);
                            } else {
                              editRoleForm.setData(
                                'permissions',
                                editRoleForm.data.permissions.filter((p) => p !== perm.name)
                              );
                            }
                          }}
                          className="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600 w-4 h-4"
                        />
                        <span className="text-xs font-bold text-slate-800">{perm.name}</span>
                      </label>
                    );
                  })}
                </div>
              </div>

              <div className="pt-3 border-t border-slate-200 flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setEditingRole(null)}
                  className="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={editRoleForm.processing}
                  className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md"
                >
                  Perbarui Permission Role
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 3: ASSIGN USER ROLE */}
      {assigningUser && (
        <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-white rounded-3xl border border-slate-200 max-w-lg w-full p-6 shadow-2xl space-y-5 my-8">
            <div className="flex items-center justify-between border-b border-slate-200 pb-4">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
                  <UserCheck size={20} />
                </div>
                <div>
                  <h3 className="text-lg font-black text-slate-900">Ubah Role & Akses Karyawan</h3>
                  <p className="text-xs text-slate-500">Tetapkan role Spatie untuk {assigningUser.name}</p>
                </div>
              </div>
              <button onClick={() => setAssigningUser(null)} className="text-slate-400 hover:text-slate-700">
                <X size={20} />
              </button>
            </div>

            <form onSubmit={handleAssignUserRole} className="space-y-4">
              <div className="p-3 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-1">
                <p className="font-bold text-slate-900">{assigningUser.name} ({assigningUser.nik})</p>
                <p className="text-slate-500">{assigningUser.email} &bull; Dept: {assigningUser.department_name}</p>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase mb-2">Pilih Role Utama</label>
                <select
                  value={assignUserForm.data.role}
                  onChange={(e) => assignUserForm.setData('role', e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-bold text-sm outline-none"
                  required
                >
                  {roles.map((r) => (
                    <option key={r.id} value={r.name}>
                      Role: {r.name.toUpperCase()} ({r.permissions.length} Permissions)
                    </option>
                  ))}
                </select>
              </div>

              <div className="pt-3 border-t border-slate-200 flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setAssigningUser(null)}
                  className="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={assignUserForm.processing}
                  className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md"
                >
                  Simpan Akses Karyawan
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 4: CREATE PERMISSION */}
      {isCreatePermissionOpen && (
        <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
          <div className="bg-white rounded-3xl border border-slate-200 max-w-md w-full p-6 shadow-2xl space-y-5 my-8">
            <div className="flex items-center justify-between border-b border-slate-200 pb-4">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center font-bold">
                  <Key size={20} />
                </div>
                <div>
                  <h3 className="text-lg font-black text-slate-900">Tambah Permission Baru</h3>
                  <p className="text-xs text-slate-500">Buat permission custom baru untuk sistem.</p>
                </div>
              </div>
              <button onClick={() => setIsCreatePermissionOpen(false)} className="text-slate-400 hover:text-slate-700">
                <X size={20} />
              </button>
            </div>

            <form onSubmit={handleCreatePermission} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-slate-700 uppercase mb-2">Nama Permission</label>
                <input
                  type="text"
                  value={createPermForm.data.name}
                  onChange={(e) => createPermForm.setData('name', e.target.value)}
                  placeholder="contoh: export-excel, approve-special-leave"
                  className="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-300 text-slate-900 font-semibold text-sm outline-none"
                  required
                />
                {createPermForm.errors.name && (
                  <p className="mt-1 text-xs text-rose-600 font-bold">{createPermForm.errors.name}</p>
                )}
              </div>

              <div className="pt-3 border-t border-slate-200 flex justify-end space-x-2">
                <button
                  type="button"
                  onClick={() => setIsCreatePermissionOpen(false)}
                  className="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  disabled={createPermForm.processing}
                  className="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md"
                >
                  Tambah Permission
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

    </AuthenticatedLayout>
  );
}
