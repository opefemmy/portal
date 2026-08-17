<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Services\SystemMaintenanceService;
use App\Services\UpdateManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MaintenanceController extends Controller
{
    use EnforcesPermission;

    protected SystemMaintenanceService $maintenance;
    protected UpdateManagerService $updater;

    public function __construct()
    {
        $this->maintenance = new SystemMaintenanceService();
        $this->updater = new UpdateManagerService();
    }

    /**
     * Dashboard
     */
    public function dashboard()
    {
        $this->requirePermission('maintenance.dashboard.view');
        $version = $this->maintenance->getCurrentVersion();
        $health = $this->maintenance->runHealthCheck();
        $backups = $this->updater->getBackups();

        $pendingMigrations = $this->updater->getPendingMigrations();

        return view('admin.maintenance.dashboard', compact(
            'version',
            'health',
            'backups',
            'pendingMigrations'
        ));
    }

    /**
     * System Health Check
     */
    public function healthCheck()
    {
        $this->requirePermission('maintenance.health.view');
        $results = $this->maintenance->runHealthCheck();
        return view('admin.maintenance.health', compact('results'));
    }

    /**
     * Run health check with repairs
     */
    public function runHealthCheck(Request $request)
    {
        $this->requirePermission('maintenance.health.repair');
        $checkName = $request->get('check_name');

        // Run specific repair based on check name
        $results = match ($checkName) {
            'database_tables' => $this->updater->repairMissingTables(),
            'database_columns' => $this->updater->repairMissingColumns(),
            'permissions' => $this->updater->repairPermissions(),
            'grading' => $this->updater->repairGradingScales(),
            'sessions' => $this->updater->repairSessions(),
            'semesters' => $this->updater->repairSemesters(),
            'levels' => $this->updater->repairLevels(),
            'settings' => $this->updater->repairSettings(),
            'payment_types' => $this->updater->repairPaymentTypes(),
            default => [],
        };

        // Re-run health check
        $health = $this->maintenance->runHealthCheck();

        return back()->with('success', 'Repair completed: ' . count($results) . ' items fixed');
    }

    /**
     * Update Manager
     */
    public function updateManager()
    {
        $this->requirePermission('maintenance.updates.view');
        $pendingMigrations = $this->updater->getPendingMigrations();
        $backups = $this->updater->getBackups();

        return view('admin.maintenance.updates', compact('pendingMigrations', 'backups'));
    }

    /**
     * Run migrations
     */
    public function runMigrations()
    {
        $this->requirePermission('maintenance.updates.apply');
        // Try to create backup first (may fail if table doesn't exist)
        try {
            $this->updater->createDatabaseBackup();
        } catch (\Throwable $e) {
            // Ignore backup errors — the caller (runMigrations / runAllRepairs)
            // should still proceed. Catch \Throwable (not \Exception) so
            // TypeError / ValueError / other PHP 8 Error subclasses don't
            // 500 the whole endpoint. Mirrors gateway-empty-body-throwable.
            \Log::warning('Backup before maintenance failed: ' . $e->getMessage());
        }

        $results = $this->updater->runMigrations();

        $message = 'Migrations completed: ' . count($results['migrated']) . ' migrated';

        if (!empty($results['errors'])) {
            $message .= ' - ' . collect($results['errors'])->pluck('error')->implode(', ');
        }

        if (!empty($results['errors'])) {
            return back()->with('error', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Run seeders
     */
    public function runSeeders(Request $request)
    {
        $this->requirePermission('maintenance.updates.apply');
        $seeder = $request->get('seeder');
        $result = $this->updater->runSeeders($seeder);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Run all repairs
     */
    public function runRepairs()
    {
        $this->requirePermission('maintenance.repairs.run');
        // Try to create backup first (may fail if table doesn't exist)
        try {
            $this->updater->createDatabaseBackup();
        } catch (\Throwable $e) {
            // Ignore backup errors — the caller (runMigrations / runAllRepairs)
            // should still proceed. Catch \Throwable (not \Exception) so
            // TypeError / ValueError / other PHP 8 Error subclasses don't
            // 500 the whole endpoint. Mirrors gateway-empty-body-throwable.
            \Log::warning('Backup before maintenance failed: ' . $e->getMessage());
        }

        $results = $this->updater->runAllRepairs();

        $totalFixed = 0;
        foreach ($results as $category => $items) {
            $totalFixed += count($items);
        }

        return back()->with('success', "Repairs completed: {$totalFixed} items fixed");
    }

    /**
     * Migration Manager
     */
    public function migrations()
    {
        $this->requirePermission('maintenance.updates.view');
        $pending = $this->updater->getPendingMigrations();
        $ran = \DB::table('migrations')->pluck('migration')->toArray();

        return view('admin.maintenance.migrations', compact('pending', 'ran'));
    }

    /**
     * Database Repair
     */
    public function databaseRepair()
    {
        $this->requirePermission('maintenance.repairs.view');
        $tables = $this->maintenance->getDatabaseTables();

        return view('admin.maintenance.database', compact('tables'));
    }

    /**
     * Module Scanner
     */
    public function moduleScanner()
    {
        $this->requirePermission('maintenance.scanners.view');
        $modules = [
            'Student Portal' => class_exists(\App\Models\Student::class),
            'Applicant Portal' => class_exists(\App\Models\Applicant::class),
            'Payment System' => class_exists(\App\Models\Payment::class),
            'Result Computation' => class_exists(\App\Services\ResultComputationService::class),
            'Hospital Module' => class_exists(\App\Models\Hospital\HospitalPatient::class),
            'Library Module' => class_exists(\App\Models\Book::class),
            'Finance Module' => class_exists(\App\Models\Finance\FinanceTransaction::class),
            'Hostel Module' => class_exists(\App\Models\Hostel::class),
            'Attendance' => class_exists(\App\Models\Attendance::class),
            'Timetable' => class_exists(\App\Models\Timetable::class),
        ];

        $controllers = glob(app_path('Http/Controllers/*/*.php'));
        $services = glob(app_path('Services/*.php'));
        $models = glob(app_path('Models/*.php'));

        return view('admin.maintenance.modules', compact('modules', 'controllers', 'services', 'models'));
    }

    /**
     * Permission Scanner
     */
    public function permissionScanner()
    {
        $this->requirePermission('maintenance.scanners.view');
        $roles = \DB::table('roles')->get();
        $users = \DB::table('users')->with('role')->get();

        return view('admin.maintenance.permissions', compact('roles', 'users'));
    }

    /**
     * Storage Scanner
     */
    public function storageScanner()
    {
        $this->requirePermission('maintenance.scanners.view');
        $directories = [
            'storage/app' => is_dir(base_path('storage/app')),
            'storage/framework/cache' => is_dir(base_path('storage/framework/cache')),
            'storage/framework/sessions' => is_dir(base_path('storage/framework/sessions')),
            'storage/framework/views' => is_dir(base_path('storage/framework/views')),
            'public/uploads' => is_dir(base_path('public/uploads')),
        ];

        $usage = $this->maintenance->getSystemReport()['storage'] ?? [];

        return view('admin.maintenance.storage', compact('directories', 'usage'));
    }

    /**
     * Cache Manager
     */
    public function cacheManager()
    {
        $this->requirePermission('maintenance.cache.view');
        return view('admin.maintenance.cache');
    }

    /**
     * Clear caches
     */
    public function clearCaches()
    {
        $this->requirePermission('maintenance.cache.manage');
        $results = $this->maintenance->clearCaches();

        return back()->with('success', implode(', ', $results));
    }

    /**
     * Optimize system
     */
    public function optimizeSystem()
    {
        $this->requirePermission('maintenance.cache.manage');
        $results = $this->maintenance->optimizeSystem();

        return back()->with('success', implode(', ', $results));
    }

    /**
     * Backup Manager
     */
    public function backupManager()
    {
        $this->requirePermission('maintenance.backups.view');
        $backups = $this->updater->getBackups();

        return view('admin.maintenance.backups', compact('backups'));
    }

    /**
     * Create backup
     */
    public function createBackup(Request $request)
    {
        $this->requirePermission('maintenance.backups.create');
        $type = $request->get('type', 'database');

        if ($type === 'database') {
            $backup = $this->updater->createDatabaseBackup();
        } else {
            $backup = $this->updater->createFilesBackup();
        }

        return back()->with('success', 'Backup created: ' . $backup->name);
    }

    /**
     * Log Viewer
     */
    public function logViewer(Request $request)
    {
        $this->requirePermission('maintenance.logs.view');
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return view('admin.maintenance.logs', ['logs' => [], 'error' => 'Log file not found']);
        }

        $lines = $request->get('lines', 100);
        $content = file($logFile);
        $logs = array_slice($content, -$lines);

        return view('admin.maintenance.logs', compact('logs'));
    }

    /**
     * Version Manager
     */
    public function versionManager()
    {
        $this->requirePermission('maintenance.versions.view');
        $versions = $this->maintenance->getVersions();
        $current = $this->maintenance->getCurrentVersion();

        return view('admin.maintenance.versions', compact('versions', 'current'));
    }

    /**
     * Register new version
     */
    public function registerVersion(Request $request)
    {
        $this->requirePermission('maintenance.versions.manage');
        $request->validate([
            'version' => 'required|string',
            'release_name' => 'nullable|string',
        ]);

        $version = $this->maintenance->registerVersion(
            $request->version,
            $request->release_name
        );

        return back()->with('success', 'Version registered: ' . $version->version);
    }

    /**
     * System Report
     */
    public function systemReport()
    {
        $this->requirePermission('maintenance.report.view');
        $report = $this->maintenance->getSystemReport();
        $tables = $this->maintenance->getDatabaseTables();

        $routes = [];
        foreach (\Route::getRoutes() as $route) {
            if ($route->uri() && !str_starts_with($route->uri(), '_')) {
                $routes[] = [
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'method' => implode('|', $route->methods()),
                ];
            }
        }

        $controllers = count(glob(app_path('Http/Controllers/**/*.php')));
        $services = count(glob(app_path('Services/*.php')));
        $models = count(glob(app_path('Models/*.php')));

        return view('admin.maintenance.report', compact('report', 'tables', 'routes', 'controllers', 'services', 'models'));
    }
}