<?php

require_once __DIR__ . '/../DatabaseAdapter.php';

class PostgreSQLAdapter extends DatabaseAdapter
{
    public function connect($host, $port, $dbname, $username, $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;

        try {
            $dsn = $this->getDSN($host, $port, $dbname);
            $this->connection = new PDO($dsn, $username, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            $this->connection = null;
            return false;
        }
    }

    public function importSQL($sqlFile)
    {
        if (!file_exists($sqlFile)) {
            return ['success' => false, 'error' => 'SQL file not found'];
        }

        try {
            $sql = file_get_contents($sqlFile);
            
            // Remove MySQL-specific comments
            $sql = preg_replace('/^--.*$/m', '', $sql);
            $sql = preg_replace('/^\/\*.*?\*\//ms', '', $sql);
            
            // Split by semicolon but preserve within quotes
            $queries = [];
            $currentQuery = '';
            $inQuotes = false;
            $quoteChar = null;
            
            for ($i = 0; $i < strlen($sql); $i++) {
                $char = $sql[$i];
                
                if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    if (!$inQuotes) {
                        $inQuotes = true;
                        $quoteChar = $char;
                    } elseif ($char === $quoteChar) {
                        $inQuotes = false;
                        $quoteChar = null;
                    }
                }
                
                $currentQuery .= $char;
                
                if (!$inQuotes && $char === ';') {
                    $query = trim($currentQuery);
                    if (!empty($query)) {
                        $queries[] = $query;
                    }
                    $currentQuery = '';
                }
            }
            
            // Execute queries in transaction
            $this->connection->beginTransaction();
            
            foreach ($queries as $query) {
                if (empty(trim($query))) {
                    continue;
                }
                $this->connection->exec($query);
            }
            
            $this->connection->commit();
            return ['success' => true];
            
        } catch (PDOException $e) {
            if ($this->connection && $this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function executeQuery($sql)
    {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getDSN($host, $port, $dbname)
    {
        return "pgsql:host={$host};port={$port};dbname={$dbname}";
    }

    public function getDefaultPort()
    {
        return 5432;
    }

    public function getDriverName()
    {
        return 'pgsql';
    }

    public function escapeIdentifier($identifier)
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}

