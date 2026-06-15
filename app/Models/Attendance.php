<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['student_course_id', 'date', 'status', 'marked_by', 'remarks'];

    protected $casts = [
        'date' => 'date',
    ];

    public function studentCourse(): BelongsTo
    {
        return $this->belongsTo(StudentCourse::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}