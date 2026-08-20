<?php
// Proxy to public/update.php or execute directly
if (file_exists(__DIR__ . '/public/update.php')) {
    require __DIR__ . '/public/update.php';
} else {
    echo "update.php not found in public folder.";
}
