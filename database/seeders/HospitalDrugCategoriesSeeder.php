<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalDrugCategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Analgesics', 'code' => 'ANA', 'description' => 'Pain relievers and anti-inflammatory drugs'],
            ['name' => 'Antibiotics', 'code' => 'ANT', 'description' => 'Antibacterial medications'],
            ['name' => 'Antipyretics', 'code' => 'APY', 'description' => 'Fever reducing medications'],
            ['name' => 'Antivirals', 'code' => 'AVL', 'description' => 'Antiviral medications'],
            ['name' => 'Antihistamines', 'code' => 'AHN', 'description' => 'Allergy medications'],
            ['name' => 'Cardiovascular', 'code' => 'CAR', 'description' => 'Heart and blood pressure medications'],
            ['name' => 'Gastrointestinal', 'code' => 'GAS', 'description' => 'Stomach and digestive medications'],
            ['name' => 'Respiratory', 'code' => 'RES', 'description' => 'Breathing and lung medications'],
            ['name' => 'Vitamins & Supplements', 'code' => 'VIT', 'description' => 'Vitamins and nutritional supplements'],
            ['name' => 'First Aid', 'code' => 'FAD', 'description' => 'First aid and emergency supplies'],
            ['name' => 'Diabetes', 'code' => 'DIA', 'description' => 'Diabetes management medications'],
            ['name' => 'Neurology', 'code' => 'NEU', 'description' => 'Brain and nervous system medications'],
        ];

        foreach ($categories as $category) {
            DB::table('hospital_drug_categories')->updateOrInsert(
                ['code' => $category['code']],
                [
                    'name' => $category['name'],
                    'code' => $category['code'],
                    'description' => $category['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        echo "Drug categories seeded successfully!\n";
    }
}
