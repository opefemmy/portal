<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A historical result row ingested from a previous institution or
 * earlier session. See the migration for the column rationale.
 */
class PreviousResult extends Model
{
    protected $fillable = [
        'student_id',
        'course_code',
        'course_title',
        'units',
        'session_name',
        'semester',
        'level',
        'ca', 'test', 'assignment', 'exam',
        'total_score',
        'grade', 'grade_point', 'remarks',
        'source_institution',
        'uploaded_by', 'uploaded_at',
    ];

    protected $casts = [
        'ca'          => 'decimal:2',
        'test'        => 'decimal:2',
        'assignment'  => 'decimal:2',
        'exam'        => 'decimal:2',
        'total_score' => 'decimal:2',
        'grade_point' => 'decimal:1',
        'units'       => 'integer',
        'level'       => 'integer',
        'uploaded_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * If the importer supplied total_score but no grade, derive the
     * grade + grade_point using the existing Grade table rules.
     * Returns $this for chaining.
     */
    public function assignGrade(): self
    {
        if ($this->grade && $this->grade_point !== null) {
            return $this;
        }

        $grade = Grade::getGrade($this->total_score);
        if ($grade) {
            $this->grade = $grade->grade;
            $this->grade_point = $grade->grade_point;
            if (!$this->remarks) {
                $this->remarks = $grade->remark;
            }
        }
        return $this;
    }
}