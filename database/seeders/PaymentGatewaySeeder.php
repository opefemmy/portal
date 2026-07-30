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
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'xpresspayments',
                'test_public_key' => env('XPRESSPAYMENTS_PUBLIC_KEY', 'pk_test_xpress'),
                'test_secret_key' => env('XPRESSPAYMENTS_SECRET_KEY', 'sk_test_xpress'),
                'live_public_key' => env('XPRESSPAYMENTS_LIVE_PUBLIC_KEY', ''),
                'live_secret_key' => env('XPRESSPAYMENTS_LIVE_SECRET_KEY', ''),
                'is_test_mode' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'flutterwave',
                'test_public_key' => env('FLUTTERWAVE_PUBLIC_KEY', 'pk_test_flutterwave'),
                'test_secret_key' => env('FLUTTERWAVE_SECRET_KEY', 'sk_test_flutterwave'),
                'live_public_key' => env('FLUTTERWAVE_LIVE_PUBLIC_KEY', ''),
                'live_secret_key' => env('FLUTTERWAVE_LIVE_SECRET_KEY', ''),
                'is_test_mode' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'stripe',
                'test_public_key' => env('STRIPE_PUBLIC_KEY', 'pk_test_stripe'),
                'test_secret_key' => env('STRIPE_SECRET_KEY', 'sk_test_stripe'),
                'live_public_key' => env('STRIPE_LIVE_PUBLIC_KEY', ''),
                'live_secret_key' => env('STRIPE_LIVE_SECRET_KEY', ''),
                'is_test_mode' => true,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider' => 'remita',
                'test_public_key' => env('REMITA_MERCHANT_ID', '2547916'), // Demo merchant ID
                'test_secret_key' => env('REMITA_API_KEY', '1946'), // Demo API key
                'live_public_key' => env('REMITA_LIVE_MERCHANT_ID', ''),
                'live_secret_key' => env('REMITA_LIVE_API_KEY', ''),
                'is_test_mode' => true,
                'is_active' => true,
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
