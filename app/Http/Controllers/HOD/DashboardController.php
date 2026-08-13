<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('academic.dashboard.view');

        $user = $request->user();
        $departmentId = $user->department_id;

        // Stat tiles + the two Recent Assignments / Pending Results
        // tables are all widget-rendered; their closures already
        // scope by `auth()->user()->department_id` and degrade to
        // zero / empty when no department is assigned. The view
        // still surfaces a "not assigned to any department" alert
        // and a Quick Actions card — both stay in chrome.
        $widgets = DashboardResolver::widgetsForUser($user);

        return view('hod.dashboard', compact('widgets', 'departmentId'));
    }
}