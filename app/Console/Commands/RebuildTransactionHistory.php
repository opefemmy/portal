<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\ExternalPayment;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Services\ApplicantPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild the unified transaction history view from legacy data.
 *
 * Pre-2026-08 data lives in two places:
 *   - applicants.payment_* columns (application fee only)
 *   - external_payments rows that may have linked to an applicant already
 *
 * This command is idempotent: it stamps the *_paid_at columns and creates
 * Payment rows from any legacy applicant.payment_* data that doesn't
 * already have a matching Payment row.
 *
 * Usage:
 *   php artisan payments:rebuild-transaction-history
 *   php artisan payments:rebuild-transaction-history --dry-run
 *   php artisan payments:rebuild-transaction-history --applicant=123
 */
class RebuildTransactionHistory extends Command
{
    protected $signature = 'payments:rebuild-transaction-history
        {--dry-run : Show what would change without writing}
        {--applicant= : Only process this applicant id}';

    protected $description = 'Backfill per-purpose payment timestamps and unified history rows';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $applicantId = $this->option('applicant');

        $query = Applicant::query();
        if ($applicantId) {
            $query->where('id', $applicantId);
        }

        $applicants = $query->where('payment_status', 'completed')
            ->whereNotNull('payment_ref')
            ->get();

        $this->info("Found {$applicants->count()} applicants with legacy payment data.");

        // Legacy backfill from applicants.payment_*.
        $stamped = 0;
        $created = 0;
        $skipped = 0;

        foreach ($applicants as $applicant) {
            $paymentDate = $applicant->payment_date ?: now();

            // 1. Stamp application_paid_at if missing.
            if (! $applicant->application_paid_at) {
                if (! $dryRun) {
                    $applicant->update(['application_paid_at' => $paymentDate]);
                }
                $stamped++;
                $this->line("  [applicant {$applicant->id}] stamp application_paid_at");
            }

            // 2. If a Payment row already exists for this applicant + ref, skip.
            $existing = Payment::where('payer_id', $applicant->id)
                ->where(function ($q) use ($applicant) {
                    $q->where('reference', $applicant->payment_ref)
                        ->orWhere('transaction_id', $applicant->payment_ref);
                })
                ->exists();

            if ($existing) {
                $skipped++;
                continue;
            }

            // 3. Create a Payment row from the legacy applicant columns.
            if (! $dryRun) {
                $type = PaymentType::where('purpose', PaymentType::PURPOSE_APPLICATION)->first();
                Payment::create([
                    'student_id'      => $applicant->student_id,
                    'fee_id'          => $type?->id,
                    'amount'          => $applicant->payment_amount ?: 0,
                    'total_amount'    => $applicant->payment_amount ?: 0,
                    'reference'       => $applicant->payment_ref,
                    'payment_ref'     => $applicant->payment_ref,
                    'transaction_id'  => $applicant->payment_transaction_id ?: $applicant->payment_ref,
                    'gateway'         => 'legacy',
                    'payment_method'  => 'legacy',
                    'status'          => 'completed',
                    'is_verified'     => true,
                    'student_type'    => 'applicant',
                    'payment_purpose' => PaymentType::PURPOSE_APPLICATION,
                    // ENUM-safe value — see ApplicantPaymentService::feeTypeFor().
                    'fee_type'        => app(ApplicantPaymentService::class)->feeTypeFor(PaymentType::PURPOSE_APPLICATION),
                    'payer_id'        => $applicant->id,
                    'payer_name'      => $applicant->full_name,
                    'payer_email'     => $applicant->email,
                    'payment_date'    => $paymentDate,
                    'payment_details' => json_encode([
                        'source' => 'applicants.payment_* legacy columns',
                        'migrated_by' => 'payments:rebuild-transaction-history',
                    ]),
                ]);
            }

            $created++;
            $this->line("  [applicant {$applicant->id}] created Payment row from legacy data");
        }

        // 4. External payments already linked to an applicant — nothing to do,
        // ApplicantPaymentService::recordManual already creates the Payment
        // row at validation time. But for legacy data, sometimes
        // external_payments.applicant_id is set but no matching Payment
        // exists. Backfill those.
        $externalCreated = 0;
        if (! \Illuminate\Support\Facades\Schema::hasTable('external_payments')) {
            $this->warn('Skipping external_payments phase — table missing.');
        } else {
            $externalOrphans = ExternalPayment::whereNotNull('applicant_id')
                ->where('payment_status', 'completed')
                ->where('is_used', true)
                ->get();

            foreach ($externalOrphans as $ext) {
                $alreadyHas = Payment::where('payer_id', $ext->applicant_id)
                    ->where('transaction_id', $ext->transaction_id)
                    ->exists();
                if ($alreadyHas) {
                    continue;
                }

                $applicant = Applicant::find($ext->applicant_id);
                if (! $applicant) {
                    continue;
                }

                if (! $dryRun) {
                    Payment::create([
                        'student_id'      => null,
                        'amount'          => $ext->amount,
                        'total_amount'    => $ext->amount,
                        'reference'       => $ext->transaction_id,
                        'payment_ref'     => $ext->transaction_id,
                        'transaction_id'  => $ext->transaction_id,
                        'gateway'         => $ext->payment_channel ?: 'bank_transfer',
                        'payment_method'  => 'bank_transfer',
                        'status'          => 'completed',
                        'is_verified'     => true,
                        'student_type'    => 'applicant',
                        'payment_purpose' => PaymentType::PURPOSE_APPLICATION,
                        'payer_id'        => $applicant->id,
                        'payer_name'      => $ext->applicant_name ?: $applicant->full_name,
                        'payer_email'     => $ext->email ?: $applicant->email,
                        'payment_date'    => $ext->payment_date ?: now(),
                        'payment_details' => json_encode([
                            'source' => 'external_payments legacy',
                            'migrated_by' => 'payments:rebuild-transaction-history',
                        ]),
                    ]);
                }

                $externalCreated++;
                $this->line("  [external {$ext->id} → applicant {$ext->applicant_id}] created Payment row");
            }
        }

        // 5. Reconcile applicants who paid through the new pipeline but whose
        // applicant.{application,acceptance,compulsory}_paid_at columns were
        // never stamped (e.g. they hit the test-handler fallback or a callback
        // before this fix). Their Payment rows are 'completed' but the
        // dashboard's Payment Progress card still shows Pending.
        //
        // For each completed Payment row whose purpose is one of the three
        // applicant fees and whose applicant-side timestamp is null, stamp it.
        $reconciledStamps = 0;
        $reconcileQuery = Payment::query()
            ->where('status', 'completed')
            ->whereIn('payment_purpose', [
                PaymentType::PURPOSE_APPLICATION,
                PaymentType::PURPOSE_ACCEPTANCE,
                PaymentType::PURPOSE_SCHOOL_FEE,
            ])
            ->whereNotNull('payer_id')
            ->with('payer'); // eager-load applicant so we don't N+1

        if ($applicantId) {
            $reconcileQuery->where('payer_id', $applicantId);
        }

        $columnForPurpose = [
            PaymentType::PURPOSE_APPLICATION => 'application_paid_at',
            PaymentType::PURPOSE_ACCEPTANCE  => 'acceptance_paid_at',
            PaymentType::PURPOSE_SCHOOL_FEE => 'compulsory_paid_at',
        ];

        foreach ($reconcileQuery->cursor() as $row) {
            $applicant = $row->payer;
            if (! $applicant) {
                continue;
            }

            $column = $columnForPurpose[$row->payment_purpose] ?? null;
            if (! $column || $applicant->{$column}) {
                continue; // already stamped, nothing to do
            }

            if (! $dryRun) {
                $applicant->update([
                    $column => $row->payment_date ?: now(),
                    // Keep legacy columns in sync so older views don't break.
                    'payment_status' => 'completed',
                    'payment_ref' => $applicant->payment_ref ?: $row->reference,
                    'payment_transaction_id' => $applicant->payment_transaction_id ?: $row->transaction_id,
                    'payment_amount' => $applicant->payment_amount ?: $row->amount,
                    'payment_date' => $applicant->payment_date ?: ($row->payment_date ?: now()),
                ]);
            }

            $reconciledStamps++;
            $this->line("  [payment {$row->id} → applicant {$applicant->id}] stamped {$column}");
        }

        $this->info("Done.");
        $this->table(
            ['metric', 'count'],
            [
                ['applicants stamped (legacy)', $stamped],
                ['Payment rows created from applicant columns', $created],
                ['applicants skipped (already had Payment)', $skipped],
                ['Payment rows created from external_payments', $externalCreated],
                ['applicants reconciled from existing Payment rows', $reconciledStamps],
                ['dry-run', $dryRun ? 'yes' : 'no'],
            ]
        );

        return self::SUCCESS;
    }
}
