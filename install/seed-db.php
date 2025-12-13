<?php
/**
 * Simple Database Seeding Script
 * Seeds database using SQL file directly
 */

session_start();
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/DatabaseFactory.php';

echo "=== Database Seeding Tool ===\n\n";

// Get database credentials from user or .env
$envFile = __DIR__ . '/../main/.env';

if (file_exists($envFile)) {
    // Parse .env
    $envVars = [];
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        $value = trim($value, '"\'');
        $envVars[$key] = $value;
    }
    
    $dbHost = $envVars['DB_HOST'] ?? '127.0.0.1';
    $dbPort = $envVars['DB_PORT'] ?? '3306';
    $dbName = $envVars['DB_DATABASE'] ?? '';
    $dbUser = $envVars['DB_USERNAME'] ?? '';
    $dbPass = $envVars['DB_PASSWORD'] ?? '';
    $dbConnection = $envVars['DB_CONNECTION'] ?? 'mysql';
} else {
    // Prompt for credentials
    echo "Enter database credentials:\n";
    echo "Host [127.0.0.1]: ";
    $dbHost = trim(fgets(STDIN)) ?: '127.0.0.1';
    
    echo "Port [3306]: ";
    $dbPort = trim(fgets(STDIN)) ?: '3306';
    
    echo "Database name: ";
    $dbName = trim(fgets(STDIN));
    
    echo "Username: ";
    $dbUser = trim(fgets(STDIN));
    
    echo "Password: ";
    $dbPass = trim(fgets(STDIN));
    
    echo "Database type (mysql/postgresql) [mysql]: ";
    $dbTypeInput = trim(fgets(STDIN)) ?: 'mysql';
    $dbConnection = ($dbTypeInput === 'postgresql' || $dbTypeInput === 'postgres') ? 'pgsql' : 'mysql';
}

if (empty($dbName) || empty($dbUser)) {
    echo "\nERROR: Database name and username are required!\n";
    exit(1);
}

// Determine database type
$dbType = ($dbConnection === 'pgsql') ? 'postgresql' : 'mysql';

echo "\nDatabase Configuration:\n";
echo "  Type: $dbType\n";
echo "  Host: $dbHost\n";
echo "  Port: $dbPort\n";
echo "  Database: $dbName\n";
echo "  Username: $dbUser\n\n";

// Create adapter and connect
try {
    $adapter = DatabaseFactory::createAdapter($dbType);
    
    echo "Connecting to database...\n";
    if (!$adapter->connect($dbHost, $dbPort, $dbName, $dbUser, $dbPass)) {
        echo "ERROR: Failed to connect to database.\n";
        echo "Error: " . $adapter->getLastError() . "\n";
        exit(1);
    }
    
    echo "✓ Connected successfully!\n\n";
    
    // Check existing tables
    $connection = $adapter->getConnection();
    if ($dbType === 'postgresql') {
        $checkQuery = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'";
        $stmt = $connection->query($checkQuery);
    } else {
        $checkQuery = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?";
        $stmt = $connection->prepare($checkQuery);
        $stmt->execute([$dbName]);
    }
    $tableCount = $stmt->fetchColumn();
    
    if ($tableCount > 0) {
        echo "WARNING: Database already contains $tableCount table(s).\n";
        echo "Continue anyway? (yes/no): ";
        $answer = trim(fgets(STDIN));
        if (strtolower($answer) !== 'yes') {
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
                echo "Using PostgreSQL SQL file.\n";
            } else {
                echo "ERROR: PostgreSQL SQL file exists but is incomplete.\n";
                echo "Please convert database.mysql.sql to PostgreSQL format first.\n";
                exit(1);
            }
        } else {
            echo "ERROR: PostgreSQL SQL file not found.\n";
            echo "Please convert database.mysql.sql to PostgreSQL format first.\n";
            exit(1);
        }
    }
    
    if (!file_exists($sqlFile)) {
        echo "ERROR: SQL file not found: $sqlFile\n";
        exit(1);
    }
    
    echo "Importing SQL file: " . basename($sqlFile) . "\n";
    echo "This may take a few minutes...\n\n";
    
    $startTime = microtime(true);
    $result = $adapter->importSQL($sqlFile);
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    if ($result['success']) {
        echo "✓ Database schema imported successfully!\n";
        echo "  Time taken: {$duration} seconds\n\n";
        
        // Check tables created
        if ($dbType === 'postgresql') {
            $checkQuery = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'";
            $stmt = $connection->query($checkQuery);
        } else {
            $checkQuery = "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?";
            $stmt = $connection->prepare($checkQuery);
            $stmt->execute([$dbName]);
        }
        $newTableCount = $stmt->fetchColumn();
        echo "  Tables created: $newTableCount\n\n";
        
        echo "✓ Database seeding completed!\n";
        echo "\nNext steps:\n";
        echo "1. Run Laravel seeders: cd ../main && php artisan db:seed\n";
        echo "2. Or access the installer to set up admin credentials\n";
        
    } else {
        echo "ERROR: Failed to import SQL file.\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

