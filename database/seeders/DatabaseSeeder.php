<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Grade;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Roles
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full system access', 'permissions' => ['*']],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrative access', 'permissions' => ['users.*', 'schools.*', 'departments.*', 'courses.*', 'reports.*']],
            ['name' => 'Registrar', 'slug' => 'registrar', 'description' => 'Registry operations', 'permissions' => ['applicants.*', 'students.*', 'admission.*']],
            ['name' => 'Bursar', 'slug' => 'bursar', 'description' => 'Financial operations', 'permissions' => ['payments.*', 'fees.*', 'reports.payments']],
            ['name' => 'Dean', 'slug' => 'dean', 'description' => 'Faculty Dean', 'permissions' => ['results.approve', 'timetable.approve', 'departments.view']],
            ['name' => 'HOD', 'slug' => 'hod', 'description' => 'Head of Department', 'permissions' => ['courses.assign', 'courses.view', 'timetable.*', 'results.approve', 'lecturers.view']],
            ['name' => 'Lecturer', 'slug' => 'lecturer', 'description' => 'Teaching Staff', 'permissions' => ['courses.teach', 'results.enter', 'attendance.*', 'timetable.view']],
            ['name' => 'Staff', 'slug' => 'staff', 'description' => 'Administrative Staff', 'permissions' => ['profile.view']],
            ['name' => 'Student', 'slug' => 'student', 'description' => 'Student Portal', 'permissions' => ['courses.register', 'results.view', 'payments.view', 'timetable.view', 'profile.view']],
            ['name' => 'Applicant', 'slug' => 'applicant', 'description' => 'Applicant Portal', 'permissions' => ['applications.create', 'applications.view']],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        // Create Programmes
        $programmes = [
            ['name' => 'National Diploma', 'code' => 'ND', 'type' => 'ND'],
            ['name' => 'Higher National Diploma', 'code' => 'HND', 'type' => 'HND'],
            ['name' => 'Bachelor Degree', 'code' => 'DEG', 'type' => 'Degree'],
            ['name' => 'Post Graduate Diploma', 'code' => 'PGD', 'type' => 'PGD'],
            ['name' => 'Masters', 'code' => 'MAST', 'type' => 'Masters'],
            ['name' => 'Doctor of Philosophy', 'code' => 'PHD', 'type' => 'PhD'],
        ];

        foreach ($programmes as $programme) {
            Programme::create($programme);
        }

        // Create Current Session
        $session = Session::create([
            'name' => '2025/2026',
            'is_active' => true,
            'is_current' => true,
            'start_date' => '2025-10-01',
            'end_date' => '2026-09-30',
        ]);

        // Create another session
        Session::create([
            'name' => '2024/2025',
            'is_active' => false,
            'is_current' => false,
            'start_date' => '2024-10-01',
            'end_date' => '2025-09-30',
        ]);

        // Create Schools
        $schools = [
            ['name' => 'School of Computing', 'code' => 'SOC', 'description' => 'Computing and Information Technology'],
            ['name' => 'School of Engineering', 'code' => 'SOE', 'description' => 'Engineering and Technology'],
            ['name' => 'School of Management', 'code' => 'SOM', 'description' => 'Business and Management Studies'],
            ['name' => 'School of Applied Sciences', 'code' => 'SAS', 'description' => 'Applied Sciences'],
        ];

        foreach ($schools as $school) {
            School::create($school);
        }

        // Create Departments
        $departments = [
            ['name' => 'Computer Science', 'code' => 'CS', 'school_id' => 1],
            ['name' => 'Information Systems', 'code' => 'IS', 'school_id' => 1],
            ['name' => 'Software Engineering', 'code' => 'SE', 'school_id' => 1],
            ['name' => 'Electrical Engineering', 'code' => 'EE', 'school_id' => 2],
            ['name' => 'Mechanical Engineering', 'code' => 'ME', 'school_id' => 2],
            ['name' => 'Business Administration', 'code' => 'BA', 'school_id' => 3],
            ['name' => 'Accountancy', 'code' => 'AC', 'school_id' => 3],
            ['name' => 'Science Laboratory Technology', 'code' => 'SLT', 'school_id' => 4],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        // Create Default Grades
        foreach (Grade::getDefaultGrades() as $grade) {
            Grade::create($grade);
        }

        // Create Super Admin User
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        $superAdmin = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@portal.edu',
            'password' => Hash::make('password'),
            'role_id' => $superAdminRole->id,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'System Admin',
            'email' => 'admin@admin.edu',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        // Create a test student user
        $studentRole = Role::where('slug', 'student')->first();
        $testStudentUser = User::create([
            'name' => 'Test Student',
            'email' => 'student@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        // Get first school, department, programme for test student
        $firstSchool = School::first();
        $firstDept = Department::first();
        $firstProg = Programme::first();

        // Create student profile for test student
        Student::create([
            'user_id' => $testStudentUser->id,
            'matric_number' => 'ND/2024/001',
            'school_id' => $firstSchool?->id,
            'department_id' => $firstDept?->id,
            'programme_id' => $firstProg?->id,
            'session_id' => $session->id,
            'level' => 1,
            'status' => 'active',
        ]);

        // Create a test applicant user
        $applicantRole = Role::where('slug', 'applicant')->first();
        User::create([
            'name' => 'Test Applicant',
            'email' => 'applicant@test.com',
            'password' => Hash::make('password123'),
            'role_id' => $applicantRole->id,
            'is_active' => true,
        ]);

        // Create Settings
        Setting::set('institution_name', 'Institution Management Portal');
        Setting::set('institution_address', 'University Road, City, State');
        Setting::set('institution_email', 'info@portal.edu');
        Setting::set('institution_phone', '+2348000000000');
        Setting::set('institution_website', 'www.portal.edu');
        Setting::set('session_id', $session->id);
        Setting::set('max_course_units', 24);
        Setting::set('min_course_units', 12);

        // Seed States and Local Governments
        $this->call([
            StatesAndLGAsSeeder::class,
            NationalitiesSeeder::class,
        ]);
    }
}