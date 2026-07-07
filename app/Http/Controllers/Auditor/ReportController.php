<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceReceipt;
use App\Models\Finance\FinanceTransaction;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now()->endOfMonth();

        $receipts = FinanceReceipt::whereBetween('payment_date', [$startDate, $endDate])
            ->with('student')
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        $totalIncome = FinanceReceipt::whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $totalExpenses = FinanceTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'debit')
            ->sum('amount');

        $transactions = FinanceTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->paginate(20);

        return view('auditor.reports', compact('receipts', 'transactions', 'startDate', 'endDate', 'totalIncome', 'totalExpenses'));
    }

    public function financialSummary()
    {
        $summary = [
            'total_income' => FinanceTransaction::where('type', 'credit')->sum('amount'),
            'total_expenses' => FinanceTransaction::where('type', 'debit')->sum('amount'),
            'this_month_income' => FinanceReceipt::whereMonth('payment_date', now()->month)->sum('amount'),
            'this_month_expenses' => FinanceTransaction::whereMonth('transaction_date', now()->month)->where('type', 'debit')->sum('amount'),
        ];

        return view('auditor.financial-summary', compact('summary'));
    }
}