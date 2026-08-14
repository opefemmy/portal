<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Concerns\ResolvesRegistrarSignature;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\PaymentType;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Student-facing admission-letter reprint.
 *
 * After an applicant has paid the compulsory fee they get migrated to a
 * Student row (ApplicantPaymentService::migrateApplicantToStudent) and
 * signed into the student portal via the applicant->student auto-login
 * flow. Once on the student side, the original applicant dashboard is no
 * longer reachable — but the user still needs to be able to print their
 * admission letter (registrar signature, fees schedule, etc.).
 *
 * This controller reuses the same applicant.admission-letter blade and
 * the same gates as the applicant-side printAdmissionLetter() in
 * Applicant\ApplicationController — the user must have been admitted
 * AND paid the acceptance fee.
 *
 * The applicant row is loaded via the Student row's `applicant_id`
 * (stamped by the migration flow). If the student is not linked to an
 * applicant (e.g. a legacy direct-admit) we 404 rather than guessing.
 */
class AdmissionLetterController extends Controller
{
    use EnforcesPermission;
    use ResolvesRegistrarSignature;

    /**
     * Show the admission letter for the authenticated student.
     *
     * Mirrors Applicant\ApplicationController::printAdmissionLetter()
     * exactly: same eager-load, same status/payment gates, same blade.
     */
    public function show(Request $request): View
    {
        $this->requirePermission('student.admission-letter.view');
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();

        // The Student row must have been linked back to its source
        // Applicant — the migration flow stamps `applicant_id` on the
        // Student. Without that link there is no admission-letter data
        // to render.
        if (! $student->applicant_id) {
            abort(404, 'No applicant record is linked to this student.');
        }

        $applicant = Applicant::with([
            'school',
            'department',
            'programme',
            'session',
            'state',
            'localGovernment',
            'nationalityRecord',
        ])->find($student->applicant_id);

        if (! $applicant || $applicant->status !== 'admitted') {
            abort(403, 'You have not been admitted yet.');
        }

        if (! $applicant->hasPaid(PaymentType::PURPOSE_ACCEPTANCE)) {
            abort(403, 'Please pay the acceptance fee before printing your admission letter.');
        }

        // The applicant.admission-letter blade reads both $applicant
        // and $student — $student is used to pull the canonical
        // matric number off the Student row rather than the applicant's
        // copy. $signatureUrl resolves through ResolvesRegistrarSignature
        // — walks the new public/uploads/ location first, then the legacy
        // storage/ paths, and falls back to a fixed
        // public/uploads/signatures/registrar_signature.{ext} file if the
        // registrar drops one in directly (the "live" fallback).
        return view('applicant.admission-letter', [
            'applicant'    => $applicant,
            'student'      => $student,
            'signatureUrl' => $this->resolveRegistrarSignatureUrl(),
        ]);
    }
}
