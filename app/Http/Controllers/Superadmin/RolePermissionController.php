<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function index()
    {
        // Forget cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = Role::with('permissions')->get()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
                'users_count' => User::role($role->name)->count(),
            ];
        });

        $permissions = Permission::all()->map(function ($perm) {
            return [
                'id' => $perm->id,
                'name' => $perm->name,
                'guard_name' => $perm->guard_name,
            ];
        });

        $employees = User::with('department')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'nik' => $user->nik,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'department_name' => $user->department ? $user->department->name : '-',
                'avatar_url' => $user->avatar_url,
            ];
        });

        $stats = [
            'total_roles' => $roles->count(),
            'total_permissions' => $permissions->count(),
            'total_employees' => $employees->count(),
        ];

        $permissionCatalog = self::getPermissionCatalog();

        $rolePresets = [
            'employee' => [
                'name' => 'Karyawan Biasa',
                'description' => 'Akses standar untuk mengajukan dan melihat riwayat cuti',
                'permissions' => ['view-dashboard', 'create-leave-request', 'view-leave-history'],
            ],
            'manager' => [
                'name' => 'Atasan / Manager',
                'description' => 'Akses karyawan ditambah wewenang persetujuan cuti bawahan',
                'permissions' => ['view-dashboard', 'create-leave-request', 'view-leave-history', 'manage-approvals'],
            ],
            'admin' => [
                'name' => 'HRD / PGA Admin',
                'description' => 'Akses manajerial HRD, data karyawan, rekapitulasi, dan ekspor laporan',
                'permissions' => [
                    'view-dashboard', 'create-leave-request', 'view-leave-history',
                    'manage-approvals', 'manage-employees', 'view-hrd-rekap', 'export-hrd-reports'
                ],
            ],
            'superadmin' => [
                'name' => 'Superadmin (Full Access)',
                'description' => 'Akses penuh ke seluruh modul, pengaturan role, dan konfigurasi sistem',
                'permissions' => $permissions->pluck('name')->toArray(),
            ],
        ];

        return Inertia::render('Superadmin/RolePermission/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'employees' => $employees,
            'stats' => $stats,
            'permission_catalog' => $permissionCatalog,
            'role_presets' => $rolePresets,
        ]);
    }

    public static function getPermissionCatalog(): array
    {
        return [
            'general' => [
                'category_name' => 'Umum & Dashboard',
                'category_desc' => 'Akses ringkasan statistik & banner pengajuan cuti',
                'icon' => 'LayoutDashboard',
                'color' => 'emerald',
                'badge_color' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'permissions' => [
                    [
                        'name' => 'view-dashboard',
                        'label' => 'Lihat Dashboard',
                        'description' => 'Melihat ringkasan statistik, kuota cuti tersisa, dan shortcut pengajuan',
                    ],
                ],
            ],
            'leave' => [
                'category_name' => 'Pengajuan & Riwayat Cuti',
                'category_desc' => 'Aktivitas pengajuan formulir cuti, sakit, dinas, dan riwayat permohonan',
                'icon' => 'FileText',
                'color' => 'teal',
                'badge_color' => 'bg-teal-100 text-teal-800 border-teal-200',
                'permissions' => [
                    [
                        'name' => 'create-leave-request',
                        'label' => 'Buat Pengajuan Baru',
                        'description' => 'Mengisi dan mengirimkan formulir izin, dinas, sakit & cuti karyawan',
                    ],
                    [
                        'name' => 'view-leave-history',
                        'label' => 'Lihat Riwayat Cuti Pribadi',
                        'description' => 'Melihat status approval, detail alasan, dan timeline permohonan sendiri',
                    ],
                ],
            ],
            'approval' => [
                'category_name' => 'Persetujuan & Approval Team',
                'category_desc' => 'Kewenangan menyetujui / menolak pengajuan cuti bawahan atau tim kerja',
                'icon' => 'CheckSquare',
                'color' => 'blue',
                'badge_color' => 'bg-blue-100 text-blue-800 border-blue-200',
                'permissions' => [
                    [
                        'name' => 'manage-approvals',
                        'label' => 'Persetujuan Cuti (Approval)',
                        'description' => 'Menyetujui (Approve) atau Menolak (Reject) permohonan cuti bawahan bertingkat',
                    ],
                ],
            ],
            'hrd' => [
                'category_name' => 'HRD & Manajemen Karyawan',
                'category_desc' => 'Pengelolaan data master karyawan, rekapitulasi, dan ekspor laporan',
                'icon' => 'Users',
                'color' => 'purple',
                'badge_color' => 'bg-purple-100 text-purple-800 border-purple-200',
                'permissions' => [
                    [
                        'name' => 'manage-employees',
                        'label' => 'Kelola Data Karyawan',
                        'description' => 'Menambah, mengedit NIK/departemen/atasan 1 & 2, import Excel, dan kelola kuota',
                    ],
                    [
                        'name' => 'view-hrd-rekap',
                        'label' => 'Rekapitulasi HRD & Kalender',
                        'description' => 'Melihat laporan cuti seluruh departemen dan kalender cuti perusahaan',
                    ],
                    [
                        'name' => 'export-hrd-reports',
                        'label' => 'Export Laporan (Excel/PDF)',
                        'description' => 'Mengunduh rekap cuti & absensi karyawan dalam format Excel / cetak',
                    ],
                ],
            ],
            'superadmin' => [
                'category_name' => 'Superadmin & Konfigurasi',
                'category_desc' => 'Kontrol hak akses tingkat tinggi, Spatie role permissions, dan konfigurasi',
                'icon' => 'ShieldCheck',
                'color' => 'amber',
                'badge_color' => 'bg-amber-100 text-amber-800 border-amber-200',
                'permissions' => [
                    [
                        'name' => 'manage-roles',
                        'label' => 'Kelola Role & Permissions',
                        'description' => 'Membuat role custom, mengatur matriks permission, dan assignment ke karyawan',
                    ],
                    [
                        'name' => 'manage-system-settings',
                        'label' => 'Pengaturan Sistem & Master Data',
                        'description' => 'Mengatur setting global aplikasi, master kategori cuti, dan konfigurasi kuota',
                    ],
                ],
            ],
        ];
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create([
            'name' => strtolower(trim($request->name)),
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil dibuat.");
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        // Protect system core roles from renaming
        $systemRoles = ['superadmin', 'admin', 'manager', 'employee'];
        if (!in_array($role->name, $systemRoles)) {
            $role->name = strtolower(trim($request->name));
            $role->save();
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Hak akses role '{$role->name}' berhasil diperbarui.");
    }

    public function destroyRole($id)
    {
        $role = Role::findOrFail($id);

        $systemRoles = ['superadmin', 'admin', 'manager', 'employee'];
        if (in_array($role->name, $systemRoles)) {
            return redirect()->back()->with('error', "Role sistem bawaan ('{$role->name}') tidak boleh dihapus.");
        }

        $roleName = $role->name;
        $role->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$roleName}' berhasil dihapus.");
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        $permName = strtolower(str_replace(' ', '-', trim($request->name)));

        Permission::create([
            'name' => $permName,
            'guard_name' => 'web',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Permission '{$permName}' berhasil ditambahkan.");
    }

    public function assignUserRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'nullable|array',
        ]);

        // Sync Spatie role
        $user->syncRoles([$request->role]);

        // Sync string column for backward compatibility
        $user->update(['role' => $request->role]);

        // Direct permissions override/sync if provided
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role & Hak Akses untuk '{$user->name}' berhasil diperbarui ke role '{$request->role}'.");
    }
}
