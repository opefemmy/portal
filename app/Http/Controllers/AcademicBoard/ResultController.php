<?php

namespace App\Http\Controllers\AcademicBoard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Result;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('academic.results.view');

        $query = Result::with(['studentCourse.student.user', 'studentCourse.course'])
            ->where('status', 'approved_by_business');

        if ($request->session_id) {
            $query->whereHas('studentCourse', function($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        $results = $query->latest()->paginate(20);
        return view('academic-board.results.index', compact('results'));
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
}