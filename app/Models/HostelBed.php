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
}