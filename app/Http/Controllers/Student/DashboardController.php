<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\Payment;
use App\Models\Fee;
use App\Models\Session;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Render the student dashboard.
     *
     * Wrapped in a top-level Throwable catch so an unhandled exception
     * in the view or any of the model accessors it calls gets logged with
     * full detail instead of bubbling up to the bootstrap exception
     * handler and rendering a generic 500 page. The user lands on a
     * useful error page they can act on, and the technical team has the
     * trace in storage/logs/laravel.log to debug from.
     */
    public function index()
    {
        try {
            return $this->indexInner();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('student dashboard: uncaught error', [
                'user_id' => optional(auth()->user())->id,
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->view('errors.500', ['exception' => $e], 500);
        }
    }

    private function indexInner()
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (!$student) {
            return view('student.dashboard', [
                'student' => null,
                'registeredCourses' => collect(),
                'payments' => collect(),
                'fees' => collect(),
                'unpaidFees' => collect(),
                'error' => 'Your student profile has not been set up yet. Please contact the registrar.'
            ]);
        }

        // Check if profile is incomplete
        $profileIncomplete = !$student->school_id || !$student->department_id || !$student->programme_id;

        $registeredCourses = StudentCourse::where('student_id', $student->id)
            ->with('course')
            ->get();

        $payments = Payment::where('student_id', $student->id)
            ->with('fee')
            ->latest()
            ->take(5)
            ->get();

        // Get fees based on student's department, programme, and session
        $fees = Fee::where('session_id', $student->session_id)
            ->where(function($query) use ($student) {
                $query->where('department_id', $student->department_id)
                    ->orWhereNull('department_id');
            })
            ->where(function($query) use ($student) {
                $query->where('programme_id', $student->programme_id)
                    ->orWhereNull('programme_id');
            })
            ->where('is_active', true)
            ->orderBy('amount')
            ->get();

        // Get unpaid fees
        $paidFeeIds = Payment::where('student_id', $student->id)
            ->where('status', 'completed')
            ->pluck('fee_id')
            ->toArray();

        $unpaidFees = $fees->whereNotIn('id', $paidFeeIds);

        // If student was manually uploaded (not from application), filter out application/acceptance fees
        if ($student->from_application === false) {
            // Manually uploaded students should not pay application fee
            $unpaidFees = $unpaidFees->filter(function($fee) {
                $feeNameLower = strtolower($fee->name ?? '');
                // Exclude application fee and acceptance fee for manually uploaded students
                return !in_array($feeNameLower, ['application fee', 'acceptance fee', 'admission fee', 'application form fee']);
            });
        }

        return view('student.dashboard', compact('student', 'registeredCourses', 'payments', 'fees', 'unpaidFees', 'profileIncomplete'));
    }
}