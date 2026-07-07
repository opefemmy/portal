<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Finance\FinanceReceipt;
use App\Models\Finance\FinanceInvoice;
use App\Models\Finance\FinanceTransaction;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalPatient;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function studentReport()
    {
        $studentsByDepartment = DB::table('users')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.slug', 'student')
            ->select('departments.name', DB::raw('count(*) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('count')
            ->get();

        $studentsByLevel = User::whereHas('role', fn($q) => $q->where('slug', 'student'))
            ->select('level', DB::raw('count(*) as count'))
            ->groupBy('level')
            ->orderByDesc('count')
            ->get();

        $studentsByProgramme = DB::table('users')
            ->join('programmes', 'users.programme_id', '=', 'programmes.id')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.slug', 'student')
            ->select('programmes.name', DB::raw('count(*) as count'))
            ->groupBy('programmes.id', 'programmes.name')
            ->orderByDesc('count')
            ->get();

        return view('executive.reports.students', compact(
            'studentsByDepartment', 'studentsByLevel', 'studentsByProgramme'
        ));
    }

    public function financialReport()
    {
        $startDate = request()->start_date ?? now()->startOfMonth();
        $endDate = request()->end_date ?? now()->endOfMonth();

        $income = FinanceTransaction::where('type', 'credit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $expenses = FinanceTransaction::where('type', 'debit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $netIncome = $income - $expenses;

        $receipts = FinanceReceipt::whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $outstanding = FinanceInvoice::whereIn('status', ['pending', 'partial'])
            ->sum('balance');

        return view('executive.reports.financial', compact(
            'startDate', 'endDate', 'income', 'expenses', 'netIncome', 'receipts', 'outstanding'
        ));
    }

    public function hospitalReport()
    {
        $month = request()->month ?? date('m');
        $year = request()->year ?? date('Y');

        $totalPatients = HospitalPatient::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)->count();

        $totalAppointments = HospitalAppointment::whereMonth('appointment_date', $month)
            ->whereYear('appointment_date', $year)->count();

        $topDiseases = DB::table('hospital_diagnoses')
            ->join('hospital_medical_records', 'hospital_diagnoses.medical_record_id', '=', 'hospital_medical_records.id')
            ->whereMonth('hospital_medical_records.consultation_date', $month)
            ->whereYear('hospital_medical_records.consultation_date', $year)
            ->select('hospital_diagnoses.diagnosis', DB::raw('count(*) as count'))
            ->groupBy('hospital_diagnoses.diagnosis')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return view('executive.reports.hospital', compact(
            'month', 'year', 'totalPatients', 'totalAppointments', 'topDiseases'
        ));
    }

    public function staffReport()
    {
        $staffByRole = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->whereNotIn('roles.slug', ['student', 'applicant'])
            ->select('roles.name', DB::raw('count(*) as count'))
            ->groupBy('roles.id', 'roles.name')
            ->orderByDesc('count')
            ->get();

        $staffByDepartment = DB::table('users')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->whereNotIn('roles.slug', ['student', 'applicant'])
            ->select('departments.name', DB::raw('count(*) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('count')
            ->get();

        return view('executive.reports.staff', compact('staffByRole', 'staffByDepartment'));
    }
}