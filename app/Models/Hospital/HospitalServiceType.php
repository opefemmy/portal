<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalServiceType extends Model
{
    protected $table = 'hospital_service_types';

    protected $fillable = [
        'name',
        'description',
        'category',
        'amount',
        'is_active',
        'requires_appointment',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_appointment' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if a service with the same name already exists
     */
    public static function hasDuplicate(string $name, int $excludeId = null): bool
    {
        $query = self::whereRaw('LOWER(name) = ?', [strtolower(trim($name))]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
