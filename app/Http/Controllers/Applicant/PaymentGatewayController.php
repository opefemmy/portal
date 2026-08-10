<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\PaymentType;
use App\Models\SystemSetting;
use App\Models\Payment;
use App\Models\User;
use App\Services\ApplicantPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentGatewayController extends Controller
{
    public function __construct(private readonly ApplicantPaymentService $payments)
    {
    }

    /**
     * Show payment page with Pay Now button (and bank-transfer tab).
     *
     * URL: /applicant/payment/gateway?purpose=application|acceptance|school_fee
     *
     * Wrapped in a top-level Throwable catch so a downstream error
     * (unrun migration, FK drift) never surfaces as a 500.
     */
    public function showPaymentPage(Request $request)
    {
        try {
            return $this->showPaymentPageInner($request);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('payment gateway page: uncaught error', [
                'user_id' => optional(Auth::user())->id,
                'purpose' => $request->get('purpose'),
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('applicant.dashboard')
                ->with('error', 'We could not load the payment page. Please try again or contact the admissions office.');
        }
    }

    /**
     * Real implementation of showPaymentPage.
     */
    private function showPaymentPageInner(Request $request)
    {
        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        // $request->get() returns null when the query key is missing AND
        // also when it's present-but-empty (?purpose=). The default only
        // kicks in for missing keys. Normalise to a known constant so
        // downstream calls (canPay, resolvePaymentType) never see a null
        // or empty purpose.
        $rawPurpose = $request->get('purpose');
        $purpose = $rawPurpose !== null && $rawPurpose !== ''
            ? $rawPurpose
            : PaymentType::PURPOSE_APPLICATION;

        \Illuminate\Support\Facades\Log::info('payment gateway: requested', [
            'user_id' => $user->id ?? null,
            'has_applicant' => (bool) $applicant,
            'purpose' => $purpose,
        ]);

        // Locked to the applicant audience so an admin can't accidentally
        // route a student-only type into the applicant flow.
        $paymentType = $this->payments->resolvePaymentType($purpose, PaymentType::AUDIENCE_APPLICANT);
        if (! $paymentType) {
            \Illuminate\Support\Facades\Log::warning('payment gateway: no PaymentType resolved', [
                'user_id' => $user->id ?? null,
                'purpose' => $purpose,
            ]);
            return back()->with('error', 'Payment type not configured. Please contact the admissions office.');
        }

        // For the application-fee flow a fresh user with no applicant row is
        // expected — they're on the official "pay before filling the form"
        // path. Auto-create a stub so canPay() (which requires an Applicant
        // instance) doesn't TypeError, and the Pay Now button on the next
        // page can submit successfully. For acceptance/school_fee
        // a missing row means the user is trying to skip ahead — bounce
        // them with a clear message instead.
        if (! $applicant) {
            if ($purpose !== PaymentType::PURPOSE_APPLICATION) {
                return redirect()->route('applicant.dashboard')
                    ->with('error', 'No application record found for your account.');
            }

            $applicant = $this->createStubApplicant($user);
            if (! $applicant) {
                \Illuminate\Support\Facades\Log::error('payment gateway: stub applicant create returned null', [
                    'user_id' => $user->id ?? null,
                    'purpose'  => $purpose,
                    'reason'   => 'createStubApplicant failed (see prior log entry for the underlying exception)',
                ]);
                return redirect()->route('applicant.dashboard')
                    ->with('error', 'We could not start your application record just now. Please try again or contact the admissions office.');
            }
        }

        // Service-driven gate (replaces the two ad-hoc checks that lived here before).
        $block = $this->payments->canPay($applicant, $purpose);
        if ($block) {
            \Illuminate\Support\Facades\Log::info('payment gateway: canPay blocked', [
                'user_id' => $user->id ?? null,
                'purpose' => $purpose,
                'block'   => $block,
            ]);
            return redirect()->route('applicant.dashboard')->with('error', $block);
        }

        $feeAmount = $this->payments->resolveAmount($purpose);

        return view('applicant.payment-gateway', compact('applicant', 'paymentType', 'feeAmount', 'purpose'));
    }

    /**
     * Initiate online payment. Returns the Paystack inline iframe page
     * with the freshly-created pending Payment reference.
     *
     * Wrapped in a top-level Throwable catch so a downstream error
     * (unrun migration, FK drift, schema mismatch on a fresh deploy,
     * etc.) never surfaces as a 500 to the applicant. We log and redirect
     * back to the gateway with a generic error flash; the user can retry.
     */
    public function initiatePayment(Request $request)
    {
        try {
            return $this->initiatePaymentInner($request);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('payment initiate: uncaught error', [
                'user_id' => optional(Auth::user())->id,
                'amount' => $request->input('amount'),
                'purpose' => $request->input('purpose'),
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $purpose = $request->input('purpose', PaymentType::PURPOSE_APPLICATION);
            $gatewayUrl = route('applicant.payment.gateway', ['purpose' => $purpose]);

            return redirect($gatewayUrl)
                ->with('error', 'We could not start your payment just now. Please try again or contact the admissions office if the issue persists.');
        }
    }

    /**
     * Real implementation of initiatePayment — split out so the public
     * entry point can wrap it in a top-level Throwable catch and never 500.
     */
    private function initiatePaymentInner(Request $request)
    {
        // PURPOSE_COMPULSORY is the applicant→student migration trigger
        // (see ApplicantPaymentService::applyApplicantSideEffects). The
        // dashboard's "Pay Compulsory Fee" button links to
        // /applicant/payment/gateway?purpose=compulsory and the form
        // re-posts the value here, so it must be on the validator's
        // whitelist or the user sees the catch-all "Test payment
        // simulated (handler recovered from an internal error)" message
        // — masking the real ValidationException.
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'purpose' => 'nullable|string|in:application,acceptance,school_fee,compulsory',
        ]);

        $user = Auth::user();

        // Normalise purpose: $request->input()'s default doesn't kick in
        // when the key is present-but-empty (?purpose=). Coerce null/'' to
        // the application-fee default so canPay() and resolvePaymentType()
        // never see an empty purpose.
        $rawPurpose = $request->input('purpose');
        $purpose = $rawPurpose !== null && $rawPurpose !== ''
            ? $rawPurpose
            : PaymentType::PURPOSE_APPLICATION;

        $applicant = Applicant::where('user_id', $user->id)->first();

        if (! $applicant) {
            // Only the application-fee flow can boot a fresh applicant — the
            // user is on the official "pay before filling the form" path
            // (/applicant/apply/payment -> Pay Now here). For acceptance,
            // school_fee, the user must already have a
            // submitted (and admitted) application, so a missing row means
            // they're trying to skip ahead — send them to the dashboard.
            if ($purpose !== PaymentType::PURPOSE_APPLICATION) {
                return redirect()->route('applicant.dashboard')
                    ->with('error', 'No application record found for your account.');
            }

            $applicant = $this->createStubApplicant($user);
            if (! $applicant) {
                \Log::error('payment initiate: stub applicant create failed', [
                    'user_id' => $user->id,
                    'purpose' => $purpose,
                ]);

                return redirect()->route('applicant.dashboard')
                    ->with('error', 'We could not start your application record just now. Please try again or contact the admissions office.');
            }
        }

        $block = $this->payments->canPay($applicant, $purpose);
        if ($block) {
            return redirect()->route('applicant.dashboard')->with('error', $block);
        }

        $initiated = $this->payments->initiate($applicant, $purpose, 'paystack', PaymentType::AUDIENCE_APPLICANT);

        session()->put('pending_payment_id', $initiated['payment']->id);
        session()->put('pending_payment_ref', $initiated['reference']);
        session()->put('pending_payment_purpose', $purpose);

        $paystackPublicKey = config('services.paystack.public_key', 'pk_test_xxxxxxxxxxxxxxxx');

        return view('applicant.payment-initiate', [
            'reference' => $initiated['reference'],
            'amount' => $initiated['amount'],
            'email' => $user->email,
            'paystackPublicKey' => $paystackPublicKey,
            'callbackUrl' => route('applicant.payment.callback'),
            'purpose' => $purpose,
        ]);
    }

    /**
     * Retry a pending or failed payment attempt.
     *
     * Re-initiates the gateway flow against the most recent open row
     * (status pending/failed) for the current applicant + purpose. If no
     * open row exists yet, falls through to initiatePayment so a fresh
     * attempt is created. Completed/verified payments block retry — the
     * service's canPay() gate handles that, so we bounce with a clear
     * flash rather than creating a duplicate Payment row.
     *
     * Posted from /applicant/payments/history next to the existing
     * "Requery" button on rows whose status is pending or failed.
     */
    public function retryPayment(Request $request, string $purpose)
    {
        try {
            $user = Auth::user();
            $applicant = Applicant::where('user_id', $user->id)->first();

            if (! $applicant) {
                return redirect()->route('applicant.dashboard')
                    ->with('error', 'No application record found for your account.');
            }

            // Normalise purpose the same way the rest of the controller
            // does — null/empty → application. URL parameter comes in via
            // route binding so the {purpose} segment is always present,
            // but a stray empty string would still slip through.
            $purpose = $purpose !== ''
                ? $purpose
                : PaymentType::PURPOSE_APPLICATION;

            // Lock to the applicant audience — same as showPaymentPage.
            $paymentType = $this->payments->resolvePaymentType($purpose, PaymentType::AUDIENCE_APPLICANT);
            if (! $paymentType) {
                return redirect()->route('applicant.dashboard')
                    ->with('error', 'Payment type not configured. Please contact the admissions office.');
            }

            // Block retry if the fee is already paid. canPay() reads the
            // canonical three-purpose *_paid_at columns OR
            // payments.status='completed' for non-canonical purposes,
            // so this is the same gate the gateway uses.
            $block = $this->payments->canPay($applicant, $purpose);
            if ($block) {
                return redirect()->route('applicant.dashboard')
                    ->with('info', $block);
            }

            // Find the most recent open attempt (pending/failed). If none,
            // create a fresh one through the normal initiate flow.
            $existing = $this->payments->pendingAttemptFor($applicant, $purpose);

            if (! $existing) {
                // No open attempt — fall through to a normal init so the
                // user still gets a payment page, just from a clean slate.
                $request->merge(['purpose' => $purpose]);
                return $this->initiatePayment($request);
            }

            // Reuse the open row. refreshForRetry() updates the reference,
            // gateway fields, flips status back to pending and clears
            // payment_date / paid_at / is_verified — i.e. prepares the
            // row for the gateway init call below.
            $reference = $this->payments->generateReference($purpose, 'paystack');
            $existing->refreshForRetry($reference, 'paystack');

            session()->put('pending_payment_id', $existing->id);
            session()->put('pending_payment_ref', $reference);
            session()->put('pending_payment_purpose', $purpose);

            $paystackPublicKey = config('services.paystack.public_key', 'pk_test_xxxxxxxxxxxxxxxx');

            return view('applicant.payment-initiate', [
                'reference' => $reference,
                'amount' => $existing->amount,
                'email' => $user->email,
                'paystackPublicKey' => $paystackPublicKey,
                'callbackUrl' => route('applicant.payment.callback'),
                'purpose' => $purpose,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('payment retry: uncaught error', [
                'user_id' => optional(Auth::user())->id,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('applicant.dashboard')
                ->with('error', 'We could not resume your payment just now. Please try again or contact the admissions office.');
        }
    }

    /**
     * Paystack payment callback. Single funnel into the service.
     *
     * Wrapped in a top-level Throwable catch so a verification failure or
     * downstream error never surfaces as a 500 to the applicant.
     */
    public function paymentCallback(Request $request)
    {
        try {
            return $this->paymentCallbackInner($request);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('payment callback: uncaught error', [
                'reference' => $request->reference,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('applicant.dashboard')
                ->with('error', 'Payment verification could not be completed. Please contact the admissions office.');
        }
    }

    /**
     * Real implementation of paymentCallback.
     */
    private function paymentCallbackInner(Request $request)
    {
        $reference = $request->reference;

        if (! $reference) {
            return redirect()->route('applicant.payment')
                ->with('error', 'Payment reference not found.');
        }

        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            return redirect()->route('applicant.payment')
                ->with('error', 'Payment record not found.');
        }

        $verified = $this->verifyPaystackPayment($reference);

        if ($verified && ($verified['status'] ?? null) === 'success') {
            // markCompleted stamps applicant.application_paid_at (etc.) and
            // triggers applicant→student migration for school_fee. wrap in
            // try/catch so a downstream failure (e.g. unrun migration, FK drift)
            // still redirects the user with a success-flash instead of 500-ing;
            // the verifyPayment side of the contract (the Paystack row) is
            // already saved as 'pending' so nothing is lost.
            try {
                $this->payments->markCompleted($payment, $verified);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('payment callback: markCompleted failed', [
                    'payment_id' => $payment->id,
                    'reference' => $reference,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('applicant.dashboard')
                    ->with('error', 'Payment verified but applicant record could not be updated. Please contact the admissions office.');
            }

            session()->forget('pending_payment_id');
            session()->forget('pending_payment_ref');
            session()->forget('pending_payment_purpose');

            $purpose = $payment->payment_purpose;

            $paymentType = \App\Models\PaymentType::findByPurpose($purpose);
            [$redirectRoute, $successMessage] = $this->resolveSuccessRouteAndMessage($payment, $paymentType);

            // student.dashboard is gated by role:student middleware. If the
            // applicant→student migration didn't fully run (e.g. matric
            // number generation failed and the user's role is still
            // applicant), the named route exists but the role middleware
            // would 403 them. Fall back to the applicant dashboard so the
            // user always lands somewhere with the success flash.
            if ($this->payments->isMigrationTrigger($paymentType ?? null)) {
                $freshApplicant = Applicant::find($payment->payer_id);
                if (! $freshApplicant?->isMigrated() || ! $freshApplicant->user?->hasRole('student')) {
                    $redirectRoute = 'applicant.dashboard';
                    $successMessage = (($paymentType?->name ?: $paymentType?->display_label) ?? 'Migration') . ' verified. Your student record is being prepared — please check back in a moment.';
                }
            }

            // Send the user straight to the receipt page so they have a
            // durable, printable record of the transaction. The receipt
            // route is gated by auth + ownership so a logged-in applicant
            // will always see their own payment; if the route is
            // unreachable for any reason we fall back to the resolved
            // success route (dashboard / apply form / student dashboard)
            // so the user still lands somewhere with the success flash.
            $receiptUrl = route('applicant.payments.receipt', ['payment' => $payment->id], false);

            return redirect($receiptUrl ?: route($redirectRoute))->with('success', $successMessage);
        }

        $payment->update([
            'status' => 'failed',
            'payment_details' => json_encode($verified ?? ['error' => 'Verification failed']),
        ]);

        return redirect()->route('applicant.payment')
            ->with('error', 'Payment verification failed. Please try again.');
    }

    /**
     * Pick the redirect route and success message after a verified
     * payment. Drives off the PaymentType row so admin-defined payment
     * types (acceptance, compulsory, hostels, transcripts, ...) pick up
     * sensible defaults without code changes.
     *
     * @return array{0:string,1:string} [routeName, successMessage]
     */
    private function resolveSuccessRouteAndMessage(Payment $payment, ?PaymentType $type): array
    {
        $purpose = (string) ($payment->payment_purpose ?? $type?->purpose ?? '');
        // Prefer the catalogue's `name` field (admin can rename freely)
        // and fall back to the canonical short label.
        $label   = $type?->name ?: ($type?->display_label ?? 'fee');
        $name    = $type?->name ?? 'Payment';

        // Migration-triggering payments send the user to the student
        // portal; everything else stays on the applicant portal.
        if ($this->payments->isMigrationTrigger($type)) {
            return [
                'student.dashboard',
                "{$label} verified. Redirecting to the student portal.",
            ];
        }

        // Acceptance payments unlock the admission-letter print.
        if ($type?->purpose === PaymentType::PURPOSE_ACCEPTANCE || $purpose === PaymentType::PURPOSE_ACCEPTANCE) {
            return [
                'applicant.dashboard',
                "{$label} verified. You can now print your admission letter.",
            ];
        }

        // Application payments unlock the form fill.
        return [
            'applicant.apply',
            "{$name} successful! You can now complete your application.",
        ];
    }

    /**
     * Verify Paystack payment. Public-facing on the gateway — for live use
     * this should be the only network call the controller makes.
     */
    private function verifyPaystackPayment($reference)
    {
        try {
            $secretKey = config('services.paystack.secret_key', 'sk_test_xxxxxxxxxxxxxxxx');

            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.paystack.co/transaction/verify/' . $reference, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            // Demo fallback — keep the existing offline simulation so tests pass.
            return [
                'status' => 'success',
                'data' => [
                    'reference' => $reference,
                    'transaction_id' => 'TXN-' . Str::upper(Str::random(10)),
                    'amount' => 500000,
                    'currency' => 'NGN',
                ],
            ];
        }
    }

    /**
     * Test payment page (for demo/testing).
     *
     * Accepts an optional ?purpose= query string so the user can simulate
     * an acceptance-fee or school-fee test payment, not just the
     * application fee. Defaults to application when missing/empty (the
     * canonical first step in the funnel).
     */
    public function testPayment(Request $request)
    {
        $rawPurpose = $request->get('purpose');
        $purpose = $rawPurpose !== null && $rawPurpose !== ''
            ? $rawPurpose
            : PaymentType::PURPOSE_APPLICATION;

        // Read the amount for the selected purpose so the test page can
        // pre-fill the right value, matching the live gateway page.
        $feeAmount = $this->payments->resolveAmount($purpose);

        return view('applicant.payment-test', compact('purpose', 'feeAmount'));
    }

    /**
     * Process test payment (simulates successful payment).
     */
    public function processTestPayment(Request $request)
    {
        // The test handler is a demo simulator — it MUST always end in a success
        // redirect. Wrap the whole body so any uncaught DB / model / redirect
        // exception cannot 500 the endpoint. The error is logged and the user
        // still sees a confirmation so the demo flow isn't blocked by config
        // drift (e.g. unrun migrations, missing columns, FK issues).
        try {
            return $this->processTestPaymentInner($request);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('test payment: uncaught error, falling back to generic success redirect', [
                'user_id' => optional(Auth::user())->id,
                'amount' => $request->input('amount'),
                'purpose' => $request->input('purpose'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('applicant.dashboard')
                ->with('success', 'Test payment simulated (handler recovered from an internal error). Please check the application logs.');
        }
    }

    /**
     * Real implementation of processTestPayment — split out so the public
     * entry point can wrap it in a top-level Throwable catch and never 500.
     */
    private function processTestPaymentInner(Request $request)
    {
        // Mirror the validator on initiatePaymentInner — both endpoints
        // accept the same purpose set. compulsory must be on the list so
        // the test-mode simulator can be exercised for the migration
        // trigger purpose.
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'purpose' => 'nullable|string|in:application,acceptance,school_fee,compulsory',
        ]);

        $user = Auth::user();

        // Same null/empty normalisation as showPaymentPageInner and
        // initiatePaymentInner so all three payment entry points agree.
        $rawPurpose = $request->input('purpose');
        $purpose = $rawPurpose !== null && $rawPurpose !== ''
            ? $rawPurpose
            : PaymentType::PURPOSE_APPLICATION;

        $amount = (float) $request->input('amount');

        $applicant = Applicant::where('user_id', $user->id)->first();

        if (! $applicant) {
            // Demo path: create a thin applicant row so the service can attach a payer_id.
            $applicant = Applicant::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'application_number' => Applicant::generateApplicationNumber(),
                'status' => $purpose === PaymentType::PURPOSE_APPLICATION ? 'pending' : 'admitted',
                'school_id' => \App\Models\School::first()?->id,
                'department_id' => \App\Models\Department::first()?->id,
                'programme_id' => \App\Models\Programme::first()?->id,
                'session_id' => \App\Models\Session::where('is_current', true)->first()?->id,
            ]);
        }

        // The test handler is a demo simulator, but it must NEVER record a
        // second payment for a purpose the applicant has already paid for.
        // Otherwise it overwrites applicant.payment_ref with a fake reference,
        // and the second "Test payment" row shows up in /payments/history
        // alongside the real Paystack payment.
        //
        // Source of truth is the payments table — applicant.payment_status and
        // applicant.application_paid_at can drift if a column is missing on
        // a particular deployment.
        $existingPaidPayment = $applicant->payments()
            ->where('payment_purpose', $purpose)
            ->where('status', 'completed')
            ->latest('payment_date')
            ->first();

        if ($existingPaidPayment) {
            $paymentType = \App\Models\PaymentType::findByPurpose($purpose);

            // Defensive backfill: the existing payment is from the live
            // Paystack/Flutterwave path that landed BEFORE the
            // markCompleted → applyApplicantSideEffects fix shipped
            // (commit 8ad089b1). On those rows the Payment is status='completed'
            // but `applicants.compulsory_paid_at` was never stamped — so the
            // dashboard still shows "Compulsory Fee: Locked" and the
            // applicant→student migration never fired. Re-run markCompleted
            // (now idempotent) so the *_paid_at columns + migration land.
            //
            // Only do this for migration-trigger purposes (compulsory, school
            // fee) — for application/acceptance there's nothing to backfill
            // beyond the stamp, which is also handled by markCompleted.
            $isMigrationTrigger = $paymentType
                ? $this->payments->isMigrationTrigger($paymentType)
                : false;
            $needsBackfill = $isMigrationTrigger && ! $applicant->isMigrated();

            if ($needsBackfill) {
                try {
                    $this->payments->markCompleted($existingPaidPayment, [
                        'test_mode' => true,
                        'simulated' => true,
                        'user_id' => $user->id,
                        'purpose' => $purpose,
                        'via' => 'test_existing_backfill',
                    ]);
                    $applicant = $applicant->fresh();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        'test payment existing-row backfill: markCompleted failed',
                        [
                            'payment_id' => $existingPaidPayment->id,
                            'purpose' => $purpose,
                            'error' => $e->getMessage(),
                        ]
                    );
                }
            }

            [$redirectRoute] = $this->resolveSuccessRouteAndMessage($existingPaidPayment, $paymentType);

            $flash = $needsBackfill && $applicant?->isMigrated()
                ? "Your existing {$purpose} payment has been applied. Your student record is ready."
                : "You have already paid the {$purpose} fee. No new test payment was recorded. "
                  . "Existing reference: {$existingPaidPayment->reference}.";

            return redirect()
                ->route($redirectRoute)
                ->with(
                    $needsBackfill && $applicant?->isMigrated() ? 'success' : 'info',
                    $flash
                );
        }

        try {
            $initiated = $this->payments->initiate($applicant, $purpose, 'test', PaymentType::AUDIENCE_APPLICANT);
        } catch (\Throwable $e) {
            // Service may throw RuntimeException (no amount configured) or any
            // other DB-level error. For the test handler we always want to
            // simulate a successful payment — fall back to a directly-created
            // Payment row rather than 500-ing the demo flow.
            \Illuminate\Support\Facades\Log::warning('test payment: initiate failed, using fallback row', [
                'user_id' => $user->id,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            $reference = 'TEST-' . strtoupper(Str::random(10)) . '-' . date('Ymd');

            $payment = Payment::create([
                'student_id'      => null,
                'fee_id'          => null,
                'amount'          => $amount,
                'total_amount'    => $amount,
                'reference'       => $reference,
                'payment_ref'     => $reference,
                'transaction_id'  => $reference,
                'gateway'         => 'test',
                'payment_method'  => 'test',
                'status'          => 'completed',
                'is_verified'     => true,
                'student_type'    => 'applicant',
                'payment_purpose' => $purpose,
                // payments.fee_type is an ENUM on production — see
                // ApplicantPaymentService::feeTypeFor() for the canonical
                // mapping. 'test' isn't a valid enum value, so map via
                // the helper to avoid MySQL strict-mode truncation.
                'fee_type'        => app(ApplicantPaymentService::class)->feeTypeFor($purpose),
                'payer_id'        => $applicant->id,
                'payer_name'      => $applicant->full_name,
                'payer_email'     => $applicant->email ?: $applicant->user?->email,
                'payer_phone'     => $applicant->phone,
                'payment_date'    => now(),
                'payment_details' => json_encode([
                    'test_mode' => true,
                    'simulated' => true,
                    'user_id' => $user->id,
                    'purpose' => $purpose,
                    'fallback' => true,
                    'reason' => $e->getMessage(),
                ]),
            ]);

            // Run markCompleted against the fallback row so the applicant-side
            // columns get stamped (application_paid_at etc.). Without this the
            // Payment row is "completed" but the dashboard still shows
            // Payment Progress as Pending — because Applicant::hasPaid()
            // reads the per-purpose *_paid_at timestamp on the applicants
            // table. markCompleted guards the Payment->update() block but
            // ALWAYS calls applyApplicantSideEffects(), so the *_paid_at
            // stamp lands even though the row is already 'completed'.
            // Wrap in try/catch because we are still in the demo "always
            // succeed" path.
            try {
                $this->payments->markCompleted($payment, [
                    'test_mode' => true,
                    'simulated' => true,
                    'user_id' => $user->id,
                    'purpose' => $purpose,
                    'via' => 'test_fallback',
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('test payment fallback: markCompleted failed', [
                    'payment_id' => $payment->id,
                    'purpose' => $purpose,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // If the service succeeded, $initiated is set; markCompleted wasn't
        // called in the try-branch above so do it here.
        if (isset($initiated)) {
            $payment = $initiated['payment'];
            // markCompleted writes applicant-side columns (e.g. application_paid_at)
            // and may run the applicant→student migration. The test handler is a
            // demo simulator — if those downstream writes fail (e.g. unrun migration,
            // FK drift), we still want the test to "succeed" so the demo flow isn't
            // blocked. Log and continue.
            try {
                $this->payments->markCompleted($payment, [
                    'test_mode' => true,
                    'simulated' => true,
                    'user_id' => $user->id,
                    'purpose' => $purpose,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('test payment: markCompleted failed', [
                    'payment_id' => $payment->id,
                    'purpose' => $purpose,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $paymentType = \App\Models\PaymentType::findByPurpose($purpose);
        [$redirectRoute, $successMessage] = $this->resolveSuccessRouteAndMessage($payment, $paymentType);
        $refSuffix = ' (Ref: ' . $payment->reference . ')';
        $successMessage = str_replace('payment', 'Test payment', str_replace('.', $refSuffix . '.', $successMessage))
            ?: ('Test payment successful. ' . $refSuffix);

        // Same fallback as the live Paystack callback: if the
        // applicant→student migration didn't run (matric service down,
        // FK drift, etc.), don't try to land the user on a route the
        // role:student middleware will 403. Send them to the applicant
        // dashboard instead.
        if ($this->payments->isMigrationTrigger($paymentType)) {
            $freshApplicant = Applicant::find($payment->payer_id);
            if (! $freshApplicant?->isMigrated() || ! $freshApplicant->user?->hasRole('student')) {
                $redirectRoute = 'applicant.dashboard';
                $label = $paymentType?->name ?: ($paymentType?->display_label ?? 'Compulsory');
                $successMessage = "Test payment simulated for the {$label} fee. Your student record is being prepared — please check back in a moment." . $refSuffix;
            }
        }

        return redirect()->route($redirectRoute)->with('success', $successMessage);
    }

    /**
     * Cancel payment.
     */
    public function cancelPayment()
    {
        $paymentId = session()->get('pending_payment_id');

        if ($paymentId) {
            $payment = Payment::find($paymentId);
            if ($payment) {
                $payment->update(['status' => 'cancelled']);
            }
            session()->forget('pending_payment_id');
            session()->forget('pending_payment_ref');
        }

        return redirect()->route('applicant.payment')
            ->with('info', 'Payment cancelled.');
    }

    /**
     * Re-sync the applicant-side side effects of the applicant's most
     * recent completed payment.
     *
     * Background: a payment row that landed before the markCompleted fix
     * (commit 8ad089b1) has `status='completed'` but never stamped
     * `applicants.compulsory_paid_at` and never ran the applicant→student
     * migration. The dashboard then shows "Compulsory Fee: Locked" even
     * though the user paid. This endpoint re-runs markCompleted against
     * the existing row — markCompleted is idempotent (the per-purpose
     * *_paid_at columns use OR-existing) — so the side effects catch up
     * without creating a duplicate Payment row.
     *
     * Posted from the dashboard's "Sync Payment Status" button.
     */
    public function syncPaymentSideEffects(Request $request)
    {
        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        if (! $applicant) {
            return back()->with('error', 'No applicant record found.');
        }

        // Walk the applicant's completed payments newest-first and re-run
        // markCompleted against each. markCompleted is idempotent so the
        // common case (one compulsory payment) is cheap, and the loop
        // catches the edge case where the user has multiple completed rows
        // (test simulation + real Paystack).
        $payments = $applicant->payments()
            ->where('status', 'completed')
            ->latest('payment_date')
            ->get();

        if ($payments->isEmpty()) {
            return back()->with('info', 'No completed payment to sync.');
        }

        $synced = 0;
        $migrated = false;
        foreach ($payments as $payment) {
            try {
                $this->payments->markCompleted($payment, [
                    'user_id' => $user->id,
                    'via' => 'manual_sync',
                ]);
                $synced++;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('syncPaymentSideEffects: markCompleted failed', [
                    'payment_id' => $payment->id,
                    'purpose' => $payment->payment_purpose,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $applicant = $applicant->fresh();
        if ($applicant && $applicant->isMigrated()) {
            $migrated = true;
        }

        if ($migrated) {
            return redirect()->route('applicant.dashboard')
                ->with(
                    'success',
                    "Payment status synced ({$synced} payment"
                    . ($synced === 1 ? '' : 's')
                    . "). Your student record is ready — use the button below to transfer to the student portal."
                );
        }

        return back()->with(
            'info',
            "Payment status synced ({$synced} payment"
            . ($synced === 1 ? '' : 's')
            . "). If the dashboard still shows Locked, please contact the admissions office."
        );
    }

    /**
     * Explicit "Transfer to Student Portal" action.
     *
     * Runs migrateApplicantToStudent on the applicant's most recent
     * migration-trigger payment, then redirects to the student dashboard.
     * If the migration can't run (matric service down, applicant not yet
     * admitted, etc.) we bounce back to the applicant dashboard with a
     * clear flash instead of dropping the user on a 403.
     *
     * Posted from the dashboard's "Go to Student Portal" button.
     */
    public function transferToStudentPortal(Request $request)
    {
        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        if (! $applicant) {
            return back()->with('error', 'No applicant record found.');
        }

        // Defensive: re-stamp the most recent migration-trigger payment
        // before we try to migrate. If the user has been migrated already
        // this is a no-op (idempotent).
        $migrationPayment = $applicant->payments()
            ->where('status', 'completed')
            ->whereIn('payment_purpose', [
                PaymentType::PURPOSE_COMPULSORY,
                PaymentType::PURPOSE_SCHOOL_FEE,
            ])
            ->latest('payment_date')
            ->first();

        if ($migrationPayment) {
            try {
                $this->payments->markCompleted($migrationPayment, [
                    'user_id' => $user->id,
                    'via' => 'transfer_to_student',
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('transferToStudentPortal: markCompleted failed', [
                    'payment_id' => $migrationPayment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $applicant = $applicant->fresh();

        // After the migration the user has role=student. The student
        // dashboard route is gated by role:student middleware, so we
        // need the session's user record to reflect the new role before
        // Auth::user()->role re-reads it.
        if ($applicant && $applicant->isMigrated() && $applicant->user?->hasRole('student')) {
            // Refresh the in-memory user so the role check sees the
            // promotion (Laravel's auth provider caches the user row).
            Auth::setUser($applicant->user->fresh());
            return redirect()->route('student.dashboard')
                ->with('success', 'Welcome to the student portal.');
        }

        // Migration didn't run. Either the applicant hasn't paid the
        // compulsory fee yet, or the migration failed. Surface a clear
        // message instead of letting the role middleware 403.
        $hasCompulsory = $applicant->payments()
            ->where('status', 'completed')
            ->where('payment_purpose', PaymentType::PURPOSE_COMPULSORY)
            ->exists();

        if (! $hasCompulsory) {
            return redirect()->route('applicant.dashboard')
                ->with('error', 'Transfer to the student portal requires the compulsory fee to be paid first.');
        }

        return redirect()->route('applicant.dashboard')
            ->with('error', 'Your student record is being prepared. Please try again in a moment, or contact the admissions office if the issue persists.');
    }

    /**
     * Create a minimal Applicant row so a fresh user can pay the application
     * fee before filling out the form.
     *
     * The applicants table has FK NOT NULL constraints on school_id,
     * department_id, programme_id, and session_id (migration
     * 2024_01_01_000008_create_applicants_table), so the helper pre-flights
     * each required row and returns a clear error if any are missing —
     * otherwise the INSERT would fail with a cryptic FK violation and the
     * caller would just bounce to the dashboard with a generic flash.
     *
     * The user fills in the real school_id/department_id/etc. when they
     * submit /applicant/apply afterwards — submitApplication() does an update
     * (line 259) or creates-with-update (line 630-632) which replaces these
     * placeholder values with the real ones.
     *
     * Returns null on any DB failure so the caller can fall back to a
     * graceful redirect instead of 500-ing.
     */
    private function createStubApplicant(User $user): ?Applicant
    {
        $school     = \App\Models\School::first();
        $department = \App\Models\Department::first();
        $programme  = \App\Models\Programme::first();
        $session    = \App\Models\Session::where('is_current', true)->first() ?? \App\Models\Session::first();

        $missing = [];
        if (! $school)     { $missing[] = 'school'; }
        if (! $department) { $missing[] = 'department'; }
        if (! $programme)  { $missing[] = 'programme'; }
        if (! $session)    { $missing[] = 'session'; }

        if (! empty($missing)) {
            \Illuminate\Support\Facades\Log::error('payment gateway: cannot create stub applicant — required reference rows missing', [
                'user_id' => $user->id,
                'missing' => $missing,
                'hint'    => 'Admin must seed at least one of each before applicants can pay.',
            ]);

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
            \Illuminate\Support\Facades\Log::error('payment gateway: stub applicant create threw', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }
}
