<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalRecordTransfer extends Model
{
    protected $table = 'hospital_record_transfers';

    protected $fillable = [
        'patient_id', 'transfer_to', 'transfer_reason', 'notes',
        'transferred_by', 'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}