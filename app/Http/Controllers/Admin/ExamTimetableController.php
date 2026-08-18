<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\ExamTimetable;
use App\Models\Course;
use App\Models\Session;
use Illuminate\Http\Request;

class ExamTimetableController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('admin.exam-timetables.manage');
        $query = ExamTimetable::with(['course', 'session']);

        if ($request->session_id) {
            $query->where('session_id', $request->session_id);
        }

        if ($request->semester) {
            $query->where('semester', $request->semester);
        }

        $timetables = $query->latest()->get();
        $sessions = Session::all();

        return view('admin.exam-timetable.index', compact('timetables', 'sessions'));
    }

    public function create()
    {
        $this->requirePermission('admin.exam-timetables.manage');
        $data = [
            'courses' => Course::with('department')->get(),
            'sessions' => Session::all(),
        ];
        return view('admin.exam-timetable.create', $data);
    }

    public function store(Request $request)
    {
        $this->requirePermission('admin.exam-timetables.manage');
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'session_id' => 'required|exists:sessions,id',
            'semester' => 'required|in:First,Second',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'nullable|string|max:255',
        ]);

        ExamTimetable::create($validated);
        return redirect()->route('admin.exam-timetable.index')->with('success', 'Exam timetable created');
    }

    public function edit(ExamTimetable $examTimetable)
    {
        $this->requirePermission('admin.exam-timetables.manage');
        $data = [
            'timetable' => $examTimetable,
            'courses' => Course::with('department')->get(),
            'sessions' => Session::all(),
        ];
        return view('admin.exam-timetable.edit', $data);
    }

    public function update(Request $request, ExamTimetable $examTimetable)
    {
        $this->requirePermission('admin.exam-timetables.manage');
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'session_id' => 'required|exists:sessions,id',
            'semester' => 'required|in:First,Second',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'venue' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $examTimetable->update($validated);
        return redirect()->route('admin.exam-timetable.index')->with('success', 'Exam timetable updated');
    }

    public function destroy(ExamTimetable $examTimetable)
    {
        $this->requirePermission('admin.exam-timetables.manage');
        $examTimetable->delete();
        return back()->with('success', 'Exam timetable deleted');
    }
}