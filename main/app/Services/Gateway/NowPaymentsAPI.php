<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use Exception;

class NowPaymentsAPI
{
    private $session;
    private string $token;

    public const API_BASE = 'https://api.nowpayments.io/v1/';

    public function __construct(string $token)
    {
        if (empty($token)) {
            throw new Exception('API key is not specified');
        }
        
        $this->token = $token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $this->session = $ch;
    }

    private function call(string $method, string $endpoint, $data = []): string|bool
    {
        $ch = $this->session;

        switch ($method) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-KEY: ' . $this->token]);
                if (!empty($data)) {
                    if (is_array($data)) {
                        $parameters = http_build_query($data);
                        curl_setopt($ch, CURLOPT_URL, self::API_BASE . $endpoint . '?' . $parameters);
                    } else {
                        if ($endpoint === 'payment') {
                            curl_setopt($ch, CURLOPT_URL, self::API_BASE . $endpoint . '/' . $data);
                        }
                    }
                } else {
                    curl_setopt($ch, CURLOPT_URL, self::API_BASE . $endpoint);
                }
                break;

            case 'POST':
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-KEY: ' . $this->token, 'Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt($ch, CURLOPT_URL, self::API_BASE . $endpoint);
                break;
        }

        return curl_exec($ch);
    }

    public function status(): string|bool
    {
        return $this->call('GET', 'status');
    }

    public function getCurrencies(): string|bool
    {
        return $this->call('GET', 'currencies');
    }

    /**
     * @param array $params Array of options
     */
    public function getEstimatePrice(array $params): string|bool
    {
        return $this->call('GET', 'estimate', $params);
    }

    /**
     * @param array $params Array of options
     */
    public function createPayment(array $params): string|bool
    {
        return $this->call('POST', 'payment', $params);
    }

    /**
     * @param int $paymentID Required. ID of created payment
     */
    public function getPaymentStatus(int $paymentID): string|bool
    {
        return $this->call('GET', 'payment', $paymentID);
    }

    /**
     * @param array $params Array of options
     */
    public function getMinimumPaymentAmount(array $params): string|bool
    {
        return $this->call('GET', 'min-amount', $params);
    }

    /**
     * @param array $params Array of options
     */
    public function getListPayments(array $params = []): string|bool
    {
        return $this->call('GET', 'payment', $params);
    }

    /**
     * @param array $params Array of options
     */
    public function createInvoice(array $params): string|bool
    {
        return $this->call('POST', 'invoice', $params);
    }

    public function __destruct()
    {
        if (is_resource($this->session)) {
            curl_close($this->session);
        }
    }
}

 ?>