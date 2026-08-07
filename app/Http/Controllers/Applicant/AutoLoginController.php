<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\AutoLoginController as StudentAutoLogin;
use App\Models\Applicant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Self-service auto-login for applicants who have been migrated to the
 * student portal.
 *
 * The applicant pays the compulsory fee → ApplicantPaymentService
 * migrates them to a Student row (migrateApplicantToStudent) → a
 * `student_id` is stamped on the applicants row. Once that happens the
 * dashboard shows a "Go to Student Portal" button that hits this
 * endpoint.
 *
 * We delegate to the existing student-side
 * {@see StudentAutoLogin::generateForStudent()} which mints a signed URL
 * bound to the user id, flips must_change_password=true, and returns a
 * one-time URL that the student-side consume() endpoint will use to
 * sign them in and bounce them to /student/password/change-required.
 *
 * Sitting in the Applicant namespace keeps the existing applicant
 * route group + middleware (auth, role:applicant) intact, so only the
 * applicant themselves can mint their own link.
 */
class AutoLoginController extends Controller
{
    /**
     * Mint a signed auto-login URL for the current applicant and 302
     * straight into it.
     */
    public function issue(Request $request): RedirectResponse
    {
        $applicant = Applicant::where('user_id', $request->user()->id)->first();

        if (! $applicant) {
            return redirect()->route('applicant.dashboard')
                ->with('error', 'Applicant record not found. Please contact the registrar.');
        }

        // Pre-migration applicants don't have a Student row yet. Bounce
        // them back to the dashboard with an explanation rather than
        // minting a link that consume() will reject (it requires
        // userModel->isStudent() to be true).
        if (! $applicant->student_id) {
            return redirect()->route('applicant.dashboard')
                ->with('error', 'Student portal access is not yet available. Please pay the compulsory fee first.');
        }

        $student = $applicant->student;
        if (! $student) {
            return redirect()->route('applicant.dashboard')
                ->with('error', 'Your student record could not be loaded. Please contact the registrar.');
        }

        $url = StudentAutoLogin::generateForStudent($student);

        // redirect()->away() because $url is an absolute signed URL on
        // the student side; we don't want Laravel to prefix it with
        // the applicant route group's URL.
        return redirect()->away($url);
    }
}
