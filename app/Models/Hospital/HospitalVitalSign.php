<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalVitalSign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'recorded_by', 'temperature', 'blood_pressure_systolic',
        'blood_pressure_diastolic', 'weight', 'height', 'pulse', 'oxygen_level',
        'blood_sugar', 'notes'
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'weight' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'recorded_by');
    }

    public function getBloodPressureAttribute(): string
    {
        return "{$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
    }
}