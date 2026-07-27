<?php

namespace Database\Factories;

use App\Models\Hospital\HospitalStaff;
use Illuminate\Database\Eloquent\Factories\Factory;

class HospitalStaffFactory extends Factory
{
    protected $model = HospitalStaff::class;

    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'user_id' => null,
            'staff_number' => 'STF' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'staff_type' => fake()->randomElement(['doctor', 'nurse', 'laboratorist', 'pharmacist', 'receptionist', 'store_keeper', 'accountant']),
            'specialization' => fake()->randomElement(['General Medicine', 'Pediatrics', 'Surgery', 'Gynecology', 'Cardiology', null]),
            'license_number' => strtoupper(fake()->bothify('??###')),
            'phone' => '080' . fake()->numerify('########'),
            'email' => strtolower($firstName . '.' . $lastName) . '@hospital.org',
            'address' => fake()->address(),
            'gender' => fake()->randomElement(['male', 'female']),
            'is_available' => true,
            'is_active' => true,
        ];
    }
}
