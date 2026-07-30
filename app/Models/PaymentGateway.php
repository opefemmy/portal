<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = ['provider', 'test_public_key', 'test_secret_key', 'live_public_key', 'live_secret_key', 'is_test_mode', 'is_active'];

    protected $casts = [
        'is_test_mode' => 'boolean',
        'is_active' => 'boolean',
    ];

    const PROVIDER_FLUTTERWAVE = 'flutterwave';
    const PROVIDER_PAYSTACK = 'paystack';
    const PROVIDER_STRIPE = 'stripe';
    const PROVIDER_XPRESSPAYMENTS = 'xpresspayments';
    const PROVIDER_REMITA = 'remita';

    const PROVIDERS = [
        self::PROVIDER_FLUTTERWAVE => 'Flutterwave',
        self::PROVIDER_PAYSTACK => 'Paystack',
        self::PROVIDER_STRIPE => 'Stripe',
        self::PROVIDER_XPRESSPAYMENTS => 'XpressPayments',
        self::PROVIDER_REMITA => 'Remita',
    ];

    const PROVIDER_LOGOS = [
        self::PROVIDER_FLUTTERWAVE => 'https://flutterwave.com/images/logo-dark.png',
        self::PROVIDER_PAYSTACK => 'https://cdnjs.cloudflare.com/ajax/libs/paystack-badge/1.0.0/paystack-badge.png',
        self::PROVIDER_STRIPE => 'https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg',
        self::PROVIDER_XPRESSPAYMENTS => 'https://xpresspay.com.ng/wp-content/uploads/2023/01/Xpress-Logo-1.png',
        self::PROVIDER_REMITA => 'https://www.remita.net/wp-content/uploads/2023/04/Remita-Logo.png',
    ];

    public static function getActiveGateway()
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Get all active gateways for selection
     */
    public static function getActiveGateways()
    {
        return static::where('is_active', true)->get();
    }

    /**
     * Get all gateway providers for dropdown (both active and inactive for configuration)
     */
    public static function getEnabledProviders(): array
    {
        // Show all active gateways - even without keys for testing
        $gateways = static::where('is_active', true)->get();

        $providers = [];

        foreach ($gateways as $gateway) {
            $providers[$gateway->provider] = self::PROVIDERS[$gateway->provider] ?? ucfirst($gateway->provider);
        }

        // If no gateways configured, show all available options
        if (empty($providers)) {
            return self::PROVIDERS;
        }

        return $providers;
    }

    /**
     * Get all active gateways with their configurations
     */
    public static function getActiveGatewaysWithConfig(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->get();
    }

    /**
     * Get all available gateway providers including those without keys
     */
    public static function getAllProviders(): array
    {
        return self::PROVIDERS;
    }

    public function getPublicKey(): string
    {
        return $this->is_test_mode ? $this->test_public_key : $this->live_public_key;
    }

    public function getSecretKey(): string
    {
        return $this->is_test_mode ? $this->test_secret_key : $this->live_secret_key;
    }

    public function getBaseUrl(): string
    {
        if ($this->provider === self::PROVIDER_XPRESSPAYMENTS) {
            if ($this->is_test_mode) {
                return 'https://xpresspayonlinesandbox.xpresspayments.com:8000';
            }
            return 'https://xpresspayonline.com';
        }

        if ($this->provider === self::PROVIDER_REMITA) {
            if ($this->is_test_mode) {
                return 'https://remitademo.xpresspayments.com';
            }
            return 'https://login.remita.net';
        }

        return '';
    }

    /**
     * Get gateway logo URL
     */
    public function getLogoUrl(): string
    {
        return self::PROVIDER_LOGOS[$this->provider] ?? '';
    }

    /**
     * Get provider display name
     */
    public function getDisplayName(): string
    {
        return self::PROVIDERS[$this->provider] ?? ucfirst($this->provider);
    }
}