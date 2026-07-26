<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SystemSetting;
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

    /**
     * Send password reset link via email
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'If that email exists, we have sent a password reset link.');
        }

        // Generate reset token
        $token = Str::random(64);

        // Delete any existing tokens for this email
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Store token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        // Build reset URL
        $resetUrl = url('/password/reset/' . $token . '?email=' . urlencode($request->email));

        // In production, send via email
        // For demo, we'll show the link
        $resetLink = $resetUrl;

        // Try to send email if configured
        try {
            // Check if mail is configured
            if ($this->isMailConfigured()) {
                Mail::send('emails.password-reset', ['token' => $token, 'user' => $user], function($message) use ($user) {
                    $message->to($user->email);
                    $message->subject('Password Reset Request');
                });
            }
        } catch (\Exception $e) {
            // Continue even if email fails - we'll show the link for demo
        }

        // Store for demo purposes
        session()->put('demo_reset_token', $token);
        session()->put('demo_reset_email', $request->email);

        return back()->with('status', 'Password reset link sent! For demo: ' . $resetLink);
    }

    /**
     * Show password reset form
     */
    public function showResetForm(Request $request)
    {
        $token = $request->route('token');
        $email = $request->query('email');

        if (!$token || !$email) {
            return redirect()->route('password.forgot')->with('error', 'Invalid reset link.');
        }

        return view('auth.reset-password', compact('token', 'email'));
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Verify token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$resetRecord) {
            return back()->with('error', 'Invalid or expired reset token.');
        }

        // Check if token is expired (1 hour)
        $tokenAge = Carbon::parse($resetRecord->created_at)->diffInMinutes(Carbon::now());
        if ($tokenAge > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->with('error', 'Reset token has expired. Please request a new one.');
        }

        // Update user password
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => Carbon::now(),
            'must_change_password' => false,
        ]);

        // Delete used token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', 'Password reset successfully. Please login with your new password.');
    }

    /**
     * Check if mail is configured
     */
    private function isMailConfigured()
    {
        $driver = config('mail.mailer', 'smtp');
        $host = config('mail.host');

        return !empty($host) && $host !== 'smtp.mailtrap.io';
    }

    // ===========================================
    // LEGACY SECRET QUESTION METHODS (Deprecated)
    // ===========================================

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

        return redirect()->route('password.reset-form')->with('info', 'Your password reset token: ' . $token);
    }
}
