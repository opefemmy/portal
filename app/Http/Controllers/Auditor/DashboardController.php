<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Widget tiles (audit + finance totals) come from the registry.
        $widgets = DashboardResolver::widgetsForUser($request->user());

        // Chrome data: recent audit logs and failed actions stay in the
        // view as full tables (they need date/user joins that don't fit
        // the generic widget shape). The `withTrashed()` calls defend
        // against a soft-delete column mismatch — the AuditLog model
        // declares SoftDeletes but the audit_logs table doesn't have
        // a `deleted_at` column, so plain `AuditLog::count()` 500s on
        // missing-column SQL.
        $recentLogs = AuditLog::with('user')
            ->withTrashed()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $failedActions = AuditLog::with('user')
            ->withTrashed()
            ->where('status', 'failed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('auditor.dashboard', compact('widgets', 'recentLogs', 'failedActions'));
    }
}