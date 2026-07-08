<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['code', 'title', 'units', 'semester', 'school_id', 'department_id', 'programme_id', 'level', 'description', 'prerequisites'];

    protected $casts = [
        'prerequisites' => 'array',
    ];

    // Level mapping for Nigerian system
    const LEVEL_MAP = [
        'ND1' => 1,
        'ND' => 2,
        'HND1' => 3,
        'HND2' => 4,
        '100L' => 1,
        '200L' => 2,
        '300L' => 3,
        '400L' => 4,
        '500L' => 5,
        '600L' => 6,
    ];

    const LEVEL_NAMES = [
        1 => 'ND1 (100L)',
        2 => 'ND (200L)',
        3 => 'HND1 (300L)',
        4 => 'HND2 (400L)',
        5 => '500L',
        6 => '600L',
    ];

    public static function getLevelFromName($name): ?int
    {
        return self::LEVEL_MAP[$name] ?? null;
    }

    public static function getLevelName($level): string
    {
        return self::LEVEL_NAMES[$level] ?? (string) $level;
    }

    public function getLevelDisplayAttribute(): string
    {
        return self::LEVEL_NAMES[$this->level] ?? (string) $this->level;
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

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function studentCourses(): HasMany
    {
        return $this->hasMany(StudentCourse::class);
    }

    public function classification()
    {
        return $this->hasOne(CourseClassification::class);
    }
}