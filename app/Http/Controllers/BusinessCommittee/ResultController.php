<?php

namespace App\Http\Controllers\BusinessCommittee;

use App\Http\Controllers\Controller;
use App\Models\Result;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $query = Result::with(['studentCourse.student.user', 'studentCourse.course'])
            ->where('status', 'approved_by_dean');

        if ($request->session_id) {
            $query->whereHas('studentCourse', function($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        $results = $query->latest()->paginate(20);
        return view('business-committee.results.index', compact('results'));
    }

    public function approve(Request $request, Result $result)
    {
        $this->assertInSameSchool($result);
        $this->assertCanActOn($result, 'approved_by_dean');
        $result->update([
            'status' => 'approved_by_business',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Result approved by Business Committee');
    }

    public function reject(Request $request, Result $result)
    {
        $this->assertInSameSchool($result);
        $this->assertCanActOn($result, 'approved_by_dean');
        $result->update([
            'status' => 'rejected_by_business',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Result rejected by Business Committee');
    }

    /**
     * Bulk approve results at the Business Committee stage.
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'integer|exists:results,id',
        ]);

        $user = auth()->user();
        $updated = $this->bulkUpdateStatus($request->result_ids, $user, 'approved_by_dean', 'approved_by_business', null);

        return back()->with('success', "{$updated} result(s) approved at Business Committee stage.");
    }

    /**
     * Bulk reject results at the Business Committee stage.
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'integer|exists:results,id',
            'remarks' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        $updated = $this->bulkUpdateStatus($request->result_ids, $user, 'approved_by_dean', 'rejected_by_business', $request->remarks);

        return back()->with('success', "{$updated} result(s) rejected at Business Committee stage.");
    }

    private function bulkUpdateStatus(array $ids, $user, string $fromStatus, string $toStatus, ?string $remarks): int
    {
        $query = Result::whereIn('id', $ids)
            ->where('status', $fromStatus);

        if ($user && $user->school_id) {
            $query->whereHas('studentCourse.student', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
        }

        return $query->update(array_filter([
            'status' => $toStatus,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'remarks' => $remarks,
        ], fn ($v) => $v !== null));
    }

    private function assertCanActOn(Result $result, string $expectedStatus): void
    {
        if ($result->status !== $expectedStatus) {
            abort(409, "This result is no longer in the {$expectedStatus} state and cannot be acted on at the Business Committee stage.");
        }
    }

    private function assertInSameSchool(Result $result): void
    {
        $user = auth()->user();
        // If the user has a school_id, the result's student must belong to that school.
        if ($user && $user->school_id) {
            $student = $result->studentCourse->student ?? null;
            if (!$student || (int) $student->school_id !== (int) $user->school_id) {
                abort(403, 'You are not allowed to act on this result.');
            }
        }
    }
}