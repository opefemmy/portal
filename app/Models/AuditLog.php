<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use SoftDeletes;

    public $table = 'audit_logs';

    protected $fillable = [
        'user_id', 'module', 'action', 'description', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'computer_name',
        'status', 'error_message'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function log(array $data): self
    {
        return static::create([
            'user_id' => auth()->id() ?? null,
            'module' => $data['module'] ?? null,
            'action' => $data['action'] ?? null,
            'description' => $data['description'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'old_values' => $data['old_values'] ?? null,
            'new_values' => $data['new_values'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $data['status'] ?? 'success',
            'error_message' => $data['error_message'] ?? null,
        ]);
    }
}