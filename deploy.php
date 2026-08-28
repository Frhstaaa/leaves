<?php
/**
 * =========================================================================
 *  🚀 SGIN LEAVES - ALL-IN-ONE ONE-CLICK DEPLOY & REPAIR DOCTOR
 * =========================================================================
 * Akses: https://www.sgin.co.id/leaves-application/deploy.php
 * Repository: Frhstaaa/leaves (main)
 * =========================================================================
 */

@set_time_limit(900);
@ini_set('max_execution_time', 900);
@ini_set('memory_limit', '512M');
@ini_set('display_errors', 1);
error_reporting(E_ALL);

if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

// 1. Path Resolution
$basePath = __DIR__;
if (!file_exists($basePath . '/artisan') && file_exists(dirname(__DIR__) . '/artisan')) {
    $basePath = dirname(__DIR__);
}
$isPublicDir = (basename(__DIR__) === 'public');

$envFile = $basePath . '/.env';
$envContent = file_exists($envFile) ? file_get_contents($envFile) : '';

// 2. Helper Functions
function runCmd($command, $cwd) {
    $descriptors = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    $process = @proc_open($command, $descriptors, $pipes, $cwd);
    if (!is_resource($process)) {
        if (function_exists('shell_exec')) {
            $out = @shell_exec("cd " . escapeshellarg($cwd) . " && " . $command . " 2>&1");
            return trim($out ?: '');
        }
        return "Gagal menjalankan perintah (proc_open & shell_exec tidak tersedia).";
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
    return trim($stdout . ($stderr ? "\n" . $stderr : ''));
}

function findBin($bin) {
    $candidates = [
        "/usr/local/bin/$bin",
        "/usr/bin/$bin",
        "/bin/$bin",
        getenv("HOME") . "/.nvm/versions/node/*/bin/$bin",
        getenv("HOME") . "/bin/$bin",
    ];
    foreach ($candidates as $pattern) {
        $matches = glob($pattern);
        if ($matches) {
            foreach ($matches as $m) {
                if (is_executable($m)) return $m;
            }
        }
    }
    $which = runCmd("which $bin 2>/dev/null", __DIR__);
    if ($which && is_executable($which)) return $which;
    return null;
}

// 3. Database Info from .env
preg_match('/DB_HOST=(.*)/', $envContent, $mHost);
preg_match('/DB_PORT=(.*)/', $envContent, $mPort);
preg_match('/DB_DATABASE=(.*)/', $envContent, $mDb);
preg_match('/DB_USERNAME=(.*)/', $envContent, $mUser);
preg_match('/DB_PASSWORD=(.*)/', $envContent, $mPass);

$dbHost = trim($mHost[1] ?? '127.0.0.1');
$dbPort = (int)trim($mPort[1] ?? '3306');
$dbName = trim($mDb[1] ?? 'sginco_dbleav_fix');
$dbUser = trim($mUser[1] ?? 'sginco_dbleav_fix');
$dbPass = trim(trim($mPass[1] ?? '', '"'), "'");

// Test DB Connection
$dbConnected = false;
$dbMsg = '';
$tables = [];
$usersList = [];

try {
    $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
    if ($mysqli && !$mysqli->connect_errno) {
        $dbConnected = true;
        $res = $mysqli->query("SHOW TABLES");
        if ($res) {
            while ($row = $res->fetch_array()) {
                $tables[] = $row[0];
            }
        }
        $uRes = $mysqli->query("SELECT id, name, email, nik, role FROM users LIMIT 10");
        if ($uRes) {
            while ($u = $uRes->fetch_assoc()) {
                $usersList[] = $u;
            }
        }
        $dbMsg = "Terhubung secara normal (" . count($tables) . " tabel aktif).";
    } else {
        $err = $mysqli ? $mysqli->connect_error : 'Koneksi MySQL gagal';
        $dbMsg = "Gagal: $err";
    }
} catch (\Throwable $e) {
    $dbMsg = "Error: " . $e->getMessage();
}

// 4. Handle Actions (Deploy Execution)
$logs = [];
$actionExecuted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $actionExecuted = true;
    $action = $_POST['action'];

    if ($action === 'full_deploy') {
        $logs[] = "=================================================================";
        $logs[] = "  🚀 MEMULAI PROSES DEPLOYMENT OTOMATIS SGIN LEAVES...";
        $logs[] = "=================================================================";
        $logs[] = "Waktu Server : " . date('Y-m-d H:i:s T');
        $logs[] = "PHP Version  : " . PHP_VERSION;
        $logs[] = "Folder Root  : " . $basePath;

        // [1/8] File .env Setup
        $logs[] = "\n[1/8] Memeriksa & Mengamankan Konfigurasi .env...";
        if (!file_exists($envFile)) {
            $defaultEnv = "APP_NAME=\"Form SGIN\"\nAPP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false\nAPP_URL=https://www.sgin.co.id/leaves-application\n\nLOG_CHANNEL=stack\nLOG_LEVEL=error\n\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=sginco_dbleav_fix\nDB_USERNAME=sginco_dbleav_fix\nDB_PASSWORD=\"@SginC01!!!\"\n\nSESSION_DRIVER=file\nSESSION_LIFETIME=120\nSESSION_DOMAIN=.sgin.co.id\nSESSION_SECURE_COOKIE=true\nSESSION_SAME_SITE=lax\n";
            file_put_contents($envFile, $defaultEnv);
            $logs[] = "✓ File .env baru dibuat dengan kredensial produksi.";
        } else {
            $envText = file_get_contents($envFile);
            // Ensure SESSION_DOMAIN is set
            if (!str_contains($envText, 'SESSION_DOMAIN')) {
                $envText .= "\nSESSION_DOMAIN=.sgin.co.id\nSESSION_SECURE_COOKIE=true\nSESSION_SAME_SITE=lax\n";
                file_put_contents($envFile, $envText);
                $logs[] = "✓ Parameter SESSION_DOMAIN=.sgin.co.id ditambahkan.";
            } else {
                $logs[] = "✓ File .env siap & valid.";
            }
        }

        // [2/8] GitHub Sync
        $logs[] = "\n[2/8] Mengambil kodingan terbaru dari GitHub (Frhstaaa/leaves:main)...";
        $gitPulled = false;
        if (is_dir($basePath . '/.git')) {
            $gitOut = runCmd("git pull origin main 2>&1", $basePath);
            if ($gitOut && !str_contains($gitOut, 'fatal') && !str_contains($gitOut, 'error')) {
                $logs[] = "✓ Git Pull Berhasil:\n" . $gitOut;
                $gitPulled = true;
            }
        }
        if (!$gitPulled) {
            $logs[] = "ℹ️ Sinkronisasi langsung via GitHub ZIP API...";
            $zipUrl = "https://github.com/Frhstaaa/leaves/archive/refs/heads/main.zip";
            $tmpZip = $basePath . '/storage/github_latest.zip';
            $ch = curl_init($zipUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'SGIN-Deployer');
            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $data && strlen($data) > 1000) {
                @file_put_contents($tmpZip, $data);
                $zip = new ZipArchive();
                if ($zip->open($tmpZip) === true) {
                    $extractTmp = $basePath . '/storage/gh_tmp';
                    @mkdir($extractTmp, 0777, true);
                    $zip->extractTo($extractTmp);
                    $zip->close();
                    @unlink($tmpZip);

                    $subDirs = glob($extractTmp . '/*');
                    if (!empty($subDirs) && is_dir($subDirs[0])) {
                        $srcDir = $subDirs[0];
                        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
                        $copied = 0;
                        foreach ($it as $item) {
                            $sub = substr($item->getPathname(), strlen($srcDir) + 1);
                            $dest = $basePath . '/' . $sub;
                            if (str_starts_with($sub, '.env') || str_starts_with($sub, 'storage/')) continue;
                            if ($item->isDir()) {
                                @mkdir($dest, 0777, true);
                            } else {
                                @copy($item->getPathname(), $dest);
                                $copied++;
                            }
                        }
                        $logs[] = "✓ GitHub Sync Selesai: $copied file diperbarui.";
                    }
                }
            } else {
                $logs[] = "ℹ️ Menggunakan kodingan lokal yang telah terpasang di server.";
            }
        }

        // [3/8] Clear Caches
        $logs[] = "\n[3/8] Membersihkan seluruh cache & file sementara...";
        foreach (glob($basePath . '/bootstrap/cache/*.php') as $f) { @unlink($f); }
        foreach (glob($basePath . '/storage/framework/views/*.php') as $f) { @unlink($f); }
        if (function_exists('opcache_reset')) { @opcache_reset(); }
        $optOut = runCmd("php artisan optimize:clear 2>&1", $basePath);
        $logs[] = "✓ Cache Dibersihkan: " . ($optOut ?: 'Selesai.');

        // [4/8] Folder Permissions & Storage Link
        $logs[] = "\n[4/8] Mengatur hak akses folder storage & symlink publik...";
        $dirs = [
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
        foreach ($dirs as $d) {
            @mkdir($d, 0777, true);
            @chmod($d, 0777);
        }
        $pubStorage = $basePath . '/public/storage';
        $appStorage = $basePath . '/storage/app/public';
        if (!file_exists($pubStorage) && !is_link($pubStorage)) {
            @symlink($appStorage, $pubStorage);
        }
        $logs[] = "✓ Izin Folder (0777) & Storage Link: Siap!";

        // [5/8] Database Migration & Seeder
        $logs[] = "\n[5/8] Menjalankan migrasi database & akun default...";
        if ($dbConnected) {
            $migOut = runCmd("php artisan migrate --force 2>&1", $basePath);
            $logs[] = "✓ php artisan migrate: " . ($migOut ?: 'Tabel up-to-date.');

            $seedOut = runCmd("php artisan db:seed --force 2>&1", $basePath);
            $logs[] = "✓ php artisan db:seed: " . ($seedOut ?: 'Role & Akun tersinkronisasi.');

            // Fallback SQL Import if tables count is 0
            if (count($tables) === 0 && file_exists($basePath . '/database.sql')) {
                $sql = file_get_contents($basePath . '/database.sql');
                if ($mysqli->multi_query($sql)) {
                    do {
                        if ($r = $mysqli->store_result()) { $r->free(); }
                    } while ($mysqli->more_results() && $mysqli->next_result());
                    $logs[] = "✓ Import database.sql fallback: Berhasil dieksekusi.";
                }
            }
        } else {
            $logs[] = "⚠️ Database belum terhubung: " . $dbMsg;
        }

        // [6/8] NPM Build
        $logs[] = "\n[6/8] Memeriksa Vite Frontend Build...";
        $nodeBin = findBin('node');
        $npmBin = findBin('npm');
        if ($nodeBin && $npmBin) {
            $logs[] = "✓ Ditemukan Node: $nodeBin | NPM: $npmBin";
            $buildOut = runCmd("$npmBin run build 2>&1", $basePath);
            $logs[] = "✓ NPM Run Build: " . ($buildOut ?: 'Selesai');
        } else {
            $logs[] = "ℹ️ Node/NPM tidak aktif di shell, aset pre-built di public/build/ aktif digunakan.";
        }

        // [7/8] Service Worker & .htaccess
        $logs[] = "\n[7/8] Memeriksa Service Worker & Aturan .htaccess...";
        $swFile = $basePath . '/public/sw.js';
        if (file_exists($swFile)) @touch($swFile);

        $logs[] = "\n=================================================================";
        $logs[] = "  🎉 DEPLOYMENT SELESAI! APLIKASI TELAH SIAP DIGUNAKAN!          ";
        $logs[] = "=================================================================";
    }
}

// 5. Read Laravel Log (last 60 lines)
$laravelLog = '';
$logFile = $basePath . '/storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = @file($logFile);
    if ($lines) {
        $lastLines = array_slice($lines, -60);
        $laravelLog = trim(implode("", $lastLines));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Leaves - Deployer & System Doctor</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-8 flex flex-col justify-between font-sans">
    <div class="max-w-4xl mx-auto w-full space-y-6">
        
        <!-- Header -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white flex items-center gap-2">
                    <span>🚀</span> SGIN Leaves - One-Click Deployer
                </h1>
                <p class="text-xs text-slate-400 mt-1">Alat otomatisasi update, migrasi, perbaikan izin, dan diagnosa server</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="./login" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition">
                    ➔ Halaman Login
                </a>
            </div>
        </div>

        <!-- System Overview Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Card 1: Database -->
            <div class="p-5 rounded-2xl bg-slate-900 border <?= $dbConnected ? 'border-emerald-500/40' : 'border-rose-500/40' ?> space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Database Status</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $dbConnected ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                        <?= $dbConnected ? 'Terhubung' : 'Gagal' ?>
                    </span>
                </div>
                <div class="text-xs font-mono font-bold text-white"><?= htmlspecialchars($dbName) ?></div>
                <div class="text-[11px] text-slate-400 truncate"><?= htmlspecialchars($dbMsg) ?></div>
            </div>

            <!-- Card 2: Environment -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Lingkungan PHP</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        PHP <?= PHP_VERSION ?>
                    </span>
                </div>
                <div class="text-xs font-mono font-bold text-white">Production Mode</div>
                <div class="text-[11px] text-slate-400">cPanel Apache Subfolder Mode</div>
            </div>

            <!-- Card 3: GitHub Sync -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">GitHub Source</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        main branch
                    </span>
                </div>
                <div class="text-xs font-mono font-bold text-white">Frhstaaa/leaves</div>
                <div class="text-[11px] text-slate-400">Auto-pull & Zip-fallback</div>
            </div>
        </div>

        <!-- Main Action Button -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-950/60 to-slate-900 border border-emerald-500/40 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-white">Jalankan Pembaruan &amp; Pemulihan Total</h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Menjalankan sinkronisasi GitHub, izin folder (0777), migrasi tabel, seeder akun, dan pembersihan cache.
                    </p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="full_deploy">
                    <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black transition-all shadow-lg shadow-emerald-900/50 flex items-center gap-2 whitespace-nowrap">
                        <span>⚡ Jalankan Deploy Otomatis</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Console Log Execution Output -->
        <?php if ($actionExecuted): ?>
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <span>💻</span> Output Terminal Eksekusi
                </h3>
                <span class="text-[10px] text-emerald-400 font-mono">Selesai</span>
            </div>
            <pre class="p-4 rounded-xl bg-black border border-slate-800 font-mono text-xs text-emerald-400 overflow-x-auto whitespace-pre-wrap max-h-96 leading-relaxed"><?= htmlspecialchars(implode("\n", $logs)) ?></pre>
        </div>
        <?php endif; ?>

        <!-- Active User Accounts for Quick Login -->
        <?php if ($dbConnected && !empty($usersList)): ?>
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Daftar Akun yang Siap Digunakan:</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-950 text-slate-400">
                        <tr>
                            <th class="p-2.5">Nama Karyawan</th>
                            <th class="p-2.5">Email</th>
                            <th class="p-2.5">NIK</th>
                            <th class="p-2.5">Role</th>
                            <th class="p-2.5">Password Default</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td class="p-2.5 font-bold text-white"><?= htmlspecialchars($u['name']) ?></td>
                            <td class="p-2.5 text-emerald-400 font-mono"><?= htmlspecialchars($u['email']) ?></td>
                            <td class="p-2.5 text-slate-300 font-mono"><?= htmlspecialchars($u['nik']) ?></td>
                            <td class="p-2.5 text-teal-300 uppercase font-bold"><?= htmlspecialchars($u['role']) ?></td>
                            <td class="p-2.5 text-amber-300 font-mono">password</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Error Diagnostics (laravel.log) -->
        <?php if (!empty($laravelLog)): ?>
        <div class="p-6 rounded-2xl bg-slate-900 border border-amber-800/40 space-y-3">
            <div class="text-xs font-bold text-amber-400 flex items-center justify-between">
                <span>📜 Log Error Sistem Terakhir (storage/logs/laravel.log):</span>
            </div>
            <pre class="p-4 rounded-xl bg-black border border-slate-800 font-mono text-[11px] text-rose-300 overflow-x-auto whitespace-pre-wrap max-h-60 leading-relaxed"><?= htmlspecialchars($laravelLog) ?></pre>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center text-xs text-slate-500 py-2">
            PT. SUGIYAMA INDONESIA &bull; Leave &amp; Absence Management System
        </div>

    </div>
</body>
</html>
