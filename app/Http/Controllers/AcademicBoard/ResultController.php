<?php

namespace App\Http\Controllers\AcademicBoard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Result;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session as AcademicSession;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    use EnforcesPermission;

    /**
     * Combined signing page — every result cleared by the Business
     * Committee, grouped by school / department / programme / session
     * via the filter form, followed by the six-place signature block
     * (HOD · Dean · BC · Academic Board · Registrar · Rector).
     *
     * Slice E of the multi-area plan. The view is printable on A4 and
     * the bulk-final-approve form reuses the existing bulkApprove
     * endpoint so a board member can sign a batch and approve it in
     * one motion.
     */
    public function signingPage(Request $request)
    {
        $this->requirePermission('academic.results.view');

        $query = Result::with([
                'studentCourse.student.user',
                'studentCourse.student.programme',
                'studentCourse.student.school',
                'studentCourse.course',
            ])
            ->where('status', 'approved_by_business');

        $filters = [
            'school_id'     => $request->school_id ?: null,
            'department_id' => $request->department_id ?: null,
            'programme_id'  => $request->programme_id ?: null,
            'session_id'    => $request->session_id ?: null,
        ];

        if ($filters['school_id']) {
            $query->whereHas('studentCourse.student', function ($q) use ($filters) {
                $q->where('school_id', $filters['school_id']);
            });
        }
        if ($filters['department_id']) {
            $query->whereHas('studentCourse.student', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }
        if ($filters['programme_id']) {
            $query->whereHas('studentCourse.student', function ($q) use ($filters) {
                $q->where('programme_id', $filters['programme_id']);
            });
        }
        if ($filters['session_id']) {
            $query->whereHas('studentCourse', function ($q) use ($filters) {
                $q->where('session_id', $filters['session_id']);
            });
        }

        $results = $query->latest()->get();

        // Selector data for the filter form — bounded so the dropdowns
        // stay small on large installs.
        $schools = School::orderBy('name')->get(['id', 'name']);
        $departments = Department::orderBy('name')
            ->when($filters['school_id'], fn($q) => $q->where('school_id', $filters['school_id']))
            ->get(['id', 'name', 'school_id']);
        $programmes = Programme::orderBy('name')
            ->when($filters['department_id'], fn($q) => $q->where('department_id', $filters['department_id']))
            ->get(['id', 'name', 'department_id']);
        $sessions = AcademicSession::orderBy('name')->get(['id', 'name']);

        return view('academic-board.results.signing-page', compact(
            'results', 'filters', 'schools', 'departments', 'programmes', 'sessions'
        ));
    }

    public function index(Request $request)
    {
        $this->requirePermission('academic.results.view');

        // Department roll-up only. Each row in the index represents
        // ONE department with summary counts — total results,
        // pending (status=approved_by_business) and final-approved
        // (status=approved_final). No individual student or course
        // row renders here; those live on the per-department drill-in
        // page (byDepartment). The previous layout mixed a per-result
        // table with a department roll-up and the user wanted the
        // roll-up to be the only thing on the dashboard.
        $query = Result::query()
            ->select('results.id', 'results.status', 'student_courses.student_id')
            ->join('student_courses', 'student_courses.id', '=', 'results.student_course_id')
            ->join('students', 'students.id', '=', 'student_courses.student_id')
            ->whereIn('results.status', ['approved_by_business', 'approved_final']);

        if ($request->session_id) {
            $query->where('student_courses.session_id', $request->session_id);
        }

        if (auth()->user()->school_id) {
            $query->where('students.school_id', auth()->user()->school_id);
        }

        $rows = $query->get(['id', 'status', 'students.department_id', 'students.programme_id', 'students.school_id']);

        // Group by department only — no programme sub-grouping.
        // Each bucket holds the count of pending vs final-approved
        // results in that department. We don't need any other
        // student/course data on this page.
        $byDepartment = $rows
            ->groupBy('department_id')
            ->map(function ($rs, $deptId) {
                $pending = $rs->where('status', 'approved_by_business')->count();
                $final   = $rs->where('status', 'approved_final')->count();

                $department = \App\Models\Department::with('school')->find($deptId);

                return [
                    'department' => $department,
                    'school'     => $department?->school,
                    'pending'    => $pending,
                    'final'      => $final,
                    'total'      => $pending + $final,
                    'pending_ids' => $rs->where('status', 'approved_by_business')->pluck('id')->all(),
                ];
            })
            // Drop buckets whose department row was deleted (orphaned
            // results pointing at a department_id that no longer exists).
            // Without this the view's route('academic-board.results.
            // byDepartment', $dept) explodes with UrlGenerationException
            // because the parameter is null.
            ->filter(fn ($g) => $g['department'] !== null)
            // Departments with zero work don't deserve a card.
            ->filter(fn ($g) => $g['total'] > 0)
            ->sortBy(function ($g) {
                // Departments with pending work bubble to the top
                // (the board's actionable queue), then by name.
                return ($g['pending'] > 0 ? '0' : '1')
                    . '|' . ($g['department']->name ?? 'zzz');
            })
            ->values();

        return view('academic-board.results.index', compact('byDepartment'));
    }

    /**
     * Per-department drill-in view. The /academic-board/results
     * index shows one roll-up row per department. This route opens
     * the per-result table (course + student + grade) for a single
     * department — the place to actually click Approve / Reject on
     * individual rows.
     */
    public function byDepartment(Request $request, \App\Models\Department $department)
    {
        $this->requirePermission('academic.results.view');
        $this->assertSameDepartmentScope($department);

        $query = Result::with([
                'studentCourse.student.user',
                'studentCourse.student.programme',
                'studentCourse.student.department',
                'studentCourse.student.school',
                'studentCourse.course',
            ])
            ->whereIn('status', ['approved_by_business', 'approved_final'])
            ->whereHas('studentCourse.student', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            });

        if ($request->session_id) {
            $query->whereHas('studentCourse', function ($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        if (auth()->user()->school_id) {
            $query->whereHas('studentCourse.student', function ($q) {
                $q->where('school_id', auth()->user()->school_id);
            });
        }

        $all = $query->latest('updated_at')->get();

        // Approved rows first (read-only archive), then pending. Same
        // ordering as the previous index so the operator reads
        // "signed off → needs sign-off" top to bottom.
        $rows = $all
            ->sortBy(fn ($r) => ($r->status === 'approved_final' ? '0' : '1') . '|' . ($r->studentCourse->course->code ?? 'zzz'))
            ->values();

        $pendingIds = $rows->where('status', 'approved_by_business')->pluck('id')->all();

        $department->load('school');

        return view('academic-board.results.department', [
            'department'  => $department,
            'school'      => $department->school,
            'rows'        => $rows,
            'pendingIds'  => $pendingIds,
        ]);
    }

    /**
     * School-scoped guard for the per-department view. Mirrors
     * assertInSameSchool() on Result but takes a Department model
     * so the same-school rule still applies when drilling in.
     */
    private function assertSameDepartmentScope(\App\Models\Department $department): void
    {
        $user = auth()->user();
        if (! $user || ! $user->school_id) {
            return;
        }
        if ((int) $department->school_id !== (int) $user->school_id) {
            abort(403, 'You are not allowed to view results for this department.');
        }
    }

    public function approve(Request $request, Result $result)
    {
        $this->requirePermission('academic.results.board-approve');
        $this->assertInSameSchool($result);
        $this->assertCanActOn($result, 'approved_by_business');
        $result->update([
            'status' => 'approved_final',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Result finally approved by Academic Board');
    }

    public function reject(Request $request, Result $result)
    {
        $this->requirePermission('academic.results.board-approve');
        $this->assertInSameSchool($result);
        $this->assertCanActOn($result, 'approved_by_business');
        $result->update([
            'status' => 'rejected_final',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Result rejected by Academic Board');
    }

    /**
     * Bulk final-approve results.
     */
    public function bulkApprove(Request $request)
    {
        $this->requirePermission('academic.results.board-approve');

        $request->validate([
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'integer|exists:results,id',
        ]);

        $user = auth()->user();
        $query = Result::whereIn('id', $request->result_ids)
            ->where('status', 'approved_by_business');

        if ($user && $user->school_id) {
            $query->whereHas('studentCourse.student', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
        }

        $updated = $query->update([
            'status' => 'approved_final',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', "{$updated} result(s) finally approved by Academic Board.");
    }

    /**
     * Bulk reject results at the Academic Board stage.
     */
    public function bulkReject(Request $request)
    {
        $this->requirePermission('academic.results.board-approve');

        $request->validate([
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'integer|exists:results,id',
            'remarks' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        $query = Result::whereIn('id', $request->result_ids)
            ->where('status', 'approved_by_business');

        if ($user && $user->school_id) {
            $query->whereHas('studentCourse.student', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
        }

        $updated = $query->update([
            'status' => 'rejected_final',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', "{$updated} result(s) rejected by Academic Board.");
    }

    private function assertCanActOn(Result $result, string $expectedStatus): void
    {
        if ($result->status !== $expectedStatus) {
            abort(409, "This result is no longer in the {$expectedStatus} state and cannot be acted on at the Academic Board stage.");
        }
    }

    private function assertInSameSchool(Result $result): void
    {
        $user = auth()->user();
        if ($user && $user->school_id) {
            $student = $result->studentCourse->student ?? null;
            if (!$student || (int) $student->school_id !== (int) $user->school_id) {
                abort(403, 'You are not allowed to act on this result.');
            }
        }
    }

    /**
     * Per-result printable sheet — renders ONE approved result on
     * its own A4 page with the institution header, watermark, and
     * six-role signing block. Available only for status =
     * `approved_final` (the AB-approved terminal state) so the
     * printable record always matches what the board actually
     * signed off.
     */
    public function printResult(Result $result)
    {
        $this->requirePermission('academic.results.view');
        $this->assertInSameSchool($result);
        $this->assertCanActOn($result, 'approved_final');

        $result->load([
            'studentCourse.student.user',
            'studentCourse.student.programme',
            'studentCourse.student.department',
            'studentCourse.student.school',
            'studentCourse.course',
            'studentCourse.session',
            'approvedBy',
        ]);

        return view('academic-board.results.print', [
            'result' => $result,
            'student' => $result->studentCourse->student,
        ]);
    }

    /**
     * Per-student printable transcript sheet (RESULT_1.jpg format).
     *
     * Loads every approved result for the student (across all
     * sessions/semesters), then pre-computes everything the view
     * needs to render three column groups side-by-side:
     *
     *   - COURSE TITLE table: course title / code / units / total
     *   - CURRENT  semester: per-course code + units + grade
     *   - PREVIOUS  semester: per-course TCP / TLU / GPA
     *   - CUMULATIVE      : per-course TCP / TLU / GPA all-time
     *   - SUMMARY OF RESULTS: Distinction / Upper Credit / Lower
     *     Credit / Pass / Fail counts as defined in the grades table
     *   - GRADING SCALE table
     *
     * Plus four signatures stacked vertically down the left margin
     * (HOD, Dean, Registrar, Rector).
     */
    public function printStudent(Request $request, \App\Models\Student $student)
    {
        $this->requirePermission('academic.results.view');

        $student->load(['user', 'department', 'programme', 'school']);

        if (auth()->user()->school_id && (int) $student->school_id !== (int) auth()->user()->school_id) {
            abort(403, 'You are not allowed to view this student.');
        }

        $results = Result::with([
                'studentCourse.course',
                'studentCourse.session',
                'approvedBy',
            ])
            ->whereHas('studentCourse', fn ($q) => $q->where('student_id', $student->id))
            ->whereIn('status', ['approved_final', 'approved_by_business'])
            ->get();

        if ($results->isEmpty()) {
            abort(404, 'This student has no approved results to print.');
        }

        // Order results by course code for stable column alignment
        // across the three CURRENT / PREVIOUS / CUMULATIVE column
        // groups in the view.
        $rows = $results
            ->sortBy(fn ($r) => $r->studentCourse->course->code ?? 'zzz')
            ->values();

        $cumulativeTCP = 0; // Σ (grade_point × units) all-time
        $cumulativeTLU = 0; // Σ units all-time (passed units only)
        $cumulativeQP  = 0; // Σ quality points (per-course quality_point or grade_point × units)
        $cumulativeCuTLU = 0; // Σ cumulative units table column

        // Per-course breakdown: most recent attempt wins.
        $byCode = [];
        foreach ($rows as $r) {
            $code = $r->studentCourse->course->code ?? 'N/A';
            $byCode[$code][] = $r;
        }

        $courseRows = []; // shape used directly by the view
        $summary    = [
            'distinction' => 0, 'upper_credit' => 0, 'lower_credit' => 0,
            'pass' => 0, 'fail' => 0,
        ];
        $outstanding = [];

        foreach ($byCode as $code => $attempts) {
            $attempts = collect($attempts)->sortByDesc(
                fn ($r) => optional($r->studentCourse->session)->id ?? 0
            )->values();

            $current  = $attempts->first();
            $units    = (int) ($current->studentCourse->course->units ?? 0);
            $gp       = (float) ($current->grade_point ?? 0);
            $grade    = $current->grade ?? '';
            $qPoint   = (float) ($current->quality_point ?? ($gp * $units));
            $passed   = $grade !== 'F' && ($current->pass_status ?? '') !== 'fail';

            $courseRows[] = [
                'sn'     => count($courseRows) + 1,
                'code'   => $code,
                'units'  => $units,
                'grade'  => $grade,
                'gp'     => $gp,
                'qpoint' => $qPoint,
                'tcp'    => $qPoint,
                'tlu'    => $passed ? $units : 0,
                'cu_tcp' => 0,           // populated below
                'cu_tlu' => 0,           // populated below
                'cu_gpa' => 0,           // populated below
                'passed' => $passed,
                'carry'  => $passed ? 'CLEARED' : 'CARRY OVER',
                'repeat' => $passed ? 'PASS' : 'FAIL',
            ];

            if ($passed) {
                $cumulativeTCP    += $qPoint;
                $cumulativeCuTLU  += $units;
                $summary = $this->bucketSummary($summary, $grade);
            } else {
                $outstanding[] = [
                    'code'   => $code,
                    'title'  => $current->studentCourse->course->title ?? '—',
                    'units'  => $units,
                    'grade'  => $grade,
                ];
            }
        }

        // Cumulative columns on the bottom row — running totals.
        $runningTCP = 0; $runningTLU = 0;
        foreach ($courseRows as $i => $row) {
            $runningTCP += (float) $row['tcp'];
            $runningTLU += (int) $row['tlu'];
            $courseRows[$i]['cu_tcp'] = round($runningTCP, 2);
            $courseRows[$i]['cu_tlu'] = $runningTLU;
            $courseRows[$i]['cu_gpa'] = $runningTLU > 0 ? round($runningTCP / $runningTLU, 2) : 0.0;
        }
        $cumulativeGPA = $cumulativeCuTLU > 0
            ? round($cumulativeTCP / $cumulativeCuTLU, 2)
            : 0.0;

        return view('academic-board.results.print-student', [
            'student'        => $student,
            'rows'           => $courseRows,
            'cumulative_tcp' => round($cumulativeTCP, 2),
            'cumulative_tlu' => $cumulativeCuTLU,
            'cumulative_gpa' => $cumulativeGPA,
            'summary'        => $summary,
            'outstanding'    => $outstanding,
            'gradingScale'   => \App\Models\Grade::orderByDesc('min_score')->get(),
        ]);
    }

    /**
     * Bucket a single grade into the SUMMARY OF RESULTS table on
     * the reference (RESULT_1.jpg) sheet:
     *   Distinction  → A
     *   Upper Credit → B
     *   Lower Credit → C
     *   Pass         → D / E
     *   Fail         → F
     */
    private function bucketSummary(array $summary, string $grade): array
    {
        return match (strtoupper($grade)) {
            'A'      => array_merge($summary, ['distinction' => $summary['distinction'] + 1]),
            'B'      => array_merge($summary, ['upper_credit' => $summary['upper_credit'] + 1]),
            'C'      => array_merge($summary, ['lower_credit' => $summary['lower_credit'] + 1]),
            'D', 'E' => array_merge($summary, ['pass'         => $summary['pass'] + 1]),
            default  => array_merge($summary, ['fail'         => $summary['fail'] + 1]),
        };
    }
}