<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalAppointment extends Model
{
    protected $table = 'hospital_appointments';

    protected $fillable = [
        'patient_id',
        'appointment_number',
        'appointment_date',
        'department',
        'doctor_id',
        'purpose',
        'status',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ExternalPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'doctor_id');
    }

    public static function generateAppointmentNumber()
    {
        $prefix = 'APT';
        $date = date('Ymd');
        $lastAppointment = self::whereDate('appointment_date', today())->orderBy('id', 'desc')->first();

        if ($lastAppointment) {
            $lastNumber = intval(substr($lastAppointment->appointment_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "{$prefix}{$date}{$newNumber}";
    }
}
