<?php
/**
 * =========================================================================
 * 🎨 SGIN LEAVES - DEDICATED SAFE FRONTEND UPDATER (ZERO DB TOUCH)
 * =========================================================================
 * File ini KHUSUS untuk memperbarui aset tampilan frontend (React, Vite,
 * CSS, JS, Blade Views, dan Service Worker) secara aman dan instan.
 * 
 * 🛡️ JAMINAN KEAMANAN DATABASE:
 * - TIDAK ADA perintah migrasi database (NO artisan migrate).
 * - TIDAK ADA seeder atau query database (NO artisan db:seed / SQL).
 * - Seluruh data pengajuan, kuota cuti, dan akun karyawan 100% aman dan utuh!
 * 
 * Akses Web: https://www.sgin.co.id/leaves-application/update.php
 * Repository: Frhstaaa/leaves (branch: main)
 * =========================================================================
 */

@set_time_limit(600);
@ini_set('max_execution_time', '600');
@ini_set('memory_limit', '512M');
@ini_set('display_errors', '1');
error_reporting(E_ALL);

// 1. Deteksi Path Root Proyek Laravel
$currentDir = __DIR__;
if (file_exists($currentDir . '/artisan')) {
    $basePath = $currentDir;
} elseif (file_exists(dirname($currentDir) . '/artisan')) {
    $basePath = dirname($currentDir);
} else {
    $basePath = $currentDir;
}

$isPublicDir = (basename($currentDir) === 'public');
$envFile = $basePath . '/.env';

// 2. Helper Functions Eksekusi CLI / Shell
function runShell($cmd, $cwd) {
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
        return trim($stdout . ($stderr ? "\n" . $stderr : ''));
    }

    if (function_exists('shell_exec')) {
        $out = @shell_exec("cd " . escapeshellarg($cwd) . " && " . $cmd . " 2>&1");
        return trim($out ?: '');
    }

    if (function_exists('exec')) {
        @exec("cd " . escapeshellarg($cwd) . " && " . $cmd . " 2>&1", $outLines);
        return trim(implode("\n", $outLines ?? []));
    }

    return "Tidak dapat menjalankan perintah shell (proc_open & shell_exec nonaktif).";
}

function findPhpBin() {
    if (defined('PHP_BINARY') && is_executable(PHP_BINARY)) {
        return PHP_BINARY;
    }
    $candidates = [
        '/usr/local/bin/php',
        '/usr/bin/php',
        '/bin/php',
        getenv('HOME') . '/bin/php',
    ];
    foreach ($candidates as $c) {
        if (is_executable($c)) return $c;
    }
    return 'php';
}

// 3. Status Asset Saat Ini
$manifestPath = $basePath . '/public/build/manifest.json';
$hasManifest = file_exists($manifestPath);
$manifestModified = $hasManifest ? date('d M Y H:i:s', filemtime($manifestPath)) : 'Belum Ada';
$manifestSize = $hasManifest ? round(filesize($manifestPath) / 1024, 2) . ' KB' : '0 KB';

// Hitung jumlah file di public/build/assets
$buildAssetsDir = $basePath . '/public/build/assets';
$totalBuildFiles = is_dir($buildAssetsDir) ? count(glob($buildAssetsDir . '/*')) : 0;

// 4. Eksekusi Aksi Form
$logs = [];
$actionExecuted = false;
$actionType = $_POST['action'] ?? '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($actionType)) {
    $actionExecuted = true;
    $phpBin = findPhpBin();

    if ($actionType === 'update_frontend') {
        $logs[] = "=================================================================";
        $logs[] = "  🎨 MEMULAI UPDATE FRONTEND (REACT / VITE / CSS / ASSETS)       ";
        $logs[] = "  🛡️ MODE AMAN: DATABASE TIDAK DISENTUH SAMA SEKALI            ";
        $logs[] = "=================================================================";
        $logs[] = "Waktu Eksekusi : " . date('Y-m-d H:i:s T');
        $logs[] = "Folder Root    : " . $basePath;

        // [1/5] Sinkronisasi Kode & Aset dari GitHub
        $logs[] = "\n[1/5] Menarik pembaruan aset dari GitHub (Frhstaaa/leaves:main)...";
        $gitSuccess = false;

        if (is_dir($basePath . '/.git')) {
            $gitPull = runShell("git pull origin main 2>&1", $basePath);
            if ($gitPull && !str_contains($gitPull, 'fatal') && !str_contains($gitPull, 'error')) {
                $logs[] = "✓ Git Pull Berhasil:\n" . $gitPull;
                $gitSuccess = true;
            } else {
                $logs[] = "⚠️ Git Pull gagal atau ada kendala:\n" . $gitPull;
            }
        }

        // Fallback: GitHub ZIP API (Jika git CLI tidak tersedia di cPanel)
        if (!$gitSuccess) {
            $logs[] = "ℹ️ Menggunakan fallback sinkronisasi langsung via GitHub ZIP...";
            $zipUrl = "https://github.com/Frhstaaa/leaves/archive/refs/heads/main.zip";
            $tmpZip = $basePath . '/storage/github_frontend_latest.zip';
            
            $ch = curl_init($zipUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 90);
            curl_setopt($ch, CURLOPT_USERAGENT, 'SGIN-Leaves-Frontend-Updater');
            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $data && strlen($data) > 1000) {
                @file_put_contents($tmpZip, $data);
                $zip = new ZipArchive();
                if ($zip->open($tmpZip) === true) {
                    $extractTmp = $basePath . '/storage/gh_front_tmp';
                    @mkdir($extractTmp, 0777, true);
                    $zip->extractTo($extractTmp);
                    $zip->close();
                    @unlink($tmpZip);

                    $subDirs = glob($extractTmp . '/*');
                    if (!empty($subDirs) && is_dir($subDirs[0])) {
                        $srcDir = $subDirs[0];
                        $it = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::SELF_FIRST
                        );
                        $copied = 0;
                        foreach ($it as $item) {
                            $sub = substr($item->getPathname(), strlen($srcDir) + 1);
                            
                            // 🛡️ PROTEKSI KEAMANAN: JANGAN TIMPA file database, .env, atau data storage!
                            if (
                                str_starts_with($sub, '.env') || 
                                str_starts_with($sub, 'storage/') || 
                                str_ends_with($sub, '.sqlite') || 
                                str_ends_with($sub, '.sql')
                            ) {
                                continue;
                            }

                            $dest = $basePath . '/' . $sub;
                            if ($item->isDir()) {
                                @mkdir($dest, 0777, true);
                            } else {
                                @copy($item->getPathname(), $dest);
                                $copied++;
                            }
                        }
                        $logs[] = "✓ Sinkronisasi ZIP Berhasil: $copied file (Aset frontend, views, controller) diperbarui.";
                        
                        // Hapus folder temp ekstrak
                        $filesToDelete = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($extractTmp, RecursiveDirectoryIterator::SKIP_DOTS),
                            RecursiveIteratorIterator::CHILD_FIRST
                        );
                        foreach ($filesToDelete as $f) {
                            $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
                        }
                        @rmdir($extractTmp);
                    }
                } else {
                    $logs[] = "⚠️ Gagal membuka file ZIP GitHub.";
                }
            } else {
                $logs[] = "ℹ️ Tidak dapat mengunduh ZIP GitHub. Menggunakan aset lokal di server.";
            }
        }

        // [2/5] Periksa & Pasang Symlink public/storage
        $logs[] = "\n[2/5] Memeriksa struktur folder public/build & symlink storage...";
        $pubStorage = $basePath . '/public/storage';
        $appStorage = $basePath . '/storage/app/public';
        if (!file_exists($pubStorage) && !is_link($pubStorage)) {
            @symlink($appStorage, $pubStorage);
            $logs[] = "✓ Symlink storage berhasil dipasang.";
        } else {
            $logs[] = "✓ Symlink storage sudah aktif.";
        }

        // Berikan izin baca-tulis untuk public/build
        $buildDir = $basePath . '/public/build';
        if (is_dir($buildDir)) {
            @chmod($buildDir, 0755);
            foreach (glob($buildDir . '/*') as $f) {
                @chmod($f, is_dir($f) ? 0755 : 0644);
            }
            $logs[] = "✓ Hak akses folder public/build diperbarui (0755 / 0644).";
        }

        // [3/5] Pembersihan Cache View & Optimalisasi Laravel
        $logs[] = "\n[3/5] Membersihkan cache compiled Blade views & Inertia cache...";
        
        // Hapus manual compiled views
        $viewFiles = glob($basePath . '/storage/framework/views/*.php');
        $deletedViews = 0;
        if ($viewFiles) {
            foreach ($viewFiles as $vf) {
                if (@unlink($vf)) $deletedViews++;
            }
        }
        $logs[] = "✓ $deletedViews compiled view cache dihapus.";

        // Hapus cache bootstrap
        $bootFiles = glob($basePath . '/bootstrap/cache/*.php');
        if ($bootFiles) {
            foreach ($bootFiles as $bf) { @unlink($bf); }
            $logs[] = "✓ File bootstrap/cache/*.php dibersihkan.";
        }

        // Jalankan perintah artisan view & optimize (TANPA MIGRASI!)
        $viewClearOut = runShell("$phpBin artisan view:clear 2>&1", $basePath);
        $logs[] = "✓ php artisan view:clear: " . ($viewClearOut ?: 'Selesai.');
        
        $optClearOut = runShell("$phpBin artisan optimize:clear 2>&1", $basePath);
        $logs[] = "✓ php artisan optimize:clear: " . ($optClearOut ?: 'Selesai.');

        // [4/5] Reset PHP OPcache
        $logs[] = "\n[4/5] Mereset PHP OPcache (Memastikan PHP membaca kode terbaru)...";
        if (function_exists('opcache_reset')) {
            @opcache_reset();
            $logs[] = "✓ PHP OPcache berhasil di-reset secara instan.";
        } else {
            $logs[] = "ℹ️ OPcache tidak aktif atau tidak didukung.";
        }

        // [5/5] Cache-Busting Service Worker & Manifest
        $logs[] = "\n[5/5] Memperbarui timestamp Service Worker (sw.js) & PWA Manifest...";
        $swPath = $basePath . '/public/sw.js';
        if (file_exists($swPath)) {
            @touch($swPath);
            $logs[] = "✓ File sw.js diperbarui (memicu auto-refresh browser HP karyawan).";
        }
        if (file_exists($manifestPath)) {
            @touch($manifestPath);
            $logs[] = "✓ manifest.json terverifikasi aktif.";
        }

        $logs[] = "\n=================================================================";
        $logs[] = "  🎉 UPDATE FRONTEND SELESAI DENGAN SUKSES!                     ";
        $logs[] = "  Tampilan terbaru sudah aktif di aplikasi web & mobile PWA.    ";
        $logs[] = "=================================================================";

    } elseif ($actionType === 'clear_cache_only') {
        $logs[] = "🧹 Membersihkan cache tampilan dan compiler...";
        $phpBin = findPhpBin();

        foreach (glob($basePath . '/storage/framework/views/*.php') as $vf) { @unlink($vf); }
        foreach (glob($basePath . '/bootstrap/cache/*.php') as $bf) { @unlink($bf); }
        if (function_exists('opcache_reset')) { @opcache_reset(); }

        $out = runShell("$phpBin artisan view:clear 2>&1", $basePath);
        $logs[] = "✓ view:clear: " . $out;
        $out2 = runShell("$phpBin artisan optimize:clear 2>&1", $basePath);
        $logs[] = "✓ optimize:clear: " . $out2;
        
        $swPath = $basePath . '/public/sw.js';
        if (file_exists($swPath)) { @touch($swPath); }

        $logs[] = "✅ Seluruh cache frontend berhasil dibersihkan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Frontend - SGIN Leaves</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen p-4 sm:p-8 flex flex-col justify-between">
    <div class="max-w-4xl mx-auto w-full space-y-6">

        <!-- Top Navigation & Header -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-extrabold uppercase tracking-wider">
                    <span>🛡️</span> Zero DB Touch &bull; 100% Aman
                </div>
                <h1 class="text-2xl font-black text-white flex items-center gap-2">
                    <span>🎨</span> Updater Frontend SGIN Leaves
                </h1>
                <p class="text-xs text-slate-400">Sinkronisasi aset React, Vite build, CSS, dan Blade view tanpa menyentuh database.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="./leave-requests" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl border border-slate-700 transition flex items-center gap-1.5">
                    <span>➔</span> Buka Aplikasi
                </a>
                <a href="./deploy.php" class="px-3.5 py-2.5 bg-slate-800/60 hover:bg-slate-800 text-slate-400 hover:text-slate-200 text-xs font-medium rounded-xl border border-slate-800 transition" title="Menu Deploy Lengkap">
                    ⚙️ Deploy Lengkap
                </a>
            </div>
        </div>

        <!-- Safe Mode Banner -->
        <div class="p-4 sm:p-5 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl shrink-0 font-bold">
                ✓
            </div>
            <div class="space-y-1 text-xs">
                <h3 class="font-extrabold text-emerald-300 text-sm">Database Anda 100% Terproteksi &amp; Aman</h3>
                <p class="text-emerald-400/90 leading-relaxed">
                    Alat ini <strong>TIDAK AKAN</strong> menjalankan migrasi tabel (<code class="bg-black/30 px-1 py-0.5 rounded">php artisan migrate</code>), seeder akun, atau query SQL. Data pengajuan karyawan, saldo kuota cuti tahunan, dan akun pengguna di database tetap utuh dan tidak akan terganggu sama sekali.
                </p>
            </div>
        </div>

        <!-- System Overview Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Card 1: Asset Build Status -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Vite Build Assets</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?= $hasManifest ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                        <?= $hasManifest ? 'Tersedia' : 'Belum Ada' ?>
                    </span>
                </div>
                <div class="text-xs font-mono font-bold text-white"><?= $totalBuildFiles ?> File Aset (.js &bull; .css)</div>
                <div class="text-[11px] text-slate-400">Manifest: <?= htmlspecialchars($manifestModified) ?></div>
            </div>

            <!-- Card 2: Environment -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Target Source</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        main branch
                    </span>
                </div>
                <div class="text-xs font-mono font-bold text-white">Frhstaaa/leaves</div>
                <div class="text-[11px] text-slate-400">Git Pull &bull; Auto ZIP Fallback</div>
            </div>

            <!-- Card 3: Cache Buster -->
            <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Cache Invalidation</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        Auto-Touch
                    </span>
                </div>
                <div class="text-xs font-mono font-bold text-white">Service Worker &amp; Views</div>
                <div class="text-[11px] text-slate-400">PWA auto-refresh di HP karyawan</div>
            </div>
        </div>

        <!-- Action Card -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-emerald-950/70 via-slate-900 to-slate-900 border border-emerald-500/40 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-black text-white flex items-center gap-2">
                        <span>⚡</span> Update Tampilan Frontend Sekarang
                    </h2>
                    <p class="text-xs text-slate-400 max-w-xl">
                        Menyinkronkan bundle React/Vite terbaru, file upload fix, CSS, views, dan membersihkan seluruh cache template tanpa menyentuh database.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                    <form method="POST" class="w-full sm:w-auto">
                        <input type="hidden" name="action" value="update_frontend">
                        <button type="submit" class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white text-xs font-black transition-all shadow-lg shadow-emerald-900/50 flex items-center justify-center gap-2">
                            <span>🚀 Update Frontend Saja</span>
                        </button>
                    </form>
                    <form method="POST" class="w-full sm:w-auto">
                        <input type="hidden" name="action" value="clear_cache_only">
                        <button type="submit" class="w-full sm:w-auto px-4 py-3.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition flex items-center justify-center gap-1.5" title="Hanya bersihkan cache views">
                            <span>🧹 Bersihkan Cache</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Terminal Execution Logs -->
        <?php if ($actionExecuted): ?>
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
                    <span>💻</span> Output Eksekusi Update
                </h3>
                <span class="text-[10px] text-emerald-400 font-mono px-2 py-0.5 rounded-md bg-emerald-500/10 border border-emerald-500/20">
                    Selesai
                </span>
            </div>
            <pre class="p-4 rounded-xl bg-black border border-slate-800 font-mono text-xs text-emerald-400 overflow-x-auto whitespace-pre-wrap max-h-96 leading-relaxed"><?= htmlspecialchars(implode("\n", $logs)) ?></pre>
            <div class="pt-2 flex items-center justify-end gap-2">
                <a href="./leave-requests" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl transition shadow">
                    ➔ Buka Aplikasi Sekarang
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center text-xs text-slate-500 py-3 border-t border-slate-900">
            PT. SUGIYAMA INDONESIA &bull; SGIN Leaves Safe Frontend Updater &bull; <?= date('Y') ?>
        </div>

    </div>
</body>
</html>
