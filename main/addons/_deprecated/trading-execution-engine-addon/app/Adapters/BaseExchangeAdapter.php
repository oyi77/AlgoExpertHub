<?php

namespace Addons\TradingExecutionEngine\App\Adapters;

use Addons\TradingExecutionEngine\App\Contracts\ExchangeAdapterInterface;
use Addons\TradingExecutionEngine\App\Models\ExecutionConnection;
use Illuminate\Support\Facades\Log;

abstract class BaseExchangeAdapter implements ExchangeAdapterInterface
{
    protected ExecutionConnection $connection;

    protected array $credentials;

    protected bool $connected = false;

    public function __construct(ExecutionConnection $connection)
    {
        $this->connection = $connection;
        $this->credentials = $connection->credentials ?? [];
    }

    protected function getCredential(string $key, $default = null)
    {
        return $this->credentials[$key] ?? $default;
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("Exchange Adapter Error [{$this->getExchangeName()}]: {$message}", array_merge([
            'connection_id' => $this->connection->id,
            'connection_name' => $this->connection->name,
            'exchange_name' => $this->connection->exchange_name,
        ], $context));
    }

    protected function logInfo(string $message, array $context = []): void
    {
        Log::info("Exchange Adapter [{$this->getExchangeName()}]: {$message}", array_merge([
            'connection_id' => $this->connection->id,
        ], $context));
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getConnection(): ExecutionConnection
    {
        return $this->connection;
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    protected function rateLimit(int $seconds): void
    {
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

    /**
     * Get exchange name identifier.
     */
    abstract public function getExchangeName(): string;

    /**
     * Validate credentials format.
     */
    abstract public function validateCredentials(array $credentials): bool;
}

