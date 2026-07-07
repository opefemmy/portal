<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FinanceInvoice;
use App\Models\Finance\FinanceReceipt;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceBudget;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'today_income' => FinanceReceipt::whereDate('payment_date', today())->sum('amount'),
            'monthly_income' => FinanceReceipt::whereMonth('payment_date', date('m'))
                ->whereYear('payment_date', date('Y'))->sum('amount'),
            'pending_invoices' => FinanceInvoice::where('status', 'pending')->count(),
            'outstanding_balance' => FinanceInvoice::whereIn('status', ['pending', 'partial'])
                ->sum('balance'),
            'total_expenses' => FinanceTransaction::where('type', 'debit')
                ->whereMonth('transaction_date', date('m'))
                ->whereYear('transaction_date', date('Y'))
                ->sum('amount'),
            'active_budgets' => FinanceBudget::where('status', 'active')->count(),
        ];

        // Recent transactions
        $recentTransactions = FinanceTransaction::with('user')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();

        // Recent receipts
        $recentReceipts = FinanceReceipt::with('student')
            ->orderBy('payment_date', 'desc')
            ->limit(10)
            ->get();

        // Daily income for last 7 days
        $dailyIncome = FinanceReceipt::select(
            DB::raw('DATE(payment_date) as date'),
            DB::raw('SUM(amount) as total')
        )
            ->whereDate('payment_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Income by category this month
        $incomeByCategory = FinanceTransaction::where('type', 'credit')
            ->whereMonth('transaction_date', date('m'))
            ->whereYear('transaction_date', date('Y'))
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        return view('finance.dashboard', compact('stats', 'recentTransactions', 'recentReceipts', 'dailyIncome', 'incomeByCategory'));
    }

    public function reports()
    {
        return view('finance.reports.index');
    }

    public function dailyReport()
    {
        $date = request()->date ?? today();

        $receipts = FinanceReceipt::with('student')
            ->whereDate('payment_date', $date)
            ->get();

        $total = $receipts->sum('amount');

        return view('finance.reports.daily', compact('receipts', 'date', 'total'));
    }

    public function monthlyReport()
    {
        $month = request()->month ?? date('m');
        $year = request()->year ?? date('Y');

        $receipts = FinanceReceipt::with('student')
            ->whereMonth('payment_date', $month)
            ->whereYear('payment_date', $year)
            ->get();

        $total = $receipts->sum('amount');

        return view('finance.reports.monthly', compact('receipts', 'month', 'year', 'total'));
    }

    public function incomeExpenditure()
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

        $incomeByCategory = FinanceTransaction::where('type', 'credit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        $expensesByCategory = FinanceTransaction::where('type', 'debit')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        return view('finance.reports.income-expenditure', compact(
            'startDate', 'endDate', 'income', 'expenses', 'netIncome',
            'incomeByCategory', 'expensesByCategory'
        ));
    }
}