<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalStaff extends Model
{
    use SoftDeletes;

    protected $table = 'hospital_staff';

    protected $fillable = [
        'user_id', 'staff_number', 'first_name', 'last_name', 'staff_type',
        'phone', 'email', 'is_active', 'is_available'
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'is_available' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(HospitalAppointment::class, 'staff_id');
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(HospitalMedicalRecord::class, 'staff_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(HospitalPrescription::class, 'staff_id');
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(HospitalLabRequest::class, 'staff_id');
    }

    public function vitalSigns(): HasMany
    {
        return $this->hasMany(HospitalVitalSign::class, 'recorded_by');
    }

    public function referralsMade(): HasMany
    {
        return $this->hasMany(HospitalReferral::class, 'referrer_id');
    }

    public function referralsReceived(): HasMany
    {
        return $this->hasMany(HospitalReferral::class, 'referred_to_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isDoctor(): bool
    {
        return $this->staff_type === 'doctor';
    }

    public function isNurse(): bool
    {
        return $this->staff_type === 'nurse';
    }
}