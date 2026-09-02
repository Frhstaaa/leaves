<?php
/**
 * SGIN Leaves - Public Frontend Updater Bridge
 * Directs traffic to root update.php
 */
if (file_exists(__DIR__ . '/../update.php')) {
    require __DIR__ . '/../update.php';
} else {
    echo "File update.php tidak ditemukan di direktori root.";
}
