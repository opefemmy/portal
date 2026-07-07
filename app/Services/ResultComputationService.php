<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Result;
use App\Models\StudentCourse;
use App\Models\GradingScale;
use App\Models\GradeClassification;
use App\Models\Semester;
use App\Models\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResultComputationService
{
    /**
     * Default grading scale (Nigerian Higher Institution Standard)
     */
    public static function getDefaultGradingScales(): array
    {
        return [
            ['min_score' => 70, 'max_score' => 100, 'grade' => 'A', 'grade_point' => 4.00, 'remark' => 'Excellent', 'classification' => 'distinction', 'sort_order' => 1],
            ['min_score' => 60, 'max_score' => 69, 'grade' => 'B', 'grade_point' => 3.50, 'remark' => 'Very Good', 'classification' => 'upper_credit', 'sort_order' => 2],
            ['min_score' => 50, 'max_score' => 59, 'grade' => 'C', 'grade_point' => 3.00, 'remark' => 'Good', 'classification' => 'lower_credit', 'sort_order' => 3],
            ['min_score' => 45, 'max_score' => 49, 'grade' => 'D', 'grade_point' => 2.50, 'remark' => 'Fair', 'classification' => 'pass', 'sort_order' => 4],
            ['min_score' => 40, 'max_score' => 44, 'grade' => 'E', 'grade_point' => 2.00, 'remark' => 'Pass', 'classification' => 'pass', 'sort_order' => 5],
            ['min_score' => 0, 'max_score' => 39, 'grade' => 'F', 'grade_point' => 0.00, 'remark' => 'Fail', 'classification' => 'fail', 'sort_order' => 6],
        ];
    }

    /**
     * Default degree classification
     */
    public static function getDefaultClassifications(): array
    {
        return [
            ['name' => 'Distinction', 'slug' => 'distinction', 'min_gpa' => 3.50, 'max_gpa' => 4.00, 'description' => 'Excellent performance', 'sort_order' => 1],
            ['name' => 'Upper Credit', 'slug' => 'upper_credit', 'min_gpa' => 3.00, 'max_gpa' => 3.49, 'description' => 'Very Good performance', 'sort_order' => 2],
            ['name' => 'Lower Credit', 'slug' => 'lower_credit', 'min_gpa' => 2.50, 'max_gpa' => 2.99, 'description' => 'Good performance', 'sort_order' => 3],
            ['name' => 'Pass', 'slug' => 'pass', 'min_gpa' => 2.00, 'max_gpa' => 2.49, 'description' => 'Satisfactory performance', 'sort_order' => 4],
            ['name' => 'Fail', 'slug' => 'fail', 'min_gpa' => 0.00, 'max_gpa' => 1.99, 'description' => 'Below standard', 'sort_order' => 5],
        ];
    }

    /**
     * Calculate total score from CA and Exam
     */
    public static function calculateTotalScore($ca, $exam): float
    {
        $caScore = is_null($ca) ? 0 : floatval($ca);
        $examScore = is_null($exam) ? 0 : floatval($exam);
        return $caScore + $examScore;
    }

    /**
     * Get grade from score using configured grading scales
     */
    public static function getGradeFromScore(float $score): ?GradingScale
    {
        return GradingScale::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Calculate quality point (Course Unit × Grade Point)
     */
    public static function calculateQualityPoint(int $courseUnit, float $gradePoint): float
    {
        return floatval($courseUnit) * floatval($gradePoint);
    }

    /**
     * Get pass status based on grade
     */
    public static function getPassStatus(string $grade, bool $isRepeated = false): string
    {
        if ($isRepeated) {
            return 'REPEAT';
        }

        return match ($grade) {
            'A', 'B', 'C', 'D', 'E' => 'PASS',
            'F' => 'CARRY_OVER',
            default => 'INCOMPLETE',
        };
    }

    /**
     * Calculate semester results for a student
     */
    public static function calculateSemesterResults(
        Student $student,
        Session $session,
        Semester $semester
    ): array {
        // Get all registered courses for this semester
        $studentCourses = StudentCourse::where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->where('semester', $semester->id)
            ->with(['course', 'results'])
            ->get();

        $results = [];
        $tcp = 0; // Total Credit Points
        $tlu = 0; // Total Load Units
        $tup = 0; // Total Units Passed

        foreach ($studentCourses as $studentCourse) {
            $course = $studentCourse->course;
            if (!$course) continue;

            $result = $studentCourse->results()->first();

            if ($result) {
                $unit = $course->units ?? 0;
                $gradePoint = $result->grade_point ?? 0;

                // TCP = Course Unit × Grade Point
                $qp = self::calculateQualityPoint($unit, $gradePoint);
                $tcp += $qp;
                $tlu += $unit;

                // TUP = SUM(Course Units where Grade is NOT F)
                if (($result->grade ?? 'F') !== 'F') {
                    $tup += $unit;
                }
            }
        }

        // Calculate GPA
        $gpa = $tlu > 0 ? round($tcp / $tlu, 2) : 0.0;

        return [
            'tcp' => $tcp,
            'tlu' => $tlu,
            'tup' => $tup,
            'gpa' => $gpa,
            'course_count' => $studentCourses->count(),
        ];
    }

    /**
     * Calculate cumulative results (all semesters up to current)
     */
    public static function calculateCumulativeResults(
        Student $student,
        ?Session $upToSession = null,
        ?Semester $upToSemester = null
    ): array {
        $query = Result::whereHas('studentCourse', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        });

        if ($upToSession) {
            $query->whereHas('studentCourse', function ($q) use ($upToSession) {
                $q->where('session_id', '<=', $upToSession->id);
            });
        }

        if ($upToSemester) {
            $query->whereHas('studentCourse', function ($q) use ($upToSemester) {
                $q->where('semester', '<=', $upToSemester->id);
            });
        }

        // Exclude repeated courses (keep only latest attempt)
        $results = $query->get()->groupBy(function ($result) {
            return $result->studentCourse->course_id ?? $result->course_id;
        })->map(function ($group) {
            return $group->sortByDesc('attempt_number')->first();
        });

        $tcp = 0;
        $tlu = 0;
        $tup = 0;

        foreach ($results as $result) {
            $course = $result->course;
            if (!$course) continue;

            $unit = $course->units ?? 0;
            $gradePoint = $result->grade_point ?? 0;

            $qp = self::calculateQualityPoint($unit, $gradePoint);
            $tcp += $qp;
            $tlu += $unit;

            if (($result->grade ?? 'F') !== 'F') {
                $tup += $unit;
            }
        }

        $cgpa = $tlu > 0 ? round($tcp / $tlu, 2) : 0.0;

        return [
            'tcp' => $tcp,
            'tlu' => $tlu,
            'tup' => $tup,
            'cgpa' => $cgpa,
            'total_courses' => $results->count(),
        ];
    }

    /**
     * Get previous semester results
     */
    public static function getPreviousResults(Student $student, Session $currentSession, Semester $currentSemester): ?array
    {
        // Get all results before current semester
        $previousResults = Result::whereHas('studentCourse', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->whereHas('studentCourse', function ($q) use ($currentSession, $currentSemester) {
            $q->where(function ($sub) use ($currentSession, $currentSemester) {
                $sub->where(function ($s) use ($currentSession) {
                    $s->whereHas('session', function ($ss) use ($currentSession) {
                        $ss->where('id', '<', $currentSession->id);
                    });
                })->orWhere(function ($s) use ($currentSession, $currentSemester) {
                    $s->whereHas('session', function ($ss) use ($currentSession) {
                        $ss->where('id', '=', $currentSession->id);
                    })->where('semester', '<', $currentSemester->id);
                });
            });
        })
        ->get();

        if ($previousResults->isEmpty()) {
            return null;
        }

        $tcp = 0;
        $tlu = 0;
        $tup = 0;

        foreach ($previousResults as $result) {
            $course = $result->course;
            if (!$course) continue;

            $unit = $course->units ?? 0;
            $gradePoint = $result->grade_point ?? 0;

            $tcp += self::calculateQualityPoint($unit, $gradePoint);
            $tlu += $unit;

            if (($result->grade ?? 'F') !== 'F') {
                $tup += $unit;
            }
        }

        return [
            'tcp' => $tcp,
            'tlu' => $tlu,
            'tup' => $tup,
            'gpa' => $tlu > 0 ? round($tcp / $tlu, 2) : 0.0,
        ];
    }

    /**
     * Get failed courses (carry over courses)
     */
    public static function getFailedCourses(Student $student, ?Session $session = null, ?Semester $semester = null): Collection
    {
        $query = Result::whereHas('studentCourse', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->where('grade', 'F');

        if ($session) {
            $query->whereHas('studentCourse', function ($q) use ($session) {
                $q->where('session_id', $session->id);
            });
        }

        if ($semester) {
            $query->whereHas('studentCourse', function ($q) use ($semester) {
                $q->where('semester', $semester->id);
            });
        }

        return $query->get()->map(function ($result) {
            return [
                'course_code' => $result->course->code ?? 'N/A',
                'course_title' => $result->course->title ?? 'N/A',
                'unit' => $result->course->units ?? 0,
                'score' => $result->total_score,
                'grade' => $result->grade,
                'remark' => $result->remarks,
            ];
        });
    }

    /**
     * Determine academic remark based on CGPA
     */
    public static function getAcademicRemark(float $cgpa): string
    {
        $classification = GradeClassification::where('min_gpa', '<=', $cgpa)
            ->where('max_gpa', '>=', $cgpa)
            ->orderBy('sort_order')
            ->first();

        return $classification ? strtoupper($classification->name) : 'FAIL';
    }

    /**
     * Compute and update a single result
     */
    public static function computeResult(Result $result): Result
    {
        DB::beginTransaction();
        try {
            $studentCourse = $result->studentCourse;
            $course = $result->course;
            $student = $studentCourse?->student;

            if (!$course || !$student) {
                DB::rollBack();
                return $result;
            }

            // Calculate total score
            $result->total_score = self::calculateTotalScore(
                $result->ca1 + $result->ca2,
                $result->exam
            );

            // Get grade
            $gradeObj = self::getGradeFromScore($result->total_score);
            if ($gradeObj) {
                $result->grade = $gradeObj->grade;
                $result->grade_point = $gradeObj->grade_point;
                $result->remarks = $gradeObj->remark;
            }

            // Calculate quality point
            $result->quality_point = self::calculateQualityPoint(
                $course->units ?? 0,
                $result->grade_point ?? 0
            );

            // Determine pass status
            $result->pass_status = self::getPassStatus(
                $result->grade ?? 'F',
                $result->is_repeated ?? false
            );

            // Calculate semester GPA
            $semesterResults = self::calculateSemesterResults(
                $student,
                $studentCourse->session,
                Semester::find($studentCourse->semester)
            );
            $result->gpa = $semesterResults['gpa'];
            $result->tlu = $semesterResults['tlu'];

            // Calculate cumulative GPA
            $cumulativeResults = self::calculateCumulativeResults(
                $student,
                $studentCourse->session,
                Semester::find($studentCourse->semester)
            );
            $result->cgpa = $cumulativeResults['cgpa'];
            $result->previous_cga = $cumulativeResults['cgpa'] - $semesterResults['gpa'];
            $result->previous_tlu = $cumulativeResults['tlu'] - $semesterResults['tlu'];

            // Set academic remark
            $result->academic_remark = self::getAcademicRemark($result->cgpa ?? 0);

            $result->save();
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Recompute all results for a student
     */
    public static function recomputeAllResults(Student $student): array
    {
        $results = Result::whereHas('studentCourse', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->orderBy('created_at')->get();

        $count = 0;
        foreach ($results as $result) {
            self::computeResult($result);
            $count++;
        }

        return [
            'total' => $count,
            'student' => $student->matric_number,
        ];
    }

    /**
     * Validate result before submission
     */
    public static function validateResult(array $data): array
    {
        $errors = [];

        if (isset($data['ca1']) && ($data['ca1'] < 0 || $data['ca1'] > 40)) {
            $errors[] = 'CA1 must be between 0 and 40';
        }

        if (isset($data['ca2']) && ($data['ca2'] < 0 || $data['ca2'] > 40)) {
            $errors[] = 'CA2 must be between 0 and 40';
        }

        if (isset($data['exam']) && ($data['exam'] < 0 || $data['exam'] > 60)) {
            $errors[] = 'Exam must be between 0 and 60';
        }

        $total = ($data['ca1'] ?? 0) + ($data['ca2'] ?? 0) + ($data['exam'] ?? 0);
        if ($total > 100) {
            $errors[] = 'Total score cannot exceed 100';
        }

        return $errors;
    }
}