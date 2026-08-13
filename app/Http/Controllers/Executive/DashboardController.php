<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Finance\FinanceReceipt;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('executive.dashboard.view');

        // Stat tiles and the recent-receipts table come from the
        // widget registry; the rest stays in chrome.
        $widgets = DashboardResolver::widgetsForUser($request->user());

        // Chrome data: students-by-department table — needs a
        // users.role_id subquery join that doesn't fit the generic
        // widget data shape.
        $topDepartments = DB::table('users')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->where('users.role_id', function ($q) {
                $q->select('id')->from('roles')->where('slug', 'student');
            })
            ->select('departments.name', DB::raw('count(*) as count'))
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Chrome data: full list of recent receipts (the widget tile
        // shows the same data but capped at 5; chrome is for the
        // page-header "Recent Receipts" table if we want a full list
        // elsewhere — kept for forward compatibility).
        $recentReceipts = FinanceReceipt::with('student')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('executive.dashboard', compact(
            'widgets', 'topDepartments', 'recentReceipts'
        ));
    }
}