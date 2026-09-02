<?php
/**
 * Public Bridge & Standalone Runner for fix_upload.php
 */
if (file_exists(__DIR__ . '/../fix_upload.php')) {
    require __DIR__ . '/../fix_upload.php';
} else {
    echo "File fix_upload.php tidak ditemukan di direktori root.";
}
