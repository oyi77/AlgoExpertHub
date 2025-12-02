<?php

/**
 * Fix addon namespaces and class references after incorrect automated replacements.
 */

$baseDir = __DIR__ . '/app';

if (!is_dir($baseDir)) {
    fwrite(STDERR, "App directory not found: {$baseDir}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if ($fileInfo->getExtension() !== 'php') {
        continue;
    }

    $path = $fileInfo->getPathname();
    $relativePath = substr($path, strlen($baseDir) + 1); // e.g. "Adapters/ApiAdapter.php"
    $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

    $segments = explode('/', $relativePath);
    array_pop($segments); // remove filename

    $namespace = 'Addons\\MultiChannelSignalAddon\\App';
    if (!empty($segments)) {
        $namespace .= '\\' . implode('\\', $segments);
    }

    $content = file_get_contents($path);

    if ($content === false) {
        fwrite(STDERR, "Failed to read {$path}\n");
        continue;
    }

    $lines = preg_split("/\r?\n/", $content);
    $updated = false;

    foreach ($lines as $index => $line) {
        if (strpos($line, 'namespace ') === 0) {
            $lines[$index] = 'namespace ' . $namespace . ';';
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        foreach ($lines as $index => $line) {
            if (strpos($line, '<?php') === 0) {
                array_splice($lines, $index + 1, 0, ['', 'namespace ' . $namespace . ';']);
                $updated = true;
                break;
            }
        }
    }

    if (!$updated) {
        fwrite(STDERR, "Skipped namespace update for {$path}\n");
        continue;
    }

    $content = implode(PHP_EOL, $lines);

    $replacements = [
        'Addons\\MultiChannelSignalAddon\\Addons\\MultiChannelSignalAddon' => 'Addons\\MultiChannelSignalAddon',
        'Addons\\MultiChannelSignalAddon\\App$' => 'Addons\\MultiChannelSignalAddon\\App',
    ];

    $content = strtr($content, $replacements);

    $content = preg_replace_callback(
        '/Addons\\\\MultiChannelSignalAddon[^;\n]*/',
        static function (array $matches): string {
            return preg_replace('/[$0-9]+/', '', $matches[0]);
        },
        $content
    );

    $content = preg_replace(
        '/Addons\\\\MultiChannelSignalAddon\\\\App\\\\\\\\/',
        'Addons\\MultiChannelSignalAddon\\App\\',
        $content
    );

    file_put_contents($path, $content);
    echo "Fixed namespaces in: {$path}\n";
}

echo "Namespace fix complete.\n";


