<?php

namespace Database\Factories;

use App\Models\Hospital\ExternalPatient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ExternalPatientFactory extends Factory
{
    protected $model = ExternalPatient::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'patient_number' => 'EXT' . date('Y') . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'access_code' => strtoupper(Str::random(8)),
            'access_code_expires_at' => now()->addDays(30),
            'password' => bcrypt('password'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $firstName . ' ' . $lastName,
            'email' => strtolower($firstName . '.' . $lastName) . '@example.com',
            'phone' => '080' . fake()->numerify('########'),
            'gender' => fake()->randomElement(['male', 'female']),
            'date_of_birth' => fake()->date('Y-m-d', '-20 years'),
            'age' => fake()->numberBetween(18, 70),
            'blood_group' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'genotype' => fake()->randomElement(['AA', 'AS', 'SS', 'AC']),
            'address' => fake()->address(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => '080' . fake()->numerify('########'),
            'is_active' => true,
        ];
    }
}
