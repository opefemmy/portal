<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Finance\FinanceReceipt;
use App\Models\Finance\FinanceInvoice;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalAdmission;
use App\Models\Finance\FinancePayroll;
use App\Models\Finance\FinanceBudget;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Student Stats
        $studentStats = [
            'total' => User::whereHas('role', fn($q) => $q->where('slug', 'student'))->count(),
            'active' => User::whereHas('role', fn($q) => $q->where('slug', 'student'))->where('is_active', true)->count(),
            'new_this_month' => User::whereHas('role', fn($q) => $q->where('slug', 'student'))
                ->whereMonth('created_at', date('m'))
                ->count(),
        ];

        // Staff Stats
        $staffStats = [
            'total' => User::whereHas('role', fn($q) => $q->whereNotIn('slug', ['student', 'applicant']))->count(),
            'active' => User::whereHas('role', fn($q) => $q->whereNotIn('slug', ['student', 'applicant']))->where('is_active', true)->count(),
        ];

        // Financial Stats
        $financialStats = [
            'today_revenue' => FinanceReceipt::whereDate('payment_date', today())->sum('amount'),
            'monthly_revenue' => FinanceReceipt::whereMonth('payment_date', date('m'))
                ->whereYear('payment_date', date('Y'))->sum('amount'),
            'outstanding' => FinanceInvoice::whereIn('status', ['pending', 'partial'])->sum('balance'),
            'pending_payments' => FinanceInvoice::where('status', 'pending')->count(),
        ];

        // Hospital Stats
        $hospitalStats = [
            'today_appointments' => HospitalAppointment::whereDate('appointment_date', today())->count(),
            'admitted_patients' => HospitalAdmission::where('status', 'admitted')->count(),
            'today_patients' => HospitalAppointment::whereDate('appointment_date', today())->count(),
        ];

        // Monthly Revenue Trend (Last 6 months)
        $revenueTrend = FinanceReceipt::select(
            DB::raw('MONTH(payment_date) as month'),
            DB::raw('YEAR(payment_date) as year'),
            DB::raw('SUM(amount) as total')
        )
            ->whereDate('payment_date', '>=', now()->subMonths(6))
            ->groupBy('month', 'year')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Top Departments by Student Count
        $topDepartments = DB::table('users')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->where('users.role_id', function($q) {
                $q->select('id')->from('roles')->where('slug', 'student');
            })
            ->select('departments.name', DB::raw('count(*) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Recent Activities
        $recentReceipts = FinanceReceipt::with('student')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('executive.dashboard', compact(
            'studentStats', 'staffStats', 'financialStats', 'hospitalStats',
            'revenueTrend', 'topDepartments', 'recentReceipts'
        ));
    }
}