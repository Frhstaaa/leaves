<?php
// Proxy loader to the root update.php or direct execution
if (file_exists(dirname(__DIR__) . '/update.php')) {
    require_once dirname(__DIR__) . '/update.php';
} else {
    require_once __DIR__ . '/update.php';
}
