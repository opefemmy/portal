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

    public static function getActiveGateway()
    {
        return static::where('is_active', true)->first();
    }

    public function getPublicKey()
    {
        return $this->is_test_mode ? $this->test_public_key : $this->live_public_key;
    }

    public function getSecretKey()
    {
        return $this->is_test_mode ? $this->test_secret_key : $this->live_secret_key;
    }
}