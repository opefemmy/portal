<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Result;
use App\Models\StudentCourse;
use App\Models\Session;
use App\Models\Semester;
use App\Services\ResultComputationService;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('student.results.view');
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        // Get current session
        $currentSession = Session::where('is_current', true)->first();

        // Get results for all semesters — exclude the raw 'pending'
        // sentinel (no CA/exam uploaded yet) but DO include every
        // other approval-stage row, so the student sees main score
        // progress as it moves through HOD → Dean → Business →
        // Academic Board. Final-approval gate is applied below.
        $query = Result::whereHas('studentCourse', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })->where('status', '!=', 'pending');

        if ($request->session_id) {
            $query->whereHas('studentCourse', function ($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        $results = $query->with(['studentCourse.course', 'studentCourse.session'])->get();

        // Slice N — Gate GPA + classification behind final approval
        // (Academic Board). Until a result reaches `approved_final`
        // it contributes only its main score (CA + Exam + Total) to
        // the view — no grade point, no GPA contribution, no class
        // badge. Once the Academic Board approves, the row joins
        // `approvedResults` and is summed into the live GPA.
        //
        // We split here rather than masking the badge in the view so
        // the controller-owned number is the one the student sees,
        // and so /transcript /print can read the same `$approvedResults`.
        $approvedResults = $results->where('status', 'approved_final');
        $pendingResults  = $results->where('status', '!=', 'approved_final');
        $gpaUnlocked     = $approvedResults->isNotEmpty();

        // Calculate current semester stats
        $currentStats = ResultComputationService::calculateSemesterResults(
            $student,
            $currentSession ?? Session::first(),
            Semester::first()
        );

        // Calculate cumulative stats — only relevant once at least
        // one result is finally approved. Until then we ship empty
        // stats so the view shows '--' rather than a misleading
        // figure built from un-approved scores.
        $cumulativeStats = $gpaUnlocked
            ? ResultComputationService::calculateCumulativeResults($student)
            : ['cgpa' => null, 'tlu' => null, 'totalPoints' => null, 'totalUnits' => null];

        // Get failed courses (from approved rows only — pending
        // failures don't count toward carry-over status until the
        // result is finally approved).
        $failedCourses = $gpaUnlocked
            ? ResultComputationService::getFailedCourses($student)
            : collect();

        // Get academic remark (only if we have a real CGPA).
        $academicRemark = $gpaUnlocked
            ? ResultComputationService::getAcademicRemark($cumulativeStats['cgpa'])
            : null;

        return view('student.results', compact(
            'results',
            'approvedResults',
            'pendingResults',
            'gpaUnlocked',
            'student',
            'currentStats',
            'cumulativeStats',
            'failedCourses',
            'academicRemark'
        ));
    }

    public function show($semesterId)
    {
        $this->requirePermission('student.results.view');
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $semester = Semester::findOrFail($semesterId);
        $session = Session::where('is_current', true)->first();

        $results = Result::whereHas('studentCourse', function ($query) use ($student, $session, $semester) {
            $query->where('student_id', $student->id)
                  ->where('session_id', $session?->id)
                  ->where('semester', $semester->id);
        })->with('studentCourse.course')->get();

        $stats = ResultComputationService::calculateSemesterResults($student, $session, $semester);

        return view('student.results-semester', compact('results', 'stats', 'semester'));
    }

    public function printResult(Request $request)
    {
        $this->requirePermission('student.results.view');
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $session = Session::where('is_current', true)->first();
        $semester = Semester::where('is_active', true)->first();

        $results = Result::whereHas('studentCourse', function ($query) use ($student, $session, $semester) {
            $query->where('student_id', $student->id);
            if ($session) {
                $query->where('session_id', $session->id);
            }
        })->with('studentCourse.course')->get();

        $stats = ResultComputationService::calculateSemesterResults($student, $session, $semester);
        $cumulative = ResultComputationService::calculateCumulativeResults($student);

        return view('student.results-print', compact('results', 'student', 'stats', 'cumulative', 'session', 'semester'));
    }

    /**
     * Get transcript data
     */
    public function transcript(Request $request)
    {
        $this->requirePermission('student.results.view');
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        $sessions = Session::orderBy('name', 'desc')->get();
        $allResults = [];

        foreach ($sessions as $session) {
            $semesters = Semester::orderBy('sort_order')->get();
            $sessionResults = [];

            foreach ($semesters as $semester) {
                $results = Result::whereHas('studentCourse', function ($query) use ($student, $session, $semester) {
                    $query->where('student_id', $student->id)
                          ->where('session_id', $session->id)
                          ->where('semester', $semester->id);
                })->with('studentCourse.course')->get();

                if ($results->count() > 0) {
                    // Slice N — transcript only counts finally-approved
                    // results toward GPA. Pending rows still render
                    // in the per-semester table but flagged.
                    $approved = $results->where('status', 'approved_final');
                    $stats = $approved->isNotEmpty()
                        ? ResultComputationService::calculateSemesterResults($student, $session, $semester)
                        : ['gpa' => null, 'tlu' => null];

                    $sessionResults[] = [
                        'semester' => $semester,
                        'results' => $results,
                        'approvedResults' => $approved,
                        'stats' => $stats,
                    ];
                }
            }

            if (!empty($sessionResults)) {
                $allResults[] = [
                    'session' => $session,
                    'semesters' => $sessionResults,
                ];
            }
        }

        // Same gate as index — CGPA + remark only when at least one
        // result on the transcript is finally approved.
        $hasApproved = collect($allResults)->flatten(1)->pluck('approvedResults')->flatten()->isNotEmpty();
        $cumulative = $hasApproved
            ? ResultComputationService::calculateCumulativeResults($student)
            : ['cgpa' => null, 'tlu' => null];
        $academicRemark = $hasApproved
            ? ResultComputationService::getAcademicRemark($cumulative['cgpa'])
            : null;

        return view('student.transcript', compact('student', 'allResults', 'cumulative', 'academicRemark'));
    }
}