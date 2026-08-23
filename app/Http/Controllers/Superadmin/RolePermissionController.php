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
                'description' => 'Akses standar untuk mengajukan cuti, cek riwayat, dan melihat slip gaji pribadi',
                'permissions' => [
                    'view-dashboard',
                    'create-leave-request',
                    'view-leave-history',
                    'delete-leave-request',
                    'view-payslips',
                    'download-payslips',
                ],
            ],
            'manager' => [
                'name' => 'Atasan / Manager',
                'description' => 'Akses karyawan + persetujuan cuti bawahan & monitoring matrix tahunan',
                'permissions' => [
                    'view-dashboard',
                    'create-leave-request',
                    'view-leave-history',
                    'delete-leave-request',
                    'view-payslips',
                    'download-payslips',
                    'manage-approvals',
                    'view-monitoring-annual',
                    'view-monitoring-analytics',
                ],
            ],
            'admin' => [
                'name' => 'HRD / PGA Admin',
                'description' => 'Akses manajerial HRD lengkap: data karyawan, kuota, departemen, distribusi slip gaji, rekapitulasi, & ekspor',
                'permissions' => [
                    'view-dashboard',
                    'create-leave-request',
                    'view-leave-history',
                    'delete-leave-request',
                    'view-payslips',
                    'download-payslips',
                    'manage-approvals',
                    'view-monitoring-annual',
                    'view-monitoring-analytics',
                    'manage-employees',
                    'create-employee',
                    'edit-employee',
                    'delete-employee',
                    'import-employees',
                    'manage-leave-quotas',
                    'view-hrd-rekap',
                    'export-hrd-reports',
                    'manage-departments',
                    'manage-hrd-payslips',
                    'upload-hrd-payslips',
                    'delete-hrd-payslips',
                    'manage-system-settings',
                    'manage-roles',
                    'assign-user-roles',
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
                    [
                        'name' => 'delete-leave-request',
                        'label' => 'Batalkan Pengajuan Cuti',
                        'description' => 'Membatalkan / menghapus pengajuan cuti sendiri yang masih menunggu persetujuan',
                    ],
                ],
            ],
            'payslips' => [
                'category_name' => 'Slip Gaji Saya',
                'category_desc' => 'Akses karyawan untuk melihat dan mengunduh slip gaji bulanan pribadi',
                'icon' => 'Receipt',
                'color' => 'cyan',
                'badge_color' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                'permissions' => [
                    [
                        'name' => 'view-payslips',
                        'label' => 'Lihat Slip Gaji Pribadi',
                        'description' => 'Melihat daftar rincian komponen penerimaan dan potongan gaji pribadi',
                    ],
                    [
                        'name' => 'download-payslips',
                        'label' => 'Download PDF Slip Gaji',
                        'description' => 'Mengunduh dan mencetak file PDF slip gaji bulanan pribadi',
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
            'monitoring' => [
                'category_name' => 'Laporan & Monitoring Eksekutif',
                'category_desc' => 'Visualisasi analitik, tren absensi, dan matrix rekapitulasi cuti tahunan',
                'icon' => 'Activity',
                'color' => 'sky',
                'badge_color' => 'bg-sky-100 text-sky-800 border-sky-200',
                'permissions' => [
                    [
                        'name' => 'view-monitoring-annual',
                        'label' => 'Matrix Laporan Cuti 1 Tahun',
                        'description' => 'Melihat matrix rekapitulasi cuti seluruh karyawan selama 12 bulan (Jan - Des)',
                    ],
                    [
                        'name' => 'view-monitoring-analytics',
                        'label' => 'Executive Analytics & Trends',
                        'description' => 'Melihat grafik statistik tren cuti, pemanfaatan kuota, dan monitoring departemen',
                    ],
                ],
            ],
            'hrd' => [
                'category_name' => 'HRD & Manajemen Karyawan',
                'category_desc' => 'Pengelolaan data master karyawan, kuota, rekapitulasi, dan ekspor laporan',
                'icon' => 'Users',
                'color' => 'purple',
                'badge_color' => 'bg-purple-100 text-purple-800 border-purple-200',
                'permissions' => [
                    [
                        'name' => 'manage-employees',
                        'label' => 'Kelola Data Karyawan',
                        'description' => 'Melihat direktori dan profil lengkap seluruh karyawan perusahaan',
                    ],
                    [
                        'name' => 'create-employee',
                        'label' => 'Tambah Karyawan Baru',
                        'description' => 'Mendaftarkan karyawan baru dan membuat akun login sistem',
                    ],
                    [
                        'name' => 'edit-employee',
                        'label' => 'Edit Data Karyawan',
                        'description' => 'Mengubah data NIK, email, jabatan, departemen, serta atasan 1 & 2',
                    ],
                    [
                        'name' => 'delete-employee',
                        'label' => 'Hapus Karyawan',
                        'description' => 'Menghapus data karyawan dan hak aksesnya dari sistem',
                    ],
                    [
                        'name' => 'import-employees',
                        'label' => 'Import Excel / CSV Karyawan',
                        'description' => 'Mengimpor data karyawan dan kuota cuti secara massal dari file spreadsheet',
                    ],
                    [
                        'name' => 'manage-leave-quotas',
                        'label' => 'Atur Kuota Cuti Karyawan',
                        'description' => 'Menyesuaikan dan menambah/mengurangi saldo cuti tahunan karyawan',
                    ],
                    [
                        'name' => 'view-hrd-rekap',
                        'label' => 'Rekapitulasi HRD & Kalender',
                        'description' => 'Melihat laporan cuti seluruh departemen dan kalender cuti perusahaan',
                    ],
                    [
                        'name' => 'export-hrd-reports',
                        'label' => 'Export Laporan (Excel/PDF)',
                        'description' => 'Mengunduh rekap cuti & absensi karyawan dalam format Excel / PDF',
                    ],
                ],
            ],
            'departments' => [
                'category_name' => 'Setup Departemen & Organisasi',
                'category_desc' => 'Pengelolaan struktur departemen dan pejabat penanggung jawab approval',
                'icon' => 'Building',
                'color' => 'indigo',
                'badge_color' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                'permissions' => [
                    [
                        'name' => 'manage-departments',
                        'label' => 'Kelola Master Departemen',
                        'description' => 'Membuat, mengedit, dan menghapus departemen serta struktur atasan penanggung jawab',
                    ],
                ],
            ],
            'hrd_payslips' => [
                'category_name' => 'Distribusi Slip Gaji HRD',
                'category_desc' => 'Manajemen distribusi slip gaji seluruh karyawan oleh HRD',
                'icon' => 'Receipt',
                'color' => 'emerald',
                'badge_color' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'permissions' => [
                    [
                        'name' => 'manage-hrd-payslips',
                        'label' => 'Kelola Distribusi Slip Gaji',
                        'description' => 'Melihat status pengiriman dan tracking pembacaan slip gaji karyawan',
                    ],
                    [
                        'name' => 'upload-hrd-payslips',
                        'label' => 'Upload Slip Gaji (Single & Bulk Zip)',
                        'description' => 'Mengunggah file PDF slip gaji satuan atau upload massal zip per periode',
                    ],
                    [
                        'name' => 'delete-hrd-payslips',
                        'label' => 'Hapus Arsip Slip Gaji',
                        'description' => 'Menghapus arsip slip gaji yang salah unggah dari server',
                    ],
                ],
            ],
            'superadmin' => [
                'category_name' => 'Superadmin & Sistem',
                'category_desc' => 'Kontrol hak akses tingkat tinggi, Spatie role permissions, dan konfigurasi aplikasi',
                'icon' => 'ShieldCheck',
                'color' => 'amber',
                'badge_color' => 'bg-amber-100 text-amber-800 border-amber-200',
                'permissions' => [
                    [
                        'name' => 'manage-roles',
                        'label' => 'Kelola Role & Permissions',
                        'description' => 'Membuat role custom, mengatur matriks permission, dan kontrol hak akses',
                    ],
                    [
                        'name' => 'assign-user-roles',
                        'label' => 'Assign Role ke Karyawan',
                        'description' => 'Menetapkan atau mengubah role hak akses pada data karyawan',
                    ],
                    [
                        'name' => 'manage-system-settings',
                        'label' => 'Pengaturan Sistem & Branding',
                        'description' => 'Mengatur nama aplikasi, logo, warna tema, favicon, dan PWA webmanifest',
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

        // Prevent renaming system superadmin role to something else
        if ($role->name === 'superadmin' && strtolower(trim($request->name)) !== 'superadmin') {
            return redirect()->back()->with('error', "Role 'superadmin' sistem tidak boleh diubah namanya.");
        }

        $role->name = strtolower(trim($request->name));
        $role->save();

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil diperbarui.");
    }

    public function destroyRole($id)
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, ['superadmin', 'admin', 'manager', 'employee'])) {
            return redirect()->back()->with('error', "Role sistem bawaan '{$role->name}' tidak boleh dihapus.");
        }

        if (User::role($role->name)->count() > 0) {
            return redirect()->back()->with('error', "Role '{$role->name}' masih digunakan oleh beberapa karyawan.");
        }

        $role->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Role '{$role->name}' berhasil dihapus.");
    }

    public function storePermission(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::create([
            'name' => strtolower(trim($request->name)),
            'guard_name' => 'web',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', 'Permission baru berhasil ditambahkan.');
    }

    public function assignUserRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'nullable|array',
        ]);

        // Sync Spatie Role
        $user->syncRoles([$request->role]);

        // Sync additional direct user permissions if provided
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        // Sync legacy string 'role' column on users table
        $user->role = $request->role;
        $user->save();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return redirect()->back()->with('success', "Hak akses role karyawan '{$user->name}' berhasil disinkronkan ke '{$request->role}'.");
    }
}
