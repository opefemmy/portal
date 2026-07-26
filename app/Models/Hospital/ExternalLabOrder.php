<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalLabOrder extends Model
{
    protected $table = 'hospital_lab_orders';

    protected $fillable = [
        'visit_id',
        'test_name',
        'test_type',
        'urgency',
        'result',
        'result_date',
        'status',
    ];

    protected $casts = [
        'result_date' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(HospitalVisit::class, 'visit_id');
    }
}
