<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HospitalPrescriptionItem extends Model
{
    protected $table = 'hospital_prescription_items';

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

    /**
     * Order items that reference this prescription_item (used for payment
     * gating and pharmacy queue filtering).
     */
    public function orderItems(): MorphMany
    {
        return $this->morphMany(HospitalOrderItem::class, 'orderable');
    }
}