<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalDutyRoster extends Model
{
    protected $table = 'hospital_duty_roster';

    protected $fillable = [
        'staff_id', 'duty_date', 'start_time', 'end_time',
        'shift', 'location', 'notes', 'is_active',
    ];

    protected $casts = [
        'duty_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'staff_id');
    }
}