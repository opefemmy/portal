<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalDrugsSeeder extends Seeder
{
    public function run()
    {
        $drugs = [
            // Analgesics
            ['name' => 'Paracetamol', 'generic_name' => 'Acetaminophen', 'code' => 'DRG001', 'form' => 'Tablet', 'strength' => '500mg', 'unit' => 'tablet', 'cost_price' => 5, 'selling_price' => 10, 'category' => 'Analgesics'],
            ['name' => 'Ibuprofen', 'generic_name' => 'Ibuprofen', 'code' => 'DRG002', 'form' => 'Tablet', 'strength' => '400mg', 'unit' => 'tablet', 'cost_price' => 8, 'selling_price' => 15, 'category' => 'Analgesics'],
            ['name' => 'Aspirin', 'generic_name' => 'Acetylsalicylic Acid', 'code' => 'DRG003', 'form' => 'Tablet', 'strength' => '300mg', 'unit' => 'tablet', 'cost_price' => 3, 'selling_price' => 8, 'category' => 'Analgesics'],
            ['name' => 'Tramadol', 'generic_name' => 'Tramadol Hydrochloride', 'code' => 'DRG004', 'form' => 'Capsule', 'strength' => '50mg', 'unit' => 'capsule', 'cost_price' => 25, 'selling_price' => 40, 'category' => 'Analgesics'],
            ['name' => 'Diclofenac', 'generic_name' => 'Diclofenac Sodium', 'code' => 'DRG005', 'form' => 'Tablet', 'strength' => '50mg', 'unit' => 'tablet', 'cost_price' => 15, 'selling_price' => 25, 'category' => 'Analgesics'],

            // Antibiotics
            ['name' => 'Amoxicillin', 'generic_name' => 'Amoxicillin Trihydrate', 'code' => 'DRG006', 'form' => 'Capsule', 'strength' => '500mg', 'unit' => 'capsule', 'cost_price' => 20, 'selling_price' => 35, 'category' => 'Antibiotics'],
            ['name' => 'Ciprofloxacin', 'generic_name' => 'Ciprofloxacin Hydrochloride', 'code' => 'DRG007', 'form' => 'Tablet', 'strength' => '500mg', 'unit' => 'tablet', 'cost_price' => 30, 'selling_price' => 50, 'category' => 'Antibiotics'],
            ['name' => 'Azithromycin', 'generic_name' => 'Azithromycin Dihydrate', 'code' => 'DRG008', 'form' => 'Tablet', 'strength' => '250mg', 'unit' => 'tablet', 'cost_price' => 45, 'selling_price' => 75, 'category' => 'Antibiotics'],
            ['name' => 'Metronidazole', 'generic_name' => 'Metronidazole', 'code' => 'DRG009', 'form' => 'Tablet', 'strength' => '400mg', 'unit' => 'tablet', 'cost_price' => 15, 'selling_price' => 25, 'category' => 'Antibiotics'],
            ['name' => 'Cephalexin', 'generic_name' => 'Cephalexin Monohydrate', 'code' => 'DRG010', 'form' => 'Capsule', 'strength' => '250mg', 'unit' => 'capsule', 'cost_price' => 25, 'selling_price' => 40, 'category' => 'Antibiotics'],
            ['name' => 'Augmentin', 'generic_name' => 'Amoxicillin/Clavulanic Acid', 'code' => 'DRG011', 'form' => 'Tablet', 'strength' => '625mg', 'unit' => 'tablet', 'cost_price' => 50, 'selling_price' => 80, 'category' => 'Antibiotics'],
            ['name' => 'Gentamicin', 'generic_name' => 'Gentamicin Sulfate', 'code' => 'DRG012', 'form' => 'Injection', 'strength' => '80mg', 'unit' => 'ampoule', 'cost_price' => 35, 'selling_price' => 55, 'category' => 'Antibiotics'],

            // Antipyretics
            ['name' => 'Paracetamol Syrup', 'generic_name' => 'Acetaminophen', 'code' => 'DRG013', 'form' => 'Syrup', 'strength' => '120mg/5ml', 'unit' => 'bottle', 'cost_price' => 80, 'selling_price' => 120, 'category' => 'Antipyretics'],
            ['name' => 'Ibuprofen Syrup', 'generic_name' => 'Ibuprofen', 'code' => 'DRG014', 'form' => 'Syrup', 'strength' => '100mg/5ml', 'unit' => 'bottle', 'cost_price' => 100, 'selling_price' => 150, 'category' => 'Antipyretics'],

            // Antihistamines
            ['name' => 'Chlorpheniramine', 'generic_name' => 'Chlorpheniramine Maleate', 'code' => 'DRG015', 'form' => 'Tablet', 'strength' => '4mg', 'unit' => 'tablet', 'cost_price' => 3, 'selling_price' => 8, 'category' => 'Antihistamines'],
            ['name' => 'Loratadine', 'generic_name' => 'Loratadine', 'code' => 'DRG016', 'form' => 'Tablet', 'strength' => '10mg', 'unit' => 'tablet', 'cost_price' => 10, 'selling_price' => 20, 'category' => 'Antihistamines'],
            ['name' => 'Cetirizine', 'generic_name' => 'Cetirizine Dihydrochloride', 'code' => 'DRG017', 'form' => 'Tablet', 'strength' => '10mg', 'unit' => 'tablet', 'cost_price' => 15, 'selling_price' => 30, 'category' => 'Antihistamines'],

            // Cardiovascular
            ['name' => 'Amlodipine', 'generic_name' => 'Amlodipine Besylate', 'code' => 'DRG018', 'form' => 'Tablet', 'strength' => '5mg', 'unit' => 'tablet', 'cost_price' => 20, 'selling_price' => 35, 'category' => 'Cardiovascular'],
            ['name' => 'Lisinopril', 'generic_name' => 'Lisinopril', 'code' => 'DRG019', 'form' => 'Tablet', 'strength' => '10mg', 'unit' => 'tablet', 'cost_price' => 25, 'selling_price' => 45, 'category' => 'Cardiovascular'],
            ['name' => 'Atenolol', 'generic_name' => 'Atenolol', 'code' => 'DRG020', 'form' => 'Tablet', 'strength' => '50mg', 'unit' => 'tablet', 'cost_price' => 15, 'selling_price' => 30, 'category' => 'Cardiovascular'],
            ['name' => 'Nifedipine', 'generic_name' => 'Nifedipine', 'code' => 'DRG021', 'form' => 'Tablet', 'strength' => '10mg', 'unit' => 'tablet', 'cost_price' => 18, 'selling_price' => 32, 'category' => 'Cardiovascular'],

            // Gastrointestinal
            ['name' => 'Omeprazole', 'generic_name' => 'Omeprazole', 'code' => 'DRG022', 'form' => 'Capsule', 'strength' => '20mg', 'unit' => 'capsule', 'cost_price' => 20, 'selling_price' => 35, 'category' => 'Gastrointestinal'],
            ['name' => 'Ranitidine', 'generic_name' => 'Ranitidine Hydrochloride', 'code' => 'DRG023', 'form' => 'Tablet', 'strength' => '150mg', 'unit' => 'tablet', 'cost_price' => 12, 'selling_price' => 22, 'category' => 'Gastrointestinal'],
            ['name' => 'Antacid', 'generic_name' => 'Magnesium Hydroxide/Aluminum Hydroxide', 'code' => 'DRG024', 'form' => 'Suspension', 'strength' => '200ml', 'unit' => 'bottle', 'cost_price' => 50, 'selling_price' => 80, 'category' => 'Gastrointestinal'],
            ['name' => 'Domperidone', 'generic_name' => 'Domperidone', 'code' => 'DRG025', 'form' => 'Tablet', 'strength' => '10mg', 'unit' => 'tablet', 'cost_price' => 10, 'selling_price' => 20, 'category' => 'Gastrointestinal'],

            // Respiratory
            ['name' => 'Salbutamol', 'generic_name' => 'Salbutamol Sulfate', 'code' => 'DRG026', 'form' => 'Inhaler', 'strength' => '100mcg', 'unit' => 'inhaler', 'cost_price' => 350, 'selling_price' => 500, 'category' => 'Respiratory'],
            ['name' => 'Ammonium Chloride', 'generic_name' => 'Ammonium Chloride', 'code' => 'DRG027', 'form' => 'Syrup', 'strength' => '100ml', 'unit' => 'bottle', 'cost_price' => 60, 'selling_price' => 100, 'category' => 'Respiratory'],
            ['name' => 'Bromhexine', 'generic_name' => 'Bromhexine Hydrochloride', 'code' => 'DRG028', 'form' => 'Syrup', 'strength' => '100ml', 'unit' => 'bottle', 'cost_price' => 80, 'selling_price' => 130, 'category' => 'Respiratory'],

            // Vitamins & Supplements
            ['name' => 'Vitamin C', 'generic_name' => 'Ascorbic Acid', 'code' => 'DRG029', 'form' => 'Tablet', 'strength' => '500mg', 'unit' => 'tablet', 'cost_price' => 5, 'selling_price' => 12, 'category' => 'Vitamins & Supplements'],
            ['name' => 'Vitamin B Complex', 'generic_name' => 'Vitamin B Complex', 'code' => 'DRG030', 'form' => 'Tablet', 'strength' => '100 tablets', 'unit' => 'pack', 'cost_price' => 50, 'selling_price' => 80, 'category' => 'Vitamins & Supplements'],
            ['name' => 'Ferrous Sulfate', 'generic_name' => 'Ferrous Sulfate', 'code' => 'DRG031', 'form' => 'Tablet', 'strength' => '200mg', 'unit' => 'tablet', 'cost_price' => 8, 'selling_price' => 15, 'category' => 'Vitamins & Supplements'],
            ['name' => 'Folic Acid', 'generic_name' => 'Folic Acid', 'code' => 'DRG032', 'form' => 'Tablet', 'strength' => '5mg', 'unit' => 'tablet', 'cost_price' => 5, 'selling_price' => 10, 'category' => 'Vitamins & Supplements'],

            // First Aid
            ['name' => 'Antiseptic Cream', 'generic_name' => 'Cetrimide', 'code' => 'DRG033', 'form' => 'Cream', 'strength' => '15g', 'unit' => 'tube', 'cost_price' => 80, 'selling_price' => 120, 'category' => 'First Aid'],
            ['name' => 'Bandage', 'generic_name' => 'Cotton Bandage', 'code' => 'DRG034', 'form' => 'Bandage', 'strength' => '4 inch', 'unit' => 'piece', 'cost_price' => 50, 'selling_price' => 80, 'category' => 'First Aid'],
            ['name' => 'Gauze', 'generic_name' => 'Sterile Gauze', 'code' => 'DRG035', 'form' => 'Gauze', 'strength' => '10x10cm', 'unit' => 'piece', 'cost_price' => 30, 'selling_price' => 50, 'category' => 'First Aid'],
            ['name' => 'Cotton Wool', 'generic_name' => 'Cotton Wool', 'code' => 'DRG036', 'form' => 'Cotton', 'strength' => '50g', 'unit' => 'roll', 'cost_price' => 40, 'selling_price' => 65, 'category' => 'First Aid'],
            ['name' => 'Hydrogen Peroxide', 'generic_name' => 'Hydrogen Peroxide', 'code' => 'DRG037', 'form' => 'Solution', 'strength' => '200ml', 'unit' => 'bottle', 'cost_price' => 50, 'selling_price' => 80, 'category' => 'First Aid'],

            // Diabetes
            ['name' => 'Metformin', 'generic_name' => 'Metformin Hydrochloride', 'code' => 'DRG038', 'form' => 'Tablet', 'strength' => '500mg', 'unit' => 'tablet', 'cost_price' => 15, 'selling_price' => 28, 'category' => 'Diabetes'],
            ['name' => 'Glibenclamide', 'generic_name' => 'Glibenclamide', 'code' => 'DRG039', 'form' => 'Tablet', 'strength' => '5mg', 'unit' => 'tablet', 'cost_price' => 12, 'selling_price' => 22, 'category' => 'Diabetes'],
            ['name' => 'Insulin (Regular)', 'generic_name' => 'Insulin Human', 'code' => 'DRG040', 'form' => 'Injection', 'strength' => '100IU/ml', 'unit' => 'vial', 'cost_price' => 500, 'selling_price' => 750, 'category' => 'Diabetes'],
        ];

        // Get category IDs
        $categories = DB::table('hospital_drug_categories')->pluck('id', 'name');

        foreach ($drugs as $drug) {
            $categoryId = $categories[$drug['category']] ?? null;

            DB::table('hospital_drugs')->updateOrInsert(
                ['code' => $drug['code']],
                [
                    'category_id' => $categoryId,
                    'name' => $drug['name'],
                    'generic_name' => $drug['generic_name'],
                    'code' => $drug['code'],
                    'form' => $drug['form'],
                    'strength' => $drug['strength'],
                    'unit' => $drug['unit'],
                    'cost_price' => $drug['cost_price'],
                    'selling_price' => $drug['selling_price'],
                    'reorder_level' => 50,
                    'current_stock' => rand(100, 500),
                    'storage_location' => 'Pharmacy Store A',
                    'requires_prescription' => in_array($drug['category'], ['Antibiotics', 'Analgesics', 'Cardiovascular', 'Diabetes']),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        echo "Drugs seeded successfully!\n";
    }
}
