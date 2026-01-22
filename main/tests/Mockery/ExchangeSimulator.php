<?php

declare(strict_types=1);

namespace Tests\Mockery;

/**
 * Exchange simulator for testing without real API calls.
 */
interface ExchangeSimulatorInterface
{
    public function setBalance(string $asset, float $amount): self;
    public function placeOrder(array $params): OrderResult;
    public function getBalance(string $asset): float;
    public function getOrder(string $orderId): ?OrderResult;
}

/**
 * Simple order result DTO.
 */
class OrderResult
{
    public function __construct(
        public string $orderId,
        public string $status,
        public float $price = 0.0,
        public float $quantity = 0.0
    ) {}
}

/**
 * In-memory exchange simulator.
 */
class ExchangeSimulator implements ExchangeSimulatorInterface
{
    private array $balances = [];
    private array $orders = [];
    
    public function setBalance(string $asset, float $amount): self
    {
        $this->balances[$asset] = $amount;
        return $this;
    }
    
    public function getBalance(string $asset): float
    {
        return $this->balances[$asset] ?? 0.0;
    }
    
    public function placeOrder(array $params): OrderResult
    {
        $orderId = strtoupper(bin2hex(random_bytes(8)));
        
        $order = [
            'id' => $orderId,
            'symbol' => $params['symbol'],
            'side' => $params['type'] ?? 'market',
            'type' => $params['type'] ?? 'market',
            'quantity' => $params['quantity'],
            'price' => $params['price'] ?? 0.0,
            'status' => 'open',
        ];
        
        $this->orders[$orderId] = $order;
        
        return new OrderResult(
            orderId: $orderId,
            status: 'open',
            price: $order['price'],
            quantity: $order['quantity']
        );
    }
    
    public function getOrder(string $orderId): ?OrderResult
    {
        if (!isset($this->orders[$orderId])) {
            return null;
        }
        
        $order = $this->orders[$orderId];
        return new OrderResult(
            orderId: $order['id'],
            status: $order['status'],
            price: $order['price'],
            quantity: $order['quantity']
        );
    }
}
