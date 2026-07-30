<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalSuppliersSeeder extends Seeder
{
    public function run()
    {
        $suppliers = [
            ['name' => 'MedPharm Nigeria Ltd', 'code' => 'SUP001', 'contact_person' => 'Mr. Adebayo', 'phone' => '08012345678', 'email' => 'info@medpharm.com'],
            ['name' => 'HealthCare Distributors', 'code' => 'SUP002', 'contact_person' => 'Mrs. Folake', 'phone' => '08023456789', 'email' => 'sales@healthcaredist.com'],
            ['name' => 'PharmaPlus Ltd', 'code' => 'SUP003', 'contact_person' => 'Dr. Chukwuemeka', 'phone' => '08034567890', 'email' => 'contact@pharmaplus.com'],
            ['name' => 'Global Medical Supplies', 'code' => 'SUP004', 'contact_person' => 'Mr. Okonkwo', 'phone' => '08045678901', 'email' => 'orders@globalmed.com'],
            ['name' => 'Nigerian Pharmaceutical Co', 'code' => 'SUP005', 'contact_person' => 'Mrs. Adaeze', 'phone' => '08056789012', 'email' => 'info@nigerpharm.com'],
            ['name' => 'FirstCare Medical', 'code' => 'SUP006', 'contact_person' => 'Mr. Tunde', 'phone' => '08067890123', 'email' => 'supply@firstcaremed.com'],
            ['name' => 'Alpha Pharm Ltd', 'code' => 'SUP007', 'contact_person' => 'Dr. Ngozi', 'phone' => '08078901234', 'email' => 'orders@alphapharm.com'],
            ['name' => 'Vital Health Supplies', 'code' => 'SUP008', 'contact_person' => 'Mr. Emeka', 'phone' => '08089012345', 'email' => 'sales@vitalhealth.com'],
        ];

        foreach ($suppliers as $supplier) {
            DB::table('hospital_suppliers')->updateOrInsert(
                ['code' => $supplier['code']],
                [
                    'name' => $supplier['name'],
                    'code' => $supplier['code'],
                    'contact_person' => $supplier['contact_person'],
                    'phone' => $supplier['phone'],
                    'email' => $supplier['email'],
                    'address' => 'Lagos, Nigeria',
                    'bank_name' => 'First Bank of Nigeria',
                    'account_number' => '1234567890',
                    'account_name' => $supplier['name'],
                    'notes' => 'Reliable supplier with fast delivery',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        echo "Suppliers seeded successfully!\n";
    }
}
