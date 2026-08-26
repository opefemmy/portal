<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\ExternalPayment;
use App\Models\LocalGovernment;
use App\Models\Nationality;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Models\State;
use App\Models\Student;
use App\Models\SystemSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        PaymentType::PURPOSE_COMPULSORY => 'COMP_FEE',
    ];

    /**
     * Map an applicant payment purpose to the value that must be written
     * to payments.fee_type on production.
     *
     * payments.fee_type is an ENUM column on production
     * ('application','acceptance','school_fees','hostel','library','other')
     * — see UpdateManagerService's repair path and the live SHOW CREATE
     * TABLE output. The PaymentType.code values (APP_FORM / ACCEPT_FEE /
     * SCHOOL_FEE) are short identifiers for joining against
     * payment_types, NOT valid enum values for the payments.fee_type
     * column. Inserting those codes raises "Data truncated for column
     * 'fee_type'" on MySQL strict mode.
     *
     * Anything not in the canonical three purposes maps to 'other' so
     * the call never has to worry about strict-mode truncation.
     */
    public const FEE_TYPE_FOR_PURPOSE = [
        PaymentType::PURPOSE_APPLICATION => 'application',
        PaymentType::PURPOSE_ACCEPTANCE  => 'acceptance',
        PaymentType::PURPOSE_SCHOOL_FEE => 'school_fees',
    ];

    /**
     * Resolve the enum-safe value to write into payments.fee_type for a
     * given purpose. Public so other call sites (test handler fallback,
     * registrar flow, legacy rebuild) can centralise the mapping here
     * instead of duplicating magic strings.
     */
    public function feeTypeFor(?string $purpose): string
    {
        if ($purpose !== null && isset(self::FEE_TYPE_FOR_PURPOSE[$purpose])) {
            return self::FEE_TYPE_FOR_PURPOSE[$purpose];
        }

        return 'other';
    }

    /* ------------------------------------------------------------------
     | Resolution
     * ------------------------------------------------------------------*/

    /**
     * Resolve the PaymentType row for a purpose, falling back to matching on
     * the purpose column if the canonical code is missing.
     *
     * When $audience is supplied, the result is constrained to rows whose
     * audience is 'both' or matches $audience. Pass AUDIENCE_APPLICANT from
     * the applicant flow so an admin can't serve an applicant-only type to
     * a student, and vice versa.
     *
     * The audience filter is skipped when payment_types.audience does not
     * exist on this DB (i.e. the 2026_08_04 migration is unrun) so the
     * payment flow keeps working on legacy deployments. Once the migration
     * is applied, the filter kicks in automatically — no code change.
     */
    public function resolvePaymentType(string $purpose, ?string $audience = null): ?PaymentType
    {
        $code = self::PURPOSE_CODES[$purpose] ?? null;
        $audienceColumnExists = \Illuminate\Support\Facades\Schema::hasColumn('payment_types', 'audience');

        if ($code) {
            $query = PaymentType::where('code', $code);
            if ($audience && $audienceColumnExists) {
                $query->where(function ($q) use ($audience) {
                    $q->where('audience', PaymentType::AUDIENCE_BOTH)
                        ->orWhere('audience', $audience);
                });
            }
            $byCode = $query->first();
            if ($byCode) {
                return $byCode;
            }
        }

        $fallback = PaymentType::where('purpose', $purpose)
            ->where('is_active', true)
            ->orderBy('priority');

        if ($audience && $audienceColumnExists) {
            $fallback->where(function ($q) use ($audience) {
                $q->where('audience', PaymentType::AUDIENCE_BOTH)
                    ->orWhere('audience', $audience);
            });
        }

        return $fallback->first();
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

        // Locked to applicant audience. Amount resolution runs on every
        // applicant-side payment page load, so we must not pick a
        // student-only row even if its purpose matches.
        $type = $this->resolvePaymentType($purpose, PaymentType::AUDIENCE_APPLICANT);

        return (float) ($type?->amount ?? 0);
    }

    /* ------------------------------------------------------------------
     | Catalogue — what the admin has configured
     * ------------------------------------------------------------------*/

    /**
     * Every active PaymentType visible to the applicant portal,
     * ordered by admin priority then by name. Audience filtering is
     * applied so the applicant never sees student-only rows.
     *
     * This is the source of truth for "what to list on the applicant
     * dashboard" — no controller should hand-roll a where query.
     *
     * @return Collection<int, PaymentType>
     */
    public function getApplicantPaymentTypes(): Collection
    {
        return PaymentType::query()
            ->active()
            ->forAudience(PaymentType::AUDIENCE_APPLICANT)
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
    }

    /**
     * Static helper for callers that don't have DI handy (Eloquent
     * models, Blade views). Mirrors getApplicantPaymentTypes().
     */
    public static function getApplicantPaymentTypesStatic(): Collection
    {
        return PaymentType::query()
            ->active()
            ->forAudience(PaymentType::AUDIENCE_APPLICANT)
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
    }

    /**
     * Every active PaymentType visible to the student portal. Same
     * shape as getApplicantPaymentTypes() but audience=student.
     *
     * @return Collection<int, PaymentType>
     */
    public function getStudentPaymentTypes(): Collection
    {
        return PaymentType::query()
            ->active()
            ->forAudience(PaymentType::AUDIENCE_STUDENT)
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
    }

    /**
     * Static mirror of getStudentPaymentTypes() for callers without DI.
     */
    public static function getStudentPaymentTypesStatic(): Collection
    {
        return PaymentType::query()
            ->active()
            ->forAudience(PaymentType::AUDIENCE_STUDENT)
            ->orderBy('priority')
            ->orderBy('name')
            ->get();
    }

    /**
     * Find an active PaymentType by code. Returns null when not found.
     * Backward-compat alias for code that used to query
     * `PaymentType::where('code', 'X')->first()`.
     */
    public function findPaymentType(string $code): ?PaymentType
    {
        return PaymentType::findByCode($code);
    }

    /**
     * Friendly purpose label for a PaymentType, used by views. Backed by
     * PaymentType's getDisplayLabelAttribute for admin-defined purposes
     * and falls back to the canonical getPurposes() map for the seeded
     * set.
     */
    public function resolvePaymentPurpose(PaymentType $type): string
    {
        return (string) $type->display_label;
    }

    /**
     * Resolve the amount an applicant should pay for a given PaymentType,
     * honouring any live system-setting override. Mirrors the legacy
     * resolveAmount($purpose) but takes the resolved type directly so
     * callers can use it on a row from getApplicantPaymentTypes().
     */
    public function getPaymentAmount(PaymentType $type, ?Applicant $applicant = null): float
    {
        // Override key derived from the type's purpose so admins can
        // change the price without touching code. Falls back to a
        // purpose-based legacy key for the three canonical rows so
        // existing overrides keep working.
        $overrideKey = $this->overrideKeyFor($type);
        if ($overrideKey) {
            $override = SystemSetting::get($overrideKey);
            if ($override !== null && $override !== '' && is_numeric($override)) {
                return (float) $override;
            }
        }

        return (float) ($type->amount ?? 0);
    }

    /**
     * Build the system-settings key used to override this payment type's
     * default amount. For the three legacy purposes we keep the original
     * key names so existing override values on production keep applying;
     * for any other purpose we derive a stable, code-based key.
     */
    private function overrideKeyFor(PaymentType $type): ?string
    {
        if (isset(self::OVERRIDE_KEYS_PUBLIC[$type->purpose])) {
            return self::OVERRIDE_KEYS_PUBLIC[$type->purpose];
        }

        // `admission_<code-without-dashes>_amount` for admin-defined types.
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) $type->code));
        $slug = trim($slug, '_');

        return $slug ? "admission_{$slug}_amount" : null;
    }

    /* ------------------------------------------------------------------
     | Per-applicant gates (PaymentType-driven)
     * ------------------------------------------------------------------*/

    /**
     * Whether the given PaymentType is meant to be paid by this applicant
     * RIGHT NOW. Returns null when allowed, or a string explaining why
     * not. Caller turns the string into a flash message.
     *
     * This is the modern, PaymentType-aware replacement for the legacy
     * canPay(Applicant, string $purpose). It routes by purpose through a
     * small allow-list of business rules — for the three legacy purposes
     * the rules are unchanged (preserving existing behaviour); for any
     * other purpose the gate is permissive (admin's choice — they can
     * make a payment type available or not via the active toggle).
     */
    public function canPayApplicant(Applicant $applicant, PaymentType $type): ?string
    {
        // Already paid? Block regardless of purpose.
        if ($this->applicantHasPaidType($applicant, $type)) {
            return sprintf('You have already paid the %s fee.', $type->display_label);
        }

        // Per-purpose gates (only the canonical purposes have them).
        // Compulsory is the new applicant-facing migration trigger
        // (replacing school_fee on the applicant catalogue), so it
        // shares the same prerequisite gate as school_fee.
        $reason = match ($type->purpose) {
            PaymentType::PURPOSE_APPLICATION => $this->canPayApplication($applicant),
            PaymentType::PURPOSE_ACCEPTANCE  => $this->canPayAcceptance($applicant),
            PaymentType::PURPOSE_SCHOOL_FEE,
            PaymentType::PURPOSE_COMPULSORY  => $this->canPayCompulsory($applicant),
            default                          => null,
        };

        if ($reason !== null) {
            return $reason;
        }

        // Closed-form gate: the admission form must be open for any
        // applicant-side payment to be payable. Skipped for school_fee
        // and compulsory because those flows continue past admission
        // closing (admitted applicants still pay after the cycle ends).
        $isPostAdmissionFee = in_array($type->purpose, [
            PaymentType::PURPOSE_SCHOOL_FEE,
            PaymentType::PURPOSE_COMPULSORY,
        ], true);
        if (! $isPostAdmissionFee
            && ! SystemSetting::isOpen(SystemSetting::ADMISSION_FORM_OPEN)
        ) {
            return 'The admission form is currently closed.';
        }

        return null;
    }

    /**
     * Backward-compat: route a legacy canPay($applicant, $purpose) call
     * through the new PaymentType-aware gate. Resolves the type for the
     * purpose first (using the legacy PURPOSE_CODES map) so any old
     * caller keeps working.
     */
    public function canPay(Applicant $applicant, string $purpose): ?string
    {
        $type = $this->resolvePaymentType($purpose, PaymentType::AUDIENCE_APPLICANT);
        if (! $type) {
            return 'Unknown payment purpose.';
        }

        return $this->canPayApplicant($applicant, $type);
    }

    /**
     * Whether the applicant has paid every applicant-audience payment
     * type the admin has marked requires_payment=true. Used by the
     * applicant dashboard to decide whether to render a "you're all
     * done" panel.
     */
    public function isFullyPaid(Applicant $applicant): bool
    {
        foreach ($this->getApplicantPaymentTypes() as $type) {
            if (! $type->requires_payment) {
                continue;
            }
            if (! $this->applicantHasPaidType($applicant, $type)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generic, PaymentType-driven has-paid check. Routes by purpose so
     * the canonical three purposes still read the per-purpose timestamps
     * on the applicants table; any other purpose is read from the
     * payments table directly (via payer_id).
     */
    public function applicantHasPaidType(Applicant $applicant, PaymentType $type): bool
    {
        // Canonical three purposes — read the per-purpose timestamp on
        // the applicants table. Backward-compat with existing columns.
        return match ($type->purpose) {
            PaymentType::PURPOSE_APPLICATION => ! is_null($applicant->application_paid_at),
            PaymentType::PURPOSE_ACCEPTANCE  => ! is_null($applicant->acceptance_paid_at),
            PaymentType::PURPOSE_SCHOOL_FEE => ! is_null($applicant->compulsory_paid_at),
            default => Payment::where('payer_id', $applicant->id)
                ->where('fee_id', $type->id)
                ->where('status', 'completed')
                ->exists(),
        };
    }

    /**
     * Whether the admin has marked this PaymentType as required before
     * some downstream step (course registration, exam clearance, etc).
     * Drives UI gating elsewhere.
     */
    public function requiresPayment(PaymentType $type): bool
    {
        return (bool) $type->requires_payment;
    }

    /**
     * Most recent pending-or-failed attempt for this applicant + purpose.
     * Used by initiate() to reuse the row on retry, and by the dashboard
     * "Retry" affordance to find which row to resume.
     *
     * Cancelled rows are deliberately EXCLUDED — the payer explicitly
     * cancelled that attempt, so a fresh "Pay" click should create a
     * new row.
     */
    public function pendingAttemptFor(Applicant $applicant, string $purpose): ?Payment
    {
        return Payment::openForPayer($applicant->id, $purpose)->first();
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
     * @param  string|null  $audience  Pass PaymentType::AUDIENCE_APPLICANT
     *         (or _STUDENT) to ensure the resolved type is meant for that
     *         audience. Null = no audience restriction (admin/backfill paths).
     *
     * @return array{payment: Payment, reference: string, amount: float}
     */
    public function initiate(Applicant $applicant, string $purpose, string $channel = 'paystack', ?string $audience = null): array
    {
        $type = $this->resolvePaymentType($purpose, $audience);
        $amount = $this->resolveAmount($purpose);

        if ($amount <= 0) {
            throw new \RuntimeException("No amount configured for purpose [$purpose].");
        }

        $reference = $this->generateReference($purpose, $channel);

        // Retry behaviour: if the applicant already has a pending or
        // failed attempt for this fee (gateway callback never confirmed
        // success), reuse that row instead of inserting a duplicate.
        // The previous attempt's reference / gateway / status get reset
        // to pending so the next Paystack callback can re-resolve and
        // re-stamp *_paid_at cleanly. The row count for a fee stays at
        // one even after multiple retry clicks.
        $existing = $this->pendingAttemptFor($applicant, $purpose);
        if ($existing) {
            $existing->refreshForRetry($reference, $channel);
            return [
                'payment'  => $existing,
                'reference' => $reference,
                'amount'   => $amount,
            ];
        }

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
            // payments.fee_type is an ENUM on production with values
            // (application, acceptance, school_fees, hostel, library,
            // other). The PaymentType.code (APP_FORM etc.) is a join key
            // for payment_types, NOT a valid enum value here — using it
            // raises 'Data truncated for column fee_type' under MySQL
            // strict mode. Map via feeTypeFor() so the column always gets
            // an enum-safe value.
            'fee_type'        => $this->feeTypeFor($purpose),
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
            $type = $this->resolvePaymentType($purpose, PaymentType::AUDIENCE_APPLICANT);

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
                // ENUM-safe — see initiate() for the rationale.
                'fee_type'        => $this->feeTypeFor($purpose),
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

            $this->applyApplicantSideEffects($applicant, $payment, $purpose, $type);

            return $payment;
        });
    }

    /* ------------------------------------------------------------------
     | Completion (called by gateway callback OR test handler)
     * ------------------------------------------------------------------*/

    /**
     * Mark a pending payment as completed and stamp the right applicant
     * timestamp. If the purpose is `compulsory` / `school_fee`, also runs
     * the applicant → student migration.
     *
     * Idempotency contract: this method is safe to call multiple times
     * for the same payment row. The Payment->update() block is guarded
     * by a status check (don't reset status='completed' to itself, don't
     * clobber an existing transaction_id), and the side effects in
     * applyApplicantSideEffects() use the OR-existing pattern
     * (applicant->compulsory_paid_at ?: $stamp) so a second call never
     * moves a stamp that was already written.
     *
     * Note: do NOT short-circuit with an early `return $payment` on
     * `status === 'completed'`. The test handler's fallback path
     * creates a row with status='completed' before calling this method
     * (so the demo simulator returns 'completed' to the user without
     * calling Paystack) — and that fallback relies on the side effects
     * still running. Earlier code had `if (status === completed) return`
     * which silently broke the test handler: the Payment row existed
     * with status='completed' but applicant.compulsory_paid_at was
     * never stamped, so the dashboard kept showing "Locked".
     *
     * @param  array<string, mixed>  $gatewayResponse  raw Paystack/Flutterwave data
     */
    public function markCompleted(Payment $payment, array $gatewayResponse = []): Payment
    {
        // Guard the row update, not the whole method — so side effects
        // still run when called against a pre-completed row.
        if ($payment->status !== 'completed') {
            $payment->update([
                'status' => 'completed',
                'is_verified' => true,
                'payment_details' => json_encode($gatewayResponse),
                'payment_date' => $payment->payment_date ?: now(),
                'transaction_id' => $gatewayResponse['data']['transaction_id']
                    ?? $gatewayResponse['transaction_id']
                    ?? $payment->transaction_id,
            ]);
        }

        $applicant = Applicant::find($payment->payer_id);
        if (! $applicant) {
            Log::warning("Payment {$payment->id} completed but payer_id {$payment->payer_id} has no applicant row.");

            return $payment;
        }

        // Always run the side effects. applyApplicantSideEffects() uses
        // the OR-existing guard per-payload, so a second call on the
        // same row won't move the *_paid_at stamp.
        $this->applyApplicantSideEffects($applicant, $payment, $payment->payment_purpose, $payment->fee_id ? PaymentType::find($payment->fee_id) : null);

        return $payment->fresh();
    }

    /**
     * Write all of the applicant-side effects of a completed payment:
     *   - the *_paid_at timestamp for the purpose
     *   - legacy payment_status / payment_ref / payment_amount columns
     *     (kept for back-compat with existing views)
     *   - student migration when purpose == school_fee
     */
    private function applyApplicantSideEffects(Applicant $applicant, Payment $payment, ?string $purpose = null, ?PaymentType $type = null): void
    {
        // Resolve the purpose from either the PaymentType or the string.
        // The PaymentType is preferred because it carries the full context;
        // the string is the legacy fallback.
        $effectivePurpose = $type?->purpose ?? $purpose;

        // Resolve the PaymentType from the purpose if it wasn't passed
        // explicitly — needed for the migration trigger below.
        $resolvedType = $type ?? ($purpose ? $this->resolvePaymentType($purpose, PaymentType::AUDIENCE_APPLICANT) : null);

        DB::transaction(function () use ($applicant, $payment, $effectivePurpose, $resolvedType) {
            $update = [
                'payment_status' => 'completed',
                'payment_ref' => $payment->reference,
                'payment_transaction_id' => $payment->transaction_id,
                'payment_amount' => $payment->amount,
                'payment_date' => $payment->payment_date ?: now(),
            ];

            $stamp = $payment->payment_date ?: now();

            switch ($effectivePurpose) {
                case PaymentType::PURPOSE_APPLICATION:
                    $update['application_paid_at'] = $applicant->application_paid_at ?: $stamp;
                    break;

                case PaymentType::PURPOSE_ACCEPTANCE:
                    $update['acceptance_paid_at'] = $applicant->acceptance_paid_at ?: $stamp;
                    break;

                // Compulsory is the new applicant-facing migration trigger.
                // It writes to the same column as school_fee because both
                // are the post-admission "you now become a student" fee —
                // we kept a single timestamp for backward-compat.
                case PaymentType::PURPOSE_SCHOOL_FEE:
                case PaymentType::PURPOSE_COMPULSORY:
                    $update['compulsory_paid_at'] = $applicant->compulsory_paid_at ?: $stamp;
                    break;
            }

            $applicant->update($update);

            // Compulsory triggers the applicant → student migration.
            // Allow any purpose that admin has tagged as a "compulsory"
            // step — currently school_fee, but extensible for future flows.
            if ($resolvedType && $this->isMigrationTrigger($resolvedType)) {
                $this->migrateApplicantToStudent($applicant);
            } elseif (in_array($effectivePurpose, [
                PaymentType::PURPOSE_SCHOOL_FEE,
                PaymentType::PURPOSE_COMPULSORY,
            ], true)) {
                $this->migrateApplicantToStudent($applicant);
            }
        });
    }

    /**
     * Whether this PaymentType, when paid, should trigger the
     * applicant → student migration. Defaults to true for the canonical
     * school_fee purpose; admin can opt-in additional rows by setting
     * `requires_payment = true` and adding their `code` to the
     * MIGRATION_TRIGGER_CODES list (or by tagging the row's purpose
     * as `compulsory_fee`).
     *
     * Public because the applicant payment-gateway controller calls
     * this from the post-migration check (deciding whether to redirect
     * to the student portal or fall back to the applicant dashboard
     * when the migration hasn't run yet). Was previously `private` and
     * 500'd the test-payment simulator with "Call to private method
     * ... isMigrationTrigger() from scope ... PaymentGatewayController".
     */
    public function isMigrationTrigger(PaymentType $type): bool
    {
        // Direct: school_fee / school_fees / compulsory / compulsory_fee.
        if (in_array($type->purpose, [
            PaymentType::PURPOSE_SCHOOL_FEE,
            PaymentType::PURPOSE_SCHOOL_FEE_PRODUCTION,
            PaymentType::PURPOSE_COMPULSORY,
        ], true)) {
            return true;
        }

        // Admin-defined: codes that imply "this is the final step before
        // becoming a student". Easy to extend without code changes.
        $triggerCodes = ['SCHOOL_FEE', 'COMPULSORY_FEE', 'COMP_FEE', 'MIGRATION_FEE'];

        return in_array(strtoupper((string) $type->code), $triggerCodes, true);
    }

    /**
     * Idempotent applicant → student migration.
     *
     * Reused by markCompleted() (compulsory fee path) and available as a
     * public method so the Registrar UI can re-trigger on demand if a
     * migration partially failed.
     *
     * Side effect: any pre-existing `payments` rows tied to the applicant
     * via `payer_id` (application fee, acceptance fee, compulsory fee —
     * they were created with `student_id = null` before the migration
     * existed) are back-filled with `student_id = $student->id` so the
     * student-side history view (`Payment::where('student_id', ...)`)
     * surfaces them. The `whereNull('student_id')` guard makes the
     * back-fill idempotent — a second call touches zero rows.
     */
    public function migrateApplicantToStudent(Applicant $applicant): ?Student
    {
        if (! $applicant) {
            Log::warning('migrateApplicantToStudent: applicant is null');

            return null;
        }

        if ($applicant->status !== 'admitted') {
            Log::warning('migrateApplicantToStudent: applicant status is not admitted, skipping', [
                'applicant_id' => $applicant->id,
                'status'       => $applicant->status,
                'user_id'      => $applicant->user_id,
            ]);

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

            // Back-fill applicant-side payments even when the Student row
            // already exists. Covers pre-existing migrations that
            // happened before this back-fill code shipped — without it,
            // their legacy payments would never appear in
            // /student/payments.
            $this->relinkApplicantPayments($applicant, $existing);

            return $existing;
        }

        $matricNumber = MatricNumberService::generate($applicant);
        if (! $matricNumber) {
            Log::error("migrateApplicantToStudent: MatricNumberService returned empty for applicant {$applicant->id}");

            return null;
        }

        // Build the Student::create payload defensively. The original
        // 2024_01_01_000009_create_students_table migration only declares
        // user_id, matric_number, school_id, department_id, programme_id,
        // session_id, level, status. The other columns (state_id, lga_id,
        // nationality_id, from_application, applicant_id) are added by
        // later migrations (2026_07_27_000007_ensure_critical_columns_exist
        // + 2026_07_29_000001_add_student_source_to_students_table) — and
        // on live those migrations may not have run yet (this repo has a
        // repeated DB-drift pattern — see memory notes for hospital_patients
        // / external_payments / admissions_centres etc.). Without the
        // Schema::hasColumn guards, Student::create() throws "Unknown
        // column 'state_id'/'lga_id'/'nationality_id'/'from_application'/
        // 'applicant_id'" and the whole migration silently returns null.
        $candidate = [
            'user_id'        => $applicant->user_id,
            'matric_number'  => $matricNumber,
            'school_id'      => $applicant->school_id,
            'department_id'  => $applicant->department_id,
            'programme_id'   => $applicant->programme_id,
            'session_id'     => $applicant->session_id,
            'status'         => 'active',
            'from_application' => true,
            'applicant_id'     => $applicant->id,
        ];

        // applicants.entry_level is a string ('UTME' by default) but
        // students.level is an integer column. Coerce to int — non-numeric
        // values fall back to 1 (entry-level default). Without this, MySQL
        // strict mode rejects the insert.
        $levelRaw = $applicant->entry_level;
        if (is_numeric($levelRaw)) {
            $candidate['level'] = (int) $levelRaw;
        } else {
            $candidate['level'] = 1;
        }

        // Only include the optional columns that exist on the live
        // students table. Mirrors the local-DB-drift safety-net pattern
        // from migrations 2026_08_09_000001, 2026_08_09_000002, and
        // 2026_08_11_000001.
        if (Schema::hasColumn('students', 'state_id')) {
            // FK constraint: a NULL state_id is fine (column is nullable),
            // but a non-null state_id must reference an existing states row.
            // If the applicant's state_id points to a missing/deleted row,
            // null it out so the FK doesn't reject the insert.
            $stateId = $applicant->state_id;
            if ($stateId !== null && ! \App\Models\State::where('id', $stateId)->exists()) {
                Log::warning('migrateApplicantToStudent: applicants.state_id references missing State row, dropping FK', [
                    'applicant_id' => $applicant->id,
                    'missing_state_id' => $stateId,
                ]);
                $stateId = null;
            }
            $candidate['state_id'] = $stateId;
        }

        if (Schema::hasColumn('students', 'lga_id')) {
            $lgaId = $applicant->lga_id;
            if ($lgaId !== null && ! LocalGovernment::where('id', $lgaId)->exists()) {
                Log::warning('migrateApplicantToStudent: applicants.lga_id references missing LGA row, dropping FK', [
                    'applicant_id' => $applicant->id,
                    'missing_lga_id' => $lgaId,
                ]);
                $lgaId = null;
            }
            $candidate['lga_id'] = $lgaId;
        }

        if (Schema::hasColumn('students', 'nationality_id')) {
            $nationalityId = $applicant->nationality_id;
            if ($nationalityId !== null && ! Nationality::where('id', $nationalityId)->exists()) {
                Log::warning('migrateApplicantToStudent: applicants.nationality_id references missing Nationality row, dropping FK', [
                    'applicant_id' => $applicant->id,
                    'missing_nationality_id' => $nationalityId,
                ]);
                $nationalityId = null;
            }
            $candidate['nationality_id'] = $nationalityId;
        }

        try {
            return DB::transaction(function () use ($applicant, $candidate, $matricNumber) {
                $student = Student::create($candidate);

                // Promote role=student. Separate try/catch so a missing
                // roles table on live doesn't tank the Student row we
                // already created — the Student row is the load-bearing
                // piece; the role row can be patched later.
                try {
                    $studentRole = \App\Models\Role::where('slug', 'student')->first();
                    if ($studentRole) {
                        // Promote to student role and, when the
                        // applicant recorded a gender, mirror it on the
                        // User row. The student-side hostel filter
                        // (`Student\HostelController::availableHostels`)
                        // reads `users.gender` to decide which hostels
                        // to surface; without this mirror a student
                        // who only ever set gender on the application
                        // form would see no hostels on first login.
                        // We only overwrite when the user row's gender
                        // is missing — never clobber a value the user
                        // may have updated in their portal profile.
                        $userUpdate = [
                            'role_id'  => $studentRole->id,
                            'is_active' => true,
                        ];
                        if ($applicant->gender && ! $applicant->user?->gender) {
                            $userUpdate['gender'] = $applicant->gender;
                        }
                        $applicant->user?->update($userUpdate);
                    } else {
                        Log::warning('migrateApplicantToStudent: student role row not found, skipping role promotion', [
                            'applicant_id' => $applicant->id,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::error('migrateApplicantToStudent: role promotion failed (student row already created)', [
                        'applicant_id' => $applicant->id,
                        'student_id'   => $student->id,
                        'error'        => $e->getMessage(),
                    ]);
                }

                // Stamp the applicant with the new student_id + matric
                // + migration timestamp. Separate try/catch — if this
                // fails (column missing on applicants) the Student row
                // is still usable; we can back-fill the applicant columns
                // manually.
                try {
                    $applicant->update([
                        'student_id' => $student->id,
                        'matric_number' => $matricNumber,
                        'status' => 'admitted',
                        'migrated_to_student_at' => now(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('migrateApplicantToStudent: applicant columns update failed (student row already created)', [
                        'applicant_id' => $applicant->id,
                        'student_id'   => $student->id,
                        'error'        => $e->getMessage(),
                    ]);
                }

                // Back-fill applicant-side payment rows. Non-fatal — a
                // missing payments.student_id column doesn't kill the
                // migration, the student portal just won't surface the
                // applicant's payment history until the column exists.
                try {
                    $this->relinkApplicantPayments($applicant, $student);
                } catch (\Throwable $e) {
                    Log::error('migrateApplicantToStudent: relinkApplicantPayments failed (student row already created)', [
                        'applicant_id' => $applicant->id,
                        'student_id'   => $student->id,
                        'error'        => $e->getMessage(),
                    ]);
                }

                return $student;
            });
        } catch (\Throwable $e) {
            // The Student::create threw — log the actual underlying error
            // so the operator can see whether it was a missing column,
            // FK violation, unique-index collision on matric_number, etc.
            Log::error('migrateApplicantToStudent: Student::create failed', [
                'applicant_id'  => $applicant->id,
                'matric_number' => $matricNumber,
                'error'         => $e->getMessage(),
                'sqlstate'      => $e instanceof \PDOException ? $e->getCode() : null,
            ]);

            return null;
        }
    }

    /**
     * Stamp `student_id` on every applicant-side payment row that doesn't
     * already have one. Idempotent — second invocation finds zero nullable
     * rows.
     *
     * Public because the registrar back-fill path may need to invoke it
     * directly on applicants who migrated before this code shipped.
     */
    public function relinkApplicantPayments(Applicant $applicant, Student $student): int
    {
        $relinked = Payment::where('payer_id', $applicant->id)
            ->whereNull('student_id')
            ->update(['student_id' => $student->id]);

        if ($relinked > 0) {
            Log::info("Relinked {$relinked} applicant payment(s) to student {$student->id} on migration (applicant {$applicant->id}).",
                ['applicant_id' => $applicant->id, 'student_id' => $student->id]);
        }

        return $relinked;
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
