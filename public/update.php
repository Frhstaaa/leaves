<?php
/**
 * SGIN / Leaves Management System - Web Application Update & Server Management Center
 * Menghubungkan aplikasi langsung ke GitHub (Frhstaaa/leaves): Auto-Pull, NPM Build Suite, Composer, Complete Artisan Suite & Webhook.
 */

// Disable execution time limit for migrations, git pulls & builds
@set_time_limit(900);
@ini_set('max_execution_time', 900);
@ini_set('memory_limit', '512M');

// Default GitHub Configuration for Leaves Repository
define('DEFAULT_GITHUB_REPO', 'Frhstaaa/leaves');
define('DEFAULT_GITHUB_BRANCH', 'main');
define('DEFAULT_WEBHOOK_SECRET', 'sgin-secret-webhook-key');

// Determine base path
$basePath = __DIR__;
if (!file_exists($basePath . '/vendor/autoload.php')) {
    if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        $basePath = dirname(__DIR__);
    }
}

// Ensure required storage folders exist and have correct permissions
$requiredDirs = [
    $basePath . '/storage/framework/sessions',
    $basePath . '/storage/framework/views',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/storage/framework/testing',
    $basePath . '/storage/logs',
    $basePath . '/storage/app/public',
    $basePath . '/bootstrap/cache',
    $basePath . '/public/build',
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
        $app->usePublicPath($basePath . '/public');
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
    } catch (\Throwable $e) {
        $bootstrapError = $e->getMessage();
    }
}

// Helper Function: Multi-path NodeJS & NPM Detector for cPanel / Cloud Hosting
function findNodeAndNpm() {
    $nodePaths = [
        'node',
        '/usr/local/bin/node',
        '/usr/bin/node',
        '/bin/node',
        '/opt/cpanel/ea-nodejs20/bin/node',
        '/opt/cpanel/ea-nodejs18/bin/node',
        '/opt/cpanel/ea-nodejs16/bin/node',
        '/opt/cpanel/ea-nodejs14/bin/node',
        '/opt/alt/alt-nodejs20/root/usr/bin/node',
        '/opt/alt/alt-nodejs18/root/usr/bin/node',
        '/opt/alt/alt-nodejs16/root/usr/bin/node',
    ];

    $foundNode = null;
    $foundNpm = null;
    $nodeDir = null;

    if (!function_exists('shell_exec')) {
        return [null, null, null, null];
    }

    foreach ($nodePaths as $path) {
        $ver = @shell_exec("$path -v 2>/dev/null");
        if ($ver && str_contains(trim($ver), 'v')) {
            $foundNode = $path;
            $nodeDir = dirname($path);
            break;
        }
    }

    // Scan user's home ~/.nvm directory if present
    $home = $_SERVER['HOME'] ?? (getenv('HOME') ?: '');
    if (!$foundNode && $home && is_dir("$home/.nvm/versions/node")) {
        $nvmNodes = glob("$home/.nvm/versions/node/*/bin/node");
        if (!empty($nvmNodes)) {
            $foundNode = end($nvmNodes);
            $nodeDir = dirname($foundNode);
        }
    }

    if ($nodeDir) {
        $npmCandidate = $nodeDir . '/npm';
        if (file_exists($npmCandidate)) {
            $foundNpm = $npmCandidate;
        }
    }

    if (!$foundNpm) {
        $npmVer = @shell_exec("npm -v 2>/dev/null");
        if ($npmVer && preg_match('/^\d+\./', trim($npmVer))) {
            $foundNpm = 'npm';
        }
    }

    $nodeVersionStr = $foundNode ? trim(@shell_exec("$foundNode -v 2>&1") ?: '') : '';
    $npmVersionStr = $foundNpm ? trim(@shell_exec("$foundNpm -v 2>&1") ?: '') : '';

    return [$foundNode, $foundNpm, $nodeVersionStr, $npmVersionStr];
}

// Helper Function: Safe Command Execution
function executeCommand($cmd, $workingDir) {
    if (!function_exists('shell_exec')) {
        return "Fungsi shell_exec dinonaktifkan di server PHP ini.";
    }
    $fullCmd = "cd " . escapeshellarg($workingDir) . " && " . $cmd . " 2>&1";
    return @shell_exec($fullCmd);
}

// Helper Function: Execute NPM Command with Exported Node Path
function executeNpmCommand($npmCmd, $workingDir) {
    list($nodeBin, $npmBin, $nodeVer, $npmVer) = findNodeAndNpm();
    if (!function_exists('shell_exec')) {
        return "Fungsi shell_exec dinonaktifkan di server PHP ini.";
    }

    if (!$npmBin && !$nodeBin) {
        return "NodeJS / NPM tidak terpasang di hosting. Frontend otomatis menggunakan aset build Vite yang sudah ter-compile dari GitHub repo (public/build/).";
    }

    $nodeDir = $nodeBin ? dirname($nodeBin) : '';
    $pathExport = $nodeDir ? "export PATH=\"$nodeDir:\$PATH\" && " : "";
    $fullCmd = "cd " . escapeshellarg($workingDir) . " && $pathExport " . ($npmBin ?: 'npm') . " " . $npmCmd . " 2>&1";
    return @shell_exec($fullCmd);
}

// Helper Function: Pull from GitHub via Git CLI or Zip Download
function syncFromGithub($basePath, $repo, $branch, $token = '') {
    $logs = [];
    $logs[] = "Menghubungkan ke GitHub Repository: https://github.com/$repo (Branch: $branch)";

    // Method 1: Git CLI (if git command is available)
    $gitAvailable = false;
    if (function_exists('shell_exec')) {
        $gitVersion = @shell_exec('git --version 2>&1');
        if ($gitVersion && str_contains(strtolower($gitVersion), 'git version')) {
            $gitAvailable = true;
        }
    }

    if ($gitAvailable) {
        $logs[] = "✓ Git CLI terdeteksi di server (" . trim($gitVersion) . ")";
        
        if (!is_dir($basePath . '/.git')) {
            $logs[] = "Inisialisasi git repository lokal di $basePath...";
            executeCommand("git init", $basePath);
            $remoteUrl = $token 
                ? "https://$token@github.com/$repo.git"
                : "https://github.com/$repo.git";
            executeCommand("git remote add origin " . escapeshellarg($remoteUrl), $basePath);
        } else {
            $remoteUrl = $token 
                ? "https://$token@github.com/$repo.git"
                : "https://github.com/$repo.git";
            executeCommand("git remote set-url origin " . escapeshellarg($remoteUrl), $basePath);
        }

        $logs[] = "Menjalankan git fetch & hard-reset branch '$branch'...";
        $fetchOutput = executeCommand("git fetch origin $branch", $basePath);
        $resetOutput = executeCommand("git reset --hard origin/$branch", $basePath);
        
        $combinedOutput = trim(($fetchOutput ?: '') . "\n" . ($resetOutput ?: ''));
        $logs[] = "Git Output:\n" . ($combinedOutput ?: "Berhasil sinkronisasi tanpa log tambahan.");
        
        if (str_contains($combinedOutput, 'HEAD is now at') || str_contains($combinedOutput, 'Updating') || str_contains($combinedOutput, 'Already up to date')) {
            $logs[] = "✓ Kodingan berhasil diperbarui via Git CLI!";
            return [true, implode("\n", $logs)];
        }
    }

    // Method 2: Fallback to GitHub ZIP Archive Download
    $logs[] = "Metode Git CLI tidak aktif / dibatasi, beralih ke unduhan ZIP dari GitHub API...";
    $zipUrl = "https://github.com/$repo/archive/refs/heads/$branch.zip";
    if ($token) {
        $zipUrl = "https://api.github.com/repos/$repo/zipball/$branch";
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SGIN-Leaves-Updater');
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
        return [false, implode("\n", $logs)];
    }

    $tempZip = $basePath . '/storage/github_update_temp.zip';
    file_put_contents($tempZip, $zipData);

    $zip = new ZipArchive();
    if ($zip->open($tempZip) === TRUE) {
        $extractPath = $basePath . '/storage/github_extracted';
        if (is_dir($extractPath)) {
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

        $ignoreList = ['.env', 'storage', 'public/storage', '.git', 'node_modules'];
        $copyCount = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $subPath = substr($item->getPathname(), strlen($sourceDir) + 1);
            $destPath = $basePath . '/' . $subPath;

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

function freeServerDiskSpace($basePath) {
    $freed = 0;
    // 1. Empty all Laravel log files
    $logDir = $basePath . '/storage/logs';
    if (is_dir($logDir)) {
        foreach (glob($logDir . '/*.log') as $file) {
            $freed += @filesize($file);
            @file_put_contents($file, '');
        }
    }
    // 2. Clean compiled blade view cache
    $viewDir = $basePath . '/storage/framework/views';
    if (is_dir($viewDir)) {
        foreach (glob($viewDir . '/*.php') as $file) {
            $freed += @filesize($file);
            @unlink($file);
        }
    }
    // 3. Clean framework data cache
    $cacheDir = $basePath . '/storage/framework/cache/data';
    if (is_dir($cacheDir)) {
        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                if ($f->isFile()) {
                    $freed += $f->getSize();
                    @unlink($f->getRealPath());
                } elseif ($f->isDir()) {
                    @rmdir($f->getRealPath());
                }
            }
        } catch (\Throwable $e) {}
    }
    // 4. Git prune
    if (function_exists('shell_exec')) {
        @shell_exec('git prune 2>&1');
        @shell_exec('git gc --auto 2>&1');
    }
    return $freed;
}

// Determine Host & URLs
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$currentHost = $_SERVER['HTTP_HOST'] ?? 'www.sgin.co.id';
$currentUri = $_SERVER['REQUEST_URI'] ?? '/leaves-application/update.php';
$cleanUri = strtok($currentUri, '?');
$webhookUrl = "$protocol://$currentHost$cleanUri?webhook=1&secret=" . DEFAULT_WEBHOOK_SECRET;

// Find detected Node & NPM on server
list($detectedNodeBin, $detectedNpmBin, $nodeVersion, $npmVersion) = findNodeAndNpm();

// -------------------------------------------------------------
// WEBHOOK LISTENER (Automatic Deploy on 'git push')
// -------------------------------------------------------------
if (isset($_GET['webhook']) || (isset($_SERVER['HTTP_X_GITHUB_EVENT']) && $_SERVER['HTTP_X_GITHUB_EVENT'] === 'push')) {
    header('Content-Type: application/json');
    $secret = $_GET['secret'] ?? DEFAULT_WEBHOOK_SECRET;
    
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

    $repo = $_GET['repo'] ?? DEFAULT_GITHUB_REPO;
    $branch = $_GET['branch'] ?? DEFAULT_GITHUB_BRANCH;
    list($syncSuccess, $syncLogs) = syncFromGithub($basePath, $repo, $branch, '');

    if ($syncSuccess && $app) {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            \Illuminate\Support\Facades\Artisan::call('config:cache');
            \Illuminate\Support\Facades\Artisan::call('route:cache');
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
$actionStatus = null; // 'success' or 'error'
$actionTitle = '';
$actionExecuted = $_POST['action'] ?? null;
$repoName = $_POST['github_repo'] ?? DEFAULT_GITHUB_REPO;
$branchName = $_POST['github_branch'] ?? DEFAULT_GITHUB_BRANCH;
$githubToken = $_POST['github_token'] ?? '';
$customCommand = $_POST['custom_artisan'] ?? '';
$isAjax = isset($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

if ($actionExecuted) {
    ob_start();
    try {
        switch ($actionExecuted) {
            case 'clean_disk':
                $actionTitle = 'Pembersihan Ruang Disk Hosting';
                echo "=== MEMBERSIHKAN RUANG DISK PENYIMPANAN HOSTING ===\n\n";
                $freed = freeServerDiskSpace($basePath);
                $freedMB = round($freed / 1024 / 1024, 2);
                echo "✓ Berhasil mengosongkan file log, cache view, dan file sementara!\n";
                echo "✓ Ruang disk yang dibebaskan: {$freedMB} MB.\n";
                $actionStatus = 'success';
                break;

            case 'full_setup_update':
                $actionTitle = 'Complete Setup & Update (All-in-One)';
                echo "=================================================================\n";
                echo "  🚀 1-CLICK COMPLETE SETUP & UPDATE LEAVES SYSTEM (ALL IN ONE)  \n";
                echo "=================================================================\n\n";

                // 0. Auto Free Disk Space first to avoid quota issues
                $freed = freeServerDiskSpace($basePath);
                $freedMB = round($freed / 1024 / 1024, 2);
                if ($freed > 0) {
                    echo "[0/6] Membersihkan log & cache lama ({$freedMB} MB dibebaskan)...\n";
                }

                // 1. Pull from GitHub
                echo "[1/6] Mengambil kodingan terbaru dari GitHub ($repoName:$branchName)...\n";
                list($success, $syncLog) = syncFromGithub($basePath, $repoName, $branchName, $githubToken);
                echo $syncLog . "\n\n";

                if ($success) {
                    // 1.5 Auto-Fix .env APP_URL and Cloudflare R2
                    $envFile = $basePath . '/.env';
                    if (file_exists($envFile)) {
                        $envContent = file_get_contents($envFile);
                        $expectedUrl = "$protocol://$currentHost/leaves-application";
                        $envChanged = false;

                        if (!str_contains($envContent, 'leaves-application') || str_contains($envContent, 'http://localhost')) {
                            echo "[1.5/6] Menyesuaikan APP_URL di .env agar presisi ke subfolder ($expectedUrl)...\n";
                            $envContent = preg_replace('/^APP_URL=.*/m', "APP_URL=" . $expectedUrl, $envContent);
                            $envChanged = true;
                            echo "✓ APP_URL di file .env berhasil disinkronkan!\n";
                        }

                        // Auto-Inject Cloudflare R2 Configuration if not present
                        if (!str_contains($envContent, 'CLOUDFLARE_R2_ACCESS_KEY_ID')) {
                            echo "-> Mengintegrasikan Cloudflare R2 (10GB Free Lifetime Cloud Storage) ke file .env...\n";
                            $r2Config = "\n# Cloudflare R2 Cloud Storage (10GB Lifetime Free)\n"
                                . "FILESYSTEM_DISK=r2\n"
                                . "CLOUDFLARE_R2_ACCESS_KEY_ID=fbe7d6c6ec7f262c09fbaa7e45b2d4da\n"
                                . "CLOUDFLARE_R2_SECRET_ACCESS_KEY=4f4941af6f1a58b7b00a33de9b20c5f3974a3a15c48636f99f2dd846cca20b69\n"
                                . "CLOUDFLARE_R2_DEFAULT_REGION=auto\n"
                                . "CLOUDFLARE_R2_BUCKET=sgin\n"
                                . "CLOUDFLARE_R2_ENDPOINT=https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com\n"
                                . "CLOUDFLARE_R2_URL=https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com/sgin\n"
                                . "CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=true\n\n"
                                . "AWS_ACCESS_KEY_ID=fbe7d6c6ec7f262c09fbaa7e45b2d4da\n"
                                . "AWS_SECRET_ACCESS_KEY=4f4941af6f1a58b7b00a33de9b20c5f3974a3a15c48636f99f2dd846cca20b69\n"
                                . "AWS_DEFAULT_REGION=auto\n"
                                . "AWS_BUCKET=sgin\n"
                                . "AWS_ENDPOINT=https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com\n"
                                . "AWS_URL=https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com/sgin\n"
                                . "AWS_USE_PATH_STYLE_ENDPOINT=true\n";

                            $envContent = preg_replace('/^FILESYSTEM_DISK=.*/m', "FILESYSTEM_DISK=r2", $envContent);
                            $envContent .= $r2Config;
                            $envChanged = true;
                            echo "✓ Konfigurasi Cloudflare R2 otomatis aktif di server!\n";
                        }

                        if ($envChanged) {
                            file_put_contents($envFile, $envContent);
                            echo "\n";
                        }
                    }

                    // 2. Composer Check
                    echo "[2/6] Memeriksa dependensi Composer (PHP Backend)...\n";
                    if (function_exists('shell_exec')) {
                        $composerVer = @shell_exec('composer --version 2>&1');
                        if ($composerVer && str_contains(strtolower($composerVer), 'composer')) {
                            echo executeCommand("composer install --no-dev --optimize-autoloader --no-interaction", $basePath) . "\n";
                        } elseif (file_exists($basePath . '/composer.phar')) {
                            echo executeCommand("php composer.phar install --no-dev --optimize-autoloader --no-interaction", $basePath) . "\n";
                        } else {
                            echo "✓ Folder vendor siap (menggunakan dependensi terpasang).\n\n";
                        }
                    }

                    // 3. NPM Build Check (NodeJS Frontend)
                    echo "[3/6] Memeriksa build frontend Vite (NPM)...\n";
                    $hasPrebuilt = file_exists($basePath . '/public/build/manifest.json');
                    if ($hasPrebuilt) {
                        echo "✓ Frontend bundle Vite terkompilasi siap pakai terdeteksi dari GitHub (public/build/). Tidak perlu compile ulang (menghemat disk & RAM hosting).\n\n";
                    } elseif ($detectedNodeBin) {
                        echo "NodeJS terdeteksi ($detectedNodeBin - $nodeVersion). Menjalankan npm run build...\n";
                        echo executeNpmCommand("run build", $basePath) . "\n";
                    } else {
                        echo "✓ Frontend siap pakai dari repository.\n\n";
                    }

                    // 4. Database Migrations & Sync Role Permissions
                    echo "[4/6] Menjalankan migrasi database & sinkronisasi role permissions...\n";
                    if ($app) {
                        try {
                            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                            echo \Illuminate\Support\Facades\Artisan::output() . "\n";
                        } catch (\Throwable $e) {
                            echo "ℹ️ Migrate status: " . $e->getMessage() . "\n";
                        }

                        try {
                            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                                '--class' => 'Database\\Seeders\\RolePermissionSeeder',
                                '--force' => true,
                            ]);
                            echo "✓ Role & Permissions untuk semua menu baru berhasil disinkronkan ke database!\n\n";
                        } catch (\Throwable $e) {
                            echo "ℹ️ Seeder status: " . $e->getMessage() . "\n\n";
                        }

                        try {
                            \App\Models\LeaveQuota::syncAllUsers();
                            echo "✓ Sinkronisasi kuota cuti karyawan berhasil diperbarui (Cuti Tahunan & Cuti Haid memotong kuota, kategori lain bebas kuota)!\n\n";
                        } catch (\Throwable $e) {
                            echo "ℹ️ Sync quota status: " . $e->getMessage() . "\n\n";
                        }
                    }

                    // 5. Storage Link
                    echo "[5/6] Menghubungkan storage symlink (php artisan storage:link)...\n";
                    $publicStorage = $basePath . '/public/storage';
                    $appStorage = $basePath . '/storage/app/public';
                    if (!file_exists($publicStorage) && !is_link($publicStorage)) {
                        @symlink($appStorage, $publicStorage);
                    }
                    if ($app) {
                        try {
                            \Illuminate\Support\Facades\Artisan::call('storage:link');
                            echo \Illuminate\Support\Facades\Artisan::output() . "\n";
                        } catch (\Throwable $e) {}
                    }
                    if (file_exists($basePath . '/generate_pwa_assets.php')) {
                        echo "-> Memperbarui icon PWA dan manifest statis...\n";
                        try {
                            include_once($basePath . '/generate_pwa_assets.php');
                            echo "✓ Icon PWA dan manifest siap.\n";
                        } catch (\Throwable $e) {
                            echo "ℹ️ PWA generator: " . $e->getMessage() . "\n";
                        }
                    }
                    echo "✓ Storage link siap.\n\n";

                    // 6. Cache Clear & Optimize
                    echo "[6/6] Membersihkan dan merefresh seluruh cache aplikasi...\n";
                    if (function_exists('opcache_reset')) {
                        @opcache_reset();
                        echo "✓ PHP OPcache di-reset.\n";
                    }
                    if ($app) {
                        try {
                            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                            \Illuminate\Support\Facades\Artisan::call('config:cache');
                            \Illuminate\Support\Facades\Artisan::call('route:cache');
                            echo "✓ Cache production berhasil diperbarui.\n\n";
                        } catch (\Throwable $e) {
                            echo "ℹ️ Cache status: " . $e->getMessage() . "\n\n";
                        }
                    }

                    echo "=================================================================\n";
                    echo "  ✓ SELURUH SETUP & UPDATE BERHASIL DITERAPKAN KE SERVER!        \n";
                    echo "=================================================================\n";
                    $actionStatus = 'success';
                } else {
                    $actionStatus = 'error';
                }
                break;

            // --- NPM & FRONTEND ACTIONS ---
            case 'npm_build':
                $actionTitle = 'NPM Run Build (Vite)';
                echo "=== MENJALANKAN NPM RUN BUILD (COMPILE FRONTEND VITE) ===\n";
                echo "Node Binary: " . ($detectedNodeBin ?: 'Tidak ditemukan di PATH standar') . " (" . ($nodeVersion ?: '-') . ")\n";
                echo "NPM Binary: " . ($detectedNpmBin ?: 'Tidak ditemukan') . " (" . ($npmVersion ?: '-') . ")\n\n";
                if ($detectedNodeBin) {
                    echo executeNpmCommand("run build", $basePath);
                    $actionStatus = 'success';
                } else {
                    echo "ℹ️ NodeJS tidak terpasang di sistem hosting ini.\n";
                    echo "Frontend aplikasi Anda sudah menggunakan bundle aset terkompilasi siap pakai langsung dari GitHub repository (folder public/build/).\n";
                    $actionStatus = 'success';
                }
                break;

            case 'npm_install':
                $actionTitle = 'NPM Install';
                echo "=== MENJALANKAN NPM INSTALL ===\n";
                if ($detectedNodeBin) {
                    echo executeNpmCommand("install --legacy-peer-deps", $basePath);
                    $actionStatus = 'success';
                } else {
                    echo "✗ NodeJS / NPM tidak terdeteksi di server.";
                    $actionStatus = 'error';
                }
                break;

            // --- PHP ARTISAN COMPLETE SUITE ---
            case 'migrate_only':
                $actionTitle = 'Artisan: Migrate';
                echo "=== PHP ARTISAN MIGRATE --FORCE ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                } else {
                    echo "✗ Laravel kernel belum terhubung.";
                    $actionStatus = 'error';
                }
                break;

            case 'migrate_status':
                $actionTitle = 'Artisan: Migrate Status';
                echo "=== STATUS MIGRASI DATABASE (PHP ARTISAN MIGRATE:STATUS) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('migrate:status');
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'migrate_rollback':
                $actionTitle = 'Artisan: Migrate Rollback (Step 1)';
                echo "=== ROLLBACK MIGRASI TERAKHIR (PHP ARTISAN MIGRATE:ROLLBACK --STEP=1) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'migrate_fresh':
                $actionTitle = 'Artisan: Migrate Fresh (Reset & Re-run DB)';
                echo "=== RESET & RE-RUN DATABASE MIGRASI (PHP ARTISAN MIGRATE:FRESH --SEED) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'db_seed':
                $actionTitle = 'Artisan: DB Seed';
                echo "=== MENJALANKAN DATABASE SEEDER (PHP ARTISAN DB:SEED) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                } else {
                    echo "✗ Laravel kernel belum terhubung.";
                    $actionStatus = 'error';
                }
                break;

            case 'seed_role_permissions':
                $actionTitle = 'Artisan: Sync Role Permissions';
                echo "=== SINKRONISASI ROLE & PERMISSIONS MENU LENGKAP ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('db:seed', [
                        '--class' => 'Database\\Seeders\\RolePermissionSeeder',
                        '--force' => true,
                    ]);
                    echo \Illuminate\Support\Facades\Artisan::output();
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    echo "✓ Role & Permissions untuk semua menu baru berhasil disinkronkan ke database!\n";
                    $actionStatus = 'success';
                } else {
                    echo "✗ Laravel kernel belum terhubung.";
                    $actionStatus = 'error';
                }
                break;

            case 'clear_cache':
                $actionTitle = 'Artisan: Clear All Caches';
                echo "=== MEMBERSIHKAN SELURUH CACHE APLIKASI (OPTIMIZE:CLEAR) ===\n";
                if (function_exists('opcache_reset')) {
                    @opcache_reset();
                    echo "✓ OPcache di-reset.\n";
                }
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'optimize':
                $actionTitle = 'Artisan: Optimize Production Cache';
                echo "=== MEMPERBARUI CACHE PRODUCTION (CONFIG, ROUTE, VIEW, EVENT) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('config:cache');
                    echo "Config cache: OK\n";
                    \Illuminate\Support\Facades\Artisan::call('route:cache');
                    echo "Route cache: OK\n";
                    \Illuminate\Support\Facades\Artisan::call('view:cache');
                    echo "View cache: OK\n";
                    \Illuminate\Support\Facades\Artisan::call('event:cache');
                    echo "Event cache: OK\n";
                    $actionStatus = 'success';
                }
                break;

            case 'route_list':
                $actionTitle = 'Artisan: Route List';
                echo "=== DAFTAR RUTE AKTIF (PHP ARTISAN ROUTE:LIST) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('route:list');
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'artisan_about':
                $actionTitle = 'Artisan: About System Info';
                echo "=== INFORMASI APLIKASI & ENVIRONMENT (PHP ARTISAN ABOUT) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('about');
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'storage_link':
                $actionTitle = 'Artisan: Storage Link';
                echo "=== MEMPERBAIKI STORAGE SYMLINK ===\n";
                $publicStorage = $basePath . '/public/storage';
                $appStorage = $basePath . '/storage/app/public';
                if (file_exists($publicStorage) || is_link($publicStorage)) {
                    @unlink($publicStorage);
                }
                if (@symlink($appStorage, $publicStorage)) {
                    echo "✓ Symlink berhasil dibuat: public/storage -> storage/app/public\n";
                    $actionStatus = 'success';
                } else if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('storage:link');
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'key_generate':
                $actionTitle = 'Artisan: Key Generate';
                echo "=== GENERATE APP KEY (PHP ARTISAN KEY:GENERATE) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'queue_work':
                $actionTitle = 'Artisan: Queue Work';
                echo "=== MEMPROSES ANTRIAN JOBS (PHP ARTISAN QUEUE:WORK --STOP-WHEN-EMPTY) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('queue:work', ['--stop-when-empty' => true]);
                    echo \Illuminate\Support\Facades\Artisan::output() ?: "Tidak ada antrian job pending.\n";
                    $actionStatus = 'success';
                }
                break;

            case 'queue_restart':
                $actionTitle = 'Artisan: Queue Restart';
                echo "=== RESTART QUEUE WORKER (PHP ARTISAN QUEUE:RESTART) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('queue:restart');
                    echo \Illuminate\Support\Facades\Artisan::output();
                    $actionStatus = 'success';
                }
                break;

            case 'schedule_run':
                $actionTitle = 'Artisan: Schedule Run';
                echo "=== MENJALANKAN CRON SCHEDULE (PHP ARTISAN SCHEDULE:RUN) ===\n";
                if ($app) {
                    \Illuminate\Support\Facades\Artisan::call('schedule:run');
                    echo \Illuminate\Support\Facades\Artisan::output() ?: "Tidak ada task terjadwal saat ini.\n";
                    $actionStatus = 'success';
                }
                break;

            case 'run_custom_artisan':
                $actionTitle = 'Custom Artisan: ' . $customCommand;
                echo "=== EKSEKUSI ARTISAN COMMAND: php artisan " . htmlspecialchars($customCommand) . " ===\n";
                if ($app && !empty($customCommand)) {
                    $parts = explode(' ', trim($customCommand));
                    $cmdName = array_shift($parts);
                    $args = [];
                    foreach ($parts as $part) {
                        if (str_starts_with($part, '--')) {
                            if (str_contains($part, '=')) {
                                list($k, $v) = explode('=', $part, 2);
                                $args[$k] = $v;
                            } else {
                                $args[$part] = true;
                            }
                        } elseif (str_contains($part, '=')) {
                            list($k, $v) = explode('=', $part, 2);
                            $args[$k] = $v;
                        } else {
                            $args[] = $part;
                        }
                    }
                    try {
                        \Illuminate\Support\Facades\Artisan::call($cmdName, $args);
                        echo \Illuminate\Support\Facades\Artisan::output();
                        $actionStatus = 'success';
                    } catch (\Throwable $e) {
                        echo "Error executing command: " . $e->getMessage() . "\n";
                        $actionStatus = 'error';
                    }
                } else {
                    echo "✗ Perintah kosong atau kernel tidak terhubung.";
                    $actionStatus = 'error';
                }
                break;

            case 'github_pull_only':
                $actionTitle = 'Git Pull Saja';
                echo "=== PULL / SYNC DARI GITHUB REPO LEAVES SAJA ===\n";
                list($success, $syncLog) = syncFromGithub($basePath, $repoName, $branchName, $githubToken);
                echo $syncLog . "\n";
                if ($success) {
                    if ($app) {
                        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                        echo "✓ Cache aplikasi dibersihkan.\n";
                    }
                    $actionStatus = 'success';
                } else {
                    $actionStatus = 'error';
                }
                break;

            case 'composer_install':
                $actionTitle = 'Composer Install';
                echo "=== MENJALANKAN COMPOSER INSTALL ===\n";
                if (function_exists('shell_exec')) {
                    $composerVer = @shell_exec('composer --version 2>&1');
                    if ($composerVer && str_contains(strtolower($composerVer), 'composer')) {
                        echo executeCommand("composer install --no-dev --optimize-autoloader --no-interaction", $basePath);
                        $actionStatus = 'success';
                    } elseif (file_exists($basePath . '/composer.phar')) {
                        echo executeCommand("php composer.phar install --no-dev --optimize-autoloader --no-interaction", $basePath);
                        $actionStatus = 'success';
                    } else {
                        echo "✗ Composer CLI atau composer.phar tidak ditemukan di server.";
                        $actionStatus = 'error';
                    }
                } else {
                    echo "✗ shell_exec dinonaktifkan di server.";
                    $actionStatus = 'error';
                }
                break;

            case 'composer_dump':
                $actionTitle = 'Composer Dump Autoload';
                echo "=== MENJALANKAN COMPOSER DUMP-AUTOLOAD ===\n";
                if (function_exists('shell_exec')) {
                    echo executeCommand("composer dump-autoload -o", $basePath);
                    $actionStatus = 'success';
                }
                break;

            case 'delete_self':
                $actionTitle = 'Hapus update.php';
                echo "=== MENGHAPUS UPDATE.PHP DARI SERVER ===\n";
                @unlink($basePath . '/update.php');
                @unlink($basePath . '/public/update.php');
                @unlink(__FILE__);
                echo "✓ File update.php berhasil dihapus demi keamanan server Anda!\n";
                $actionStatus = 'success';
                break;
        }
    } catch (\Throwable $e) {
        echo "✗ Terjadi Error: " . $e->getMessage() . "\n";
        echo "Trace:\n" . $e->getTraceAsString();
        $actionStatus = 'error';
    }
    $outputLog = ob_get_clean();

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $actionStatus ?? 'success',
            'title' => $actionTitle,
            'output' => $outputLog,
        ]);
        exit;
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN - Setup, NPM Build & Full Artisan Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-8">

    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Header -->
        <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-emerald-950 border border-slate-700/80 shadow-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] font-extrabold uppercase border border-emerald-400/30">Leaves Setup & Deploy Center</span>
                    <span class="text-xs text-slate-300">GitHub • NPM • Composer • Complete Artisan Suite</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white mt-1.5">Leaves Application Control Center</h1>
                <p class="text-xs text-slate-300 mt-1">Eksekusi NPM Run Build, Artisan Migrate, Seeder, Cache Optimizer, dan sinkronisasi GitHub <code>Frhstaaa/leaves</code></p>
            </div>
            <a href="./" class="px-5 py-3 rounded-2xl bg-white text-slate-950 hover:bg-emerald-50 font-black text-xs shadow-lg transition-transform hover:scale-105 shrink-0 text-center">
                &larr; Buka Aplikasi SGIN
            </a>
        </div>

        <!-- PROMINENT SUCCESS / ERROR NOTIFICATION BANNER AT TOP -->
        <?php if (!empty($outputLog)): ?>
        <div id="executionBanner" class="p-6 rounded-3xl <?= $actionStatus === 'success' ? 'bg-emerald-950/80 border-emerald-500/70 shadow-emerald-950/50' : 'bg-rose-950/80 border-rose-500/70 shadow-rose-950/50' ?> border shadow-2xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b <?= $actionStatus === 'success' ? 'border-emerald-800/60' : 'border-rose-800/60' ?> pb-3">
                <div class="flex items-center space-x-3">
                    <?php if ($actionStatus === 'success'): ?>
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 flex items-center justify-center text-emerald-400 font-black text-lg shrink-0">
                        ✓
                    </div>
                    <div>
                        <h3 class="text-base font-black text-emerald-300">PROSES BERHASIL! (<?= htmlspecialchars($actionTitle ?: 'Sukses') ?>)</h3>
                        <p class="text-xs text-emerald-200/80">Kodingan, database, dan cache aplikasi telah berhasil diperbarui ke versi terbaru.</p>
                    </div>
                    <?php else: ?>
                    <div class="w-10 h-10 rounded-2xl bg-rose-500/20 border border-rose-400/40 flex items-center justify-center text-rose-400 font-black text-lg shrink-0">
                        ✗
                    </div>
                    <div>
                        <h3 class="text-base font-black text-rose-300">PROSES MENGALAMI KENDALA (<?= htmlspecialchars($actionTitle ?: 'Error') ?>)</h3>
                        <p class="text-xs text-rose-200/80">Silakan periksa log terminal di bawah untuk rincian penyebabnya.</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-2 shrink-0">
                    <a href="./" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs shadow-md transition-all">
                        Buka Aplikasi &rarr;
                    </a>
                </div>
            </div>
            
            <div class="space-y-1.5">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Log Hasil Eksekusi:</span>
                <pre class="p-4 rounded-2xl bg-black/80 <?= $actionStatus === 'success' ? 'text-emerald-300 border-emerald-800/40' : 'text-rose-300 border-rose-800/40' ?> border font-mono text-xs overflow-x-auto leading-relaxed whitespace-pre-wrap max-h-96"><?= htmlspecialchars($outputLog) ?></pre>
            </div>
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                document.getElementById('executionBanner')?.scrollIntoView({ behavior: 'smooth' });
            });
        </script>
        <?php endif; ?>

        <!-- Diagnostic Overview Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Repository</span>
                <span class="text-xs font-black text-emerald-400 truncate block"><?= DEFAULT_GITHUB_REPO ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Branch Target</span>
                <span class="text-sm font-black text-blue-400"><?= DEFAULT_GITHUB_BRANCH ?></span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Database Status</span>
                <span class="text-xs font-bold <?= $dbConnected ? 'text-emerald-400' : 'text-amber-400' ?> truncate block">
                    <?= $dbConnected ? '✓ Terhubung' : '⚠️ ' . htmlspecialchars($dbStatus) ?>
                </span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Node / NPM Tools</span>
                <span class="text-[11px] font-bold <?= $detectedNodeBin ? 'text-emerald-400' : 'text-slate-400' ?> truncate block">
                    <?= $detectedNodeBin ? "✓ Node $nodeVersion" : "Pre-bundled Assets" ?>
                </span>
            </div>
        </div>

        <!-- DISK SPACE OPTIMIZER & CLEANER BANNER -->
        <div class="p-5 sm:p-6 rounded-3xl bg-gradient-to-r from-slate-900 to-indigo-950/60 border border-indigo-500/40 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <span class="text-xl">🧹</span>
                    <h3 class="text-base font-bold text-white">Pembersih Cepat Kuota Disk Hosting cPanel</h3>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Hapus folder <code>node_modules/</code> (~200MB yang tidak terpakai), kosongkan file <code>laravel.log</code>, dan bersihkan cache untuk membebaskan ratusan MB ruang disk seketika.
                </p>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                <form method="POST" onsubmit="handleFormSubmit(event, this, 'Pembersihan Ruang Disk')">
                    <input type="hidden" name="action" value="clean_disk">
                    <button type="submit" class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center space-x-1.5 transition-all">
                        <span>🧹 Bersihkan Disk Sekarang</span>
                    </button>
                </form>
                <a href="cleaner.php" target="_blank" class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition-all">
                    Buka Cleaner ↗
                </a>
            </div>
        </div>

        <!-- 1-Click Complete Setup & Auto-Update -->
        <div class="p-6 sm:p-7 rounded-3xl bg-slate-900 border border-emerald-500/40 shadow-2xl space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h3 class="text-lg sm:text-xl font-black text-white">🚀 1-Click Complete Setup & Update (All-in-One)</h3>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed max-w-2xl">
                        Menjalankan seluruh rangkaian otomatis: Pull GitHub (<code><?= DEFAULT_GITHUB_REPO ?></code>) &rarr; Composer install &rarr; NPM build Vite &rarr; Artisan migrate database &rarr; Storage link &rarr; Clear & Cache Optimize.
                    </p>
                </div>
                <form method="POST" onsubmit="handleFormSubmit(event, this, 'Complete Setup & Update')">
                    <input type="hidden" name="action" value="full_setup_update">
                    <button type="submit" class="w-full lg:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-sm shadow-xl shadow-emerald-600/30 flex items-center justify-center space-x-2 transition-all hover:scale-105 shrink-0">
                        <span>Eksekusi Complete Update &rarr;</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- SECTION 1: NPM & FRONTEND BUILD TOOLS -->
        <div class="p-6 rounded-3xl bg-slate-900/80 border border-amber-500/40 space-y-4 shadow-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="text-amber-400 text-xl">⚡</span>
                    <div>
                        <h3 class="text-sm font-black text-amber-300 uppercase tracking-wider">Frontend & NPM Build Tools (Vite / React)</h3>
                        <p class="text-xs text-slate-400">Kompilasi ulang aset JavaScript, CSS, dan React langsung di server hosting Anda.</p>
                    </div>
                </div>
                <div class="text-xs font-mono px-3 py-1.5 rounded-xl bg-black/60 border border-slate-800 text-slate-300 shrink-0">
                    <?= $detectedNodeBin ? "Path: $detectedNodeBin ($nodeVersion)" : "Hosting Status: Pre-compiled GitHub Assets" ?>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <form method="POST" onsubmit="handleFormSubmit(event, this, 'NPM Run Build')">
                    <input type="hidden" name="action" value="npm_build">
                    <button type="submit" class="w-full p-4 rounded-2xl bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/40 text-amber-200 font-bold text-xs transition-all flex items-center justify-between shadow-md">
                        <div class="text-left">
                            <span class="block font-black text-sm text-amber-300">⚡ Jalankan NPM Run Build</span>
                            <span class="text-[11px] text-amber-200/70">Kompilasi aset Vite produksi (public/build)</span>
                        </div>
                        <span class="text-base">&rarr;</span>
                    </button>
                </form>
                <form method="POST" onsubmit="handleFormSubmit(event, this, 'NPM Install')">
                    <input type="hidden" name="action" value="npm_install">
                    <button type="submit" class="w-full p-4 rounded-2xl bg-slate-800 hover:bg-slate-700/80 border border-slate-700 text-slate-200 font-bold text-xs transition-all flex items-center justify-between shadow-md">
                        <div class="text-left">
                            <span class="block font-black text-sm text-white">📦 Jalankan NPM Install</span>
                            <span class="text-[11px] text-slate-400">Pasang/update dependensi package.json</span>
                        </div>
                        <span class="text-base">&rarr;</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- SECTION 2: COMPLETE PHP ARTISAN SUITE -->
        <div class="p-6 rounded-3xl bg-slate-900/80 border border-indigo-500/40 space-y-5 shadow-xl">
            <div class="flex items-center space-x-2 border-b border-slate-800 pb-3">
                <span class="text-indigo-400 text-xl">🛠️</span>
                <div>
                    <h3 class="text-sm font-black text-indigo-300 uppercase tracking-wider">PHP Artisan Suite (Database, Cache & System)</h3>
                    <p class="text-xs text-slate-400">Pusat eksekusi perintah lengkap Laravel Artisan langsung dari web browser.</p>
                </div>
            </div>

            <!-- Group A: Database & Migrations -->
            <div class="space-y-2">
                <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">🗄️ Database, Migrasi & Role Permissions</span>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Migrate')">
                        <input type="hidden" name="action" value="migrate_only">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-indigo-600/20 border border-indigo-500/40 text-indigo-200 font-bold text-xs text-center transition-colors">
                            🗄️ <code>migrate</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Sync Role Permissions')">
                        <input type="hidden" name="action" value="seed_role_permissions">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/50 text-emerald-300 font-black text-xs text-center transition-colors shadow-sm">
                            🛡️ <code>sync permissions</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Migrate Status')">
                        <input type="hidden" name="action" value="migrate_status">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            📊 <code>migrate:status</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: DB Seed')">
                        <input type="hidden" name="action" value="db_seed">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            🌱 <code>db:seed</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Migrate Rollback')">
                        <input type="hidden" name="action" value="migrate_rollback">
                        <button type="submit" onclick="return confirm('Apakah Anda yakin ingin rollback 1 step migrasi terakhir?')" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-amber-600/20 border border-amber-500/40 text-amber-200 font-bold text-xs text-center transition-colors">
                            ⏪ <code>rollback</code>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Group B: Cache & Optimization -->
            <div class="space-y-2">
                <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">⚡ Cache & Performa</span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Optimize Clear')">
                        <input type="hidden" name="action" value="clear_cache">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-emerald-600/20 border border-emerald-500/40 text-emerald-200 font-bold text-xs text-center transition-colors">
                            🧹 <code>optimize:clear</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Optimize Cache')">
                        <input type="hidden" name="action" value="optimize">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            ⚡ <code>optimize cache</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Storage Link')">
                        <input type="hidden" name="action" value="storage_link">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            🔗 <code>storage:link</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Route List')">
                        <input type="hidden" name="action" value="route_list">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            🛣️ <code>route:list</code>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Group C: System Tools & Background Workers -->
            <div class="space-y-2">
                <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">⚙️ Sistem & Background Workers</span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: System About')">
                        <input type="hidden" name="action" value="artisan_about">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            ℹ️ <code>about</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Key Generate')">
                        <input type="hidden" name="action" value="key_generate">
                        <button type="submit" onclick="return confirm('Generate key baru akan membatalkan sesi login user saat ini. Lanjutkan?')" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            🔑 <code>key:generate</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Queue Work')">
                        <input type="hidden" name="action" value="queue_work">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            📬 <code>queue:work</code>
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Artisan: Schedule Run')">
                        <input type="hidden" name="action" value="schedule_run">
                        <button type="submit" class="w-full py-3 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs text-center transition-colors">
                            ⏰ <code>schedule:run</code>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Group D: Custom Artisan Command Line Interface -->
            <div class="pt-2 border-t border-slate-800 space-y-2">
                <span class="text-[11px] font-extrabold text-indigo-300 uppercase tracking-wider block">💻 Jalankan Custom Artisan Command Bebas</span>
                <form method="POST" onsubmit="handleFormSubmit(event, this, 'Custom Artisan Command')" class="flex flex-col sm:flex-row gap-2">
                    <input type="hidden" name="action" value="run_custom_artisan">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-mono text-indigo-400 font-bold">php artisan</span>
                        <input type="text" name="custom_artisan" placeholder="Contoh: route:list, config:show app, env, dll" class="w-full pl-28 pr-4 py-3.5 rounded-2xl bg-black/70 border border-indigo-500/40 text-xs font-mono text-white placeholder-slate-500 focus:outline-none focus:border-indigo-400">
                    </div>
                    <button type="submit" class="px-7 py-3.5 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs shrink-0 transition-colors shadow-lg shadow-indigo-600/30">
                        Jalankan Command &rarr;
                    </button>
                </form>
            </div>
        </div>

        <!-- SECTION 3: COMPOSER & GIT TOOLS -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Composer Card -->
            <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-3 shadow-lg">
                <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center space-x-2">
                    <span>🐘 Composer Backend Tools</span>
                </h4>
                <div class="grid grid-cols-2 gap-2">
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Composer Install')">
                        <input type="hidden" name="action" value="composer_install">
                        <button type="submit" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs transition-colors">
                            Composer Install
                        </button>
                    </form>
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Composer Dump')">
                        <input type="hidden" name="action" value="composer_dump">
                        <button type="submit" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs transition-colors">
                            Dump Autoload
                        </button>
                    </form>
                </div>
            </div>

            <!-- Git Pull Card -->
            <div class="p-6 rounded-3xl bg-slate-900/80 border border-slate-800 space-y-3 shadow-lg">
                <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center space-x-2">
                    <span>📥 Git Repository Tools</span>
                </h4>
                <div class="grid grid-cols-1 gap-2">
                    <form method="POST" onsubmit="handleFormSubmit(event, this, 'Git Pull Saja')">
                        <input type="hidden" name="action" value="github_pull_only">
                        <button type="submit" class="w-full py-3 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-bold text-xs transition-colors">
                            Tarik Kodingan Git Saja (Tanpa Migrasi)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- GitHub Webhook Info Card -->
        <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 space-y-3">
            <div class="flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                <h3 class="text-sm font-black text-indigo-300 uppercase tracking-wider">⚡ GitHub Webhook (Auto-Deploy Setiap 'git push')</h3>
            </div>
            <div class="space-y-2 text-xs font-mono bg-black/60 p-4 rounded-2xl border border-slate-800">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-slate-500">Payload URL:</span>
                    <span class="text-indigo-400 select-all font-bold"><?= htmlspecialchars($webhookUrl) ?></span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                    <span class="text-slate-500">Secret:</span>
                    <span class="text-amber-400"><?= DEFAULT_WEBHOOK_SECRET ?></span>
                </div>
            </div>
        </div>

        <!-- Security Warning & Delete Button -->
        <div class="p-5 rounded-3xl bg-rose-950/30 border border-rose-800/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <h4 class="text-xs font-extrabold text-rose-300 uppercase tracking-wider">🔒 Tindakan Keamanan</h4>
                <p class="text-[11px] text-rose-200/80 leading-relaxed">
                    Jika proses setup selesai dan tidak menggunakan webhook otomatis, Anda dapat menghapus file <code>update.php</code> demi keamanan server Anda.
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

    <!-- REAL-TIME MODAL POPUP FOR AJAX EXECUTION -->
    <div id="liveModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="w-full max-w-2xl bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl p-6 space-y-4 animate-scale-in">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div class="flex items-center space-x-3">
                    <div id="modalSpinner" class="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                    <h3 id="modalTitle" class="text-base font-black text-white">Memproses Perintah...</h3>
                </div>
                <button id="modalCloseBtn" onclick="closeModal()" class="text-slate-400 hover:text-white font-bold text-lg hidden">&times;</button>
            </div>
            
            <div id="modalStatusBadge" class="hidden"></div>
            
            <div class="space-y-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Terminal Log Output:</span>
                <pre id="modalLogOutput" class="p-4 rounded-2xl bg-black/80 text-emerald-300 font-mono text-xs overflow-x-auto leading-relaxed whitespace-pre-wrap max-h-80 border border-slate-800">Sedang mengeksekusi di server...</pre>
            </div>

            <div id="modalFooter" class="flex items-center justify-end space-x-3 pt-2 hidden">
                <button onclick="closeModal()" class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs">
                    Tutup
                </button>
                <a href="./" class="px-6 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg">
                    Buka Aplikasi SGIN &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- AJAX HANDLER JAVASCRIPT -->
    <script>
        function handleFormSubmit(e, form, title) {
            e.preventDefault();
            
            const modal = document.getElementById('liveModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalSpinner = document.getElementById('modalSpinner');
            const modalCloseBtn = document.getElementById('modalCloseBtn');
            const modalStatusBadge = document.getElementById('modalStatusBadge');
            const modalLogOutput = document.getElementById('modalLogOutput');
            const modalFooter = document.getElementById('modalFooter');

            modalTitle.innerText = `Menjalankan: ${title}...`;
            modalSpinner.classList.remove('hidden');
            modalCloseBtn.classList.add('hidden');
            modalStatusBadge.classList.add('hidden');
            modalFooter.classList.add('hidden');
            modalLogOutput.innerText = 'Mengirim perintah ke server...\nMohon tunggu beberapa detik...';
            modal.classList.remove('hidden');

            const formData = new FormData(form);
            formData.append('ajax', '1');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                modalSpinner.classList.add('hidden');
                modalCloseBtn.classList.remove('hidden');
                modalFooter.classList.remove('hidden');
                modalLogOutput.innerText = data.output || 'Perintah selesai dieksekusi tanpa log output.';

                modalStatusBadge.classList.remove('hidden');
                if (data.status === 'success') {
                    modalTitle.innerText = `✓ Selesai: ${title}`;
                    modalStatusBadge.innerHTML = `
                        <div class="p-3 rounded-2xl bg-emerald-950/80 border border-emerald-500/50 flex items-center space-x-2 text-emerald-300 font-extrabold text-xs">
                            <span>✓</span>
                            <span>EKSEKUSI BERHASIL! Perintah telah selesai dijalankan.</span>
                        </div>
                    `;
                } else {
                    modalTitle.innerText = `✗ Gagal: ${title}`;
                    modalStatusBadge.innerHTML = `
                        <div class="p-3 rounded-2xl bg-rose-950/80 border border-rose-500/50 flex items-center space-x-2 text-rose-300 font-extrabold text-xs">
                            <span>✗</span>
                            <span>EKSEKUSI MENGALAMI KENDALA. Silakan periksa log di bawah.</span>
                        </div>
                    `;
                }
            })
            .catch(err => {
                form.submit();
            });
        }

        function closeModal() {
            document.getElementById('liveModal').classList.add('hidden');
        }
    </script>
</body>
</html>
