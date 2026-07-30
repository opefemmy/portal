<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalWardsSeeder extends Seeder
{
    public function run()
    {
        $wards = [
            ['name' => 'General Ward', 'type' => 'general', 'total_beds' => 20, 'daily_rate' => 5000],
            ['name' => 'Male Ward', 'type' => 'general', 'total_beds' => 15, 'daily_rate' => 4500],
            ['name' => 'Female Ward', 'type' => 'general', 'total_beds' => 15, 'daily_rate' => 4500],
            ['name' => 'Private Ward A', 'type' => 'private', 'total_beds' => 5, 'daily_rate' => 15000],
            ['name' => 'Private Ward B', 'type' => 'private', 'total_beds' => 5, 'daily_rate' => 12000],
            ['name' => 'Emergency Ward', 'type' => 'emergency', 'total_beds' => 10, 'daily_rate' => 8000],
            ['name' => 'Maternity Ward', 'type' => 'maternity', 'total_beds' => 10, 'daily_rate' => 10000],
            ['name' => 'Pediatric Ward', 'type' => 'general', 'total_beds' => 12, 'daily_rate' => 6000],
            ['name' => 'ICU', 'type' => 'private', 'total_beds' => 4, 'daily_rate' => 25000],
            ['name' => 'NICU', 'type' => 'private', 'total_beds' => 4, 'daily_rate' => 20000],
        ];

        // Insert or update wards (prevent duplicates)
        foreach ($wards as $ward) {
            DB::table('hospital_wards')->updateOrInsert(
                ['name' => $ward['name']],
                [
                    'name' => $ward['name'],
                    'type' => $ward['type'],
                    'total_beds' => $ward['total_beds'],
                    'available_beds' => $ward['total_beds'],
                    'daily_rate' => $ward['daily_rate'],
                    'description' => $ward['type'] . ' ward with ' . $ward['total_beds'] . ' beds',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Create beds for each ward (only if they don't exist)
        $allWards = DB::table('hospital_wards')->get();
        foreach ($allWards as $ward) {
            // Check if beds already exist for this ward
            $existingBeds = DB::table('hospital_beds')->where('ward_id', $ward->id)->count();

            if ($existingBeds > 0) {
                continue; // Skip if beds already exist
            }

            for ($i = 1; $i <= $ward->total_beds; $i++) {
                DB::table('hospital_beds')->updateOrInsert(
                    ['ward_id' => $ward->id, 'bed_number' => 'BED-' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                    [
                        'ward_id' => $ward->id,
                        'bed_number' => 'BED-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                        'status' => 'available',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        echo "Hospital wards and beds seeded successfully!\n";
    }
}
