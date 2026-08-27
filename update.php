<?php
/**
 * ============================================================================
 * SGIN Leaves Application - Master Web Updater & Server Management Center
 * PT Sugiyama Indonesia (Leaves & Attendance Management System)
 * ============================================================================
 * 
 * Tool Pembaruan Komprehensif (All-in-One):
 * 1. 🚀 1-Klik Update Total (GitHub Sync + Cache Purge + Safe Migrate + Permissions)
 * 2. ⚡ Update Frontend Saja (React / Inertia / Blade / CSS / PWA Assets)
 * 3. 🔄 Tarik Kode Terbaru dari GitHub (Hybrid cURL ZIP Sync & Git CLI)
 * 4. 🧹 Pembersihan Total Cache Aplikasi & OPcache
 * 5. 🛠️ Self-Healing: Perbaikan .env, APP_KEY & Izin Folder Storage
 * 6. 📦 Unggah & Ekstraksi Paket ZIP
 * 7. 🤖 Webhook Otomatis untuk GitHub Push
 * 
 * Akses: https://www.sgin.co.id/leaves-application/update.php
 * ============================================================================
 */

@set_time_limit(600);
@ini_set('max_execution_time', 600);
@ini_set('memory_limit', '512M');
@ini_set('display_errors', 1);
error_reporting(E_ALL);

define('APP_NAME_SYSTEM', 'SGIN Leaves Management');
define('GITHUB_REPO', 'Frhstaaa/leaves');
define('GITHUB_BRANCH', 'main');
define('WEBHOOK_SECRET', 'sgin-secret-webhook-key');

// Determine base path
$basePath = __DIR__;
if (!file_exists($basePath . '/vendor/autoload.php')) {
    if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        $basePath = dirname(__DIR__);
    }
}

// ----------------------------------------------------------------------------
// 1. Storage & Directory Preparation
// ----------------------------------------------------------------------------
$storageDirs = [
    $basePath . '/storage',
    $basePath . '/storage/app',
    $basePath . '/storage/app/public',
    $basePath . '/storage/app/public/logos',
    $basePath . '/storage/framework',
    $basePath . '/storage/framework/cache',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/views',
    $basePath . '/storage/framework/testing',
    $basePath . '/storage/logs',
    $basePath . '/bootstrap/cache',
    $basePath . '/public/build',
    $basePath . '/public/build/assets',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
}

// ----------------------------------------------------------------------------
// 2. Safe Shell Execution Helper
// ----------------------------------------------------------------------------
function runShell($cmd, $dir) {
    if (!function_exists('shell_exec')) {
        return "shell_exec() tidak aktif di PHP server ini.";
    }
    $full = "cd " . escapeshellarg($dir) . " && " . $cmd . " 2>&1";
    return trim(@shell_exec($full) ?: '');
}

// ----------------------------------------------------------------------------
// 3. Ensure & Auto-Heal .env File & APP_KEY
// ----------------------------------------------------------------------------
function ensureEnvFile($basePath) {
    $envPath = $basePath . '/.env';
    $defaultAppKey = 'base64:fn2kAMl3S31maRCtRvzVAluYwEGHTrVblVjLr4rxokE=';
    
    if (!file_exists($envPath)) {
        $envTemplate = <<<ENV
APP_NAME="Form SGIN"
APP_ENV=production
APP_KEY={$defaultAppKey}
APP_DEBUG=false
APP_URL=https://www.sgin.co.id/leaves-application

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sginco_leav
DB_USERNAME=sginco_leav
DB_PASSWORD="@SginC01!!!"

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="leaves@sgin.co.id"
MAIL_FROM_NAME="\${APP_NAME}"
ENV;
        @file_put_contents($envPath, $envTemplate);
        @chmod($envPath, 0644);
        return "File .env baru berhasil dibuat otomatis dengan kredensial produksi & APP_KEY.";
    } else {
        $content = @file_get_contents($envPath);
        if (!str_contains($content, 'APP_KEY=') || preg_match('/APP_KEY=\s*$/m', $content) || preg_match('/APP_KEY=\s*\r?\n/', $content)) {
            if (str_contains($content, 'APP_KEY=')) {
                $content = preg_replace('/APP_KEY=.*$/m', "APP_KEY={$defaultAppKey}", $content);
            } else {
                $content = "APP_KEY={$defaultAppKey}\n" . $content;
            }
            @file_put_contents($envPath, $content);
            return "APP_KEY berhasil ditambahkan/diperbaiki di file .env.";
        }
    }
    return "File .env sudah ada dan valid.";
}

ensureEnvFile($basePath);

// ----------------------------------------------------------------------------
// 4. Direct File Cache Cleaning (Without Laravel Kernel)
// ----------------------------------------------------------------------------
function directFileCacheClear($basePath) {
    $cleared = [];

    // Delete bootstrap cache files
    foreach (glob($basePath . '/bootstrap/cache/*.php') as $f) {
        if (basename($f) !== '.gitignore') {
            @unlink($f);
            $cleared[] = 'bootstrap/cache/' . basename($f);
        }
    }

    // Delete compiled blade views
    foreach (glob($basePath . '/storage/framework/views/*.php') as $f) {
        if (basename($f) !== '.gitignore') {
            @unlink($f);
            $cleared[] = 'views/' . basename($f);
        }
    }

    // Delete data cache files
    $cacheData = $basePath . '/storage/framework/cache/data';
    if (is_dir($cacheData)) {
        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($cacheData, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                if ($file->isFile() && $file->getFilename() !== '.gitignore') {
                    @unlink($file->getRealPath());
                } elseif ($file->isDir()) {
                    @rmdir($file->getRealPath());
                }
            }
            $cleared[] = 'storage/framework/cache/data/*';
        } catch (\Throwable $e) {}
    }

    // Reset OPcache if present
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $cleared[] = 'PHP OPcache Memory';
    }

    return $cleared;
}

// ----------------------------------------------------------------------------
// 5. GitHub Direct ZIP Sync (Bypasses Git CLI)
// ----------------------------------------------------------------------------
function syncFromGitHubZip($repo, $branch, $basePath, $frontendOnly = false) {
    $zipUrl = "https://github.com/$repo/archive/refs/heads/$branch.zip";
    
    if (!function_exists('curl_init')) {
        return ['success' => false, 'msg' => 'cURL tidak tersedia pada server PHP.'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SGIN-Master-Updater/3.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);
    $zipData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || empty($zipData)) {
        return ['success' => false, 'msg' => "Gagal mengunduh ZIP dari GitHub (HTTP $httpCode): $curlErr"];
    }

    $tempZip = $basePath . '/storage/github_sync_temp.zip';
    file_put_contents($tempZip, $zipData);

    if (!class_exists('ZipArchive')) {
        @unlink($tempZip);
        return ['success' => false, 'msg' => 'Ekstensi PHP ZipArchive tidak aktif pada server.'];
    }

    $extractPath = $basePath . '/storage/github_sync_extracted';
    if (is_dir($extractPath)) {
        $old = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($old as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
        @rmdir($extractPath);
    }
    @mkdir($extractPath, 0777, true);

    $zip = new ZipArchive();
    if ($zip->open($tempZip) !== TRUE) {
        @unlink($tempZip);
        return ['success' => false, 'msg' => 'Gagal membuka file ZIP hasil unduhan.'];
    }
    $zip->extractTo($extractPath);
    $zip->close();
    @unlink($tempZip);

    $sourceDir = '';
    foreach (scandir($extractPath) as $item) {
        if ($item !== '.' && $item !== '..' && is_dir("$extractPath/$item")) {
            $sourceDir = "$extractPath/$item";
            break;
        }
    }

    if (!$sourceDir) {
        return ['success' => false, 'msg' => 'Folder root repositori tidak ditemukan dalam file ZIP.'];
    }

    // Safety rules: Never overwrite .env or uploaded user storage
    $ignoreList = ['.env', '.env.production', 'storage/app/public/', 'public/storage', '.git/', 'node_modules/'];
    $allowedFrontend = ['public/build/', 'resources/', 'public/sw.js', 'public/manifest.webmanifest', 'public/icons/'];

    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $subPath = str_replace('\\', '/', substr($item->getPathname(), strlen($sourceDir) + 1));
        $destPath = $basePath . '/' . $subPath;

        // Check if ignored
        $skip = false;
        foreach ($ignoreList as $ig) {
            if (str_starts_with($subPath, rtrim($ig, '/'))) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;

        // If Frontend Only mode
        if ($frontendOnly) {
            $isFront = false;
            foreach ($allowedFrontend as $af) {
                if (str_starts_with($subPath, rtrim($af, '/'))) {
                    $isFront = true;
                    break;
                }
            }
            if (!$isFront) continue;
        }

        if ($item->isDir()) {
            if (!is_dir($destPath)) {
                @mkdir($destPath, 0777, true);
            }
        } else {
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0777, true);
            }
            if (@copy($item->getPathname(), $destPath)) {
                @chmod($destPath, 0644);
                $count++;
            }
        }
    }

    // Clean up temp extraction folder
    try {
        $old = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($old as $f) { $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname()); }
        @rmdir($extractPath);
    } catch (\Throwable $e) {}

    return ['success' => true, 'count' => $count];
}

// ----------------------------------------------------------------------------
// 6. Bootstrap Laravel Kernel Helper
// ----------------------------------------------------------------------------
function getLaravelApp($basePath) {
    if (file_exists($basePath . '/vendor/autoload.php') && file_exists($basePath . '/bootstrap/app.php')) {
        try {
            require_once $basePath . '/vendor/autoload.php';
            $app = require_once $basePath . '/bootstrap/app.php';
            $app->usePublicPath($basePath . '/public');
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            return $app;
        } catch (\Throwable $e) {
            return null;
        }
    }
    return null;
}

// ----------------------------------------------------------------------------
// 7. Handle Webhook from GitHub
// ----------------------------------------------------------------------------
if (isset($_GET['webhook']) || isset($_GET['key']) && $_GET['key'] === WEBHOOK_SECRET) {
    header('Content-Type: application/json; charset=utf-8');
    $sync = syncFromGitHubZip(GITHUB_REPO, GITHUB_BRANCH, $basePath);
    $cleared = directFileCacheClear($basePath);
    
    $app = getLaravelApp($basePath);
    if ($app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        } catch (\Throwable $e) {}
    }

    echo json_encode([
        'status' => $sync['success'] ? 'success' : 'error',
        'message' => $sync['success'] ? "Pembaruan otomatis webhook selesai! ({$sync['count']} file diperbarui)" : $sync['msg'],
        'cleared_cache_items' => count($cleared),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
    exit;
}

// ----------------------------------------------------------------------------
// 8. Handle Form Actions
// ----------------------------------------------------------------------------
$action = $_POST['action'] ?? (!empty($_GET['run']) ? $_GET['run'] : null);
$logs = [];
$statusType = 'info';

if ($action === 'full_update' || $action === '1') {
    $logs[] = "=================================================================";
    $logs[] = "  🚀 MEMULAI PEMBARUAN TOTAL APLIKASI SGIN LEAVES...             ";
    $logs[] = "=================================================================\n";

    // Step 1: Ensure .env
    $logs[] = "[1/6] Memeriksa integritas file .env & APP_KEY...";
    $envMsg = ensureEnvFile($basePath);
    $logs[] = "✓ Status .env: " . $envMsg;

    // Step 2: Sync Code from GitHub
    $logs[] = "\n[2/6] Mengambil kodingan terbaru dari GitHub (" . GITHUB_REPO . ":" . GITHUB_BRANCH . ")...";
    $gitVer = runShell("git --version", $basePath);
    $syncSuccess = false;

    if (str_contains(strtolower($gitVer), 'git version')) {
        $fetch = runShell("git fetch origin " . GITHUB_BRANCH, $basePath);
        $reset = runShell("git reset --hard origin/" . GITHUB_BRANCH, $basePath);
        $logs[] = "✓ Git Sync: " . ($reset ?: $fetch ?: 'Selesai');
        $syncSuccess = true;
    }

    if (!$syncSuccess) {
        $zipRes = syncFromGitHubZip(GITHUB_REPO, GITHUB_BRANCH, $basePath);
        if ($zipRes['success']) {
            $logs[] = "✓ Direct ZIP Sync: Berhasil memperbarui {$zipRes['count']} file dari GitHub.";
        } else {
            $logs[] = "⚠️ ZIP Sync: " . $zipRes['msg'];
        }
    }

    // Step 3: Direct Cache Purge
    $logs[] = "\n[3/6] Membersihkan seluruh cache file & template...";
    $cleared = directFileCacheClear($basePath);
    $logs[] = "✓ Cache yang dibersihkan: " . count($cleared) . " entri (Blade views, bootstrap, data cache).";

    // Step 4: Bootstrap Laravel & Safe Migrations
    $logs[] = "\n[4/6] Menghubungkan Laravel Kernel & Memeriksa Database...";
    $app = getLaravelApp($basePath);
    if ($app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $logs[] = "✓ Artisan optimize:clear: " . trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $logs[] = "ℹ️ optimize:clear: " . $e->getMessage();
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $logs[] = "✓ Artisan migrate: " . trim(\Illuminate\Support\Facades\Artisan::output() ?: 'Database up-to-date.');
        } catch (\Throwable $e) {
            $logs[] = "ℹ️ migrate: " . $e->getMessage();
        }

        try {
            if (class_exists('Database\\Seeders\\RolePermissionSeeder')) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\RolePermissionSeeder',
                    '--force' => true,
                ]);
                $logs[] = "✓ Role & Permission Sync: Selesai!";
            }
        } catch (\Throwable $e) {}

        try {
            if (class_exists('\\App\\Models\\LeaveQuota')) {
                \App\Models\LeaveQuota::syncAllUsers();
                $logs[] = "✓ Kuota Cuti Karyawan: Terverifikasi!";
            }
        } catch (\Throwable $e) {}
    } else {
        $logs[] = "ℹ️ Kernel bootstrap dilewati (menggunakan native filesystem updater).";
    }

    // Step 5: Storage Symlink & Permissions
    $logs[] = "\n[5/6] Memperbaiki izin folder storage & symlink...";
    $pubStorage = $basePath . '/public/storage';
    $appStorage = $basePath . '/storage/app/public';
    if (!file_exists($pubStorage) && !is_link($pubStorage)) {
        @symlink($appStorage, $pubStorage);
    }
    foreach ($storageDirs as $dir) {
        @chmod($dir, 0777);
    }
    $logs[] = "✓ Izin folder (0777) & Public Storage Symlink: Siap!";

    // Step 6: PWA & Service Worker Cache Buster
    $logs[] = "\n[6/6] Memperbarui timestamp Service Worker & Cache Browser...";
    $swFile = $basePath . '/public/sw.js';
    if (file_exists($swFile)) {
        @touch($swFile);
    }
    $logs[] = "✓ Service Worker timestamp diperbarui.";

    $logs[] = "\n=================================================================";
    $logs[] = "  🎉 PEMBARUAN TOTAL SELESAI! APLIKASI KEMBALI NORMAL & CEPAT     ";
    $logs[] = "=================================================================";
    $statusType = 'success';

} elseif ($action === 'frontend_only') {
    $logs[] = "=================================================================";
    $logs[] = "  ⚡ MEMULAI PEMBARUAN KHUSUS FRONTEND (DATABASE AMAN)...        ";
    $logs[] = "=================================================================\n";

    $zipRes = syncFromGitHubZip(GITHUB_REPO, GITHUB_BRANCH, $basePath, true);
    if ($zipRes['success']) {
        $logs[] = "✓ Berhasil memperbarui {$zipRes['count']} file frontend (React, Blade, CSS, JS, PWA).";
    } else {
        $logs[] = "⚠️ Sync Frontend: " . $zipRes['msg'];
    }

    $cleared = directFileCacheClear($basePath);
    $logs[] = "✓ " . count($cleared) . " file cache Blade & template dibersihkan.";

    $swFile = $basePath . '/public/sw.js';
    if (file_exists($swFile)) {
        @touch($swFile);
    }
    $logs[] = "✓ Service Worker & PWA cache-buster aktif.";

    $logs[] = "\n=================================================================";
    $logs[] = "  🎉 FRONTEND BERHASIL DIPERBARUI!                                ";
    $logs[] = "=================================================================";
    $statusType = 'success';

} elseif ($action === 'clear_cache') {
    $cleared = directFileCacheClear($basePath);
    $app = getLaravelApp($basePath);
    if ($app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        } catch (\Throwable $e) {}
    }
    $logs[] = "✓ Pembersihan cache selesai! (" . count($cleared) . " komponen dibersihkan).";
    $statusType = 'success';

} elseif ($action === 'fix_permissions') {
    ensureEnvFile($basePath);
    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @chmod($dir, 0777);
    }
    $pubStorage = $basePath . '/public/storage';
    $appStorage = $basePath . '/storage/app/public';
    if (!file_exists($pubStorage) && !is_link($pubStorage)) {
        @symlink($appStorage, $pubStorage);
    }
    $logs[] = "✓ Hak akses folder storage (0777) & perbaikan .env selesai!";
    $statusType = 'success';

} elseif ($action === 'upload_zip') {
    if (isset($_FILES['zip_package']) && $_FILES['zip_package']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['zip_package']['tmp_name'];
        $zip = new ZipArchive();
        if ($zip->open($tmp) === TRUE) {
            $extracted = 0;
            $ignore = ['.env', '.env.production', 'storage/app/public/'];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                $skip = false;
                foreach ($ignore as $ig) {
                    if (str_starts_with($name, $ig)) { $skip = true; break; }
                }
                if ($skip) continue;
                $dest = $basePath . '/' . $name;
                if (str_ends_with($name, '/')) {
                    if (!is_dir($dest)) @mkdir($dest, 0777, true);
                } else {
                    $d = dirname($dest);
                    if (!is_dir($d)) @mkdir($d, 0777, true);
                    file_put_contents($dest, $zip->getFromIndex($i));
                    $extracted++;
                }
            }
            $zip->close();
            directFileCacheClear($basePath);
            $logs[] = "✓ Paket ZIP berhasil diekstrak! ($extracted file diterapkan).";
            $statusType = 'success';
        } else {
            $logs[] = "❌ Gagal membuka file ZIP yang diunggah.";
            $statusType = 'error';
        }
    } else {
        $logs[] = "⚠️ Silakan pilih file ZIP terlebih dahulu sebelum mengunggah.";
        $statusType = 'warning';
    }
}

// ----------------------------------------------------------------------------
// 9. Diagnostics & Status Data
// ----------------------------------------------------------------------------
$manifestPath = $basePath . '/public/build/manifest.json';
$manifestInfo = null;
if (file_exists($manifestPath)) {
    $manifestData = json_decode(file_get_contents($manifestPath), true);
    $manifestInfo = [
        'entries' => count($manifestData ?: []),
        'last_modified' => date('d M Y, H:i:s', filemtime($manifestPath)),
        'size' => round(filesize($manifestPath) / 1024, 2) . ' KB',
    ];
}

// Check database connectivity
$dbStatus = 'Belum dicek';
$dbConnected = false;
try {
    $envContent = file_exists($basePath . '/.env') ? file_get_contents($basePath . '/.env') : '';
    preg_match('/DB_HOST=(.*)/', $envContent, $mHost);
    preg_match('/DB_PORT=(.*)/', $envContent, $mPort);
    preg_match('/DB_DATABASE=(.*)/', $envContent, $mDb);
    preg_match('/DB_USERNAME=(.*)/', $envContent, $mUser);
    preg_match('/DB_PASSWORD=(.*)/', $envContent, $mPass);

    $h = trim($mHost[1] ?? '127.0.0.1');
    $p = trim($mPort[1] ?? '3306');
    $d = trim($mDb[1] ?? 'sginco_leav');
    $u = trim($mUser[1] ?? 'sginco_leav');
    $pass = trim(trim($mPass[1] ?? '', '"'), "'");

    if ($h && $d && $u) {
        $mysqli = @new mysqli($h, $u, $pass, $d, (int)$p);
        if ($mysqli->connect_errno) {
            $dbStatus = "Koneksi Gagal: " . $mysqli->connect_error;
        } else {
            $dbConnected = true;
            $res = $mysqli->query("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = '$d'");
            $rowCount = $res ? ($res->fetch_assoc()['c'] ?? 0) : 0;
            $dbStatus = "Terhubung ($d, $rowCount Tabel Aktif)";
            $mysqli->close();
        }
    }
} catch (\Throwable $e) {
    $dbStatus = "Pemeriksaan Gagal: " . $e->getMessage();
}

// Read last 40 lines of error logs
$recentLogs = '';
$logFile = $basePath . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    if (!empty($lines)) {
        $recentLogs = implode("", array_slice($lines, -40));
    }
}
if (empty($recentLogs)) {
    $recentLogs = "Tidak ada catatan error pada storage/logs/laravel.log (Server Bersih).";
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Leaves - Master Updater & Server Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 p-4 sm:p-8 flex flex-col justify-between">
    <div class="max-w-6xl mx-auto w-full space-y-6">
        
        <!-- Top Bar Header -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden">
            <div class="absolute -right-10 -top-10 w-56 h-56 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-10 -bottom-10 w-56 h-56 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 relative z-10">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-400 flex items-center justify-center font-black text-3xl text-white shadow-xl shadow-emerald-600/30">
                        ⚡
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">SGIN Leaves Master Updater</h1>
                            <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">v3.0 Production</span>
                        </div>
                        <p class="text-slate-400 text-xs sm:text-sm mt-0.5">Pusat Kendali Pembaruan Aplikasi, Sinkronisasi GitHub, Frontend Build & Pemulihan Sistem</p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 flex-wrap">
                    <a href="./" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-900/40 transition-all">
                        <span>Buka Aplikasi</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <a href="./update-front-end.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs sm:text-sm font-semibold border border-slate-700 transition-all">
                        <span>Frontend Only</span>
                    </a>
                </div>
            </div>

            <!-- System Info Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-800/80 font-mono text-xs">
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase tracking-wider font-sans font-semibold">PHP Version</div>
                    <div class="text-slate-200 font-bold mt-0.5"><?= PHP_VERSION ?></div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase tracking-wider font-sans font-semibold">Database</div>
                    <div class="<?= $dbConnected ? 'text-emerald-400' : 'text-amber-400' ?> font-bold mt-0.5 truncate" title="<?= htmlspecialchars($dbStatus) ?>">
                        <?= $dbConnected ? '✓ Terhubung' : '⚠️ Periksa' ?>
                    </div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase tracking-wider font-sans font-semibold">GitHub Source</div>
                    <div class="text-slate-200 font-bold mt-0.5 truncate"><?= GITHUB_REPO ?></div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase tracking-wider font-sans font-semibold">Vite Build</div>
                    <div class="text-slate-200 font-bold mt-0.5"><?= $manifestInfo ? $manifestInfo['last_modified'] : 'Belum Ada' ?></div>
                </div>
            </div>
        </div>

        <!-- Notification / Execution Log Output -->
        <?php if (!empty($logs)): ?>
        <div class="bg-slate-900 border <?= $statusType === 'success' ? 'border-emerald-500/40' : ($statusType === 'error' ? 'border-rose-500/40' : 'border-amber-500/40') ?> rounded-3xl p-6 shadow-2xl space-y-3 animate-fade-in">
            <div class="flex items-center justify-between">
                <h2 class="text-xs sm:text-sm font-bold tracking-wider uppercase text-slate-300 flex items-center gap-2 font-mono">
                    <span>📋</span> Log Terminal Eksekusi Pembaruan
                </h2>
                <span class="text-xs <?= $statusType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?> px-3 py-1 rounded-full font-bold">
                    <?= $statusType === 'success' ? 'Berhasil' : 'Selesai' ?>
                </span>
            </div>
            <div class="p-4 rounded-2xl bg-black border border-slate-800 font-mono text-xs text-slate-300 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-72">
                <?= htmlspecialchars(implode("\n", $logs)) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Primary Action: 1-Click Total Update -->
        <div class="bg-gradient-to-r from-emerald-950/60 via-slate-900 to-teal-950/60 border border-emerald-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-500 text-slate-950">Rekomendasi Utama</span>
                        <h2 class="text-lg sm:text-xl font-black text-white">1-Klik Pembaruan Total (Full Sync)</h2>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-2xl">
                        Secara otomatis mendownload kodingan terbaru dari GitHub, memperbarui asset React/Inertia, membersihkan seluruh cache, menjalankan migrasi database non-destruktif, dan memulihkan seluruh izin folder.
                    </p>
                </div>

                <form method="POST" class="shrink-0 w-full sm:w-auto">
                    <input type="hidden" name="action" value="full_update">
                    <button type="submit" class="w-full sm:w-auto py-4 px-8 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-sm sm:text-base shadow-xl shadow-emerald-500/20 transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2.5">
                        <span>🚀 Jalankan Pembaruan Total</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Action Modular Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Modular Card 1: Frontend Only -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all space-y-4">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center font-bold text-lg">
                        ⚡
                    </div>
                    <h3 class="text-sm font-bold text-white">Update Frontend Saja</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Hanya menarik bundle React, CSS, Blade views, dan PWA tanpa menyentuh database atau backend.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="frontend_only">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-teal-400 border border-teal-500/30 text-xs font-bold transition-all">
                        Sync Frontend Aja
                    </button>
                </form>
            </div>

            <!-- Modular Card 2: Purge Cache -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all space-y-4">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-lg">
                        🧹
                    </div>
                    <h3 class="text-sm font-bold text-white">Bersihkan Cache Total</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Hapus seluruh file cache compiled blade, config, routes, bootstrap cache, dan reset PHP OPcache.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition-all">
                        Purge Seluruh Cache
                    </button>
                </form>
            </div>

            <!-- Modular Card 3: Fix Permissions & .env -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all space-y-4">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold text-lg">
                        🔒
                    </div>
                    <h3 class="text-sm font-bold text-white">Perbaiki Izin & .env</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Pulihkan APP_KEY, periksa .env, perbaiki symlink storage publik, dan terapkan permission 0777.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="fix_permissions">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-indigo-500/30 text-xs font-bold transition-all">
                        Self-Healing Permissions
                    </button>
                </form>
            </div>

            <!-- Modular Card 4: Upload Custom ZIP -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-5 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all space-y-4">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center font-bold text-lg">
                        📦
                    </div>
                    <h3 class="text-sm font-bold text-white">Unggah Paket ZIP</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Unggah dan ekstrak file ZIP rilis langsung ke server tanpa menimpa .env & data user.
                    </p>
                </div>
                <form method="POST" enctype="multipart/form-data" class="space-y-2">
                    <input type="hidden" name="action" value="upload_zip">
                    <input type="file" name="zip_package" accept=".zip" class="block w-full text-[10px] text-slate-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-slate-800 file:text-slate-200">
                    <button type="submit" class="w-full py-2 px-3 rounded-xl bg-cyan-600/20 hover:bg-cyan-600/30 text-cyan-300 border border-cyan-500/30 text-xs font-bold transition-all">
                        Upload & Terapkan
                    </button>
                </form>
            </div>

        </div>

        <!-- Diagnostic & Server Error Log Viewer -->
        <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>📜</span> Catatan Log Server (storage/logs/laravel.log)
                    </h3>
                    <p class="text-xs text-slate-400">Menampilkan 40 baris log error terakhir di server</p>
                </div>
                <button onclick="window.location.reload()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono font-medium border border-slate-700">
                    🔄 Refresh Log
                </button>
            </div>

            <div class="p-4 rounded-2xl bg-black border border-slate-800 text-[11px] font-mono text-slate-400 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-60">
                <?= htmlspecialchars($recentLogs) ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-slate-600 py-4">
            PT Sugiyama Indonesia (SGIN) &bull; Leaves Management Master Updater &bull; <?= date('Y') ?>
        </div>

    </div>
</body>
</html>
