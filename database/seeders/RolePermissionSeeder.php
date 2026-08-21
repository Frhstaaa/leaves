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

        // 1. Define permissions list with detailed description and category
        $permissions = [
            // General / Dashboard
            ['name' => 'view-dashboard', 'guard_name' => 'web'],
            
            // Cuti / Leave Requests
            ['name' => 'create-leave-request', 'guard_name' => 'web'],
            ['name' => 'view-leave-history', 'guard_name' => 'web'],
            
            // Manager / Approval
            ['name' => 'manage-approvals', 'guard_name' => 'web'],
            
            // HRD / PGA Admin (Granular)
            ['name' => 'create-employee', 'guard_name' => 'web'],
            ['name' => 'edit-employee', 'guard_name' => 'web'],
            ['name' => 'delete-employee', 'guard_name' => 'web'],
            ['name' => 'view-hrd-rekap', 'guard_name' => 'web'],
            ['name' => 'export-hrd-reports', 'guard_name' => 'web'],
            ['name' => 'view-leave-quota-report', 'guard_name' => 'web'],
            ['name' => 'view-department-report', 'guard_name' => 'web'],
            ['name' => 'manage-leave-categories', 'guard_name' => 'web'],
            
            // Superadmin / System
            ['name' => 'manage-roles', 'guard_name' => 'web'],
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
        $superadminRole->syncPermissions(Permission::all());

        $adminRole->syncPermissions([
            'view-dashboard',
            'create-leave-request',
            'view-leave-history',
            'manage-approvals',
            'create-employee',
            'edit-employee',
            'delete-employee',
            'view-hrd-rekap',
            'export-hrd-reports',
            'view-leave-quota-report',
            'view-department-report',
            'manage-leave-categories',
        ]);

        $managerRole->syncPermissions([
            'view-dashboard',
            'create-leave-request',
            'view-leave-history',
            'manage-approvals',
        ]);

        $employeeRole->syncPermissions([
            'view-dashboard',
            'create-leave-request',
            'view-leave-history',
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
