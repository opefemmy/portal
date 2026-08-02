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
        'auto_dispense_drug_id',
        'auto_dispense_quantity',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_appointment' => 'boolean',
        'auto_dispense_quantity' => 'integer',
    ];

    public function autoDispenseDrug(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(HospitalDrug::class, 'auto_dispense_drug_id');
    }

    public function hasAutoDispense(): bool
    {
        return !is_null($this->auto_dispense_drug_id);
    }

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
