<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalPrescription extends Model
{
    protected $table = 'hospital_prescriptions';

    protected $fillable = [
        'visit_id',
        'medication_name',
        'dosage',
        'frequency',
        'duration',
        'instructions',
        'quantity',
        'is_dispensed',
    ];

    protected $casts = [
        'is_dispensed' => 'boolean',
    ];

    public function visit()
    {
        return $this->belongsTo(HospitalVisit::class, 'visit_id');
    }
}
