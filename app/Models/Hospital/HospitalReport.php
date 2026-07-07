<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'generated_by', 'report_type', 'title', 'content', 'file_path', 'status', 'released_at'
    ];

    protected $casts = [
        'released_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}