<?php

namespace Database\Factories;

use App\Models\Hospital\HospitalDrug;
use Illuminate\Database\Eloquent\Factories\Factory;

class HospitalDrugFactory extends Factory
{
    protected $model = HospitalDrug::class;

    public function definition(): array
    {
        return [
            'category_id' => null,
            'name' => fake()->randomElement(['Paracetamol', 'Ibuprofen', 'Amoxicillin', 'Ciprofloxacin', 'Metronidazole', 'Aspirin', 'Cetirizine', 'Metformin']),
            'generic_name' => fake()->randomElement(['Acetaminophen', 'Ibuprofen', 'Amoxicillin Trihydrate', 'Ciprofloxacin', 'Metronidazole', 'Acetylsalicylic Acid']),
            'code' => 'DRG' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'form' => fake()->randomElement(['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream', 'Suspension']),
            'strength' => fake()->randomElement(['100mg', '250mg', '500mg', '1000mg', '50mg', '200mg']),
            'unit' => fake()->randomElement(['tablet', 'capsule', 'bottle', 'ampoule', 'tube']),
            'cost_price' => fake()->randomFloat(2, 5, 500),
            'selling_price' => fake()->randomFloat(2, 10, 750),
            'reorder_level' => fake()->numberBetween(10, 50),
            'current_stock' => fake()->numberBetween(50, 500),
            'storage_location' => 'Pharmacy Store A',
            'requires_prescription' => fake()->boolean(70),
            'is_active' => true,
        ];
    }
}
