<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemBackup extends Model
{
    protected $fillable = [
        'name',
        'type',
        'file_path',
        'file_size',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    const TYPE_DATABASE = 'database';
    const TYPE_FILES = 'files';
    const TYPE_STORAGE = 'storage';
    const TYPE_CONFIG = 'config';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * Check if the table exists
     */
    public static function tableExists(): bool
    {
        return Schema::hasTable('system_backups');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public static function createBackup(string $type, string $name): self
    {
        if (!self::tableExists()) {
            throw new \RuntimeException('system_backups table does not exist. Please run migrations.');
        }

        return self::create([
            'name' => $name,
            'type' => $type,
            'status' => self::STATUS_PENDING,
            'created_by' => auth()->user()->name ?? 'System',
        ]);
    }

    public function markInProgress(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function markCompleted(string $filePath, string $fileSize): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'file_path' => $filePath,
            'file_size' => $fileSize,
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }
}