<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['user_id', 'matric_number', 'school_id', 'department_id', 'programme_id', 'session_id', 'level', 'status', 'state_id', 'lga_id', 'nationality_id'];

    const LEVEL_NAMES = [
        1 => 'ND1 (100L)',
        2 => 'ND (200L)',
        3 => 'HND1 (300L)',
        4 => 'HND2 (400L)',
        5 => '500L',
        6 => '600L',
    ];

    public function getLevelDisplayAttribute(): string
    {
        return self::LEVEL_NAMES[$this->level] ?? (string) $this->level;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function studentCourses(): HasMany
    {
        return $this->hasMany(StudentCourse::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(LocalGovernment::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function calculateGPA($sessionId = null, $semester = null)
    {
        $query = $this->results();

        if ($sessionId) {
            $query->whereHas('studentCourse', function($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            });
        }

        if ($semester) {
            $query->whereHas('studentCourse', function($q) use ($semester) {
                $q->where('semester', $semester);
            });
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return 0.0;
        }

        $totalPoints = 0;
        $totalUnits = 0;

        foreach ($results as $result) {
            $course = $result->studentCourse->course;
            $units = $course->units ?? 0;
            $gradePoint = $result->grade_point ?? 0;

            $totalPoints += $gradePoint * $units;
            $totalUnits += $units;
        }

        return $totalUnits > 0 ? round($totalPoints / $totalUnits, 2) : 0.0;
    }

    public function calculateCGPA()
    {
        $results = $this->results()->get();

        if ($results->isEmpty()) {
            return 0.0;
        }

        $totalPoints = 0;
        $totalUnits = 0;

        foreach ($results as $result) {
            $course = $result->studentCourse->course;
            $units = $course->units ?? 0;
            $gradePoint = $result->grade_point ?? 0;

            $totalPoints += $gradePoint * $units;
            $totalUnits += $units;
        }

        return $totalUnits > 0 ? round($totalPoints / $totalUnits, 2) : 0.0;
    }
}