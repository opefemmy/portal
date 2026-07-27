<?php

namespace Database\Factories;

use App\Models\Hospital\HospitalPatient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HospitalPatientFactory extends Factory
{
    protected $model = HospitalPatient::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'user_id' => null,
            'patient_number' => 'PAT' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'other_name' => null,
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => fake()->date('Y-m-d', '-18 years'),
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'genotype' => fake()->randomElement(['AA', 'AS', 'SS', 'AC']),
            'phone' => '080' . fake()->numerify('########'),
            'email' => strtolower($firstName . '.' . $lastName) . '@example.com',
            'address' => fake()->address(),
            'state' => fake()->state(),
            'lga' => fake()->city(),
            'nationality' => 'Nigerian',
            'next_of_kin_name' => fake()->name(),
            'next_of_kin_phone' => '080' . fake()->numerify('########'),
            'next_of_kin_relationship' => fake()->randomElement(['Father', 'Mother', 'Sibling', 'Spouse', 'Parent']),
            'patient_type' => fake()->randomElement(['student', 'staff', 'visitor', 'dependent']),
            'is_active' => true,
        ];
    }
}
