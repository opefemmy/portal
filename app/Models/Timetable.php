<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timetable extends Model
{
    protected $fillable = ['course_assignment_id', 'venue', 'day', 'start_time', 'end_time', 'week', 'session_id', 'status', 'approved_by', 'approved_at'];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'approved_at' => 'datetime',
    ];

    public function courseAssignment(): BelongsTo
    {
        return $this->belongsTo(CourseAssignment::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getCourseAttribute()
    {
        return $this->courseAssignment->course ?? null;
    }

    public function getLecturerAttribute()
    {
        return $this->courseAssignment->lecturer ?? null;
    }
}