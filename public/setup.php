<?php
/**
 * ============================================================================
 * SGIN Leaves Application - Master Cloud Setup, GitHub Deployer & Server Suite
 * PT Sugiyama Indonesia (Leaves, Attendance & E-Slip Gaji System)
 * ============================================================================
 * 
 * Terhubung langsung ke GitHub (Frhstaaa/leaves:main):
 * 1. 🔄 GitHub Auto-Pull & Direct ZIP Sync (Bypass Git CLI)
 * 2. ⚡ Artisan Suite: migrate, optimize:clear, seed, storage:link, custom artisan
 * 3. 📦 NPM & Node.js Suite: Auto-detect Node/NPM, npm install, npm run build
 * 4. 🎼 Composer Suite: dump-autoload, composer install
 * 5. 💻 Interactive Web Console: Eksekusi perintah shell & artisan langsung
 * 6. 🛠️ Self-Healing: Auto .env, APP_KEY, permissions 0777, symlink storage
 * 
 * Akses: https://www.sgin.co.id/leaves-application/setup.php
 * ============================================================================
 */

@set_time_limit(900);
@ini_set('max_execution_time', 900);
@ini_set('memory_limit', '512M');
@ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('GITHUB_REPO')) define('GITHUB_REPO', 'Frhstaaa/leaves');
if (!defined('GITHUB_BRANCH')) define('GITHUB_BRANCH', 'main');
if (!defined('WEBHOOK_SECRET')) define('WEBHOOK_SECRET', 'sgin-secret-webhook-key');

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
        return "shell_exec() tidak aktif di server ini.";
    }
    $full = "cd " . escapeshellarg($dir) . " && " . $cmd . " 2>&1";
    return trim(@shell_exec($full) ?: '');
}

// ----------------------------------------------------------------------------
// 3. Node.js & NPM Auto-Detector across cPanel / Cloud Hosting
// ----------------------------------------------------------------------------
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
        return [null, null, null];
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
        $versions = glob("$home/.nvm/versions/node/*", GLOB_ONLYDIR);
        if (!empty($versions)) {
            $latest = end($versions);
            if (file_exists("$latest/bin/node")) {
                $foundNode = "$latest/bin/node";
                $nodeDir = "$latest/bin";
            }
        }
    }

    if ($foundNode && $nodeDir) {
        $npmCandidate = $nodeDir . '/npm';
        if (file_exists($npmCandidate)) {
            $foundNpm = $npmCandidate;
        } else {
            $npmVer = @shell_exec("npm -v 2>/dev/null");
            if ($npmVer && preg_match('/^\d+\./', trim($npmVer))) {
                $foundNpm = 'npm';
            }
        }
    }

    return [$foundNode, $foundNpm, $nodeDir];
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

ensureEnvFile($basePath);

// ----------------------------------------------------------------------------
// 5. Direct File Cache Cleaning
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
// 6. GitHub Direct ZIP Sync (Bypasses Git CLI)
// ----------------------------------------------------------------------------
function syncFromGitHubZip($repo, $branch, $basePath) {
    $zipUrl = "https://github.com/$repo/archive/refs/heads/$branch.zip";
    
    if (!function_exists('curl_init')) {
        return ['success' => false, 'msg' => 'cURL tidak tersedia pada server PHP.'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SGIN-Master-Setup/3.0');
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

    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $subPath = str_replace('\\', '/', substr($item->getPathname(), strlen($sourceDir) + 1));
        $destPath = $basePath . '/' . $subPath;

        $skip = false;
        foreach ($ignoreList as $ig) {
            if (str_starts_with($subPath, rtrim($ig, '/'))) {
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

    // Clean up temp folder
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
// 7. Database Connection Tester (Safe with try-catch & MYSQLI_REPORT_OFF)
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
    ];

    if (!$d || !$u) {
        $results['message'] = "Kredensial database tidak lengkap di file .env.";
        return $results;
    }

    $errMsg = '';

    // 1. Try current host
    try {
        $mysqli = @new mysqli($h, $u, $pass, $d, (int)$p);
        if ($mysqli && !$mysqli->connect_errno) {
            $results['connected'] = true;
            $res = $mysqli->query("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = '$d'");
            $results['tables_count'] = $res ? ($res->fetch_assoc()['c'] ?? 0) : 0;
            $results['message'] = "Terhubung ($d - {$results['tables_count']} tabel aktif).";
            $mysqli->close();
            return $results;
        }
        $errMsg = $mysqli ? $mysqli->connect_error : 'Gagal terhubung';
    } catch (\Throwable $e) {
        $errMsg = $e->getMessage();
    }

    // 2. Try alternative host (localhost vs 127.0.0.1)
    $altHost = ($h === '127.0.0.1') ? 'localhost' : '127.0.0.1';
    try {
        $mysqliAlt = @new mysqli($altHost, $u, $pass, $d, (int)$p);
        if ($mysqliAlt && !$mysqliAlt->connect_errno) {
            $resAlt = $mysqliAlt->query("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema = '$d'");
            $altTables = $resAlt ? ($resAlt->fetch_assoc()['c'] ?? 0) : 0;
            $mysqliAlt->close();
            
            $newEnv = preg_replace('/DB_HOST=.*$/m', "DB_HOST={$altHost}", $envContent);
            @file_put_contents($basePath . '/.env', $newEnv);

            $results['connected'] = true;
            $results['active_host'] = $altHost;
            $results['tables_count'] = $altTables;
            $results['message'] = "Terhubung otomatis via '$altHost' ($d - $altTables tabel).";
            return $results;
        }
    } catch (\Throwable $e) {}

    $results['message'] = "Akses ditolak: $errMsg. Pastikan user '$u' terhubung ke '$d' dengan 'ALL PRIVILEGES' di cPanel.";
    return $results;
}

// ----------------------------------------------------------------------------
// 8. Bootstrap Laravel Kernel Helper
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
// 9. Handle Actions
// ----------------------------------------------------------------------------
$action = $_POST['action'] ?? (!empty($_GET['run']) ? ($_GET['run'] === '1' ? 'auto_repair' : $_GET['run']) : null);
$customCmd = $_POST['custom_cmd'] ?? null;
$logs = [];
$statusType = 'info';

list($nodeBinary, $npmBinary, $nodeDir) = findNodeAndNpm();

if ($action === 'auto_repair' || $action === 'full_setup') {
    $logs[] = "=================================================================";
    $logs[] = "  🚀 MENJALANKAN SETUP & PEMULIHAN TOTAL SISTEM SGIN LEAVES...   ";
    $logs[] = "=================================================================\n";

    // Step 1: Ensure .env
    $logs[] = "[1/7] Memeriksa integritas file .env & APP_KEY...";
    $envMsg = ensureEnvFile($basePath);
    $logs[] = "✓ Status .env: " . $envMsg;

    // Step 2: Sync Code from GitHub
    $logs[] = "\n[2/7] Mengambil kodingan terbaru dari GitHub (" . GITHUB_REPO . ":" . GITHUB_BRANCH . ")...";
    $gitVer = runShell("git --version", $basePath);
    $synced = false;

    if (str_contains(strtolower($gitVer), 'git version')) {
        $fetch = runShell("git fetch origin " . GITHUB_BRANCH, $basePath);
        $reset = runShell("git reset --hard origin/" . GITHUB_BRANCH, $basePath);
        $logs[] = "✓ Git Sync: " . ($reset ?: $fetch ?: 'Selesai');
        $synced = true;
    }

    if (!$synced) {
        $zipRes = syncFromGitHubZip(GITHUB_REPO, GITHUB_BRANCH, $basePath);
        if ($zipRes['success']) {
            $logs[] = "✓ Direct ZIP Sync: Berhasil mengunduh & memperbarui {$zipRes['count']} file dari GitHub.";
        } else {
            $logs[] = "⚠️ ZIP Sync: " . $zipRes['msg'];
        }
    }

    // Step 3: Clear All File Caches
    $logs[] = "\n[3/7] Membersihkan seluruh file cache & template Blade...";
    $cleared = directFileCacheClear($basePath);
    $logs[] = "✓ Cache dibersihkan: " . count($cleared) . " file.";

    // Step 4: Storage Permissions & Symlink
    $logs[] = "\n[4/7] Memeriksa hak akses folder storage & symlink publik...";
    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
    }
    $pubStorage = $basePath . '/public/storage';
    $appStorage = $basePath . '/storage/app/public';
    if (!file_exists($pubStorage) && !is_link($pubStorage)) {
        @symlink($appStorage, $pubStorage);
    }
    $logs[] = "✓ Izin folder (0777) & Public Storage Symlink: Siap!";

    // Step 5: Test Database & Run Safe Migrations
    $logs[] = "\n[5/7] Memeriksa koneksi database MySQL...";
    $dbTest = testDatabaseConnection($basePath);
    $logs[] = ($dbTest['connected'] ? "✓ " : "⚠️ ") . $dbTest['message'];

    $app = getLaravelApp($basePath);
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
                    $logs[] = "✓ Role & Permission Sync: Selesai!";
                }
            } catch (\Throwable $e) {}

            try {
                if (class_exists('\\App\\Models\\LeaveQuota')) {
                    \App\Models\LeaveQuota::syncAllUsers();
                    $logs[] = "✓ Kuota Cuti Karyawan: Terverifikasi!";
                }
            } catch (\Throwable $e) {}
        }
    }

    // Step 6: NPM Build (if Node & NPM available on server)
    $logs[] = "\n[6/7] Memeriksa lingkungan Node.js & NPM...";
    if ($nodeBinary && $npmBinary) {
        $logs[] = "✓ Ditemukan Node: $nodeBinary | NPM: $npmBinary";
        $envPrefix = "PATH=" . escapeshellarg($nodeDir) . ':$PATH ';
        $buildOut = runShell($envPrefix . escapeshellcmd($npmBinary) . " run build", $basePath);
        $logs[] = "✓ NPM Run Build: " . ($buildOut ?: 'Selesai');
    } else {
        $logs[] = "ℹ️ Node/NPM server tidak aktif, aset pre-built di public/build aktif digunakan.";
    }

    // Step 7: Service Worker
    $logs[] = "\n[7/7] Memperbarui Service Worker & timestamp PWA...";
    $swFile = $basePath . '/public/sw.js';
    if (file_exists($swFile)) @touch($swFile);
    $logs[] = "✓ PWA & Service Worker siap.";

    $logs[] = "\n=================================================================";
    $logs[] = "  🎉 SETUP SELESAI! APLIKASI TELAH TERHUBUNG & SIAP DIGUNAKAN     ";
    $logs[] = "=================================================================";
    $statusType = 'success';

} elseif ($action === 'run_artisan') {
    $cmd = trim($_POST['artisan_cmd'] ?? 'optimize:clear');
    $logs[] = "Menjalankan: php artisan $cmd ...";

    $app = getLaravelApp($basePath);
    if ($app) {
        try {
            $params = [];
            $parts = explode(' ', $cmd);
            $commandName = array_shift($parts);
            
            foreach ($parts as $part) {
                if (str_starts_with($part, '--')) {
                    $kv = explode('=', substr($part, 2), 2);
                    $params['--' . $kv[0]] = $kv[1] ?? true;
                }
            }

            \Illuminate\Support\Facades\Artisan::call($commandName, $params);
            $output = \Illuminate\Support\Facades\Artisan::output();
            $logs[] = $output ?: "✓ Perintah selesai dijalankan tanpa output.";
            $statusType = 'success';
        } catch (\Throwable $e) {
            $logs[] = "❌ Error Artisan: " . $e->getMessage();
            $statusType = 'error';
        }
    } else {
        // Fallback to shell execution
        $out = runShell("php artisan " . escapeshellcmd($cmd), $basePath);
        $logs[] = $out ?: "✓ Perintah selesai via shell.";
        $statusType = 'success';
    }

} elseif ($action === 'run_npm') {
    if ($nodeBinary && $npmBinary) {
        $logs[] = "Menjalankan: npm run build ...";
        $envPrefix = "PATH=" . escapeshellarg($nodeDir) . ':$PATH ';
        $out = runShell($envPrefix . escapeshellcmd($npmBinary) . " run build", $basePath);
        $logs[] = $out ?: "✓ Build frontend berhasil!";
        directFileCacheClear($basePath);
        $statusType = 'success';
    } else {
        $logs[] = "❌ Node.js / NPM tidak terdeteksi pada server cPanel ini.";
        $statusType = 'error';
    }

} elseif ($action === 'run_git_pull') {
    $logs[] = "Menjalankan sinkronisasi GitHub (" . GITHUB_REPO . ":" . GITHUB_BRANCH . ") ...";
    $gitVer = runShell("git --version", $basePath);
    if (str_contains(strtolower($gitVer), 'git version')) {
        $fetch = runShell("git fetch origin " . GITHUB_BRANCH, $basePath);
        $reset = runShell("git reset --hard origin/" . GITHUB_BRANCH, $basePath);
        $logs[] = "✓ Git Pull: " . ($reset ?: $fetch ?: 'Selesai');
    } else {
        $zipRes = syncFromGitHubZip(GITHUB_REPO, GITHUB_BRANCH, $basePath);
        $logs[] = $zipRes['success'] ? "✓ Direct ZIP Sync: Berhasil mengunduh {$zipRes['count']} file dari GitHub." : "❌ " . $zipRes['msg'];
    }
    directFileCacheClear($basePath);
    $statusType = 'success';

} elseif ($action === 'run_custom' && !empty($customCmd)) {
    $logs[] = "$ " . htmlspecialchars($customCmd);
    $out = runShell($customCmd, $basePath);
    $logs[] = $out ?: "✓ Perintah selesai dijalankan tanpa output.";
    $statusType = 'info';
}

$dbDiag = testDatabaseConnection($basePath);

// Manifest status
$manifestPath = $basePath . '/public/build/manifest.json';
$manifestInfo = null;
if (file_exists($manifestPath)) {
    $manifestData = json_decode(file_get_contents($manifestPath), true);
    $manifestInfo = [
        'entries' => count($manifestData ?: []),
        'last_modified' => date('d M Y, H:i:s', filemtime($manifestPath)),
    ];
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Leaves - Master Cloud Setup & GitHub Runner</title>
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
        
        <!-- Header Banner -->
        <div class="p-6 sm:p-8 rounded-3xl bg-slate-900 border border-slate-800 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-12 -top-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-12 -bottom-12 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 relative z-10">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-500 to-cyan-400 text-white flex items-center justify-center font-black text-3xl shadow-xl shadow-emerald-950/40">
                        ⚡
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-black text-white">SGIN Leaves Setup & GitHub Runner</h1>
                            <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">GitHub Connected</span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400 mt-0.5">Pusat Eksekusi Artisan, NPM Build, Git Auto-Pull, dan Pemulihan Sistem Terpadu</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <a href="./" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 text-white text-xs sm:text-sm font-bold shadow-lg shadow-emerald-900/40 transition-all">
                        Buka Aplikasi
                    </a>
                    <a href="./update.php" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs sm:text-sm font-semibold border border-slate-700 transition-all">
                        Master Updater
                    </a>
                </div>
            </div>

            <!-- Diagnostics Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-800 font-mono text-xs">
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase font-sans font-semibold">PHP & Node.js</div>
                    <div class="text-slate-200 font-bold mt-0.5 truncate"><?= PHP_VERSION ?> | <?= $nodeBinary ? 'Node Aktif' : 'Node Off' ?></div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase font-sans font-semibold">Database Status</div>
                    <div class="<?= $dbDiag['connected'] ? 'text-emerald-400' : 'text-rose-400' ?> font-bold mt-0.5 truncate" title="<?= htmlspecialchars($dbDiag['message']) ?>">
                        <?= $dbDiag['connected'] ? "✓ Terhubung ({$dbDiag['tables_count']} Tabel)" : "❌ Ditolak" ?>
                    </div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase font-sans font-semibold">GitHub Source</div>
                    <div class="text-slate-200 font-bold mt-0.5 truncate"><?= GITHUB_REPO ?> (<?= GITHUB_BRANCH ?>)</div>
                </div>
                <div class="p-3 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <div class="text-slate-500 text-[10px] uppercase font-sans font-semibold">Vite Build</div>
                    <div class="text-slate-200 font-bold mt-0.5 truncate"><?= $manifestInfo ? $manifestInfo['last_modified'] : 'Belum Ada' ?></div>
                </div>
            </div>
        </div>

        <!-- Notification Output Terminal -->
        <?php if (!empty($logs)): ?>
        <div class="p-6 rounded-3xl bg-slate-900 border <?= $statusType === 'success' ? 'border-emerald-500/40' : ($statusType === 'error' ? 'border-rose-500/40' : 'border-slate-700') ?> shadow-2xl space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-300 font-mono flex items-center gap-2">
                    <span>💻</span> Output Terminal Eksekusi
                </h2>
                <span class="text-xs px-3 py-1 rounded-full font-bold <?= $statusType === 'success' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-300' ?>">
                    <?= $statusType === 'success' ? 'Berhasil' : 'Selesai' ?>
                </span>
            </div>
            <pre class="p-4 rounded-2xl bg-black border border-slate-800 font-mono text-xs text-slate-300 overflow-x-auto whitespace-pre-wrap leading-relaxed max-h-72"><?= htmlspecialchars(implode("\n", $logs)) ?></pre>
        </div>
        <?php endif; ?>

        <!-- Primary Action: 1-Click Total Setup -->
        <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-emerald-950/60 via-slate-900 to-teal-950/60 border border-emerald-500/30 shadow-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-1 max-w-2xl">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-500 text-slate-950">Rekomendasi Utama</span>
                    <h2 class="text-lg sm:text-xl font-black text-white">1-Klik Sinkronisasi Total (GitHub + Artisan + Migrasi)</h2>
                </div>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    Menarik update terbaru dari GitHub, membersihkan semua cache, menjalankan migrasi database non-destruktif, mengatur hak akses folder storage `0777`, dan memperbarui seluruh sistem secara otomatis.
                </p>
            </div>

            <form method="POST" class="shrink-0 w-full md:w-auto">
                <input type="hidden" name="action" value="full_setup">
                <button
                    type="submit"
                    class="w-full md:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-black text-sm sm:text-base shadow-xl shadow-emerald-500/20 transition-all flex items-center justify-center space-x-2.5 transform hover:-translate-y-0.5"
                >
                    <span>🚀 Jalankan Pembaruan Total</span>
                </button>
            </form>
        </div>

        <!-- Quick Control Buttons Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            
            <!-- Quick Action 1: Git Pull -->
            <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between space-y-4">
                <div class="space-y-1.5">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center font-bold text-lg">
                        🔄
                    </div>
                    <h3 class="text-sm font-bold text-white">Tarik dari GitHub</h3>
                    <p class="text-xs text-slate-400">Ambil update kodingan terbaru dari branch <code class="text-teal-400">main</code>.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="run_git_pull">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-teal-400 border border-teal-500/30 text-xs font-bold transition-all">
                        Git Pull / Sync ZIP
                    </button>
                </form>
            </div>

            <!-- Quick Action 2: Artisan Migrate -->
            <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between space-y-4">
                <div class="space-y-1.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-lg">
                        🗄️
                    </div>
                    <h3 class="text-sm font-bold text-white">Migrasi Database</h3>
                    <p class="text-xs text-slate-400">Jalankan <code class="text-emerald-400">migrate --force</code> non-destruktif.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="run_artisan">
                    <input type="hidden" name="artisan_cmd" value="migrate --force">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 text-xs font-bold transition-all">
                        Artisan Migrate
                    </button>
                </form>
            </div>

            <!-- Quick Action 3: Clear Cache -->
            <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between space-y-4">
                <div class="space-y-1.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold text-lg">
                        🧹
                    </div>
                    <h3 class="text-sm font-bold text-white">Optimize & Cache Clear</h3>
                    <p class="text-xs text-slate-400">Jalankan <code class="text-amber-400">optimize:clear</code> & bersihkan Blade cache.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="run_artisan">
                    <input type="hidden" name="artisan_cmd" value="optimize:clear">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 border border-amber-500/30 text-xs font-bold transition-all">
                        Optimize Clear
                    </button>
                </form>
            </div>

            <!-- Quick Action 4: NPM Build -->
            <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col justify-between space-y-4">
                <div class="space-y-1.5">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center font-bold text-lg">
                        ⚡
                    </div>
                    <h3 class="text-sm font-bold text-white">NPM Run Build</h3>
                    <p class="text-xs text-slate-400">Kompilasi React / Vite langsung di server hosting.</p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="run_npm">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-cyan-400 border border-cyan-500/30 text-xs font-bold transition-all">
                        NPM Run Build
                    </button>
                </form>
            </div>

        </div>

        <!-- Custom Command Runner Console -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                        <span>⌨️</span> Konsol Perintah Kustom (Custom Command Runner)
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Jalankan perintah PHP Artisan, Git, Composer, atau Shell kustom di server</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                
                <!-- Artisan Command Box -->
                <form method="POST" class="space-y-2 p-4 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <input type="hidden" name="action" value="run_artisan">
                    <label class="block text-xs font-bold text-slate-300">Jalankan PHP Artisan:</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-2 text-xs font-mono text-slate-500 select-none">artisan</span>
                            <input type="text" name="artisan_cmd" placeholder="route:list / storage:link / db:seed" class="w-full pl-16 pr-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shrink-0">
                            Jalankan
                        </button>
                    </div>
                </form>

                <!-- Shell Command Box -->
                <form method="POST" class="space-y-2 p-4 rounded-2xl bg-slate-950/60 border border-slate-800">
                    <input type="hidden" name="action" value="run_custom">
                    <label class="block text-xs font-bold text-slate-300">Jalankan Shell / Terminal:</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-2 text-xs font-mono text-slate-500 select-none">$</span>
                            <input type="text" name="custom_cmd" placeholder="git status / ls -la / composer -v" class="w-full pl-8 pr-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs font-mono text-white focus:border-teal-500 focus:outline-none">
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-500 text-white text-xs font-bold shrink-0">
                            Eksekusi
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-slate-600 py-4">
            PT Sugiyama Indonesia (SGIN) &bull; Master Setup & Cloud Deployer &bull; <?= date('Y') ?>
        </div>

    </div>

</body>
</html>
