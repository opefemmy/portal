<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Hospital\HospitalStaff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing roles
        $roles = [];
        $roleSlugs = ['super_admin', 'admin', 'bursar', 'doctor', 'nurse', 'pharmacist', 'lab_scientist', 'hospital_receptionist', 'rector', 'auditor', 'student', 'lecturer', 'hod', 'dean', 'business_committee', 'academic_board', 'librarian'];

        foreach ($roleSlugs as $slug) {
            $roles[$slug] = Role::where('slug', $slug)->first();
        }

        // Test Users with Roles
        $testUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@portal.edu',
                'password' => 'password',
                'role' => 'super_admin',
                'gender' => 'male',
            ],
            [
                'name' => 'Bursar Test',
                'email' => 'bursar@portal.edu',
                'password' => 'password123',
                'role' => 'bursar',
                'gender' => 'male',
            ],
            [
                'name' => 'Dr. John Smith',
                'email' => 'doctor@portal.edu',
                'password' => 'password123',
                'role' => 'doctor',
                'gender' => 'male',
                'staff_id' => 'HOS001',
            ],
            [
                'name' => 'Nurse Mary Jane',
                'email' => 'nurse@portal.edu',
                'password' => 'password123',
                'role' => 'nurse',
                'gender' => 'female',
                'staff_id' => 'HOS002',
            ],
            [
                'name' => 'Pharmacist Adam',
                'email' => 'pharmacist@portal.edu',
                'password' => 'password123',
                'role' => 'pharmacist',
                'gender' => 'male',
                'staff_id' => 'HOS003',
            ],
            [
                'name' => 'Lab Scientist Eve',
                'email' => 'labscientist@portal.edu',
                'password' => 'password123',
                'role' => 'lab_scientist',
                'gender' => 'female',
                'staff_id' => 'HOS004',
            ],
            [
                'name' => 'Hospital Reception',
                'email' => 'reception@portal.edu',
                'password' => 'password123',
                'role' => 'hospital_receptionist',
                'gender' => 'female',
                'staff_id' => 'HOS005',
            ],
            [
                'name' => 'Rector Test',
                'email' => 'rector@portal.edu',
                'password' => 'password123',
                'role' => 'rector',
                'gender' => 'male',
            ],
            [
                'name' => 'Auditor Test',
                'email' => 'auditor@portal.edu',
                'password' => 'password123',
                'role' => 'auditor',
                'gender' => 'female',
            ],
            [
                'name' => 'Lecturer Test',
                'email' => 'lecturer@portal.edu',
                'password' => 'password123',
                'role' => 'lecturer',
                'gender' => 'male',
                'staff_id' => 'LEC001',
            ],
            [
                'name' => 'HOD Test',
                'email' => 'hod@portal.edu',
                'password' => 'password123',
                'role' => 'hod',
                'gender' => 'male',
                'staff_id' => 'HOD001',
            ],
            [
                'name' => 'Dean Test',
                'email' => 'dean@portal.edu',
                'password' => 'password123',
                'role' => 'dean',
                'gender' => 'male',
                'staff_id' => 'DEAN001',
            ],
            [
                'name' => 'Business Committee',
                'email' => 'business@portal.edu',
                'password' => 'password123',
                'role' => 'business_committee',
                'gender' => 'male',
                'staff_id' => 'BC001',
            ],
            [
                'name' => 'Academic Board',
                'email' => 'academic@portal.edu',
                'password' => 'password123',
                'role' => 'academic_board',
                'gender' => 'female',
                'staff_id' => 'AB001',
            ],
            [
                'name' => 'Librarian Test',
                'email' => 'librarian@portal.edu',
                'password' => 'password123',
                'role' => 'librarian',
                'gender' => 'female',
                'staff_id' => 'LIB001',
            ],
            [
                'name' => 'Test Student',
                'email' => 'student@test.com',
                'password' => 'password123',
                'role' => 'student',
                'gender' => 'male',
                'matric_number' => 'ND/2024/001',
            ],
            [
                'name' => 'Test Student Female',
                'email' => 'student2@test.com',
                'password' => 'password123',
                'role' => 'student',
                'gender' => 'female',
                'matric_number' => 'ND/2024/002',
            ],
        ];

        $this->command->info('Creating test users...');

        foreach ($testUsers as $userData) {
            $role = $userData['role'];
            $roleObj = isset($roles[$role]) ? $roles[$role] : null;
            $roleId = $roleObj ? $roleObj->id : null;

            if (!$roleId) {
                $this->command->warn("Role '$role' not found, skipping user: " . $userData['name']);
                continue;
            }

            // Check if user already exists
            $existingUser = User::where('email', $userData['email'])->first();
            if ($existingUser) {
                $this->command->info("User " . $userData['email'] . " already exists, updating...");
                $user = $existingUser;
                $user->update([
                    'name' => $userData['name'],
                    'role_id' => $roleId,
                    'is_active' => true,
                ]);
            } else {
                $user = User::create([
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($userData['password']),
                    'role_id' => $roleId,
                    'gender' => $userData['gender'] ?? 'male',
                    'is_active' => true,
                    'staff_id' => $userData['staff_id'] ?? null,
                    'date_of_birth' => '1990-01-01',
                    'phone' => '+2348000000000',
                    'address' => 'Test Address',
                ]);
            }

            // Create student profile if student role
            if ($role === 'student' && !Student::where('user_id', $user->id)->first()) {
                $school = School::first();
                $department = Department::first();
                $programme = Programme::first();
                $session = Session::where('is_current', true)->first();

                Student::create([
                    'user_id' => $user->id,
                    'matric_number' => $userData['matric_number'] ?? 'ND/2024/' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                    'school_id' => $school ? $school->id : null,
                    'department_id' => $department ? $department->id : null,
                    'programme_id' => $programme ? $programme->id : null,
                    'session_id' => $session ? $session->id : null,
                    'level' => 1,
                    'status' => 'active',
                ]);

                $this->command->info("Created student profile for " . $userData['email']);
            }

            // Create hospital staff if hospital role
            if (in_array($role, ['doctor', 'nurse', 'pharmacist', 'lab_scientist', 'hospital_receptionist'])) {
                $staffType = 'doctor';
                if ($role === 'nurse') $staffType = 'nurse';
                if ($role === 'pharmacist') $staffType = 'pharmacist';
                if ($role === 'lab_scientist') $staffType = 'laboratorist';
                if ($role === 'hospital_receptionist') $staffType = 'receptionist';

                if (!HospitalStaff::where('user_id', $user->id)->first()) {
                    $names = explode(' ', $userData['name']);
                    $firstName = isset($names[0]) ? $names[0] : $userData['name'];
                    $lastName = isset($names[1]) ? implode(' ', array_slice($names, 1)) : 'Staff';

                    HospitalStaff::create([
                        'user_id' => $user->id,
                        'staff_number' => $userData['staff_id'] ?? 'HOS' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'staff_type' => $staffType,
                        'phone' => '+2348000000000',
                        'is_active' => true,
                        'is_available' => true,
                    ]);

                    $this->command->info("Created hospital staff profile for " . $userData['email']);
                }
            }

            $this->command->info("Created/Updated user: " . $userData['email'] . " (" . $role . ")");
        }

        $this->command->info('Test users created successfully!');
        $this->command->info('');
        $this->command->info('=== TEST CREDENTIALS ===');
        $this->command->info('Admin: admin@portal.edu / password');
        $this->command->info('Bursar: bursar@portal.edu / password123');
        $this->command->info('Doctor: doctor@portal.edu / password123');
        $this->command->info('Nurse: nurse@portal.edu / password123');
        $this->command->info('Pharmacist: pharmacist@portal.edu / password123');
        $this->command->info('Lab Scientist: labscientist@portal.edu / password123');
        $this->command->info('Reception: reception@portal.edu / password123');
        $this->command->info('Rector: rector@portal.edu / password123');
        $this->command->info('Auditor: auditor@portal.edu / password123');
        $this->command->info('Lecturer: lecturer@portal.edu / password123');
        $this->command->info('HOD: hod@portal.edu / password123');
        $this->command->info('Dean: dean@portal.edu / password123');
        $this->command->info('Business Committee: business@portal.edu / password123');
        $this->command->info('Academic Board: academic@portal.edu / password123');
        $this->command->info('Librarian: librarian@portal.edu / password123');
        $this->command->info('Student: student@test.com / password123');
    }
}