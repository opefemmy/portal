<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Department;
use App\Models\Programme;
use App\Models\School;
use App\Models\StudentCourse;
use App\Models\Result;
use App\Models\Student;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ResultController extends Controller
{
    use EnforcesPermission;
    /**
     * The unique (school_id, department_id, programme_id, level, session_id)
     * tuples the currently-authenticated lecturer is actually assigned to
     * via CourseAssignment. Inferred from assignments rather than the
     * lecturer's user profile so a lecturer assigned to courses across
     * multiple departments / programmes / levels / sessions keeps the
     * union of those scopes.
     *
     * Returned shape:
     *   [
     *     ['school_id' => 1, 'department_id' => 2, 'programme_id' => 3,
     *      'level' => 1, 'session_id' => 4],
     *     ...
     *   ]
     *
     * @return array<int, array{school_id: int|null, department_id: int|null, programme_id: int|null, level: int|null, session_id: int|null}>
     */
    protected function lecturerScope(): array
    {
        $rows = CourseAssignment::where('lecturer_id', auth()->id())
            ->join('courses', 'courses.id', '=', 'course_assignments.course_id')
            ->whereNotNull('courses.school_id')
            ->whereNotNull('courses.department_id')
            ->whereNotNull('courses.programme_id')
            ->whereNotNull('courses.level')
            ->whereNotNull('course_assignments.session_id')
            ->select(
                'courses.school_id',
                'courses.department_id',
                'courses.programme_id',
                'courses.level',
                'course_assignments.session_id'
            )
            ->distinct()
            ->get();

        return $rows
            ->map(fn ($r) => [
                'school_id' => (int) $r->school_id,
                'department_id' => (int) $r->department_id,
                'programme_id' => (int) $r->programme_id,
                'level' => (int) $r->level,
                'session_id' => (int) $r->session_id,
            ])
            ->values()
            ->all();
    }

    /**
     * True if the given course's (school, department, programme, level, session)
     * tuple matches one of the tuples in the lecturer's inferred scope.
     * A course with any of those columns null can never match (safe default).
     */
    protected function isCourseInLecturerScope(Course $course, ?int $sessionId = null): bool
    {
        if (empty($course->school_id) || empty($course->department_id) || empty($course->programme_id) || empty($course->level)) {
            return false;
        }

        $courseSessionId = $sessionId ?? $this->resolveCourseSessionId($course);

        if (empty($courseSessionId)) {
            return false;
        }

        foreach ($this->lecturerScope() as $row) {
            if ($row['school_id'] === (int) $course->school_id
                && $row['department_id'] === (int) $course->department_id
                && $row['programme_id'] === (int) $course->programme_id
                && $row['level'] === (int) $course->level
                && $row['session_id'] === (int) $courseSessionId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Best-effort resolution of the session this course is being
     * uploaded for. A CourseAssignment carries the session, so we look
     * up the lecturer's own assignment for this course first; if none,
     * fall back to the current session.
     */
    protected function resolveCourseSessionId(Course $course): ?int
    {
        $assignment = CourseAssignment::where('course_id', $course->id)
            ->where('lecturer_id', auth()->id())
            ->first();

        if ($assignment && !empty($assignment->session_id)) {
            return (int) $assignment->session_id;
        }

        $current = Session::getCurrentSession();
        return $current?->id !== null ? (int) $current->id : null;
    }

    /**
     * Defensive server-side guard for write paths. Returns null when the
     * request is allowed, or a redirect Response when it must be blocked.
     * The lecturer's inferred scope (from CourseAssignment) must contain a
     * tuple matching the course, AND the request must carry matching
     * school_id / department_id / programme_id / level / session_id
     * hidden inputs set by the upload-page picker.
     */
    protected function enforceScope(Request $request, Course $course)
    {
        $courseSessionId = $this->resolveCourseSessionId($course);

        if (!$this->isCourseInLecturerScope($course, $courseSessionId)) {
            return back()->with(
                'error',
                'You cannot upload results for this course — it belongs to a different school, department, programme, level, or session than your assigned scope.'
            );
        }

        $rSchool = $request->input('school_id');
        $rDept = $request->input('department_id');
        $rProg = $request->input('programme_id');
        $rLevel = $request->input('level');
        $rSess = $request->input('session_id');

        if ($rSchool === null || $rDept === null || $rProg === null || $rLevel === null || $rSess === null
            || (int) $rSchool !== (int) $course->school_id
            || (int) $rDept !== (int) $course->department_id
            || (int) $rProg !== (int) $course->programme_id
            || (int) $rLevel !== (int) $course->level
            || (int) $rSess !== (int) $courseSessionId) {
            return back()->with(
                'error',
                'Please select the matching School, Department, Programme, Session and Level before uploading results for this course.'
            );
        }

        return null;
    }

    /**
     * Show students registered for lecturer's assigned course
     */
    public function courseStudents(Course $course)
    {
        $this->requirePermission('academic.results.view');

        try {
            // Verify lecturer is assigned to this course
            $assignment = CourseAssignment::where('course_id', $course->id)
                ->where('lecturer_id', auth()->id())
                ->first();

            if (!$assignment) {
                return back()->with('error', 'You are not assigned to this course.');
            }

            $studentCourses = collect();
            $results = collect();

            $currentSession = Session::getCurrentSession();
            $sessionId = $currentSession->id ?? null;

            // Get students registered for this course (don't filter by session if no current session)
            $query = StudentCourse::where('course_id', $course->id)
                ->where('status', 'registered')
                ->with(['student.user', 'student.department', 'student.programme', 'results']);

            if ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            $studentCourses = $query->get();

            // Get existing results to show status
            if ($studentCourses->isNotEmpty()) {
                $results = Result::whereIn('student_course_id', $studentCourses->pluck('id'))
                    ->get()
                    ->keyBy('student_course_id');
            }

            // Eager-load the course's department and assignment's session so the
            // view doesn't hit lazy-load exceptions if those rows are missing.
            $course->loadMissing('department');
            $assignment->loadMissing('session');

            return view('lecturer.course-students', compact('course', 'studentCourses', 'results', 'assignment'));
        } catch (\Throwable $e) {
            \Log::error('Lecturer courseStudents 500 for course ' . $course->id . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', 'Unable to load student list: ' . $e->getMessage());
        }
    }

    /**
     * Show result entry form
     */
    public function enter(Course $course)
    {
        $this->requirePermission('academic.results.enter');

        try {
            // Verify lecturer is assigned to this course
            $assignment = CourseAssignment::where('course_id', $course->id)
                ->where('lecturer_id', auth()->id())
                ->first();

            if (!$assignment) {
                return back()->with('error', 'You are not assigned to this course.');
            }

            // Eager-load the course's school/dept/programme so the
            // upload-page picker can pre-select the right scope.
            $course->loadMissing(['department', 'programme', 'school']);

            // Infer the lecturer's allowed scope from their assignments.
            $scope = $this->lecturerScope();
            $courseSessionId = $this->resolveCourseSessionId($course);

            // Picker choices: only schools / departments / programmes /
            // levels / sessions the lecturer is actually assigned to.
            // Cascading is handled in the view via JS — the controller
            // sends every distinct value in scope and the view filters
            // departments by selected school, etc.
            $allowedSchools = School::whereIn('id', collect($scope)->pluck('school_id')->unique())
                ->orderBy('name')
                ->get(['id', 'name']);
            $allowedDepartments = Department::whereIn('id', collect($scope)->pluck('department_id')->unique())
                ->orderBy('name')
                ->get(['id', 'name', 'school_id']);
            $allowedProgrammes = Programme::whereIn('id', collect($scope)->pluck('programme_id')->unique())
                ->orderBy('name')
                ->get(['id', 'name', 'department_id']);
            $allowedLevels = collect($scope)->pluck('level')->unique()->sort()->values()
                ->map(fn ($l) => ['value' => (int) $l, 'name' => Course::LEVEL_NAMES[(int) $l] ?? (string) $l]);
            $allowedSessions = Session::whereIn('id', collect($scope)->pluck('session_id')->unique())
                ->orderBy('name')
                ->get(['id', 'name']);

            // Selected scope: prefer query string (picker Apply), fall back
            // to the course's own scope, fall back to the first allowed row.
            $selectedSchoolId = (int) (request('school_id') ?: ($course->school_id ?: ($allowedSchools->first()->id ?? 0)));
            $selectedDepartmentId = (int) (request('department_id') ?: ($course->department_id ?: ($allowedDepartments->first()->id ?? 0)));
            $selectedProgrammeId = (int) (request('programme_id') ?: ($course->programme_id ?: ($allowedProgrammes->first()->id ?? 0)));
            $selectedLevel = (int) (request('level') ?: ($course->level ?: ($allowedLevels->first()['value'] ?? 0)));
            $selectedSessionId = (int) (request('session_id') ?: ($courseSessionId ?: ($allowedSessions->first()->id ?? 0)));

            $studentCourses = collect();
            $existingResults = collect();

            $currentSession = Session::getCurrentSession();
            $sessionId = $currentSession->id ?? null;

            // Get students registered for this course
            $query = StudentCourse::where('course_id', $course->id)
                ->where('status', 'registered')
                ->with(['student.user', 'results']);

            if ($sessionId) {
                $query->where('session_id', $sessionId);
            }

            $studentCourses = $query->get();

            // Get existing results
            if ($studentCourses->isNotEmpty()) {
                $existingResults = Result::whereIn('student_course_id', $studentCourses->pluck('id'))
                    ->get()
                    ->keyBy('student_course_id');
            }

            return view('lecturer.results-enter', compact(
                'course',
                'studentCourses',
                'existingResults',
                'assignment',
                'allowedSchools',
                'allowedDepartments',
                'allowedProgrammes',
                'allowedLevels',
                'allowedSessions',
                'selectedSchoolId',
                'selectedDepartmentId',
                'selectedProgrammeId',
                'selectedLevel',
                'selectedSessionId'
            ));
        } catch (\Throwable $e) {
            \Log::error('Lecturer enter 500 for course ' . $course->id . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', 'Unable to load results form: ' . $e->getMessage());
        }
    }

    /**
     * Store results manually entered
     */
    public function store(Request $request, Course $course)
    {
        $this->requirePermission('academic.results.enter');

        try {
            $assignment = CourseAssignment::where('course_id', $course->id)
                ->where('lecturer_id', auth()->id())
                ->first();

            if (!$assignment) {
                return back()->with('error', 'You are not assigned to this course.');
            }

            // Restrict by School / Department / Programme scope.
            if ($blocked = $this->enforceScope($request, $course)) {
                return $blocked;
            }

            $request->validate([
                'results' => 'required|array',
                'results.*.student_course_id' => 'required|exists:student_courses,id',
                'results.*.ca1' => 'nullable|numeric|min:0|max:40',
                'results.*.ca2' => 'nullable|numeric|min:0|max:40',
                'results.*.exam' => 'nullable|numeric|min:0|max:60',
            ]);

            $currentSession = Session::getCurrentSession();
            $resultsSaved = 0;

            foreach ($request->results as $resultData) {
                $studentCourseId = $resultData['student_course_id'];
                $ca1 = $resultData['ca1'] ?? 0;
                $ca2 = $resultData['ca2'] ?? 0;
                $exam = $resultData['exam'] ?? 0;
                $total = $ca1 + $ca2 + $exam;

                // Calculate grade
                $grade = \App\Models\Grade::getGrade($total);

                // SPIKE: Delete existing result first to avoid duplicates, then create new
                Result::where('student_course_id', $studentCourseId)->delete();

                // Create fresh result record
                $result = Result::create([
                    'student_course_id' => $studentCourseId,
                    'course_id' => $course->id,
                    'ca1' => $ca1,
                    'ca2' => $ca2,
                    'exam' => $exam,
                    'total_score' => $total,
                    'grade' => $grade ? $grade->grade : null,
                    'grade_point' => $grade ? $grade->grade_point : 0,
                    'remarks' => $grade ? $grade->remark : null,
                    'status' => 'pending_approval',
                ]);

                // Calculate GPA/CGPA for the student
                $studentCourse = StudentCourse::find($studentCourseId);
                if ($studentCourse) {
                    $result->calculateAll($studentCourse->student_id);
                }

                $resultsSaved++;
            }

            return back()->with('success', "{$resultsSaved} results saved successfully! Previous records have been replaced.");
        } catch (\Throwable $e) {
            \Log::error('Lecturer store 500 for course ' . $course->id . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', 'Unable to save results: ' . $e->getMessage());
        }
    }

    /**
     * Edit a specific result before HOD approval
     */
    public function edit(Result $result)
    {
        $this->requirePermission('academic.results.edit');

        // Verify lecturer owns this result
        $studentCourse = $result->studentCourse;
        $assignment = CourseAssignment::where('course_id', $studentCourse->course_id)
            ->where('lecturer_id', auth()->id())
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this course.');
        }

        // Check if result is approved
        if ($result->status === 'approved') {
            return back()->with('error', 'Cannot edit approved results.');
        }

        return view('lecturer.result-edit', compact('result', 'studentCourse'));
    }

    /**
     * Update a result
     */
    public function update(Request $request, Result $result)
    {
        $this->requirePermission('academic.results.edit');

        try {
            $studentCourse = $result->studentCourse;
            $assignment = CourseAssignment::where('course_id', $studentCourse->course_id)
                ->where('lecturer_id', auth()->id())
                ->first();

            if (!$assignment) {
                return back()->with('error', 'You are not assigned to this course.');
            }

            if ($result->status === 'approved') {
                return back()->with('error', 'Cannot edit approved results.');
            }

            // Restrict by School / Department / Programme scope.
            $resultCourse = Course::find($studentCourse->course_id);
            if ($resultCourse && ($blocked = $this->enforceScope($request, $resultCourse))) {
                return $blocked;
            }

            $request->validate([
                'ca1' => 'nullable|numeric|min:0|max:40',
                'ca2' => 'nullable|numeric|min:0|max:40',
                'exam' => 'nullable|numeric|min:0|max:60',
            ]);

            $ca1 = $request->ca1 ?? 0;
            $ca2 = $request->ca2 ?? 0;
            $exam = $request->exam ?? 0;
            $total = $ca1 + $ca2 + $exam;

            $grade = \App\Models\Grade::getGrade($total);

            $result->update([
                'ca1' => $ca1,
                'ca2' => $ca2,
                'exam' => $exam,
                'total_score' => $total,
                'grade' => $grade ? $grade->grade : null,
                'grade_point' => $grade ? $grade->grade_point : 0,
                'remarks' => $grade ? $grade->remark : null,
                'status' => 'pending_approval',
            ]);

            return redirect()->route('lecturer.courses.results', $studentCourse->course_id)
                ->with('success', 'Result updated successfully!');
        } catch (\Throwable $e) {
            \Log::error('Lecturer update 500 for result ' . $result->id . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return back()->with('error', 'Unable to update result: ' . $e->getMessage());
        }
    }

    /**
     * Bulk upload results via Excel
     */
    public function bulkUpload(Request $request, Course $course)
    {
        $this->requirePermission('academic.results.enter');

        $assignment = CourseAssignment::where('course_id', $course->id)
            ->where('lecturer_id', auth()->id())
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this course.');
        }

        // Restrict by School / Department / Programme scope.
        if ($blocked = $this->enforceScope($request, $course)) {
            return $blocked;
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $file = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Skip header row
            array_shift($rows);

            $currentSession = Session::getCurrentSession();
            $errors = [];
            $successCount = 0;

            foreach ($rows as $index => $row) {
                if (empty($row[0])) continue;

                // Expected format: Matric No, Fullname, CA1, CA2, Exam, Total
                $matricNo = trim($row[0]);
                $fullname = trim($row[1] ?? '');
                $ca1 = is_numeric($row[2] ?? null) ? floatval($row[2]) : 0;
                $ca2 = is_numeric($row[3] ?? null) ? floatval($row[3]) : 0;
                $exam = is_numeric($row[4] ?? null) ? floatval($row[4]) : 0;

                // Find student by matric number
                $student = Student::where('matric_number', $matricNo)->first();

                if (!$student) {
                    $errors[] = "Row " . ($index + 2) . ": Student with matric number {$matricNo} not found.";
                    continue;
                }

                // Find student's course registration
                $studentCourse = StudentCourse::where('student_id', $student->id)
                    ->where('course_id', $course->id)
                    ->where('session_id', $currentSession->id ?? 0)
                    ->where('status', 'registered')
                    ->first();

                if (!$studentCourse) {
                    $errors[] = "Row " . ($index + 2) . ": {$matricNo} is not registered for this course.";
                    continue;
                }

                $total = $ca1 + $ca2 + $exam;
                $grade = \App\Models\Grade::getGrade($total);

                // SPIKE: Delete existing result first to avoid duplicates
                Result::where('student_course_id', $studentCourse->id)->delete();

                // Create fresh result record
                Result::create([
                    'student_course_id' => $studentCourse->id,
                    'course_id' => $course->id,
                    'ca1' => $ca1,
                    'ca2' => $ca2,
                    'exam' => $exam,
                    'total_score' => $total,
                    'grade' => $grade ? $grade->grade : null,
                    'grade_point' => $grade ? $grade->grade_point : 0,
                    'remarks' => $grade ? $grade->remark : null,
                    'status' => 'pending_approval',
                ]);

                $successCount++;
            }

            if (count($errors) > 0) {
                return back()->with('warning', "Uploaded {$successCount} results. " . count($errors) . " errors: " . implode(', ', array_slice($errors, 0, 5)));
            }

            return back()->with('success', "Successfully uploaded {$successCount} results!");
        } catch (\Throwable $e) {
            \Log::error('Lecturer bulkUpload failed for course ' . $course->id . ': ' . $e->getMessage());
            return back()->with('error', 'Bulk upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Download result template
     */
    public function downloadTemplate(Course $course)
    {
        $this->requirePermission('academic.results.enter');

        $currentSession = Session::getCurrentSession();

        // Get registered students
        $studentCourses = StudentCourse::where('course_id', $course->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->where('status', 'registered')
            ->with('student.user')
            ->get();

        // Create spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Matric No');
        $sheet->setCellValue('B1', 'Fullname');
        $sheet->setCellValue('C1', 'CA1');
        $sheet->setCellValue('D1', 'CA2');
        $sheet->setCellValue('E1', 'Exam');
        $sheet->setCellValue('F1', 'Total');

        // Data
        $row = 2;
        foreach ($studentCourses as $sc) {
            $sheet->setCellValue('A' . $row, $sc->student->matric_number);
            $sheet->setCellValue('B' . $row, $sc->student->user->name);
            $sheet->setCellValue('C' . $row, '');
            $sheet->setCellValue('D' . $row, '');
            $sheet->setCellValue('E' . $row, '');
            // Total is a live formula so Excel auto-sums C+D+E as scores are typed.
            $sheet->setCellValue('F' . $row, '=IF(SUM(C' . $row . ':E' . $row . ')=0,"",SUM(C' . $row . ':E' . $row . '))');
            $row++;
        }

        // Download
        $filename = $course->code . '_results_template.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}