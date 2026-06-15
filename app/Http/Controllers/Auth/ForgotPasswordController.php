<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'Email address not found in our records.');
        }

        // Check if user has secret question set up
        if (!$user->secret_question) {
            return back()->with('error', 'This account does not have a secret question set up. Please contact the administrator.');
        }

        // Store email in session temporarily
        session()->put('password_reset_email', $request->email);

        return redirect()->route('password.secret-question');
    }

    public function showSecretQuestionForm()
    {
        if (!session()->has('password_reset_email')) {
            return redirect()->route('password.forgot');
        }

        $email = session('password_reset_email');
        $user = User::where('email', $email)->first();

        return view('auth.secret-question', compact('user'));
    }

    public function verifySecretAnswer(Request $request)
    {
        $request->validate([
            'secret_answer' => 'required',
        ]);

        $email = session('password_reset_email');
        $user = User::where('email', $email)->first();

        if (!$user || strtolower($user->secret_answer) !== strtolower($request->secret_answer)) {
            return back()->with('error', 'Invalid secret answer. Please try again.');
        }

        // Generate reset token
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        session()->forget('password_reset_email');
        session()->put('password_reset_token', $token);

        // Send reset link (in production, send via email)
        // For now, show the token directly
        return redirect()->route('password.reset-form')->with('info', 'Your password reset token: ' . $token);
    }

    public function showResetForm()
    {
        if (!session()->has('password_reset_token')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $token = session('password_reset_token');
        $email = DB::table('password_reset_tokens')
            ->where('token', Hash::make($token))
            ->first();

        if (!$email) {
            return back()->with('error', 'Invalid or expired reset token.');
        }

        $user = User::where('email', $email->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_tokens')->where('email', $email->email)->delete();
        session()->forget('password_reset_token');

        return redirect()->route('login')->with('success', 'Password reset successfully. Please login with your new password.');
    }
}