<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemHealthLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'check_name',
        'status',
        'message',
        'details',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    const STATUS_HEALTHY = 'healthy';
    const STATUS_WARNING = 'warning';
    const STATUS_CRITICAL = 'critical';

    /**
     * Check if the table exists
     */
    public static function tableExists(): bool
    {
        return Schema::hasTable('system_health_logs');
    }

    public static function logCheck(string $name, string $status, string $message = null, array $details = []): ?self
    {
        try {
            if (!self::tableExists()) {
                return null;
            }
            return self::create([
                'check_name' => $name,
                'status' => $status,
                'message' => $message,
                'details' => json_encode($details),
                'checked_at' => now(),
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getLatestChecks(): array
    {
        try {
            if (!self::tableExists()) {
                return [];
            }
            return self::select('check_name', 'status', 'message', 'checked_at')
                ->orderByDesc('checked_at')
                ->get()
                ->groupBy('check_name')
                ->map(fn ($group) => $group->first())
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function getHealthSummary(): array
    {
        try {
            $checks = self::getLatestChecks();
            $healthy = count(array_filter($checks, fn ($c) => $c['status'] === self::STATUS_HEALTHY));
            $warning = count(array_filter($checks, fn ($c) => $c['status'] === self::STATUS_WARNING));
            $critical = count(array_filter($checks, fn ($c) => $c['status'] === self::STATUS_CRITICAL));

            return [
                'total' => count($checks),
                'healthy' => $healthy,
                'warning' => $warning,
                'critical' => $critical,
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'healthy' => 0,
                'warning' => 0,
                'critical' => 0,
            ];
        }
    }
}