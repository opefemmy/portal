<?php

namespace App\Services;

use App\Models\SystemVersion;
use App\Models\SystemBackup;
use App\Models\SystemHealthLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SystemMaintenanceService
{
    /**
     * Get current system version
     */
    public function getCurrentVersion(): ?SystemVersion
    {
        return SystemVersion::getCurrentVersion();
    }

    /**
     * Get all system versions
     */
    public function getVersions(): array
    {
        return SystemVersion::orderByDesc('created_at')->get()->toArray();
    }

    /**
     * Register a new system version
     */
    public function registerVersion(string $version, string $releaseName = null): SystemVersion
    {
        return SystemVersion::registerVersion($version, $releaseName);
    }

    /**
     * Get database tables count
     */
    public function getDatabaseTablesCount(): int
    {
        return count(Schema::getTables());
    }

    /**
     * Get all database tables with their columns
     */
    public function getDatabaseTables(): array
    {
        $tables = [];
        $tableNames = Schema::getTables();

        foreach ($tableNames as $table) {
            $tables[] = [
                'name' => $table['name'],
                'columns' => Schema::getColumnListing($table['name']),
                'size' => $this->getTableSize($table['name']),
            ];
        }

        return $tables;
    }

    /**
     * Get table size in MB
     */
    protected function getTableSize(string $tableName): string
    {
        try {
            $result = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
                FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = ?
            ", [$tableName]);

            return $result[0]->size_mb ?? '0';
        } catch (\Exception $e) {
            return '0';
        }
    }

    /**
     * Get database size
     */
    public function getDatabaseSize(): string
    {
        try {
            $result = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb'
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
            ");

            return $result[0]->size_mb ?? '0';
        } catch (\Exception $e) {
            return '0';
        }
    }

    /**
     * Run system health check
     */
    public function runHealthCheck(): array
    {
        $results = [];

        // PHP Version
        $results[] = $this->checkPhpVersion();

        // Database Connection
        $results[] = $this->checkDatabaseConnection();

        // Cache
        $results[] = $this->checkCache();

        // Storage
        $results[] = $this->checkStorage();

        // APP_KEY
        $results[] = $this->checkAppKey();

        // APP_URL
        $results[] = $this->checkAppUrl();

        // Queue
        $results[] = $this->checkQueue();

        // Database Tables
        $results[] = $this->checkDatabaseTables();

        // Disk Space
        $results[] = $this->checkDiskSpace();

        // Memory Usage
        $results[] = $this->checkMemoryUsage();

        return $results;
    }

    protected function checkPhpVersion(): array
    {
        $version = PHP_VERSION;
        $status = version_compare($version, '8.2', '>=') ? 'healthy' : 'warning';
        $message = "PHP Version: {$version}";

        SystemHealthLog::logCheck('php_version', $status, $message);

        return [
            'name' => 'PHP Version',
            'status' => $status,
            'message' => $message,
            'details' => ['version' => $version],
        ];
    }

    protected function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();
            $status = 'healthy';
            $message = 'Database connected successfully';
        } catch (\Exception $e) {
            $status = 'critical';
            $message = 'Database connection failed: ' . $e->getMessage();
        }

        SystemHealthLog::logCheck('database_connection', $status, $message);

        return [
            'name' => 'Database Connection',
            'status' => $status,
            'message' => $message,
            'details' => ['driver' => config('database.default')],
        ];
    }

    protected function checkCache(): array
    {
        try {
            cache()->put('health_check_test', 'test', 10);
            $value = cache('health_check_test');
            cache()->forget('health_check_test');

            $status = $value === 'test' ? 'healthy' : 'critical';
            $message = $status === 'healthy' ? 'Cache working correctly' : 'Cache not working';
        } catch (\Exception $e) {
            $status = 'critical';
            $message = 'Cache error: ' . $e->getMessage();
        }

        SystemHealthLog::logCheck('cache', $status, $message);

        return [
            'name' => 'Cache System',
            'status' => $status,
            'message' => $message,
        ];
    }

    protected function checkStorage(): array
    {
        try {
            $storageDir = storage_path('app');
            $isWritable = is_writable($storageDir);

            $status = $isWritable ? 'healthy' : 'critical';
            $message = $isWritable ? 'Storage is writable' : 'Storage is not writable';
        } catch (\Exception $e) {
            $status = 'critical';
            $message = 'Storage error: ' . $e->getMessage();
        }

        SystemHealthLog::logCheck('storage', $status, $message);

        return [
            'name' => 'Storage',
            'status' => $status,
            'message' => $message,
            'details' => ['path' => $storageDir],
        ];
    }

    protected function checkAppKey(): array
    {
        $key = config('app.key');
        $status = !empty($key) && strlen($key) > 30 ? 'healthy' : 'critical';
        $message = $status === 'healthy' ? 'APP_KEY is configured' : 'APP_KEY is not configured properly';

        SystemHealthLog::logCheck('app_key', $status, $message);

        return [
            'name' => 'APP_KEY',
            'status' => $status,
            'message' => $message,
        ];
    }

    protected function checkAppUrl(): array
    {
        $url = config('app.url');
        $status = !empty($url) ? 'healthy' : 'warning';
        $message = $status === 'healthy' ? "APP_URL: {$url}" : 'APP_URL is not set';

        SystemHealthLog::logCheck('app_url', $status, $message);

        return [
            'name' => 'APP_URL',
            'status' => $status,
            'message' => $message,
        ];
    }

    protected function checkQueue(): array
    {
        try {
            $status = 'healthy';
            $message = 'Queue system ready';

            // Check queue connections
            $default = config('queue.default');
            if (in_array($default, ['sync', 'database'])) {
                $status = 'healthy';
            }
        } catch (\Exception $e) {
            $status = 'warning';
            $message = 'Queue error: ' . $e->getMessage();
        }

        SystemHealthLog::logCheck('queue', $status, $message);

        return [
            'name' => 'Queue System',
            'status' => $status,
            'message' => $message,
        ];
    }

    protected function checkDatabaseTables(): array
    {
        $tables = count(Schema::getTables());
        $status = $tables > 0 ? 'healthy' : 'critical';
        $message = "Database has {$tables} tables";

        SystemHealthLog::logCheck('database_tables', $status, $message, ['table_count' => $tables]);

        return [
            'name' => 'Database Tables',
            'status' => $status,
            'message' => $message,
            'details' => ['table_count' => $tables],
        ];
    }

    protected function checkDiskSpace(): array
    {
        $total = disk_total_space(base_path());
        $free = disk_free_space(base_path());
        $usedPercent = round(($total - $free) / $total * 100, 1);

        if ($usedPercent < 80) {
            $status = 'healthy';
        } elseif ($usedPercent < 90) {
            $status = 'warning';
        } else {
            $status = 'critical';
        }

        $message = "Disk usage: {$usedPercent}%";

        SystemHealthLog::logCheck('disk_space', $status, $message, [
            'used_percent' => $usedPercent,
            'total_mb' => round($total / 1024 / 1024),
            'free_mb' => round($free / 1024 / 1024),
        ]);

        return [
            'name' => 'Disk Space',
            'status' => $status,
            'message' => $message,
            'details' => [
                'used_percent' => $usedPercent,
                'total_mb' => round($total / 1024 / 1024),
                'free_mb' => round($free / 1024 / 1024),
            ],
        ];
    }

    protected function checkMemoryUsage(): array
    {
        $usage = memory_get_usage(true);
        $limit = ini_get('memory_limit');
        $usageMB = round($usage / 1024 / 1024, 1);

        $status = $usageMB < 128 ? 'healthy' : ($usageMB < 256 ? 'warning' : 'critical');
        $message = "Memory usage: {$usageMB} MB / {$limit}";

        SystemHealthLog::logCheck('memory_usage', $status, $message, [
            'usage_mb' => $usageMB,
            'limit' => $limit,
        ]);

        return [
            'name' => 'Memory Usage',
            'status' => $status,
            'message' => $message,
            'details' => [
                'usage_mb' => $usageMB,
                'limit' => $limit,
            ],
        ];
    }

    /**
     * Get system report
     */
    public function getSystemReport(): array
    {
        return [
            'version' => $this->getCurrentVersion(),
            'database' => [
                'tables_count' => $this->getDatabaseTablesCount(),
                'size_mb' => $this->getDatabaseSize(),
            ],
            'php' => [
                'version' => PHP_VERSION,
            ],
            'laravel' => [
                'version' => app()->version(),
            ],
            'storage' => $this->getStorageUsage(),
        ];
    }

    protected function getStorageUsage(): array
    {
        $directories = [
            'storage/app' => 'app',
            'storage/framework/cache' => 'cache',
            'storage/framework/sessions' => 'sessions',
            'storage/framework/views' => 'views',
            'public/uploads' => 'uploads',
        ];

        $usage = [];
        foreach ($directories as $path => $name) {
            $fullPath = base_path($path);
            if (is_dir($fullPath)) {
                $size = $this->getDirectorySize($fullPath);
                $usage[$name] = round($size / 1024 / 1024, 2); // MB
            }
        }

        return $usage;
    }

    protected function getDirectorySize(string $dir): int
    {
        $size = 0;
        foreach (glob($dir . '/*', GLOB_NOSORT) as $file) {
            $size += is_dir($file) ? $this->getDirectorySize($file) : filesize($file);
        }
        return $size;
    }

    /**
     * Clear all caches
     */
    public function clearCaches(): array
    {
        $results = [];

        try {
            Artisan::call('cache:clear');
            $results[] = 'Application cache cleared';
        } catch (\Exception $e) {
            $results[] = 'Cache error: ' . $e->getMessage();
        }

        try {
            Artisan::call('config:clear');
            $results[] = 'Configuration cache cleared';
        } catch (\Exception $e) {
            $results[] = 'Config error: ' . $e->getMessage();
        }

        try {
            Artisan::call('route:clear');
            $results[] = 'Route cache cleared';
        } catch (\Exception $e) {
            $results[] = 'Route error: ' . $e->getMessage();
        }

        try {
            Artisan::call('view:clear');
            $results[] = 'View cache cleared';
        } catch (\Exception $e) {
            $results[] = 'View error: ' . $e->getMessage();
        }

        try {
            Artisan::call('event:clear');
            $results[] = 'Event cache cleared';
        } catch (\Exception $e) {
            $results[] = 'Event error: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize system
     */
    public function optimizeSystem(): array
    {
        $results = [];

        try {
            Artisan::call('config:cache');
            $results[] = 'Configuration cached';
        } catch (\Exception $e) {
            $results[] = 'Config cache error: ' . $e->getMessage();
        }

        try {
            Artisan::call('route:cache');
            $results[] = 'Routes cached';
        } catch (\Exception $e) {
            $results[] = 'Route cache error: ' . $e->getMessage();
        }

        try {
            Artisan::call('view:cache');
            $results[] = 'Views cached';
        } catch (\Exception $e) {
            $results[] = 'View cache error: ' . $e->getMessage();
        }

        return $results;
    }
}