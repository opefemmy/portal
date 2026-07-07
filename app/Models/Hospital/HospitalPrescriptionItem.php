<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalPrescriptionItem extends Model
{
    protected $fillable = [
        'prescription_id', 'drug_id', 'drug_name', 'dosage', 'frequency', 'duration',
        'quantity', 'instructions', 'is_dispensed'
    ];

    protected $casts = [
        'is_dispensed' => 'boolean',
    ];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(HospitalPrescription::class, 'prescription_id');
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(HospitalDrug::class, 'drug_id');
    }
}