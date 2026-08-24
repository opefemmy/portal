<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\Session;
use App\Models\CarryOverCourse;
use App\Models\CourseClassification;
use App\Services\SchoolFeeCalculator;
use Illuminate\Http\Request;

class CourseRegistrationController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('student.courses.manage');
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $courses = StudentCourse::where('student_id', $student->id)
            ->with('course', 'session')
            ->get();
        return view('student.courses', compact('courses'));
    }

    public function register()
    {
        $this->requirePermission('student.courses.manage');
        $student = Student::where('user_id', auth()->id())->first();

        if (!$student) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Please complete your profile first.');
        }

        if (!$student->school_id || !$student->department_id || !$student->programme_id) {
            return redirect()->route('student.profile')
                ->with('error', 'Please complete your profile to select department and programme.');
        }

        $currentSession = Session::getCurrentSession();

        // Payment gate: the student must have paid at least the 60%
        // first-semester slice before any course registration is allowed.
        $canRegisterFirstSem  = SchoolFeeCalculator::canRegisterSemester($student, 'first');
        $canRegisterSecondSem = SchoolFeeCalculator::canRegisterSemester($student, 'second');
        $paidPercent = SchoolFeeCalculator::maxPercentPaidAcrossRequiredFees($student);

        if (!$canRegisterFirstSem && !$canRegisterSecondSem) {
            return redirect()->route('student.payments')
                ->with('error', 'You must pay your school fees before registering for courses.');
        }

        // Get carry over courses from previous semesters
        $carryOverCourses = CarryOverCourse::where('student_id', $student->id)
            ->where('status', 'pending')
            ->with('course')
            ->get();

        // Get main courses for department, programme, and level
        $mainCourses = Course::where('school_id', $student->school_id)
            ->where('department_id', $student->department_id)
            ->where('programme_id', $student->programme_id)
            ->where('level', $student->level)
            ->whereDoesntHave('studentCourses', function($q) use ($student, $currentSession) {
                $q->where('student_id', $student->id)
                  ->where('session_id', $currentSession->id ?? 0);
            })
            ->get();

        // Get elective courses (courses with same level but different programme or marked as elective)
        $electiveCourses = Course::where('school_id', $student->school_id)
            ->where('department_id', $student->department_id)
            ->where('level', $student->level)
            ->where('programme_id', '!=', $student->programme_id)
            ->orWhere(function($q) use ($student) {
                $q->where('department_id', $student->department_id)
                  ->where('level', $student->level)
                  ->whereHas('classification', function($q2) {
                      $q2->where('type', 'elective');
                  });
            })
            ->whereDoesntHave('studentCourses', function($q) use ($student, $currentSession) {
                $q->where('student_id', $student->id)
                  ->where('session_id', $currentSession->id ?? 0);
            })
            ->get();

        // Get already registered courses
        $registeredCourses = StudentCourse::where('student_id', $student->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->with('course')
            ->get();

        return view('student.courses-register', compact(
            'mainCourses', 'electiveCourses', 'carryOverCourses', 'registeredCourses', 'student',
            'canRegisterFirstSem', 'canRegisterSecondSem', 'paidPercent'
        ));
    }

    public function storeRegistration(Request $request)
    {
        $this->requirePermission('student.courses.manage');
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = Session::getCurrentSession();

        // Course type whitelist. Lets the student pick carry_over / main
        // / elective per course. Carry-over is the only path that the
        // carry_over_courses row follows back to a status update below.
        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id',
            'course_types' => 'array',
            'course_types.*' => 'in:main,elective,carry_over',
        ]);

        // Re-check the gate at write-time too — students could submit a
        // form they queued before paying.
        $canRegisterFirstSem  = SchoolFeeCalculator::canRegisterSemester($student, 'first');
        $canRegisterSecondSem = SchoolFeeCalculator::canRegisterSemester($student, 'second');
        $fullyPaid = $canRegisterFirstSem && $canRegisterSecondSem;

        if (!$canRegisterFirstSem && !$canRegisterSecondSem) {
            return redirect()->route('student.payments')
                ->with('error', 'You must pay your school fees before registering for courses.');
        }

        $courseTypes = $request->input('course_types', []);

        // Look up the courses once so we know their declared semester
        // (first / second) — we tag each StudentCourse row by the
        // course's own semester, not a blanket "first" or "both"
        // label. The 'both' label the old code used is not a valid
        // ENUM on the live DB (`enum('first','second')`); collapsing
        // all rows under one semester lost the second-semester
        // distinction once the unique key was relaxed to allow two
        // rows per (student, course, session).
        $courseRows = Course::whereIn('id', $request->courses)
            ->get()
            ->keyBy('id');

        foreach ($request->courses as $courseId) {
            $course = $courseRows->get($courseId);
            if (!$course) {
                continue; // validated above; defensive only
            }

            $courseSemester = $course->semester;
            $type = $courseTypes[$courseId] ?? 'main';

            // 60%-paid students are blocked from second-semester
            // courses entirely (the view disables those checkboxes;
            // this is the write-side guard).
            if (!$fullyPaid && $courseSemester === 'second') {
                continue;
            }

            StudentCourse::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id'  => $courseId,
                    'session_id' => $currentSession->id,
                    'semester'   => $courseSemester,
                ],
                [
                    'status'      => 'registered',
                    'course_type' => $type,
                ]
            );

            // If carry over, update the carry over status
            if ($type === 'carry_over') {
                CarryOverCourse::where('student_id', $student->id)
                    ->where('course_id', $courseId)
                    ->update(['status' => 'registered']);
            }
        }

        return redirect()->route('student.courses')->with('success', 'Courses registered successfully!');
    }

    public function dropCourse(StudentCourse $studentCourse)
    {
        $this->requirePermission('student.courses.manage');
        // Ownership check: a student can only drop their own registered courses.
        $student = Student::where('user_id', auth()->id())->first();
        if (!$student || $studentCourse->student_id !== $student->id) {
            abort(403, 'You are not allowed to drop this course.');
        }
        $studentCourse->update(['status' => 'dropped']);
        return back()->with('success', 'Course dropped successfully!');
    }

    public function printForm()
    {
        $this->requirePermission('student.courses.manage');
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = Session::getCurrentSession();

        $courses = StudentCourse::where('student_id', $student->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->where('status', 'registered')
            ->with('course')
            ->get();

        return view('student.courses-print', compact('courses', 'student'));
    }

    /**
     * AJAX search across a student's past failed courses for
     * carry-over re-registration. Returns courses the student
     * previously registered for and either scored F or has
     * `pass_status = 'fail'` — i.e. courses they owe and should
     * re-take. The search box matches code / title prefix so a
     * student typing "MTH" can find "MTH 101 — General Maths"
     * from their year-1 results even though no CarryOverCourse
     * row exists yet (carry-over rows are an admin convenience,
     * not the source of truth — the source of truth is the
     * student's past failed Result).
     */
    public function searchCarryOvers(Request $request)
    {
        $this->requirePermission('student.courses.manage');
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        $term = trim((string) $request->input('q', ''));

        $query = Result::query()
            ->whereHas('studentCourse', function ($q) use ($student) {
                $q->where('student_id', $student->id);
            })
            ->where(function ($q) {
                $q->where('grade', 'F')
                  ->orWhere('pass_status', 'fail')
                  ->orWhere('total_score', '<', 40);
            })
            ->with(['studentCourse.course.department', 'studentCourse.session']);

        if ($term !== '') {
            $query->whereHas('studentCourse.course', function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                  ->orWhere('title', 'like', "%{$term}%");
            });
        }

        $results = $query->orderByDesc('created_at')->limit(20)->get();

        // De-duplicate by course id — the same course may have
        // been failed across multiple sittings. Keep the most
        // recent failure.
        $byCourse = [];
        foreach ($results as $r) {
            $course = $r->studentCourse->course ?? null;
            if (!$course) continue;
            if (isset($byCourse[$course->id])) continue;
            $byCourse[$course->id] = [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'units' => $course->units,
                'semester' => $course->semester,
                'department' => $course->department->name ?? '',
                'failed_session' => $r->studentCourse->session->name ?? '',
                'last_grade' => $r->grade,
                'last_total' => $r->total_score,
            ];
        }

        return response()->json([
            'carry_overs' => array_values($byCourse),
        ]);
    }
}