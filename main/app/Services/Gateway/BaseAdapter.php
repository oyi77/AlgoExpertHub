<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Helpers\Helper\Helper;
use Illuminate\Support\Facades\Log;

abstract class BaseAdapter
{
    /**
     * Standard success response
     * 
     * @param string $message
     * @param array $data
     * @return array
     */
    protected function success(string $message, array $data = []): array
    {
        return [
            'type' => 'success',
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Standard error response
     * 
     * @param string $message
     * @param array $errors
     * @param int $code
     * @return array
     */
    protected function error(string $message, array $errors = [], int $code = 400): array
    {
        return [
            'type' => 'error',
            'message' => $message,
            'errors' => $errors,
            'code' => $code
        ];
    }

    /**
     * Log adapter operation
     * 
     * @param string $operation
     * @param array $context
     * @return void
     */
    protected function log(string $operation, array $context = []): void
    {
        Log::info('Gateway Adapter: ' . static::class, [
            'operation' => $operation,
            'context' => $context
        ]);
    }

    /**
     * Standard payment success handling
     * 
     * @param mixed $deposit
     * @param float $fee
     * @param string $transaction
     * @return void
     */
    protected function handlePaymentSuccess($deposit, float $fee, string $transaction): void
    {
        Helper::paymentSuccess($deposit, $fee, $transaction);
    }
}
