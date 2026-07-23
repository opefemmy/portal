<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Level extends Model
{
    protected $fillable = ['name', 'code', 'sort_order', 'programme_type', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function scopeActive($query)
    {
        if (!Schema::hasTable('levels')) {
            return $query->whereRaw('1=0');
        }
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, $type)
    {
        if (!Schema::hasTable('levels')) {
            return $query->whereRaw('1=0');
        }
        return $query->where('programme_type', $type);
    }
}
