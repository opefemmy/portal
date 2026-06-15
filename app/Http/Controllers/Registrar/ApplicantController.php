<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function index()
    {
        $applicants = Applicant::with('user', 'department', 'programme')->latest()->get();
        return view('registrar.applicants', compact('applicants'));
    }

    public function show(Applicant $applicant)
    {
        return view('registrar.applicants-show', compact('applicant'));
    }

    public function admit(Applicant $applicant, Request $request)
    {
        $applicant->update([
            'status' => 'admitted',
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Applicant admitted');
    }

    public function reject(Applicant $applicant, Request $request)
    {
        $applicant->update([
            'status' => 'rejected',
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Applicant rejected');
    }
}