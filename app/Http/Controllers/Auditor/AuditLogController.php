<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('auditor.audit.logs');

        $query = AuditLog::with('user');

        if ($request->module) {
            $query->where('module', $request->module);
        }

        if ($request->action) {
            $query->where('action', $request->action);
        }

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('auditor.audit-logs', compact('logs'));
    }

    public function show(AuditLog $auditLog)
    {
        $this->requirePermission('auditor.audit.view');

        $auditLog->load('user');

        return view('auditor.audit-log-show', compact('auditLog'));
    }
}