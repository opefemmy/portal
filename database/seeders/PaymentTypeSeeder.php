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
                'name' => 'Application Form Fee',
                'code' => 'APP_FORM',
                'description' => 'Fee for purchasing application form',
                'amount' => 5000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 1,
                'purpose' => 'application',
                'audience' => PaymentType::AUDIENCE_APPLICANT,
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
                'purpose' => 'acceptance',
                'audience' => PaymentType::AUDIENCE_APPLICANT,
            ],
            [
                // Compulsory fee — distinct from School Fees so admins can
                // configure / report them independently. Paying this row
                // marks the applicant as admitted and migrates them into
                // the Student table because PURPOSE_COMPULSORY is in
                // ApplicantPaymentService::isMigrationTrigger().
                'name' => 'Compulsory Fee',
                'code' => 'COMP_FEE',
                'description' => 'Compulsory fee paid to migrate an admitted applicant into the student portal',
                'amount' => 100000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 3,
                'purpose' => 'compulsory',
                'audience' => PaymentType::AUDIENCE_APPLICANT,
            ],
            [
                'name' => 'School Fees',
                'code' => 'SCHOOL_FEE',
                'description' => 'Tuition and other school fees',
                'amount' => 50000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 4,
                'purpose' => 'school_fees',
                // School fee is charged to applicants (compulsory, to migrate to
                // the student portal) AND to returning students each session.
                'audience' => PaymentType::AUDIENCE_BOTH,
            ],
            [
                'name' => 'Hostel Fee',
                'code' => 'HOSTEL_FEE',
                'description' => 'Fee for hostel accommodation',
                'amount' => 25000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 5,
                'purpose' => 'hostel',
                'audience' => PaymentType::AUDIENCE_STUDENT,
            ],
            [
                'name' => 'Convocation Fee',
                'code' => 'CONVOCATION',
                'description' => 'Fee for convocation ceremony',
                'amount' => 10000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 6,
                'purpose' => 'other',
                'audience' => PaymentType::AUDIENCE_STUDENT,
            ],
            [
                'name' => 'Indexing Fee',
                'code' => 'INDEXING',
                'description' => 'Fee for student indexing/registration',
                'amount' => 5000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 7,
                'purpose' => 'registration',
                'audience' => PaymentType::AUDIENCE_STUDENT,
            ],
            [
                'name' => 'Registration Fee',
                'code' => 'REGISTRATION',
                'description' => 'Fee for course registration',
                'amount' => 3000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 8,
                'purpose' => 'registration',
                'audience' => PaymentType::AUDIENCE_STUDENT,
            ],
            [
                'name' => 'Result Verification Fee',
                'code' => 'RESULT_VERIFY',
                'description' => 'Fee for verifying results',
                'amount' => 1000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 9,
                'purpose' => 'other',
                'audience' => PaymentType::AUDIENCE_STUDENT,
            ],
            [
                'name' => 'Certificate Fee',
                'code' => 'CERTIFICATE',
                'description' => 'Fee for certificate collection',
                'amount' => 5000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 10,
                'purpose' => 'other',
                'audience' => PaymentType::AUDIENCE_STUDENT,
            ],
            [
                'name' => 'Library Fee',
                'code' => 'LIBRARY',
                'description' => 'Library service and fine fees',
                'amount' => 1000.00,
                'is_active' => true,
                'requires_payment' => true,
                'payment_channel' => 'external',
                'priority' => 11,
                'purpose' => 'library',
                'audience' => PaymentType::AUDIENCE_STUDENT,
            ],
        ];

        foreach ($paymentTypes as $type) {
            PaymentType::updateOrCreate(['code' => $type['code']], $type);
        }

        $this->command->info('Created ' . count($paymentTypes) . ' payment types.');
    }
}
