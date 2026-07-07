<?php

namespace App\Http\Controllers\BusinessCommittee;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $pendingResults = Result::where('status', 'approved_by_dean')->count();
        $approvedResults = Result::where('status', 'approved_by_business')->count();

        return view('business-committee.dashboard', compact('pendingResults', 'approvedResults'));
    }
}