<?php

namespace App\Http\Controllers\AcademicBoard;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $pendingResults = Result::where('status', 'approved_by_business')->count();
        $finalApproved = Result::where('status', 'approved_final')->count();

        // Widget grid (stat tiles) — read from the registry via
        // DashboardResolver. The "Final Result Approval"
        // call-to-action card with intro paragraph stays in the view's
        // chrome.
        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('academic-board.dashboard', compact(
            'widgets', 'pendingResults', 'finalApproved'
        ));
    }
}