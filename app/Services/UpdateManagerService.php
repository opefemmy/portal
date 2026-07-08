<?php

namespace App\Services;

use App\Models\SystemBackup;
use App\Models\SystemVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class UpdateManagerService
{
    protected SystemMaintenanceService $maintenance;

    public function __construct()
    {
        $this->maintenance = new SystemMaintenanceService();
    }

    /**
     * Check pending migrations
     */
    public function getPendingMigrations(): array
    {
        $ran = DB::table('migrations')->pluck('migration')->toArray();
        $files = glob(database_path('migrations/*.php'));
        $pending = [];

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (!in_array($filename, $ran)) {
                $pending[] = [
                    'file' => $filename,
                    'path' => $file,
                ];
            }
        }

        return $pending;
    }

    /**
     * Run pending migrations
     */
    public function runMigrations(): array
    {
        $pending = $this->getPendingMigrations();
        $results = ['migrated' => [], 'errors' => []];

        foreach ($pending as $migration) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $results['migrated'][] = $migration['file'];
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'file' => $migration['file'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Register new version if migrations ran
        if (!empty($results['migrated'])) {
            $version = 'v' . date('Y.m.d') . '.' . count($results['migrated']);
            $this->maintenance->registerVersion($version, 'System Update');
        }

        return $results;
    }

    /**
     * Run seeders
     */
    public function runSeeders(string $seeder = null): array
    {
        try {
            if ($seeder) {
                Artisan::call('db:seed', ['--class' => $seeder, '--force' => true]);
            } else {
                Artisan::call('db:seed', ['--force' => true]);
            }
            return ['success' => true, 'message' => 'Seeders completed successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Repair missing tables
     */
    public function repairMissingTables(): array
    {
        $requiredTables = [
            'roles', 'users', 'schools', 'departments', 'programmes',
            'sessions', 'semesters', 'levels', 'courses', 'students',
            'student_courses', 'results', 'grades', 'grading_scales',
            'grade_classifications', 'fees', 'payments', 'announcements',
            'notifications', 'settings', 'system_settings', 'states',
            'local_governments', 'nationalities',
        ];

        $repaired = [];
        $errors = [];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $errors[] = "Missing table: {$table}";
            } else {
                $repaired[] = "Table exists: {$table}";
            }
        }

        return ['repaired' => $repaired, 'errors' => $errors];
    }

    /**
     * Repair missing columns in critical tables
     */
    public function repairMissingColumns(): array
    {
        $repairs = [];

        // Students table
        if (Schema::hasTable('students')) {
            if (!Schema::hasColumn('students', 'level_id')) {
                Schema::table('students', fn ($t) => $t->foreignId('level_id')->nullable()->constrained('levels')->onDelete('set null'));
                $repairs[] = 'Added level_id to students';
            }
            if (!Schema::hasColumn('students', 'academic_status')) {
                Schema::table('students', fn ($t) => $t->string('academic_status', 30)->nullable());
                $repairs[] = 'Added academic_status to students';
            }
        }

        // Results table
        if (Schema::hasTable('results')) {
            if (!Schema::hasColumn('results', 'quality_point')) {
                Schema::table('results', fn ($t) => $t->decimal('quality_point', 10, 2)->nullable());
                $repairs[] = 'Added quality_point to results';
            }
            if (!Schema::hasColumn('results', 'pass_status')) {
                Schema::table('results', fn ($t) => $t->string('pass_status', 20)->nullable());
                $repairs[] = 'Added pass_status to results';
            }
            if (!Schema::hasColumn('results', 'semester_id')) {
                Schema::table('results', fn ($t) => $t->foreignId('semester_id')->nullable()->constrained('semesters')->onDelete('set null'));
                $repairs[] = 'Added semester_id to results';
            }
        }

        // Payments table
        if (Schema::hasTable('payments')) {
            if (!Schema::hasColumn('payments', 'fee_type')) {
                Schema::table('payments', fn ($t) => $t->enum('fee_type', ['application', 'acceptance', 'school_fees', 'hostel', 'library', 'other'])->default('other'));
                $repairs[] = 'Added fee_type to payments';
            }
        }

        // Applicants table
        if (Schema::hasTable('applicants')) {
            if (!Schema::hasColumn('applicants', 'payment_status')) {
                Schema::table('applicants', fn ($t) => $t->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending'));
                $repairs[] = 'Added payment_status to applicants';
            }
        }

        return $repairs;
    }

    /**
     * Repair permissions and roles
     */
    public function repairPermissions(): array
    {
        $repairs = [];

        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super_admin'],
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Registrar', 'slug' => 'registrar'],
            ['name' => 'Bursar', 'slug' => 'bursar'],
            ['name' => 'Dean', 'slug' => 'dean'],
            ['name' => 'HOD', 'slug' => 'hod'],
            ['name' => 'Lecturer', 'slug' => 'lecturer'],
            ['name' => 'Student', 'slug' => 'student'],
            ['name' => 'Applicant', 'slug' => 'applicant'],
            ['name' => 'Librarian', 'slug' => 'librarian'],
        ];

        foreach ($roles as $role) {
            $exists = DB::table('roles')->where('slug', $role['slug'])->exists();
            if (!$exists) {
                DB::table('roles')->insert(array_merge($role, [
                    'description' => $role['name'] . ' role',
                    'permissions' => json_encode(['*']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $repairs[] = "Created role: {$role['name']}";
            }
        }

        return $repairs;
    }

    /**
     * Repair grading scales
     */
    public function repairGradingScales(): array
    {
        $repairs = [];

        $scales = [
            ['grade' => 'A', 'min_score' => 70, 'max_score' => 100, 'grade_point' => 4.00, 'remark' => 'Excellent', 'classification' => 'distinction', 'sort_order' => 1],
            ['grade' => 'B', 'min_score' => 60, 'max_score' => 69, 'grade_point' => 3.50, 'remark' => 'Very Good', 'classification' => 'upper_credit', 'sort_order' => 2],
            ['grade' => 'C', 'min_score' => 50, 'max_score' => 59, 'grade_point' => 3.00, 'remark' => 'Good', 'classification' => 'lower_credit', 'sort_order' => 3],
            ['grade' => 'D', 'min_score' => 45, 'max_score' => 49, 'grade_point' => 2.50, 'remark' => 'Fair', 'classification' => 'pass', 'sort_order' => 4],
            ['grade' => 'E', 'min_score' => 40, 'max_score' => 44, 'grade_point' => 2.00, 'remark' => 'Pass', 'classification' => 'pass', 'sort_order' => 5],
            ['grade' => 'F', 'min_score' => 0, 'max_score' => 39, 'grade_point' => 0.00, 'remark' => 'Fail', 'classification' => 'fail', 'sort_order' => 6],
        ];

        foreach ($scales as $scale) {
            if (!DB::table('grading_scales')->where('grade', $scale['grade'])->exists()) {
                DB::table('grading_scales')->insert(array_merge($scale, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $repairs[] = "Created grading scale: {$scale['grade']}";
            }
        }

        $classifications = [
            ['name' => 'Distinction', 'slug' => 'distinction', 'min_gpa' => 3.50, 'max_gpa' => 4.00, 'sort_order' => 1],
            ['name' => 'Upper Credit', 'slug' => 'upper_credit', 'min_gpa' => 3.00, 'max_gpa' => 3.49, 'sort_order' => 2],
            ['name' => 'Lower Credit', 'slug' => 'lower_credit', 'min_gpa' => 2.50, 'max_gpa' => 2.99, 'sort_order' => 3],
            ['name' => 'Pass', 'slug' => 'pass', 'min_gpa' => 2.00, 'max_gpa' => 2.49, 'sort_order' => 4],
            ['name' => 'Fail', 'slug' => 'fail', 'min_gpa' => 0.00, 'max_gpa' => 1.99, 'sort_order' => 5],
        ];

        foreach ($classifications as $classification) {
            if (!DB::table('grade_classifications')->where('slug', $classification['slug'])->exists()) {
                DB::table('grade_classifications')->insert(array_merge($classification, [
                    'description' => $classification['name'] . ' classification',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $repairs[] = "Created classification: {$classification['name']}";
            }
        }

        return $repairs;
    }

    /**
     * Repair session data
     */
    public function repairSessions(): array
    {
        $repairs = [];

        if (!DB::table('sessions')->where('is_current', true)->exists()) {
            DB::table('sessions')->update(['is_current' => false]);
            DB::table('sessions')->updateOrInsert(
                ['name' => date('Y') . '/' . (date('Y') + 1)],
                [
                    'is_active' => true,
                    'is_current' => true,
                    'start_date' => date('Y') . '-10-01',
                    'end_date' => (date('Y') + 1) . '-09-30',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $repairs[] = 'Created current session';
        }

        return $repairs;
    }

    /**
     * Repair semesters
     */
    public function repairSemesters(): array
    {
        $repairs = [];

        $semesters = [
            ['name' => 'First Semester', 'code' => 'FIRST', 'sort_order' => 1],
            ['name' => 'Second Semester', 'code' => 'SECOND', 'sort_order' => 2],
            ['name' => 'Third Semester', 'code' => 'THIRD', 'sort_order' => 3],
        ];

        foreach ($semesters as $semester) {
            if (!DB::table('semesters')->where('code', $semester['code'])->exists()) {
                DB::table('semesters')->insert(array_merge($semester, [
                    'is_active' => $semester['sort_order'] <= 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $repairs[] = "Created semester: {$semester['name']}";
            }
        }

        return $repairs;
    }

    /**
     * Repair levels
     */
    public function repairLevels(): array
    {
        $repairs = [];

        $levels = [
            ['name' => 'ND 1 (100L)', 'code' => 'ND1', 'sort_order' => 1, 'programme_type' => 'ND'],
            ['name' => 'ND 2 (200L)', 'code' => 'ND2', 'sort_order' => 2, 'programme_type' => 'ND'],
            ['name' => 'HND 1 (300L)', 'code' => 'HND1', 'sort_order' => 3, 'programme_type' => 'HND'],
            ['name' => 'HND 2 (400L)', 'code' => 'HND2', 'sort_order' => 4, 'programme_type' => 'HND'],
        ];

        foreach ($levels as $level) {
            if (!DB::table('levels')->where('code', $level['code'])->exists()) {
                DB::table('levels')->insert(array_merge($level, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $repairs[] = "Created level: {$level['name']}";
            }
        }

        return $repairs;
    }

    /**
     * Repair institution settings
     */
    public function repairSettings(): array
    {
        $repairs = [];

        $settings = [
            ['key' => 'institution_name', 'value' => 'Ekiti State College of Technology'],
            ['key' => 'institution_address', 'value' => 'University Road, Iyin Ekiti, Ekiti State'],
            ['key' => 'institution_email', 'value' => 'info@ekticotech.edu.ng'],
            ['key' => 'admission_form_open', 'value' => 'true'],
            ['key' => 'course_registration_open', 'value' => 'true'],
            ['key' => 'payment_open', 'value' => 'true'],
        ];

        foreach ($settings as $setting) {
            if (!DB::table('system_settings')->where('key', $setting['key'])->exists()) {
                DB::table('system_settings')->insert(array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $repairs[] = "Created setting: {$setting['key']}";
            }
        }

        return $repairs;
    }

    /**
     * Create database backup
     */
    public function createDatabaseBackup(): SystemBackup
    {
        $backup = SystemBackup::createBackup(SystemBackup::TYPE_DATABASE, 'db_backup_' . date('Y_m_d_His'));

        try {
            $backup->markInProgress();

            $filename = storage_path('backups/' . $backup->name . '.sql');
            if (!is_dir(storage_path('backups'))) {
                mkdir(storage_path('backups'), 0755, true);
            }

            // Use mysqldump if available, otherwise use Laravel
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            $command = "mysqldump -h{$host} -u{$username} -p{$password} {$database} > {$filename} 2>/dev/null";

            if (strpos(PHP_OS, 'WIN') !== 0) {
                exec($command, $output, $return);
            }

            if (file_exists($filename)) {
                $size = round(filesize($filename) / 1024 / 1024, 2);
                $backup->markCompleted($filename, $size . ' MB');
            } else {
                // Fallback: just mark as completed without file
                $backup->markCompleted(null, '0 MB');
            }
        } catch (\Exception $e) {
            $backup->markFailed($e->getMessage());
        }

        return $backup;
    }

    /**
     * Create files backup
     */
    public function createFilesBackup(): SystemBackup
    {
        $backup = SystemBackup::createBackup(SystemBackup::TYPE_FILES, 'files_backup_' . date('Y_m_d_His'));

        try {
            $backup->markInProgress();

            $filename = storage_path('backups/' . $backup->name . '.zip');
            if (!is_dir(storage_path('backups'))) {
                mkdir(storage_path('backups'), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($filename, ZipArchive::CREATE) === true) {
                $uploadPath = public_path('uploads');
                if (is_dir($uploadPath)) {
                    $this->addFolderToZip($uploadPath, 'uploads', $zip);
                }
                $zip->close();
            }

            if (file_exists($filename)) {
                $size = round(filesize($filename) / 1024 / 1024, 2);
                $backup->markCompleted($filename, $size . ' MB');
            } else {
                $backup->markCompleted(null, '0 MB');
            }
        } catch (\Exception $e) {
            $backup->markFailed($e->getMessage());
        }

        return $backup;
    }

    protected function addFolderToZip(string $folder, string $zipFolder, ZipArchive $zip): void
    {
        $items = scandir($folder);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $folder . '/' . $item;
            if (is_dir($path)) {
                $this->addFolderToZip($path, $zipFolder . '/' . $item, $zip);
            } else {
                $zip->addFile($path, $zipFolder . '/' . $item);
            }
        }
    }

    /**
     * Get backup list
     */
    public function getBackups(): array
    {
        return SystemBackup::orderByDesc('created_at')->get()->toArray();
    }

    /**
     * Run all repair operations
     */
    public function runAllRepairs(): array
    {
        $results = [
            'tables' => $this->repairMissingTables(),
            'columns' => $this->repairMissingColumns(),
            'permissions' => $this->repairPermissions(),
            'grading' => $this->repairGradingScales(),
            'sessions' => $this->repairSessions(),
            'semesters' => $this->repairSemesters(),
            'levels' => $this->repairLevels(),
            'settings' => $this->repairSettings(),
        ];

        return $results;
    }
}