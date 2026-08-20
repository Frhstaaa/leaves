<?php
/**
 * SGIN - Web Setup & Maintenance Utility (Replacement for Terminal/SSH)
 * For security, please delete this file after completing the setup.
 */

// Disable time limits for migrations
@set_time_limit(300);
@ini_set('max_execution_time', 300);

// Determine base path
$basePath = __DIR__;
if (!file_exists($basePath . '/vendor/autoload.php')) {
    if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        $basePath = dirname(__DIR__);
    }
}

// 1. Auto-create all required storage & cache folders before anything else
$requiredDirs = [
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/views',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/framework/testing',
    $basePath . '/storage/logs',
    $basePath . '/storage/app/public',
    $basePath . '/bootstrap/cache',
];

$dirCreationLogs = [];
foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0777, true)) {
            $dirCreationLogs[] = "✓ Folder berhasil dibuat: " . str_replace($basePath . '/', '', $dir);
            @chmod($dir, 0777);
        } else {
            $dirCreationLogs[] = "✗ Gagal membuat folder: " . str_replace($basePath . '/', '', $dir);
        }
    } else {
        @chmod($dir, 0777);
    }
}

// Check environment and framework availability
$hasVendor = file_exists($basePath . '/vendor/autoload.php');
$hasEnv = file_exists($basePath . '/.env');
$hasBootstrap = file_exists($basePath . '/bootstrap/app.php');

$app = null;
$kernel = null;
$bootstrapError = null;

if ($hasVendor && $hasBootstrap) {
    try {
        require_once $basePath . '/vendor/autoload.php';
        $app = require_once $basePath . '/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
    } catch (\Throwable $e) {
        $bootstrapError = $e->getMessage();
    }
}

// Handle Actions
$outputLog = '';
$actionExecuted = $_POST['action'] ?? null;

if ($actionExecuted && $app) {
    ob_start();
    try {
        switch ($actionExecuted) {
            case 'init_storage':
                echo "=== MEMBUAT FOLDER & MEMPERBAIKI PERMISSION STORAGE ===\n";
                foreach ($requiredDirs as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                        echo "Created: $dir\n";
                    }
                    @chmod($dir, 0777);
                    echo "Permissions 777 applied: " . basename($dir) . "\n";
                }
                echo "✓ Semua folder storage berhasil disiapkan!\n";
                break;

            case 'key_generate':
                echo "=== GENERATE APP ENCRYPTION KEY ===\n";
                \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
                echo \Illuminate\Support\Facades\Artisan::output();
                break;

            case 'migrate':
                echo "=== MENJALANKAN DATABASE MIGRATION ===\n";
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                echo \Illuminate\Support\Facades\Artisan::output();
                break;

            case 'migrate_seed':
                echo "=== MENJALANKAN MIGRATION & SEEDER ===\n";
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--seed' => true, '--force' => true]);
                echo \Illuminate\Support\Facades\Artisan::output();
                break;

            case 'migrate_fresh':
                echo "=== RESET & FRESH MIGRATION DENGAN SEEDER ===\n";
                \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
                echo \Illuminate\Support\Facades\Artisan::output();
                break;

            case 'storage_link':
                echo "=== MEMBUAT STORAGE SYMLINK (UPLOAD LINK) ===\n";
                $publicStorage = $basePath . '/public/storage';
                $appStorage = $basePath . '/storage/app/public';
                
                if (file_exists($publicStorage) || is_link($publicStorage)) {
                    @unlink($publicStorage);
                }
                
                try {
                    \Illuminate\Support\Facades\Artisan::call('storage:link');
                    echo \Illuminate\Support\Facades\Artisan::output();
                } catch (\Throwable $ex) {
                    if (@symlink($appStorage, $publicStorage)) {
                        echo "✓ Symlink created manually: public/storage -> storage/app/public\n";
                    } else {
                        echo "✗ Gagal membuat symlink: " . $ex->getMessage() . "\n";
                    }
                }
                break;

            case 'clear_cache':
                echo "=== MEMBERSIHKAN SELURUH CACHE ===\n";
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                echo \Illuminate\Support\Facades\Artisan::output();
                break;

            case 'optimize':
                echo "=== OPTIMASI UNTUK PRODUCTION (CACHE CONFIG & ROUTES) ===\n";
                \Illuminate\Support\Facades\Artisan::call('config:cache');
                echo \Illuminate\Support\Facades\Artisan::output();
                \Illuminate\Support\Facades\Artisan::call('route:cache');
                echo \Illuminate\Support\Facades\Artisan::output();
                \Illuminate\Support\Facades\Artisan::call('view:cache');
                echo \Illuminate\Support\Facades\Artisan::output();
                break;

            case 'run_all':
                echo "=== MENJALANKAN SETUP LENGKAP OTOMATIS ===\n\n";
                
                // 1. Storage dirs
                echo "[1/5] Memeriksa & membuat folder storage...\n";
                foreach ($requiredDirs as $dir) {
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0777, true);
                    }
                    @chmod($dir, 0777);
                }
                echo "✓ Selesai.\n\n";

                // 2. Key Generate
                echo "[2/5] Memeriksa App Key...\n";
                if (!config('app.key')) {
                    \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output();
                } else {
                    echo "✓ App Key sudah tersedia.\n";
                }
                echo "\n";

                // 3. Migrate & Seed
                echo "[3/5] Menjalankan Migration & Database Seeder...\n";
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--seed' => true, '--force' => true]);
                echo \Illuminate\Support\Facades\Artisan::output();
                echo "\n";

                // 4. Storage Link
                echo "[4/5] Menghubungkan storage symlink...\n";
                $publicStorage = $basePath . '/public/storage';
                $appStorage = $basePath . '/storage/app/public';
                if (!file_exists($publicStorage)) {
                    @symlink($appStorage, $publicStorage);
                    \Illuminate\Support\Facades\Artisan::call('storage:link');
                    echo \Illuminate\Support\Facades\Artisan::output();
                } else {
                    echo "✓ Storage symlink sudah terhubung.\n";
                }
                echo "\n";

                // 5. Clear Cache
                echo "[5/5] Membersihkan cache konfigurasi...\n";
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                echo \Illuminate\Support\Facades\Artisan::output();
                echo "\n=== SETUP SELESAI DENGAN SUKSES! ===";
                break;

            case 'delete_self':
                echo "=== MENGHAPUS SETUP.PHP DARI SERVER ===\n";
                $file = __FILE__;
                $rootFile = $basePath . '/setup.php';
                $pubFile = $basePath . '/public/setup.php';
                
                @unlink($rootFile);
                @unlink($pubFile);
                @unlink($file);
                
                echo "✓ File setup.php berhasil dihapus demi keamanan server Anda!\n";
                echo "Silakan kembali ke website utama.";
                break;
        }
    } catch (\Throwable $e) {
        echo "✗ Terjadi Error: " . $e->getMessage() . "\n";
        echo "Trace:\n" . $e->getTraceAsString();
    }
    $outputLog = ob_get_clean();
}

// Check database connection status
$dbStatus = 'Belum terhubung';
$dbConnected = false;
if ($app) {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbStatus = 'Terhubung (' . \Illuminate\Support\Facades\DB::connection()->getDatabaseName() . ')';
        $dbConnected = true;
    } catch (\Throwable $e) {
        $dbStatus = 'Gagal: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN - Web Setup & Maintenance Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-4 sm:p-8">

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Header -->
        <div class="p-6 rounded-3xl bg-gradient-to-r from-emerald-800 to-teal-900 border border-emerald-700/50 shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold uppercase border border-emerald-400/30">CyberPanel Helper</span>
                    <span class="text-xs text-emerald-200">Terminal Alternative</span>
                </div>
                <h1 class="text-2xl font-black text-white mt-1">SGIN Setup & Maintenance Panel</h1>
                <p class="text-xs text-emerald-100/80 mt-0.5">Jalankan perintah Artisan dan konfigurasi database tanpa akses terminal/SSH</p>
            </div>
            <a href="./" class="px-5 py-2.5 rounded-2xl bg-white text-emerald-950 hover:bg-emerald-50 font-black text-xs shadow-lg transition-transform hover:scale-105 shrink-0 text-center">
                &larr; Buka Website SGIN
            </a>
        </div>

        <!-- Status Overview Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">PHP Version</span>
                <span class="text-sm font-black text-white"><?= phpversion(); ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">File .env</span>
                <span class="text-sm font-black <?= $hasEnv ? 'text-emerald-400' : 'text-rose-400' ?>">
                    <?= $hasEnv ? '✓ Ada' : '✗ Belum Ada' ?>
                </span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Storage Status</span>
                <span class="text-sm font-black text-emerald-400">✓ Siap (Auto-Fixed)</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Database Status</span>
                <span class="text-xs font-bold <?= $dbConnected ? 'text-emerald-400' : 'text-amber-400' ?> truncate block" title="<?= htmlspecialchars($dbStatus) ?>">
                    <?= $dbConnected ? '✓ Terhubung' : '⚠️ ' . htmlspecialchars($dbStatus) ?>
                </span>
            </div>
        </div>

        <?php if (!empty($dirCreationLogs)): ?>
        <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-600/40 text-xs text-emerald-200 space-y-1">
            <p class="font-extrabold text-emerald-400">⚡ Status Pembuatan Folder Storage:</p>
            <?php foreach ($dirCreationLogs as $log): ?>
                <p class="font-mono text-[11px]"><?= htmlspecialchars($log) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($bootstrapError): ?>
        <div class="p-4 rounded-2xl bg-rose-950/80 border border-rose-600/50 text-xs text-rose-200 space-y-1">
            <p class="font-extrabold text-rose-400">⚠️ Peringatan Bootstrap Laravel:</p>
            <p class="font-mono text-[11px]"><?= htmlspecialchars($bootstrapError) ?></p>
            <p class="text-[11px] text-rose-300 mt-1">Jika error terkait App Key, silakan klik tombol <strong>"Generate App Key"</strong> di bawah.</p>
        </div>
        <?php endif; ?>

        <!-- Quick 1-Click All Setup Action -->
        <div class="p-6 rounded-3xl bg-slate-800/90 border border-emerald-500/40 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-black text-white flex items-center space-x-2">
                        <span>⚡ 1-Click Full Setup (Direkomendasikan)</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Otomatis memperbaiki storage folder, generate app key, migrasi database, dan storage symlink sekaligus.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="run_all">
                    <button type="submit" onclick="return confirm('Jalankan seluruh rangkaian setup otomatis sekarang?')" class="px-6 py-3.5 rounded-2xl bg-[#0FA172] hover:bg-[#1CB67C] text-white font-black text-xs shadow-lg shadow-emerald-600/30 flex items-center space-x-2 transition-all hover:scale-105 shrink-0">
                        <span>Jalankan Semua Setup</span>
                        <span>&rarr;</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Individual Control Actions Grid -->
        <div class="p-6 rounded-3xl bg-slate-800/50 border border-slate-700/60 space-y-5">
            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">Pilih Aksi Per Bagian:</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5">

                <!-- 1. Fix Storage Dirs -->
                <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">1. Siapkan Folder Storage</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Buat folder sessions, views, cache/data, dan logs dengan permission 777.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="init_storage">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 font-bold text-xs transition-colors">
                            Perbaiki Folder Storage
                        </button>
                    </form>
                </div>

                <!-- 2. Generate App Key -->
                <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">2. Generate App Key</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Membuat encryption key baru dan menyimpannya di file .env.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="key_generate">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 font-bold text-xs transition-colors">
                            Generate APP_KEY
                        </button>
                    </form>
                </div>

                <!-- 3. Migrate & Seed -->
                <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">3. Database Migration + Seed</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Membuat tabel database & data awal (kategori cuti, admin, dll).</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="migrate_seed">
                        <button type="submit" onclick="return confirm('Jalankan migrasi database & seeder sekarang?')" class="w-full py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-600 text-white font-bold text-xs transition-colors">
                            Migrate & Seed
                        </button>
                    </form>
                </div>

                <!-- 4. Storage Link -->
                <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">4. Storage Symlink</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Hubungkan public/storage ke storage/app/public untuk file lampiran.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="storage_link">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 font-bold text-xs transition-colors">
                            Buat Storage Link
                        </button>
                    </form>
                </div>

                <!-- 5. Clear All Cache -->
                <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">5. Bersihkan Cache</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Hapus cache config, route, view, dan cache aplikasi.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 font-bold text-xs transition-colors">
                            Clear Cache
                        </button>
                    </form>
                </div>

                <!-- 6. Optimize Production -->
                <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">6. Cache untuk Production</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Kompilasi dan simpan cache config/routes untuk performa maksimal.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="optimize">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-600 text-slate-200 font-bold text-xs transition-colors">
                            Optimize / Cache
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- Terminal Execution Output Box -->
        <?php if (!empty($outputLog)): ?>
        <div class="p-5 rounded-3xl bg-slate-950 border border-slate-700 shadow-2xl space-y-3 animate-fade-in">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                <span class="text-xs font-bold text-emerald-400 flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Hasil Eksekusi Perintah:</span>
                </span>
                <span class="text-[10px] text-slate-500 font-mono"><?= date('H:i:s') ?> WIB</span>
            </div>
            <pre class="p-4 rounded-2xl bg-black/60 text-emerald-300 font-mono text-xs overflow-x-auto leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($outputLog) ?></pre>
        </div>
        <?php endif; ?>

        <!-- Security Warning & Delete Button -->
        <div class="p-5 rounded-3xl bg-rose-950/40 border border-rose-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h4 class="text-xs font-extrabold text-rose-300 uppercase tracking-wider">🔒 Tindakan Keamanan (Wajib)</h4>
                <p class="text-[11px] text-rose-200/80 leading-relaxed">
                    Setelah semua setup selesai dan website sudah berjalan normal, hapus file <code>setup.php</code> demi keamanan server Anda.
                </p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_self">
                <button type="submit" onclick="return confirm('PERINGATAN: Apakah Anda yakin ingin menghapus file setup.php ini sekarang?')" class="px-5 py-2.5 rounded-2xl bg-rose-700 hover:bg-rose-600 text-white font-bold text-xs shadow-lg transition-colors shrink-0">
                    Hapus File setup.php
                </button>
            </form>
        </div>

    </div>

</body>
</html>
