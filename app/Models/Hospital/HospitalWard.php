<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalWard extends Model
{
    use SoftDeletes;

    protected $table = 'hospital_wards';

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

    /**
     * Recompute and persist `available_beds` from the live bed statuses.
     *
     * Called by WardController whenever an assignment or discharge changes
     * bed.status, so the ward-level counter stays in sync with the bed-level
     * truth without forcing every caller to do the math.
     */
    public function refreshAvailableBeds(): void
    {
        $available = $this->beds()->where('status', 'available')->count();
        $this->forceFill(['available_beds' => $available])->save();
    }
}