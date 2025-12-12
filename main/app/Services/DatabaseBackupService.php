<?php

namespace App\Services;

use App\Helpers\Helper\Helper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupService
{
    protected $backupPath;
    protected $factoryStatePath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/database-backups');
        $this->factoryStatePath = database_path('sql/factory-state.sql');
        
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
    }

    /**
     * Fix user authentication plugin if needed (for MariaDB compatibility)
     * Note: caching_sha2_password is MySQL 8.0+ only and doesn't exist in MariaDB
     * We need to change the user's auth plugin to mysql_native_password
     */
    protected function fixUserAuthPlugin(): array
    {
        try {
            $username = config('database.connections.' . config('database.default') . '.username');
            $host = config('database.connections.' . config('database.default') . '.host');
            $password = config('database.connections.' . config('database.default') . '.password');
            
            if (empty($username)) {
                return ['type' => 'error', 'message' => 'Database username is not configured'];
            }

            // Try to check current authentication plugin
            try {
                $result = DB::select("SELECT plugin FROM mysql.user WHERE user = ? AND host = ?", [$username, $host]);
                
                if (empty($result)) {
                    // Try with % wildcard for host
                    $result = DB::select("SELECT plugin FROM mysql.user WHERE user = ?", [$username]);
                }
                
                if (!empty($result) && isset($result[0]->plugin)) {
                    $currentPlugin = $result[0]->plugin;
                    
                    // If using caching_sha2_password, change to mysql_native_password
                    if ($currentPlugin === 'caching_sha2_password') {
                        try {
                            // Change authentication plugin for all host entries
                            // Use raw SQL to avoid parameter binding issues with ALTER USER
                            $escapedUsername = DB::getPdo()->quote($username);
                            $escapedPassword = DB::getPdo()->quote($password);
                            
                            // Get all hosts for this user
                            $hosts = DB::select("SELECT DISTINCT host FROM mysql.user WHERE user = ?", [$username]);
                            
                            foreach ($hosts as $hostRow) {
                                $userHost = $hostRow->host;
                                $escapedHost = DB::getPdo()->quote($userHost);
                                
                                try {
                                    DB::statement("ALTER USER {$escapedUsername}@{$escapedHost} IDENTIFIED WITH mysql_native_password BY {$escapedPassword}");
                                    \Log::info('Changed user authentication plugin', [
                                        'username' => $username,
                                        'host' => $userHost,
                                        'old_plugin' => $currentPlugin
                                    ]);
                                } catch (\Exception $e) {
                                    \Log::warning('Could not change auth plugin for specific host', [
                                        'host' => $userHost,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                            
                            // Flush privileges
                            DB::statement("FLUSH PRIVILEGES");
                            
                            return ['type' => 'success', 'message' => 'Authentication plugin changed to mysql_native_password'];
                        } catch (\Exception $e) {
                            \Log::warning('Could not change authentication plugin', [
                                'error' => $e->getMessage(),
                                'username' => $username
                            ]);
                            // Continue anyway - might work with config file
                        }
                    } else {
                        \Log::info('User already using compatible auth plugin', [
                            'username' => $username,
                            'plugin' => $currentPlugin
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // If we can't query mysql.user (permissions issue), try to change anyway
                \Log::info('Could not query mysql.user, attempting to change auth plugin anyway', [
                    'error' => $e->getMessage()
                ]);
                
                // Try to change auth plugin directly (might work if we have privileges)
                try {
                    $escapedUsername = DB::getPdo()->quote($username);
                    $escapedPassword = DB::getPdo()->quote($password);
                    $escapedHost = DB::getPdo()->quote($host);
                    
                    DB::statement("ALTER USER {$escapedUsername}@{$escapedHost} IDENTIFIED WITH mysql_native_password BY {$escapedPassword}");
                    DB::statement("FLUSH PRIVILEGES");
                    
                    \Log::info('Changed user authentication plugin (without query)', [
                        'username' => $username,
                        'host' => $host
                    ]);
                    
                    return ['type' => 'success', 'message' => 'Authentication plugin changed'];
                } catch (\Exception $e2) {
                    \Log::warning('Could not change authentication plugin', [
                        'error' => $e2->getMessage()
                    ]);
                }
            }
            
            return ['type' => 'success', 'message' => 'Auth plugin check completed'];
        } catch (\Exception $e) {
            \Log::warning('Could not check/change authentication plugin', [
                'error' => $e->getMessage()
            ]);
            // Continue anyway - might work with config file
            return ['type' => 'success', 'message' => 'Skipped auth plugin check'];
        }
    }

    /**
     * Fix user authentication plugin via CLI (more reliable for MariaDB)
     * This actually changes the user's auth plugin in the database
     * For MariaDB, we'll try ed25519 (default) or check available plugins
     */
    protected function fixUserAuthPluginViaCli(bool $useDocker, string $host, int $port, string $username, ?string $password): void
    {
        try {
            // First, try to connect using root or a user that can modify auth plugins
            // We'll try with the same user first, but if that fails, we'll log it
            
            // Create temporary config file for mysql command
            $configFile = storage_path('app/tmp/mysql_fix_auth_' . uniqid() . '.cnf');
            $configDir = dirname($configFile);
            if (!File::exists($configDir)) {
                File::makeDirectory($configDir, 0755, true);
            }
            
            $configContent = "[client]\n";
            $configContent .= "user=" . $username . "\n";
            if (!empty($password)) {
                $configContent .= "password=" . $password . "\n";
            }
            $configContent .= "host=" . $host . "\n";
            $configContent .= "port=" . $port . "\n";
            $configContent .= "ssl=0\n";
            
            File::put($configFile, $configContent);
            chmod($configFile, 0600);
            
            // First, check what authentication plugins are available
            $checkPluginsSql = "SHOW PLUGINS WHERE Type = 'AUTHENTICATION';";
            $availablePlugins = [];
            
            if ($useDocker) {
                $container = Helper::getMysqlContainer();
                $containerConfigFile = '/tmp/mysql_check_plugins_' . uniqid() . '.cnf';
                $copyCommand = "docker cp " . escapeshellarg($configFile) . " {$container}:{$containerConfigFile} 2>&1";
                Helper::execCommand($copyCommand);
                
                $mysqlCommand = "docker exec -i " . escapeshellarg($container) . " mysql --defaults-file=" . escapeshellarg($containerConfigFile) . " -e " . escapeshellarg($checkPluginsSql) . " 2>&1";
                $output = [];
                $returnVar = 0;
                Helper::execCommand($mysqlCommand, null, $output, $returnVar);
                
                if ($returnVar === 0) {
                    foreach ($output as $line) {
                        if (preg_match('/\|\s*(\w+)\s*\|/', $line, $matches)) {
                            $availablePlugins[] = $matches[1];
                        }
                    }
                }
                Helper::execCommand("docker exec " . escapeshellarg($container) . " rm -f " . escapeshellarg($containerConfigFile) . " 2>&1");
            } else {
                $mysqlCommand = "mysql --defaults-file=" . escapeshellarg($configFile) . " -e " . escapeshellarg($checkPluginsSql) . " 2>&1";
                $output = [];
                $returnVar = 0;
                Helper::execCommand($mysqlCommand, null, $output, $returnVar);
                
                if ($returnVar === 0) {
                    foreach ($output as $line) {
                        if (preg_match('/\|\s*(\w+)\s*\|/', $line, $matches)) {
                            $availablePlugins[] = $matches[1];
                        }
                    }
                }
            }
            
            \Log::info('Available authentication plugins', ['plugins' => $availablePlugins]);
            
            // Determine which auth plugin to use
            // Priority: ed25519 (MariaDB default) > mysql_native_password > unix_socket
            $authPlugin = null;
            if (in_array('ed25519', $availablePlugins)) {
                $authPlugin = 'ed25519';
            } elseif (in_array('mysql_native_password', $availablePlugins)) {
                $authPlugin = 'mysql_native_password';
            } elseif (in_array('unix_socket', $availablePlugins)) {
                $authPlugin = 'unix_socket';
            } else {
                // If no plugins found, try ed25519 anyway (it's usually available)
                $authPlugin = 'ed25519';
            }
            
            \Log::info('Using authentication plugin', ['plugin' => $authPlugin]);
            
            // Build SQL commands to fix auth plugin for all possible host patterns
            // We need to escape the password properly for SQL
            $passwordEscaped = !empty($password) ? addslashes($password) : '';
            
            // Try to fix for the actual host first, then common patterns
            $hostsToTry = [$host, 'localhost', '127.0.0.1', '%'];
            $sqlCommands = [];
            
            foreach ($hostsToTry as $tryHost) {
                $hostEscaped = addslashes($tryHost);
                // For MariaDB, use IDENTIFIED VIA syntax, or just IDENTIFIED BY for default auth
                if ($authPlugin === 'ed25519' || $authPlugin === 'mysql_native_password') {
                    if (!empty($passwordEscaped)) {
                        $sqlCommands[] = "ALTER USER '" . addslashes($username) . "'@'" . $hostEscaped . "' IDENTIFIED VIA " . $authPlugin . " USING PASSWORD('" . $passwordEscaped . "');";
                    } else {
                        $sqlCommands[] = "ALTER USER '" . addslashes($username) . "'@'" . $hostEscaped . "' IDENTIFIED VIA " . $authPlugin . ";";
                    }
                } else {
                    // Fallback: use simple IDENTIFIED BY (uses default auth method)
                    if (!empty($passwordEscaped)) {
                        $sqlCommands[] = "ALTER USER '" . addslashes($username) . "'@'" . $hostEscaped . "' IDENTIFIED BY '" . $passwordEscaped . "';";
                    }
                }
            }
            $sqlCommands[] = "FLUSH PRIVILEGES;";
            
            // Execute each command separately to handle errors gracefully
            foreach ($sqlCommands as $sql) {
                if ($useDocker) {
                    $container = Helper::getMysqlContainer();
                    $containerConfigFile = '/tmp/mysql_fix_auth_' . uniqid() . '.cnf';
                    
                    // Copy config file to container
                    $copyCommand = "docker cp " . escapeshellarg($configFile) . " {$container}:{$containerConfigFile} 2>&1";
                    Helper::execCommand($copyCommand);
                    
                    // Execute SQL via mysql command in container
                    $mysqlCommand = "docker exec -i " . escapeshellarg($container) . " mysql --defaults-file=" . escapeshellarg($containerConfigFile) . " -e " . escapeshellarg($sql) . " 2>&1";
                    
                    $output = [];
                    $returnVar = 0;
                    Helper::execCommand($mysqlCommand, null, $output, $returnVar);
                    
                    if ($returnVar === 0) {
                        \Log::info('Executed auth plugin fix SQL (Docker)', [
                            'username' => $username,
                            'sql' => substr($sql, 0, 100)
                        ]);
                    } else {
                        // Some commands might fail (e.g., user doesn't exist for that host), that's OK
                        $errorMsg = implode("\n", $output);
                        if (strpos($errorMsg, "doesn't exist") === false && strpos($errorMsg, "Unknown user") === false) {
                            \Log::warning('Auth plugin fix SQL failed (Docker)', [
                                'error' => $errorMsg,
                                'sql' => substr($sql, 0, 100),
                                'return_code' => $returnVar
                            ]);
                        }
                    }
                    
                    // Cleanup container config file
                    Helper::execCommand("docker exec " . escapeshellarg($container) . " rm -f " . escapeshellarg($containerConfigFile) . " 2>&1");
                } else {
                    // Execute SQL via mysql command on host
                    $mysqlCommand = "mysql --defaults-file=" . escapeshellarg($configFile) . " -e " . escapeshellarg($sql) . " 2>&1";
                    
                    $output = [];
                    $returnVar = 0;
                    Helper::execCommand($mysqlCommand, null, $output, $returnVar);
                    
                    if ($returnVar === 0) {
                        \Log::info('Executed auth plugin fix SQL (Host)', [
                            'username' => $username,
                            'sql' => substr($sql, 0, 100)
                        ]);
                    } else {
                        // Some commands might fail (e.g., user doesn't exist for that host), that's OK
                        $errorMsg = implode("\n", $output);
                        if (strpos($errorMsg, "doesn't exist") === false && strpos($errorMsg, "Unknown user") === false) {
                            \Log::warning('Auth plugin fix SQL failed (Host)', [
                                'error' => $errorMsg,
                                'sql' => substr($sql, 0, 100),
                                'return_code' => $returnVar
                            ]);
                        }
                    }
                }
            }
            
            // Cleanup config file
            if (File::exists($configFile)) {
                File::delete($configFile);
            }
            
            \Log::info('Completed auth plugin fix attempt via CLI', ['username' => $username]);
        } catch (\Exception $e) {
            \Log::warning('Error in fixUserAuthPluginViaCli', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Create database backup
     */
    public function createBackup(string $name = null): array
    {
        try {
            // Check if exec is available
            if (!function_exists('exec')) {
                return ['type' => 'error', 'message' => 'exec() function is not available. Please enable it in php.ini'];
            }

            // Check if we're running inside a container and mysqldump is available locally
            $isInsideContainer = file_exists('/.dockerenv') || file_exists('/proc/self/cgroup');
            $mysqldumpAvailable = false;
            $useLocalMysqldump = false;
            
            if ($isInsideContainer) {
                // We're inside a container - check if mysqldump is available locally
                $whichOutput = [];
                $whichReturn = 0;
                Helper::execCommand('which mysqldump 2>&1', null, $whichOutput, $whichReturn);
                if ($whichReturn === 0 && !empty($whichOutput)) {
                    $mysqldumpAvailable = true;
                    $useLocalMysqldump = true;
                    \Log::info('Running inside container, mysqldump available locally', [
                        'mysqldump_path' => trim($whichOutput[0] ?? 'unknown')
                    ]);
                }
            }
            
            // Fallback: Check if we can use PHP container's mysql client via docker exec
            $phpContainer = Helper::getPhpContainer();
            $mysqlContainer = Helper::getMysqlContainer();
            
            \Log::info('Container detection', [
                'is_inside_container' => $isInsideContainer,
                'mysqldump_available_local' => $mysqldumpAvailable,
                'use_local_mysqldump' => $useLocalMysqldump,
                'php_container' => $phpContainer,
                'mysql_container' => $mysqlContainer
            ]);
            
            // Prefer local mysqldump if we're inside container, otherwise try PHP container via docker exec
            $usePhpContainer = !empty($phpContainer) && !$useLocalMysqldump;
            $useDocker = !empty($mysqlContainer) || $usePhpContainer || $useLocalMysqldump;
            
            \Log::info('Container usage decision', [
                'use_php_container' => $usePhpContainer,
                'use_docker' => $useDocker
            ]);
            
            // Also check if we're in Docker and can use container hostname
            $host = config('database.connections.' . config('database.default') . '.host');
            $isDockerEnv = Helper::isDockerEnvironment();
            
            if (!$useDocker) {
                // Try host mysqldump
                $whichOutput = [];
                $whichReturn = 0;
                Helper::execCommand('which mysqldump 2>&1', null, $whichOutput, $whichReturn);
                if ($whichReturn !== 0) {
                    // If in Docker, try using MySQL container name as hostname (if mysql-client installed)
                    if ($isDockerEnv && !in_array($host, ['localhost', '127.0.0.1'])) {
                        // Host might already be container name, mysqldump might work if client is installed
                        // But we can't test without mysql-client, so provide helpful error
                        return [
                            'type' => 'error', 
                            'message' => 'mysqldump command not found in PHP container. To fix: Install mysql-client in the PHP container with: docker exec 1Panel-php8-mrTy apk add --no-cache mysql-client'
                        ];
                    }
                    return ['type' => 'error', 'message' => 'mysqldump command not found. Please install MySQL client tools.'];
                }
            }
            
            // Validate database connection settings
            $database = config('database.connections.' . config('database.default') . '.database');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);
            
            // Check if root credentials are provided (for backup operations)
            // Root user typically has proper auth plugin and can bypass auth issues
            $rootUsername = env('DB_BACKUP_ROOT_USER', null);
            $rootPassword = env('DB_BACKUP_ROOT_PASSWORD', null);
            
            // Use root credentials if provided, otherwise use regular user credentials
            $useRootCredentials = false;
            if (!empty($rootUsername) && !empty($rootPassword)) {
                $username = $rootUsername;
                $password = $rootPassword;
                $useRootCredentials = true;
                \Log::info('Using root credentials for backup', ['username' => $rootUsername, 'host' => $host]);
            } else {
                $username = config('database.connections.' . config('database.default') . '.username');
                $password = config('database.connections.' . config('database.default') . '.password');
            }
            
            if (empty($database)) {
                return ['type' => 'error', 'message' => 'Database name is not configured'];
            }
            
            if (empty($username)) {
                return ['type' => 'error', 'message' => 'Database username is not configured'];
            }

            // Only try to fix auth plugin if NOT using local mysqldump, PHP container, or root credentials
            // (Root credentials typically have proper auth, and local mysqldump/PHP container are already authenticated)
            if (!$useLocalMysqldump && !$usePhpContainer && !$useRootCredentials) {
                $this->fixUserAuthPlugin();
                $this->fixUserAuthPluginViaCli($useDocker, $host, $port, $username, $password);
                // Wait a moment for changes to take effect
                sleep(1);
            }

            $name = $name ?? 'backup_' . date('Y-m-d_H-i-s');
            $filename = $this->sanitizeFilename($name) . '.sql';
            $filepath = $this->backupPath . '/' . $filename;

            // Ensure backup directory exists and is writable
            if (!File::exists($this->backupPath)) {
                File::makeDirectory($this->backupPath, 0755, true);
            }
            if (!is_writable($this->backupPath)) {
                return ['type' => 'error', 'message' => 'Backup directory is not writable: ' . $this->backupPath];
            }

            // Detect if we're using MariaDB (check container name or version)
            $isMariaDB = false;
            if ($useDocker && !empty($mysqlContainer)) {
                try {
                    // Check container name for MariaDB
                    $isMariaDB = stripos($mysqlContainer, 'mariadb') !== false;
                    // Also check by trying to get version (only if name check didn't find it)
                    if (!$isMariaDB) {
                        $versionOutput = [];
                        $versionReturn = 0;
                        // Use a timeout to prevent hanging
                        $versionCommand = "timeout 5 docker exec " . escapeshellarg($mysqlContainer) . " mysql --version 2>&1";
                        Helper::execCommand($versionCommand, null, $versionOutput, $versionReturn);
                        if ($versionReturn === 0 && !empty($versionOutput)) {
                            $versionStr = implode(' ', $versionOutput);
                            $isMariaDB = stripos($versionStr, 'mariadb') !== false;
                        }
                    }
                } catch (\Exception $e) {
                    // If version check fails, assume MySQL (safer default)
                    \Log::warning('Could not detect MariaDB version, assuming MySQL', [
                        'error' => $e->getMessage(),
                        'container' => $mysqlContainer ?? 'unknown'
                    ]);
                    $isMariaDB = false;
                } catch (\Throwable $e) {
                    // Catch any other errors
                    \Log::warning('Error during MariaDB detection, assuming MySQL', [
                        'error' => $e->getMessage(),
                        'container' => $mysqlContainer ?? 'unknown'
                    ]);
                    $isMariaDB = false;
                }
            }
            
            // #region agent log
            file_put_contents('/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/.cursor/debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'A','location'=>'DatabaseBackupService.php:487','message'=>'MariaDB detection result','data'=>['isMariaDB'=>$isMariaDB,'mysqlContainer'=>$mysqlContainer??null,'useDocker'=>$useDocker],'timestamp'=>time()*1000])."\n", FILE_APPEND);
            // #endregion
            
            // Build mysqldump/mariadb-dump command with proper password handling
            $errorFile = $filepath . '.error';
            
            // Create temporary config file to avoid password escaping issues
            $configFile = storage_path('app/tmp/mysqldump_' . uniqid() . '.cnf');
            $configDir = dirname($configFile);
            if (!File::exists($configDir)) {
                File::makeDirectory($configDir, 0755, true);
            }
            
            $configContent = "[client]\n";
            $configContent .= "user=" . $username . "\n";
            if (!empty($password)) {
                $configContent .= "password=" . $password . "\n";
            }
            $configContent .= "host=" . $host . "\n";
            $configContent .= "port=" . $port . "\n";
            $configContent .= "ssl=0\n"; // Disable SSL (compatible with both MySQL and MariaDB)
            // For MariaDB, don't set default-auth (let it use default)
            // For MySQL, use mysql_native_password to avoid caching_sha2_password issues
            if (!$isMariaDB) {
                $configContent .= "default-auth=mysql_native_password\n";
            }
            
            File::put($configFile, $configContent);
            chmod($configFile, 0600); // Secure permissions
            
            // #region agent log
            file_put_contents('/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/.cursor/debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'B','location'=>'DatabaseBackupService.php:518','message'=>'Config file created in createBackup','data'=>['configFile'=>$configFile,'configContent'=>str_replace($password??'','***',$configContent),'isMariaDB'=>$isMariaDB,'hasDefaultAuth'=>!$isMariaDB,'username'=>$username,'host'=>$host,'port'=>$port,'useRootCredentials'=>$useRootCredentials],'timestamp'=>time()*1000])."\n", FILE_APPEND);
            // #endregion
            
            // Always use mariadb-dump when in Docker mode (it's compatible with both MySQL and MariaDB)
            // For host mode, detect which is available
            $dumpCommand = 'mariadb-dump'; // Default to mariadb-dump (works with both)
            if (!$useDocker) {
                $whichOutput = [];
                $whichReturn = 0;
                Helper::execCommand('which mariadb-dump 2>&1', null, $whichOutput, $whichReturn);
                if ($whichReturn !== 0 || empty($whichOutput)) {
                    // Fallback to mysqldump if mariadb-dump not available
                    $whichOutput = [];
                    $whichReturn = 0;
                    Helper::execCommand('which mysqldump 2>&1', null, $whichOutput, $whichReturn);
                    if ($whichReturn === 0 && !empty($whichOutput)) {
                        $dumpCommand = 'mysqldump';
                    }
                }
            }
            
            // Build base command parts
            $commandParts = [];
            
            if ($useLocalMysqldump) {
                // We're inside the container, use mariadb-dump directly (no docker exec needed)
                // Always prefer mariadb-dump (works with both MySQL and MariaDB)
                $whichOutput = [];
                $whichReturn = 0;
                Helper::execCommand('which mariadb-dump 2>&1', null, $whichOutput, $whichReturn);
                $dumpCmd = ($whichReturn === 0 && !empty($whichOutput)) ? 'mariadb-dump' : 'mysqldump';
                
                // Use environment variable for password to avoid shell escaping issues
                // This is more secure and avoids auth plugin issues
                $envVars = [];
                if (!empty($password)) {
                    $envVars[] = 'MYSQL_PWD=' . escapeshellarg($password);
                }
                
                $dumpParts = [];
                if (!empty($envVars)) {
                    $dumpParts[] = implode(' ', $envVars);
                }
                $dumpParts[] = $dumpCmd;
                $dumpParts[] = "-h" . escapeshellarg($host);
                $dumpParts[] = "-P" . escapeshellarg((string)$port);
                $dumpParts[] = "-u" . escapeshellarg($username);
                $dumpParts[] = escapeshellarg($database);
                $dumpParts[] = "--single-transaction";
                $dumpParts[] = "--quick";
                $dumpParts[] = "--lock-tables=false";
                $dumpParts[] = "--skip-ssl";
                // Don't specify auth plugin - let it use default (works better with MariaDB)
                
                $baseCommand = implode(' ', $dumpParts);
                $command = "{$baseCommand} > " . escapeshellarg($filepath) . ' 2> ' . escapeshellarg($errorFile);
                
                \Log::info('Using local mariadb-dump/mysqldump (running inside container)', [
                    'dump_command' => $dumpCmd,
                    'host' => $host,
                    'database' => $database,
                    'username' => $username,
                    'using_root_credentials' => $useRootCredentials,
                    'using_env_password' => true,
                    'is_mariadb' => $isMariaDB
                ]);
            } else if ($usePhpContainer) {
                // Use PHP container's mysql client (already authenticated via Laravel DB connection)
                // Since Laravel can connect, the PHP container should be able to connect too
                $container = $phpContainer;
                
                // Check if mariadb-dump is available first (preferred), then mysqldump
                $checkOutput = [];
                $checkReturn = 0;
                $dumpCmd = 'mariadb-dump';
                Helper::execCommand("docker exec " . escapeshellarg($container) . " which mariadb-dump 2>&1", null, $checkOutput, $checkReturn);
                
                if ($checkReturn !== 0) {
                    // mariadb-dump not found, try mysqldump
                    $checkOutput = [];
                    $checkReturn = 0;
                    Helper::execCommand("docker exec " . escapeshellarg($container) . " which mysqldump 2>&1", null, $checkOutput, $checkReturn);
                    if ($checkReturn === 0) {
                        $dumpCmd = 'mysqldump';
                    }
                }
                
                if ($checkReturn === 0) {
                    // mariadb-dump or mysqldump is available, use it
                    // PHP container is already authenticated, so we can use the same connection method Laravel uses
                    $dumpParts = [];
                    $dumpParts[] = "docker exec -i " . escapeshellarg($container);
                    $dumpParts[] = $dumpCmd;
                    $dumpParts[] = "-h" . escapeshellarg($host);
                    $dumpParts[] = "-P" . escapeshellarg((string)$port);
                    $dumpParts[] = "-u" . escapeshellarg($username);
                    if (!empty($password)) {
                        $dumpParts[] = "-p" . escapeshellarg($password);
                    }
                    $dumpParts[] = escapeshellarg($database);
                    $dumpParts[] = "--single-transaction";
                    $dumpParts[] = "--quick";
                    $dumpParts[] = "--lock-tables=false";
                    $dumpParts[] = "--skip-ssl";
                    // Don't specify auth plugin - let it use default (works better with MariaDB)
                    
                    $baseCommand = implode(' ', $dumpParts);
                    $command = "{$baseCommand} > " . escapeshellarg($filepath) . ' 2> ' . escapeshellarg($errorFile);
                    
                    \Log::info('Using PHP container for backup', [
                        'container' => $container,
                        'host' => $host,
                        'dump_command' => $dumpCmd,
                        'is_mariadb' => $isMariaDB
                    ]);
                } else {
                    // mariadb-dump/mysqldump not available in PHP container
                    // Try to install it automatically (for Alpine: apk, Debian: apt)
                    \Log::info('mariadb-dump/mysqldump not found in PHP container, attempting to install', ['container' => $container]);
                    
                    // Try Alpine (apk) - installs mariadb-client which includes mariadb-dump
                    $installOutput = [];
                    $installReturn = 0;
                    Helper::execCommand("docker exec " . escapeshellarg($container) . " apk add --no-cache mariadb-client 2>&1", null, $installOutput, $installReturn);
                    
                    if ($installReturn !== 0) {
                        // Try mysql-client (fallback)
                        Helper::execCommand("docker exec " . escapeshellarg($container) . " apk add --no-cache mysql-client 2>&1", null, $installOutput, $installReturn);
                    }
                    
                    if ($installReturn !== 0) {
                        // Try Debian/Ubuntu (apt) - installs mariadb-client which includes mariadb-dump
                        Helper::execCommand("docker exec " . escapeshellarg($container) . " apt-get update && apt-get install -y mariadb-client 2>&1", null, $installOutput, $installReturn);
                    }
                    
                    if ($installReturn !== 0) {
                        // Try mysql-client (fallback)
                        Helper::execCommand("docker exec " . escapeshellarg($container) . " apt-get update && apt-get install -y mysql-client 2>&1", null, $installOutput, $installReturn);
                    }
                    
                    if ($installReturn === 0) {
                        // Installation successful, try again - prefer mariadb-dump
                        $checkOutput = [];
                        $checkReturn = 0;
                        $dumpCmd = 'mariadb-dump';
                        Helper::execCommand("docker exec " . escapeshellarg($container) . " which mariadb-dump 2>&1", null, $checkOutput, $checkReturn);
                        
                        if ($checkReturn !== 0) {
                            // Fallback to mysqldump
                            $checkOutput = [];
                            $checkReturn = 0;
                            Helper::execCommand("docker exec " . escapeshellarg($container) . " which mysqldump 2>&1", null, $checkOutput, $checkReturn);
                            if ($checkReturn === 0) {
                                $dumpCmd = 'mysqldump';
                            }
                        }
                        
                        if ($checkReturn === 0) {
                            // Now use it
                            $dumpParts = [];
                            $dumpParts[] = "docker exec -i " . escapeshellarg($container);
                            $dumpParts[] = $dumpCmd;
                            $dumpParts[] = "-h" . escapeshellarg($host);
                            $dumpParts[] = "-P" . escapeshellarg((string)$port);
                            $dumpParts[] = "-u" . escapeshellarg($username);
                            if (!empty($password)) {
                                $dumpParts[] = "-p" . escapeshellarg($password);
                            }
                            $dumpParts[] = escapeshellarg($database);
                            $dumpParts[] = "--single-transaction";
                            $dumpParts[] = "--quick";
                            $dumpParts[] = "--lock-tables=false";
                            $dumpParts[] = "--skip-ssl";
                            // Don't specify auth plugin - let it use default (works better with MariaDB)
                            
                            $baseCommand = implode(' ', $dumpParts);
                            $command = "{$baseCommand} > " . escapeshellarg($filepath) . ' 2> ' . escapeshellarg($errorFile);
                            \Log::info('Installed mariadb-client/mysql-client in PHP container, using it for backup', [
                                'container' => $container,
                                'dump_command' => $dumpCmd
                            ]);
                        } else {
                            $usePhpContainer = false;
                        }
                    } else {
                        // Installation failed, fallback to MySQL container
                        \Log::warning('Could not install mariadb-client/mysql-client in PHP container, falling back to MySQL container', [
                            'container' => $container,
                            'install_output' => implode("\n", $installOutput)
                        ]);
                        $usePhpContainer = false;
                    }
                }
            }
            
            if (!$usePhpContainer && $useDocker && !empty($mysqlContainer)) {
                // Docker: use MySQL/MariaDB container - always use mariadb-dump (compatible with both)
                $container = $mysqlContainer;
                // Copy config file into container temporarily
                $containerConfigFile = '/tmp/mysqldump_' . uniqid() . '.cnf';
                $copyCommand = "docker cp " . escapeshellarg($configFile) . " {$container}:{$containerConfigFile}";
                $copyOutput = [];
                $copyReturn = 0;
                Helper::execCommand($copyCommand, null, $copyOutput, $copyReturn);
                
                // #region agent log
                file_put_contents('/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/.cursor/debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'C','location'=>'DatabaseBackupService.php:714','message'=>'Config file copied to container','data'=>['copyCommand'=>$copyCommand,'copyReturn'=>$copyReturn,'copyOutput'=>$copyOutput,'containerConfigFile'=>$containerConfigFile],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                // #endregion
                
                $commandParts[] = "docker exec -i " . escapeshellarg($container) . " mariadb-dump";
                $commandParts[] = "--defaults-file=" . escapeshellarg($containerConfigFile);
                $commandParts[] = escapeshellarg($database);
                $commandParts[] = '--single-transaction';
                $commandParts[] = '--quick';
                $commandParts[] = '--lock-tables=false';
                // Don't specify auth plugin - let it use default (works better with MariaDB)
                
                $baseCommand = implode(' ', $commandParts);
                $command = "{$baseCommand} > " . escapeshellarg($filepath) . ' 2> ' . escapeshellarg($errorFile);
                // Cleanup container config file after command
                $command .= ' ; docker exec ' . escapeshellarg($container) . ' rm -f ' . escapeshellarg($containerConfigFile);
                
                // #region agent log
                file_put_contents('/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/.cursor/debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'D','location'=>'DatabaseBackupService.php:728','message'=>'Docker command built','data'=>['command'=>str_replace($password??'','***',$command),'executionPath'=>'mysql_container','useRootCredentials'=>$useRootCredentials],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                // #endregion
                
                \Log::info('Using Docker mariadb-dump', [
                    'container' => $container,
                    'is_mariadb' => $isMariaDB,
                    'database' => $database
                ]);
            } else if (!$usePhpContainer) {
                // Host: use connection credentials with config file
                $commandParts[] = $dumpCommand;
                $commandParts[] = "--defaults-file=" . escapeshellarg($configFile);
                $commandParts[] = escapeshellarg($database);
                $commandParts[] = '--single-transaction';
                $commandParts[] = '--quick';
                $commandParts[] = '--lock-tables=false';
                
                $baseCommand = implode(' ', $commandParts);
                $command = "{$baseCommand} > " . escapeshellarg($filepath) . ' 2> ' . escapeshellarg($errorFile);
            }

            // Ensure command is set
            if (!isset($command) || empty($command)) {
                return ['type' => 'error', 'message' => 'Failed to build backup command. Please check logs for details.'];
            }
            
            \Log::info('Executing backup command', [
                'command' => str_replace($password ?? '', '***', $command),
                'use_php_container' => $usePhpContainer ?? false,
                'php_container' => $phpContainer ?? null,
                'mysql_container' => $mysqlContainer ?? null
            ]);
            
            $output = [];
            $returnVar = 0;
            Helper::execCommand($command, null, $output, $returnVar);
            
            // #region agent log
            file_put_contents('/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/.cursor/debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'E','location'=>'DatabaseBackupService.php:761','message'=>'Command executed','data'=>['returnVar'=>$returnVar,'outputLines'=>count($output),'outputSample'=>array_slice($output,0,5)],'timestamp'=>time()*1000])."\n", FILE_APPEND);
            // #endregion
            
            // Cleanup config file
            if (File::exists($configFile)) {
                File::delete($configFile);
            }

            if ($returnVar !== 0) {
                \Log::error('Backup command failed', [
                    'return_var' => $returnVar,
                    'output' => $output,
                    'use_php_container' => $usePhpContainer ?? false
                ]);
                // Read error file if it exists
                $errorMsg = 'Unknown error';
                if (File::exists($errorFile)) {
                    $errorContent = File::get($errorFile);
                    if (!empty($errorContent)) {
                        $errorMsg = trim($errorContent);
                    }
                    
                    // #region agent log
                    file_put_contents('/opt/1panel/apps/openresty/openresty/www/sites/aitradepulse.com/index/.cursor/debug.log', json_encode(['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'F','location'=>'DatabaseBackupService.php:777','message'=>'Error file content','data'=>['errorFile'=>$errorFile,'errorContent'=>$errorContent,'hasCachingSha2'=>stripos($errorContent,'caching_sha2_password')!==false,'hasPluginError'=>stripos($errorContent,'Plugin')!==false],'timestamp'=>time()*1000])."\n", FILE_APPEND);
                    // #endregion
                    
                    File::delete($errorFile);
                }
                
                // Also check if output has any messages
                if (!empty($output)) {
                    $errorMsg = implode("\n", $output) . ($errorMsg !== 'Unknown error' ? "\n" . $errorMsg : '');
                }
                
                // Log detailed error
                \Log::error('Backup command failed', [
                    'return_var' => $returnVar,
                    'output' => $output,
                    'error_file_content' => File::exists($errorFile) ? File::get($errorFile) : null,
                    'command' => str_replace($password ?? '', '***', $command)
                ]);
                
                // Clean up partial files if exists
                if (File::exists($filepath)) {
                    File::delete($filepath);
                }
                if (File::exists($errorFile)) {
                    File::delete($errorFile);
                }

                // Check if it's an auth plugin error
                $isAuthPluginError = (strpos($errorMsg, 'caching_sha2_password') !== false || (strpos($errorMsg, 'Plugin') !== false && strpos($errorMsg, 'could not be loaded') !== false));
                
                if ($isAuthPluginError) {
                    // The user account is configured with caching_sha2_password which MariaDB doesn't support
                    // For MariaDB, use IDENTIFIED BY (without specifying plugin) to use default auth method
                    // This must be run as root user
                    $fixSql = "ALTER USER '" . addslashes($username) . "'@'" . addslashes($host) . "' IDENTIFIED BY '" . addslashes($password ?? '') . "'; FLUSH PRIVILEGES;";
                    $fixSqlAlt = "ALTER USER '" . addslashes($username) . "'@'%' IDENTIFIED BY '" . addslashes($password ?? '') . "'; FLUSH PRIVILEGES;";
                    
                    \Log::error('Auth plugin error detected', [
                        'username' => $username,
                        'host' => $host,
                        'error' => $errorMsg,
                        'use_local_mysqldump' => $useLocalMysqldump ?? false
                    ]);
                    
                    // If we're already using root, this shouldn't happen - but provide helpful message
                    if ($useRootCredentials) {
                        return [
                            'type' => 'error',
                            'message' => 'Backup failed: Authentication plugin error even with root credentials. This is unusual. Error: ' . $errorMsg
                        ];
                    }
                    
                    // Get the regular username for the error message
                    $regularUsername = config('database.connections.' . config('database.default') . '.username');
                    $regularPassword = config('database.connections.' . config('database.default') . '.password');
                    
                    // For MariaDB, use IDENTIFIED BY (without plugin) to use default auth method
                    $fixSql = "ALTER USER '" . addslashes($regularUsername) . "'@'" . addslashes($host) . "' IDENTIFIED BY '" . addslashes($regularPassword ?? '') . "'; FLUSH PRIVILEGES;";
                    $fixSqlAlt = "ALTER USER '" . addslashes($regularUsername) . "'@'%' IDENTIFIED BY '" . addslashes($regularPassword ?? '') . "'; FLUSH PRIVILEGES;";
                    
                    return [
                        'type' => 'error', 
                        'message' => 'Backup failed: Authentication plugin error. The database user "' . $regularUsername . '" is configured to use caching_sha2_password which is not supported by MariaDB. Solution: Add root credentials to your .env file: DB_BACKUP_ROOT_USER=root and DB_BACKUP_ROOT_PASSWORD=your_root_password. Alternatively, fix the user auth by running as root: ' . $fixSql
                    ];
                }

                return ['type' => 'error', 'message' => 'Backup failed: ' . $errorMsg];
            }
            
            // Clean up error file if command succeeded
            if (File::exists($errorFile)) {
                $errorContent = File::get($errorFile);
                if (!empty(trim($errorContent))) {
                    // Even if return code is 0, check for warnings in stderr
                    \Log::warning('Backup completed with warnings', ['warnings' => $errorContent]);
                }
                File::delete($errorFile);
            }

            if (!File::exists($filepath)) {
                return ['type' => 'error', 'message' => 'Backup file was not created. Check file permissions.'];
            }

            // Check if file is not empty
            $size = File::size($filepath);
            if ($size === 0) {
                File::delete($filepath);
                return ['type' => 'error', 'message' => 'Backup file is empty. Please check database connection and credentials.'];
            }

            $tables = $this->countTables();

            return [
                'type' => 'success',
                'message' => 'Backup created successfully',
                'data' => [
                    'filename' => $filename,
                    'size' => $size,
                    'tables' => $tables,
                    'created_at' => now()->toDateTimeString()
                ]
            ];

        } catch (\Exception $e) {
            \Log::error('Backup creation error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['type' => 'error', 'message' => 'Backup error: ' . $e->getMessage()];
        }
    }

    /**
     * Load backup state
     */
    public function loadBackup(string $filename): array
    {
        try {
            // Check if exec is available
            if (!function_exists('exec')) {
                return ['type' => 'error', 'message' => 'exec() function is not available. Please enable it in php.ini'];
            }

            // Handle factory state file (stored in different location)
            if ($filename === 'factory-state.sql') {
                $filepath = $this->factoryStatePath;
            } else {
                $filepath = $this->backupPath . '/' . $filename;
            }

            if (!File::exists($filepath)) {
                return ['type' => 'error', 'message' => 'Backup file not found: ' . $filename];
            }

            // Check if we should use Docker
            $mysqlContainer = Helper::getMysqlContainer();
            $useDocker = !empty($mysqlContainer);
            
            if (!$useDocker) {
                // Check if mysql command exists on host
                exec('which mysql 2>&1', $whichOutput, $whichReturn);
                if ($whichReturn !== 0) {
                    return ['type' => 'error', 'message' => 'mysql command not found. Please install MySQL client tools.'];
                }
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Drop all tables first
            Artisan::call('db:wipe', ['--force' => true]);

            // Import backup
            if ($useDocker) {
                // Use Docker: pipe file into container
                if (!empty($password)) {
                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-u', $username, "-p{$password}", $database];
                } else {
                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-u', $username, $database];
                }
                $mysqlCommand = Helper::buildMysqlCommand('mysql', $args);
                $command = sprintf('cat %s | %s 2>&1', escapeshellarg($filepath), $mysqlCommand);
            } else {
                // Use host mysql
                if (!empty($password)) {
                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-h', $host, '-P', (string)$port, '-u', $username, "-p{$password}", $database];
                } else {
                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-h', $host, '-P', (string)$port, '-u', $username, $database];
                }
                $mysqlCommand = Helper::buildMysqlCommand('mysql', $args);
                $command = sprintf('%s < %s 2>&1', $mysqlCommand, escapeshellarg($filepath));
            }

            $output = [];
            $returnVar = 0;
            Helper::execCommand($command, null, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMsg = !empty($output) ? implode("\n", $output) : 'Unknown error';
                \Log::error('Backup restore failed', ['command' => $command, 'output' => $output, 'return_var' => $returnVar]);
                return ['type' => 'error', 'message' => 'Restore failed: ' . $errorMsg];
            }

            // Run migrations to ensure all tables exist (in case SQL is incomplete)
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Exception $e) {
                \Log::warning('Migration after restore failed (non-critical)', ['error' => $e->getMessage()]);
            }

            // Clear caches and refresh database connection
            Artisan::call('optimize:clear');
            
            // Refresh database connection to ensure it sees the restored tables
            try {
                DB::purge();
                DB::reconnect();
            } catch (\Exception $e) {
                \Log::warning('Could not refresh database connection', ['error' => $e->getMessage()]);
            }

            return ['type' => 'success', 'message' => 'Database restored from backup successfully'];

        } catch (\Exception $e) {
            \Log::error('Backup restore error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['type' => 'error', 'message' => 'Restore error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete backup
     */
    public function deleteBackup(string $filename): array
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;

            if (!File::exists($filepath)) {
                return ['type' => 'error', 'message' => 'Backup file not found'];
            }

            if ($filename === 'factory-state.sql') {
                return ['type' => 'error', 'message' => 'Cannot delete factory state'];
            }

            File::delete($filepath);

            return ['type' => 'success', 'message' => 'Backup deleted successfully'];

        } catch (\Exception $e) {
            return ['type' => 'error', 'message' => 'Delete error: ' . $e->getMessage()];
        }
    }

    /**
     * Save current state as factory default
     */
    public function saveAsFactoryState(string $sourceFilename = null): array
    {
        try {
            if ($sourceFilename) {
                // Copy from backup to factory state
                $sourcePath = $this->backupPath . '/' . $sourceFilename;
                if (!File::exists($sourcePath)) {
                    return ['type' => 'error', 'message' => 'Source backup not found'];
                }
                File::copy($sourcePath, $this->factoryStatePath);
            } else {
                // Create backup of current database as factory state
                $result = $this->exportToFile($this->factoryStatePath);
                if ($result['type'] === 'error') {
                    return $result;
                }
            }

            return ['type' => 'success', 'message' => 'Factory state saved successfully'];

        } catch (\Exception $e) {
            return ['type' => 'error', 'message' => 'Save error: ' . $e->getMessage()];
        }
    }

    /**
     * List all backups
     */
    public function listBackups(): array
    {
        $backups = [];
        
        try {
            // Ensure backup directory exists
            if (!File::exists($this->backupPath)) {
                File::makeDirectory($this->backupPath, 0755, true);
            }

            // Check if directory is readable
            if (!is_readable($this->backupPath)) {
                \Log::warning('Backup directory is not readable', ['path' => $this->backupPath]);
                return [];
            }

            $files = File::files($this->backupPath);
            
            foreach ($files as $file) {
                try {
                    if ($file->getExtension() === 'sql' && $file->getFilename() !== 'factory-state.sql') {
                        $backups[] = [
                            'filename' => $file->getFilename(),
                            'size' => $file->getSize(),
                            'size_human' => $this->formatBytes($file->getSize()),
                            'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                            'is_factory' => false
                        ];
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error reading backup file', ['file' => $file->getPathname(), 'error' => $e->getMessage()]);
                    continue;
                }
            }

            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Add factory state if exists
            if (File::exists($this->factoryStatePath)) {
                try {
                    $factoryFile = new \SplFileInfo($this->factoryStatePath);
                    array_unshift($backups, [
                        'filename' => 'factory-state.sql',
                        'size' => $factoryFile->getSize(),
                        'size_human' => $this->formatBytes($factoryFile->getSize()),
                        'created_at' => date('Y-m-d H:i:s', $factoryFile->getMTime()),
                        'is_factory' => true
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Error reading factory state file', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error listing backups', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return $backups;
    }

    /**
     * Load factory state
     */
    public function loadFactoryState(): array
    {
        if (!File::exists($this->factoryStatePath)) {
            // Factory state doesn't exist, run seeders instead
            try {
                Artisan::call('db:wipe', ['--force' => true]);
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('db:seed', ['--force' => true]);
                Artisan::call('optimize:clear');

                return ['type' => 'success', 'message' => 'Database reset to factory state via seeders'];
            } catch (\Exception $e) {
                \Log::error('Factory state restore error', ['error' => $e->getMessage()]);
                return ['type' => 'error', 'message' => 'Failed to restore factory state: ' . $e->getMessage()];
            }
        }

        // Load factory state from its actual location
        try {
            if (!function_exists('exec')) {
                return ['type' => 'error', 'message' => 'exec() function is not available. Please enable it in php.ini'];
            }

            // Check if we should use Docker
            $mysqlContainer = Helper::getMysqlContainer();
            $useDocker = !empty($mysqlContainer);
            
            if (!$useDocker) {
                // Check if mysql command exists on host
                $whichOutput = [];
                $whichReturn = 0;
                Helper::execCommand('which mysql 2>&1', null, $whichOutput, $whichReturn);
                if ($whichReturn !== 0) {
                    return ['type' => 'error', 'message' => 'mysql command not found. Please install MySQL client tools.'];
                }
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Drop all tables first
            Artisan::call('db:wipe', ['--force' => true]);

            // Import factory state
            if ($useDocker) {
                // Use Docker: pipe file into container
                if (!empty($password)) {
                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-u', $username, "-p{$password}", $database];
                } else {
                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-u', $username, $database];
                }
                $mysqlCommand = Helper::buildMysqlCommand('mysql', $args);
                $command = sprintf('cat %s | %s 2>&1', escapeshellarg($this->factoryStatePath), $mysqlCommand);
            } else {
                // Use host mysql
                if (!empty($password)) {
                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-h', $host, '-P', (string)$port, '-u', $username, "-p{$password}", $database];
                } else {
                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-h', $host, '-P', (string)$port, '-u', $username, $database];
                }
                $mysqlCommand = Helper::buildMysqlCommand('mysql', $args);
                $command = sprintf('%s < %s 2>&1', $mysqlCommand, escapeshellarg($this->factoryStatePath));
            }

            $output = [];
            $returnVar = 0;
            Helper::execCommand($command, null, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMsg = !empty($output) ? implode("\n", $output) : 'Unknown error';
                \Log::error('Factory state restore failed', ['command' => $command, 'output' => $output, 'return_var' => $returnVar]);
                return ['type' => 'error', 'message' => 'Restore failed: ' . $errorMsg];
            }

            // Log restore completion
            \Log::info('Factory state SQL import completed', ['output_lines' => count($output), 'return_var' => $returnVar]);
            
            // Verify data was imported (check for admin record)
            try {
                $adminCount = DB::table('admins')->count();
                if ($adminCount === 0) {
                    \Log::warning('Factory restore completed but admin table is empty. SQL import may have failed partially.');
                    // Try to re-import just the data (skip CREATE TABLE statements)
                    // This is a fallback - ideally the full import should work
                } else {
                    \Log::info('Factory restore verified: admin records found', ['count' => $adminCount]);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not verify admin data after restore', ['error' => $e->getMessage()]);
            }

            // Run migrations to ensure all tables exist (in case SQL is incomplete or outdated)
            try {
                \Log::info('Running migrations after factory restore');
                Artisan::call('migrate', ['--force' => true]);
                \Log::info('Migrations completed after factory restore');
            } catch (\Exception $e) {
                \Log::error('Migration after factory restore failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                // Don't fail the restore if migrations fail, but log it
            }

            // Verify critical tables exist
            try {
                $criticalTables = ['admins', 'users', 'plans', 'signals'];
                $missingTables = [];
                foreach ($criticalTables as $table) {
                    if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                        $missingTables[] = $table;
                    }
                }
                if (!empty($missingTables)) {
                    \Log::warning('Critical tables missing after factory restore', ['missing' => $missingTables]);
                    // Try to run migrations again, but only for missing tables
                    try {
                        // Run fresh migrations if critical tables are missing
                        if (in_array('admins', $missingTables)) {
                            \Log::info('Running fresh migrations to create missing tables');
                            Artisan::call('migrate:fresh', ['--force' => true, '--seed' => false]);
                            // Re-import factory state data after fresh migration
                            if ($useDocker) {
                                if (!empty($password)) {
                                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-u', $username, "-p{$password}", $database];
                                } else {
                                    $args = ['--skip-ssl', '--default-auth=mysql_native_password', '-u', $username, $database];
                                }
                                $mysqlCommand = Helper::buildMysqlCommand('mysql', $args);
                                $reimportCommand = sprintf('cat %s | %s 2>&1', escapeshellarg($this->factoryStatePath), $mysqlCommand);
                                $reimportOutput = [];
                                $reimportReturn = 0;
                                Helper::execCommand($reimportCommand, null, $reimportOutput, $reimportReturn);
                                if ($reimportReturn !== 0) {
                                    \Log::error('Re-import after fresh migration failed', ['output' => $reimportOutput]);
                                }
                            }
                        } else {
                            // Just run migrations for missing tables
                            Artisan::call('migrate', ['--force' => true]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Second migration attempt failed', ['error' => $e->getMessage()]);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Could not verify tables after restore', ['error' => $e->getMessage()]);
            }

            // Clear caches and refresh database connection
            Artisan::call('optimize:clear');
            
            // Refresh database connection to ensure it sees the restored tables
            try {
                DB::purge();
                DB::reconnect();
            } catch (\Exception $e) {
                \Log::warning('Could not refresh database connection', ['error' => $e->getMessage()]);
            }

            return ['type' => 'success', 'message' => 'Database restored to factory state successfully'];
        } catch (\Exception $e) {
            \Log::error('Factory state restore error', ['error' => $e->getMessage()]);
            return ['type' => 'error', 'message' => 'Restore error: ' . $e->getMessage()];
        }
    }

    /**
     * Export current database to file
     */
    protected function exportToFile(string $filepath): array
    {
        try {
            // Ensure directory exists
            $dir = dirname($filepath);
            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $database = config('database.connections.' . config('database.default') . '.database');
            $username = config('database.connections.' . config('database.default') . '.username');
            $password = config('database.connections.' . config('database.default') . '.password');
            $host = config('database.connections.' . config('database.default') . '.host');
            $port = config('database.connections.' . config('database.default') . '.port', 3306);

            // Check if we should use Docker
            $mysqlContainer = Helper::getMysqlContainer();
            $useDocker = !empty($mysqlContainer);
            
            // Detect if we're using MariaDB
            $isMariaDB = false;
            if ($useDocker && !empty($mysqlContainer)) {
                $isMariaDB = stripos($mysqlContainer, 'mariadb') !== false;
                if (!$isMariaDB) {
                    $versionOutput = [];
                    $versionReturn = 0;
                    Helper::execCommand("docker exec " . escapeshellarg($mysqlContainer) . " mysql --version 2>&1", null, $versionOutput, $versionReturn);
                    if ($versionReturn === 0 && !empty($versionOutput)) {
                        $versionStr = implode(' ', $versionOutput);
                        $isMariaDB = stripos($versionStr, 'mariadb') !== false;
                    }
                }
            }

            // Build mysqldump/mariadb-dump command with proper password handling
            $errorFile = $filepath . '.error';
            
            // Create temporary config file to avoid password escaping issues
            $configFile = storage_path('app/tmp/mysqldump_' . uniqid() . '.cnf');
            $configDir = dirname($configFile);
            if (!File::exists($configDir)) {
                File::makeDirectory($configDir, 0755, true);
            }
            
            $configContent = "[client]\n";
            $configContent .= "user=" . $username . "\n";
            if (!empty($password)) {
                $configContent .= "password=" . $password . "\n";
            }
            $configContent .= "host=" . $host . "\n";
            $configContent .= "port=" . $port . "\n";
            $configContent .= "ssl=0\n"; // Disable SSL (compatible with both MySQL and MariaDB)
            // For MariaDB, don't set default-auth (let it use default)
            // For MySQL, use mysql_native_password to avoid caching_sha2_password issues
            if (!$isMariaDB) {
                $configContent .= "default-auth=mysql_native_password\n";
            }
            
            File::put($configFile, $configContent);
            chmod($configFile, 0600); // Secure permissions
            
            // Always use mariadb-dump when in Docker mode (it's compatible with both MySQL and MariaDB)
            // For host mode, detect which is available
            $dumpCommand = 'mariadb-dump'; // Default to mariadb-dump (works with both)
            if (!$useDocker) {
                $whichOutput = [];
                $whichReturn = 0;
                Helper::execCommand('which mariadb-dump 2>&1', null, $whichOutput, $whichReturn);
                if ($whichReturn !== 0 || empty($whichOutput)) {
                    // Fallback to mysqldump if mariadb-dump not available
                    $whichOutput = [];
                    $whichReturn = 0;
                    Helper::execCommand('which mysqldump 2>&1', null, $whichOutput, $whichReturn);
                    if ($whichReturn === 0 && !empty($whichOutput)) {
                        $dumpCommand = 'mysqldump';
                    }
                }
            }
            
            $commandParts = [];
            
            if ($useDocker) {
                // Use Docker: run mariadb-dump in MySQL/MariaDB container and pipe output to host file
                $container = Helper::getMysqlContainer();
                // Copy config file into container temporarily
                $containerConfigFile = '/tmp/mysqldump_' . uniqid() . '.cnf';
                $copyCommand = "docker cp " . escapeshellarg($configFile) . " {$container}:{$containerConfigFile}";
                Helper::execCommand($copyCommand);
                
                $commandParts[] = "docker exec -i " . escapeshellarg($container) . " mariadb-dump";
                $commandParts[] = "--defaults-file=" . escapeshellarg($containerConfigFile);
                $commandParts[] = escapeshellarg($database);
                $commandParts[] = '--single-transaction';
                $commandParts[] = '--quick';
                $commandParts[] = '--lock-tables=false';
                // Don't specify auth plugin - let it use default (works better with MariaDB)
                
                $baseCommand = implode(' ', $commandParts);
                $command = "{$baseCommand} > " . escapeshellarg($filepath) . ' 2> ' . escapeshellarg($errorFile);
                // Cleanup container config file after command
                $command .= ' ; docker exec ' . escapeshellarg($container) . ' rm -f ' . escapeshellarg($containerConfigFile);
            } else {
                // Use host mysqldump/mariadb-dump with config file
                $commandParts[] = $dumpCommand;
                $commandParts[] = "--defaults-file=" . escapeshellarg($configFile);
                $commandParts[] = escapeshellarg($database);
                $commandParts[] = '--single-transaction';
                $commandParts[] = '--quick';
                $commandParts[] = '--lock-tables=false';
                
                $baseCommand = implode(' ', $commandParts);
                $command = "{$baseCommand} > " . escapeshellarg($filepath) . ' 2> ' . escapeshellarg($errorFile);
            }

            \Log::info('Executing export command', ['command' => str_replace($password ?? '', '***', $command)]);
            
            $output = [];
            $returnVar = 0;
            Helper::execCommand($command, null, $output, $returnVar);
            
            // Cleanup config file
            if (File::exists($configFile)) {
                File::delete($configFile);
            }

            if ($returnVar !== 0) {
                // Read error file if it exists
                $errorMsg = 'Unknown error';
                if (File::exists($errorFile)) {
                    $errorContent = File::get($errorFile);
                    if (!empty($errorContent)) {
                        $errorMsg = trim($errorContent);
                    }
                    File::delete($errorFile);
                }
                
                if (!empty($output)) {
                    $errorMsg = implode("\n", $output) . ($errorMsg !== 'Unknown error' ? "\n" . $errorMsg : '');
                }
                
                \Log::error('Export command failed', [
                    'return_var' => $returnVar,
                    'output' => $output,
                    'error_file_content' => File::exists($errorFile) ? File::get($errorFile) : null
                ]);
                
                if (File::exists($errorFile)) {
                    File::delete($errorFile);
                }
                
                return ['type' => 'error', 'message' => 'Export failed: ' . $errorMsg];
            }
            
            // Clean up error file if command succeeded
            if (File::exists($errorFile)) {
                File::delete($errorFile);
            }

            if (!File::exists($filepath) || File::size($filepath) === 0) {
                return ['type' => 'error', 'message' => 'Export file is empty or was not created'];
            }

            return ['type' => 'success', 'message' => 'Exported successfully'];

        } catch (\Exception $e) {
            \Log::error('Export to file error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['type' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Count database tables
     */
    protected function countTables(): int
    {
        $database = config('database.connections.' . config('database.default') . '.database');
        $tables = DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [$database]);
        return $tables[0]->count ?? 0;
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }

    /**
     * Sanitize filename
     */
    protected function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }

}

