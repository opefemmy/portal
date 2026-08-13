<?php

namespace App\Http\Controllers\Dean;

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

        // The dean dashboard used to be a placeholder ("Dashboard
        // content coming soon"). It now renders the four dean stat
        // tiles from the registry — institution-level overview
        // (schools, departments, students, programmes).
        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('dean.dashboard', compact('widgets'));
    }
}