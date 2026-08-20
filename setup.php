<?php
// Proxy to public/setup.php or execute directly
if (file_exists(__DIR__ . '/public/setup.php')) {
    require __DIR__ . '/public/setup.php';
} else {
    echo "setup.php not found in public folder.";
}
