<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalAuditTrail extends Model
{
    protected $table = 'hospital_audit_trail';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_role', 'patient_id',
        'action', 'subject_type', 'subject_id',
        'ip_address', 'user_agent',
        'before', 'after', 'metadata', 'created_at',
    ];

    protected $casts = [
        'before'     => 'array',
        'after'      => 'array',
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }
}