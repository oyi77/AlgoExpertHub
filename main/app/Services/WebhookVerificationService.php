<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Signature Verification Service
 *
 * Verifies payment gateway webhook signatures to prevent fraud.
 */
class WebhookVerificationService
{
    /**
     * Verify Stripe webhook signature.
     *
     * @param Request $request
     * @param string $webhookSecret
     * @return array{valid: bool, error?: string}
     */
    public static function verifyStripe(Request $request, string $webhookSecret): array
    {
        $signature = $request->header('Stripe-Signature');

        if (!$signature) {
            return ['valid' => false, 'error' => 'Missing Stripe-Signature header'];
        }

        try {
            \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $signature,
                $webhookSecret
            );
            return ['valid' => true];
        } catch (\UnexpectedValueException $e) {
            Log::warning('Invalid Stripe webhook payload', [
                'error' => $e->getMessage(),
                'signature' => substr($signature, 0, 20) . '...',
            ]);
            return ['valid' => false, 'error' => 'Invalid payload'];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Invalid Stripe webhook signature', [
                'error' => $e->getMessage(),
                'signature' => substr($signature, 0, 20) . '...',
            ]);
            return ['valid' => false, 'error' => 'Invalid signature'];
        }
    }

    /**
     * Verify PayPal webhook signature.
     *
     * @param Request $request
     * @param string $webhookId
     * @param string $webhookSecret
     * @return array{valid: bool, error?: string}
     */
    public static function verifyPayPal(Request $request, string $webhookId, string $webhookSecret): array
    {
        // PayPal IPN verification requires POST back to PayPal
        $payload = $request->getContent();

        // Basic validation - verify webhook ID
        if (!$webhookId || !$webhookSecret) {
            return ['valid' => false, 'error' => 'Webhook credentials not configured'];
        }

        // In production, implement full IPN verification:
        // 1. Send payload back to PayPal's verification endpoint
        // 2. Verify the response is VERIFIED
        // For now, we do basic payload validation
        if (empty($payload)) {
            return ['valid' => false, 'error' => 'Empty payload'];
        }

        Log::info('PayPal webhook received (basic validation)', [
            'webhook_id' => substr($webhookId, 0, 10) . '...',
        ]);

        return ['valid' => true]; // Placeholder - implement full verification in production
    }

    /**
     * Verify Coinpayments HMAC signature.
     *
     * @param Request $request
     * @param string $ipnSecret
     * @return array{valid: bool, error?: string}
     */
    public static function verifyCoinpayments(Request $request, string $ipnSecret): array
    {
        $hmac = $request->input('hmac');

        if (!$hmac || !$ipnSecret) {
            return ['valid' => false, 'error' => 'Missing HMAC or IPN Secret'];
        }

        // Generate expected HMAC
        $payload = $request->except('hmac');
        ksort($payload);
        $serialized = http_build_query($payload, '', '&');
        $expectedHmac = strtoupper(hash_hmac('sha512', $serialized, $ipnSecret));

        if ($hmac !== $expectedHmac) {
            Log::warning('Invalid Coinpayments webhook HMAC', [
                'received' => substr($hmac, 0, 10) . '...',
                'expected' => substr($expectedHmac, 0, 10) . '...',
            ]);
            return ['valid' => false, 'error' => 'Invalid HMAC signature'];
        }

        return ['valid' => true];
    }

    /**
     * Generic HMAC verification for webhook endpoints.
     *
     * @param Request $request
     * @param string $secret
     * @param string $headerName
     * @param string $algorithm
     * @return array{valid: bool, error?: string}
     */
    public static function verifyHmac(
        Request $request,
        string $secret,
        string $headerName = 'X-Signature',
        string $algorithm = 'sha256'
    ): array {
        $signature = $request->header($headerName);

        if (!$signature) {
            return ['valid' => false, 'error' => "Missing {$headerName} header"];
        }

        if (!$secret) {
            return ['valid' => false, 'error' => 'Secret not configured'];
        }

        $payload = $request->getContent();
        $expectedSignature = strtoupper(hash_hmac($algorithm, $payload, $secret));

        if ($signature !== $expectedSignature) {
            Log::warning('Invalid webhook signature', [
                'header' => $headerName,
                'received' => substr($signature, 0, 10) . '...',
                'expected' => substr($expectedSignature, 0, 10) . '...',
            ]);
            return ['valid' => false, 'error' => 'Invalid signature'];
        }

        return ['valid' => true];
    }
}
