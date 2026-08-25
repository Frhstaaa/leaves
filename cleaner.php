<?php
/**
 * 🧹 SGIN LEAVES APPLICATION - CPANEL DISK SPACE PURGER & OPTIMIZER
 * Membebaskan ratusan MB ruang disk hosting cPanel secara instan.
 */

@set_time_limit(300);
@ini_set('memory_limit', '256M');

$basePath = dirname(__DIR__);
if (!file_exists($basePath . '/vendor/autoload.php')) {
    $basePath = __DIR__;
}

function rrmdir($dir) {
    if (!is_dir($dir)) return 0;
    $freed = 0;
    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object !== "." && $object !== "..") {
            $path = $dir . "/" . $object;
            if (is_dir($path) && !is_link($path)) {
                $freed += rrmdir($path);
            } else {
                $freed += @filesize($path);
                @unlink($path);
            }
        }
    }
    @rmdir($dir);
    return $freed;
}

$action = $_POST['action'] ?? null;
$logs = [];
$totalFreed = 0;

if ($action === 'purge_disk') {
    // 1. Delete node_modules (Production never needs node_modules because Vite assets are in public/build/)
    $nodeModulesDir = $basePath . '/node_modules';
    if (is_dir($nodeModulesDir)) {
        $freed = rrmdir($nodeModulesDir);
        $freedMB = round($freed / 1024 / 1024, 2);
        $totalFreed += $freed;
        $logs[] = "✓ Folder node_modules berhasil dihapus (Membebaskan {$freedMB} MB).";
    } else {
        $logs[] = "ℹ️ Folder node_modules sudah bersih.";
    }

    // 2. Empty Laravel log files in storage/logs/
    $logDir = $basePath . '/storage/logs';
    $freedLogs = 0;
    if (is_dir($logDir)) {
        foreach (glob($logDir . '/*.log') as $logFile) {
            $sz = @filesize($logFile);
            $freedLogs += $sz;
            @file_put_contents($logFile, '');
        }
        $freedMB = round($freedLogs / 1024 / 1024, 2);
        $totalFreed += $freedLogs;
        $logs[] = "✓ File log server (laravel.log) dikosongkan (Membebaskan {$freedMB} MB).";
    }

    // 3. Clean storage/framework/views/
    $viewDir = $basePath . '/storage/framework/views';
    $freedViews = 0;
    if (is_dir($viewDir)) {
        foreach (glob($viewDir . '/*.php') as $vFile) {
            $freedViews += @filesize($vFile);
            @unlink($vFile);
        }
        $freedMB = round($freedViews / 1024 / 1024, 2);
        $totalFreed += $freedViews;
        $logs[] = "✓ Cache compiled blade views dibersihkan (Membebaskan {$freedMB} MB).";
    }

    // 4. Clean storage/framework/cache/data/
    $cacheDir = $basePath . '/storage/framework/cache/data';
    if (is_dir($cacheDir)) {
        $freedCache = rrmdir($cacheDir);
        @mkdir($cacheDir, 0777, true);
        $freedMB = round($freedCache / 1024 / 1024, 2);
        $totalFreed += $freedCache;
        $logs[] = "✓ Cache framework data dibersihkan (Membebaskan {$freedMB} MB).";
    }

    // 5. Clean git logs & prune
    if (function_exists('shell_exec')) {
        @shell_exec('git reflog expire --expire=now --all 2>&1');
        @shell_exec('git gc --prune=now --aggressive 2>&1');
        $logs[] = "✓ Git database repository dioptimasi (git gc & prune).";
    }

    // 6. Delete old zip archives in root
    foreach (glob($basePath . '/*.zip') as $zipFile) {
        $sz = @filesize($zipFile);
        $totalFreed += $sz;
        @unlink($zipFile);
        $logs[] = "✓ File arsip sementara " . basename($zipFile) . " dibersihkan.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Cleaner - Pembersih Kuota Disk Hosting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-4 sm:p-8 flex items-center justify-center">
    <div class="max-w-xl w-full bg-slate-800 border border-slate-700 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        <div class="flex items-center space-x-3 border-b border-slate-700 pb-5">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-2xl">
                🧹
            </div>
            <div>
                <h1 class="text-lg font-bold text-white">SGIN Disk Space Purger</h1>
                <p class="text-xs text-slate-400">Pembersih Cepat Kuota Disk Hosting cPanel (Bebaskan Ratusan MB)</p>
            </div>
        </div>

        <?php if ($action === 'purge_disk'): ?>
            <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 space-y-3">
                <div class="flex items-center space-x-2 text-emerald-400 font-bold text-sm">
                    <span>✓ PEMBERSIHAN SELESAI!</span>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300">
                        Total Bebas: <?= round($totalFreed / 1024 / 1024, 2) ?> MB
                    </span>
                </div>
                <ul class="text-xs text-slate-300 space-y-1.5 font-mono">
                    <?php foreach ($logs as $log): ?>
                        <li><?= htmlspecialchars($log) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="space-y-4 text-xs text-slate-300">
            <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-700/80 space-y-2">
                <div class="font-bold text-slate-200 text-sm">Target yang akan dibersihkan:</div>
                <div class="grid grid-cols-1 gap-2 text-[11px] text-slate-400">
                    <div>🗑️ <strong class="text-slate-200">node_modules/</strong> — ~150 - 250 MB (tidak dibutuhkan di server produksi).</div>
                    <div>📜 <strong class="text-slate-200">storage/logs/*.log</strong> — File riwayat error server lama.</div>
                    <div>⚡ <strong class="text-slate-200">storage/framework/views & cache</strong> — File cache sementara.</div>
                    <div>📦 <strong class="text-slate-200">Git database repository</strong> — Kompresi objek git via <code>git gc</code>.</div>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="purge_disk">
                <button type="submit" class="w-full py-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-sm shadow-lg shadow-emerald-900/40 transition-all flex items-center justify-center space-x-2">
                    <span>🧹 Bersihkan Sekarang (Free Up Space)</span>
                </button>
            </form>

            <div class="pt-2 flex items-center justify-between text-[11px] text-slate-400 border-t border-slate-700">
                <a href="update.php" class="hover:text-emerald-400 underline">← Kembali ke Control Center Update</a>
                <a href="login" class="hover:text-emerald-400 underline">Buka Aplikasi SGIN →</a>
            </div>
        </div>
    </div>
</body>
</html>
