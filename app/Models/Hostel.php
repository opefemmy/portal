<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hostel extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'capacity', 'available_rooms',
        'description', 'location', 'gender', 'is_active'
    ];

    public function rooms()
    {
        return $this->hasMany(HostelRoom::class);
    }

    public function beds()
    {
        return $this->hasManyThrough(HostelBed::class, HostelRoom::class);
    }

    public function allocations()
    {
        return $this->hasMany(HostelAllocation::class);
    }
}