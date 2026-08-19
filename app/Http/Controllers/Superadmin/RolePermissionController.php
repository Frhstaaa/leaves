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

        return Inertia::render('Superadmin/RolePermission/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'employees' => $employees,
            'stats' => $stats,
        ]);
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
