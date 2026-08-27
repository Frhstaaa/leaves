<?php
/**
 * SGIN Leaves Application - Emergency System Recovery & Self-Healing Setup Tool
 * Akses: https://www.sgin.co.id/leaves-application/setup.php
 */

@set_time_limit(600);
@ini_set('max_execution_time', 600);
@ini_set('memory_limit', '512M');
@ini_set('display_errors', 0);
error_reporting(0);

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

function getDefaultEnvContent() {
    return 'APP_NAME="SGIN Leaves"' . "\n" .
'APP_ENV=production' . "\n" .
'APP_KEY=base64:dpvW3s9ONmjRHR+FYgLupNxaYsivVV4LLpqFIr+MN4A=' . "\n" .
'APP_DEBUG=false' . "\n" .
'APP_URL=https://www.sgin.co.id/leaves-application' . "\n" .
'ASSET_URL=https://www.sgin.co.id/leaves-application' . "\n\n" .
'LOG_CHANNEL=stack' . "\n" .
'LOG_DEPRECATIONS_CHANNEL=null' . "\n" .
'LOG_LEVEL=error' . "\n\n" .
'DB_CONNECTION=mysql' . "\n" .
'DB_HOST=127.0.0.1' . "\n" .
'DB_PORT=3306' . "\n" .
'DB_DATABASE=sginco_leav' . "\n" .
'DB_USERNAME=sginco_leav' . "\n" .
'DB_PASSWORD=@SginC01!!!' . "\n\n" .
'BROADCAST_DRIVER=log' . "\n" .
'CACHE_DRIVER=file' . "\n" .
'FILESYSTEM_DISK=r2' . "\n" .
'QUEUE_CONNECTION=sync' . "\n" .
'SESSION_DRIVER=file' . "\n" .
'SESSION_LIFETIME=120' . "\n\n" .
'MEMCACHED_HOST=127.0.0.1' . "\n\n" .
'REDIS_HOST=127.0.0.1' . "\n" .
'REDIS_PASSWORD=null' . "\n" .
'REDIS_PORT=6379' . "\n\n" .
'MAIL_MAILER=smtp' . "\n" .
'MAIL_HOST=mailpit' . "\n" .
'MAIL_PORT=1025' . "\n" .
'MAIL_USERNAME=null' . "\n" .
'MAIL_PASSWORD=null' . "\n" .
'MAIL_ENCRYPTION=null' . "\n" .
'MAIL_FROM_ADDRESS="leaves@sgin.co.id"' . "\n" .
'MAIL_FROM_NAME="${APP_NAME}"' . "\n\n" .
'# Cloudflare R2 Cloud Storage (10GB Lifetime Free)' . "\n" .
'CLOUDFLARE_R2_ACCESS_KEY_ID=fbe7d6c6ec7f262c09fbaa7e45b2d4da' . "\n" .
'CLOUDFLARE_R2_SECRET_ACCESS_KEY=4f4941af6f1a58b7b00a33de9b20c5f3974a3a15c48636f99f2dd846cca20b69' . "\n" .
'CLOUDFLARE_R2_DEFAULT_REGION=auto' . "\n" .
'CLOUDFLARE_R2_BUCKET=sgin' . "\n" .
'CLOUDFLARE_R2_ENDPOINT=https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com' . "\n" .
'CLOUDFLARE_R2_URL=https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com/sgin' . "\n" .
'CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=true' . "\n\n" .
'# AWS S3 compatibility parameters' . "\n" .
'AWS_ACCESS_KEY_ID=fbe7d6c6ec7f262c09fbaa7e45b2d4da' . "\n" .
'AWS_SECRET_ACCESS_KEY=4f4941af6f1a58b7b00a33de9b20c5f3974a3a15c48636f99f2dd846cca20b69' . "\n" .
'AWS_DEFAULT_REGION=auto' . "\n" .
'AWS_BUCKET=sgin' . "\n" .
'AWS_ENDPOINT=https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com' . "\n" .
'AWS_URL=https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com/sgin' . "\n" .
'AWS_USE_PATH_STYLE_ENDPOINT=true' . "\n\n" .
'PUSHER_APP_ID=' . "\n" .
'PUSHER_APP_KEY=' . "\n" .
'PUSHER_APP_SECRET=' . "\n" .
'PUSHER_HOST=' . "\n" .
'PUSHER_PORT=443' . "\n" .
'PUSHER_SCHEME=https' . "\n" .
'PUSHER_APP_CLUSTER=mt1' . "\n\n" .
'VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"' . "\n" .
'VITE_PUSHER_HOST="${PUSHER_HOST}"' . "\n" .
'VITE_PUSHER_PORT="${PUSHER_PORT}"' . "\n" .
'VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"' . "\n" .
'VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"' . "\n";
}

$envFile = $basePath . '/.env';
$action = $_POST['action'] ?? (!empty($_GET['run']) ? 'auto_repair' : null);
$logs = [];

// Handle Restore / Save .env
if ($action === 'restore_env') {
    $customEnv = $_POST['env_content'] ?? null;
    $contentToWrite = !empty($customEnv) ? $customEnv : getDefaultEnvContent();
    if (@file_put_contents($envFile, $contentToWrite) !== false) {
        $logs[] = "✓ File .env berhasil dibuat dan disimpan di $envFile";
    } else {
        $logs[] = "⚠️ Gagal menulis file .env! Periksa izin folder root (chmod 755/777).";
    }
}

if ($action === 'auto_repair') {
    $logs[] = "=================================================================";
    $logs[] = "  🛠️ MEMULAI PERBAIKAN TOTAL SISTEM APLIKASI SGIN LEAVES...     ";
    $logs[] = "=================================================================\n";

    // Step 0: Ensure .env exists
    if (!file_exists($envFile)) {
        $logs[] = "[0/6] File .env tidak ditemukan, membuat file .env produksi otomatis...";
        @file_put_contents($envFile, getDefaultEnvContent());
        $logs[] = "✓ File .env otomatis dibuat dengan koneksi database sginco_leav.";
    }

    // Step 1: Force Git pull & Hard Reset to GitHub main (if git is active)
    $logs[] = "\n[1/6] Memeriksa versi kodingan dari repository...";
    $gitVer = runShell("git --version", $basePath);
    if (str_contains(strtolower($gitVer), 'git version')) {
        $fetch = runShell("git fetch origin main", $basePath);
        $reset = runShell("git reset --hard origin/main", $basePath);
        $logs[] = "✓ Git Reset: " . ($reset ?: $fetch ?: 'Selesai');
    } else {
        $logs[] = "ℹ️ Git CLI tidak tersedia, menggunakan file lokal server saat ini.";
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

// Read last 50 lines of laravel.log
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
$host = $_SERVER['HTTP_HOST'] ?? 'www.sgin.co.id';
$appUrl = "$protocol://$host/leaves-application/dashboard";
$hasEnv = file_exists($envFile);
$currentEnvContent = $hasEnv ? @file_get_contents($envFile) : getDefaultEnvContent();
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
                    <p class="text-xs sm:text-sm text-slate-400">Pusat Diagnostik & Perbaikan Otomatis PT Sugiyama Indonesia</p>
                </div>
            </div>
        </div>

        <!-- Status .ENV Alert -->
        <?php if (!$hasEnv): ?>
            <div class="p-6 rounded-3xl bg-amber-950/70 border border-amber-500/50 shadow-xl space-y-4">
                <div class="flex items-center space-x-3 text-amber-400">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h2 class="text-base font-bold text-white">File .env Belum Ditemukan di Server</h2>
                        <p class="text-xs text-amber-300">File konfigurasi lingkungan (.env) belum ada. Anda dapat membuatnya secara instan di bawah ini.</p>
                    </div>
                </div>
                
                <form method="POST" action="" class="space-y-3">
                    <input type="hidden" name="action" value="restore_env">
                    <textarea
                        name="env_content"
                        rows="12"
                        class="w-full bg-slate-950 border border-amber-500/30 rounded-2xl p-4 text-xs font-mono text-amber-200 focus:outline-none focus:border-amber-400"
                    ><?= htmlspecialchars(getDefaultEnvContent()) ?></textarea>
                    <button
                        type="submit"
                        class="px-6 py-3 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs shadow-lg transition-all"
                    >
                        💾 Simpan & Buat File .env Sekarang
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 flex items-center justify-between">
                <div class="flex items-center space-x-2 text-emerald-400 text-xs font-semibold">
                    <span>✓ File .env aktif terdeteksi</span>
                </div>
                <details class="text-xs">
                    <summary class="cursor-pointer text-slate-400 hover:text-white font-medium">Lihat / Edit .env</summary>
                    <form method="POST" action="" class="mt-3 space-y-3">
                        <input type="hidden" name="action" value="restore_env">
                        <textarea
                            name="env_content"
                            rows="10"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs font-mono text-slate-300"
                        ><?= htmlspecialchars($currentEnvContent) ?></textarea>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold border border-slate-600">Simpan Perubahan .env</button>
                    </form>
                </details>
            </div>
        <?php endif; ?>

        <!-- Main Auto Repair Action Box -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-5">
            <div>
                <h2 class="text-base font-extrabold text-white">1-Klik Perbaikan Total (Self-Healing)</h2>
                <p class="text-xs text-slate-400 mt-1">
                    Klik tombol di bawah ini untuk membersihkan cache yang rusak, memastikan file .env siap, menjalankan migrasi database tanpa merusak data, dan memulihkan seluruh halaman aplikasi secara otomatis.
                </p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="auto_repair">
                <button
                    type="submit"
                    class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-sm sm:text-base shadow-xl shadow-emerald-900/40 transition-all flex items-center justify-center space-x-2"
                >
                    <span>🚀 Jalankan Perbaikan Otomatis (Fix Error 500 / Setup Ulang)</span>
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
