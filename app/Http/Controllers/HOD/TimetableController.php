<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Models\Timetable;
use App\Models\Course;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function index()
    {
        return view('hod.timetable');
    }

    public function approve(Timetable $timetable)
    {
        $this->assertInHodDepartment($timetable);
        $timetable->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return back()->with('success', 'Timetable approved');
    }

    public function reject(Timetable $timetable)
    {
        $this->assertInHodDepartment($timetable);
        $timetable->update(['status' => 'rejected']);
        return back()->with('success', 'Timetable rejected');
    }

    private function assertInHodDepartment(Timetable $timetable): void
    {
        $user = auth()->user();
        if (!$user || !$user->department_id) {
            abort(403, 'You are not assigned to a department.');
        }
        $course = $timetable->courseAssignment->course ?? null;
        if (!$course || (int) $course->department_id !== (int) $user->department_id) {
            abort(403, 'You are not allowed to act on this timetable.');
        }
    }
}