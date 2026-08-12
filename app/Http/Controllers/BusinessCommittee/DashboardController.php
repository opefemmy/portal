<?php

namespace App\Http\Controllers\BusinessCommittee;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Services\Dashboard\DashboardResolver;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $pendingResults = Result::where('status', 'approved_by_dean')->count();
        $approvedResults = Result::where('status', 'approved_by_business')->count();

        // Widget grid (stat tiles) — read from the registry via
        // DashboardResolver. The "Result Approval" call-to-action
        // card with intro paragraph stays in the view's chrome.
        $widgets = DashboardResolver::widgetsForUser($request->user());

        return view('business-committee.dashboard', compact(
            'widgets', 'pendingResults', 'approvedResults'
        ));
    }
}