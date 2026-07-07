<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalAdmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'bed_id', 'admission_number', 'admission_date',
        'discharge_date', 'status', 'reason', 'diagnosis', 'treatment_plan',
        'discharge_notes', 'daily_rate', 'total_charges'
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
        'daily_rate' => 'decimal:2',
        'total_charges' => 'decimal:2',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'doctor_id');
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(HospitalBed::class, 'bed_id');
    }

    public function getDurationInDaysAttribute(): int
    {
        if ($this->discharge_date) {
            return $this->admission_date->diffInDays($this->discharge_date);
        }
        return $this->admission_date->diffInDays(now());
    }
}