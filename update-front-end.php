<?php
/**
 * SGIN Leaves Application - Dedicated Frontend & UI Asset Updater
 * 
 * KHUSUS UPDATE FRONTEND (React, Inertia, Blade, CSS, JS, PWA Assets, & Cache)
 * KEAMANAN TINGGI: SAMA SEKALI TIDAK MENYENTUH DATABASE / SCHEMA / DATA USER!
 * 
 * Akses: https://www.sgin.co.id/leaves-application/update-front-end.php
 */

@set_time_limit(300);
@ini_set('max_execution_time', 300);
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

// Ensure required directories exist and are writable
$frontendDirs = [
    $basePath . '/public/build',
    $basePath . '/public/build/assets',
    $basePath . '/storage/framework/views',
    $basePath . '/storage/framework/cache/data',
    $basePath . '/bootstrap/cache',
];

foreach ($frontendDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
}

// Direct File Cache Cleaner for Frontend
function clearFrontendFileCaches($basePath) {
    $cleared = [];

    // 1. Clear compiled Blade views
    $viewsDir = $basePath . '/storage/framework/views';
    if (is_dir($viewsDir)) {
        foreach (glob($viewsDir . '/*.php') as $f) {
            if (basename($f) !== '.gitignore') {
                @unlink($f);
                $cleared[] = 'Blade View: ' . basename($f);
            }
        }
    }

    // 2. Clear bootstrap cache files
    $bCacheDir = $basePath . '/bootstrap/cache';
    if (is_dir($bCacheDir)) {
        foreach (glob($bCacheDir . '/*.php') as $f) {
            if (basename($f) !== '.gitignore') {
                @unlink($f);
                $cleared[] = 'Bootstrap Cache: ' . basename($f);
            }
        }
    }

    // 3. Clear data caches
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
            $cleared[] = 'Storage Framework Data Cache';
        } catch (\Throwable $e) {}
    }

    // 4. Reset PHP OPcache
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $cleared[] = 'PHP OPcache Memory Reset';
    }

    return $cleared;
}

// Function to safely extract ONLY Frontend files from a ZIP package
function extractFrontendOnlyFromZip($zipFilePath, $basePath) {
    $extracted = [];
    $skipped = [];
    $errors = [];

    if (!class_exists('ZipArchive')) {
        return ['success' => false, 'error' => 'Ekstensi PHP ZipArchive tidak aktif pada server.', 'extracted' => [], 'skipped' => []];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFilePath) !== TRUE) {
        return ['success' => false, 'error' => 'Gagal membuka file ZIP: ' . basename($zipFilePath), 'extracted' => [], 'skipped' => []];
    }

    // ALLOWED FRONTEND WHITELIST PATHS (Strictly No Database / No .env)
    $allowedPrefixes = [
        'public/build/',
        'public/css/',
        'public/js/',
        'public/icons/',
        'public/images/',
        'resources/js/',
        'resources/css/',
        'resources/views/',
        'public/manifest.webmanifest',
        'public/sw.js',
        'public/favicon.ico',
        'public/robots.txt',
    ];

    // STRICTLY FORBIDDEN / IGNORED FILES
    $blockedPatterns = [
        '.env',
        'database/',
        '.sqlite',
        '.sql',
        '.db',
        'config/database.php',
    ];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);

        // Check if explicitly blocked
        $isBlocked = false;
        foreach ($blockedPatterns as $blocked) {
            if (str_contains(strtolower($entryName), strtolower($blocked))) {
                $isBlocked = true;
                $skipped[] = $entryName . ' [KEAMANAN DATABASE: DILEWATI]';
                break;
            }
        }
        if ($isBlocked) continue;

        // Check if matches allowed frontend whitelist
        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($entryName, $prefix)) {
                $isAllowed = true;
                break;
            }
        }

        if ($isAllowed) {
            $destFile = $basePath . '/' . $entryName;
            
            if (str_ends_with($entryName, '/')) {
                // Directory
                if (!is_dir($destFile)) {
                    @mkdir($destFile, 0777, true);
                }
            } else {
                // File
                $destDir = dirname($destFile);
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0777, true);
                }
                
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    file_put_contents($destFile, $content);
                    @chmod($destFile, 0666);
                    $extracted[] = $entryName;
                }
            }
        } else {
            $skipped[] = $entryName;
        }
    }

    $zip->close();
    return ['success' => true, 'extracted' => $extracted, 'skipped' => $skipped];
}

// Handle Form Actions
$action = $_POST['action'] ?? ($_GET['action'] ?? null);
$logs = [];
$statusType = 'info';

if ($action === 'clear_cache') {
    $cleared = clearFrontendFileCaches($basePath);
    $logs[] = ['type' => 'success', 'msg' => 'Pembersihan cache frontend & view selesai! (' . count($cleared) . ' item dibersihkan)'];
    $statusType = 'success';
} elseif ($action === 'extract_zip') {
    // Look for ZIP file in root or uploaded
    $targetZip = null;
    
    if (isset($_FILES['zip_file']) && $_FILES['zip_file']['error'] === UPLOAD_ERR_OK) {
        $targetZip = $_FILES['zip_file']['tmp_name'];
        $logs[] = ['type' => 'info', 'msg' => 'Memproses file ZIP yang diunggah: ' . htmlspecialchars($_FILES['zip_file']['name'])];
    } else {
        $candidates = [
            $basePath . '/sgin_leaves_production_ready.zip',
            $basePath . '/sgin_leaves_ready_deploy.zip',
        ];
        foreach ($candidates as $c) {
            if (file_exists($c)) {
                $targetZip = $c;
                $logs[] = ['type' => 'info', 'msg' => 'Menggunakan file ZIP paket di server: ' . basename($c)];
                break;
            }
        }
    }

    if ($targetZip) {
        $res = extractFrontendOnlyFromZip($targetZip, $basePath);
        if ($res['success']) {
            $logs[] = ['type' => 'success', 'msg' => 'Ekstraksi aset Frontend berhasil! ' . count($res['extracted']) . ' file UI diperbarui.'];
            $logs[] = ['type' => 'info', 'msg' => count($res['skipped']) . ' file non-frontend/database dilewati dengan aman.'];
            
            // Automatically clear caches after frontend extract
            $cleared = clearFrontendFileCaches($basePath);
            $logs[] = ['type' => 'success', 'msg' => 'Cache Blade dan template berhasil di-refresh otomatis.'];
            $statusType = 'success';
        } else {
            $logs[] = ['type' => 'error', 'msg' => 'Gagal: ' . $res['error']];
            $statusType = 'error';
        }
    } else {
        $logs[] = ['type' => 'warning', 'msg' => 'Tidak ditemukan file ZIP paket frontend di server atau melalui unggahan.'];
        $statusType = 'warning';
    }
} elseif ($action === 'touch_pwa') {
    // Touch service worker & manifest to bump browser caches
    $swFile = $basePath . '/public/sw.js';
    if (file_exists($swFile)) {
        @touch($swFile);
        $logs[] = ['type' => 'success', 'msg' => 'Service Worker timestamp diperbarui (Browser client akan auto-refresh cache).'];
    }
    clearFrontendFileCaches($basePath);
    $logs[] = ['type' => 'success', 'msg' => 'Cache busting selesai!'];
    $statusType = 'success';
}

// Inspect Manifest & Build Assets Status
$manifestPath = $basePath . '/public/build/manifest.json';
$manifestInfo = null;
$assetList = [];
if (file_exists($manifestPath)) {
    $manifestData = json_decode(file_get_contents($manifestPath), true);
    $manifestInfo = [
        'exists' => true,
        'entries' => count($manifestData ?: []),
        'last_modified' => date('d M Y, H:i:s', filemtime($manifestPath)),
        'size' => round(filesize($manifestPath) / 1024, 2) . ' KB',
    ];
}

$assetsDir = $basePath . '/public/build/assets';
if (is_dir($assetsDir)) {
    $files = scandir($assetsDir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $fp = $assetsDir . '/' . $f;
            $assetList[] = [
                'name' => $f,
                'size' => round(filesize($fp) / 1024, 1) . ' KB',
                'time' => date('d M Y H:i', filemtime($fp)),
                'type' => str_ends_with($f, '.css') ? 'CSS' : (str_ends_with($f, '.js') ? 'JS' : 'Asset')
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Leaves - Dedicated Frontend Updater (Database Safe)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 p-4 sm:p-8 flex flex-col justify-between">
    <div class="max-w-4xl mx-auto w-full space-y-6">
        
        <!-- Header & Safety Badge -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl relative overflow-hidden">
            <div class="absolute -right-12 -top-12 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center font-black text-2xl text-white shadow-lg shadow-emerald-500/20">
                        ⚡
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Frontend UI & Asset Updater</h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Frontend Only</span>
                        </div>
                        <p class="text-slate-400 text-sm mt-0.5">Sistem Pembaruan Antarmuka & Cache SGIN Leaves (Aman Tanpa Sentuh Database)</p>
                    </div>
                </div>

                <a href="./" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-medium transition-all duration-200 border border-slate-700">
                    <span>Buka Aplikasi</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>

            <!-- Database Protection Notice -->
            <div class="mt-6 bg-emerald-950/40 border border-emerald-500/30 rounded-2xl p-4 flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 font-bold">
                    🔒
                </div>
                <div class="text-xs sm:text-sm text-emerald-200/90 leading-relaxed">
                    <strong>Jaminan Keamanan Database:</strong> Script ini beroperasi secara terisolasi untuk aset frontend (React, Blade, CSS, JS, Views, & Cache). <strong>Tidak ada perintah database</strong> (seperti <code class="bg-emerald-950 px-1 py-0.5 rounded">migrate</code>, <code class="bg-emerald-950 px-1 py-0.5 rounded">fresh</code>, atau <code class="bg-emerald-950 px-1 py-0.5 rounded">seed</code>) yang dijalankan, sehingga seluruh data user yang telah diinputkan 100% aman dan utuh.
                </div>
            </div>
        </div>

        <!-- Notification / Log Output -->
        <?php if (!empty($logs)): ?>
        <div class="bg-slate-900 border <?= $statusType === 'success' ? 'border-emerald-500/40' : ($statusType === 'error' ? 'border-rose-500/40' : 'border-amber-500/40') ?> rounded-3xl p-6 shadow-xl space-y-3">
            <h2 class="text-sm font-semibold tracking-wider uppercase text-slate-400 flex items-center gap-2">
                <span>📋</span> Hasil Eksekusi Pembaruan:
            </h2>
            <div class="space-y-2 font-mono text-xs sm:text-sm">
                <?php foreach ($logs as $log): ?>
                <div class="p-3 rounded-xl <?= $log['type'] === 'success' ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20' : ($log['type'] === 'error' ? 'bg-rose-500/10 text-rose-300 border border-rose-500/20' : 'bg-slate-800 text-slate-300 border border-slate-700') ?> flex items-start gap-2">
                    <span><?= $log['type'] === 'success' ? '✓' : ($log['type'] === 'error' ? '✗' : 'ℹ') ?></span>
                    <div><?= htmlspecialchars($log['msg']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            
            <!-- Action 1: Extract Frontend from ZIP -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center font-bold text-lg">
                        📦
                    </div>
                    <h3 class="text-base font-bold text-white">Update Aset dari ZIP</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Mengekstrak otomatis hanya folder <code class="text-emerald-400">public/build/</code> dan template views dari paket ZIP tanpa menyentuh file database atau backend.
                    </p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="mt-6 space-y-3">
                    <input type="hidden" name="action" value="extract_zip">
                    
                    <label class="block">
                        <span class="text-xs text-slate-400 font-medium">Opsional Unggah ZIP:</span>
                        <input type="file" name="zip_file" accept=".zip" class="mt-1 block w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700">
                    </label>

                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white text-xs sm:text-sm font-semibold shadow-lg shadow-teal-600/20 transition-all duration-150">
                        ⚡ Ekstrak & Terapkan Frontend
                    </button>
                </form>
            </div>

            <!-- Action 2: Clear Frontend & View Cache -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-lg">
                        🧹
                    </div>
                    <h3 class="text-base font-bold text-white">Bersihkan Cache UI & View</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Menghapus seluruh cache Blade view (<code class="text-emerald-400">storage/framework/views</code>), bootstrap cache, dan reset PHP OPcache agar perubahan desain langsung aktif.
                    </p>
                </div>

                <form method="POST" class="mt-6">
                    <input type="hidden" name="action" value="clear_cache">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/30 text-xs sm:text-sm font-semibold transition-all duration-150">
                        🚀 Purge Cache Tampilan
                    </button>
                </form>
            </div>

            <!-- Action 3: PWA / Service Worker Refresh -->
            <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-xl flex flex-col justify-between hover:border-slate-700 transition-all">
                <div class="space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold text-lg">
                        📱
                    </div>
                    <h3 class="text-base font-bold text-white">Paksa Update PWA & Browser</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Memperbarui timestamp Service Worker & manifest agar aplikasi di HP/komputer user otomatis mendownload tampilan terbaru saat dibuka.
                    </p>
                </div>

                <form method="POST" class="mt-6">
                    <input type="hidden" name="action" value="touch_pwa">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-400 border border-indigo-500/30 text-xs sm:text-sm font-semibold transition-all duration-150">
                        🔄 Refresh PWA Client Cache
                    </button>
                </form>
            </div>

        </div>

        <!-- Manifest & Build Assets Status Table -->
        <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider">Status Kompilasi Frontend (Vite)</h3>
                    <p class="text-xs text-slate-400">File aset yang saat ini aktif di <code class="text-emerald-400">public/build/assets</code></p>
                </div>
                <?php if ($manifestInfo): ?>
                    <span class="text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1 rounded-full font-medium">
                        Manifest Aktif: <?= $manifestInfo['last_modified'] ?>
                    </span>
                <?php else: ?>
                    <span class="text-xs bg-rose-500/10 text-rose-400 border border-rose-500/20 px-3 py-1 rounded-full font-medium">
                        Manifest Belum Ditemukan
                    </span>
                <?php endif; ?>
            </div>

            <div class="overflow-x-auto max-h-56 overflow-y-auto rounded-2xl border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-800/80 text-slate-400 uppercase font-mono tracking-wider sticky top-0">
                        <tr>
                            <th class="p-3">Nama File Aset</th>
                            <th class="p-3">Tipe</th>
                            <th class="p-3">Ukuran</th>
                            <th class="p-3">Waktu Build</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-slate-300">
                        <?php if (!empty($assetList)): ?>
                            <?php foreach ($assetList as $item): ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="p-3 text-white font-medium"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] <?= $item['type'] === 'CSS' ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' : ($item['type'] === 'JS' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-slate-700 text-slate-300') ?>">
                                        <?= $item['type'] ?>
                                    </span>
                                </td>
                                <td class="p-3 text-slate-400"><?= $item['size'] ?></td>
                                <td class="p-3 text-slate-500"><?= $item['time'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="p-4 text-center text-slate-500">Belum ada file aset di folder public/build/assets.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs text-slate-500 py-2">
            SGIN Leaves Management System &bull; Frontend Asset Safe Updater &bull; <?= date('Y') ?>
        </div>

    </div>
</body>
</html>
