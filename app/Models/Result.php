<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    protected $fillable = [
        'student_course_id', 'course_id',
        'ca', 'ca1', 'ca2', 'test', 'assignment', 'exam',
        'total_score', 'grade', 'grade_point', 'gpa',
        'tlu', 'previous_cga', 'previous_tlu', 'cgpa',
        'carry_over_status',
        'approved_by', 'approved_at', 'status', 'remarks'
    ];

    protected $casts = [
        'ca' => 'decimal:2',
        'ca1' => 'decimal:2',
        'ca2' => 'decimal:2',
        'test' => 'decimal:2',
        'assignment' => 'decimal:2',
        'exam' => 'decimal:2',
        'total_score' => 'decimal:2',
        'grade_point' => 'decimal:1',
        'gpa' => 'decimal:2',
        'tlu' => 'integer',
        'previous_cga' => 'decimal:2',
        'previous_tlu' => 'decimal:2',
        'cgpa' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function studentCourse(): BelongsTo
    {
        return $this->belongsTo(StudentCourse::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function calculateTotal()
    {
        // Calculate from CA1, CA2 or legacy CA field
        $caScore = 0;
        if ($this->ca1 !== null || $this->ca2 !== null) {
            $caScore = ($this->ca1 ?? 0) + ($this->ca2 ?? 0);
        } elseif ($this->ca !== null) {
            $caScore = $this->ca;
        }

        $this->total_score = $caScore + ($this->test ?? 0) + ($this->assignment ?? 0) + ($this->exam ?? 0);
        return $this->total_score;
    }

    public function assignGrade()
    {
        $grade = Grade::getGrade($this->total_score);
        if ($grade) {
            $this->grade = $grade->grade;
            $this->grade_point = $grade->grade_point;
            $this->remarks = $grade->remark;
        }
        return $this;
    }

    /**
     * Calculate GPA for a student for a specific session/semester
     */
    public static function calculateGPA($studentId, $sessionId = null, $semester = null)
    {
        $query = self::whereHas('studentCourse', function($q) use ($studentId) {
            $q->where('student_id', $studentId);
        });

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
            $course = $result->studentCourse->course ?? $result->course;
            $units = $course->units ?? 0;
            $gradePoint = $result->grade_point ?? 0;

            $totalPoints += $gradePoint * $units;
            $totalUnits += $units;
        }

        return $totalUnits > 0 ? round($totalPoints / $totalUnits, 2) : 0.0;
    }

    /**
     * Calculate CGPA (Cumulative Grade Point Average)
     */
    public static function calculateCGPA($studentId, $upToSessionId = null, $upToSemester = null)
    {
        $query = self::whereHas('studentCourse', function($q) use ($studentId) {
            $q->where('student_id', $studentId);
        });

        if ($upToSessionId) {
            $query->whereHas('studentCourse', function($q) use ($upToSessionId) {
                $q->where('session_id', '<=', $upToSessionId);
            });
        }

        if ($upToSemester) {
            $query->whereHas('studentCourse', function($q) use ($upToSemester) {
                $q->where('semester', $upToSemester);
            });
        }

        $results = $query->get();

        if ($results->isEmpty()) {
            return 0.0;
        }

        $totalPoints = 0;
        $totalTLU = 0; // Total Learning Units

        foreach ($results as $result) {
            $course = $result->studentCourse->course ?? $result->course;
            $units = $course->units ?? 0;
            $gradePoint = $result->grade_point ?? 0;

            $totalPoints += $gradePoint * $units;
            $totalTLU += $units;
        }

        // Update TLU on the result
        if ($results->isNotEmpty()) {
            $lastResult = $results->last();
            $lastResult->tlu = $totalTLU;
            $lastResult->save();
        }

        return $totalTLU > 0 ? round($totalPoints / $totalTLU, 2) : 0.0;
    }

    /**
     * Calculate and update all result calculations
     */
    public function calculateAll($studentId)
    {
        // Get previous semester's data
        $previousResults = self::whereHas('studentCourse', function($q) use ($studentId) {
            $q->where('student_id', $studentId);
        })->orderBy('id', 'desc')->skip(1)->take(1)->get();

        if ($previousResults->isNotEmpty()) {
            $prevResult = $previousResults->first();
            $this->previous_cga = $prevResult->gpa ?? 0;
            $this->previous_tlu = $prevResult->tlu ?? 0;
        }

        // Calculate current GPA
        $this->gpa = self::calculateGPA($studentId);

        // Calculate CGPA
        $this->cgpa = self::calculateCGPA($studentId);

        // Assign grade and remark
        $this->assignGrade();
        $this->calculateTotal();

        // Check carry over status
        if ($this->grade == 'F' || ($this->total_score ?? 0) < 40) {
            $this->carry_over_status = 'pending';
        }

        return $this;
    }
}