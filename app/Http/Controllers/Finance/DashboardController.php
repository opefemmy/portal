<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Finance\FinanceInvoice;
use App\Models\Finance\FinanceReceipt;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceBudget;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('finance.dashboard.view');

        $widgets = DashboardResolver::widgetsForUser($request->user());

        // Chrome payload — Recent Transactions + Recent Receipts tables
        // and the daily/category income data the JS sparkline consumes.
        // Stat tiles are widget-rendered (finance audience).
        $recentTransactions = FinanceTransaction::with('user')
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();

        $recentReceipts = FinanceReceipt::with('student')
            ->orderBy('payment_date', 'desc')
            ->limit(10)
            ->get();

        $dailyIncome = FinanceReceipt::select(
            DB::raw('DATE(payment_date) as date'),
            DB::raw('SUM(amount) as total')
        )
            ->whereDate('payment_date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $incomeByCategory = FinanceTransaction::where('type', 'credit')
            ->whereMonth('transaction_date', date('m'))
            ->whereYear('transaction_date', date('Y'))
            ->select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        return view('finance.dashboard', compact('widgets', 'recentTransactions', 'recentReceipts', 'dailyIncome', 'incomeByCategory'));
    }

    public function reports()
    {
        $this->requirePermission('finance.dashboard.view');
        return view('finance.reports.index');
    }

    public function dailyReport()
    {
        $this->requirePermission('finance.receipts.view');

        $date = request()->date ?? today();

        $receipts = FinanceReceipt::with('student')
            ->whereDate('payment_date', $date)
            ->get();

        $total = $receipts->sum('amount');

        return view('finance.reports.daily', compact('receipts', 'date', 'total'));
    }

    public function monthlyReport()
    {
        $this->requirePermission('finance.receipts.view');

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
        $this->requirePermission('finance.transactions.view');

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