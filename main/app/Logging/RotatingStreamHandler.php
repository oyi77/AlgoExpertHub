<?php

namespace App\Logging;

use Monolog\Handler\StreamHandler;
use App\Services\LogRotationService;

/**
 * RotatingStreamHandler
 * 
 * Monolog handler that automatically rotates log files before writing
 */
class RotatingStreamHandler extends StreamHandler
{
    protected LogRotationService $rotationService;
    protected int $maxLines;
    protected int $writeCount = 0;
    protected int $rotationCheckInterval = 100; // Check every 100 writes
    protected string $logPath;

    public function __construct($stream, $level = 200, bool $bubble = true, ?int $filePermission = null, bool $useLocking = false, int $maxLines = 1000)
    {
        parent::__construct($stream, $level, $bubble, $filePermission, $useLocking);
        $this->rotationService = app(LogRotationService::class);
        $this->maxLines = $maxLines;
        // Store log path (we always pass string paths, not resources)
        $this->logPath = is_string($stream) ? $stream : '';
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        $this->writeCount++;
        
        // Check rotation periodically (not on every write for performance)
        if ($this->writeCount % $this->rotationCheckInterval === 0 && !empty($this->logPath)) {
            $this->rotationService->rotateIfNeeded($this->logPath, $this->maxLines);
        }
        
        parent::write($record);
    }

    /**
     * Set rotation check interval
     * 
     * @param int $interval Check rotation every N writes
     * @return self
     */
    public function setRotationCheckInterval(int $interval): self
    {
        $this->rotationCheckInterval = $interval;
        return $this;
    }
}
