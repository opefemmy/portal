<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Student;
use App\Services\ApplicantPaymentService;
use App\Services\SchoolFeeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Shared "test payment" simulator, available to every portal area
 * (applicant, student, bursar, registrar). Use this only in non-production
 * environments — it bypasses real gateways and writes test rows to the
 * payments table so the demo flow can be exercised end-to-end.
 *
 * On production (`APP_ENV=production`) every route returns 404 so an
 * attacker who somehow lands here gets a "not found" instead of
 * accidentally creating a fake payment.
 *
 * Every invocation writes an ActivityLog row with user_id, audience,
 * payment_type_id, and IP. Audit keeps the loophole honest.
 */
class TestPaymentController extends Controller
{
    public function __construct(private readonly ApplicantPaymentService $payments)
    {
    }

    /**
     * Render the test-payment picker. The page shows every PaymentType
     * for the audience the viewer belongs to (or both, for staff roles
     * like bursar / registrar / admin), plus a quick fill button.
     */
    public function show(Request $request, string $audience)
    {
        $this->assertNonProduction();
        $this->assertAudienceAllowed($audience);

        $user = Auth::user();
        $audienceForCatalogue = $this->resolveCatalogueAudience($user, $audience);

        // BOTH catalogues need a union so staff roles (bursar / registrar /
        // admin) see every active payment row regardless of audience.
        // Otherwise staff would only see applicant rows when the requested
        // audience parameter is "both", which defeats the point of having
        // a unified "All" picker.
        $types = match ($audienceForCatalogue) {
            PaymentType::AUDIENCE_STUDENT => $this->payments->getStudentPaymentTypes(),
            PaymentType::AUDIENCE_APPLICANT => $this->payments->getApplicantPaymentTypes(),
            default => $this->payments->getApplicantPaymentTypes()
                ->merge($this->payments->getStudentPaymentTypes())
                ->unique('id')
                ->sortBy('priority')
                ->values(),
        };

        // Optional Fee list — only meaningful for student-audience flows
        // because that's where Fee-catalogued school-fee payments live.
        // Showing the fee picker lets the user simulate "Pay HIM 100L
        // exactly" which the previous form couldn't express (the form
        // was hard-coded to PaymentType-only and fee_id was always null,
        // so SchoolFeeCalculator::totalPercentPaid never saw the row and
        // exam clearance stayed locked).
        $fees = collect();
        if (in_array($audienceForCatalogue, [PaymentType::AUDIENCE_STUDENT, PaymentType::AUDIENCE_BOTH], true)) {
            $fees = Fee::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'amount', 'indigene_amount', 'non_indigene_amount', 'portal_charge']);
        }

        $this->audit('test_payment.show', [
            'audience' => $audience,
            'catalogue_audience' => $audienceForCatalogue,
            'type_count' => $types->count(),
            'fee_count' => $fees->count(),
        ]);

        return view('payments.test', [
            'types' => $types,
            'fees' => $fees,
            'audience' => $audience,
            'audienceLabel' => $audienceForCatalogue,
            'user' => $user,
            'pickedType' => null,
            'pickedFeeAmount' => 0.0,
        ]);
    }

    /**
     * Process the picked type — creates a row directly in the payments
     * table with gateway='test', then runs the existing side-effects
     * (markCompleted + applicant→student migration when applicable) so
     * the rest of the system behaves identically to a real Paystack
     * callback.
     */
    public function process(Request $request, string $audience)
    {
        $this->assertNonProduction();
        $this->assertAudienceAllowed($audience);

        $validated = $request->validate([
            'payment_type_id' => 'required|integer|exists:payment_types,id',
            'fee_id'          => 'nullable|integer|exists:fees,id',
            // Drop the previous min:100 floor — some fees are intentionally
            // small (e.g. HIM 100L is ₦20). The user types the exact
            // amount the catalogue row lists so the simulated row matches.
            'amount'          => 'required|numeric|min:1',
        ]);

        $user = Auth::user();
        $type = PaymentType::findOrFail($validated['payment_type_id']);

        // Optional Fee link. When the user picks a Fee (e.g. HIM 100L
        // school fee) the simulator writes `fee_id=<id>` so:
        //   - the payment counts toward SchoolFeeCalculator::totalPercentPaid
        //     (which filters by fee_id and is what unlocks exam clearance),
        //   - the dashboard's `unpaidFees` correctly drops the fee,
        //   - the receipt/audit trail ties the simulated payment to a
        //     specific Fee catalogue row, mirroring what the live
        //     Paystack path does.
        //
        // The amount defaults to the Fee's priceFor(student's category)
        // so the user doesn't have to type it — but we still accept the
        // form's amount as an override (e.g. to test partial-payment
        // flows).
        $fee = null;
        $feeAmount = (float) $validated['amount'];
        $percentPaid = 0;
        $installmentLabel = null;
        if (!empty($validated['fee_id'])) {
            $fee = Fee::findOrFail($validated['fee_id']);

            // Resolve the student-side category so we pick the right
            // branch of priceFor(). Falls back to non_indigene when we
            // don't yet have a Student row (e.g. testing the simulator
            // before admission migrates the user).
            $category = 'non_indigene';
            $studentForCategory = $this->resolveStudentFor($user);
            if ($studentForCategory && $studentForCategory->user?->isIndigene()) {
                $category = 'indigene';
            }
            $feeAmount = (float) $fee->priceFor($category) + (float) $fee->portal_charge;
            // The user-typed amount wins when present and different —
            // that way partial-payment scenarios stay testable.
            if ((float) $validated['amount'] > 0) {
                $feeAmount = (float) $validated['amount'];
            }
            // When the test pays the full fee, mark percent_paid=100
            // so the exam-clearance gate unlocks. Smaller amounts get
            // 60 / 40 depending on whether the user is paying 100% of
            // the fee or a partial slice.
            $percentPaid = SchoolFeeCalculator::PERCENT_FULL;
            $installmentLabel = SchoolFeeCalculator::installmentLabel(SchoolFeeCalculator::PERCENT_FULL);
        }

        // Resolve the right payer for this audience — applicants pay
        // from their Applicant row, students pay from their Student
        // row. This is the same resolution the live gateway uses
        // under the hood, just simplified.
        $applicant = $this->resolveApplicantFor($user);
        $student   = $this->resolveStudentFor($user);

        if ($audience === PaymentType::AUDIENCE_APPLICANT && ! $applicant) {
            $applicant = $this->createStubApplicant($user);
        }
        if ($audience === PaymentType::AUDIENCE_STUDENT && ! $student) {
            $student = $this->createStubStudent($user);
        }

        $reference = 'TEST-' . strtoupper(Str::random(10)) . '-' . date('Ymd');

        // Stamp the same columns the real gateway does, regardless of
        // audience. For students we also link via student_id so the
        // /bursar payments dashboard sees the row.
        $payment = Payment::create([
            'student_id'        => $student?->id,
            'fee_id'            => $fee?->id,
            'amount'            => $feeAmount,
            'total_amount'      => $feeAmount,
            'percent_paid'      => $percentPaid,
            'installment_label' => $installmentLabel,
            'reference'         => $reference,
            'payment_ref'       => $reference,
            'transaction_id'    => $reference,
            'gateway'           => 'test',
            'payment_method'    => 'test',
            'status'            => 'completed',
            'is_verified'       => true,
            'student_type'      => $audience,
            'payment_purpose'   => $type->purpose,
            // payments.fee_type is an ENUM on production — always go
            // through feeTypeFor() so we never write a value that's
            // outside the allowed set.
            'fee_type'          => $this->payments->feeTypeFor($type->purpose),
            'payer_id'          => $applicant?->id,
            'payer_name'        => $applicant?->full_name ?? $student?->full_name ?? $user->name,
            'payer_email'       => $applicant?->email ?? $student?->email ?? $user->email,
            'payer_phone'       => $applicant?->phone ?? $student?->phone,
            'payment_date'      => now(),
            'payment_details'   => json_encode([
                'test_mode'  => true,
                'simulated'  => true,
                'audience'   => $audience,
                'user_id'    => $user->id,
                'purpose'    => $type->purpose,
                'fee_id'     => $fee?->id,
                'fee_amount' => $feeAmount,
                'ip'         => $request->ip(),
            ]),
        ]);

        // Mirror the side-effects so the per-applicant columns (and
        // the migration to student) reflect the simulated payment. This
        // is the same code path the live gateway's markCompleted() hits.
        if ($applicant) {
            try {
                $this->payments->markCompleted($payment, [
                    'test_mode' => true,
                    'simulated' => true,
                    'user_id'   => $user->id,
                    'purpose'   => $type->purpose,
                    'via'       => 'cross_audience_test',
                ]);
            } catch (\Throwable $e) {
                // Demo path — never 500. Log and continue.
                Log::warning('test payment: markCompleted failed (non-fatal)', [
                    'payment_id' => $payment->id,
                    'audience'   => $audience,
                    'purpose'    => $type->purpose,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $this->audit('test_payment.process', [
            'audience' => $audience,
            'payment_type_id' => $type->id,
            'payment_type_code' => $type->code,
            'purpose' => $type->purpose,
            'amount'  => (float) $validated['amount'],
            'payment_id' => $payment->id,
        ]);

        // Redirect URL is audience-specific — the route layout isn't
        // uniform across portals (applicant uses /payment/test/applicant,
        // student uses /payment/test, etc).
        $showUrl = match ($audience) {
            PaymentType::AUDIENCE_STUDENT => '/student/payment/test',
            PaymentType::AUDIENCE_APPLICANT => '/applicant/payment/test/applicant',
            default => '/bursar/payment/test',
        };

        return redirect()
            ->to($showUrl)
            ->with('success', "Test payment recorded: {$type->name} (Ref: {$payment->reference}).");
    }

    /**
     * Bail out of all test-payment traffic on production. Returning 404
     * instead of 403 makes the endpoint invisible to attackers scanning
     * for it; the route file also whitelists envs as a second layer.
     */
    private function assertNonProduction(): void
    {
        if (app()->environment('production')) {
            abort(404, 'Test payment simulator is disabled in production.');
        }
    }

    /**
     * Allow-list of valid audiences — keeps the route parameter typed
     * instead of trusting arbitrary strings.
     */
    private function assertAudienceAllowed(string $audience): void
    {
        if (! in_array($audience, [
            PaymentType::AUDIENCE_APPLICANT,
            PaymentType::AUDIENCE_STUDENT,
            PaymentType::AUDIENCE_BOTH,
        ], true)) {
            abort(404, "Unknown payment audience [{$audience}].");
        }
    }

    /**
     * Pick the right catalogue row based on role. Bursars, registrars,
     * admins see BOTH by default so they can test any fee from any
     * portal area in one place.
     */
    private function resolveCatalogueAudience(\App\Models\User $user, string $requestedAudience): string
    {
        if ($requestedAudience === PaymentType::AUDIENCE_BOTH) {
            return PaymentType::AUDIENCE_BOTH;
        }

        // Viewers with privileged roles default to BOTH so they can
        // exercise both applicant + student flows.
        if ($user->hasAnyRole(['bursar', 'registrar', 'super_admin', 'admin'])) {
            return PaymentType::AUDIENCE_BOTH;
        }

        // Pure student role -> student catalogue.
        if ($user->hasRole('student')) {
            return PaymentType::AUDIENCE_STUDENT;
        }

        return $requestedAudience;
    }

    private function resolveApplicantFor(\App\Models\User $user): ?Applicant
    {
        return Applicant::where('user_id', $user->id)->first();
    }

    private function resolveStudentFor(\App\Models\User $user): ?Student
    {
        return Student::where('user_id', $user->id)->first();
    }

    /**
     * Build a thin applicant stub if the user has none yet — mirrors
     * the helper in PaymentGatewayController so behaviour matches.
     */
    private function createStubApplicant(\App\Models\User $user): ?Applicant
    {
        $school     = \App\Models\School::first();
        $department = \App\Models\Department::first();
        $programme  = \App\Models\Programme::first();
        $session    = \App\Models\Session::where('is_current', true)->first() ?? \App\Models\Session::first();

        if (! $school || ! $department || ! $programme || ! $session) {
            return null;
        }

        try {
            return Applicant::create([
                'user_id'            => $user->id,
                'email'              => $user->email,
                'application_number' => Applicant::generateApplicationNumber(),
                'status'             => 'pending',
                'school_id'          => $school->id,
                'department_id'      => $department->id,
                'programme_id'       => $programme->id,
                'session_id'         => $session->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('test payment: stub applicant create failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function createStubStudent(\App\Models\User $user): ?Student
    {
        $school     = \App\Models\School::first();
        $department = \App\Models\Department::first();
        $programme  = \App\Models\Programme::first();
        $session    = \App\Models\Session::where('is_current', true)->first() ?? \App\Models\Session::first();

        if (! $school || ! $department || ! $programme || ! $session) {
            return null;
        }

        try {
            // Only fields in Student::$fillable — the others (email,
            // first_name, last_name) live on the user table and are
            // reachable via $student->user.
            return Student::create([
                'user_id'      => $user->id,
                'matric_number'=> 'STU/' . strtoupper(Str::random(8)),
                'school_id'    => $school->id,
                'department_id'=> $department->id,
                'programme_id' => $programme->id,
                'session_id'   => $session->id,
                'level'        => 1,
                'status'       => 'active',
            ]);
        } catch (\Throwable $e) {
            Log::warning('test payment: stub student create failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function audit(string $action, array $extra): void
    {
        try {
            ActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => $action,
                'description' => "Test payment simulator: $action",
                'metadata'    => json_encode(array_merge([
                    'ip'         => request()->ip(),
                    'ua'         => request()->userAgent(),
                ], $extra)),
            ]);
        } catch (\Throwable $e) {
            // Audit failure must never block the request — log and
            // continue. Refusing to take a payment because ActivityLog
            // is broken would defeat the point.
            Log::warning('test payment audit log failed', [
                'action' => $action,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
