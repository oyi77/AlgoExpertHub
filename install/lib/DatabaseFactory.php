<?php

require_once __DIR__ . '/DatabaseAdapter.php';
require_once __DIR__ . '/adapters/MySQLAdapter.php';
require_once __DIR__ . '/adapters/PostgreSQLAdapter.php';

class DatabaseFactory
{
    /**
     * Create database adapter by type
     */
    public static function createAdapter($type)
    {
        $type = strtolower($type);
        
        switch ($type) {
            case 'mysql':
            case 'mariadb':
                return new MySQLAdapter();
            case 'postgresql':
            case 'postgres':
                return new PostgreSQLAdapter();
            default:
                throw new Exception("Unsupported database type: {$type}");
        }
    }

    /**
     * Auto-detect database type by attempting connections
     */
    public static function detectDatabaseType($host, $port, $username, $password, $dbname = null)
    {
        // Try MySQL/MariaDB first (port 3306)
        $mysqlAdapter = new MySQLAdapter();
        $testPort = $port ?: 3306;
        
        // Try to connect without database first
        if ($dbname) {
            if ($mysqlAdapter->connect($host, $testPort, $dbname, $username, $password)) {
                if ($mysqlAdapter->testConnection()) {
                    return 'mysql';
                }
            }
        } else {
            // Try connecting to information_schema for MySQL
            if ($mysqlAdapter->connect($host, $testPort, 'information_schema', $username, $password)) {
                if ($mysqlAdapter->testConnection()) {
                    return 'mysql';
                }
            }
        }

        // Try PostgreSQL (port 5432)
        $pgAdapter = new PostgreSQLAdapter();
        $testPort = $port ?: 5432;
        
        if ($dbname) {
            if ($pgAdapter->connect($host, $testPort, $dbname, $username, $password)) {
                if ($pgAdapter->testConnection()) {
                    return 'postgresql';
                }
            }
        } else {
            // Try connecting to postgres database
            if ($pgAdapter->connect($host, $testPort, 'postgres', $username, $password)) {
                if ($pgAdapter->testConnection()) {
                    return 'postgresql';
                }
            }
        }

        return 'unknown';
    }

    /**
     * Test connection with given credentials
     */
    public static function testConnection($type, $host, $port, $dbname, $username, $password)
    {
        try {
            $adapter = self::createAdapter($type);
            if ($adapter->connect($host, $port, $dbname, $username, $password)) {
                return $adapter->testConnection();
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}

