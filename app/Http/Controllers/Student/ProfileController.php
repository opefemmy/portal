<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use EnforcesPermission;

    public function edit()
    {
        $this->requirePermission('student.profile.manage');
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $schools = School::all();
        $departments = Department::all();
        $programmes = Programme::all();
        $sessions = Session::where('is_current', true)->get();

        // Load the hospital patient record to allow editing medical info
        $patient = \App\Models\Hospital\HospitalPatient::where('user_id', auth()->id())->first();

        return view('student.profile', compact('student', 'schools', 'departments', 'programmes', 'sessions', 'patient'));
    }

    public function update(Request $request)
    {
        $this->requirePermission('student.profile.manage');
        $user = Auth::user();
        $student = $user->student;

        // Validate only guidance details - academic details are read-only
        $request->validate([
            'guidance_name' => 'nullable|string|max:255',
            'guidance_phone' => 'nullable|string|max:20',
            'guidance_address' => 'nullable|string',
            'blood_group' => 'nullable|string|max:10',
            'allergies' => 'nullable|string|max:500',
        ]);

        // Update user guidance details
        $user->update([
            'guidance_name' => $request->guidance_name,
            'guidance_phone' => $request->guidance_phone,
            'guidance_address' => $request->guidance_address,
        ]);

        // Update medical information in HospitalPatient model
        $patient = \App\Models\Hospital\HospitalPatient::where('user_id', $user->id)->first();
        if ($patient) {
            $patient->update([
                'blood_group' => $request->blood_group,
                'allergies' => $request->allergies,
            ]);
        }

        return redirect()->route('student.dashboard')->with('success', 'Profile details updated successfully!');
    }

    public function uploadPassport(Request $request)
    {
        $this->requirePermission('student.profile.manage');
        $request->validate([
            'passport' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();

        if ($request->hasFile('passport')) {
            // Delete old passport if exists
            if ($user->passport && file_exists(public_path('uploads/passports/' . $user->passport))) {
                unlink(public_path('uploads/passports/' . $user->passport));
            }

            $file = $request->file('passport');
            $filename = $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/passports'), $filename);
            $user->update(['passport' => $filename]);
        }

        return back()->with('success', 'Passport uploaded successfully');
    }
}