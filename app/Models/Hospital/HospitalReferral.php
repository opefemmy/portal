<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalReferral extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'referrer_id', 'referred_to_id', 'external_facility', 'reason',
        'notes', 'status', 'referred_at', 'accepted_at'
    ];

    protected $casts = [
        'referred_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'referrer_id');
    }

    public function referredTo(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'referred_to_id');
    }
}