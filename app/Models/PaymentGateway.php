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

    const PROVIDERS = [
        self::PROVIDER_FLUTTERWAVE => 'Flutterwave',
        self::PROVIDER_PAYSTACK => 'Paystack',
        self::PROVIDER_STRIPE => 'Stripe',
        self::PROVIDER_XPRESSPAYMENTS => 'XpressPayments',
    ];

    public static function getActiveGateway()
    {
        return static::where('is_active', true)->first();
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
        if ($this->is_test_mode) {
            return 'https://xpresspayonlinesandbox.xpresspayments.com:8000';
        }
        return 'https://xpresspayonline.com';
    }
}