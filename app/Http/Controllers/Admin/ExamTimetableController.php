<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamTimetable;
use App\Models\Course;
use App\Models\Session;
use Illuminate\Http\Request;

class ExamTimetableController extends Controller
{
    public function index(Request $request)
    {
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
        $data = [
            'courses' => Course::with('department')->get(),
            'sessions' => Session::all(),
        ];
        return view('admin.exam-timetable.create', $data);
    }

    public function store(Request $request)
    {
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
        $data = [
            'timetable' => $examTimetable,
            'courses' => Course::with('department')->get(),
            'sessions' => Session::all(),
        ];
        return view('admin.exam-timetable.edit', $data);
    }

    public function update(Request $request, ExamTimetable $examTimetable)
    {
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
        $examTimetable->delete();
        return back()->with('success', 'Exam timetable deleted');
    }
}