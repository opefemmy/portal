<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeletedRecord;
use App\Models\Finance\FinanceReceipt;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceRefund;
use App\Models\Finance\FinanceInvoice;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_transactions' => FinanceTransaction::count(),
            'total_receipts' => FinanceReceipt::count(),
            'total_deleted_records' => DeletedRecord::count(),
            'audit_logs_count' => AuditLog::count(),
            'today_transactions' => FinanceTransaction::whereDate('created_at', today())->count(),
            'total_income' => FinanceTransaction::where('type', 'credit')->sum('amount'),
            'total_expenses' => FinanceTransaction::where('type', 'debit')->sum('amount'),
            'pending_refunds' => FinanceRefund::where('status', 'pending')->count(),
        ];

        // Recent audit logs
        $recentLogs = AuditLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Failed actions
        $failedActions = AuditLog::with('user')
            ->where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('auditor.dashboard', compact('stats', 'recentLogs', 'failedActions'));
    }
}