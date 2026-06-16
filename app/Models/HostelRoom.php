<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelRoom extends Model
{
    protected $fillable = [
        'hostel_id', 'room_number', 'floor', 'capacity',
        'available_beds', 'type', 'is_active'
    ];

    public function hostel()
    {
        return $this->belongsTo(Hostel::class);
    }

    public function beds()
    {
        return $this->hasMany(HostelBed::class);
    }

    public function allocations()
    {
        return $this->hasMany(HostelAllocation::class);
    }
}