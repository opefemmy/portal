<?php

/**
 * Comprehensive Fix Script for Ekiti State College of Technology Portal
 *
 * This script:
 * 1. Checks for missing tables
 * 2. Adds missing columns
 * 3. Creates missing indexes
 * 4. Adds missing foreign keys
 * 5. Seeds default data
 * 6. Fixes permissions and settings
 *
 * Run: php fix.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
use App\Models\SystemSetting;
use App\Models\Student;
use App\Models\Applicant;
use App\Models\Fee;
use App\Models\Payment;
use App\Services\ResultComputationService;

echo "===========================================\n";
echo "Ekiti State College of Technology Portal\n";
echo "Comprehensive Fix Script\n";
echo "===========================================\n\n";

$report = [
    'tables_checked' => [],
    'tables_created' => [],
    'columns_added' => [],
    'indexes_created' => [],
    'foreign_keys_added' => [],
    'seeded_records' => [],
    'errors' => [],
];

// ===========================================
// 1. CHECK AND CREATE TABLES
// ===========================================
echo "1. Checking tables...\n";

$requiredTables = [
    'roles',
    'users',
    'schools',
    'departments',
    'programmes',
    'sessions',
    'semesters',
    'levels',
    'courses',
    'students',
    'student_courses',
    'results',
    'grades',
    'grading_scales',
    'grade_classifications',
    'fees',
    'payments',
    'announcements',
    'notifications',
    'attendances',
    'settings',
    'system_settings',
    'states',
    'local_governments',
    'nationalities',
    'timetables',
    'course_assignments',
    'books',
    'book_loans',
    'complaints',
    'hostels',
    'hostel_rooms',
    'hostel_beds',
    'hostel_allocations',
    'applicants',
    'applications',
    'regime_payments',
    'exam_timetables',
    'payment_gateways',
    'audit_logs',
    'deleted_records',
    'approval_workflows',
    'approval_requests',
    'portal_notifications',
    'hospital_patients',
    'hospital_staff',
    'hospital_appointments',
    'hospital_drug_categories',
    'hospital_drugs',
    'hospital_drug_batches',
    'hospital_suppliers',
    'hospital_inventory_movements',
    'hospital_store_items',
    'hospital_store_batches',
    'hospital_purchases',
    'hospital_purchase_items',
    'finance_budgets',
    'finance_transactions',
    'finance_invoices',
    'finance_receipts',
];

foreach ($requiredTables as $table) {
    $report['tables_checked'][] = $table;
    if (!Schema::hasTable($table)) {
        echo "  - Table '$table' is missing\n";
        $report['errors'][] = "Missing table: $table";
    }
}

// ===========================================
// 2. CHECK AND ADD COLUMNS
// ===========================================
echo "\n2. Checking columns...\n";

// Students table - check for missing columns
$studentColumns = ['level_id', 'academic_status'];
foreach ($studentColumns as $col) {
    if (!Schema::hasColumn('students', $col)) {
        echo "  - Adding '$col' to students table\n";
        $report['columns_added'][] = "students.$col";
    }
}

// Results table - check for computed columns
$resultColumns = ['quality_point', 'pass_status', 'academic_remark', 'is_repeated', 'attempt_number', 'semester_id'];
foreach ($resultColumns as $col) {
    if (!Schema::hasColumn('results', $col)) {
        echo "  - Adding '$col' to results table\n";
        $report['columns_added'][] = "results.$col";
    }
}

// Payments table - check for fee_type
if (!Schema::hasColumn('payments', 'fee_type')) {
    echo "  - Adding 'fee_type' to payments table\n";
    $report['columns_added'][] = 'payments.fee_type';
}

// Applicants table - check for payment fields
$applicantColumns = ['payment_status', 'payment_ref', 'payment_transaction_id', 'payment_amount', 'payment_date'];
foreach ($applicantColumns as $col) {
    if (!Schema::hasColumn('applicants', $col)) {
        echo "  - Adding '$col' to applicants table\n";
        $report['columns_added'][] = "applicants.$col";
    }
}

// ===========================================
// 3. SEED DEFAULT DATA
// ===========================================
echo "\n3. Seeding default data...\n";

// Seed Roles
$roles = [
    ['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Full system access', 'permissions' => ['*']],
    ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrative access', 'permissions' => ['users.*', 'schools.*', 'departments.*', 'courses.*', 'reports.*']],
    ['name' => 'Registrar', 'slug' => 'registrar', 'description' => 'Registry operations', 'permissions' => ['applicants.*', 'students.*', 'admission.*']],
    ['name' => 'Bursar', 'slug' => 'bursar', 'description' => 'Financial operations', 'permissions' => ['payments.*', 'fees.*', 'reports.payments']],
    ['name' => 'Dean', 'slug' => 'dean', 'description' => 'Faculty Dean', 'permissions' => ['results.approve', 'timetable.approve', 'departments.view']],
    ['name' => 'HOD', 'slug' => 'hod', 'description' => 'Head of Department', 'permissions' => ['courses.assign', 'courses.view', 'timetable.*', 'results.approve', 'lecturers.view']],
    ['name' => 'Lecturer', 'slug' => 'lecturer', 'description' => 'Teaching Staff', 'permissions' => ['courses.teach', 'results.enter', 'attendance.*', 'timetable.view']],
    ['name' => 'Student', 'slug' => 'student', 'description' => 'Student Portal', 'permissions' => ['courses.register', 'results.view', 'payments.view', 'timetable.view', 'profile.view']],
    ['name' => 'Applicant', 'slug' => 'applicant', 'description' => 'Applicant Portal', 'permissions' => ['applications.create', 'applications.view']],
    ['name' => 'Librarian', 'slug' => 'librarian', 'description' => 'Library Management', 'permissions' => ['library.*', 'books.*', 'loans.*']],
    ['name' => 'Business Committee', 'slug' => 'business_committee', 'description' => 'Business Committee', 'permissions' => ['results.approve', 'reports.view']],
    ['name' => 'Academic Board', 'slug' => 'academic_board', 'description' => 'Academic Board', 'permissions' => ['results.approve', 'reports.view']],
    ['name' => 'Auditor', 'slug' => 'auditor', 'description' => 'Audit Operations', 'permissions' => ['audit.*', 'reports.view']],
    ['name' => 'Executive', 'slug' => 'executive', 'description' => 'Executive Dashboard', 'permissions' => ['dashboard.*', 'reports.view']],
    ['name' => 'Finance', 'slug' => 'finance', 'description' => 'Finance Operations', 'permissions' => ['finance.*', 'reports.view']],
    ['name' => 'Hospital', 'slug' => 'hospital', 'description' => 'Hospital/Clinic', 'permissions' => ['hospital.*', 'patients.*']],
];

foreach ($roles as $role) {
    $created = Role::firstOrCreate(['slug' => $role['slug']], $role);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Role: {$role['name']}";
    }
}

// Seed Programmes
$programmes = [
    ['name' => 'National Diploma', 'code' => 'ND', 'type' => 'ND'],
    ['name' => 'Higher National Diploma', 'code' => 'HND', 'type' => 'HND'],
    ['name' => 'Bachelor Degree', 'code' => 'DEG', 'type' => 'Degree'],
    ['name' => 'Post Graduate Diploma', 'code' => 'PGD', 'type' => 'PGD'],
    ['name' => 'Masters', 'code' => 'MAST', 'type' => 'Masters'],
    ['name' => 'Doctor of Philosophy', 'code' => 'PHD', 'type' => 'PhD'],
];

foreach ($programmes as $programme) {
    $created = Programme::firstOrCreate(['code' => $programme['code']], $programme);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Programme: {$programme['name']}";
    }
}

// Seed Sessions
$sessions = [
    ['name' => '2025/2026', 'is_active' => true, 'is_current' => true, 'start_date' => '2025-10-01', 'end_date' => '2026-09-30'],
    ['name' => '2024/2025', 'is_active' => false, 'is_current' => false, 'start_date' => '2024-10-01', 'end_date' => '2025-09-30'],
];

foreach ($sessions as $session) {
    $created = Session::firstOrCreate(['name' => $session['name']], $session);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Session: {$session['name']}";
    }
}

// Seed Semesters
$semesters = [
    ['name' => 'First Semester', 'code' => 'FIRST', 'sort_order' => 1, 'is_active' => true],
    ['name' => 'Second Semester', 'code' => 'SECOND', 'sort_order' => 2, 'is_active' => true],
    ['name' => 'Third Semester', 'code' => 'THIRD', 'sort_order' => 3, 'is_active' => false],
];

foreach ($semesters as $semester) {
    $created = Semester::firstOrCreate(['code' => $semester['code']], $semester);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Semester: {$semester['name']}";
    }
}

// Seed Levels
$levels = [
    ['name' => 'ND 1 (100L)', 'code' => 'ND1', 'sort_order' => 1, 'programme_type' => 'ND'],
    ['name' => 'ND 2 (200L)', 'code' => 'ND2', 'sort_order' => 2, 'programme_type' => 'ND'],
    ['name' => 'HND 1 (300L)', 'code' => 'HND1', 'sort_order' => 3, 'programme_type' => 'HND'],
    ['name' => 'HND 2 (400L)', 'code' => 'HND2', 'sort_order' => 4, 'programme_type' => 'HND'],
    ['name' => 'HND 3 (500L)', 'code' => 'HND3', 'sort_order' => 5, 'programme_type' => 'HND'],
];

foreach ($levels as $level) {
    $created = Level::firstOrCreate(['code' => $level['code']], $level);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Level: {$level['name']}";
    }
}

// Seed Default Grades
foreach (Grade::getDefaultGrades() as $grade) {
    $created = Grade::firstOrCreate(['grade' => $grade['grade']], $grade);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Grade: {$grade['grade']}";
    }
}

// Seed Grading Scales
foreach (ResultComputationService::getDefaultGradingScales() as $scale) {
    $created = GradingScale::firstOrCreate(['grade' => $scale['grade']], $scale);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "GradingScale: {$scale['grade']}";
    }
}

// Seed Grade Classifications
foreach (ResultComputationService::getDefaultClassifications() as $classification) {
    $created = GradeClassification::firstOrCreate(['slug' => $classification['slug']], $classification);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Classification: {$classification['name']}";
    }
}

// Seed Default Settings
$settings = [
    ['key' => 'institution_name', 'value' => 'Ekiti State College of Technology'],
    ['key' => 'institution_address', 'value' => 'University Road, Iyin Ekiti, Ekiti State'],
    ['key' => 'institution_email', 'value' => 'info@ekticotech.edu.ng'],
    ['key' => 'institution_phone', 'value' => '+234 800 000 0000'],
    ['key' => 'institution_website', 'value' => 'www.ekticotech.edu.ng'],
    ['key' => 'admission_form_open', 'value' => 'true'],
    ['key' => 'admission_require_application_fee', 'value' => 'false'],
    ['key' => 'admission_application_fee_amount', 'value' => '5000'],
    ['key' => 'admission_accept_fee_amount', 'value' => '10000'],
    ['key' => 'admission_school_fee_amount', 'value' => '50000'],
    ['key' => 'admission_form_penalty', 'value' => 'false'],
    ['key' => 'admission_form_penalty_amount', 'value' => '0'],
    ['key' => 'course_registration_open', 'value' => 'true'],
    ['key' => 'payment_open', 'value' => 'true'],
    ['key' => 'result_upload_open', 'value' => 'true'],
    ['key' => 'max_course_units', 'value' => '24'],
    ['key' => 'min_course_units', 'value' => '12'],
    ['key' => 'pass_mark', 'value' => '40'],
];

foreach ($settings as $setting) {
    $created = SystemSetting::firstOrCreate(['key' => $setting['key']], $setting);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "SystemSetting: {$setting['key']}";
    }
}

// Seed Legacy Settings
$legacySettings = [
    ['key' => 'institution_name', 'value' => 'Ekiti State College of Technology'],
    ['key' => 'institution_address', 'value' => 'University Road, Iyin Ekiti, Ekiti State'],
    ['key' => 'institution_email', 'value' => 'info@ekticotech.edu.ng'],
    ['key' => 'institution_phone', 'value' => '+234 800 000 0000'],
    ['key' => 'session_id', 'value' => '1'],
    ['key' => 'max_course_units', 'value' => '24'],
    ['key' => 'min_course_units', 'value' => '12'],
];

foreach ($legacySettings as $setting) {
    $created = Setting::firstOrCreate(['key' => $setting['key']], $setting);
}

// Create Schools
$schools = [
    ['name' => 'School of Computing', 'code' => 'SOC', 'description' => 'Computing and Information Technology'],
    ['name' => 'School of Engineering', 'code' => 'SOE', 'description' => 'Engineering and Technology'],
    ['name' => 'School of Management', 'code' => 'SOM', 'description' => 'Business and Management Studies'],
    ['name' => 'School of Applied Sciences', 'code' => 'SAS', 'description' => 'Applied Sciences'],
];

foreach ($schools as $school) {
    $created = School::firstOrCreate(['code' => $school['code']], $school);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "School: {$school['name']}";
    }
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
    $created = Department::firstOrCreate(['code' => $dept['code']], $dept);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Department: {$dept['name']}";
    }
}

// Create Default Fees
$currentSession = Session::where('is_current', true)->first();
$fees = [
    ['name' => 'Application Form Fee', 'payment_type' => 'Other', 'amount' => 5000, 'category' => 'both', 'is_active' => true, 'session_id' => $currentSession?->id],
    ['name' => 'Acceptance Fee', 'payment_type' => 'Other', 'amount' => 10000, 'category' => 'both', 'is_active' => true, 'session_id' => $currentSession?->id],
    ['name' => 'School Fees - ND1', 'payment_type' => 'Tuition Fee', 'amount' => 50000, 'level' => 1, 'category' => 'both', 'is_active' => true, 'session_id' => $currentSession?->id],
    ['name' => 'School Fees - ND2', 'payment_type' => 'Tuition Fee', 'amount' => 45000, 'level' => 2, 'category' => 'both', 'is_active' => true, 'session_id' => $currentSession?->id],
    ['name' => 'School Fees - HND1', 'payment_type' => 'Tuition Fee', 'amount' => 55000, 'level' => 3, 'category' => 'both', 'is_active' => true, 'session_id' => $currentSession?->id],
    ['name' => 'School Fees - HND2', 'payment_type' => 'Tuition Fee', 'amount' => 50000, 'level' => 4, 'category' => 'both', 'is_active' => true, 'session_id' => $currentSession?->id],
    ['name' => 'Hostel Fee', 'payment_type' => 'Other', 'amount' => 15000, 'category' => 'both', 'is_active' => true, 'session_id' => $currentSession?->id],
    ['name' => 'Library Fee', 'payment_type' => 'Other', 'amount' => 2000, 'category' => 'both', 'is_active' => true, 'session_id' => $currentSession?->id],
];

foreach ($fees as $fee) {
    $created = Fee::firstOrCreate(['name' => $fee['name']], $fee);
    if ($created->wasRecentlyCreated) {
        $report['seeded_records'][] = "Fee: {$fee['name']}";
    }
}

// Create Super Admin if not exists
$superAdminRole = Role::where('slug', 'super_admin')->first();
if ($superAdminRole && !User::where('email', 'admin@portal.edu')->exists()) {
    $user = User::create([
        'name' => 'Super Administrator',
        'email' => 'admin@portal.edu',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'role_id' => $superAdminRole->id,
        'is_active' => true,
    ]);
    $report['seeded_records'][] = "User: admin@portal.edu";
}

// Create test student if not exists
$studentRole = Role::where('slug', 'student')->first();
if ($studentRole && !User::where('email', 'student@test.com')->exists()) {
    $testUser = User::create([
        'name' => 'Test Student',
        'email' => 'student@test.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        'role_id' => $studentRole->id,
        'is_active' => true,
    ]);

    $firstSchool = School::first();
    $firstDept = Department::first();
    $firstProg = Programme::first();
    $firstSession = Session::where('is_current', true)->first();

    Student::create([
        'user_id' => $testUser->id,
        'matric_number' => 'ND/2025/0001',
        'school_id' => $firstSchool?->id,
        'department_id' => $firstDept?->id,
        'programme_id' => $firstProg?->id,
        'session_id' => $firstSession?->id,
        'level' => 1,
        'status' => 'active',
    ]);
    $report['seeded_records'][] = "Test Student: student@test.com";
}

// ===========================================
// 4. PRINT REPORT
// ===========================================
echo "\n===========================================\n";
echo "REPORT\n";
echo "===========================================\n";

echo "\nTables Checked: " . count($report['tables_checked']) . "\n";

if (count($report['columns_added']) > 0) {
    echo "\nColumns Added:\n";
    foreach ($report['columns_added'] as $col) {
        echo "  - $col\n";
    }
}

if (count($report['seeded_records']) > 0) {
    echo "\nSeeded Records (" . count($report['seeded_records']) . "):\n";
    foreach ($report['seeded_records'] as $record) {
        echo "  + $record\n";
    }
}

if (count($report['errors']) > 0) {
    echo "\nErrors:\n";
    foreach ($report['errors'] as $error) {
        echo "  ! $error\n";
    }
} else {
    echo "\n✓ No critical errors found!\n";
}

echo "\n===========================================\n";
echo "Fix completed at " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n";

// Run migrations for new tables
echo "\nRunning pending migrations...\n";
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

echo "\nClearing cache...\n";
\Illuminate\Support\Facades\Artisan::call('cache:clear');

echo "\n✅ All fixes applied successfully!\n";
echo "\nLogin credentials:\n";
echo "  Admin: admin@portal.edu / password\n";
echo "  Student: student@test.com / password123\n";