<?php

namespace App\Http\Controllers\AcademicBoard;

use App\Http\Controllers\Controller;
use App\Models\Result;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
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
        $this->assertInSameSchool($result);
        $result->update([
            'status' => 'approved_final',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Result finally approved by Academic Board');
    }

    public function reject(Request $request, Result $result)
    {
        $this->assertInSameSchool($result);
        $result->update([
            'status' => 'rejected_final',
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Result rejected by Academic Board');
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