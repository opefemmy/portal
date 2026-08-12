<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            // "My Courses" table stays in the view's chrome — it carries
            // per-row action buttons (Students / Enter Results / Template)
            // that the generic table-card partial can't render. The
            // stat tiles above it are widget-rendered; their closures
            // already scope by `auth()->id()` so they stay in sync
            // with this collection.
            $assignments = CourseAssignment::where('lecturer_id', auth()->id())
                ->with(['course', 'course.department', 'session', 'studentCourses'])
                ->get();
        } catch (\Exception $e) {
            // Defensive: hand-built controller used to swallow any
            // eager-load failure and render zeros. The widget
            // closures handle their own scoping so we just need to
            // make sure the chrome table doesn't blow up the whole
            // page.
            \Log::error('Lecturer dashboard error: ' . $e->getMessage());
            $assignments = collect([]);
        }

        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('lecturer.dashboard', compact('assignments', 'widgets'));
    }

    public function courses()
    {
        try {
            $assignments = CourseAssignment::where('lecturer_id', auth()->id())
                ->with(['course', 'course.department', 'session'])
                ->get();
            return view('lecturer.courses', compact('assignments'));
        } catch (\Exception $e) {
            \Log::error('Lecturer courses error: ' . $e->getMessage());
            return view('lecturer.courses', ['assignments' => collect()]);
        }
    }

    public function courseStudents(\App\Models\Course $course)
    {
        // Delegate to LecturerResultController which provides the data needed by the view.
        return app(\App\Http\Controllers\Lecturer\ResultController::class)->courseStudents($course);
    }

    public function timetable()
    {
        return view('lecturer.timetable');
    }
}