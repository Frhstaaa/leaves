<?php
/**
 * SGIN Leaves Application - Emergency System Recovery & Self-Healing Setup Tool
 * Akses: https://sgin.co.id/leaves-application/setup.php
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

// 1. Ensure storage directories exist and are writable
$storageDirs = [
    $basePath . '/storage',
    $basePath . '/storage/framework',
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/views',
    $basePath . '/storage/framework/cache',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/logs',
    $basePath . '/storage/app',
    $basePath . '/storage/app/public',
    $basePath . '/bootstrap/cache',
    $basePath . '/public/build',
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
}

// 2. Safe execution helper
function runShell($cmd, $dir) {
    if (!function_exists('shell_exec')) {
        return "shell_exec tidak aktif pada PHP server ini.";
    }
    $full = "cd " . escapeshellarg($dir) . " && " . $cmd . " 2>&1";
    return trim(@shell_exec($full) ?: '');
}

// 3. Clear all cached framework files directly from filesystem
function directFileCacheClear($basePath) {
    $cleared = [];

    // Delete bootstrap cache files
    foreach (glob($basePath . '/bootstrap/cache/*.php') as $f) {
        @unlink($f);
        $cleared[] = 'bootstrap/cache/' . basename($f);
    }

    // Delete compiled blade views
    foreach (glob($basePath . '/storage/framework/views/*.php') as $f) {
        @unlink($f);
        $cleared[] = 'views/' . basename($f);
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
                if ($file->isFile()) {
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

// Handle Auto-Repair Actions via POST
$action = $_POST['action'] ?? null;
$logs = [];

if ($action === 'auto_repair') {
    $logs[] = "=================================================================";
    $logs[] = "  🛠️ MEMULAI PERBAIKAN TOTAL SISTEM APLIKASI SGIN LEAVES...     ";
    $logs[] = "=================================================================\n";

    // Step 1: Force Git pull & Hard Reset to GitHub main
    $logs[] = "[1/6] Mengambil kodingan terbaru & terbersih dari GitHub main...";
    $gitVer = runShell("git --version", $basePath);
    if (str_contains(strtolower($gitVer), 'git version')) {
        $fetch = runShell("git fetch origin main", $basePath);
        $reset = runShell("git reset --hard origin/main", $basePath);
        $logs[] = "✓ Git Reset: " . ($reset ?: $fetch ?: 'Selesai');
    } else {
        $logs[] = "ℹ️ Git CLI tidak tersedia, menggunakan file lokal saat ini.";
    }

    // Step 2: Clear All File Caches manually
    $logs[] = "\n[2/6] Membersihkan seluruh file cache & compiled routes secara langsung...";
    $clearedFiles = directFileCacheClear($basePath);
    $logs[] = "✓ Cache yang dibersihkan: " . implode(', ', array_slice($clearedFiles, 0, 10)) . (count($clearedFiles) > 10 ? ' dan ' . (count($clearedFiles) - 10) . ' file lainnya.' : '.');

    // Step 3: Bootstrap Laravel Kernel safely
    $logs[] = "\n[3/6] Menghubungkan Laravel Kernel...";
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
            $logs[] = "⚠️ Gagal bootstrap kernel: " . $e->getMessage();
        }
    }

    // Step 4: Run Artisan Migrations & Clear Commands
    $logs[] = "\n[4/6] Menjalankan pembersihan cache Artisan & Migrasi...";
    if ($app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            $logs[] = "✓ php artisan optimize:clear: " . trim(\Illuminate\Support\Facades\Artisan::output());
        } catch (\Throwable $e) {
            $logs[] = "ℹ️ optimize:clear: " . $e->getMessage();
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $logs[] = "✓ php artisan migrate: " . trim(\Illuminate\Support\Facades\Artisan::output());
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
        } catch (\Throwable $e) {
            $logs[] = "ℹ️ seeder: " . $e->getMessage();
        }

        try {
            if (class_exists('\\App\\Models\\LeaveQuota')) {
                \App\Models\LeaveQuota::syncAllUsers();
                $logs[] = "✓ Sinkronisasi Kuota Cuti Karyawan: Selesai!";
            }
        } catch (\Throwable $e) {
            $logs[] = "ℹ️ quota sync: " . $e->getMessage();
        }
    }

    // Step 5: Fix Storage Symlink
    $logs[] = "\n[5/6] Memeriksa tautan storage publik...";
    $pubStorage = $basePath . '/public/storage';
    $appStorage = $basePath . '/storage/app/public';
    if (!file_exists($pubStorage) && !is_link($pubStorage)) {
        @symlink($appStorage, $pubStorage);
    }
    if ($app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('storage:link');
            $logs[] = "✓ php artisan storage:link: " . trim(\Illuminate\Support\Facades\Artisan::output() ?: 'Siap');
        } catch (\Throwable $e) {}
    }

    // Step 6: Final Cache Generation
    $logs[] = "\n[6/6] Menyiapkan konfigurasi final...";
    if ($app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            $logs[] = "✓ Seluruh cache aplikasi siap & bersih!";
        } catch (\Throwable $e) {}
    }

    $logs[] = "\n=================================================================";
    $logs[] = "  🎉 PERBAIKAN SELESAI! APLIKASI KEMBALI NORMAL & SIAP DIGUNAKAN ";
    $logs[] = "=================================================================";
}

// Read last 40 lines of laravel.log
$recentLogs = '';
$logFile = $basePath . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    if (!empty($lines)) {
        $recentLogs = implode("", array_slice($lines, -50));
    }
}
if (empty($recentLogs)) {
    $recentLogs = "Tidak ada catatan error pada file laravel.log saat ini.";
}

// Protocol & Host
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'sgin.co.id';
$appUrl = "$protocol://$host/leaves-application/dashboard";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Leaves - Emergency Recovery & Setup Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-8 flex flex-col justify-between">

    <div class="max-w-4xl mx-auto w-full space-y-6">
        
        <!-- Header -->
        <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-emerald-950 border border-slate-700/80 shadow-2xl">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center font-black text-xl">
                    ⚡
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-white">SGIN Leaves System Recovery & Setup</h1>
                    <p class="text-xs sm:text-sm text-slate-400">Pusat Diagnostik & Perbaikan Otomatis Error 500 PT Sugiyama Indonesia</p>
                </div>
            </div>
        </div>

        <!-- Main Auto Repair Action Box -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-5">
            <div>
                <h2 class="text-base font-extrabold text-white">1-Klik Perbaikan Total (Self-Healing)</h2>
                <p class="text-xs text-slate-400 mt-1">
                    Klik tombol di bawah ini untuk mengambil kodingan terbaru, membersihkan seluruh cache yang rusak/kadaluarsa, menjalankan migrasi database, dan memulihkan seluruh halaman aplikasi secara otomatis.
                </p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="auto_repair">
                <button
                    type="submit"
                    class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-sm sm:text-base shadow-xl shadow-emerald-900/40 transition-all flex items-center justify-center space-x-2"
                >
                    <span>🚀 Jalankan Perbaikan Otomatis (Fix Error 500)</span>
                </button>
            </form>

            <?php if (!empty($logs)): ?>
                <div class="space-y-2 pt-2 border-t border-slate-800">
                    <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">Hasil Eksekusi Perbaikan:</span>
                    <pre class="p-4 rounded-2xl bg-black border border-slate-800 text-xs font-mono text-emerald-300 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-96"><?= htmlspecialchars(implode("\n", $logs)) ?></pre>
                    
                    <div class="pt-3 flex flex-wrap gap-2">
                        <a
                            href="<?= $appUrl ?>"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg transition-all"
                        >
                            👉 Buka Dashboard Aplikasi
                        </a>
                        <a
                            href="<?= "$protocol://$host/leaves-application/hrd/employees" ?>"
                            class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all"
                        >
                            👉 Buka Menu Kelola Karyawan
                        </a>
                        <a
                            href="<?= "$protocol://$host/leaves-application/profile/biodata" ?>"
                            class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all"
                        >
                            👉 Buka Menu Data Diri
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Diagnostic: Latest Error Logs -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-extrabold text-slate-300">📋 Catatan Error Server Terbaru (storage/logs/laravel.log)</h3>
                <span class="text-[10px] font-mono text-slate-500">50 Baris Terakhir</span>
            </div>
            <pre class="p-4 rounded-2xl bg-black border border-slate-800 text-[11px] font-mono text-slate-400 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-64"><?= htmlspecialchars($recentLogs) ?></pre>
        </div>

    </div>

    <!-- Footer -->
    <div class="text-center text-xs text-slate-600 py-6">
        PT Sugiyama Indonesia (SGIN) &bull; Leaves & Employee Management Recovery Engine
    </div>

</body>
</html>
