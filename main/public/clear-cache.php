<?php
// Temporary cache clear script
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared\n";
}

if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "APCu cleared\n";
}

// Clear Laravel caches
$basePath = dirname(__DIR__);
$files = [
    $basePath . '/bootstrap/cache/routes-v7.php',
    $basePath . '/bootstrap/cache/config.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo "Deleted: " . basename($file) . "\n";
    }
}

echo "Cache cleared! Please delete this file after use.\n";

