<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class SystemVersion extends Model
{
    protected $fillable = [
        'version',
        'release_name',
        'release_date',
        'description',
        'migration_status',
        'installed_by',
        'installed_at',
        'is_current',
    ];

    protected $casts = [
        'release_date' => 'date',
        'installed_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    /**
     * Check if the table exists
     */
    public static function tableExists(): bool
    {
        return Schema::hasTable('system_versions');
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public function scopeCompleted($query)
    {
        return $query->where('migration_status', 'completed');
    }

    public static function getCurrentVersion(): ?self
    {
        if (!static::tableExists()) {
            return null;
        }

        try {
            return static::current()->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function registerVersion(string $version, string $releaseName = null): self
    {
        if (!static::tableExists()) {
            throw new \RuntimeException('system_versions table does not exist. Please run migrations.');
        }

        // Mark all versions as not current
        static::query()->update(['is_current' => false]);

        // Create new version
        return static::create([
            'version' => $version,
            'release_name' => $releaseName,
            'release_date' => now()->toDateString(),
            'migration_status' => 'completed',
            'installed_by' => auth()->user()->name ?? 'System',
            'installed_at' => now(),
            'is_current' => true,
        ]);
    }
}