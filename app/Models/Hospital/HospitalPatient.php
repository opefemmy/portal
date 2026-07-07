<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalPatient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'patient_number', 'first_name', 'last_name', 'other_name', 'gender',
        'date_of_birth', 'blood_group', 'genotype', 'phone', 'email', 'address',
        'state', 'lga', 'nationality', 'next_of_kin_name', 'next_of_kin_phone',
        'next_of_kin_relationship', 'next_of_kin_address', 'patient_type', 'registered_by', 'is_active'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(HospitalAppointment::class, 'patient_id');
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(HospitalMedicalRecord::class, 'patient_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(HospitalPrescription::class, 'patient_id');
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(HospitalLabRequest::class, 'patient_id');
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(HospitalAdmission::class, 'patient_id');
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(HospitalVitalSign::class, 'patient_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(HospitalReferral::class, 'patient_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}" . ($this->other_name ? " {$this->other_name}" : '');
    }

    public function getAgeAttribute(): int
    {
        return $this->date_of_birth->age;
    }
}