<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\Result;
use App\Models\Grade;
use App\Models\Session;
use App\Models\Semester;
use App\Models\Programme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Student Service
 * Handles student-related operations including CGPA calculation
 */
class StudentService
{
    /**
     * Calculate CGPA for a student
     */
    public function calculateCGPA(int $studentId): ?float
    {
        $results = Result::where('student_id', $studentId)
            ->where('released', true)
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        $totalPoints = 0;
        $totalUnits = 0;

        foreach ($results as $result) {
            $grade = Grade::where('grade', $result->grade)->first();

            if ($grade) {
                $totalPoints += $grade->points * $result->course_unit;
                $totalUnits += $result->course_unit;
            }
        }

        if ($totalUnits === 0) {
            return null;
        }

        return round($totalPoints / $totalUnits, 2);
    }

    /**
     * Calculate GPA for a specific semester
     */
    public function calculateSemesterGPA(int $studentId, int $sessionId, int $semesterId): ?float
    {
        $results = Result::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('semester_id', $semesterId)
            ->where('released', true)
            ->get();

        if ($results->isEmpty()) {
            return null;
        }

        $totalPoints = 0;
        $totalUnits = 0;

        foreach ($results as $result) {
            $grade = Grade::where('grade', $result->grade)->first();

            if ($grade) {
                $totalPoints += $grade->points * $result->course_unit;
                $totalUnits += $result->course_unit;
            }
        }

        if ($totalUnits === 0) {
            return null;
        }

        return round($totalPoints / $totalUnits, 2);
    }

    /**
     * Get student's academic standing
     */
    public function getAcademicStanding(?float $cgpa): string
    {
        if ($cgpa === null) {
            return 'N/A';
        }

        return match (true) {
            $cgpa >= 3.5 => 'First Class',
            $cgpa >= 3.0 => 'Second Class Upper',
            $cgpa >= 2.0 => 'Second Class Lower',
            $cgpa >= 1.5 => 'Third Class',
            default => 'Pass',
        };
    }

    /**
     * Get grade classification
     */
    public function getGradeClassification(string $grade): string
    {
        return match ($grade) {
            'A', 'A+' => 'Excellent',
            'B', 'B+' => 'Very Good',
            'C', 'C+' => 'Good',
            'D', 'D+' => 'Satisfactory',
            'E' => 'Fair',
            'F' => 'Fail',
            default => 'N/A',
        };
    }

    /**
     * Get student's enrolled courses for a session/semester
     */
    public function getEnrolledCourses(int $studentId, int $sessionId, int $semesterId): Collection
    {
        return StudentCourse::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->where('semester_id', $semesterId)
            ->with(['course', 'course.lecturer'])
            ->get();
    }

    /**
     * Get student's results by session
     */
    public function getSessionResults(int $studentId, int $sessionId): Collection
    {
        return Result::where('student_id', $studentId)
            ->where('session_id', $sessionId)
            ->with(['course', 'session', 'semester'])
            ->orderBy('semester_id')
            ->get();
    }

    /**
     * Get student's academic summary
     */
    public function getAcademicSummary(int $studentId): array
    {
        $student = Student::findOrFail($studentId);

        // Get all results
        $results = Result::where('student_id', $studentId)
            ->where('released', true)
            ->get();

        // Calculate totals
        $totalCourses = $results->count();
        $totalUnits = $results->sum('course_unit');
        $cgpa = $this->calculateCGPA($studentId);

        // Get session summaries
        $sessions = Session::where('id', '<=', $student->session_id)->get();
        $sessionSummaries = [];

        foreach ($sessions as $session) {
            $sessionResults = Result::where('student_id', $studentId)
                ->where('session_id', $session->id)
                ->where('released', true)
                ->get();

            if ($sessionResults->isEmpty()) {
                continue;
            }

            $firstSemGPA = $this->calculateSemesterGPA($studentId, $session->id, 1);
            $secondSemGPA = $this->calculateSemesterGPA($studentId, $session->id, 2);

            $sessionSummaries[] = [
                'session' => $session,
                'first_semester_gpa' => $firstSemGPA,
                'second_semester_gpa' => $secondSemGPA,
                'courses_count' => $sessionResults->count(),
                'units' => $sessionResults->sum('course_unit'),
            ];
        }

        return [
            'student' => $student,
            'cgpa' => $cgpa,
            'academic_standing' => $this->getAcademicStanding($cgpa),
            'total_courses' => $totalCourses,
            'total_units' => $totalUnits,
            'sessions' => $sessionSummaries,
        ];
    }

    /**
     * Check if student can register for a session
     */
    public function canRegister(int $studentId, int $sessionId): array
    {
        $student = Student::findOrFail($studentId);
        $session = Session::findOrFail($sessionId);

        // Check if student belongs to this session
        if ($student->session_id != $session->id) {
            return [
                'can_register' => false,
                'reason' => 'Student does not belong to this session.',
            ];
        }

        // Check if registration is open
        $registrationOpen = Setting::get('course_registration_open', false);

        if (!$registrationOpen) {
            return [
                'can_register' => false,
                'reason' => 'Course registration is currently closed.',
            ];
        }

        // Check for outstanding fees
        $outstandingFees = \App\Models\Payment::where('student_id', $studentId)
            ->where('status', '!=', 'paid')
            ->where('amount', '>', 0)
            ->exists();

        if ($outstandingFees) {
            return [
                'can_register' => false,
                'reason' => 'You have outstanding fees. Please pay before registering.',
            ];
        }

        return [
            'can_register' => true,
            'reason' => null,
        ];
    }

    /**
     * Get carry-over courses
     */
    public function getCarryOverCourses(int $studentId): Collection
    {
        return Result::where('student_id', $studentId)
            ->whereIn('grade', ['F', 'E', 'D'])
            ->with('course')
            ->get()
            ->pluck('course')
            ->filter();
    }

    /**
     * Calculate expected graduation year
     */
    public function getExpectedGraduationYear(Student $student): ?int
    {
        $programme = Programme::find($student->programme_id);

        if (!$programme || !$programme->duration_years) {
            return null;
        }

        $session = Session::find($student->session_id);

        if (!$session) {
            return null;
        }

        // Extract year from session name (e.g., "2023/2024" -> 2023)
        $sessionYear = (int) substr($session->name, 0, 4);

        return $sessionYear + $programme->duration_years;
    }
}