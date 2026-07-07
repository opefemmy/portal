<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalWard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'total_beds', 'available_beds', 'daily_rate', 'description', 'is_active'
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function beds(): HasMany
    {
        return $this->hasMany(HospitalBed::class, 'ward_id');
    }

    public function availableBeds(): HasMany
    {
        return $this->hasMany(HospitalBed::class, 'ward_id')->where('status', 'available');
    }

    public function occupiedBeds(): HasMany
    {
        return $this->hasMany(HospitalBed::class, 'ward_id')->where('status', 'occupied');
    }
}