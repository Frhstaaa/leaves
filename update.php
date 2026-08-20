<?php
/**
 * SGIN - Web Application Update Center (GitHub Connected)
 * Menghubungkan aplikasi langsung ke GitHub: Auto-Pull, Webhook, dan One-Click Update.
 */

// Disable time limits for migrations & git operations
@set_time_limit(300);
@ini_set('max_execution_time', 300);

// Default GitHub Configuration
define('DEFAULT_GITHUB_REPO', 'Frhstaaa/sgin');
define('DEFAULT_GITHUB_BRANCH', 'main');
define('DEFAULT_WEBHOOK_SECRET', 'sgin-secret-webhook-key');

// Determine base path
$basePath = __DIR__;
if (!file_exists($basePath . '/vendor/autoload.php')) {
    if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        $basePath = dirname(__DIR__);
    }
}

// Ensure required storage folders exist
$requiredDirs = [
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/views',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/framework/testing',
    $basePath . '/storage/logs',
    $basePath . '/storage/app/public',
    $basePath . '/bootstrap/cache',
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
}

// Bootstrap Laravel
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

// Helper Function: Pull from GitHub via Git CLI or Zip Download
function syncFromGithub($basePath, $repo, $branch, $token = '') {
    $logs = [];
    $logs[] = "Menghubungkan ke GitHub: https://github.com/$repo (Branch: $branch)";

    // Try Method 1: Git CLI (if git command is available)
    $gitAvailable = false;
    if (function_exists('shell_exec')) {
        $gitVersion = @shell_exec('git --version 2>&1');
        if ($gitVersion && str_contains(strtolower($gitVersion), 'git version')) {
            $gitAvailable = true;
        }
    }

    if ($gitAvailable) {
        $logs[] = "Menggunakan Git CLI di server...";
        
        // Check if .git exists in project root
        if (!is_dir($basePath . '/.git')) {
            $logs[] = "Inisialisasi git repository lokal...";
            @shell_exec("cd " . escapeshellarg($basePath) . " && git init 2>&1");
            $remoteUrl = $token 
                ? "https://$token@github.com/$repo.git"
                : "https://github.com/$repo.git";
            @shell_exec("cd " . escapeshellarg($basePath) . " && git remote add origin " . escapeshellarg($remoteUrl) . " 2>&1");
        } else {
            // Update remote URL with token if provided
            if ($token) {
                $remoteUrl = "https://$token@github.com/$repo.git";
                @shell_exec("cd " . escapeshellarg($basePath) . " && git remote set-url origin " . escapeshellarg($remoteUrl) . " 2>&1");
            }
        }

        // Fetch and pull/reset
        $cmd = "cd " . escapeshellarg($basePath) . " && git fetch origin $branch 2>&1 && git reset --hard origin/$branch 2>&1";
        $gitOutput = @shell_exec($cmd);
        $logs[] = "Git Output:\n" . ($gitOutput ?: "Tidak ada output.");
        
        if ($gitOutput && (str_contains($gitOutput, 'HEAD is now at') || str_contains($gitOutput, 'Updating') || str_contains($gitOutput, 'Already up to date'))) {
            $logs[] = "✓ Kodingan berhasil diperbarui via Git CLI!";
            return [true, implode("\n", $logs)];
        }
    }

    // Method 2: Fallback to GitHub ZIP Archive Download
    $logs[] = "Metode Git CLI tidak dapat digunakan, beralih ke unduhan ZIP dari GitHub API...";
    $zipUrl = "https://github.com/$repo/archive/refs/heads/$branch.zip";
    if ($token) {
        $zipUrl = "https://api.github.com/repos/$repo/zipball/$branch";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SGIN-Updater');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($token) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Accept: application/vnd.github.v3+json"
        ]);
    }

    $zipData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || empty($zipData)) {
        $logs[] = "✗ Gagal mengunduh file ZIP dari GitHub (HTTP Status: $httpCode).";
        if ($httpCode === 404) {
            $logs[] = "Pastikan nama repository ($repo) dan branch ($branch) sudah benar. Jika repository Private, masukkan GitHub Personal Access Token.";
        }
        return [false, implode("\n", $logs)];
    }

    $tempZip = $basePath . '/storage/github_update_temp.zip';
    file_put_contents($tempZip, $zipData);

    $zip = new ZipArchive();
    if ($zip->open($tempZip) === TRUE) {
        $extractPath = $basePath . '/storage/github_extracted';
        if (is_dir($extractPath)) {
            // Clean up old extraction
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                @$todo($fileinfo->getRealPath());
            }
        }
        @mkdir($extractPath, 0777, true);
        $zip->extractTo($extractPath);
        $zip->close();
        @unlink($tempZip);

        // Find the root folder inside zip (e.g. sgin-main or Frhstaaa-sgin-...)
        $extractedItems = scandir($extractPath);
        $sourceDir = '';
        foreach ($extractedItems as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($extractPath . '/' . $item)) {
                $sourceDir = $extractPath . '/' . $item;
                break;
            }
        }

        if (!$sourceDir) {
            $sourceDir = $extractPath;
        }

        // Copy files to project root, preserving .env, storage, etc.
        $ignoreList = ['.env', 'storage', 'public/storage', '.git', 'node_modules'];
        $copyCount = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $subPath = substr($item->getPathname(), strlen($sourceDir) + 1);
            $destPath = $basePath . '/' . $subPath;

            // Check ignore list
            $skip = false;
            foreach ($ignoreList as $ignored) {
                if (str_starts_with($subPath, $ignored)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    @mkdir($destPath, 0777, true);
                }
            } else {
                @copy($item->getPathname(), $destPath);
                $copyCount++;
            }
        }

        $logs[] = "✓ Berhasil mengekstrak dan memperbarui $copyCount file dari GitHub!";
        return [true, implode("\n", $logs)];
    } else {
        $logs[] = "✗ Gagal membuka file zip update.";
        return [false, implode("\n", $logs)];
    }
}

// -------------------------------------------------------------
// WEBHOOK LISTENER (Automatic Deploy on 'git push')
// -------------------------------------------------------------
if (isset($_GET['webhook']) || (isset($_SERVER['HTTP_X_GITHUB_EVENT']) && $_SERVER['HTTP_X_GITHUB_EVENT'] === 'push')) {
    header('Content-Type: application/json');
    $secret = $_GET['secret'] ?? DEFAULT_WEBHOOK_SECRET;
    
    // Optional GitHub Signature verification
    $payload = file_get_contents('php://input');
    $githubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    if ($githubSignature && $secret) {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSignature, $githubSignature)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Invalid webhook secret signature.']);
            exit;
        }
    }

    // Execute Auto-Update
    $repo = $_GET['repo'] ?? DEFAULT_GITHUB_REPO;
    $branch = $_GET['branch'] ?? DEFAULT_GITHUB_BRANCH;
    $token = $_GET['token'] ?? '';

    list($syncSuccess, $syncLogs) = syncFromGithub($basePath, $repo, $branch, $token);

    if ($app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            \Illuminate\Support\Facades\Artisan::call('config:cache');
            \Illuminate\Support\Facades\Artisan::call('route:cache');
            \Illuminate\Support\Facades\Artisan::call('view:cache');
        } catch (\Throwable $e) {}
    }

    echo json_encode([
        'status' => $syncSuccess ? 'success' : 'failed',
        'timestamp' => date('Y-m-d H:i:s'),
        'logs' => $syncLogs,
    ]);
    exit;
}

// -------------------------------------------------------------
// WEB INTERFACE ACTION HANDLER
// -------------------------------------------------------------
$outputLog = '';
$actionExecuted = $_POST['action'] ?? null;
$repoName = $_POST['github_repo'] ?? DEFAULT_GITHUB_REPO;
$branchName = $_POST['github_branch'] ?? DEFAULT_GITHUB_BRANCH;
$githubToken = $_POST['github_token'] ?? '';

if ($actionExecuted) {
    ob_start();
    try {
        switch ($actionExecuted) {
            case 'github_pull_update':
                echo "=====================================================\n";
                echo "     1-CLICK GITHUB PULL & AUTO-UPDATE SGIN          \n";
                echo "=====================================================\n\n";

                // 1. Pull / Download from GitHub
                echo "[1/4] Mengambil pembaruan kodingan dari GitHub ($repoName:$branchName)...\n";
                list($success, $syncLog) = syncFromGithub($basePath, $repoName, $branchName, $githubToken);
                echo $syncLog . "\n\n";

                if ($success && $app) {
                    // 2. Database Migration
                    echo "[2/4] Menjalankan migrasi database baru...\n";
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output() . "\n";

                    // 3. Storage link
                    echo "[3/4] Memeriksa tautan storage...\n";
                    $publicStorage = $basePath . '/public/storage';
                    $appStorage = $basePath . '/storage/app/public';
                    if (!file_exists($publicStorage)) {
                        @symlink($appStorage, $publicStorage);
                        \Illuminate\Support\Facades\Artisan::call('storage:link');
                    }
                    echo "✓ Storage link siap.\n\n";

                    // 4. Optimize & Cache
                    echo "[4/4] Membersihkan dan merefresh cache aplikasi...\n";
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    \Illuminate\Support\Facades\Artisan::call('config:cache');
                    \Illuminate\Support\Facades\Artisan::call('route:cache');
                    \Illuminate\Support\Facades\Artisan::call('view:cache');
                    echo "✓ Cache production berhasil diperbarui.\n\n";

                    echo "=====================================================\n";
                    echo "   ✓ KODINGAN GITHUB & UPDATE SELESAI DITERAPKAN!   \n";
                    echo "=====================================================\n";
                }
                break;

            case 'github_pull_only':
                echo "=== PULL / SYNC DARI GITHUB SAJA ===\n";
                list($success, $syncLog) = syncFromGithub($basePath, $repoName, $branchName, $githubToken);
                echo $syncLog . "\n";
                if ($success && $app) {
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    echo "✓ Cache aplikasi dibersihkan.\n";
                }
                break;

            case 'migrate_only':
                echo "=== MENJALANKAN MIGRASI DATABASE BARU ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output();
                }
                break;

            case 'clear_cache':
                echo "=== MEMBERSIHKAN SELURUH CACHE APLIKASI ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    echo \Illuminate\Support\Facades\Artisan::output();
                }
                break;

            case 'optimize':
                echo "=== MEMPERBARUI CACHE PRODUCTION ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('config:cache');
                    echo \Illuminate\Support\Facades\Artisan::output();
                    \Illuminate\Support\Facades\Artisan::call('route:cache');
                    echo \Illuminate\Support\Facades\Artisan::output();
                    \Illuminate\Support\Facades\Artisan::call('view:cache');
                    echo \Illuminate\Support\Facades\Artisan::output();
                }
                break;

            case 'storage_link':
                echo "=== MEMPERBAIKI STORAGE SYMLINK ===\n";
                $publicStorage = $basePath . '/public/storage';
                $appStorage = $basePath . '/storage/app/public';
                if (file_exists($publicStorage) || is_link($publicStorage)) {
                    @unlink($publicStorage);
                }
                if (@symlink($appStorage, $publicStorage)) {
                    echo "✓ Symlink berhasil: public/storage -> storage/app/public\n";
                } else if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('storage:link');
                    echo \Illuminate\Support\Facades\Artisan::output();
                }
                break;

            case 'delete_self':
                echo "=== MENGHAPUS UPDATE.PHP DARI SERVER ===\n";
                @unlink($basePath . '/update.php');
                @unlink($basePath . '/public/update.php');
                @unlink(__FILE__);
                echo "✓ File update.php berhasil dihapus demi keamanan server Anda!\n";
                break;
        }
    } catch (\Throwable $e) {
        echo "✗ Terjadi Error: " . $e->getMessage() . "\n";
        echo "Trace:\n" . $e->getTraceAsString();
    }
    $outputLog = ob_get_clean();
}

// Check Git Status if .git is available
$lastCommitInfo = 'Tidak terdeteksi';
if (is_dir($basePath . '/.git') && function_exists('shell_exec')) {
    $commitLog = @shell_exec("cd " . escapeshellarg($basePath) . " && git log -1 --pretty=format:'%h - %s (%cr) <%an>' 2>&1");
    if ($commitLog) {
        $lastCommitInfo = $commitLog;
    }
}

// Database Connection Status
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

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$currentHost = $_SERVER['HTTP_HOST'] ?? 'leave-application-sgin.frahesta.com';
$webhookUrl = "$protocol://$currentHost/update.php?webhook=1&secret=" . DEFAULT_WEBHOOK_SECRET;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN - GitHub Auto-Update & Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-8">

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Header -->
        <div class="p-6 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-emerald-950 border border-slate-700/80 shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold uppercase border border-emerald-400/30">GitHub Sync v2.0</span>
                    <span class="text-xs text-slate-300">Auto Pull & Deploy</span>
                </div>
                <h1 class="text-2xl font-black text-white mt-1">SGIN GitHub Update Center</h1>
                <p class="text-xs text-slate-300 mt-0.5">Tarik kodingan terbaru dari GitHub & terapkan migrasi otomatis ke server CyberPanel</p>
            </div>
            <a href="./" class="px-5 py-2.5 rounded-2xl bg-white text-slate-950 hover:bg-emerald-50 font-black text-xs shadow-lg transition-transform hover:scale-105 shrink-0 text-center">
                &larr; Buka Website SGIN
            </a>
        </div>

        <!-- Status Overview Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Repository</span>
                <span class="text-xs font-black text-emerald-400 truncate block" title="<?= DEFAULT_GITHUB_REPO ?>"><?= DEFAULT_GITHUB_REPO ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Branch Target</span>
                <span class="text-sm font-black text-blue-400"><?= DEFAULT_GITHUB_BRANCH ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Database</span>
                <span class="text-xs font-bold <?= $dbConnected ? 'text-emerald-400' : 'text-amber-400' ?> truncate block">
                    <?= $dbConnected ? '✓ Terhubung' : '⚠️ ' . htmlspecialchars($dbStatus) ?>
                </span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Last Commit</span>
                <span class="text-[11px] font-mono text-slate-300 truncate block" title="<?= htmlspecialchars($lastCommitInfo) ?>">
                    <?= htmlspecialchars($lastCommitInfo) ?>
                </span>
            </div>
        </div>

        <!-- 1-Click Pull from GitHub & Auto-Update -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-emerald-500/40 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-black text-white flex items-center space-x-2">
                        <span>🚀 Tarik dari GitHub & Update Otomatis (1-Click)</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Mengambil kodingan terbaru dari GitHub (<code><?= DEFAULT_GITHUB_REPO ?>:<?= DEFAULT_GITHUB_BRANCH ?></code>), menjalankan migrasi database baru, dan merefresh cache server.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="github_pull_update">
                    <input type="hidden" name="github_repo" value="<?= htmlspecialchars($repoName) ?>">
                    <input type="hidden" name="github_branch" value="<?= htmlspecialchars($branchName) ?>">
                    <input type="hidden" name="github_token" value="<?= htmlspecialchars($githubToken) ?>">
                    <button type="submit" onclick="return confirm('Tarik kodingan terbaru dari GitHub dan terapkan update sekarang?')" class="px-6 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs shadow-lg shadow-emerald-600/30 flex items-center space-x-2 transition-all hover:scale-105 shrink-0">
                        <span>Tarik dari GitHub & Update</span>
                        <span>&rarr;</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- GitHub Webhook Info Card -->
        <div class="p-6 rounded-3xl bg-slate-900/60 border border-indigo-500/30 space-y-3">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                <h3 class="text-sm font-black text-indigo-300 uppercase tracking-wider">⚡ Setup GitHub Webhook (Auto-Deploy Setiap 'git push')</h3>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
                Ingin server CyberPanel Anda otomatis ter-update setiap kali Anda melakukan <code>git push</code> di laptop/komputer?
                Tambahkan Webhook ini di GitHub repository Anda:
            </p>
            <div class="space-y-2 text-xs font-mono bg-black/60 p-4 rounded-2xl border border-slate-800">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-slate-500">Payload URL:</span>
                    <span class="text-indigo-400 select-all font-bold"><?= htmlspecialchars($webhookUrl) ?></span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-slate-500">Content type:</span>
                    <span class="text-emerald-400">application/json</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-slate-500">Secret:</span>
                    <span class="text-amber-400"><?= DEFAULT_WEBHOOK_SECRET ?></span>
                </div>
            </div>
            <p class="text-[11px] text-slate-400">
                Cara pasang di GitHub: Buka repository <strong><?= DEFAULT_GITHUB_REPO ?></strong> &rarr; <strong>Settings</strong> &rarr; <strong>Webhooks</strong> &rarr; <strong>Add Webhook</strong> &rarr; Masukkan Payload URL di atas &rarr; Pilih <em>"Just the push event"</em> &rarr; <strong>Add webhook</strong>.
            </p>
        </div>

        <!-- Individual Control Actions Grid -->
        <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 space-y-5">
            <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-400">Aksi Pembaruan Terpisah:</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">

                <!-- 1. Pull Only -->
                <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">1. Tarik Kodingan GitHub Saja</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Hanya mengunduh / pull file terbaru tanpa migrasi database.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="github_pull_only">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs transition-colors">
                            Git Pull Saja
                        </button>
                    </form>
                </div>

                <!-- 2. Migrate Only -->
                <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">2. Jalankan Migrasi Database</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Terapkan perubahan tabel / kolom baru ke database server.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="migrate_only">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs transition-colors">
                            Jalankan Migrasi
                        </button>
                    </form>
                </div>

                <!-- 3. Clear Cache -->
                <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">3. Bersihkan Seluruh Cache</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Hapus cache config, route, dan views agar perubahan langsung aktif.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs transition-colors">
                            Clear Cache
                        </button>
                    </form>
                </div>

                <!-- 4. Storage Link -->
                <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between space-y-3">
                    <div>
                        <h4 class="text-xs font-bold text-white">4. Perbaiki Storage Symlink</h4>
                        <p class="text-[11px] text-slate-400 mt-0.5">Hubungkan ulang public/storage agar file lampiran tidak 404.</p>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="storage_link">
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs transition-colors">
                            Perbaiki Storage Link
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <!-- Terminal Execution Output Box -->
        <?php if (!empty($outputLog)): ?>
        <div class="p-5 rounded-3xl bg-slate-950 border border-slate-800 shadow-2xl space-y-3 animate-fade-in">
            <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                <span class="text-xs font-bold text-emerald-400 flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Hasil Eksekusi Perintah:</span>
                </span>
                <span class="text-[10px] text-slate-500 font-mono"><?= date('H:i:s') ?> WIB</span>
            </div>
            <pre class="p-4 rounded-2xl bg-black/70 text-emerald-300 font-mono text-xs overflow-x-auto leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($outputLog) ?></pre>
        </div>
        <?php endif; ?>

        <!-- Security Warning & Delete Button -->
        <div class="p-5 rounded-3xl bg-rose-950/40 border border-rose-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h4 class="text-xs font-extrabold text-rose-300 uppercase tracking-wider">🔒 Tindakan Keamanan</h4>
                <p class="text-[11px] text-rose-200/80 leading-relaxed">
                    Jika tidak menggunakan webhook otomatis, Anda dapat menghapus file <code>update.php</code> demi keamanan server.
                </p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_self">
                <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menghapus file update.php ini sekarang?')" class="px-5 py-2.5 rounded-2xl bg-rose-700 hover:bg-rose-600 text-white font-bold text-xs shadow-lg transition-colors shrink-0">
                    Hapus File update.php
                </button>
            </form>
        </div>

    </div>

</body>
</html>
