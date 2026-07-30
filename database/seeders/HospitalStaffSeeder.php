<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HospitalStaffSeeder extends Seeder
{
    public function run()
    {
        $staff = [
            // Doctors
            ['first_name' => 'John', 'last_name' => 'Smith', 'staff_type' => 'doctor', 'specialization' => 'General Medicine', 'license_number' => 'MD001'],
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'staff_type' => 'doctor', 'specialization' => 'Pediatrics', 'license_number' => 'MD002'],
            ['first_name' => 'Michael', 'last_name' => 'Brown', 'staff_type' => 'doctor', 'specialization' => 'Surgery', 'license_number' => 'MD003'],
            ['first_name' => 'Emily', 'last_name' => 'Davis', 'staff_type' => 'doctor', 'specialization' => 'Gynecology', 'license_number' => 'MD004'],
            ['first_name' => 'David', 'last_name' => 'Wilson', 'staff_type' => 'doctor', 'specialization' => 'Cardiology', 'license_number' => 'MD005'],
            ['first_name' => 'Jennifer', 'last_name' => 'Taylor', 'staff_type' => 'doctor', 'specialization' => 'Dermatology', 'license_number' => 'MD006'],
            ['first_name' => 'Robert', 'last_name' => 'Anderson', 'staff_type' => 'doctor', 'specialization' => 'Orthopedics', 'license_number' => 'MD007'],
            ['first_name' => 'Lisa', 'last_name' => 'Martinez', 'staff_type' => 'doctor', 'specialization' => 'Neurology', 'license_number' => 'MD008'],

            // Nurses
            ['first_name' => 'Mary', 'last_name' => 'Garcia', 'staff_type' => 'nurse', 'specialization' => null, 'license_number' => 'RN001'],
            ['first_name' => 'Patricia', 'last_name' => 'Rodriguez', 'staff_type' => 'nurse', 'specialization' => null, 'license_number' => 'RN002'],
            ['first_name' => 'Linda', 'last_name' => 'Hernandez', 'staff_type' => 'nurse', 'specialization' => null, 'license_number' => 'RN003'],
            ['first_name' => 'Barbara', 'last_name' => 'Lopez', 'staff_type' => 'nurse', 'specialization' => null, 'license_number' => 'RN004'],
            ['first_name' => 'Elizabeth', 'last_name' => 'Gonzalez', 'staff_type' => 'nurse', 'specialization' => null, 'license_number' => 'RN005'],
            ['first_name' => 'Susan', 'last_name' => 'Wilson', 'staff_type' => 'nurse', 'specialization' => null, 'license_number' => 'RN006'],
            ['first_name' => 'Jessica', 'last_name' => 'Anderson', 'staff_type' => 'nurse', 'specialization' => null, 'license_number' => 'RN007'],
            ['first_name' => 'Amanda', 'last_name' => 'Thomas', 'staff_type' => 'nurse', 'specialization' => null, 'license_number' => 'RN008'],

            // Laboratorists
            ['first_name' => 'Christopher', 'last_name' => 'Jackson', 'staff_type' => 'laboratorist', 'specialization' => 'Laboratory Medicine', 'license_number' => 'LAB001'],
            ['first_name' => 'Michelle', 'last_name' => 'White', 'staff_type' => 'laboratorist', 'specialization' => 'Blood Banking', 'license_number' => 'LAB002'],
            ['first_name' => 'Daniel', 'last_name' => 'Harris', 'staff_type' => 'laboratorist', 'specialization' => 'Microbiology', 'license_number' => 'LAB003'],

            // Pharmacists
            ['first_name' => 'Jessica', 'last_name' => 'Martin', 'staff_type' => 'pharmacist', 'specialization' => 'Clinical Pharmacy', 'license_number' => 'PH001'],
            ['first_name' => 'Matthew', 'last_name' => 'Thompson', 'staff_type' => 'pharmacist', 'specialization' => 'Pharmacy', 'license_number' => 'PH002'],
            ['first_name' => 'Ashley', 'last_name' => 'Garcia', 'staff_type' => 'pharmacist', 'specialization' => 'Pharmacy', 'license_number' => 'PH003'],

            // Receptionists
            ['first_name' => 'Stephanie', 'last_name' => 'Martinez', 'staff_type' => 'receptionist', 'specialization' => null, 'license_number' => 'REC001'],
            ['first_name' => 'Andrew', 'last_name' => 'Robinson', 'staff_type' => 'receptionist', 'specialization' => null, 'license_number' => 'REC002'],

            // Store Keepers
            ['first_name' => 'Kevin', 'last_name' => 'Clark', 'staff_type' => 'store_keeper', 'specialization' => null, 'license_number' => 'SK001'],
            ['first_name' => 'Rachel', 'last_name' => 'Lewis', 'staff_type' => 'store_keeper', 'specialization' => null, 'license_number' => 'SK002'],

            // Accountants
            ['first_name' => 'Brian', 'last_name' => 'Lee', 'staff_type' => 'accountant', 'specialization' => null, 'license_number' => 'ACC001'],
            ['first_name' => 'Nicole', 'last_name' => 'Walker', 'staff_type' => 'accountant', 'specialization' => null, 'license_number' => 'ACC002'],
        ];

        foreach ($staff as $index => $member) {
            // Check if staff already exists to prevent duplicates
            $exists = DB::table('hospital_staff')
                ->where('first_name', $member['first_name'])
                ->where('last_name', $member['last_name'])
                ->where('staff_type', $member['staff_type'])
                ->exists();

            if ($exists) {
                continue; // Skip if already exists
            }

            DB::table('hospital_staff')->insert([
                'staff_number' => 'STF' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'staff_type' => $member['staff_type'],
                'specialization' => $member['specialization'],
                'license_number' => $member['license_number'],
                'phone' => '080' . rand(10000000, 99999999),
                'email' => strtolower($member['first_name'] . '.' . $member['last_name']) . '@hospital.org',
                'address' => 'Hospital Staff Quarters',
                'gender' => in_array($member['staff_type'], ['nurse', 'receptionist']) && $member['first_name'] === 'Mary' ? 'female' : 'male',
                'is_available' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        echo "Hospital staff seeded successfully!\n";
    }
}
