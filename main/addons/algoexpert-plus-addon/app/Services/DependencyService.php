<?php

namespace Addons\AlgoExpertPlus\App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DependencyService
{
    /**
     * Get module dependencies mapping
     */
    public function getModuleDependencies(): array
    {
        return [
            'seo' => [
                'package' => 'artesaos/seotools',
                'class' => \Artesaos\SEOTools\Providers\SEOToolsServiceProvider::class,
                'config' => null,
            ],
            'queues' => [
                'package' => 'laravel/horizon',
                'class' => \Laravel\Horizon\HorizonServiceProvider::class,
                'config' => [
                    'env_key' => 'QUEUE_CONNECTION',
                    'required_value' => 'redis',
                    // current_value will be read dynamically in getModuleDependencyStatus
                ],
            ],
            'backup' => [
                'package' => 'spatie/laravel-backup',
                'class' => \Spatie\Backup\BackupServiceProvider::class,
                'config' => null,
            ],
            'health' => [
                'package' => 'spatie/laravel-health',
                'class' => \Spatie\Health\HealthServiceProvider::class,
                'config' => null,
            ],
        ];
    }

    /**
     * Check if a package is installed
     */
    public function isPackageInstalled(string $package): bool
    {
        $vendorPath = base_path("vendor/{$package}");
        return File::isDirectory($vendorPath);
    }

    /**
     * Check if a class exists (package loaded)
     */
    public function isClassAvailable(string $className): bool
    {
        return class_exists($className);
    }

    /**
     * Get dependency status for a module
     */
    public function getModuleDependencyStatus(string $moduleKey): array
    {
        $dependencies = $this->getModuleDependencies();
        $dep = $dependencies[$moduleKey] ?? null;

        if (!$dep) {
            return [
                'installed' => false,
                'available' => false,
                'package' => null,
                'needs_install' => false,
                'needs_config' => false,
                'config_message' => null,
            ];
        }

        $packageInstalled = $this->isPackageInstalled($dep['package']);
        $classAvailable = $this->isClassAvailable($dep['class']);
        $needsConfig = false;
        $configMessage = null;

        // Check configuration requirements
        if ($dep['config']) {
            // Read current value dynamically (not cached)
            $envKey = $dep['config']['env_key'];
            $currentValue = config("queue.default", env($envKey, 'database'));
            $requiredValue = $dep['config']['required_value'];
            if ($currentValue !== $requiredValue) {
                $needsConfig = true;
                $configMessage = "Set {$envKey}={$requiredValue} in .env";
            }
        }

        return [
            'installed' => $packageInstalled,
            'available' => $classAvailable,
            'package' => $dep['package'],
            'needs_install' => !$packageInstalled || !$classAvailable,
            'needs_config' => $needsConfig,
            'config_message' => $configMessage,
        ];
    }

    /**
     * Install missing packages via composer
     */
    public function installPackages(array $packages): array
    {
        if (!function_exists('shell_exec')) {
            return [
                'success' => false,
                'message' => 'shell_exec is disabled. Please install packages manually via: composer install',
            ];
        }

        $disabledFunctions = explode(',', ini_get('disable_functions'));
        if (in_array('shell_exec', $disabledFunctions)) {
            return [
                'success' => false,
                'message' => 'shell_exec is disabled. Please install packages manually via: composer install',
            ];
        }

        $basePath = base_path();
        $phpPath = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $composerPath = $this->findComposer();

        if (!$composerPath) {
            return [
                'success' => false,
                'message' => 'Composer not found. Please install packages manually.',
                'manual_command' => "cd {$basePath} && composer install",
                'troubleshooting' => [
                    '1. Install Composer: https://getcomposer.org/download/',
                    '2. Or run: composer install manually',
                ],
            ];
        }

        // Check if vendor directory is writable
        $vendorPath = base_path('vendor');
        if (!File::isDirectory($vendorPath)) {
            // Try to create it
            try {
                File::makeDirectory($vendorPath, 0755, true);
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => 'Cannot create vendor directory. Check permissions.',
                    'manual_command' => "cd {$basePath} && mkdir -p vendor && composer install",
                    'troubleshooting' => [
                        '1. Check directory permissions: chmod 755 ' . $basePath,
                        '2. Create vendor directory: mkdir -p ' . $vendorPath,
                        '3. Run: composer install',
                    ],
                ];
            }
        }

        if (!is_writable($vendorPath)) {
            return [
                'success' => false,
                'message' => 'Vendor directory is not writable. Check permissions.',
                'manual_command' => "cd {$basePath} && chmod -R 755 vendor && composer install",
                'troubleshooting' => [
                    '1. Fix permissions: chmod -R 755 ' . $vendorPath,
                    '2. Or run: sudo composer install',
                    '3. Check ownership: ls -la ' . $vendorPath,
                ],
            ];
        }

        // Check which packages are already in composer.json
        $composerJson = json_decode(File::get(base_path('composer.json')), true);
        $requiredPackages = [];
        $missingInComposer = [];
        
        foreach ($packages as $package) {
            $installed = $this->isPackageInstalled($package);
            $inComposer = isset($composerJson['require'][$package]) || 
                         isset($composerJson['require-dev'][$package]);
            
            if (!$installed && $inComposer) {
                // Package in composer.json but not installed - run composer install
                $requiredPackages[] = $package;
            } elseif (!$installed && !$inComposer) {
                // Package not in composer.json - need to add it first
                $missingInComposer[] = $package;
            }
        }

        // If packages are missing from composer.json, provide instructions
        if (!empty($missingInComposer)) {
            $commands = [];
            foreach ($missingInComposer as $pkg) {
                $commands[] = "composer require {$pkg}";
            }
            $allCommands = implode(' && ', $commands);
            
            return [
                'success' => false,
                'message' => 'The following packages are not in composer.json: ' . implode(', ', $missingInComposer) . '. Add them first.',
                'manual_command' => "cd {$basePath} && " . $allCommands,
                'packages_missing' => $missingInComposer,
            ];
        }

        if (empty($requiredPackages)) {
            // Verify all packages are actually installed
            $allVerified = true;
            foreach ($packages as $package) {
                if (!$this->isPackageInstalled($package)) {
                    $allVerified = false;
                    break;
                }
            }
            
            if ($allVerified) {
                return [
                    'success' => true,
                    'message' => 'All packages are already installed',
                ];
            } else {
                // Packages in composer.json but not installed - try install
                $requiredPackages = $packages;
            }
        }

        try {
            // Set HOME environment variable for composer (required for composer to run)
            $homeDir = getenv('HOME') ?: (getenv('COMPOSER_HOME') ?: sys_get_temp_dir() . '/.composer');
            
            // Ensure composer home directory exists
            if (!File::isDirectory($homeDir)) {
                @File::makeDirectory($homeDir, 0755, true);
            }
            
            // First, try composer install with HOME set
            $command = "cd {$basePath} && HOME={$homeDir} COMPOSER_HOME={$homeDir} {$composerPath} install --no-interaction --prefer-dist --no-scripts 2>&1";
            $output = shell_exec($command);
            
            Log::info('Composer install executed', [
                'packages' => $requiredPackages,
                'output' => $output ? substr($output, 0, 500) : 'No output',
            ]);

            // Check if output indicates failure or success
            $hasError = false;
            $hasSuccess = false;
            if ($output) {
                $errorIndicators = ['error', 'failed', 'exception', 'fatal', 'could not', 'not found', 'nothing to install'];
                foreach ($errorIndicators as $indicator) {
                    if (stripos($output, $indicator) !== false) {
                        $hasError = true;
                        break;
                    }
                }
                
                // Check for success indicators
                $successIndicators = ['installing', 'updating', 'package operations', 'memory usage'];
                foreach ($successIndicators as $indicator) {
                    if (stripos($output, $indicator) !== false) {
                        $hasSuccess = true;
                        break;
                    }
                }
            }
            
            // If output says "nothing to install" but packages are missing, composer.lock might be out of sync
            if (!$hasSuccess && stripos($output, 'nothing to install') !== false) {
                // Set HOME for composer
                $homeDir = getenv('HOME') ?: (getenv('COMPOSER_HOME') ?: sys_get_temp_dir() . '/.composer');
                
                // Try composer update instead with HOME set
                $updateCommand = "cd {$basePath} && HOME={$homeDir} COMPOSER_HOME={$homeDir} {$composerPath} update --no-interaction --prefer-dist --no-scripts 2>&1";
                $updateOutput = shell_exec($updateCommand);
                $output = ($output ? $output . "\n\n--- UPDATE ATTEMPT ---\n" : '') . $updateOutput;
                Log::info('Composer update executed after nothing to install', [
                    'output' => $updateOutput ? substr($updateOutput, 0, 500) : 'No output',
                ]);
            }

            // Verify packages were actually installed
            $allInstalled = true;
            $failedPackages = [];
            foreach ($requiredPackages as $package) {
                if (!$this->isPackageInstalled($package)) {
                    $allInstalled = false;
                    $failedPackages[] = $package;
                }
            }

            // If packages still not installed, try composer update for specific packages
            if (!$allInstalled && !$hasError) {
                // Try updating specific packages with HOME set
                $updateCommand = "cd {$basePath} && HOME={$homeDir} COMPOSER_HOME={$homeDir} {$composerPath} update " . implode(' ', $failedPackages) . " --no-interaction --prefer-dist --no-scripts 2>&1";
                $updateOutput = shell_exec($updateCommand);
                
                Log::info('Composer update executed', [
                    'packages' => $failedPackages,
                    'output' => $updateOutput ? substr($updateOutput, 0, 500) : 'No output',
                ]);

                // Re-verify after update
                $allInstalled = true;
                $stillFailed = [];
                foreach ($failedPackages as $package) {
                    if (!$this->isPackageInstalled($package)) {
                        $allInstalled = false;
                        $stillFailed[] = $package;
                    }
                }
                $failedPackages = $stillFailed;
                $output = ($output ? $output . "\n\n--- UPDATE ATTEMPT ---\n" : '') . $updateOutput;
            }

            if (!$allInstalled) {
                // Build manual commands with HOME set
                $homeDir = getenv('HOME') ?: (getenv('COMPOSER_HOME') ?: sys_get_temp_dir() . '/.composer');
                $manualCommands = [];
                $manualCommands[] = "cd {$basePath}";
                $manualCommands[] = "export HOME={$homeDir} && export COMPOSER_HOME={$homeDir} && composer install";
                if (!empty($failedPackages)) {
                    $manualCommands[] = "export HOME={$homeDir} && export COMPOSER_HOME={$homeDir} && composer require " . implode(' ', $failedPackages);
                }
                
                return [
                    'success' => false,
                    'message' => 'Installation failed. Packages not installed: ' . implode(', ', $failedPackages) . '.',
                    'output' => $output ? substr($output, 0, 1000) : 'No output from composer command',
                    'command' => $command,
                    'manual_command' => implode(' && ', $manualCommands),
                    'packages_failed' => $failedPackages,
                    'troubleshooting' => [
                        '1. Set HOME environment: export HOME=' . $homeDir,
                        '2. Run: composer install',
                        '3. If still failing, run: composer require ' . implode(' ', $failedPackages),
                        '4. Check file permissions on vendor directory',
                        '5. Check composer.lock is up to date',
                        '6. Try: mkdir -p ' . $homeDir . ' && chmod 755 ' . $homeDir,
                    ],
                ];
            }

            if ($hasError && $output) {
                $homeDir = getenv('HOME') ?: (getenv('COMPOSER_HOME') ?: sys_get_temp_dir() . '/.composer');
                return [
                    'success' => false,
                    'message' => 'Composer command reported errors. Check output below.',
                    'output' => substr($output, 0, 1000),
                    'command' => $command,
                    'manual_command' => "cd {$basePath} && export HOME={$homeDir} && export COMPOSER_HOME={$homeDir} && composer install",
                    'troubleshooting' => [
                        '1. The HOME environment variable must be set for composer',
                        '2. Run: export HOME=' . $homeDir,
                        '3. Or: export COMPOSER_HOME=' . $homeDir,
                        '4. Then run: composer install',
                    ],
                ];
            }

            // Regenerate autoload with HOME set
            $homeDir = getenv('HOME') ?: (getenv('COMPOSER_HOME') ?: sys_get_temp_dir() . '/.composer');
            $autoloadCommand = "cd {$basePath} && HOME={$homeDir} COMPOSER_HOME={$homeDir} {$composerPath} dump-autoload 2>&1";
            $autoloadOutput = shell_exec($autoloadCommand);

            // Verify classes are available
            $allAvailable = true;
            $failedClasses = [];
            foreach ($requiredPackages as $package) {
                $dependencies = $this->getModuleDependencies();
                foreach ($dependencies as $dep) {
                    if ($dep['package'] === $package && isset($dep['class'])) {
                        if (!$this->isClassAvailable($dep['class'])) {
                            $allAvailable = false;
                            $failedClasses[] = $dep['class'];
                        }
                    }
                }
            }

            if (!$allAvailable) {
                return [
                    'success' => false,
                    'message' => 'Packages installed but classes not available. Run: composer dump-autoload',
                    'output' => $output,
                    'autoload_output' => $autoloadOutput,
                    'manual_command' => "cd {$basePath} && composer dump-autoload",
                ];
            }

            return [
                'success' => true,
                'message' => 'Packages installed and verified successfully. Please refresh the page.',
                'output' => $output,
                'packages' => $requiredPackages,
            ];
        } catch (\Throwable $e) {
            Log::error('Composer install failed', [
                'error' => $e->getMessage(),
                'packages' => $requiredPackages,
            ]);

            return [
                'success' => false,
                'message' => 'Installation failed: ' . $e->getMessage(),
                'manual_command' => "cd {$basePath} && composer install",
            ];
        }
    }

    /**
     * Update .env configuration
     */
    public function updateEnvConfig(string $key, string $value): array
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            return [
                'success' => false,
                'message' => '.env file not found',
            ];
        }

        try {
            $envContent = File::get($envPath);
            
            // Use string manipulation approach similar to Helper::setEnv for reliability
            $keyPosition = strpos($envContent, "{$key}=");
            
            if ($keyPosition !== false) {
                // Key exists - find the end of line
                $endOfLinePosition = strpos($envContent, "\n", $keyPosition);
                
                if ($endOfLinePosition === false) {
                    // Key is on last line (no newline after)
                    $oldLine = substr($envContent, $keyPosition);
                    $envContent = str_replace($oldLine, "{$key}={$value}", $envContent);
                } else {
                    // Key has a newline after it
                    $oldLine = substr($envContent, $keyPosition, $endOfLinePosition - $keyPosition);
                    $envContent = str_replace($oldLine, "{$key}={$value}", $envContent);
                }
            } else {
                // Key doesn't exist - add it
                // Ensure content ends with newline
                $envContent = rtrim($envContent) . "\n";
                $envContent .= "{$key}={$value}\n";
            }

            // Write file
            if (!File::put($envPath, $envContent)) {
                throw new \Exception('Failed to write to .env file. Check file permissions.');
            }
            
            // Clear config cache so Laravel picks up the new .env value
            try {
                \Artisan::call('config:clear');
            } catch (\Throwable $e) {
                // Log but don't fail if config:clear fails
                Log::warning('Config clear failed after env update', ['error' => $e->getMessage()]);
            }

            return [
                'success' => true,
                'message' => "Updated {$key} in .env. Config cache cleared. Please refresh the page.",
                'output' => "Updated {$key}={$value} in .env file successfully.\nConfig cache cleared.",
            ];
        } catch (\Throwable $e) {
            Log::error('Env update failed', [
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to update .env: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Find composer executable
     */
    protected function findComposer(): ?string
    {
        $paths = [
            base_path('composer.phar'),
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            'composer', // In PATH
        ];

        foreach ($paths as $path) {
            if ($path === 'composer') {
                // Check if composer is in PATH
                $output = shell_exec('which composer 2>&1');
                if ($output && trim($output)) {
                    return trim($output);
                }
            } elseif (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
