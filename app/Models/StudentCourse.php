<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentCourse extends Model
{
    protected $fillable = ['student_id', 'course_id', 'session_id', 'semester', 'status', 'course_type', 'carry_over_from_id'];

    const TYPE_MAIN = 'main';
    const TYPE_ELECTIVE = 'elective';
    const TYPE_CARRY_OVER = 'carry_over';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function carryOverFrom(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'carry_over_from_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getResult()
    {
        return $this->results()->first();
    }
}