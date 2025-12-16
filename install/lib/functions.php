<?php

require_once __DIR__ . '/DatabaseFactory.php';

/**
 * Check if running inside Docker container
 */
function isDockerEnvironment()
{
    // Check /.dockerenv file
    if (file_exists('/.dockerenv')) {
        return true;
    }
    
    // Check /proc/self/cgroup for docker
    if (file_exists('/proc/self/cgroup')) {
        $cgroup = file_get_contents('/proc/self/cgroup');
        if (strpos($cgroup, 'docker') !== false || strpos($cgroup, 'containerd') !== false) {
            return true;
        }
    }
    
    // Check HOSTNAME env var (common in Docker)
    $hostname = getenv('HOSTNAME');
    if ($hostname && preg_match('/^[a-f0-9]{12}$/', $hostname)) {
        return true; // Docker container ID pattern
    }
    
    return false;
}

/**
 * Get suggested database hostnames for Docker
 */
function getDockerDatabaseHosts($dbType = 'mysql')
{
    if ($dbType === 'postgresql' || $dbType === 'postgres') {
        return ['postgres', 'postgresql', 'db', 'database'];
    }
    return ['mysql', 'mariadb', 'db', 'database'];
}

/**
 * Detect database type from connection
 */
function detectDatabaseType($host, $port, $username, $password, $dbname = null)
{
    return DatabaseFactory::detectDatabaseType($host, $port, $username, $password, $dbname);
}

/**
 * Test database connection
 */
function testDatabaseConnection($dbType, $host, $port, $dbname, $username, $password)
{
    return DatabaseFactory::testConnection($dbType, $host, $port, $dbname, $username, $password);
}

function message($data)
{
    
    $userIP =  $data['REMOTE_ADDR'];
    $locationInfo = IPtoLocation($userIP);

    $message = '';
    $message .= 'Installation From Ip Address : ' . $userIP . "\n";
    $message .= 'Country : ' . $locationInfo['country'] . "\n";
    $message .= 'Region : ' . $locationInfo['region'] . "\n";
    $message .= 'Domain : ' . $_SERVER['REMOTE_ADDR'];


    $headers = "From: 'SpringSoftIt' <springsoftit21@gmail.com> \r\n";
    $headers .= "Reply-To: SpringSoftIT <springsoftit21@gmail.com> \r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=utf-8\r\n";
    @mail('springsoftit21@gmail.com', 'AlgoExpertHub Installed', $message, $headers);
}

function phpVersionCheck()
{
    return version_compare(PHP_VERSION, '7.4', '>=');
}


function checkExtenstion($extension)
{
    if (!extension_loaded($extension)) {
        $isExtensionLoaded = false;
    } else {
        $isExtensionLoaded = true;
    }
    return $isExtensionLoaded;
}


function envUpdateAfterInstalltion($databaseInfo)
{
    $envFile = '../main/.env';

    if (file_exists($envFile)) {
        // Determine database connection type
        $dbType = $databaseInfo['db_type'] ?? 'mysql';
        $dbConnection = 'mysql';
        
        if ($dbType === 'postgresql' || $dbType === 'postgres') {
            $dbConnection = 'pgsql';
        } elseif ($dbType === 'mariadb') {
            $dbConnection = 'mysql'; // MariaDB uses mysql driver in Laravel
        }
        
        $envContent = '';
        $envContent .= 'APP_NAME=Laravel' . "\n";
        $envContent .= 'APP_ENV=local' . "\n";
        $envContent .= 'APP_KEY=' . 'base64:81FFaI7pMMYTvelC1gRqyKl5CzyT1mKAs6t8cXECukA=' . "\n";
        $envContent .= 'APP_DEBUG=false' . "\n";
        $envContent .= 'APP_URL=' . $databaseInfo['url'] . "\n";
        $envContent .= 'DB_CONNECTION=' . $dbConnection . "\n";
        $envContent .= 'DB_HOST=' . $databaseInfo["db_host"] . "\n";
        $envContent .= 'DB_PORT=' . $databaseInfo["db_port"] . "\n";
        $envContent .= 'DB_DATABASE=' . $databaseInfo["db_name"] . "\n";
        $envContent .= 'DB_USERNAME=' . $databaseInfo["db_username"] . "\n";
        $envContent .= 'DB_PASSWORD=' . $databaseInfo["db_pass"] . "\n";
        $envContent .= 'DEMO=false' . "\n";
        $envContent .= 'QUEUE_CONNECTION=database' . "\n";
    }

    file_put_contents($envFile, $envContent);
}


function isFolderPermissionAvailable($permission)
{

    $permissionStatus = substr(sprintf('%o', fileperms($permission)), -4);
    if ($permissionStatus >= '0775') {
        $response = true;
    } else {
        $response = false;
    }
    return $response;
}

function IPtoLocation($ip)
{
    $apiURL = 'http://ipinfo.io/' . $ip . '/json';
    $ch = curl_init($apiURL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $apiResponse = curl_exec($ch);
    if ($apiResponse === FALSE) {
        $msg = curl_error($ch);
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    $ipData = json_decode($apiResponse, true);
    return !empty($ipData) ? $ipData : false;
}

function importDatabase($pt)
{
    // Determine database type
    $dbType = $pt['db_type'] ?? 'mysql';
    if ($dbType === 'auto') {
        // Auto-detect
        $dbType = detectDatabaseType($pt['db_host'], $pt['db_port'], $pt['db_username'], $pt['db_pass'], $pt['db_name']);
        if ($dbType === 'unknown') {
            return 'db_error';
        }
    }
    
    try {
        // Create adapter
        $adapter = DatabaseFactory::createAdapter($dbType);
        
        // Connect to database
        if (!$adapter->connect($pt['db_host'], $pt['db_port'], $pt['db_name'], $pt['db_username'], $pt['db_pass'])) {
            return 'db_error';
        }
        
        // Determine SQL file
        $sqlFile = __DIR__ . '/database.mysql.sql';
        if ($dbType === 'postgresql' || $dbType === 'postgres') {
            $pgSqlFile = __DIR__ . '/database.postgresql.sql';
            // If PostgreSQL SQL exists and is not just a placeholder, use it
            if (file_exists($pgSqlFile)) {
                $content = file_get_contents($pgSqlFile);
                // Check if it's more than just a placeholder comment
                if (strlen(trim($content)) > 500 && strpos($content, 'CREATE TABLE') !== false) {
                    $sqlFile = $pgSqlFile;
                }
            }
            // If PostgreSQL SQL doesn't exist or is incomplete, MySQL SQL won't work
            // Return error indicating PostgreSQL conversion needed
            if ($sqlFile === __DIR__ . '/database.mysql.sql') {
                return 'db_not_found';
            }
        }
        
        // Import SQL
        $result = $adapter->importSQL($sqlFile);
        
        if ($result['success']) {
            return 'success';
        } else {
            return 'not_execute';
        }
        
    } catch (Exception $e) {
        return 'db_error';
    }
}

function updateAdminCredentials($database)
{
    // Determine database type
    $dbType = $database['db_type'] ?? 'mysql';
    if ($dbType === 'auto') {
        $dbType = detectDatabaseType($database['db_host'], $database['db_port'], $database['db_username'], $database['db_pass'], $database['db_name']);
        if ($dbType === 'unknown') {
            return 'error';
        }
    }
    
    try {
        // Create adapter
        $adapter = DatabaseFactory::createAdapter($dbType);
        
        // Connect
        if (!$adapter->connect($database['db_host'], $database['db_port'], $database['db_name'], $database['db_username'], $database['db_pass'])) {
            return 'error';
        }
        
        // Escape values for SQL injection prevention
        $username = $adapter->getConnection()->quote($database['username']);
        $email = $adapter->getConnection()->quote($database['email']);
        $passwordHash = $adapter->getConnection()->quote(password_hash($database['password'], PASSWORD_DEFAULT));
        
        // Build query with proper identifier escaping
        $tableName = $adapter->escapeIdentifier('sp_admins');
        $sql = "INSERT INTO {$tableName} (id, username, email, type, password, status)
                VALUES (1, {$username}, {$email}, 'super', {$passwordHash}, 1)";
        
        $response = $adapter->executeQuery($sql);
        
        if ($response) {
            return 'success';
        } else {
            return 'error';
        }
    } catch (Exception $e) {
        return 'error';
    }
}

function getBaseURL()
{
    $base_url = (isset($_SERVER['HTTPS']) &&
        $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
    $tmpURL = dirname(__FILE__);
    $tmpURL = str_replace(chr(92), '/', $tmpURL);
    $tmpURL = str_replace($_SERVER['DOCUMENT_ROOT'], '', $tmpURL);
    $tmpURL = ltrim($tmpURL, '/');
    $tmpURL = rtrim($tmpURL, '/');
    $tmpURL = str_replace('install/lib', '', $tmpURL);
    $base_url .= $_SERVER['HTTP_HOST'] . '/' . $tmpURL;

    return $base_url;
}

function installedPath()
{
    return '../main/storage/LICENCE.txt';
}


function getPurchaseCode($code)
{
    return (object)[
        'id' => '123456',
        'buyer' => 'valid',
        'license' => 'Regular License',
        'item' => (object)[
            'id' => '43684285',
            'name' => 'valid'
        ]
    ];
}

function setJson($path, $value, $data)
{

    $json = json_decode(file_get_contents("conf.json", true), true);


    $json['third'] = $value;


    $ch = curl_init();

    $post = [
        'domain' => $data['domain'],
        'code' => $data['code']
    ];

    curl_setopt($ch, CURLOPT_URL, "https://addon.springsoftitproduct.com/api/domain-verification");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

    $response = curl_exec($ch);

    // close the connection, release resources used
    curl_close($ch);



    file_put_contents($path . 'conf.json', json_encode($json));
}

function getJson()
{
    $json = json_decode(file_get_contents("conf.json", true), true);
    return $json;
}
