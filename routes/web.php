<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrdController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Leave Management System (Form SGIN)
|--------------------------------------------------------------------------
*/

Route::get('/', [AuthController::class, 'showLogin']);

// Service Worker Route (Ensures sw.js is always reachable in subfolders)
Route::get('/sw.js', [AssetController::class, 'serviceWorker']);

// Vite Build Assets Direct Route (Guarantees JS/CSS are served with 200 OK & CORS)
Route::get('/build/{path}', [AssetController::class, 'buildAsset'])->where('path', '.*');

// Storage Direct & Cloudflare R2 Proxy Route with Master Fallback
Route::get('/storage/{path}', [AssetController::class, 'storageProxy'])->where('path', '.*');

// PWA Manifest & Dynamic App Icon Routes
Route::get('/manifest.webmanifest', [SettingController::class, 'manifest'])->name('pwa.manifest');
Route::get('/manifest.json', [SettingController::class, 'manifest']);
Route::get('/app-icon/{size?}', [SettingController::class, 'getAppIcon'])->name('pwa.icon');
Route::get('/icons/{filename}', [AssetController::class, 'iconAsset'])->where('filename', '.*');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Session Heartbeat & CSRF Token Auto-Refresh Endpoint (Prevents 419 Page Expired)
Route::match(['get', 'post'], '/ping', function () {
    return response()->json([
        'status' => 'ok',
        'csrf_token' => csrf_token(),
        'authenticated' => auth()->check(),
    ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
})->name('ping');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Karyawan Leave Requests
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('/leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::get('/leave-requests/report/print', [LeaveRequestController::class, 'printPersonalReport'])->name('leave-requests.report.print');
    Route::get('/leave-requests/{id}/attachment', [LeaveRequestController::class, 'viewAttachment'])->name('leave-requests.attachment');
    Route::get('/leave-requests/{id}/attachment/view', [LeaveRequestController::class, 'viewAttachment'])->name('leave-requests.attachment.view');
    Route::get('/leave-requests/{id}/attachment/download', [LeaveRequestController::class, 'downloadAttachment'])->name('leave-requests.attachment.download');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::get('/leave-requests/{id}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy'])->name('leave-requests.destroy');

    // Manager / Approver Routes
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{id}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{id}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');

    // HRD / PGA Admin Routes
    Route::get('/hrd', [HrdController::class, 'index'])->name('hrd.index');
    Route::get('/hrd/departments', [HrdController::class, 'departments'])->name('hrd.departments');
    Route::post('/hrd/departments', [HrdController::class, 'storeDepartment'])->name('hrd.departments.store');
    Route::match(['put', 'post'], '/hrd/departments/{id}/update', [HrdController::class, 'updateDepartment'])->name('hrd.departments.update');
    Route::match(['put', 'post'], '/hrd/departments/{id}', [HrdController::class, 'updateDepartment'])->name('hrd.departments.update.direct');
    Route::match(['put', 'post'], '/departments/{id}/update', [HrdController::class, 'updateDepartment']);
    Route::match(['put', 'post'], '/departments/{id}', [HrdController::class, 'updateDepartment']);
    Route::delete('/hrd/departments/{id}', [HrdController::class, 'destroyDepartment'])->name('hrd.departments.destroy');
    Route::get('/hrd/employees', [HrdController::class, 'employees'])->name('hrd.employees');
    Route::get('/hrd/employees/template', [HrdController::class, 'downloadTemplate'])->name('hrd.employees.template');
    Route::post('/hrd/employees/import', [HrdController::class, 'importEmployees'])->name('hrd.employees.import');
    Route::post('/hrd/employees/import/preview', [HrdController::class, 'previewImport'])->name('hrd.employees.import.preview');
    Route::get('/hrd/employees/export-biodata', [HrdController::class, 'exportBiodataCsv'])->name('hrd.employees.export-biodata');
    Route::get('/hrd/employees/{userId}/biodata', [HrdController::class, 'employeeBiodata'])->name('hrd.employees.biodata');
    Route::get('/hrd/employees/biodata/{userId}', [HrdController::class, 'employeeBiodata'])->name('hrd.employees.biodata.alt');
    Route::match(['put', 'post'], '/hrd/employees/{userId}/biodata', [HrdController::class, 'updateEmployeeBiodata'])->name('hrd.employees.biodata.update');
    Route::match(['put', 'post'], '/hrd/employees/biodata/{userId}/update', [HrdController::class, 'updateEmployeeBiodata']);
    Route::match(['put', 'post'], '/hrd/employees/biodata/{userId}', [HrdController::class, 'updateEmployeeBiodata']);
    Route::get('/hrd/employees/{userId}/biodata/print', [HrdController::class, 'printEmployeeBiodata'])->name('hrd.employees.biodata.print');
    Route::get('/hrd/employees/biodata/{userId}/print', [HrdController::class, 'printEmployeeBiodata'])->name('hrd.employees.biodata.print.alt');
    Route::post('/hrd/employees', [HrdController::class, 'storeEmployee'])->name('hrd.employees.store');
    Route::match(['put', 'post'], '/hrd/employees/{userId}/update', [HrdController::class, 'updateEmployee'])->name('hrd.employees.update');
    Route::match(['put', 'post'], '/hrd/employees/update/{userId}', [HrdController::class, 'updateEmployee']);
    Route::match(['put', 'post'], '/hrd/employees/{userId}', [HrdController::class, 'updateEmployee']);
    Route::match(['put', 'post'], '/employees/{userId}/update', [HrdController::class, 'updateEmployee']);
    Route::match(['put', 'post'], '/employees/update/{userId}', [HrdController::class, 'updateEmployee']);
    Route::match(['put', 'post'], '/employees/{userId}', [HrdController::class, 'updateEmployee']);
    Route::match(['put', 'post'], '/update/{userId}', [HrdController::class, 'updateEmployee']);
    Route::delete('/hrd/employees/{userId}', [HrdController::class, 'destroyEmployee'])->name('hrd.employees.destroy');
    Route::delete('/employees/{userId}', [HrdController::class, 'destroyEmployee']);
    Route::match(['put', 'post'], '/hrd/employees/{userId}/quota', [HrdController::class, 'updateQuota'])->name('hrd.update-quota');
    Route::match(['put', 'post'], '/hrd/employees/quota/{userId}', [HrdController::class, 'updateQuota']);
    Route::match(['put', 'post'], '/employees/{userId}/quota', [HrdController::class, 'updateQuota']);
    Route::post('/hrd/requests/{id}/override', [HrdController::class, 'overrideStatus'])->name('hrd.requests.override');
    Route::get('/hrd/export', [HrdController::class, 'export'])->name('hrd.export');
    Route::get('/hrd/reports/quotas', [HrdController::class, 'exportLeaveQuotas'])->name('hrd.reports.quotas');
    Route::get('/hrd/reports/departments', [HrdController::class, 'exportDepartmentSummary'])->name('hrd.reports.departments');

    // Enterprise Monitoring Dashboard & Annual Leave Matrix Reports
    Route::get('/monitoring', [\App\Http\Controllers\MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/annual-report', [\App\Http\Controllers\MonitoringController::class, 'annualReport'])->name('monitoring.annual-report');
    Route::get('/monitoring/annual-report/pdf', [\App\Http\Controllers\MonitoringController::class, 'annualReportPdf'])->name('monitoring.annual-report.pdf');
    Route::get('/monitoring/annual-report/export', [\App\Http\Controllers\MonitoringController::class, 'exportAnnualReportCsv'])->name('monitoring.annual-report.export');

    // App Settings Routes
    Route::get('/hrd/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('hrd.settings');
    Route::post('/hrd/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('hrd.settings.update');
    Route::post('/hrd/settings/test-r2', [\App\Http\Controllers\SettingController::class, 'testR2'])->name('hrd.settings.test-r2');

    // Profile & Biodata Routes
    Route::get('/profile/biodata', [\App\Http\Controllers\ProfileController::class, 'biodata'])->name('profile.biodata');
    Route::get('/biodata', [\App\Http\Controllers\ProfileController::class, 'biodata']);
    Route::match(['put', 'post'], '/profile/biodata', [\App\Http\Controllers\ProfileController::class, 'updateBiodata'])->name('profile.biodata.update');
    Route::match(['put', 'post'], '/profile/biodata/update', [\App\Http\Controllers\ProfileController::class, 'updateBiodata']);
    Route::match(['put', 'post'], '/biodata', [\App\Http\Controllers\ProfileController::class, 'updateBiodata']);
    Route::get('/profile/biodata/print', [\App\Http\Controllers\ProfileController::class, 'printBiodata'])->name('profile.biodata.print');
    Route::post('/profile/avatar', [\App\Http\Controllers\ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::match(['put', 'post'], '/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');

    // Employee Payslips Portal
    Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('/payslips/{id}/download', [PayslipController::class, 'download'])->name('payslips.download');
    Route::get('/payslips/{id}/preview', [PayslipController::class, 'preview'])->name('payslips.preview');
    Route::post('/payslips/{id}/viewed', [PayslipController::class, 'markViewed'])->name('payslips.mark-viewed');

    // HRD Payslip Management & Bulk Upload
    Route::get('/hrd/payslips', [PayslipController::class, 'manage'])->name('hrd.payslips');
    Route::post('/hrd/payslips/bulk-upload', [PayslipController::class, 'bulkUpload'])->name('hrd.payslips.bulk-upload');
    Route::post('/hrd/payslips/single-upload', [PayslipController::class, 'singleUpload'])->name('hrd.payslips.single-upload');
    Route::delete('/hrd/payslips/{id}', [PayslipController::class, 'destroy'])->name('hrd.payslips.destroy');

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


