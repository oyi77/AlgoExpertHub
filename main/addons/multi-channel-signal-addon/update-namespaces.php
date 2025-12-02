<?php
/**
 * Script to update namespaces in addon files
 * Run: php update-namespaces.php
 */

$addonDir = __DIR__;
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($addonDir . '/app'),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        
        // Update namespace from App\ to Addons\MultiChannelSignalAddon\App\
        $content = preg_replace('/^namespace App\\\\(.+);/m', 'namespace Addons\\MultiChannelSignalAddon\\App\\$1;', $content);
        
        // Update use statements
        $content = preg_replace('/use App\\\\(Adapters|Contracts|DTOs|Parsers|Services|Models|Http|Jobs|Console)\\\\(.+);/m', 'use Addons\\MultiChannelSignalAddon\\App\\$1\\$2;', $content);
        
        // Update class references
        $content = str_replace('new App\\', 'new Addons\\MultiChannelSignalAddon\\App\\', $content);
        $content = str_replace('\\App\\', '\\Addons\\MultiChannelSignalAddon\\App\\', $content);
        
        file_put_contents($file->getPathname(), $content);
        echo "Updated: " . $file->getPathname() . "\n";
    }
}

echo "Namespace update complete!\n";


