<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'sessions' => Session::all(),
            'states' => State::orderBy('name')->get(),
        ];
        return view('applicant.apply', $data);
    }

    public function submitApplication(Request $request)
    {
        $validated = $request->validate([
            // Personal Information
            'surname' => 'required|string|max:50',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string|max:100',
            'gender' => 'required|in:Male,Female,Other',
            'marital_status' => 'nullable|in:Single,Married,Divorced,Widowed',
            'nationality' => 'required|string|max:50',
            'state_of_origin' => 'nullable|string|max:50',
            'lga' => 'nullable|string|max:50',
            'permanent_address' => 'required|string',
            'contact_address' => 'nullable|string',
            'phone' => 'required|string|max:20',

            // Guardian Information
            'guardian_name' => 'required|string|max:100',
            'guardian_relationship' => 'required|string|max:50',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email',
            'guardian_occupation' => 'nullable|string|max:100',
            'guardian_address' => 'nullable|string',

            // Educational Background
            'primary_school' => 'nullable|string|max:100',
            'primary_school_start' => 'nullable|string',
            'primary_school_end' => 'nullable|string',
            'secondary_school' => 'nullable|string|max:100',
            'secondary_school_start' => 'nullable|string',
            'secondary_school_end' => 'nullable|string',
            'tertiary_institution' => 'nullable|string|max:100',
            'tertiary_qualification' => 'nullable|string|max:50',
            'tertiary_start' => 'nullable|string',
            'tertiary_end' => 'nullable|string',

            // Programme Selection
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',
            'mode_of_study' => 'nullable|string|max:50',
            'entry_level' => 'nullable|string|max:50',

            // JAMB Details
            'jamb_registration_number' => 'nullable|string|max:20',
            'jamb_year' => 'nullable|string|max:4',
            'jamb_score' => 'nullable|integer|min:0|max:400',
            'jamb_subject1' => 'nullable|string|max:50',
            'jamb_subject2' => 'nullable|string|max:50',
            'jamb_subject3' => 'nullable|string|max:50',
            'jamb_subject4' => 'nullable|string|max:50',

            // Documents
            'passport' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'olevel_certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'birth_certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'jamb_result' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        // Handle file uploads
        if ($request->hasFile('passport')) {
            $validated['passport'] = $this->uploadFile($request->file('passport'), 'passports');
        }
        if ($request->hasFile('olevel_certificate')) {
            $validated['olevel_certificate'] = $this->uploadFile($request->file('olevel_certificate'), 'certificates');
        }
        if ($request->hasFile('birth_certificate')) {
            $validated['birth_certificate'] = $this->uploadFile($request->file('birth_certificate'), 'certificates');
        }
        if ($request->hasFile('jamb_result')) {
            $validated['jamb_result'] = $this->uploadFile($request->file('jamb_result'), 'results');
        }

        // Set email from authenticated user
        $validated['email'] = Auth::user()->email;
        $validated['user_id'] = Auth::id();
        $validated['application_number'] = Applicant::generateApplicationNumber();
        $validated['status'] = 'pending';

        $applicant = Applicant::create($validated);

        return redirect()->route('applicant.application')
            ->with('success', 'Application submitted successfully! Your Application Number is: ' . $applicant->application_number);
    }

    public function viewApplication()
    {
        $applicant = Applicant::where('user_id', auth()->id())->firstOrFail();
        return view('applicant.application', compact('applicant'));
    }

    public function checkStatus(Request $request)
    {
        $request->validate([
            'application_number' => 'required|string',
        ]);

        $applicant = Applicant::where('application_number', $request->application_number)->first();

        if (!$applicant) {
            return back()->with('error', 'Application not found. Please check your application number.');
        }

        return view('applicant.status-check', compact('applicant'));
    }

    public function getDepartments($schoolId)
    {
        $departments = Department::where('school_id', $schoolId)->get();
        return response()->json($departments);
    }

    private function uploadFile($file, $folder)
    {
        $filename = $folder . '_' . time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');
        return $filename;
    }
}