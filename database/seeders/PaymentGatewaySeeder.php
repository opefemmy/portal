<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            [
                'provider' => 'paystack',
                'test_public_key' => env('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxx'),
                'test_secret_key' => env('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxx'),
                'live_public_key' => env('PAYSTACK_LIVE_PUBLIC_KEY', ''),
                'live_secret_key' => env('PAYSTACK_LIVE_SECRET_KEY', ''),
                'is_test_mode' => true,
                'is_active' => true, // Enable by default
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'xpresspayments',
                'test_public_key' => env('XPRESSPAYMENTS_PUBLIC_KEY', ''),
                'test_secret_key' => env('XPRESSPAYMENTS_SECRET_KEY', ''),
                'live_public_key' => env('XPRESSPAYMENTS_LIVE_PUBLIC_KEY', ''),
                'live_secret_key' => env('XPRESSPAYMENTS_LIVE_SECRET_KEY', ''),
                'is_test_mode' => true,
                'is_active' => true, // Enable by default
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'flutterwave',
                'test_public_key' => env('FLUTTERWAVE_PUBLIC_KEY', ''),
                'test_secret_key' => env('FLUTTERWAVE_SECRET_KEY', ''),
                'live_public_key' => env('FLUTTERWAVE_LIVE_PUBLIC_KEY', ''),
                'live_secret_key' => env('FLUTTERWAVE_LIVE_SECRET_KEY', ''),
                'is_test_mode' => true,
                'is_active' => true, // Enable by default
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'stripe',
                'test_public_key' => env('STRIPE_PUBLIC_KEY', ''),
                'test_secret_key' => env('STRIPE_SECRET_KEY', ''),
                'live_public_key' => env('STRIPE_LIVE_PUBLIC_KEY', ''),
                'live_secret_key' => env('STRIPE_LIVE_SECRET_KEY', ''),
                'is_test_mode' => true,
                'is_active' => false, // Disable by default
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'remita',
                'test_public_key' => env('REMITA_MERCHANT_ID', ''), // Merchant ID goes in public_key
                'test_secret_key' => env('REMITA_API_KEY', ''), // API Key goes in secret_key
                'live_public_key' => env('REMITA_LIVE_MERCHANT_ID', ''),
                'live_secret_key' => env('REMITA_LIVE_API_KEY', ''),
                'is_test_mode' => true,
                'is_active' => true, // Enable by default
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($gateways as $gateway) {
            DB::table('payment_gateways')->updateOrInsert(
                ['provider' => $gateway['provider']],
                $gateway
            );
        }

        $this->command->info('Payment gateways seeded successfully!');
    }
}
