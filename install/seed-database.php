<?php
/**
 * Database Seeding Script
 * This script helps seed the database using the SQL file or Laravel seeders
 */

require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/DatabaseFactory.php';

// Get database credentials
echo "=== Database Seeding Tool ===\n\n";

// Check if .env exists
$envFile = __DIR__ . '/../main/.env';
if (!file_exists($envFile)) {
    echo "ERROR: .env file not found at: $envFile\n";
    echo "Please run the installer first or create .env file manually.\n";
    exit(1);
}

// Parse .env file
$envVars = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') === false) continue;
    
    list($key, $value) = explode('=', $line, 2);
    $key = trim($key);
    $value = trim($value);
    $value = trim($value, '"\'');
    $envVars[$key] = $value;
}

// Get database config
$dbHost = $envVars['DB_HOST'] ?? '127.0.0.1';
$dbPort = $envVars['DB_PORT'] ?? '3306';
$dbName = $envVars['DB_DATABASE'] ?? '';
$dbUser = $envVars['DB_USERNAME'] ?? '';
$dbPass = $envVars['DB_PASSWORD'] ?? '';
$dbConnection = $envVars['DB_CONNECTION'] ?? 'mysql';

if (empty($dbName) || empty($dbUser)) {
    echo "ERROR: Database credentials not found in .env file.\n";
    echo "Required: DB_DATABASE, DB_USERNAME, DB_PASSWORD\n";
    exit(1);
}

echo "Database Configuration:\n";
echo "  Host: $dbHost\n";
echo "  Port: $dbPort\n";
echo "  Database: $dbName\n";
echo "  Username: $dbUser\n";
echo "  Connection: $dbConnection\n\n";

// Determine database type
$dbType = $dbConnection === 'pgsql' ? 'postgresql' : 'mysql';

// Create adapter
try {
    $adapter = DatabaseFactory::createAdapter($dbType);
    echo "Connecting to database...\n";
    
    if (!$adapter->connect($dbHost, $dbPort, $dbName, $dbUser, $dbPass)) {
        echo "ERROR: Failed to connect to database.\n";
        echo "Error: " . $adapter->getLastError() . "\n";
        exit(1);
    }
    
    echo "✓ Connected successfully!\n\n";
    
    // Check if tables already exist
    $connection = $adapter->getConnection();
    $tableCheckQuery = $dbType === 'postgresql' 
        ? "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'"
        : "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?";
    
    $stmt = $connection->prepare($tableCheckQuery);
    if ($dbType === 'postgresql') {
        $stmt->execute();
    } else {
        $stmt->execute([$dbName]);
    }
    $tableCount = $stmt->fetchColumn();
    
    if ($tableCount > 0) {
        echo "WARNING: Database already contains $tableCount table(s).\n";
        echo "Do you want to continue? This may add duplicate data. (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) !== 'yes') {
            echo "Aborted.\n";
            exit(0);
        }
    }
    
    // Determine SQL file
    $sqlFile = __DIR__ . '/lib/database.mysql.sql';
    if ($dbType === 'postgresql') {
        $pgSqlFile = __DIR__ . '/lib/database.postgresql.sql';
        if (file_exists($pgSqlFile)) {
            $content = file_get_contents($pgSqlFile);
            if (strlen(trim($content)) > 500 && strpos($content, 'CREATE TABLE') !== false) {
                $sqlFile = $pgSqlFile;
            } else {
                echo "ERROR: PostgreSQL SQL file is incomplete. Please convert database.mysql.sql to PostgreSQL format.\n";
                exit(1);
            }
        } else {
            echo "ERROR: PostgreSQL SQL file not found. Please convert database.mysql.sql to PostgreSQL format.\n";
            exit(1);
        }
    }
    
    if (!file_exists($sqlFile)) {
        echo "ERROR: SQL file not found: $sqlFile\n";
        exit(1);
    }
    
    echo "Importing SQL file: " . basename($sqlFile) . "\n";
    echo "This may take a few minutes...\n\n";
    
    $result = $adapter->importSQL($sqlFile);
    
    if ($result['success']) {
        echo "✓ Database schema imported successfully!\n\n";
        
        // Option to run Laravel seeders
        echo "Do you want to run Laravel seeders? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) === 'yes') {
            echo "\nRunning Laravel seeders...\n";
            $laravelPath = __DIR__ . '/../main';
            chdir($laravelPath);
            
            // Run seeders
            $output = [];
            $return = 0;
            exec('php artisan db:seed --force 2>&1', $output, $return);
            
            if ($return === 0) {
                echo "✓ Seeders completed successfully!\n";
                echo implode("\n", $output) . "\n";
            } else {
                echo "WARNING: Seeders may have failed. Output:\n";
                echo implode("\n", $output) . "\n";
            }
        }
        
        echo "\n✓ Database seeding completed!\n";
        
    } else {
        echo "ERROR: Failed to import SQL file.\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

