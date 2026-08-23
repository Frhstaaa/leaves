<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\LeaveQuota;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Comprehensive list of granular permissions matching all menus & modules
        $permissions = [
            // General / Dashboard
            ['name' => 'view-dashboard', 'guard_name' => 'web'],
            
            // Cuti / Leave Requests (Karyawan)
            ['name' => 'create-leave-request', 'guard_name' => 'web'],
            ['name' => 'view-leave-history', 'guard_name' => 'web'],
            ['name' => 'delete-leave-request', 'guard_name' => 'web'],

            // Slip Gaji Saya (Karyawan)
            ['name' => 'view-payslips', 'guard_name' => 'web'],
            ['name' => 'download-payslips', 'guard_name' => 'web'],
            
            // Tim & Persetujuan (Manager / Atasan)
            ['name' => 'manage-approvals', 'guard_name' => 'web'],

            // Laporan & Monitoring (Executive)
            ['name' => 'view-monitoring-annual', 'guard_name' => 'web'],
            ['name' => 'view-monitoring-analytics', 'guard_name' => 'web'],
            
            // HRD: Karyawan & Kuota
            ['name' => 'manage-employees', 'guard_name' => 'web'],
            ['name' => 'create-employee', 'guard_name' => 'web'],
            ['name' => 'edit-employee', 'guard_name' => 'web'],
            ['name' => 'delete-employee', 'guard_name' => 'web'],
            ['name' => 'import-employees', 'guard_name' => 'web'],
            ['name' => 'manage-leave-quotas', 'guard_name' => 'web'],
            ['name' => 'view-hrd-rekap', 'guard_name' => 'web'],
            ['name' => 'export-hrd-reports', 'guard_name' => 'web'],

            // HRD: Setup Departemen
            ['name' => 'manage-departments', 'guard_name' => 'web'],

            // HRD: Distribusi Slip Gaji
            ['name' => 'manage-hrd-payslips', 'guard_name' => 'web'],
            ['name' => 'upload-hrd-payslips', 'guard_name' => 'web'],
            ['name' => 'delete-hrd-payslips', 'guard_name' => 'web'],
            
            // Superadmin / Sistem
            ['name' => 'manage-roles', 'guard_name' => 'web'],
            ['name' => 'assign-user-roles', 'guard_name' => 'web'],
            ['name' => 'manage-system-settings', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name'], 'guard_name' => $perm['guard_name']]);
        }

        // 2. Define Roles
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $adminRole      = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $managerRole    = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $employeeRole   = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        // 3. Assign Permissions to Roles
        // Superadmin: All permissions
        $superadminRole->syncPermissions(Permission::all());

        // HRD / PGA Admin: Full managerial access
        $adminRole->syncPermissions([
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
        ]);

        // Manager: Approvals + Monitoring & Personal leaves/payslips
        $managerRole->syncPermissions([
            'view-dashboard',
            'create-leave-request',
            'view-leave-history',
            'delete-leave-request',
            'view-payslips',
            'download-payslips',
            'manage-approvals',
            'view-monitoring-annual',
            'view-monitoring-analytics',
        ]);

        // Employee: Basic self-service leave requests & personal payslips
        $employeeRole->syncPermissions([
            'view-dashboard',
            'create-leave-request',
            'view-leave-history',
            'delete-leave-request',
            'view-payslips',
            'download-payslips',
        ]);

        // 4. Create or update Superadmin user
        $deptIT = Department::where('code', 'DEPT-IT')->first() ?? Department::first();
        
        $superUser = User::firstOrCreate(
            ['email' => 'superadmin@sgin.com'],
            [
                'nik' => 'SA-001',
                'name' => 'Superadmin SGIN',
                'password' => Hash::make('password'),
                'role' => 'superadmin',
                'department_id' => $deptIT ? $deptIT->id : null,
            ]
        );
        $superUser->assignRole($superadminRole);
        $superUser->update(['role' => 'superadmin']);

        // Give Superadmin initial leave quota
        LeaveQuota::firstOrCreate(
            ['user_id' => $superUser->id, 'year' => date('Y')],
            [
                'total_quota' => 12,
                'used_quota' => 0,
                'remaining_quota' => 12,
            ]
        );

        // 5. Sync spatie roles to existing users in database based on their string 'role' column
        $users = User::all();
        foreach ($users as $user) {
            if ($user->role === 'superadmin' && !$user->hasRole('superadmin')) {
                $user->assignRole($superadminRole);
            } elseif ($user->role === 'admin' && !$user->hasRole('admin')) {
                $user->assignRole($adminRole);
            } elseif ($user->role === 'manager' && !$user->hasRole('manager')) {
                $user->assignRole($managerRole);
            } elseif (($user->role === 'employee' || !$user->role) && !$user->hasRole('employee')) {
                $user->assignRole($employeeRole);
            }
        }
    }
}
