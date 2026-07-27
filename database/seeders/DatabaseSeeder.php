<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Semester;
use App\Models\Level;
use App\Models\Grade;
use App\Models\GradingScale;
use App\Models\GradeClassification;
use App\Models\Setting;
use App\Models\Student;
use App\Services\ResultComputationService;
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
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }

        // Create Programmes
        $programmes = [
            ['name' => 'National Diploma', 'code' => 'ND', 'type' => 'ND'],
            ['name' => 'Higher National Diploma', 'code' => 'HND', 'type' => 'HND'],
            ['name' => 'Diploma', 'code' => 'DIP', 'type' => 'Diploma'],
            ['name' => 'Pre-ND', 'code' => 'PRE-ND', 'type' => 'Pre-ND'],
            ['name' => 'Bachelor Degree', 'code' => 'DEG', 'type' => 'Degree'],
            ['name' => 'Post Graduate Diploma', 'code' => 'PGD', 'type' => 'PGD'],
            ['name' => 'Masters', 'code' => 'MAST', 'type' => 'Masters'],
            ['name' => 'Doctor of Philosophy', 'code' => 'PHD', 'type' => 'PhD'],
        ];

        foreach ($programmes as $programme) {
            Programme::firstOrCreate(['code' => $programme['code']], $programme);
        }

        // Create Current Session
        $session = Session::firstOrCreate(
            ['name' => '2025/2026'],
            [
                'is_active' => true,
                'is_current' => true,
                'start_date' => '2025-10-01',
                'end_date' => '2026-09-30',
            ]
        );

        // Create another session
        Session::firstOrCreate(
            ['name' => '2024/2025'],
            [
                'is_active' => false,
                'is_current' => false,
                'start_date' => '2024-10-01',
                'end_date' => '2025-09-30',
            ]
        );

        // ===========================================
        // SCHOOLS - Based on Updated Programs Document
        // ===========================================
        $schoolsData = [
            ['name' => 'School of Clinical Technology', 'code' => 'SOCT', 'description' => 'Clinical and Medical Technology Programs'],
            ['name' => 'School of Allied Health Sciences', 'code' => 'SOAHS', 'description' => 'Allied Health and Public Health Programs'],
            ['name' => 'School of Finance, Business and Management Studies', 'code' => 'SOFBMS', 'description' => 'Finance, Business and Management Programs'],
            ['name' => 'School of Pure and Applied Sciences', 'code' => 'SPAS', 'description' => 'Pure and Applied Sciences Programs'],
            ['name' => 'School of Engineering', 'code' => 'SOENG', 'description' => 'Engineering Programs'],
        ];

        $schoolIds = [];
        foreach ($schoolsData as $school) {
            $schoolModel = School::firstOrCreate(['code' => $school['code']], $school);
            $schoolIds[$school['code']] = $schoolModel->id;
        }

        // ===========================================
        // DEPARTMENTS AND PROGRAMMES
        // ===========================================

        // School of Clinical Technology
        $departmentsData = [
            // COMMUNITY HEALTH STUDIES (CHS)
            [
                'name' => 'Community Health Studies',
                'code' => 'CHS',
                'school_id' => $schoolIds['SOCT'],
                'programmes' => [
                    ['name' => 'Community Health', 'code' => 'COMMHEALTH', 'type' => 'ND/HND'],
                    ['name' => 'Family Health', 'code' => 'FAMILYHEALTH', 'type' => 'ND'],
                    ['name' => 'Health Education and Promotion', 'code' => 'HEALTHEDU', 'type' => 'ND'],
                ]
            ],
            // MEDICAL EMERGENCY STUDIES (MES)
            [
                'name' => 'Medical Emergency Studies',
                'code' => 'MES',
                'school_id' => $schoolIds['SOCT'],
                'programmes' => [
                    ['name' => 'Paramedicine Science', 'code' => 'PARAMEDIC', 'type' => 'ND/HND'],
                    ['name' => 'Orthopedic Plaster Cast', 'code' => 'ORTHOPLAST', 'type' => 'ND'],
                ]
            ],
            // DENTAL HEALTH STUDIES (DHS)
            [
                'name' => 'Dental Health Studies',
                'code' => 'DHS',
                'school_id' => $schoolIds['SOCT'],
                'programmes' => [
                    ['name' => 'Dental Therapy', 'code' => 'DENTHERAPY', 'type' => 'ND/HND'],
                    ['name' => 'Dental Surgery Technology', 'code' => 'DENTSURG', 'type' => 'ND/HND'],
                ]
            ],
            // MEDICAL DIAGNOSTIC STUDIES (MDS)
            [
                'name' => 'Medical Diagnostic Studies',
                'code' => 'MDS',
                'school_id' => $schoolIds['SOCT'],
                'programmes' => [
                    ['name' => 'Medical Laboratory Technology', 'code' => 'MEDLAB', 'type' => 'ND'],
                    ['name' => 'Medical Imaging Technology', 'code' => 'MEDIMAGING', 'type' => 'ND'],
                ]
            ],
            // MEDICAL THERAPY AND INTERVENTION SCIENCES
            [
                'name' => 'Medical Therapy and Intervention Sciences',
                'code' => 'MTIS',
                'school_id' => $schoolIds['SOCT'],
                'programmes' => [
                    ['name' => 'Pharmacy', 'code' => 'PHARMACY', 'type' => 'Diploma'],
                    ['name' => 'Dispensing Opticianry', 'code' => 'DISPOPT', 'type' => 'ND'],
                ]
            ],
        ];

        foreach ($departmentsData as $dept) {
            $deptModel = Department::firstOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'school_id' => $dept['school_id']]
            );

            // Create programmes for this department
            foreach ($dept['programmes'] as $prog) {
                // Determine programme type
                $progType = match(true) {
                    str_contains($prog['type'], 'ND/HND') => 'ND',
                    str_contains($prog['type'], 'ND') => 'ND',
                    str_contains($prog['type'], 'HND') => 'HND',
                    str_contains($prog['type'], 'DIP') => 'Diploma',
                    str_contains($prog['type'], 'PRE') => 'Pre-ND',
                    default => 'ND',
                };

                Programme::firstOrCreate(
                    ['code' => $prog['code']],
                    [
                        'name' => $prog['name'],
                        'type' => $progType,
                        'department_id' => $deptModel->id,
                    ]
                );
            }
        }

        // School of Allied Health Sciences
        $alliedHealthDepartments = [
            [
                'name' => 'Public Health Studies',
                'code' => 'PHS',
                'school_id' => $schoolIds['SOAHS'],
                'programmes' => [
                    ['name' => 'Environmental Health Technology', 'code' => 'ENVHEALTH', 'type' => 'ND/HND'],
                    ['name' => 'Public Health Technology', 'code' => 'PUBHEALTH', 'type' => 'ND'],
                ]
            ],
            [
                'name' => 'Care Studies',
                'code' => 'CAREST',
                'school_id' => $schoolIds['SOAHS'],
                'programmes' => [
                    ['name' => 'Nutrition and Dietetics', 'code' => 'NUTRITION', 'type' => 'ND'],
                    ['name' => 'Social Work', 'code' => 'SOCIALWORK', 'type' => 'ND'],
                ]
            ],
            [
                'name' => 'Health Information Management',
                'code' => 'HIM',
                'school_id' => $schoolIds['SOAHS'],
                'programmes' => [
                    ['name' => 'Health Information Management', 'code' => 'HEALTHINFO', 'type' => 'ND/HND'],
                ]
            ],
            [
                'name' => 'Medical Store Management',
                'code' => 'MSM',
                'school_id' => $schoolIds['SOAHS'],
                'programmes' => [
                    ['name' => 'Medical Store Management', 'code' => 'MEDSTORE', 'type' => 'ND'],
                ]
            ],
            [
                'name' => 'Applied Sciences',
                'code' => 'APPLSCI',
                'school_id' => $schoolIds['SOAHS'],
                'programmes' => [
                    ['name' => 'Remedial Studies', 'code' => 'REMEDIAL', 'type' => 'PRE-ND'],
                ]
            ],
        ];

        foreach ($alliedHealthDepartments as $dept) {
            $deptModel = Department::firstOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'school_id' => $dept['school_id']]
            );

            foreach ($dept['programmes'] as $prog) {
                $progType = match(true) {
                    str_contains($prog['type'], 'ND/HND') => 'ND',
                    str_contains($prog['type'], 'ND') => 'ND',
                    str_contains($prog['type'], 'HND') => 'HND',
                    str_contains($prog['type'], 'DIP') => 'Diploma',
                    str_contains($prog['type'], 'PRE') => 'Pre-ND',
                    default => 'ND',
                };

                Programme::firstOrCreate(
                    ['code' => $prog['code']],
                    ['name' => $prog['name'], 'type' => $progType, 'department_id' => $deptModel->id]
                );
            }
        }

        // School of Finance, Business and Management Studies
        $businessDepartments = [
            [
                'name' => 'Business Administration and Management',
                'code' => 'BAM',
                'school_id' => $schoolIds['SOFBMS'],
                'programmes' => [
                    ['name' => 'Accountancy', 'code' => 'ACCOUNTANCY', 'type' => 'ND'],
                ]
            ],
        ];

        foreach ($businessDepartments as $dept) {
            $deptModel = Department::firstOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'school_id' => $dept['school_id']]
            );

            foreach ($dept['programmes'] as $prog) {
                Programme::firstOrCreate(
                    ['code' => $prog['code']],
                    ['name' => $prog['name'], 'type' => 'ND', 'department_id' => $deptModel->id]
                );
            }
        }

        // School of Pure and Applied Sciences
        $scienceDepartments = [
            [
                'name' => 'Computer Studies',
                'code' => 'COMPST',
                'school_id' => $schoolIds['SPAS'],
                'programmes' => [
                    ['name' => 'Computer Science', 'code' => 'COMPUTERSCI', 'type' => 'ND'],
                    ['name' => 'Cyber Security and Data Protection', 'code' => 'CYBERSEC', 'type' => 'HND'],
                    ['name' => 'Artificial Intelligence', 'code' => 'AI', 'type' => 'HND'],
                    ['name' => 'Software and Web Development', 'code' => 'SOFTWARE', 'type' => 'HND'],
                ]
            ],
        ];

        foreach ($scienceDepartments as $dept) {
            $deptModel = Department::firstOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'school_id' => $dept['school_id']]
            );

            foreach ($dept['programmes'] as $prog) {
                $progType = str_contains($prog['type'], 'HND') ? 'HND' : 'ND';
                Programme::firstOrCreate(
                    ['code' => $prog['code']],
                    ['name' => $prog['name'], 'type' => $progType, 'department_id' => $deptModel->id]
                );
            }
        }

        // School of Engineering
        $engineeringDepartments = [
            [
                'name' => 'Engineering Studies',
                'code' => 'ENGST',
                'school_id' => $schoolIds['SOENG'],
                'programmes' => [
                    ['name' => 'Electrical/Electronics Engineering', 'code' => 'EEE', 'type' => 'ND'],
                    ['name' => 'Biomedical Engineering', 'code' => 'BIOMED', 'type' => 'ND'],
                    ['name' => 'Computer Engineering', 'code' => 'COMPENG', 'type' => 'ND'],
                ]
            ],
        ];

        foreach ($engineeringDepartments as $dept) {
            $deptModel = Department::firstOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'school_id' => $dept['school_id']]
            );

            foreach ($dept['programmes'] as $prog) {
                Programme::firstOrCreate(
                    ['code' => $prog['code']],
                    ['name' => $prog['name'], 'type' => 'ND', 'department_id' => $deptModel->id]
                );
            }
        }

        // Create Default Grades
        foreach (Grade::getDefaultGrades() as $grade) {
            Grade::firstOrCreate(['grade' => $grade['grade']], $grade);
        }

        // Create Grading Scales (Nigerian Higher Institution Standard)
        foreach (ResultComputationService::getDefaultGradingScales() as $scale) {
            GradingScale::firstOrCreate(['grade' => $scale['grade']], $scale);
        }

        // Create Grade Classifications
        foreach (ResultComputationService::getDefaultClassifications() as $classification) {
            GradeClassification::firstOrCreate(['slug' => $classification['slug']], $classification);
        }

        // Create Semesters
        $semesters = [
            ['name' => 'First Semester', 'code' => 'FIRST', 'sort_order' => 1],
            ['name' => 'Second Semester', 'code' => 'SECOND', 'sort_order' => 2],
            ['name' => 'Third Semester', 'code' => 'THIRD', 'sort_order' => 3],
        ];
        foreach ($semesters as $semester) {
            Semester::firstOrCreate(['code' => $semester['code']], $semester);
        }

        // Create Levels
        $levels = [
            ['name' => 'ND 1 (100L)', 'code' => 'ND1', 'sort_order' => 1, 'programme_type' => 'ND'],
            ['name' => 'ND 2 (200L)', 'code' => 'ND2', 'sort_order' => 2, 'programme_type' => 'ND'],
            ['name' => 'HND 1 (300L)', 'code' => 'HND1', 'sort_order' => 3, 'programme_type' => 'HND'],
            ['name' => 'HND 2 (400L)', 'code' => 'HND2', 'sort_order' => 4, 'programme_type' => 'HND'],
            ['name' => 'HND 3 (500L)', 'code' => 'HND3', 'sort_order' => 5, 'programme_type' => 'HND'],
            ['name' => 'Pre-ND', 'code' => 'PRE', 'sort_order' => 0, 'programme_type' => 'PRE-ND'],
        ];
        foreach ($levels as $level) {
            Level::firstOrCreate(['code' => $level['code']], $level);
        }

        // Create Super Admin User
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();

        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@portal.edu'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'role_id' => $superAdminRole->id,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@admin.edu'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('admin123'),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ]
        );

        // Create a test student user
        $studentRole = Role::where('slug', 'student')->first();
        $testStudentUser = User::firstOrCreate(
            ['email' => 'student@test.com'],
            [
                'name' => 'Test Student',
                'password' => Hash::make('password123'),
                'role_id' => $studentRole->id,
                'is_active' => true,
            ]
        );

        // Get first school, department, programme for test student
        $firstSchool = School::first();
        $firstDept = Department::first();
        $firstProg = Programme::first();

        // Create student profile for test student
        Student::firstOrCreate(
            ['matric_number' => 'ND/2024/001'],
            [
                'user_id' => $testStudentUser->id,
                'school_id' => $firstSchool?->id,
                'department_id' => $firstDept?->id,
                'programme_id' => $firstProg?->id,
                'session_id' => $session->id,
                'level' => 1,
                'status' => 'active',
            ]
        );

        // Create a test applicant user
        $applicantRole = Role::where('slug', 'applicant')->first();
        User::firstOrCreate(
            ['email' => 'applicant@test.com'],
            [
                'name' => 'Test Applicant',
                'password' => Hash::make('password123'),
                'role_id' => $applicantRole->id,
                'is_active' => true,
            ]
        );

        // Create Settings
        Setting::set('institution_name', 'Ekiti State College of Technology');
        Setting::set('institution_short_name', 'EKSCOTECH');
        Setting::set('institution_address', 'Ekiti State College of Technology, Jero Ekiti, Ekiti State');
        Setting::set('institution_email', 'info@ekscotech.edu.ng');
        Setting::set('institution_phone', '08012345678');
        Setting::set('institution_website', 'www.ekscotech.edu.ng');
        Setting::set('institution_tagline', 'Excellence in Technical Education');
        Setting::set('session_id', $session->id);
        Setting::set('max_course_units', 24);
        Setting::set('min_course_units', 12);

        // Seed States and Local Governments
        $this->call([
            StatesAndLGAsSeeder::class,
            NationalitiesSeeder::class,
            ERPRolesSeeder::class,
            PaymentTypeSeeder::class,
            HospitalModuleSeeder::class,
        ]);
    }
}
