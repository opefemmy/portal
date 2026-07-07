<?php

namespace App\Http\Controllers\Student;

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
    public function index(Request $request)
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        // Get current session
        $currentSession = Session::where('is_current', true)->first();

        // Get results for all semesters
        $query = Result::whereHas('studentCourse', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })->where('status', '!=', 'pending');

        if ($request->session_id) {
            $query->whereHas('studentCourse', function ($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        $results = $query->with(['studentCourse.course', 'studentCourse.session'])->get();

        // Calculate current semester stats
        $currentStats = ResultComputationService::calculateSemesterResults(
            $student,
            $currentSession ?? Session::first(),
            Semester::first()
        );

        // Calculate cumulative stats
        $cumulativeStats = ResultComputationService::calculateCumulativeResults($student);

        // Get failed courses
        $failedCourses = ResultComputationService::getFailedCourses($student);

        // Get academic remark
        $academicRemark = ResultComputationService::getAcademicRemark($cumulativeStats['cgpa']);

        return view('student.results', compact(
            'results',
            'student',
            'currentStats',
            'cumulativeStats',
            'failedCourses',
            'academicRemark'
        ));
    }

    public function show($semesterId)
    {
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
                    $stats = ResultComputationService::calculateSemesterResults($student, $session, $semester);
                    $sessionResults[] = [
                        'semester' => $semester,
                        'results' => $results,
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

        $cumulative = ResultComputationService::calculateCumulativeResults($student);
        $academicRemark = ResultComputationService::getAcademicRemark($cumulative['cgpa']);

        return view('student.transcript', compact('student', 'allResults', 'cumulative', 'academicRemark'));
    }
}