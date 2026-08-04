<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ExternalPayment;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\Student;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Central service for the applicant payment pipeline.
 *
 * Three fees, in order, all routed through this service:
 *
 *   1. application → unlocks the application form
 *   2. acceptance  → unlocks admission letter printing (requires status=admitted)
 *   3. compulsory  → migrates the applicant to the student portal
 *
 * Every fee takes the same path:
 *   resolveAmount() → canPay() → initiate() → markCompleted()
 *
 * Amount resolution: PaymentType.amount is the default; if a SystemSetting
 * override exists (admission_application_fee_amount, etc.) it wins.
 *
 * All writes to applicants.payment_* columns and the new *_paid_at
 * timestamps go through this service — no other code should touch them.
 */
class ApplicantPaymentService
{
    /** Live-override keys in system_settings. Public so admin screens can read it. */
    public const OVERRIDE_KEYS_PUBLIC = [
        PaymentType::PURPOSE_APPLICATION => 'admission_application_fee_amount',
        PaymentType::PURPOSE_ACCEPTANCE  => 'admission_accept_fee_amount',
        PaymentType::PURPOSE_SCHOOL_FEE => 'admission_school_fee_amount',
    ];

    /** PaymentType.code for each purpose (the seeder uses these). */
    private const PURPOSE_CODES = [
        PaymentType::PURPOSE_APPLICATION => 'APP_FORM',
        PaymentType::PURPOSE_ACCEPTANCE  => 'ACCEPT_FEE',
        PaymentType::PURPOSE_SCHOOL_FEE => 'SCHOOL_FEE',
    ];

    /* ------------------------------------------------------------------
     | Resolution
     * ------------------------------------------------------------------*/

    /**
     * Resolve the PaymentType row for a purpose, falling back to matching on
     * the purpose column if the canonical code is missing.
     */
    public function resolvePaymentType(string $purpose): ?PaymentType
    {
        $code = self::PURPOSE_CODES[$purpose] ?? null;

        if ($code) {
            $byCode = PaymentType::where('code', $code)->first();
            if ($byCode) {
                return $byCode;
            }
        }

        return PaymentType::where('purpose', $purpose)
            ->where('is_active', true)
            ->orderBy('priority')
            ->first();
    }

    /**
     * Resolve the amount the applicant should pay for a purpose.
     *
     *   PaymentType.amount  (default; managed in /admin/payment-types)
     *     ↓ overridden by
     *   SystemSetting override (live promo / per-cycle pricing)
     *
     * Returns 0.0 if nothing is configured; caller decides whether that is
     * an error.
     */
    public function resolveAmount(string $purpose): float
    {
        $overrideKey = self::OVERRIDE_KEYS_PUBLIC[$purpose] ?? null;
        if ($overrideKey) {
            $override = SystemSetting::get($overrideKey);
            if ($override !== null && $override !== '' && is_numeric($override)) {
                return (float) $override;
            }
        }

        $type = $this->resolvePaymentType($purpose);

        return (float) ($type?->amount ?? 0);
    }

    /* ------------------------------------------------------------------
     | Gating
     * ------------------------------------------------------------------*/

    /**
     * Whether the applicant is allowed to pay the given purpose RIGHT NOW.
     *
     * Returns null when allowed, or a string explaining why not.
     * Caller turns the string into a 403 / flash message.
     */
    public function canPay(Applicant $applicant, string $purpose): ?string
    {
        return match ($purpose) {
            PaymentType::PURPOSE_APPLICATION => $this->canPayApplication($applicant),
            PaymentType::PURPOSE_ACCEPTANCE  => $this->canPayAcceptance($applicant),
            PaymentType::PURPOSE_SCHOOL_FEE => $this->canPayCompulsory($applicant),
            default => 'Unknown payment purpose.',
        };
    }

    private function canPayApplication(Applicant $applicant): ?string
    {
        if ($applicant->hasPaid(PaymentType::PURPOSE_APPLICATION)) {
            return 'You have already paid the application fee.';
        }

        if (! SystemSetting::isOpen(SystemSetting::ADMISSION_FORM_OPEN)) {
            return 'The admission form is currently closed.';
        }

        return null;
    }

    private function canPayAcceptance(Applicant $applicant): ?string
    {
        if (! $applicant->hasPaid(PaymentType::PURPOSE_APPLICATION)) {
            return 'Pay the application fee before paying the acceptance fee.';
        }

        if ($applicant->hasPaid(PaymentType::PURPOSE_ACCEPTANCE)) {
            return 'You have already paid the acceptance fee.';
        }

        if ($applicant->status !== 'admitted') {
            return 'You must be admitted before paying the acceptance fee.';
        }

        return null;
    }

    private function canPayCompulsory(Applicant $applicant): ?string
    {
        if (! $applicant->hasPaid(PaymentType::PURPOSE_APPLICATION)) {
            return 'Pay the application fee first.';
        }

        if ($applicant->status !== 'admitted') {
            return 'You must be admitted before paying the compulsory fee.';
        }

        if (! $applicant->hasPaid(PaymentType::PURPOSE_ACCEPTANCE)) {
            return 'Pay the acceptance fee before paying the compulsory fee.';
        }

        if ($applicant->hasPaid(PaymentType::PURPOSE_SCHOOL_FEE)) {
            return 'You have already paid the compulsory fee.';
        }

        if ($applicant->isMigrated()) {
            return 'You are already a student.';
        }

        return null;
    }

    /* ------------------------------------------------------------------
     | Initiation
     * ------------------------------------------------------------------*/

    /**
     * Create a pending Payment row for the given purpose.
     *
     * Caller is responsible for handing the reference to a gateway
     * (Paystack/Flutterwave/Xpress) and then calling markCompleted()
     * when the gateway returns success.
     *
     * @return array{payment: Payment, reference: string, amount: float}
     */
    public function initiate(Applicant $applicant, string $purpose, string $channel = 'paystack'): array
    {
        $type = $this->resolvePaymentType($purpose);
        $amount = $this->resolveAmount($purpose);

        if ($amount <= 0) {
            throw new \RuntimeException("No amount configured for purpose [$purpose].");
        }

        $reference = $this->generateReference($purpose, $channel);

        $payment = Payment::create([
            'student_id'      => null, // becomes populated on migration
            'fee_id'          => $type?->id,
            'amount'          => $amount,
            'total_amount'    => $amount,
            'reference'       => $reference,
            'payment_ref'     => $reference,
            'transaction_id'  => $reference,
            'gateway'         => $channel,
            'payment_method'  => $channel,
            'status'          => 'pending',
            'student_type'    => 'applicant',
            'payment_purpose' => $purpose,
            // Use the short code (APP_FORM, ACCEPT_FEE, SCHOOL_FEE) instead of
            // the human name (e.g. "Application Form Fee"). The code always
            // fits the payments.fee_type column, the name may not if the
            // column was redefined shorter on a given deployment.
            'fee_type'        => $type?->code ?? 'other',
            'payer_id'        => $applicant->id,
            'payer_name'      => $applicant->full_name,
            'payer_email'     => $applicant->email ?: $applicant->user?->email,
            'payer_phone'     => $applicant->phone,
            'payment_date'    => null,
        ]);

        return [
            'payment' => $payment,
            'reference' => $reference,
            'amount' => $amount,
        ];
    }

    /**
     * Record a manual (bank transfer / external) payment that has been
     * validated against the external_payments table.
     */
    public function recordManual(Applicant $applicant, ExternalPayment $external, string $purpose): Payment
    {
        return DB::transaction(function () use ($applicant, $external, $purpose) {
            $type = $this->resolvePaymentType($purpose);

            $payment = Payment::create([
                'student_id'      => null,
                'fee_id'          => $type?->id,
                'amount'          => $external->amount,
                'total_amount'    => $external->amount,
                'reference'       => $external->transaction_id,
                'payment_ref'     => $external->transaction_id,
                'transaction_id'  => $external->transaction_id,
                'gateway'         => $external->payment_channel ?: 'bank_transfer',
                'payment_method'  => 'bank_transfer',
                'status'          => 'completed',
                'is_verified'     => true,
                'student_type'    => 'applicant',
                'payment_purpose' => $purpose,
                // Short code — same reasoning as in initiate().
                'fee_type'        => $type?->code ?? 'other',
                'payer_id'        => $applicant->id,
                'payer_name'      => $external->applicant_name ?: $applicant->full_name,
                'payer_email'     => $external->email ?: $applicant->email,
                'payer_phone'     => $applicant->phone,
                'payment_date'    => $external->payment_date ?: now(),
                'payment_details' => json_encode([
                    'source' => 'external_payments',
                    'external_id' => $external->id,
                ]),
            ]);

            $this->applyApplicantSideEffects($applicant, $payment, $purpose);

            return $payment;
        });
    }

    /* ------------------------------------------------------------------
     | Completion (called by gateway callback OR test handler)
     * ------------------------------------------------------------------*/

    /**
     * Mark a pending payment as completed and stamp the right applicant
     * timestamp. If the purpose is `school_fee`, also runs the
     * applicant → student migration.
     *
     * @param  array<string, mixed>  $gatewayResponse  raw Paystack/Flutterwave data
     */
    public function markCompleted(Payment $payment, array $gatewayResponse = []): Payment
    {
        if ($payment->status === 'completed') {
            return $payment; // idempotent
        }

        $payment->update([
            'status' => 'completed',
            'is_verified' => true,
            'payment_details' => json_encode($gatewayResponse),
            'payment_date' => $payment->payment_date ?: now(),
            'transaction_id' => $gatewayResponse['data']['transaction_id']
                ?? $gatewayResponse['transaction_id']
                ?? $payment->transaction_id,
        ]);

        $applicant = Applicant::find($payment->payer_id);
        if (! $applicant) {
            Log::warning("Payment {$payment->id} completed but payer_id {$payment->payer_id} has no applicant row.");

            return $payment;
        }

        $this->applyApplicantSideEffects($applicant, $payment, $payment->payment_purpose);

        return $payment->fresh();
    }

    /**
     * Write all of the applicant-side effects of a completed payment:
     *   - the *_paid_at timestamp for the purpose
     *   - legacy payment_status / payment_ref / payment_amount columns
     *     (kept for back-compat with existing views)
     *   - student migration when purpose == school_fee
     */
    private function applyApplicantSideEffects(Applicant $applicant, Payment $payment, string $purpose): void
    {
        DB::transaction(function () use ($applicant, $payment, $purpose) {
            $update = [
                'payment_status' => 'completed',
                'payment_ref' => $payment->reference,
                'payment_transaction_id' => $payment->transaction_id,
                'payment_amount' => $payment->amount,
                'payment_date' => $payment->payment_date ?: now(),
            ];

            $stamp = $payment->payment_date ?: now();

            switch ($purpose) {
                case PaymentType::PURPOSE_APPLICATION:
                    $update['application_paid_at'] = $applicant->application_paid_at ?: $stamp;
                    break;

                case PaymentType::PURPOSE_ACCEPTANCE:
                    $update['acceptance_paid_at'] = $applicant->acceptance_paid_at ?: $stamp;
                    break;

                case PaymentType::PURPOSE_SCHOOL_FEE:
                    $update['compulsory_paid_at'] = $applicant->compulsory_paid_at ?: $stamp;
                    break;
            }

            $applicant->update($update);

            // Compulsory triggers the applicant → student migration.
            if ($purpose === PaymentType::PURPOSE_SCHOOL_FEE) {
                $this->migrateApplicantToStudent($applicant);
            }
        });
    }

    /**
     * Idempotent applicant → student migration.
     *
     * Reused by markCompleted() (compulsory fee path) and available as a
     * public method so the Registrar UI can re-trigger on demand if a
     * migration partially failed.
     */
    public function migrateApplicantToStudent(Applicant $applicant): ?Student
    {
        if (! $applicant || $applicant->status !== 'admitted') {
            return null;
        }

        $existing = Student::where('user_id', $applicant->user_id)->first();
        if ($existing) {
            if (! $applicant->student_id) {
                $applicant->update([
                    'student_id' => $existing->id,
                    'matric_number' => $existing->matric_number,
                    'migrated_to_student_at' => $applicant->migrated_to_student_at ?: now(),
                ]);
            }

            return $existing;
        }

        $matricNumber = MatricNumberService::generate($applicant);
        if (! $matricNumber) {
            Log::error("Failed to generate matric number for applicant {$applicant->id}");

            return null;
        }

        return DB::transaction(function () use ($applicant, $matricNumber) {
            $student = Student::create([
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

            $studentRole = \App\Models\Role::where('slug', 'student')->first();
            if ($studentRole) {
                $applicant->user?->update([
                    'role_id'  => $studentRole->id,
                    'is_active' => true,
                ]);
            }

            $applicant->update([
                'student_id' => $student->id,
                'matric_number' => $matricNumber,
                'status' => 'admitted',
                'migrated_to_student_at' => now(),
            ]);

            return $student;
        });
    }

    /* ------------------------------------------------------------------
     | Helpers
     * ------------------------------------------------------------------*/

    /**
     * Return a unique, human-readable reference keyed on purpose.
     */
    public function generateReference(string $purpose, string $channel = 'paystack'): string
    {
        $short = strtoupper(substr($purpose, 0, 3));

        return $short . '-' . strtoupper(Str::random(10)) . '-' . now()->format('Ymd');
    }
}
