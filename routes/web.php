<?php

use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HrdController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PayslipController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Leave Management System (Form SGIN)
|--------------------------------------------------------------------------
*/

Route::get('/', [AuthController::class, 'showLogin']);

// Service Worker Route (Ensures sw.js is always reachable in subfolders)
Route::get('/sw.js', function () {
    $path = public_path('sw.js');
    if (!file_exists($path)) {
        $path = base_path('public/sw.js');
    }
    if (file_exists($path)) {
        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
    return response('// sw.js not found', 404);
});

// Vite Build Assets Direct Route (Guarantees JS/CSS are served with 200 OK & CORS)
Route::get('/build/{path}', function ($path) {
    if (str_contains($path, '..') || str_contains($path, "\0")) {
        abort(404);
    }
    $cleanPath = ltrim(str_replace('\\', '/', $path), '/');
    $filePath = public_path('build/' . $cleanPath);
    if (!file_exists($filePath)) {
        $filePath = base_path('public/build/' . $cleanPath);
    }
    $real = realpath($filePath);
    $publicBuild = realpath(public_path('build')) ?: realpath(base_path('public/build'));
    if ($real && is_file($real) && $publicBuild && str_starts_with($real, $publicBuild)) {
        $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
        $mimes = [
            'js' => 'application/javascript; charset=utf-8',
            'mjs' => 'application/javascript; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'webmanifest' => 'application/manifest+json; charset=utf-8',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
            'ttf' => 'font/ttf',
        ];
        $contentType = $mimes[$ext] ?? 'application/octet-stream';
        return response(file_get_contents($real), 200, [
            'Content-Type' => $contentType,
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
    return response('File not found', 404);
})->where('path', '.*');

// Storage Direct & Cloudflare R2 Proxy Route with Master Fallback
Route::get('/storage/{path}', function ($path) {
    if (str_contains($path, '..') || str_contains($path, "\0")) {
        abort(404);
    }
    $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

    // 1. Check local file in storage/app/public or public/storage
    $localCandidates = [
        storage_path('app/public/' . $cleanPath),
        public_path('storage/' . $cleanPath),
        base_path('public/storage/' . $cleanPath),
    ];
    $allowedRoots = array_filter([
        realpath(storage_path('app/public')),
        realpath(public_path('storage')),
        realpath(base_path('public/storage')),
    ]);

    foreach ($localCandidates as $loc) {
        $real = realpath($loc);
        if ($real && is_file($real)) {
            $isSafe = false;
            foreach ($allowedRoots as $root) {
                if ($root && str_starts_with($real, $root)) {
                    $isSafe = true;
                    break;
                }
            }
            if ($isSafe) {
                $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
                $mimes = [
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'pdf' => 'application/pdf',
                ];
                return response(file_get_contents($real), 200, [
                    'Content-Type' => $mimes[$ext] ?? 'application/octet-stream',
                    'Access-Control-Allow-Origin' => '*',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }
    }

    // 2. Check in Cloudflare R2
    if (\App\Services\CloudflareR2::isConfigured() && \App\Services\CloudflareR2::exists($cleanPath)) {
        $content = \App\Services\CloudflareR2::get($cleanPath);
        if ($content !== null) {
            $ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
            $mimes = [
                'png' => 'image/png',
                'webp' => 'image/webp',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                'pdf' => 'application/pdf',
            ];
            return response($content, 200, [
                'Content-Type' => $mimes[$ext] ?? 'application/octet-stream',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
    }

    // 3. Fallback for logos
    if (str_contains($cleanPath, 'logo')) {
        $masterLogo = public_path('icons/company_logo_master.png');
        if (!file_exists($masterLogo)) {
            $masterLogo = base_path('public/icons/company_logo_master.png');
        }
        if (file_exists($masterLogo)) {
            return response(file_get_contents($masterLogo), 200, [
                'Content-Type' => 'image/png',
                'Access-Control-Allow-Origin' => '*',
            ]);
        }
    }

    return response('File not found', 404);
})->where('path', '.*');

// PWA Manifest & Dynamic App Icon Routes
Route::get('/manifest.webmanifest', [\App\Http\Controllers\SettingController::class, 'manifest'])->name('pwa.manifest');
Route::get('/manifest.json', [\App\Http\Controllers\SettingController::class, 'manifest']);
Route::get('/app-icon/{size?}', [\App\Http\Controllers\SettingController::class, 'getAppIcon'])->name('pwa.icon');
Route::get('/icons/{filename}', function ($filename) {
    if (str_contains($filename, '..') || str_contains($filename, "\0")) {
        abort(404);
    }
    $cleanFilename = basename($filename);
    $path = public_path('icons/' . $cleanFilename);
    $real = realpath($path);
    $iconsDir = realpath(public_path('icons'));
    if ($real && is_file($real) && $iconsDir && str_starts_with($real, $iconsDir)) {
        return response(file_get_contents($real), 200, [
            'Content-Type' => 'image/png',
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
    return response('Icon not found', 404);
})->where('filename', '.*');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Karyawan Leave Requests
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::get('/leave-requests/create', [LeaveRequestController::class, 'create'])->name('leave-requests.create');
    Route::get('/leave-requests/report/print', [LeaveRequestController::class, 'printPersonalReport'])->name('leave-requests.report.print');
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
    Route::get('/hrd/employees/export-biodata', [HrdController::class, 'exportBiodataCsv'])->name('hrd.employees.export-biodata');
    Route::get('/hrd/employees/{userId}/biodata', [HrdController::class, 'employeeBiodata'])->name('hrd.employees.biodata');
    Route::get('/hrd/employees/biodata/{userId}', [HrdController::class, 'employeeBiodata'])->name('hrd.employees.biodata.alt');
    Route::match(['put', 'post'], '/hrd/employees/{userId}/biodata', [HrdController::class, 'updateEmployeeBiodata'])->name('hrd.employees.biodata.update');
    Route::match(['put', 'post'], '/hrd/employees/biodata/{userId}/update', [HrdController::class, 'updateEmployeeBiodata']);
    Route::match(['put', 'post'], '/hrd/employees/biodata/{userId}', [HrdController::class, 'updateEmployeeBiodata']);
    Route::get('/hrd/employees/{userId}/biodata/print', [HrdController::class, 'printEmployeeBiodata'])->name('hrd.employees.biodata.print');
    Route::get('/hrd/employees/biodata/{userId}/print', [HrdController::class, 'printEmployeeBiodata'])->name('hrd.employees.biodata.print.alt');
    Route::post('/hrd/employees', [HrdController::class, 'storeEmployee'])->name('hrd.employees.store');
    Route::post('/hrd/employees/{userId}/update', [HrdController::class, 'updateEmployee'])->name('hrd.employees.update');
    Route::delete('/hrd/employees/{userId}', [HrdController::class, 'destroyEmployee'])->name('hrd.employees.destroy');
    Route::post('/hrd/employees/{userId}/quota', [HrdController::class, 'updateQuota'])->name('hrd.update-quota');
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

    // Profile & Biodata Routes
    Route::get('/profile/biodata', [\App\Http\Controllers\ProfileController::class, 'biodata'])->name('profile.biodata');
    Route::put('/profile/biodata', [\App\Http\Controllers\ProfileController::class, 'updateBiodata'])->name('profile.biodata.update');
    Route::get('/profile/biodata/print', [\App\Http\Controllers\ProfileController::class, 'printBiodata'])->name('profile.biodata.print');
    Route::post('/profile/avatar', [\App\Http\Controllers\ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');

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


