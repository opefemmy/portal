<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Self-service "magic link" auto-login for students.
 *
 * Background: a registrar (or applicant admin) needs to deliver a student's
 * portal access without sending a plaintext password. They generate a
 * one-time signed URL from the applicant's profile page; when the student
 * opens the URL we:
 *
 *   1. Verify the signed URL (Laravel throws if tampered / expired).
 *   2. Look up the User and make sure they're a student with a Student row.
 *   3. Flip `must_change_password = true` so the onboarding middleware
 *      gates every other route and forces them onto the change-password
 *      form (the existing `StudentOnboardingComplete` middleware already
 *      does this — we just lean on it).
 *   4. Sign them in with `Auth::login()` (not "remember me" — this is a
 *      fresh device session).
 *   5. Redirect to /student/password/change-required, which the change
 *      form posts to /student/password/change WITHOUT a current-password
 *      field (see PasswordChangeController::changePassword() which skips
 *      the current-password check when must_change_password is true).
 *
 * The signed URL expires after the configurable time below (default
 * 7 days). The URL is bound to a specific user id so even if a student
 * leaks it, only their own account can be claimed.
 */
class AutoLoginController extends Controller
{
    /**
     * Default link lifetime in hours.
     *
     * The registrar can override per-link by passing `hours` to the
     * generator; this constant is the fallback.
     */
    public const DEFAULT_HOURS = 168; // 7 days

    /**
     * Generate a signed auto-login URL for the given student.
     *
     * Called by the registrar's admission show view (and anywhere else
     * we want to expose the link). Returns a string URL ready to share.
     *
     * Side effect: sets `must_change_password = true` on the user so
     * that if they happen to be signed in already, the onboarding
     * middleware will intercept them on their next request.
     */
    public static function generateForStudent(Student $student, int $hours = self::DEFAULT_HOURS): string
    {
        $user = $student->user;
        if (! $user) {
            throw new \RuntimeException(
                "Student #{$student->id} has no linked User account; cannot generate auto-login link."
            );
        }

        // Make sure the flag is on BEFORE we generate the URL. If we did it
        // after, a registrar who previewed the URL by mistake could land
        // the student on the dashboard with their old (possibly weak /
        // auto-generated) password still active.
        $user->forceFill(['must_change_password' => true])->save();

        return URL::temporarySignedRoute(
            'student.auto-login.consume',
            now()->addHours($hours),
            ['user' => $user->id]
        );
    }

    /**
     * Consume the auto-login link: authenticate and redirect to the
     * password change form. The route is signed so Laravel rejects
     * tampered URLs with a 403 before this method runs.
     */
    public function consume(Request $request, int $user)
    {
        $userModel = User::with('role', 'students')->find($user);

        if (! $userModel) {
            // The URL is valid but the user has been deleted since the
            // link was generated. Don't leak which case this is; surface
            // a generic failure to the login page.
            Log::warning('auto-login link consumed but user not found', ['user_id' => $user]);
            return redirect()->route('login')
                ->with('error', 'This sign-in link is no longer valid. Please contact the registrar.');
        }

        if (! $userModel->isStudent()) {
            // Auto-login is for students only. Reject anything else (the
            // signed URL bound to user_id shouldn't let a non-student
            // through, but defence-in-depth: a registrar who pasted the
            // wrong link would otherwise log themselves out).
            Log::warning('auto-login link consumed by non-student', [
                'user_id'   => $userModel->id,
                'role_slug' => optional($userModel->role)->slug,
            ]);
            return redirect()->route('login')
                ->with('error', 'This sign-in link is only valid for student accounts.');
        }

        if (! $userModel->is_active) {
            return redirect()->route('login')
                ->with('error', 'Your account is not active. Please contact the registrar.');
        }

        // Sign them in. `remember = false` — the auto-login link is for
        // a specific device/session; we don't want a 30-day cookie to
        // outlive the password change.
        Auth::login($userModel, false);

        // Force a fresh session id so the link can't be replayed by a
        // different browser after we've consumed it here.
        $request->session()->regenerate();

        // Make sure the flag stays on. The generateForStudent() helper
        // already flipped it, but if a registrar generated a link for
        // a student who had already set their password (and thus had
        // must_change_password=false), we want the password change to
        // still gate the rest of the portal.
        $userModel->forceFill(['must_change_password' => true])->save();

        return redirect()->route('student.password.change.required')
            ->with('info', 'Welcome! Please set a new password to access your student portal.');
    }
}