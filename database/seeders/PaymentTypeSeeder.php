<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $paymentTypes = [
            [
                'name' => 'Application Fee',
                'code' => 'APP_FEE',
                'description' => 'Fee for submitting admission application',
                'amount' => 5000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 1,
            ],
            [
                'name' => 'Acceptance Fee',
                'code' => 'ACCEPT_FEE',
                'description' => 'Fee to accept admission offer',
                'amount' => 25000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 2,
            ],
            [
                'name' => 'School Fee',
                'code' => 'SCHOOL_FEE',
                'description' => 'Tuition and other school fees',
                'amount' => 50000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 3,
            ],
            [
                'name' => 'Hostel Fee',
                'code' => 'HOSTEL_FEE',
                'description' => 'Fee for hostel accommodation',
                'amount' => 25000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 4,
            ],
        ];

        foreach ($paymentTypes as $type) {
            PaymentType::create($type);
        }

        $this->command->info('Created ' . count($paymentTypes) . ' payment types.');
    }
}
