<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrdController;
use App\Http\Controllers\LeaveRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Leave Management System (Form SGIN)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/quick-login', [AuthController::class, 'quickLogin'])->name('quick-login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Karyawan Leave Requests
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('/leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::get('/leave-requests/{id}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy'])->name('leave-requests.destroy');

    // Manager / Approver Routes
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

    // HRD / PGA Admin Routes
    Route::get('/hrd', [HrdController::class, 'index'])->name('hrd.index');
    Route::get('/hrd/employees', [HrdController::class, 'employees'])->name('hrd.employees');
    Route::get('/hrd/employees/template', [HrdController::class, 'downloadTemplate'])->name('hrd.employees.template');
    Route::post('/hrd/employees/import', [HrdController::class, 'importEmployees'])->name('hrd.employees.import');
    Route::post('/hrd/employees', [HrdController::class, 'storeEmployee'])->name('hrd.employees.store');
    Route::post('/hrd/employees/{userId}/update', [HrdController::class, 'updateEmployee'])->name('hrd.employees.update');
    Route::delete('/hrd/employees/{userId}', [HrdController::class, 'destroyEmployee'])->name('hrd.employees.destroy');
    Route::post('/hrd/employees/{userId}/quota', [HrdController::class, 'updateQuota'])->name('hrd.update-quota');
    Route::get('/hrd/export', [HrdController::class, 'export'])->name('hrd.export');
    // Profile Avatar Route
    Route::post('/profile/avatar', [\App\Http\Controllers\ProfileController::class, 'updateAvatar'])->name('profile.avatar');

    // Superadmin Role & Permission Management Routes
    Route::middleware(['superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/roles', [\App\Http\Controllers\Superadmin\RolePermissionController::class, 'index'])->name('roles.index');
        Route::post('/roles', [\App\Http\Controllers\Superadmin\RolePermissionController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{id}', [\App\Http\Controllers\Superadmin\RolePermissionController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{id}', [\App\Http\Controllers\Superadmin\RolePermissionController::class, 'destroyRole'])->name('roles.destroy');
        Route::post('/permissions', [\App\Http\Controllers\Superadmin\RolePermissionController::class, 'storePermission'])->name('permissions.store');
        Route::post('/users/{userId}/assign-role', [\App\Http\Controllers\Superadmin\RolePermissionController::class, 'assignUserRole'])->name('users.assign-role');
    });
});
