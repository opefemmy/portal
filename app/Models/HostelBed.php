<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostelBed extends Model
{
    protected $fillable = [
        'hostel_room_id', 'bed_number', 'status', 'student_id'
    ];

    public function room()
    {
        return $this->belongsTo(HostelRoom::class, 'hostel_room_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * All `HostelAllocation` rows that point at this bed, regardless of
     * status. Used by the admin room-occupant view to render the
     * active allocation's check-in date and session. Filtering to
     * `status='active'` happens at the query site (controller), not here,
     * so legacy rows that haven't been normalised to a single status
     * still resolve.
     */
    public function allocations()
    {
        return $this->hasMany(HostelAllocation::class, 'bed_id');
    }
}