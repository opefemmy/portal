<?php

namespace App\Http\Controllers\AcademicBoard;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $pendingResults = Result::where('status', 'approved_by_business')->count();
        $finalApproved = Result::where('status', 'approved_final')->count();

        return view('academic-board.dashboard', compact('pendingResults', 'finalApproved'));
    }
}