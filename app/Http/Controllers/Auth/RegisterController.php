<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Student;
use App\Models\Applicant;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6',
            'phone' => 'nullable|string|max:20',
            'matric_number' => 'nullable|string|max:20',
        ]);

        $studentRole = Role::where('slug', 'student')->first();

        if (!$studentRole) {
            return back()->with('error', 'Student role not found. Please contact administrator.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        // Get current active session
        $currentSession = Session::where('is_current', true)->first();

        // Create student profile
        Student::create([
            'user_id' => $user->id,
            'matric_number' => $validated['matric_number'] ?? null,
            'session_id' => $currentSession?->id,
            'level' => 1, // Default to ND1 (100L)
            'status' => 'active',
        ]);

        auth()->login($user);

        return redirect('/student/dashboard')->with('success', 'Registration successful!');
    }

    public function showApplicantForm()
    {
        return view('auth.applicant-register');
    }

    public function registerApplicant(Request $request)
    {
        $validated = $request->validate([
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6',
            'phone' => 'nullable|string|max:20',
        ]);

        $applicantRole = Role::where('slug', 'applicant')->first();

        if (!$applicantRole) {
            return back()->with('error', 'Applicant role not found. Please contact administrator.');
        }

        // Concatenate the three name parts for users.name so existing code
        // (mailers, navigation, audit logs) that reads users.name still works.
        $fullName = trim(
            ($validated['surname'] ?? '') . ' ' .
            ($validated['first_name'] ?? '') . ' ' .
            ($validated['middle_name'] ?? '')
        );

        $user = User::create([
            'name' => $fullName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role_id' => $applicantRole->id,
            'is_active' => true,
        ]);

        // Seed an Applicant row with the split name parts so the apply form
        // can pre-fill and lock them. The Applicant record is created
        // here at signup (typically created later, on first payment), but
        // doing it now lets us enforce the name-once contract early.
        Applicant::firstOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $validated['email'],
                'surname' => $validated['surname'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'status' => 'draft',
            ]
        );

        auth()->login($user);

        return redirect('/applicant/dashboard')->with('success', 'Account created! Complete your application.');
    }
}