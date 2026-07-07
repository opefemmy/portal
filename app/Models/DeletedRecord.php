<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeletedRecord extends Model
{
    public $table = 'deleted_records';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'table_name', 'record_id', 'record_data', 'deletion_reason',
        'ip_address', 'user_agent', 'created_at'
    ];

    protected $casts = [
        'record_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function archive(Model $model, ?string $reason = null): self
    {
        return static::create([
            'user_id' => auth()->id() ?? null,
            'table_name' => $model->getTable(),
            'record_id' => $model->getKey(),
            'record_data' => $model->toArray(),
            'deletion_reason' => $reason,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}