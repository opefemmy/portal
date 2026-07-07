<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalAppointment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'scheduled_by', 'appointment_date', 'appointment_time',
        'status', 'complaint', 'notes', 'checked_in_at', 'completed_at'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'checked_in_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'doctor_id');
    }

    public function scheduledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(HospitalMedicalRecord::class, 'appointment_id');
    }
}