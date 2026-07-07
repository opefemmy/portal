<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalBed extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ward_id', 'bed_number', 'status', 'patient_id', 'occupied_at', 'discharged_at'
    ];

    public function ward(): BelongsTo
    {
        return $this->belongsTo(HospitalWard::class, 'ward_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(HospitalAdmission::class, 'bed_id');
    }
}