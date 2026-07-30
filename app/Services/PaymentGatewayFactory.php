<?php

namespace App\Services;

use App\Models\PaymentGateway;
use Exception;

/**
 * Payment Gateway Factory
 * Creates the appropriate payment gateway service based on provider
 */
class PaymentGatewayFactory
{
    /**
     * Create a payment gateway service instance
     */
    public static function create(string $provider): object
    {
        return match ($provider) {
            PaymentGateway::PROVIDER_XPRESSPAYMENTS => new XpressPaymentsService(),
            PaymentGateway::PROVIDER_PAYSTACK => new PaystackService(),
            PaymentGateway::PROVIDER_FLUTTERWAVE => new FlutterwaveService(),
            default => throw new Exception("Unsupported payment gateway: {$provider}"),
        };
    }

    /**
     * Create service from gateway model
     */
    public static function createFromGateway(PaymentGateway $gateway): object
    {
        return self::create($gateway->provider);
    }

    /**
     * Get all enabled gateway providers with their display names
     */
    public static function getEnabledProviders(): array
    {
        return PaymentGateway::getEnabledProviders();
    }
}
