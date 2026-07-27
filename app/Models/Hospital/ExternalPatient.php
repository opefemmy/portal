<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExternalPatient extends Model
{
    protected $table = 'hospital_external_patients';

    protected $fillable = [
        'patient_number',
        'access_code',
        'access_code_expires_at',
        'password',
        'last_login_at',
        'first_name',
        'last_name',
        'full_name',
        'email',
        'phone',
        'gender',
        'date_of_birth',
        'age',
        'blood_group',
        'genotype',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'chronic_conditions',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'access_code_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? bcrypt($value) : null;
    }

    public function checkAccessCode($code)
    {
        return $this->access_code === $code
            && $this->access_code_expires_at
            && now()->lessThan($this->access_code_expires_at);
    }

    public static function generateAccessCode($patient)
    {
        $code = strtoupper(Str::random(8));
        $patient->update([
            'access_code' => $code,
            'access_code_expires_at' => now()->addDays(30),
        ]);
        return $code;
    }

    public function generateNewAccessCode()
    {
        return self::generateAccessCode($this);
    }

    public function visits()
    {
        return $this->hasMany(HospitalVisit::class, 'patient_id');
    }

    public function appointments()
    {
        return $this->hasMany(HospitalAppointment::class, 'patient_id');
    }

    public function communications()
    {
        return $this->hasMany(ExternalCommunication::class, 'patient_id');
    }

    public function prescriptions()
    {
        return $this->hasManyThrough(ExternalPrescription::class, HospitalVisit::class);
    }

    public function service_requests()
    {
        return $this->hasMany(HospitalServiceRequest::class, 'patient_id');
    }

    public function latestVisit()
    {
        return $this->hasOne(HospitalVisit::class, 'patient_id')->latestOfMany();
    }

    public static function generatePatientNumber()
    {
        $prefix = 'EXT';
        $year = date('Y');
        $lastPatient = self::whereYear('created_at', $year)->orderBy('id', 'desc')->first();

        if ($lastPatient) {
            $lastNumber = intval(substr($lastPatient->patient_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$year}{$newNumber}";
    }
}
