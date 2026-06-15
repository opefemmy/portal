<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Applicant;
use App\Models\Course;
use App\Models\Result;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        // Student enrollment by level
        $enrollmentByLevel = Student::select('level', DB::raw('count(*) as count'))
            ->groupBy('level')->get();

        // Monthly payments (last 12 months)
        $paymentsByMonth = Payment::where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(amount) as total'))
            ->groupBy('month')->get();

        // Applications by status
        $applicationsByStatus = Applicant::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->get();

        // Top departments by student count
        $topDepartments = Student::select('department_id', DB::raw('count(*) as count'))
            ->with('department')
            ->groupBy('department_id')
            ->orderByDesc('count')
            ->limit(5)->get();

        // Payment summary
        $paymentStats = [
            'total_collected' => Payment::where('status', 'completed')->sum('amount'),
            'this_month' => Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'pending' => Payment::where('status', 'pending')->sum('amount'),
        ];

        // Result statistics
        $resultStats = [
            'total_results' => Result::count(),
            'passed' => Result::whereRaw('total_score >= 40')->count(),
            'failed' => Result::whereRaw('total_score < 40')->count(),
        ];

        return view('admin.analytics.index', compact(
            'enrollmentByLevel', 'paymentsByMonth', 'applicationsByStatus',
            'topDepartments', 'paymentStats', 'resultStats'
        ));
    }
}