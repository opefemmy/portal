<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Result;
use App\Models\Payment;
use App\Models\Course;
use App\Models\Department;
use App\Models\Session;
use App\Models\Programme;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Report Service
 * Generates various academic and financial reports
 */
class ReportService
{
    /**
     * Generate student enrollment report
     */
    public function generateEnrollmentReport(int $sessionId): array
    {
        $session = Session::findOrFail($sessionId);

        $enrollments = Student::where('session_id', $sessionId)
            ->with(['programme', 'programme.department', 'programme.department.school'])
            ->get()
            ->groupBy('programme.department.school.name');

        $summary = [
            'session' => $session->name,
            'total_students' => $enrollments->flatten()->count(),
            'by_school' => [],
        ];

        foreach ($enrollments as $schoolName => $students) {
            $byDepartment = $students->groupBy('programme.department.name');

            $schoolData = [
                'name' => $schoolName,
                'total' => $students->count(),
                'departments' => [],
            ];

            foreach ($byDepartment as $deptName => $deptStudents) {
                $schoolData['departments'][] = [
                    'name' => $deptName,
                    'total' => $deptStudents->count(),
                    'by_level' => $deptStudents->groupBy('level')->map(fn($g) => $g->count()),
                ];
            }

            $summary['by_school'][] = $schoolData;
        }

        return $summary;
    }

    /**
     * Generate academic performance report
     */
    public function generateAcademicReport(int $sessionId, int $semesterId = null): array
    {
        $query = Result::where('session_id', $sessionId)
            ->where('released', true);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        $results = $query->with(['course', 'student.user'])->get();

        $gradeDistribution = $results->groupBy('grade')->map(fn($g) => $g->count());
        $passRate = $results->whereNotIn('grade', ['F'])->count() / max($results->count(), 1) * 100;

        return [
            'session' => Session::find($sessionId)->name,
            'semester' => $semesterId ? Semester::find($semesterId)->name : 'All',
            'total_results' => $results->count(),
            'pass_rate' => round($passRate, 2),
            'grade_distribution' => $gradeDistribution,
            'top_performers' => $this->getTopPerformers($sessionId, $semesterId, 10),
            'at_risk_students' => $this->getAtRiskStudents($sessionId, $semesterId),
        ];
    }

    /**
     * Get top performers
     */
    public function getTopPerformers(int $sessionId, int $semesterId = null, int $limit = 10): Collection
    {
        $studentGPAs = [];

        $students = Student::where('session_id', $sessionId)->get();

        foreach ($students as $student) {
            $query = Result::where('student_id', $student->id)
                ->where('session_id', $sessionId)
                ->where('released', true);

            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }

            $results = $query->get();

            if ($results->isEmpty()) {
                continue;
            }

            $totalPoints = 0;
            $totalUnits = 0;

            foreach ($results as $result) {
                $grade = \App\Models\Grade::where('grade', $result->grade)->first();
                if ($grade) {
                    $totalPoints += $grade->points * $result->course_unit;
                    $totalUnits += $result->course_unit;
                }
            }

            if ($totalUnits > 0) {
                $studentGPAs[] = [
                    'student' => $student,
                    'gpa' => round($totalPoints / $totalUnits, 2),
                ];
            }
        }

        return collect($studentGPAs)
            ->sortByDesc('gpa')
            ->take($limit)
            ->values();
    }

    /**
     * Get at-risk students (GPA < 1.5)
     */
    public function getAtRiskStudents(int $sessionId, int $semesterId = null): Collection
    {
        $atRisk = [];

        $students = Student::where('session_id', $sessionId)->get();

        foreach ($students as $student) {
            $query = Result::where('student_id', $student->id)
                ->where('session_id', $sessionId)
                ->where('released', true);

            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }

            $results = $query->get();

            if ($results->isEmpty()) {
                continue;
            }

            $totalPoints = 0;
            $totalUnits = 0;

            foreach ($results as $result) {
                $grade = \App\Models\Grade::where('grade', $result->grade)->first();
                if ($grade) {
                    $totalPoints += $grade->points * $result->course_unit;
                    $totalUnits += $result->course_unit;
                }
            }

            if ($totalUnits > 0) {
                $gpa = $totalPoints / $totalUnits;

                if ($gpa < 1.5) {
                    $atRisk[] = [
                        'student' => $student,
                        'gpa' => round($gpa, 2),
                    ];
                }
            }
        }

        return collect($atRisk)->sortBy('gpa')->values();
    }

    /**
     * Generate financial report
     */
    public function generateFinancialReport(int $sessionId): array
    {
        $payments = Payment::where('session_id', $sessionId)
            ->where('status', 'paid')
            ->with(['fee', 'student.user'])
            ->get();

        $totalCollected = $payments->sum('amount');

        $byFeeType = $payments->groupBy('fee.name')->map(fn($g) => $g->sum('amount'));

        $byMonth = $payments->groupBy(fn($p) => $p->paid_at->format('Y-m'))
            ->map(fn($g) => $g->sum('amount'));

        return [
            'session' => Session::find($sessionId)->name,
            'total_collected' => $totalCollected,
            'total_transactions' => $payments->count(),
            'by_fee_type' => $byFeeType,
            'by_month' => $byMonth,
        ];
    }

    /**
     * Generate course report
     */
    public function generateCourseReport(int $sessionId, int $semesterId = null): array
    {
        $query = Result::where('session_id', $sessionId);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        $results = $query->get();

        $byCourse = $results->groupBy('course_id')->map(function ($courseResults) {
            $passCount = $courseResults->whereNotIn('grade', ['F'])->count();
            $totalCount = $courseResults->count();

            return [
                'course' => Course::find($courseResults->first()->course_id),
                'total_registered' => $totalCount,
                'passed' => $passCount,
                'failed' => $totalCount - $passCount,
                'pass_rate' => round($passCount / max($totalCount, 1) * 100, 2),
                'average_score' => round($courseResults->avg('total_score'), 2),
            ];
        });

        return [
            'session' => Session::find($sessionId)->name,
            'semester' => $semesterId ? Semester::find($semesterId)->name : 'All',
            'courses' => $byCourse->values(),
        ];
    }

    /**
     * Generate department summary
     */
    public function generateDepartmentSummary(int $sessionId): array
    {
        $departments = Department::with(['programmes', 'programmes.students' => function ($query) use ($sessionId) {
            $query->where('session_id', $sessionId);
        }])->get();

        return $departments->map(function ($dept) {
            $students = $dept->programmes->flatMap->students;
            $totalStudents = $students->count();

            $results = Result::whereIn('student_id', $students->pluck('id'))
                ->where('session_id', request()->session_id ?? $sessionId)
                ->where('released', true)
                ->get();

            $totalPassed = $results->whereNotIn('grade', ['F'])->count();
            $passRate = $results->count() > 0 ? $totalPassed / $results->count() * 100 : 0;

            return [
                'department' => $dept->name,
                'school' => $dept->school->name,
                'total_students' => $totalStudents,
                'pass_rate' => round($passRate, 2),
            ];
        });
    }

    /**
     * Export data to CSV format
     */
    public function exportToCSV(Collection $data, string $filename): string
    {
        $csv = implode(',', array_keys($data->first())) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_values($row)) . "\n";
        }

        return $csv;
    }
}