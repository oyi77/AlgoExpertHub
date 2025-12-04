<?php
// Clear all PHP caches
$results = [];

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = 'OPcache cleared';
} else {
    $results[] = 'OPcache not available';
}

// Clear APCu
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    $results[] = 'APCu cleared';
}

// Clear Laravel caches
chdir(__DIR__ . '/main');
exec('php artisan optimize:clear 2>&1', $output, $return);
$results[] = 'Laravel caches cleared: ' . ($return === 0 ? 'success' : 'failed');

foreach ($results as $result) {
    echo $result . "\n";
}

echo "\n✅ All caches cleared! Please refresh your browser.\n";

