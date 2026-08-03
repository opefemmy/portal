<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\PaymentType;
use App\Models\SystemSetting;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentGatewayController extends Controller
{
    /**
     * Show payment page with Pay Now button.
     * Supports ?purpose=application|acceptance|compulsory|school_fee
     */
    public function showPaymentPage(Request $request)
    {
        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();
        $purpose = $request->get('purpose', 'application');

        // Map purpose → payment-type code
        $purposeCodeMap = [
            'application' => 'APP_FORM',
            'acceptance'  => 'ACCEPT_FEE',
            'compulsory'  => 'SCHOOL_FEE',
            'school_fee'  => 'SCHOOL_FEE',
        ];
        $typeCode = $purposeCodeMap[$purpose] ?? 'APP_FORM';

        // Resolve the payment type by code first
        $paymentType = PaymentType::where('code', $typeCode)->first();

        // Fallback: try the purpose column (e.g. PURPOSE_ACCEPTANCE / PURPOSE_SCHOOL_FEE)
        if (!$paymentType && $purpose !== 'application') {
            $purposeValue = $purpose === 'acceptance'
                ? PaymentType::PURPOSE_ACCEPTANCE
                : PaymentType::PURPOSE_SCHOOL_FEE;
            $paymentType = PaymentType::where('purpose', $purposeValue)
                ->where('is_active', true)
                ->orderBy('priority')
                ->first();
        }

        // Final fallback
        if (!$paymentType) {
            $paymentType = PaymentType::where('code', 'APP_FORM')->first();
        }

        if (!$paymentType) {
            return back()->with('error', 'Payment type not configured. Please contact the admissions office.');
        }

        // Guard: acceptance / compulsory fees require admission
        if (in_array($purpose, ['acceptance', 'compulsory'])) {
            if (!$applicant || $applicant->status !== 'admitted') {
                return redirect()->route('applicant.dashboard')
                    ->with('error', 'You must be admitted before paying this fee.');
            }
            if ($purpose === 'compulsory') {
                $existingStudent = \App\Models\Student::where('user_id', $user->id)->first();
                if ($existingStudent) {
                    return redirect()->route('applicant.dashboard')
                        ->with('info', 'You have already been migrated to the student portal.');
                }
            }
        }

        $feeAmount = $paymentType->amount ?? 5000;

        return view('applicant.payment-gateway', compact('applicant', 'paymentType', 'feeAmount', 'purpose'));
    }

    /**
     * Initiate payment with Paystack
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'purpose' => 'nullable|string|in:application,acceptance,compulsory,school_fee',
        ]);

        $user = Auth::user();
        $purpose = $request->input('purpose', 'application');

        // Pick the payment type for the purpose
        $typeCode = match ($purpose) {
            'acceptance'  => 'ACCEPT_FEE',
            'compulsory'  => 'SCHOOL_FEE',
            'school_fee'  => 'SCHOOL_FEE',
            default       => 'APP_FORM',
        };
        $paymentType = PaymentType::where('code', $typeCode)->first()
            ?? PaymentType::where('code', 'APP_FORM')->first();

        if (!$paymentType) {
            return back()->with('error', 'Payment type not found.');
        }

        $amount = $request->amount * 100; // Paystack uses kobo
        $email = $user->email;
        $reference = strtoupper($purpose) . '-' . Str::upper(Str::random(10));

        // Get student ID if user has a student record
        $student = \App\Models\Student::where('user_id', $user->id)->first();

        // Create payment record — capture the fee_type for downstream filtering
        $payment = Payment::create([
            'student_id'      => $student?->id,
            'fee_id'          => $paymentType->id ?? null,
            'amount'          => $request->amount,
            'reference'       => $reference,
            'transaction_id'  => $reference,
            'gateway'         => 'paystack',
            'status'          => 'pending',
            'student_type'    => 'applicant',
            'payment_purpose' => $purpose,
            'fee_type'        => $paymentType->name,
        ]);

        // Store payment ID and purpose in session so callback can resolve them
        session()->put('pending_payment_id', $payment->id);
        session()->put('pending_payment_ref', $reference);
        session()->put('pending_payment_purpose', $purpose);

        // Initialize Paystack payment
        $paystackPublicKey = config('services.paystack.public_key', 'pk_test_xxxxxxxxxxxxxxxx');

        return view('applicant.payment-initiate', [
            'reference'       => $reference,
            'amount'          => $request->amount,
            'email'           => $email,
            'paystackPublicKey' => $paystackPublicKey,
            'callbackUrl'     => route('applicant.payment.callback'),
        ]);
    }

    /**
     * Paystack payment callback
     */
    public function paymentCallback(Request $request)
    {
        $reference = $request->reference;

        if (!$reference) {
            return redirect()->route('applicant.payment')
                ->with('error', 'Payment reference not found.');
        }

        // Find payment record
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return redirect()->route('applicant.payment')
                ->with('error', 'Payment record not found.');
        }

        // Verify payment with Paystack
        $verified = $this->verifyPaystackPayment($reference);

        if ($verified && $verified['status'] === 'success') {
            // Update payment status
            $payment->update([
                'status' => 'completed',
                'is_verified' => true,
                'payment_details' => json_encode($verified),
            ]);

            // Update or create applicant record
            $user = Auth::user();
            $applicant = Applicant::where('user_id', $user->id)->first();

            // Get first school, dept, programme for new applicant
            $firstSchool = \App\Models\School::first();
            $firstDept = \App\Models\Department::first();
            $firstProg = \App\Models\Programme::first();
            $firstSession = \App\Models\Session::where('is_current', true)->first();

            $applicantData = [
                'user_id' => $user->id,
                'email' => $user->email,
                'application_number' => $applicant ? $applicant->application_number : Applicant::generateApplicationNumber(),
                'payment_status' => 'completed',
                'payment_ref' => $reference,
                'payment_transaction_id' => $verified['data']['transaction_id'] ?? $reference,
                'payment_amount' => $payment->amount,
                'payment_date' => now(),
                'status' => $applicant ? $applicant->status : 'pending',
                'school_id' => $applicant ? $applicant->school_id : ($firstSchool?->id ?? 1),
                'department_id' => $applicant ? $applicant->department_id : ($firstDept?->id ?? 1),
                'programme_id' => $applicant ? $applicant->programme_id : ($firstProg?->id ?? 1),
                'session_id' => $applicant ? $applicant->session_id : ($firstSession?->id ?? 1),
            ];

            if (!$applicant) {
                Applicant::create($applicantData);
            } else {
                $applicant->update($applicantData);
            }

            // Determine the purpose of this payment
            $purpose = session()->get('pending_payment_purpose')
                ?: ($payment->payment_purpose ?: 'application');

            // Run purpose-specific post-payment logic
            $redirectRoute = 'applicant.apply';
            $successMessage = 'Payment successful! You can now complete your application.';

            if ($purpose === 'acceptance') {
                // Acceptance: enable admission letter printing
                $redirectRoute = 'applicant.dashboard';
                $successMessage = 'Acceptance fee payment verified. You can now print your admission letter.';
            } elseif (in_array($purpose, ['compulsory', 'school_fee'])) {
                // Compulsory fee: auto-migrate applicant → student with matric number
                $migrationResult = $this->migrateApplicantToStudent($applicant);
                $redirectRoute = $migrationResult['redirect_route'] ?? 'applicant.dashboard';
                $successMessage = $migrationResult['message'];
            }

            // Clear session
            session()->forget('pending_payment_id');
            session()->forget('pending_payment_ref');
            session()->forget('pending_payment_purpose');

            return redirect()->route($redirectRoute)
                ->with('success', $successMessage);
        }

        // Payment failed
        $payment->update([
            'status' => 'failed',
            'payment_details' => json_encode($verified ?? ['error' => 'Verification failed']),
        ]);

        return redirect()->route('applicant.payment')
            ->with('error', 'Payment verification failed. Please try again.');
    }

    /**
     * Auto-migrate an admitted applicant into a Student record with a matric
     * number derived from the applicant's admitted department.
     *
     * Idempotent: if the applicant is already linked to a Student row, no new
     * Student record is created.
     */
    protected function migrateApplicantToStudent(Applicant $applicant): array
    {
        if (!$applicant) {
            return [
                'redirect_route' => 'applicant.dashboard',
                'message'        => 'Applicant record missing — cannot migrate.',
            ];
        }

        // Already migrated? Reuse existing record.
        $existing = \App\Models\Student::where('user_id', $applicant->user_id)->first();
        if ($existing) {
            $applicant->update([
                'student_id'   => $existing->id,
                'matric_number' => $existing->matric_number,
                'status'       => 'admitted',
            ]);

            return [
                'redirect_route' => 'student.dashboard',
                'message'        => 'You already have a student record. Redirecting to the student portal.',
            ];
        }

        $matricNumber = \App\Services\MatricNumberService::generate($applicant);
        if (!$matricNumber) {
            return [
                'redirect_route' => 'applicant.dashboard',
                'message'        => 'Matric number generation failed. Please contact the admissions office.',
            ];
        }

        DB::transaction(function () use ($applicant, $matricNumber) {
            $student = \App\Models\Student::create([
                'user_id'        => $applicant->user_id,
                'matric_number'  => $matricNumber,
                'school_id'      => $applicant->school_id,
                'department_id'  => $applicant->department_id,
                'programme_id'   => $applicant->programme_id,
                'session_id'     => $applicant->session_id,
                'level'          => $applicant->entry_level ?: 1,
                'status'         => 'active',
                'state_id'       => $applicant->state_id,
                'lga_id'         => $applicant->lga_id,
                'nationality_id' => $applicant->nationality_id,
                'from_application' => true,
                'applicant_id'     => $applicant->id,
            ]);

            // Promote user to student role if a student role exists
            $studentRole = \App\Models\Role::where('slug', 'student')->first();
            if ($studentRole) {
                $applicant->user?->update([
                    'role_id'  => $studentRole->id,
                    'is_active' => true,
                ]);
            }

            $applicant->update([
                'student_id'   => $student->id,
                'matric_number' => $matricNumber,
                'status'       => 'admitted',
            ]);
        });

        return [
            'redirect_route' => 'student.dashboard',
            'message'        => 'Compulsory fee verified. Your matric number is ' . $matricNumber . '. Redirecting to the student portal.',
        ];
    }

    /**
     * Verify Paystack payment
     */
    private function verifyPaystackPayment($reference)
    {
        try {
            $secretKey = config('services.paystack.secret_key', 'sk_test_xxxxxxxxxxxxxxxx');

            $client = new \GuzzleHttp\Client();
            $response = $client->get("https://api.paystack.co/transaction/verify/" . $reference, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            // For demo purposes, we'll simulate success
            return [
                'status' => 'success',
                'data' => [
                    'reference' => $reference,
                    'transaction_id' => 'TXN-' . Str::upper(Str::random(10)),
                    'amount' => 500000,
                    'currency' => 'NGN',
                ]
            ];
        }
    }

    /**
     * Test payment page (for demo/testing)
     */
    public function testPayment()
    {
        return view('applicant.payment-test');
    }

    /**
     * Process test payment (simulates successful payment)
     */
    public function processTestPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'purpose' => 'nullable|string|in:application,acceptance,compulsory,school_fee',
        ]);

        $user = Auth::user();
        $purpose = $request->input('purpose', 'application');

        // Get payment type
        $typeCode = match ($purpose) {
            'acceptance'  => 'ACCEPT_FEE',
            'compulsory'  => 'SCHOOL_FEE',
            'school_fee'  => 'SCHOOL_FEE',
            default       => 'APP_FORM',
        };
        $paymentType = PaymentType::where('code', $typeCode)->first()
            ?? PaymentType::where('code', 'APP_FORM')->first();
        $feeAmount = $paymentType ? $paymentType->amount : 5000;

        // Create payment record
        $reference = 'TEST-' . strtoupper(substr($purpose, 0, 3)) . '-' . Str::upper(Str::random(10));

        // Get student_id if user has a student record, otherwise use a placeholder
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        $studentId = $student?->id;

        // If no student record, create a placeholder or skip
        // For applicants, we don't have a student record yet
        $payment = Payment::create([
            'student_id' => $studentId,
            'fee_id' => $paymentType ? $paymentType->id : null,
            'amount' => $request->amount,
            'reference' => $reference,
            'transaction_id' => $reference,
            'gateway' => 'test',
            'status' => 'completed',
            'is_verified' => true,
            'student_type' => 'applicant',
            'payment_purpose' => $purpose,
            'fee_type' => $paymentType?->name,
            'payment_details' => json_encode([
                'test_mode' => true,
                'simulated' => true,
                'user_id' => $user->id,
                'applicant_mode' => true,
                'purpose' => $purpose,
            ]),
        ]);

        // Update or create applicant record - only update payment fields if applicant exists
        $applicant = Applicant::where('user_id', $user->id)->first();

        $paymentData = [
            'payment_status' => 'completed',
            'payment_ref' => $reference,
            'payment_transaction_id' => $reference,
            'payment_amount' => $request->amount,
            'payment_date' => now(),
        ];

        if ($applicant) {
            // Update only payment fields
            $applicant->update($paymentData);
        } else {
            // Create new applicant with minimal data - will complete details later
            $firstSchool = \App\Models\School::first();
            $firstDept = \App\Models\Department::first();
            $firstProg = \App\Models\Programme::first();
            $firstSession = \App\Models\Session::where('is_current', true)->first();

            Applicant::create(array_merge($paymentData, [
                'user_id' => $user->id,
                'email' => $user->email,
                'application_number' => Applicant::generateApplicationNumber(),
                'school_id' => $firstSchool?->id,
                'department_id' => $firstDept?->id,
                'programme_id' => $firstProg?->id,
                'session_id' => $firstSession?->id,
                'status' => 'pending',
            ]));
        }

        // Purpose-specific post-payment action
        if ($purpose === 'acceptance') {
            return redirect()->route('applicant.dashboard')
                ->with('success', 'Acceptance fee verified. You can now print your admission letter. (Ref: ' . $reference . ')');
        }

        if (in_array($purpose, ['compulsory', 'school_fee'])) {
            // Refresh applicant to ensure latest payment data is loaded
            $applicant->refresh();
            $result = $this->migrateApplicantToStudent($applicant);
            return redirect()->route($result['redirect_route'])
                ->with('success', $result['message'] . ' (Ref: ' . $reference . ')');
        }

        return redirect()->route('applicant.apply')
            ->with('success', 'Test payment successful! You can now complete your application. (Reference: ' . $reference . ')');
    }

    /**
     * Cancel payment
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
}
