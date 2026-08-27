<?php
/**
 * SGIN Leaves - Production Deployment Packager
 * Membuat file ZIP deployment bersih yang siap diekstrak langsung ke hosting/server
 * tanpa mengganggu atau merubah database yang sudah ada.
 */

$root = __DIR__;
$zipName = $root . '/sgin_leaves_deploy.zip';
$altZipName = $root . '/sgin_leaves_production_ready.zip';

echo "=== SGIN LEAVES DEPLOYMENT PACKAGER ===\n";

if (file_exists($zipName)) {
    @unlink($zipName);
}
if (file_exists($altZipName)) {
    @unlink($altZipName);
}

$zip = new ZipArchive();
if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("ERROR: Gagal membuat file ZIP di $zipName\n");
}

$filesCount = 0;
$totalBytes = 0;

// Helper recursive add directory
function addDirToZip($zip, $sourceDir, $zipPrefix, &$filesCount, &$totalBytes) {
    $dirIterator = new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $item) {
        $filePath = $item->getRealPath();
        $subPath = substr($filePath, strlen($sourceDir) + 1);
        $zipEntry = $zipPrefix . '/' . str_replace('\\', '/', $subPath);

        // Filter out unwanted items inside directories
        if (str_contains($zipEntry, '/.git/') || str_contains($zipEntry, '/node_modules/')) {
            continue;
        }

        if ($item->isDir()) {
            $zip->addEmptyDir($zipEntry);
        } elseif ($item->isFile()) {
            $zip->addFile($filePath, $zipEntry);
            $filesCount++;
            $totalBytes += $item->getSize();
        }
    }
}

// 1. App directories
$dirsToInclude = ['app', 'config', 'database', 'resources', 'routes', 'vendor'];
foreach ($dirsToInclude as $d) {
    $path = $root . '/' . $d;
    if (is_dir($path)) {
        echo "Menambahkan folder $d/...\n";
        $zip->addEmptyDir($d);
        addDirToZip($zip, $path, $d, $filesCount, $totalBytes);
    }
}

// 2. Bootstrap directory (with clean cache)
echo "Menambahkan folder bootstrap/...\n";
$zip->addEmptyDir('bootstrap');
$zip->addFile($root . '/bootstrap/app.php', 'bootstrap/app.php');
$zip->addEmptyDir('bootstrap/cache');
if (file_exists($root . '/bootstrap/cache/.gitignore')) {
    $zip->addFile($root . '/bootstrap/cache/.gitignore', 'bootstrap/cache/.gitignore');
} else {
    $zip->addFromString('bootstrap/cache/.gitignore', "*\n!.gitignore\n");
}

// 3. Public directory (including compiled assets in public/build)
echo "Menambahkan folder public/...\n";
$zip->addEmptyDir('public');
$pubIterator = new RecursiveDirectoryIterator($root . '/public', FilesystemIterator::SKIP_DOTS);
$pubIt = new RecursiveIteratorIterator($pubIterator, RecursiveIteratorIterator::SELF_FIRST);
foreach ($pubIt as $item) {
    $filePath = $item->getRealPath();
    $subPath = substr($filePath, strlen($root . '/public') + 1);
    $zipEntry = 'public/' . str_replace('\\', '/', $subPath);

    // Skip public/storage symlink/folder
    if ($zipEntry === 'public/storage' || str_starts_with($zipEntry, 'public/storage/')) {
        continue;
    }

    if ($item->isDir()) {
        $zip->addEmptyDir($zipEntry);
    } elseif ($item->isFile()) {
        $zip->addFile($filePath, $zipEntry);
        $filesCount++;
        $totalBytes += $item->getSize();
    }
}

// 4. Storage structure (empty clean directories)
echo "Menyiapkan struktur folder storage/...\n";
$storageStructure = [
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/framework/testing',
    'storage/logs',
];

foreach ($storageStructure as $sDir) {
    $zip->addEmptyDir($sDir);
    $zip->addFromString($sDir . '/.gitignore', "*\n!.gitignore\n");
    $filesCount++;
}

// 5. Root files (IMPORTANT: Never include .env so existing database settings are NOT touched)
$rootFiles = [
    '.htaccess',
    'index.php',
    'setup.php',
    'cleaner.php',
    'update.php',
    'artisan',
    'composer.json',
    'composer.lock',
    'package.json',
    '.env.example',
];

echo "Menambahkan file konfigurasi utama...\n";
foreach ($rootFiles as $rf) {
    $p = $root . '/' . $rf;
    if (file_exists($p)) {
        $zip->addFile($p, $rf);
        $filesCount++;
        $totalBytes += filesize($p);
    }
}

$zip->close();

// Copy to alternate name for maximum compatibility
@copy($zipName, $altZipName);

$zipSizeMB = round(filesize($zipName) / 1024 / 1024, 2);
$uncompressedMB = round($totalBytes / 1024 / 1024, 2);

echo "=======================================================\n";
echo "✓ ZIP DEPLOYMENT BERHASIL DIBUAT!\n";
echo "📦 File: " . basename($zipName) . " & " . basename($altZipName) . "\n";
echo "📊 Total File: " . number_format($filesCount) . " file\n";
echo "💾 Ukuran Terkompresi: " . $zipSizeMB . " MB (Uncompressed: " . $uncompressedMB . " MB)\n";
echo "🔒 Keamanan Database: 100% AMAN (File .env TIDAK disertakan sehingga DB server tidak akan tertimpa/berubah).\n";
echo "=======================================================\n";
