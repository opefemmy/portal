<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalLabResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lab_request_id', 'recorded_by', 'test_name', 'parameter', 'result', 'unit',
        'reference_range', 'status', 'notes', 'recorded_at'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function labRequest(): BelongsTo
    {
        return $this->belongsTo(HospitalLabRequest::class, 'lab_request_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'recorded_by');
    }
}