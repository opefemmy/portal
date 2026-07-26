<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalCommunication extends Model
{
    protected $table = 'hospital_communications';

    protected $fillable = [
        'patient_id',
        'visit_id',
        'staff_id',
        'type',
        'subject',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ExternalPatient::class, 'patient_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(HospitalVisit::class, 'visit_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'staff_id');
    }
}
