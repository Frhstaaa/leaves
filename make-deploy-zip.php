<?php
$sourceDir = realpath(__DIR__);
$zipFile = $sourceDir . DIRECTORY_SEPARATOR . 'sgin_leaves_cpanel_ready.zip';

if (file_exists($zipFile)) {
    @unlink($zipFile);
}

foreach (glob($sourceDir . '/bootstrap/cache/*.php') as $f) {
    @unlink($f);
}

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Cannot create zip archive\n");
}

$excludes = [
    'node_modules',
    '.git',
    'tests',
    'sgin_leaves_cpanel_ready.zip',
    'make-deploy-zip.php',
    'test-local-db.php',
    'export-db.php',
    '.env',
];

$dirIterator = new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

$fileCount = 0;
foreach ($iterator as $item) {
    $subPath = substr($item->getPathname(), strlen($sourceDir) + 1);
    $subPath = str_replace('\\', '/', $subPath);

    $skip = false;
    foreach ($excludes as $ex) {
        if ($subPath === $ex || str_starts_with($subPath, $ex . '/')) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    if ($item->isDir()) {
        $zip->addEmptyDir($subPath);
    } else {
        $zip->addFile($item->getPathname(), $subPath);
        $fileCount++;
    }
}

$envProdContent = file_get_contents($sourceDir . '/.env.production');
$zip->addFromString('.env', $envProdContent);
$fileCount++;

$storageDirs = [
    'storage/app/public',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
];
foreach ($storageDirs as $sd) {
    $zip->addEmptyDir($sd);
}

$zip->close();
$zipSizeMb = round(filesize($zipFile) / (1024 * 1024), 2);
echo "ZIP_SUCCESS: Created {$zipFile} ({$zipSizeMb} MB, {$fileCount} files packaged).\n";
