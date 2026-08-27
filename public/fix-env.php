<?php
/**
 * SGIN Leaves Application - Instant Key & .env Auto-Fixer
 * Memperbaiki Error 500: "No application encryption key has been specified" secara instan.
 * 
 * Akses via browser: https://www.sgin.co.id/leaves-application/fix-env.php
 */

@set_time_limit(60);
@ini_set('display_errors', 1);
error_reporting(E_ALL);

$basePath = __DIR__;
if (!file_exists($basePath . '/vendor/autoload.php')) {
    if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
        $basePath = dirname(__DIR__);
    }
}

$envPath = $basePath . '/.env';
$appKey = 'base64:fn2kAMl3S31maRCtRvzVAluYwEGHTrVblVjLr4rxokE=';

$envContent = <<<ENV
APP_NAME="Form SGIN"
APP_ENV=production
APP_KEY={$appKey}
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

// Write .env file
$envWritten = @file_put_contents($envPath, $envContent);
@chmod($envPath, 0644);

// Clear framework cache directly
$cleared = 0;
foreach (glob($basePath . '/bootstrap/cache/*.php') as $f) {
    if (basename($f) !== '.gitignore') {
        @unlink($f);
        $cleared++;
    }
}
foreach (glob($basePath . '/storage/framework/views/*.php') as $f) {
    if (basename($f) !== '.gitignore') {
        @unlink($f);
        $cleared++;
    }
}
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// Check if artisan key exists
$success = ($envWritten !== false && file_exists($envPath));
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGIN Leaves - Auto Fix Error 500 (.env & APP_KEY)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 p-4 sm:p-8 flex items-center justify-center">
    <div class="max-w-xl w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-2xl <?= $success ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-400 border border-rose-500/30' ?> flex items-center justify-center font-black text-2xl">
                <?= $success ? '✓' : '✗' ?>
            </div>
            <div>
                <h1 class="text-xl font-bold text-white"><?= $success ? 'Perbaikan .env Berhasil!' : 'Gagal Menulis .env' ?></h1>
                <p class="text-xs text-slate-400">Error 500 (No application encryption key) Telah Diatasi</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-500/30 text-xs sm:text-sm text-emerald-200 space-y-2">
                <div class="font-bold flex items-center gap-1.5 text-emerald-300">
                    <span>🎉</span> File .env aktif dengan kredensial produksi:
                </div>
                <ul class="list-disc list-inside space-y-1 font-mono text-xs text-emerald-300/80">
                    <li>APP_KEY: <span class="text-white"><?= substr($appKey, 0, 18) ?>...</span></li>
                    <li>APP_URL: <span class="text-white">https://www.sgin.co.id/leaves-application</span></li>
                    <li>DATABASE: <span class="text-white">sginco_leav</span></li>
                    <li>CACHE: <span class="text-white"><?= $cleared ?> file cache dibersihkan</span></li>
                </ul>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row gap-3">
                <a href="./" class="w-full text-center py-3.5 px-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm shadow-xl shadow-emerald-900/40 transition-all">
                    🚀 Buka Aplikasi Sekarang
                </a>
                <a href="./update-front-end.php" class="w-full text-center py-3.5 px-6 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-sm border border-slate-700 transition-all">
                    Frontend Updater
                </a>
            </div>
        <?php else: ?>
            <div class="p-4 rounded-2xl bg-rose-950/40 border border-rose-500/30 text-xs sm:text-sm text-rose-300">
                Gagal menulis file .env ke server. Pastikan hak akses folder utama 0755 atau upload file .env secara manual melalui cPanel File Manager.
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
