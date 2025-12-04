<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

function installedPath()
{
    return 'main/storage/LICENCE.txt';
}

// Check if installer is being accessed
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$isInstallerRequest = strpos($requestUri, '/install/') !== false;

// If accessing installer, allow it (PHP server will route to installer files)
if ($isInstallerRequest) {
    // Let the installer handle the request - don't check for LICENCE.txt
    // The installer files will be served directly by PHP server
}

// Only check installation status if not accessing installer
if (!file_exists(installedPath()) && !$isInstallerRequest) {
    // Check if we're in Railway or installer is explicitly allowed
    $isRailway = getenv('RAILWAY_ENVIRONMENT') !== false || getenv('PORT') !== false;
    $allowInstaller = getenv('ALLOW_INSTALLER') === 'true';
    
    if ($isRailway || $allowInstaller) {
        // In Railway or when installer is allowed, show installation instructions
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><title>Installation Required</title>";
        echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}</style>";
        echo "</head><body>";
        echo "<h1>🚀 Installation Required</h1>";
        echo "<p>This application needs to be installed before use.</p>";
        echo "<h2>Installation Options:</h2>";
        echo "<h3>Option 1: Web Installer (Recommended)</h3>";
        echo "<ol>";
        echo "<li>Set environment variable: <code>ALLOW_INSTALLER=true</code></li>";
        echo "<li>Access the installer at: <a href='/install/index.php'><strong>/install/index.php</strong></a></li>";
        echo "<li>Fill in the installation form with your database credentials</li>";
        echo "</ol>";
        echo "<h3>Option 2: Command Line Installation</h3>";
        echo "<ol>";
        echo "<li>Set <code>AUTO_INSTALL=true</code> and ensure database variables are set</li>";
        echo "<li>Or run manually: <code>php artisan install:database</code></li>";
        echo "<li>Then create LICENCE.txt: <code>echo 'installed' > main/storage/LICENCE.txt</code></li>";
        echo "</ol>";
        echo "<p><strong>Note:</strong> After installation, the LICENCE.txt file will be created automatically.</p>";
        echo "</body></html>";
        die();
    } else {
        // Traditional hosting - redirect to installer
        header('Location:install/index.php');
        die();
    }
}



/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__ . '/main/storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__ . '/main/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__ . '/main/bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
