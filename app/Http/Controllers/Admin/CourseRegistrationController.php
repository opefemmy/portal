<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentCourse;
use App\Models\Session;
use App\Models\Student;
use Illuminate\Http\Request;

class CourseRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = StudentCourse::with(['student.user', 'course', 'session']);

        // Filter by session
        if ($request->session_id) {
            $query->where('session_id', $request->session_id);
        } else {
            $query->where('session_id', Session::getCurrentSession()?->id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by semester
        if ($request->semester) {
            $query->where('semester', $request->semester);
        }

        $registrations = $query->latest()->get();
        $sessions = Session::all();

        return view('admin.course-registrations.index', compact('registrations', 'sessions'));
    }

    public function unsubmit(StudentCourse $registration)
    {
        // Only allow unsubmit if status is registered
        if ($registration->status !== 'registered') {
            return back()->with('error', 'Cannot unsubmit this course registration.');
        }

        $registration->update(['status' => 'unsubmitted']);
        return back()->with('success', 'Course registration unsubmitted successfully.');
    }

    public function resubmit(StudentCourse $registration)
    {
        $registration->update(['status' => 'registered']);
        return back()->with('success', 'Course registration resubmitted successfully.');
    }

    public function export(Request $request)
    {
        $query = StudentCourse::with(['student.user', 'course', 'session']);

        if ($request->session_id) {
            $query->where('session_id', $request->session_id);
        }

        $registrations = $query->get();

        // Return as downloadable CSV
        $headers = ['Matric Number', 'Student Name', 'Course Code', 'Course Title', 'Session', 'Semester', 'Status', 'Date'];

        $data = $registrations->map(function ($reg) {
            return [
                $reg->student->matric_number ?? 'N/A',
                $reg->student->user->name ?? 'N/A',
                $reg->course->code ?? 'N/A',
                $reg->course->title ?? 'N/A',
                $reg->session->name ?? 'N/A',
                $reg->semester,
                $reg->status,
                $reg->created_at->format('Y-m-d'),
            ];
        });

        return response()->download_csv($data, 'course_registrations.csv', $headers);
    }
}