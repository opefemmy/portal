<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;

class HospitalVisit extends Model
{
    protected $table = 'hospital_visits';

    protected $fillable = [
        'patient_id',
        'visit_number',
        'visit_date',
        'visit_type',
        'department',
        'doctor_id',
        'chief_complaint',
        'diagnosis',
        'treatment',
        'status',
        'next_visit_date',
        'next_visit_notes',
        'vital_signs_temperature',
        'vital_signs_bp',
        'vital_signs_pulse',
        'vital_signs_respiration',
        'vital_signs_oxygen',
        'height',
        'weight',
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'next_visit_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(ExternalPatient::class, 'patient_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(HospitalPrescription::class, 'visit_id');
    }

    public function labOrders()
    {
        return $this->hasMany(HospitalLabOrder::class, 'visit_id');
    }

    public function doctor()
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public static function generateVisitNumber()
    {
        $prefix = 'VIS';
        $date = date('Ymd');
        $lastVisit = self::whereDate('visit_date', today())->orderBy('id', 'desc')->first();

        if ($lastVisit) {
            $lastNumber = intval(substr($lastVisit->visit_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }
}
