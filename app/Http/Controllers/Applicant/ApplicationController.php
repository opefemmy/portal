<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function dashboard()
    {
        $applicant = Applicant::where('user_id', auth()->id())->first();
        return view('applicant.dashboard', compact('applicant'));
    }

    public function showApplicationForm()
    {
        $data = [
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
            'sessions' => Session::where('is_active', true)->get(),
        ];
        return view('applicant.apply', $data);
    }

    public function submitApplication(Request $request)
    {
        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        $applicant = Applicant::create([
            'user_id' => auth()->id(),
            'application_number' => Applicant::generateApplicationNumber(),
            'school_id' => $validated['school_id'],
            'department_id' => $validated['department_id'],
            'programme_id' => $validated['programme_id'],
            'session_id' => $validated['session_id'],
            'status' => 'pending',
        ]);

        return redirect()->route('applicant.application')->with('success', 'Application submitted successfully!');
    }

    public function viewApplication()
    {
        $applicant = Applicant::where('user_id', auth()->id())->firstOrFail();
        return view('applicant.application', compact('applicant'));
    }
}