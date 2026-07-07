<?php

namespace App\Http\Controllers\Student;

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
    public function edit()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $schools = School::all();
        $departments = Department::all();
        $programmes = Programme::all();
        $sessions = Session::where('is_current', true)->get();

        return view('student.profile', compact('student', 'schools', 'departments', 'programmes', 'sessions'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $student = $user->student;

        // Validate only guidance details - academic details are read-only
        $request->validate([
            'guidance_name' => 'nullable|string|max:255',
            'guidance_phone' => 'nullable|string|max:20',
            'guidance_address' => 'nullable|string',
        ]);

        // Update only user guidance details - academic details are managed by institution
        $user->update([
            'guidance_name' => $request->guidance_name,
            'guidance_phone' => $request->guidance_phone,
            'guidance_address' => $request->guidance_address,
        ]);

        return redirect()->route('student.dashboard')->with('success', 'Guidance details saved successfully!');
    }

    public function uploadPassport(Request $request)
    {
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