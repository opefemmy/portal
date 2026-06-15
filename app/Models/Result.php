<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    protected $fillable = ['student_course_id', 'ca', 'test', 'assignment', 'exam', 'total_score', 'grade', 'grade_point', 'gpa', 'approved_by', 'approved_at', 'status', 'remarks'];

    protected $casts = [
        'ca' => 'decimal:2',
        'test' => 'decimal:2',
        'assignment' => 'decimal:2',
        'exam' => 'decimal:2',
        'total_score' => 'decimal:2',
        'grade_point' => 'decimal:1',
        'gpa' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function studentCourse(): BelongsTo
    {
        return $this->belongsTo(StudentCourse::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateTotal()
    {
        $this->total_score = ($this->ca ?? 0) + ($this->test ?? 0) + ($this->assignment ?? 0) + ($this->exam ?? 0);
        return $this->total_score;
    }

    public function assignGrade()
    {
        $grade = Grade::getGrade($this->total_score);
        if ($grade) {
            $this->grade = $grade->grade;
            $this->grade_point = $grade->grade_point;
        }
        return $this;
    }
}