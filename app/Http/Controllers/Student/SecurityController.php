<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SecurityController extends Controller
{
    public function showSetupForm()
    {
        return view('student.auth.security-setup');
    }

    public function setup(Request $request)
    {
        $request->validate([
            'security_question' => 'required|string|max:255',
            'security_answer' => 'required|string|max:255',
            'confirm_answer' => 'required|same:security_answer',
        ], [
            'confirm_answer.same' => 'Security answer confirmation does not match.',
        ]);

        $user = Auth::user();

        $user->update([
            'security_question' => $request->security_question,
            'security_answer' => Hash::make($request->security_answer),
        ]);

        // Redirect to profile to complete biodata
        return redirect()->route('student.profile')
            ->with('success', 'Security question set successfully. Please complete your profile.')
            ->with('info', 'Please fill in your guidance details and upload your passport.');
    }
}