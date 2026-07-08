<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public static function logCheck(string $name, string $status, string $message = null, array $details = []): self
    {
        return self::create([
            'check_name' => $name,
            'status' => $status,
            'message' => $message,
            'details' => json_encode($details),
            'checked_at' => now(),
        ]);
    }

    public static function getLatestChecks(): array
    {
        return self::select('check_name', 'status', 'message', 'checked_at')
            ->orderByDesc('checked_at')
            ->get()
            ->groupBy('check_name')
            ->map(fn ($group) => $group->first())
            ->toArray();
    }

    public static function getHealthSummary(): array
    {
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
    }
}