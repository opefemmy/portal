<?php

namespace Database\Seeders;

use App\Models\ExternalPayment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExternalPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $payments = [
            // Completed payments - ready to use
            [
                'transaction_id' => 'TXN-' . strtoupper(Str::random(8)),
                'applicant_name' => 'John Adebayo',
                'email' => 'john.adebayo@example.com',
                'amount' => 5000.00,
                'payment_date' => now()->subDays(2),
                'payment_status' => 'completed',
                'payment_channel' => 'card',
                'is_used' => false,
            ],
            [
                'transaction_id' => 'TXN-' . strtoupper(Str::random(8)),
                'applicant_name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
                'amount' => 5000.00,
                'payment_date' => now()->subDays(1),
                'payment_status' => 'completed',
                'payment_channel' => 'bank_transfer',
                'is_used' => false,
            ],
            [
                'transaction_id' => 'TXN-' . strtoupper(Str::random(8)),
                'applicant_name' => 'Michael Oluwafemi',
                'email' => 'michael.oluwafemi@example.com',
                'amount' => 5000.00,
                'payment_date' => now()->subHours(12),
                'payment_status' => 'completed',
                'payment_channel' => 'USSd',
                'is_used' => false,
            ],
            [
                'transaction_id' => 'TXN-' . strtoupper(Str::random(8)),
                'applicant_name' => 'Grace Adedoyin',
                'email' => 'grace.adedoyin@example.com',
                'amount' => 5000.00,
                'payment_date' => now()->subHours(6),
                'payment_status' => 'completed',
                'payment_channel' => 'card',
                'is_used' => false,
            ],
            [
                'transaction_id' => 'TXN-' . strtoupper(Str::random(8)),
                'applicant_name' => 'David Oladipo',
                'email' => 'david.oladipo@example.com',
                'amount' => 5000.00,
                'payment_date' => now()->subHours(3),
                'payment_status' => 'completed',
                'payment_channel' => 'bank_transfer',
                'is_used' => false,
            ],
            // Already used payment (simulating a previously validated transaction)
            [
                'transaction_id' => 'TXN-USED-001',
                'applicant_name' => 'Emma Williams',
                'email' => 'emma.williams@example.com',
                'amount' => 5000.00,
                'payment_date' => now()->subDays(5),
                'payment_status' => 'completed',
                'payment_channel' => 'card',
                'is_used' => true,
                // applicant_id will be set when validated
            ],
            // Pending payments
            [
                'transaction_id' => 'TXN-PENDING-01',
                'applicant_name' => 'James Oyedele',
                'email' => 'james.oyedele@example.com',
                'amount' => 5000.00,
                'payment_date' => now()->subHours(1),
                'payment_status' => 'pending',
                'payment_channel' => 'bank_transfer',
                'is_used' => false,
            ],
            // Failed payment
            [
                'transaction_id' => 'TXN-FAILED-01',
                'applicant_name' => 'Patricia Adekunle',
                'email' => 'patricia.adekunle@example.com',
                'amount' => 2000.00,
                'payment_date' => now()->subDays(3),
                'payment_status' => 'failed',
                'payment_channel' => 'card',
                'is_used' => false,
            ],
            // Another unused payment with different amount (for testing validation)
            [
                'transaction_id' => 'TXN-LOW-AMT-01',
                'applicant_name' => 'Robert Bakare',
                'email' => 'robert.bakare@example.com',
                'amount' => 2000.00,
                'payment_date' => now()->subDays(4),
                'payment_status' => 'completed',
                'payment_channel' => 'card',
                'is_used' => false,
            ],
        ];

        foreach ($payments as $payment) {
            ExternalPayment::create($payment);
        }

        $this->command->info('Created ' . count($payments) . ' external payment records.');
    }
}
