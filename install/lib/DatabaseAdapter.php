<?php

abstract class DatabaseAdapter
{
    protected $connection;
    protected $host;
    protected $port;
    protected $dbname;
    protected $username;
    protected $password;

    /**
     * Connect to database
     */
    abstract public function connect($host, $port, $dbname, $username, $password);

    /**
     * Import SQL file
     */
    abstract public function importSQL($sqlFile);

    /**
     * Execute a query
     */
    abstract public function executeQuery($sql);

    /**
     * Get PDO DSN string
     */
    abstract public function getDSN($host, $port, $dbname);

    /**
     * Get default port
     */
    abstract public function getDefaultPort();

    /**
     * Get driver name for Laravel
     */
    abstract public function getDriverName();

    /**
     * Test connection
     */
    public function testConnection()
    {
        try {
            if (!$this->connection) {
                return false;
            }
            $this->connection->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get connection instance
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Escape identifier (for table/column names)
     */
    abstract public function escapeIdentifier($identifier);

    /**
     * Get last error
     */
    public function getLastError()
    {
        if ($this->connection) {
            $errorInfo = $this->connection->errorInfo();
            return $errorInfo[2] ?? 'Unknown error';
        }
        return 'No connection';
    }
}

