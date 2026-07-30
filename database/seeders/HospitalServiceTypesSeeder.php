<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalServiceTypesSeeder extends Seeder
{
    public function run()
    {
        $services = [
            // Registration
            ['name' => 'Hospital Registration Card', 'category' => 'Registration', 'amount' => 500, 'requires_appointment' => false],
            ['name' => 'Medical Record', 'category' => 'Registration', 'amount' => 200, 'requires_appointment' => false],

            // Consultation
            ['name' => 'General Consultation', 'category' => 'Consultation', 'amount' => 1000, 'requires_appointment' => true],
            ['name' => 'Specialist Consultation', 'category' => 'Consultation', 'amount' => 2500, 'requires_appointment' => true],
            ['name' => 'Emergency Consultation', 'category' => 'Consultation', 'amount' => 1500, 'requires_appointment' => false],

            // Laboratory
            ['name' => 'Malaria Test', 'category' => 'Laboratory', 'amount' => 500, 'requires_appointment' => false],
            ['name' => 'Typhoid Test', 'category' => 'Laboratory', 'amount' => 800, 'requires_appointment' => false],
            ['name' => 'HIV Test', 'category' => 'Laboratory', 'amount' => 1000, 'requires_appointment' => false],
            ['name' => 'Blood Group Test', 'category' => 'Laboratory', 'amount' => 500, 'requires_appointment' => false],
            ['name' => 'Blood Sugar Test', 'category' => 'Laboratory', 'amount' => 400, 'requires_appointment' => false],
            ['name' => 'Full Blood Count (CBC)', 'category' => 'Laboratory', 'amount' => 2000, 'requires_appointment' => false],
            ['name' => 'Urinalysis', 'category' => 'Laboratory', 'amount' => 500, 'requires_appointment' => false],
            ['name' => 'Stool Analysis', 'category' => 'Laboratory', 'amount' => 500, 'requires_appointment' => false],
            ['name' => 'Liver Function Test', 'category' => 'Laboratory', 'amount' => 3500, 'requires_appointment' => false],
            ['name' => 'Kidney Function Test', 'category' => 'Laboratory', 'amount' => 3500, 'requires_appointment' => false],

            // Pharmacy
            ['name' => 'First Aid Treatment', 'category' => 'Pharmacy', 'amount' => 300, 'requires_appointment' => false],
            ['name' => 'Dressing', 'category' => 'Pharmacy', 'amount' => 500, 'requires_appointment' => false],
            ['name' => 'Injection', 'category' => 'Pharmacy', 'amount' => 200, 'requires_appointment' => false],
            ['name' => 'Infusion (IV)', 'category' => 'Pharmacy', 'amount' => 800, 'requires_appointment' => false],
            ['name' => 'Catheterization', 'category' => 'Pharmacy', 'amount' => 1000, 'requires_appointment' => false],
            ['name' => 'Wound Suturing', 'category' => 'Pharmacy', 'amount' => 2000, 'requires_appointment' => false],
            ['name' => 'Nebulization', 'category' => 'Pharmacy', 'amount' => 800, 'requires_appointment' => false],

            // Radiology
            ['name' => 'X-Ray (Plain)', 'category' => 'Radiology', 'amount' => 2500, 'requires_appointment' => true],
            ['name' => 'X-Ray (Contrast)', 'category' => 'Radiology', 'amount' => 5000, 'requires_appointment' => true],
            ['name' => 'Ultrasound Scan', 'category' => 'Radiology', 'amount' => 3000, 'requires_appointment' => true],
            ['name' => 'ECG', 'category' => 'Radiology', 'amount' => 2000, 'requires_appointment' => false],

            // Admission
            ['name' => 'Bed Charge (Per Day)', 'category' => 'Admission', 'amount' => 3000, 'requires_appointment' => false],
            ['name' => 'Admission Fee', 'category' => 'Admission', 'amount' => 5000, 'requires_appointment' => false],
            ['name' => 'Nursing Care (Per Day)', 'category' => 'Admission', 'amount' => 1500, 'requires_appointment' => false],

            // Others
            ['name' => 'Ambulance Service', 'category' => 'Others', 'amount' => 5000, 'requires_appointment' => false],
            ['name' => 'Physiotherapy Session', 'category' => 'Others', 'amount' => 2000, 'requires_appointment' => true],
            ['name' => 'Dental Checkup', 'category' => 'Others', 'amount' => 1000, 'requires_appointment' => true],
            ['name' => 'Eye Test', 'category' => 'Others', 'amount' => 800, 'requires_appointment' => true],
        ];

        foreach ($services as $service) {
            DB::table('hospital_service_types')->updateOrInsert(
                ['name' => $service['name'], 'category' => $service['category']],
                [
                    'name' => $service['name'],
                    'category' => $service['category'],
                    'amount' => $service['amount'],
                    'is_active' => true,
                    'requires_appointment' => $service['requires_appointment'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        echo 'Hospital service types seeded successfully!';
    }
}
