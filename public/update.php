<?php
/**
 * =========================================================================
 * 🚀 SGIN LEAVES - MASTER UPDATER (FRONTEND & BACKEND)
 * =========================================================================
 * File ini digunakan untuk melakukan pembaruan otomatis (1-Click Update)
 * untuk Frontend (React, Vite, CSS, Assets) dan Backend (PHP, Laravel, DB).
 * 
 * Akses Web: http(s)://domain-anda/update.php atau /leaves-application/update.php
 * Repository: Frhstaaa/leaves (branch: main)
 * =========================================================================
 */

@set_time_limit(600);
@ini_set('max_execution_time', '600');
@ini_set('memory_limit', '512M');
@ini_set('display_errors', '1');
error_reporting(E_ALL);

// Matikan error exception mysqli otomatis pada PHP 8.1+
if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

// 1. Deteksi Path Root Proyek Laravel
$currentDir = __DIR__;
if (file_exists($currentDir . '/artisan')) {
    $basePath = $currentDir;
} elseif (file_exists(dirname($currentDir) . '/artisan')) {
    $basePath = dirname($currentDir);
} else {
    $basePath = $currentDir;
}

$isPublic = (basename($currentDir) === 'public');
$envFile = $basePath . '/.env';
$envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

// 2. Helper Functions Eksekusi Perintah CLI / Shell
function executeCmd($cmd, $cwd) {
    $output = '';
    if (function_exists('proc_open')) {
        $descriptors = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];
        $process = @proc_open($cmd, $descriptors, $pipes, $cwd);
        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            $output = trim($stdout . ($stderr ? "\n" . $stderr : ''));
        }
    }
    
    if (empty($output) && function_exists('shell_exec')) {
        $output = @shell_exec("cd " . escapeshellarg($cwd) . " && " . $cmd . " 2>&1");
    }
    
    if (empty($output) && function_exists('exec')) {
        @exec("cd " . escapeshellarg($cwd) . " && " . $cmd . " 2>&1", $outLines);
        $output = implode("\n", $outLines ?? []);
    }

    return trim($output ?: 'Perintah dieksekusi (tidak ada output teks).');
}

function findBinary($name) {
    $paths = [
        "/usr/local/bin/$name",
        "/usr/bin/$name",
        "/bin/$name",
        getenv("HOME") . "/.nvm/versions/node/*/bin/$name",
        getenv("HOME") . "/bin/$name",
    ];
    foreach ($paths as $pattern) {
        $matches = glob($pattern);
        if ($matches) {
            foreach ($matches as $m) {
                if (is_executable($m)) return $m;
            }
        }
    }
    $which = executeCmd("which $name 2>/dev/null", __DIR__);
    if ($which && !str_contains($which, 'not found') && is_executable($which)) {
        return $which;
    }
    return $name;
}

// 3. Helper Cek Koneksi Database Langsung
function testDbConnection($envContent) {
    preg_match('/DB_HOST=(.*)/', $envContent, $mHost);
    preg_match('/DB_PORT=(.*)/', $envContent, $mPort);
    preg_match('/DB_DATABASE=(.*)/', $envContent, $mDb);
    preg_match('/DB_USERNAME=(.*)/', $envContent, $mUser);
    preg_match('/DB_PASSWORD=(.*)/', $envContent, $mPass);

    $host = trim($mHost[1] ?? '127.0.0.1');
    $port = (int)trim($mPort[1] ?? '3306');
    $db = trim($mDb[1] ?? '');
    $user = trim($mUser[1] ?? '');
    $pass = trim(trim($mPass[1] ?? '', '"'), "'");

    if (empty($db) || empty($user)) {
        return ['status' => false, 'message' => 'Konfigurasi DB di .env belum lengkap'];
    }

    try {
        $mysqli = @new mysqli($host, $user, $pass, $db, $port);
        if ($mysqli->connect_errno) {
            return ['status' => false, 'message' => "Gagal: {$mysqli->connect_error} (Host: $host, DB: $db)"];
        }
        $res = $mysqli->query("SELECT COUNT(*) as total_users FROM users");
        $userCount = ($res && $row = $res->fetch_assoc()) ? $row['total_users'] : 0;
        $mysqli->close();
        return ['status' => true, 'message' => "Terhubung! Database: $db ($userCount user terdaftar)"];
    } catch (\Throwable $e) {
        return ['status' => false, 'message' => 'Exception: ' . $e->getMessage()];
    }
}

// 4. Proses Eksekusi Aksi Form
$action = $_POST['action'] ?? '';
$actionLog = [];
$actionStatus = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    $phpBin = PHP_BINARY ?: findBinary('php');

    switch ($action) {
        // =========================================================================
        // AKSI 1: UPDATE TOTAL (FRONTEND & BACKEND)
        // =========================================================================
        case 'full_update':
            $actionLog[] = "🚀 [1/6] Memulai Pembaruan Menyeluruh (Frontend & Backend)...";

            // A. Git Pull dari GitHub
            $actionLog[] = "📥 [2/6] Menarik pembaruan kode dari GitHub (git pull origin main)...";
            $gitOutput = executeCmd("git pull origin main 2>&1", $basePath);
            $actionLog[] = ">>> " . $gitOutput;

            // Jika Git pull gagal atau bukan repo git, coba download arsip zip GitHub
            if (str_contains($gitOutput, 'fatal') || str_contains($gitOutput, 'not a git repository')) {
                $actionLog[] = "⚠️ Git pull tidak tersedia, mencoba auto-sync via GitHub Zip Archive...";
                $zipUrl = "https://github.com/Frhstaaa/leaves/archive/refs/heads/main.zip";
                $zipPath = $basePath . '/latest_release.zip';
                $fp = @fopen($zipPath, 'w+');
                $ch = @curl_init($zipUrl);
                if ($ch && $fp) {
                    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Leaves-AutoUpdater');
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    if (class_exists('ZipArchive') && file_exists($zipPath) && filesize($zipPath) > 1000) {
                        $zip = new ZipArchive();
                        if ($zip->open($zipPath) === TRUE) {
                            $zip->extractTo($basePath . '/temp_update');
                            $zip->close();
                            // Copy files from extracted dir
                            $extractedFolder = glob($basePath . '/temp_update/leaves-*')[0] ?? '';
                            if ($extractedFolder && is_dir($extractedFolder)) {
                                executeCmd("cp -rf {$extractedFolder}/* " . escapeshellarg($basePath) . "/", $basePath);
                                $actionLog[] = "✅ Sukses menerapkan file update dari GitHub Zip Archive.";
                            }
                            @executeCmd("rm -rf " . escapeshellarg($basePath . '/temp_update') . " " . escapeshellarg($zipPath), $basePath);
                        }
                    }
                }
            }

            // B. Migrasi Database Backend
            $actionLog[] = "🗄️ [3/6] Menjalankan Database Migration...";
            $migrateOutput = executeCmd("$phpBin artisan migrate --force 2>&1", $basePath);
            $actionLog[] = ">>> " . $migrateOutput;

            // C. Sinkronisasi Asset Frontend & Manifest
            $actionLog[] = "🎨 [4/6] Memeriksa Asset Frontend (React/Inertia/CSS)...";
            $buildDir = $basePath . '/public/build';
            if (file_exists($buildDir . '/manifest.json')) {
                $actionLog[] = "✅ Asset Frontend terdeteksi di public/build (Manifest valid).";
            } else {
                $actionLog[] = "⚠️ File public/build/manifest.json belum terdeteksi. Mencoba compile via npm jika ada...";
                $npmBin = findBinary('npm');
                if ($npmBin) {
                    $buildOutput = executeCmd("$npmBin run build 2>&1", $basePath);
                    $actionLog[] = ">>> " . $buildOutput;
                }
            }

            // D. Membersihkan & Mengoptimalkan Seluruh Cache
            $actionLog[] = "🧹 [5/6] Membersihkan dan menyusun ulang cache Laravel...";
            executeCmd("$phpBin artisan optimize:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan config:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan route:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan view:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan cache:clear 2>&1", $basePath);

            // Rebuild Caches
            $cacheOutput = executeCmd("$phpBin artisan config:cache 2>&1", $basePath);
            $actionLog[] = ">>> Config Cached: " . $cacheOutput;
            $routeCacheOutput = executeCmd("$phpBin artisan route:cache 2>&1", $basePath);
            $actionLog[] = ">>> Route Cached: " . $routeCacheOutput;
            $viewCacheOutput = executeCmd("$phpBin artisan view:cache 2>&1", $basePath);
            $actionLog[] = ">>> View Cached: " . $viewCacheOutput;

            // E. Reset OPcache & Izin Folder
            $actionLog[] = "🔒 [6/6] Memperbaiki izin folder storage & cache...";
            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $actionLog[] = "✅ PHP OPcache berhasil di-reset.";
            }

            $storageDirs = [
                $basePath . '/storage',
                $basePath . '/storage/app',
                $basePath . '/storage/app/public',
                $basePath . '/storage/framework',
                $basePath . '/storage/framework/cache',
                $basePath . '/storage/framework/cache/data',
                $basePath . '/storage/framework/sessions',
                $basePath . '/storage/framework/views',
                $basePath . '/storage/logs',
                $basePath . '/bootstrap/cache',
            ];
            foreach ($storageDirs as $dir) {
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                @chmod($dir, 0777);
            }
            executeCmd("$phpBin artisan storage:link 2>&1", $basePath);

            $actionStatus = 'success';
            $actionLog[] = "🎉 Pembaruan Total (Frontend & Backend) BERHASIL DISELESAIKAN!";
            break;

        // =========================================================================
        // AKSI 2: UPDATE FRONTEND SAJA
        // =========================================================================
        case 'frontend_only':
            $actionLog[] = "🎨 [1/3] Memulai sinkronisasi Asset Frontend...";
            $gitOutput = executeCmd("git pull origin main 2>&1", $basePath);
            $actionLog[] = ">>> " . $gitOutput;

            $actionLog[] = "🧹 [2/3] Membersihkan cache compiled views & cache Inertia...";
            executeCmd("$phpBin artisan view:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan view:cache 2>&1", $basePath);

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }

            $actionLog[] = "✅ [3/3] Frontend React, CSS, dan Blade view berhasil diperbarui.";
            $actionStatus = 'success';
            break;

        // =========================================================================
        // AKSI 3: UPDATE BACKEND SAJA
        // =========================================================================
        case 'backend_only':
            $actionLog[] = "⚙️ [1/4] Menarik pembaruan PHP backend dari GitHub...";
            $gitOutput = executeCmd("git pull origin main 2>&1", $basePath);
            $actionLog[] = ">>> " . $gitOutput;

            $actionLog[] = "🗄️ [2/4] Menjalankan Database Migration...";
            $migrateOutput = executeCmd("$phpBin artisan migrate --force 2>&1", $basePath);
            $actionLog[] = ">>> " . $migrateOutput;

            $actionLog[] = "🧹 [3/4] Membersihkan dan me-refresh cache routing & konfigurasi...";
            executeCmd("$phpBin artisan optimize:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan config:cache 2>&1", $basePath);
            executeCmd("$phpBin artisan route:cache 2>&1", $basePath);

            $actionLog[] = "✅ [4/4] Backend & Database berhasil diperbarui.";
            $actionStatus = 'success';
            break;

        // =========================================================================
        // AKSI 4: BERSIHKAN SEMUA CACHE
        // =========================================================================
        case 'clear_cache':
            $actionLog[] = "🧹 Membersihkan seluruh cache sistem...";
            executeCmd("$phpBin artisan optimize:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan config:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan route:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan view:clear 2>&1", $basePath);
            executeCmd("$phpBin artisan cache:clear 2>&1", $basePath);

            // Bersihkan file manual di bootstrap/cache
            $bootstrapFiles = glob($basePath . '/bootstrap/cache/*.php');
            foreach ($bootstrapFiles as $f) {
                @unlink($f);
            }

            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $actionLog[] = "✅ PHP OPcache di-reset.";
            }

            $actionLog[] = "✅ Seluruh cache aplikasi, route, view, dan konfigurasi bersih.";
            $actionStatus = 'success';
            break;

        // =========================================================================
        // AKSI 5: PERBAIKI IZIN FOLDER & STORAGE LINK
        // =========================================================================
        case 'fix_permissions':
            $actionLog[] = "🔒 Memperbaiki folder storage, bootstrap/cache, dan symlink...";
            $storageDirs = [
                $basePath . '/storage',
                $basePath . '/storage/app',
                $basePath . '/storage/app/public',
                $basePath . '/storage/framework',
                $basePath . '/storage/framework/cache',
                $basePath . '/storage/framework/cache/data',
                $basePath . '/storage/framework/sessions',
                $basePath . '/storage/framework/views',
                $basePath . '/storage/logs',
                $basePath . '/bootstrap/cache',
            ];
            foreach ($storageDirs as $dir) {
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                @chmod($dir, 0777);
            }

            $linkOutput = executeCmd("$phpBin artisan storage:link 2>&1", $basePath);
            $actionLog[] = ">>> " . $linkOutput;
            $actionLog[] = "✅ Izin folder dipulihkan ke 0777 dan storage:link aktif.";
            $actionStatus = 'success';
            break;

        // =========================================================================
        // AKSI 6: EKSEKUSI CUSTOM ARTISAN COMMAND
        // =========================================================================
        case 'custom_artisan':
            $customCmd = trim($_POST['artisan_command'] ?? '');
            if (!empty($customCmd)) {
                // Filter keamanan sederhana
                $cleanCmd = preg_replace('/[^a-zA-Z0-9:\-_ ]/', '', $customCmd);
                $actionLog[] = "💻 Menjalankan: php artisan $cleanCmd ...";
                $artisanOutput = executeCmd("$phpBin artisan $cleanCmd 2>&1", $basePath);
                $actionLog[] = ">>>\n" . $artisanOutput;
                $actionStatus = 'success';
            } else {
                $actionLog[] = "⚠️ Perintah artisan tidak boleh kosong.";
                $actionStatus = 'error';
            }
            break;

        default:
            $actionLog[] = "⚠️ Aksi tidak dikenali: $action";
            $actionStatus = 'error';
            break;
    }
}

// 5. Data Diagnostik untuk Tampilan UI
$dbCheck = testDbConnection($envContent);
$gitCommit = executeCmd("git log -1 --format=\"%h - %s (%ci)\" 2>&1", $basePath);
if (str_contains($gitCommit, 'fatal') || empty($gitCommit)) {
    $gitCommit = 'Git CLI tidak terdeteksi / rilis manual';
}

$gitBranch = executeCmd("git rev-parse --abbrev-ref HEAD 2>&1", $basePath);
if (str_contains($gitBranch, 'fatal') || empty($gitBranch)) {
    $gitBranch = 'main (default)';
}

// Ambil log error terbaru
$logFile = $basePath . '/storage/logs/laravel.log';
$recentLogs = 'Belum ada catatan error di laravel.log.';
if (file_exists($logFile) && is_readable($logFile)) {
    $lines = file($logFile);
    if ($lines) {
        $recentLogs = implode("", array_slice($lines, -35));
    }
}

$hasBuildManifest = file_exists($basePath . '/public/build/manifest.json');
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Leaves - Master Updater (Frontend & Backend)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        code, pre { font-family: 'JetBrains Mono', monospace; }
        .glass-card {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen antialiased selection:bg-emerald-500 selection:text-slate-950">

    <!-- Background Decorative Glow -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-600/15 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 bg-teal-600/15 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-5xl mx-auto px-4 py-8 space-y-6">

        <!-- Header Card -->
        <header class="glass-card rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-800">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-xl shadow-lg shadow-emerald-500/10">
                            🚀
                        </div>
                        <div>
                            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">SGIN Leaves Updater</h1>
                            <p class="text-xs text-slate-400 font-medium">Universal One-Click Update Tool for Frontend & Backend</p>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="./" target="_blank" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition flex items-center gap-1.5 shadow-sm">
                        <span>🌐 Buka Aplikasi</span>
                    </a>
                    <a href="?refresh=1" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition flex items-center gap-1.5 shadow-sm">
                        <span>🔄 Refresh</span>
                    </a>
                </div>
            </div>

            <!-- Status Badges Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-800/80 text-xs">
                <div class="bg-slate-900/60 p-3 rounded-2xl border border-slate-800">
                    <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider block">Database Status</span>
                    <span class="font-bold <?= $dbCheck['status'] ? 'text-emerald-400' : 'text-rose-400' ?> flex items-center gap-1 mt-1 truncate" title="<?= htmlspecialchars($dbCheck['message']) ?>">
                        <span class="w-2 h-2 rounded-full <?= $dbCheck['status'] ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400' ?>"></span>
                        <?= $dbCheck['status'] ? 'Connected' : 'Error DB' ?>
                    </span>
                </div>

                <div class="bg-slate-900/60 p-3 rounded-2xl border border-slate-800">
                    <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider block">Frontend Build</span>
                    <span class="font-bold <?= $hasBuildManifest ? 'text-teal-400' : 'text-amber-400' ?> flex items-center gap-1 mt-1">
                        <span class="w-2 h-2 rounded-full <?= $hasBuildManifest ? 'bg-teal-400' : 'bg-amber-400 animate-ping' ?>"></span>
                        <?= $hasBuildManifest ? 'Manifest Ready' : 'Need Build' ?>
                    </span>
                </div>

                <div class="bg-slate-900/60 p-3 rounded-2xl border border-slate-800">
                    <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider block">Branch</span>
                    <span class="font-bold text-slate-200 mt-1 truncate block font-mono">
                        🌿 <?= htmlspecialchars($gitBranch) ?>
                    </span>
                </div>

                <div class="bg-slate-900/60 p-3 rounded-2xl border border-slate-800">
                    <span class="text-slate-500 text-[10px] font-bold uppercase tracking-wider block">PHP Version</span>
                    <span class="font-bold text-indigo-400 mt-1 block font-mono">
                        🐘 PHP <?= PHP_VERSION ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Notification Execution Output (Jika ada action POST) -->
        <?php if (!empty($actionLog)): ?>
            <div class="glass-card rounded-3xl p-6 border <?= $actionStatus === 'success' ? 'border-emerald-500/40 bg-emerald-950/20' : 'border-rose-500/40 bg-rose-950/20' ?> shadow-2xl space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold <?= $actionStatus === 'success' ? 'text-emerald-300' : 'text-rose-300' ?> flex items-center gap-2">
                        <span><?= $actionStatus === 'success' ? '✅' : '⚠️' ?></span>
                        Hasil Eksekusi Pembaruan
                    </h3>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-900 text-slate-400">
                        <?= date('H:i:s') ?> WIB
                    </span>
                </div>
                <div class="p-4 rounded-2xl bg-black/90 border border-slate-800 text-xs font-mono text-emerald-400 overflow-x-auto whitespace-pre-wrap max-h-80 leading-relaxed shadow-inner">
                    <?= htmlspecialchars(implode("\n", $actionLog)) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Primary Hero Action: Update Total -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-900/80 via-teal-900/60 to-slate-900 border border-emerald-500/40 p-6 sm:p-8 shadow-2xl">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6 relative z-10">
                <div class="space-y-2 max-w-xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/20">
                        ⭐ Rekomendasi Utama
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">1-Klik Pembaruan Total (Frontend & Backend)</h2>
                    <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                        Menarik kodingan terbaru dari GitHub, menyinkronkan asset Frontend (React/Vite), mengeksekusi migrasi database, membersihkan & menyusun seluruh cache Laravel, serta memulihkan izin storage.
                    </p>
                </div>

                <form method="POST" class="shrink-0 w-full sm:w-auto">
                    <input type="hidden" name="action" value="full_update">
                    <button type="submit" onclick="return confirm('Jalankan pembaruan total Frontend & Backend sekarang?')" class="w-full sm:w-auto py-4 px-8 rounded-2xl bg-gradient-to-r from-emerald-400 to-teal-400 hover:from-emerald-300 hover:to-teal-300 text-slate-950 font-black text-sm sm:text-base shadow-xl shadow-emerald-500/25 transition transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                        <span>🚀 Jalankan Pembaruan Total</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Modular Quick Actions Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- Card 1: Frontend Only -->
            <div class="glass-card rounded-3xl p-5 shadow-xl flex flex-col justify-between hover:border-teal-500/40 transition space-y-4">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-2xl bg-teal-500/10 text-teal-400 border border-teal-500/20 flex items-center justify-center font-bold text-lg">
                        🎨
                    </div>
                    <h3 class="text-sm font-bold text-white">Update Frontend Saja</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Tarik file React, CSS, Blade views, dan asset bundle tanpa mengubah database atau migrasi.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="frontend_only">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-teal-300 border border-teal-500/30 text-xs font-bold transition">
                        Sync Frontend Aja
                    </button>
                </form>
            </div>

            <!-- Card 2: Backend Only -->
            <div class="glass-card rounded-3xl p-5 shadow-xl flex flex-col justify-between hover:border-indigo-500/40 transition space-y-4">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 flex items-center justify-center font-bold text-lg">
                        ⚙️
                    </div>
                    <h3 class="text-sm font-bold text-white">Update Backend Saja</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Tarik controller, model, route, jalankan database migration, dan susun cache config backend.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="backend_only">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 border border-indigo-500/30 text-xs font-bold transition">
                        Sync Backend & DB
                    </button>
                </form>
            </div>

            <!-- Card 3: Clear All Cache -->
            <div class="glass-card rounded-3xl p-5 shadow-xl flex flex-col justify-between hover:border-amber-500/40 transition space-y-4">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 flex items-center justify-center font-bold text-lg">
                        🧹
                    </div>
                    <h3 class="text-sm font-bold text-white">Bersihkan Semua Cache</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Hapus seluruh cache Laravel (config, routes, views, sessions) dan lakukan PHP OPcache reset.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 border border-amber-500/30 text-xs font-bold transition">
                        Purge Seluruh Cache
                    </button>
                </form>
            </div>

            <!-- Card 4: Fix Permissions -->
            <div class="glass-card rounded-3xl p-5 shadow-xl flex flex-col justify-between hover:border-emerald-500/40 transition space-y-4">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center font-bold text-lg">
                        🔒
                    </div>
                    <h3 class="text-sm font-bold text-white">Perbaiki Izin Storage</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Terapkan izin 0777 pada folder storage, framework cache, logs, dan buat ulang symlink publik.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="fix_permissions">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-emerald-500/30 text-xs font-bold transition">
                        Perbaiki Permissions
                    </button>
                </form>
            </div>

        </div>

        <!-- Custom Artisan Command Runner & Error Logs -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <!-- Artisan Runner -->
            <div class="glass-card rounded-3xl p-6 shadow-xl space-y-4 lg:col-span-1">
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>💻</span> Artisan CLI Runner
                    </h3>
                    <p class="text-xs text-slate-400">Jalankan perintah Artisan langsung</p>
                </div>

                <form method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="custom_artisan">
                    <div>
                        <label class="text-[10px] font-mono text-slate-400 block mb-1">Perintah (tanpa kata 'php artisan'):</label>
                        <input type="text" name="artisan_command" placeholder="route:list, migrate, dsb" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-slate-200 focus:outline-none focus:border-emerald-500">
                    </div>
                    <button type="submit" class="w-full py-2 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold border border-slate-700 transition">
                        Jalankan Artisan
                    </button>
                </form>

                <div class="pt-3 border-t border-slate-800 text-[11px] text-slate-500 font-mono">
                    Commit Terakhir:<br>
                    <span class="text-slate-400"><?= htmlspecialchars($gitCommit) ?></span>
                </div>
            </div>

            <!-- Server Log Viewer -->
            <div class="glass-card rounded-3xl p-6 shadow-xl space-y-4 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>📜</span> Log Error Server (storage/logs/laravel.log)
                        </h3>
                        <p class="text-xs text-slate-400">35 baris log error terbaru</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-black/80 border border-slate-800 text-[11px] font-mono text-slate-400 overflow-x-auto whitespace-pre-wrap max-h-56 leading-relaxed">
                    <?= htmlspecialchars($recentLogs) ?>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <footer class="text-center text-xs text-slate-600 py-4">
            PT Sugiyama Indonesia (SGIN) &bull; Leaves Master Auto-Updater &bull; <?= date('Y') ?>
        </footer>

    </div>

</body>
</html>
