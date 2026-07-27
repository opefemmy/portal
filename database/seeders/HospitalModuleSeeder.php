<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HospitalModuleSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Seeding Hospital Module...');

        // Order matters due to foreign key constraints
        $this->call([
            HospitalWardsSeeder::class,
            HospitalStaffSeeder::class,
            HospitalDrugCategoriesSeeder::class,
            HospitalDrugsSeeder::class,
            HospitalSuppliersSeeder::class,
            HospitalServiceTypesSeeder::class,
        ]);

        $this->command->info('Hospital Module seeded successfully!');
    }
}
