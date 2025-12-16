<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * LogRotationService
 * 
 * Rotates log files to keep only the last N lines, preventing log files from growing too large
 */
class LogRotationService
{
    /**
     * Maximum lines to keep in log files
     */
    protected int $maxLines = 1000;

    /**
     * Rotate log file if it exceeds max lines
     * 
     * @param string $logPath Path to log file
     * @param int|null $maxLines Maximum lines to keep (default: 1000)
     * @return bool True if rotation occurred, false otherwise
     */
    public function rotateIfNeeded(string $logPath, ?int $maxLines = null): bool
    {
        if (!File::exists($logPath)) {
            return false;
        }

        $maxLines = $maxLines ?? $this->maxLines;
        
        // Count lines in file
        $lineCount = $this->countLines($logPath);
        
        if ($lineCount <= $maxLines) {
            return false;
        }

        // Read all lines
        $lines = File::lines($logPath);
        $allLines = iterator_to_array($lines);
        
        // Keep only the last N lines
        $keptLines = array_slice($allLines, -$maxLines);
        
        // Write back to file
        File::put($logPath, implode("\n", $keptLines) . "\n");
        
        return true;
    }

    /**
     * Count lines in a file efficiently
     * 
     * @param string $filePath
     * @return int
     */
    protected function countLines(string $filePath): int
    {
        $count = 0;
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            return 0;
        }
        
        while (!feof($handle)) {
            $chunk = fread($handle, 8192);
            $count += substr_count($chunk, "\n");
        }
        
        fclose($handle);
        
        return $count;
    }

    /**
     * Rotate log file before writing (wrapper for file_put_contents)
     * 
     * @param string $logPath Path to log file
     * @param string $data Data to append
     * @param int $flags File flags (default: FILE_APPEND | LOCK_EX)
     * @param int|null $maxLines Maximum lines to keep
     * @return int|false Bytes written or false on failure
     */
    public function appendWithRotation(string $logPath, string $data, int $flags = FILE_APPEND | LOCK_EX, ?int $maxLines = null): int|false
    {
        // Rotate if needed before appending
        $this->rotateIfNeeded($logPath, $maxLines);
        
        // Append new data
        return file_put_contents($logPath, $data, $flags);
    }

    /**
     * Set maximum lines to keep
     * 
     * @param int $maxLines
     * @return self
     */
    public function setMaxLines(int $maxLines): self
    {
        $this->maxLines = $maxLines;
        return $this;
    }

    /**
     * Get maximum lines to keep
     * 
     * @return int
     */
    public function getMaxLines(): int
    {
        return $this->maxLines;
    }
}
