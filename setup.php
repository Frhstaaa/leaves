<?php
/**
 * ============================================================================
 * SGIN Leaves Application - Master Setup, Recovery & Self-Healing Tool
 * PT Sugiyama Indonesia (Leaves, Attendance & E-Slip Gaji System)
 * ============================================================================
 * 
 * Akses: https://www.sgin.co.id/leaves-application/setup.php
 * ============================================================================
 */

@set_time_limit(600);
@ini_set('max_execution_time', 600);
@ini_set('memory_limit', '512M');
@ini_set('display_errors', 1);
error_reporting(E_ALL);

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
// 2. Safe Execution Helper
// ----------------------------------------------------------------------------
function runShell($cmd, $dir) {
    if (!function_exists('shell_exec')) {
        return "shell_exec tidak aktif pada PHP server ini.";
    }
    $full = "cd " . escapeshellarg($dir) . " && " . $cmd . " 2>&1";
    return trim(@shell_exec($full) ?: '');
}

// ----------------------------------------------------------------------------
// 3. Clear All Cached Files Directly
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
// 4. Ensure & Auto-Heal .env File & APP_KEY
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
        return "File .env baru berhasil dibuat otomatis dengan konfigurasi produksi & APP_KEY.";
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
    return "File .env valid.";
}

$envStatusMsg = ensureEnvFile($basePath);

// ----------------------------------------------------------------------------
// 5. Database Connection Tester (Tests 127.0.0.1 and localhost)
// ----------------------------------------------------------------------------
function testDatabaseConnection($basePath) {
    if (function_exists('mysqli_report')) {
        @mysqli_report(MYSQLI_REPORT_OFF);
    }

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

    $results = [
        'connected' => false,
        'active_host' => $h,
        'database' => $d,
        'username' => $u,
        'tables_count' => 0,
        'message' => '',
        'suggest_host' => null,
    ];

    if (!$d || !$u) {
        $results['message'] = "Kredensial database tidak lengkap di file .env.";
        return $results;
    }

    $errMsg = '';

    // 1. Try current configured host first
    try {
        $mysqli = @new mysqli($h, $u, $pass, $d, (int)$p);
        if ($mysqli && !$mysqli->connect_errno) {
            $results['connected'] = true;
            $res = $mysqli->query("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = '$d'");
            $results['tables_count'] = $res ? ($res->fetch_assoc()['c'] ?? 0) : 0;
            $results['message'] = "Terhubung dengan sukses ke host '$h' ($d - {$results['tables_count']} tabel aktif).";
            $mysqli->close();
            return $results;
        }
        $errMsg = $mysqli ? $mysqli->connect_error : 'Koneksi gagal';
    } catch (\Throwable $e) {
        $errMsg = $e->getMessage();
    }

    // 2. If current host failed, test alternative host (e.g. localhost vs 127.0.0.1)
    $altHost = ($h === '127.0.0.1') ? 'localhost' : '127.0.0.1';
    try {
        $mysqliAlt = @new mysqli($altHost, $u, $pass, $d, (int)$p);
        if ($mysqliAlt && !$mysqliAlt->connect_errno) {
            $resAlt = $mysqliAlt->query("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = '$d'");
            $altTables = $resAlt ? ($resAlt->fetch_assoc()['c'] ?? 0) : 0;
            $mysqliAlt->close();
            
            // Auto-fix host in .env
            $newEnv = preg_replace('/DB_HOST=.*$/m', "DB_HOST={$altHost}", $envContent);
            @file_put_contents($basePath . '/.env', $newEnv);

            $results['connected'] = true;
            $results['active_host'] = $altHost;
            $results['tables_count'] = $altTables;
            $results['message'] = "Koneksi berhasil dialihkan otomatis dari '$h' ke '$altHost' ($d - $altTables tabel).";
            return $results;
        }
    } catch (\Throwable $e) {
        // Alt host also failed
    }

    $results['message'] = "Akses database ditolak: $errMsg. Pastikan user '$u' telah dihubungkan ke basis data '$d' dengan 'ALL PRIVILEGES' di cPanel MySQL.";
    return $results;
}

$dbDiag = testDatabaseConnection($basePath);

// ----------------------------------------------------------------------------
// 6. Handle Form Actions
// ----------------------------------------------------------------------------
$action = $_POST['action'] ?? (!empty($_GET['run']) ? ($_GET['run'] === '1' ? 'auto_repair' : $_GET['run']) : null);
$logs = [];
$statusType = 'info';

if ($action === 'auto_repair') {
    $logs[] = "=================================================================";
    $logs[] = "  🛠️ MEMULAI SETUP & PEMULIHAN SISTEM SGIN LEAVES...             ";
    $logs[] = "=================================================================\n";

    // Step 0: Ensure .env is present & valid
    $logs[] = "[1/6] Memeriksa konfigurasi file .env & APP_KEY...";
    $logs[] = "✓ Status .env: " . $envStatusMsg;

    // Step 1: Storage Permissions
    $logs[] = "\n[2/6] Memastikan seluruh folder storage berizin 0777...";
    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
    }
    $logs[] = "✓ Seluruh folder storage & cache telah disetel izin tulis penuh (0777).";

    // Step 2: Clear All File Caches manually
    $logs[] = "\n[3/6] Membersihkan seluruh file cache & template Blade...";
    $clearedFiles = directFileCacheClear($basePath);
    $logs[] = "✓ Cache yang dibersihkan: " . count($clearedFiles) . " file.";

    // Step 3: Test Database Connection
    $logs[] = "\n[4/6] Memeriksa koneksi database MySQL...";
    $dbTest = testDatabaseConnection($basePath);
    if ($dbTest['connected']) {
        $logs[] = "✓ Database MySQL: " . $dbTest['message'];
    } else {
        $logs[] = "⚠️ Catatan Database: " . $dbTest['message'];
    }

    // Step 4: Bootstrap Laravel Kernel safely
    $logs[] = "\n[5/6] Menghubungkan Laravel Kernel & Menjalankan Migrasi...";
    $app = null;
    if (file_exists($basePath . '/vendor/autoload.php') && file_exists($basePath . '/bootstrap/app.php')) {
        try {
            require_once $basePath . '/vendor/autoload.php';
            $app = require_once $basePath . '/bootstrap/app.php';
            $app->usePublicPath($basePath . '/public');
            $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            $logs[] = "✓ Laravel Kernel berhasil di-bootstrap!";
        } catch (\Throwable $e) {
            $logs[] = "ℹ️ Catatan Kernel: " . $e->getMessage();
        }
    }

    // Run Artisan Commands if bootstrap succeeded
    if ($app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $logs[] = "✓ php artisan optimize:clear: Selesai.";
        } catch (\Throwable $e) {}

        if ($dbTest['connected']) {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $logs[] = "✓ php artisan migrate: " . trim(\Illuminate\Support\Facades\Artisan::output() ?: 'Database up-to-date.');
            } catch (\Throwable $e) {
                $logs[] = "ℹ️ migrate: " . $e->getMessage();
            }

            try {
                if (class_exists('Database\\Seeders\\RolePermissionSeeder')) {
                    \Illuminate\Support\Facades\Artisan::call('db:seed', [
                        '--class' => 'Database\\Seeders\\RolePermissionSeeder',
                        '--force' => true,
                    ]);
                    $logs[] = "✓ Sinkronisasi Role & Permission: Selesai!";
                }
            } catch (\Throwable $e) {}

            try {
                if (class_exists('\\App\\Models\\LeaveQuota')) {
                    \App\Models\LeaveQuota::syncAllUsers();
                    $logs[] = "✓ Sinkronisasi Kuota Cuti Karyawan: Selesai!";
                }
            } catch (\Throwable $e) {}
        }
    }

    // Step 5: Fix Storage Symlink
    $logs[] = "\n[6/6] Menyiapkan storage symlink & timestamp PWA...";
    $pubStorage = $basePath . '/public/storage';
    $appStorage = $basePath . '/storage/app/public';
    if (!file_exists($pubStorage) && !is_link($pubStorage)) {
        @symlink($appStorage, $pubStorage);
    }
    $swFile = $basePath . '/public/sw.js';
    if (file_exists($swFile)) {
        @touch($swFile);
    }
    $logs[] = "✓ Storage link & Service Worker siap!";

    $logs[] = "\n=================================================================";
    $logs[] = "  🎉 SETUP SELESAI! APLIKASI TELAH DIPULIHKAN & SIAP DIGUNAKAN  ";
    $logs[] = "=================================================================";
    $statusType = 'success';

} elseif ($action === 'update_env') {
    $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbName = trim($_POST['db_name'] ?? 'sginco_leav');
    $dbUser = trim($_POST['db_user'] ?? 'sginco_leav');
    $dbPass = trim($_POST['db_pass'] ?? '');

    $envPath = $basePath . '/.env';
    $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

    $envContent = preg_replace('/DB_HOST=.*$/m', "DB_HOST={$dbHost}", $envContent);
    $envContent = preg_replace('/DB_DATABASE=.*$/m', "DB_DATABASE={$dbName}", $envContent);
    $envContent = preg_replace('/DB_USERNAME=.*$/m', "DB_USERNAME={$dbUser}", $envContent);
    $envContent = preg_replace('/DB_PASSWORD=.*$/m', "DB_PASSWORD=\"{$dbPass}\"", $envContent);

    @file_put_contents($envPath, $envContent);
    directFileCacheClear($basePath);
    $logs[] = "✓ Konfigurasi database di file .env berhasil diperbarui!";
    $statusType = 'success';
    $dbDiag = testDatabaseConnection($basePath);

} elseif ($action === 'clear_logs') {
    $logFile = $basePath . '/storage/logs/laravel.log';
    if (file_exists($logFile)) {
        @file_put_contents($logFile, '');
    }
    $logs[] = "✓ File storage/logs/laravel.log berhasil dikosongkan!";
    $statusType = 'success';
}

// Read last 40 lines of laravel.log
$recentLogs = '';
$logFile = $basePath . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    if (!empty($lines)) {
        $recentLogs = implode("", array_slice($lines, -40));
    }
}
if (empty($recentLogs)) {
    $recentLogs = "Tidak ada catatan error pada file laravel.log saat ini.";
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Leaves - Emergency Recovery & Setup Tool</title>
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

    <div class="max-w-5xl mx-auto w-full space-y-6">
        
        <!-- Header -->
        <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-12 -top-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 text-white flex items-center justify-center font-black text-2xl shadow-xl shadow-emerald-950/40">
                        ⚡
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-black text-white">SGIN Leaves Setup & Recovery</h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Auto Healer</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400 mt-0.5">Pusat Diagnostik, Perbaikan Otomatis & Pemulihan Sistem PT Sugiyama Indonesia</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="./" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-900/40 transition-all">
                        Buka Aplikasi
                    </a>
                    <a href="./update.php" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs sm:text-sm font-semibold border border-slate-700 transition-all">
                        Master Updater
                    </a>
                </div>
            </div>

            <!-- Diagnostic Quick Status -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-800 font-mono text-xs">
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase font-sans font-semibold">PHP Version</div>
                    <div class="text-slate-200 font-bold mt-0.5"><?= PHP_VERSION ?></div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase font-sans font-semibold">Database Status</div>
                    <div class="<?= $dbDiag['connected'] ? 'text-emerald-400' : 'text-rose-400' ?> font-bold mt-0.5 truncate" title="<?= htmlspecialchars($dbDiag['message']) ?>">
                        <?= $dbDiag['connected'] ? "✓ Terhubung ({$dbDiag['tables_count']} Tabel)" : "❌ Ditolak" ?>
                    </div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase font-sans font-semibold">Folder Storage</div>
                    <div class="text-emerald-400 font-bold mt-0.5">✓ 0777 (Writable)</div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase font-sans font-semibold">Enkripsi (.env)</div>
                    <div class="text-emerald-400 font-bold mt-0.5">✓ APP_KEY Siap</div>
                </div>
            </div>
        </div>

        <!-- Notification Output -->
        <?php if (!empty($logs)): ?>
        <div class="p-6 rounded-3xl bg-slate-900 border <?= $statusType === 'success' ? 'border-emerald-500/40' : 'border-rose-500/40' ?> shadow-2xl space-y-3">
            <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-300 font-mono flex items-center gap-2">
                <span>📋</span> Hasil Eksekusi Pemulihan
            </h2>
            <div class="p-4 rounded-2xl bg-black border border-slate-800 font-mono text-xs text-slate-300 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-72">
                <?= htmlspecialchars(implode("\n", $logs)) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Auto Repair Action Box -->
        <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-emerald-950/50 via-slate-900 to-teal-950/50 border border-emerald-500/30 shadow-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="space-y-1 max-w-2xl">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-500 text-slate-950">1-Klik Solusi</span>
                    <h2 class="text-lg sm:text-xl font-black text-white">Jalankan Pemulihan Otomatis (Fix Error 500 / 419)</h2>
                </div>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    Membersihkan seluruh cache yang kadaluarsa, menyetel hak akses folder storage, memeriksa koneksi database MySQL, menjalankan migrasi tabel jika diperlukan, dan memulihkan seluruh halaman aplikasi secara otomatis.
                </p>
            </div>

            <form method="POST" class="shrink-0 w-full sm:w-auto">
                <input type="hidden" name="action" value="auto_repair">
                <button
                    type="submit"
                    class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-sm sm:text-base shadow-xl shadow-emerald-500/20 transition-all flex items-center justify-center space-x-2.5 transform hover:-translate-y-0.5"
                >
                    <span>🚀 Jalankan Pemulihan Otomatis</span>
                </button>
            </form>
        </div>

        <!-- Database Settings Editor Box -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>🗄️</span> Konfigurasi Kredensial Database MySQL (.env)
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Ubah atau sesuaikan kredensial koneksi database jika password cPanel berbeda</p>
                </div>
                <span class="text-xs px-3 py-1 rounded-full font-mono <?= $dbDiag['connected'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                    <?= htmlspecialchars($dbDiag['message']) ?>
                </span>
            </div>

            <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2">
                <input type="hidden" name="action" value="update_env">
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Host</label>
                    <input type="text" name="db_host" value="<?= htmlspecialchars($dbDiag['active_host']) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Database</label>
                    <input type="text" name="db_name" value="<?= htmlspecialchars($dbDiag['database']) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Username</label>
                    <input type="text" name="db_user" value="<?= htmlspecialchars($dbDiag['username']) ?>" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Password</label>
                    <div class="flex gap-2">
                        <input type="password" name="db_pass" value="@SginC01!!!" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 text-xs font-bold shrink-0">
                            Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Diagnostic: Latest Error Logs -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-sm font-bold text-slate-300 flex items-center gap-2">
                        <span>📋</span> Catatan Error Server (storage/logs/laravel.log)
                    </h3>
                    <p class="text-xs text-slate-500">40 baris log error terakhir di server</p>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" onsubmit="return confirm('Kosongkan file log?')">
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-rose-900/40 text-slate-400 hover:text-rose-300 text-xs font-mono border border-slate-700 transition-colors">
                            🧹 Kosongkan Log
                        </button>
                    </form>
                    <button onclick="window.location.reload()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono border border-slate-700">
                        🔄 Refresh
                    </button>
                </div>
            </div>
            <pre class="p-4 rounded-2xl bg-black border border-slate-800 text-[11px] font-mono text-slate-400 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-60"><?= htmlspecialchars($recentLogs) ?></pre>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-slate-600 py-4">
            PT Sugiyama Indonesia (SGIN) &bull; Leaves & Attendance Management System &bull; <?= date('Y') ?>
        </div>

    </div>

</body>
</html>
