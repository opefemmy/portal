<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalClinicalNote extends Model
{
    protected $table = 'hospital_clinical_notes';

    protected $fillable = [
        'patient_id', 'staff_id', 'appointment_id', 'medical_record_id',
        'note_type',
        'subjective', 'objective', 'assessment', 'plan', 'free_text',
        'signed_by_name', 'signed_at', 'signature_hash',
        'is_amended', 'amended_by',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'is_amended' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'staff_id');
    }

    /**
     * Compute the deterministic hash for the electronic signature.
     */
    public function sign(string $signerName): string
    {
        $payload = implode('|', [
            $this->id ?? 'new',
            $signerName,
            (string) $this->subjective,
            (string) $this->objective,
            (string) $this->assessment,
            (string) $this->plan,
            (string) $this->free_text,
        ]);
        $this->signed_by_name = $signerName;
        $this->signed_at      = now();
        $this->signature_hash = hash('sha256', $payload);
        return $this->signature_hash;
    }
}