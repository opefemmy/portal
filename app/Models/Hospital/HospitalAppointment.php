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

    protected $table = 'hospital_appointments';

    protected $fillable = [
        'patient_id', 'staff_id', 'appointment_number', 'appointment_date',
        'status', 'symptoms', 'notes'
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

    public function staff(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'staff_id');
    }

    /**
     * @deprecated Use staff() instead
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'staff_id');
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