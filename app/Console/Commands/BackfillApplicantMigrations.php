<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\Payment;
use App\Models\PaymentType;
use App\Services\ApplicantPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Migrate applicants who paid the compulsory (or school-fee) fee but never
 * had a Student row created. The classic case is a live deployment where
 * markCompleted / applyApplicantSideEffects / migrateApplicantToStudent
 * silently failed inside the original DB::transaction — the Payment row
 * is status='completed', applicants.compulsory_paid_at is set, but
 * applicants.student_id is null. Without intervention, those users see
 * "Compulsory Fee: Locked" forever and cannot reach the student portal.
 *
 * Usage:
 *   php artisan applicants:backfill-migrations                     # dry run
 *   php artisan applicants:backfill-migrations --apply              # actually migrate
 *   php artisan applicants:backfill-migrations --applicant=42      # only one row
 *   php artisan applicants:backfill-migrations --limit=100         # cap the batch
 *   php artisan applicants:backfill-migrations --include-school-fee  # also catch PURPOSE_SCHOOL_FEE payments
 *
 * The eligibility query mirrors the dashboard's $hasCompletedCompulsory
 * check: an applicant is a back-fill candidate when they have at least
 * one completed migration-trigger Payment row AND applicants.student_id
 * is null AND applicants.status = 'admitted'. Without 'admitted' the
 * ApplicantPaymentService::migrateApplicantToStudent() guard returns
 * null immediately, so we'd waste a row trying.
 *
 * Each migration runs through ApplicantPaymentService::migrateApplicantToStudent
 * — the same path the live Paystack callback and the test-payment
 * simulator use. Service is idempotent: a Student row that already
 * exists short-circuits without touching role_id (legacy migrations
 * leave role_id stale; the per-row repair lives in the
 * processTestPaymentInner/syncPaymentSideEffects retry paths, not here).
 *
 * Output:
 *   - dry run prints a table of candidates (id, email, application_number,
 *     compulsory_paid_at, payment_ref, status) and exits
 *   - --apply mode prints per-row result (migrated | skipped | failed)
 *     with a final summary table.
 *
 * Why a dedicated command and not a one-off SQL script:
 *   - The migration runs in DB::transaction per row, so a single
 *     failure doesn't poison the rest of the batch.
 *   - The service does matric generation, FK lookups, role assignment
 *     and payment back-link — none of which we want to reimplement in
 *     raw SQL.
 *   - Row counts and the result table give the operator an auditable
 *     record of what changed (and what didn't).
 */
class BackfillApplicantMigrations extends Command
{
    protected $signature = 'applicants:backfill-migrations
        {--apply : Actually run migrateApplicantToStudent. Without this the command only lists candidates.}
        {--applicant= : Only process this single applicant id (overrides --limit).}
        {--limit=200 : Cap the batch size when not using --applicant. Defaults to 200 to avoid runaway on huge cohorts.}
        {--include-school-fee : Also catch applicants whose only migration-trigger payment is on PURPOSE_SCHOOL_FEE (without this flag, only PURPOSE_COMPULSORY is matched).}';

    protected $description = 'Migrate applicants who paid the compulsory fee but never had a Student row created';

    public function handle(ApplicantPaymentService $payments): int
    {
        $apply    = (bool) $this->option('apply');
        $limit    = (int) $this->option('limit');
        $onlyOne  = $this->option('applicant');
        $includeSchoolFee = (bool) $this->option('include-school-fee');

        $purposes = [PaymentType::PURPOSE_COMPULSORY];
        if ($includeSchoolFee) {
            $purposes[] = PaymentType::PURPOSE_SCHOOL_FEE;
        }

        // Build the candidate query. Applicant must:
        //   - have a completed migration-trigger Payment row
        //   - NOT have a Student row yet (student_id is null AND
        //     migrated_to_student_at is null — Applicant::isMigrated
        //     reads both)
        //   - have status='admitted' (the migrateApplicantToStudent
        //     guard short-circuits otherwise)
        $query = Applicant::query()
            ->whereIn('id', function ($sub) use ($purposes) {
                $sub->select('payer_id')
                    ->from((new Payment)->getTable())
                    ->where('status', 'completed')
                    ->whereIn('payment_purpose', $purposes);
            })
            ->whereNull('student_id')
            ->whereNull('migrated_to_student_at')
            ->where('status', 'admitted')
            ->orderBy('id');

        if ($onlyOne !== null) {
            $query->where('id', (int) $onlyOne);
        } elseif ($limit > 0) {
            $query->limit($limit);
        }

        $candidates = $query->with(['user', 'payments' => function ($q) use ($purposes) {
            $q->where('status', 'completed')
              ->whereIn('payment_purpose', $purposes)
              ->latest('payment_date');
        }])->get();

        if ($candidates->isEmpty()) {
            $this->info('No applicants need back-fill migration.');

            return self::SUCCESS;
        }

        // Render the dry-run table once — same shape both branches use,
        // so the operator can compare dry-run vs apply output.
        $rows = $candidates->map(function (Applicant $a) {
            $firstPay = $a->payments->first();

            return [
                'applicant_id'      => $a->id,
                'application_no'    => $a->application_number ?: '—',
                'email'             => optional($a->user)->email ?: '—',
                'status'            => $a->status,
                'compulsory_paid'   => $a->compulsory_paid_at ?: '—',
                'latest_payment'    => $firstPay ? $firstPay->reference : '—',
                'student_id'        => $a->student_id ?: '—',
            ];
        })->all();

        $this->table(
            ['applicant_id', 'application_no', 'email', 'status', 'compulsory_paid', 'latest_payment', 'student_id'],
            $rows
        );

        $this->newLine();
        $this->line(sprintf('Candidates: <info>%d</info>', $candidates->count()));

        if (! $apply) {
            $this->warn('Dry run — pass --apply to actually migrate.');

            return self::SUCCESS;
        }

        // --apply path. Each row is its own try/catch — a single
        // failure logs and continues. We do NOT wrap the whole batch
        // in one transaction because the service internally runs its
        // own DB::transaction per row.
        $results = ['migrated' => 0, 'already_migrated' => 0, 'failed' => 0];
        $failures = [];

        foreach ($candidates as $applicant) {
            // Re-check eligibility inside the loop — a previous row's
            // migration could have back-filled this one (the service
            // runs relinkApplicantPayments even when a Student row
            // already exists, but we still want to skip cleanly).
            $fresh = $applicant->fresh();
            if ($fresh && $fresh->isMigrated()) {
                $results['already_migrated']++;
                continue;
            }

            try {
                $student = $payments->migrateApplicantToStudent($fresh);

                if ($student === null) {
                    $results['failed']++;
                    $failures[] = [
                        'applicant_id' => $fresh->id,
                        'reason'       => 'migrateApplicantToStudent returned null (likely not admitted or matric service failed)',
                    ];
                    continue;
                }

                // Cover the legacy migration gap: if a Student row
                // already existed when migrateApplicantToStudent was
                // called, the service early-returns without touching
                // role_id. Promote role=student here so the user can
                // reach /student/dashboard.
                if ($fresh->user && ! $fresh->user->hasRole('student')) {
                    $studentRole = \App\Models\Role::where('slug', 'student')->first();
                    if ($studentRole) {
                        $fresh->user->update(['role_id' => $studentRole->id, 'is_active' => true]);
                    }
                }

                $results['migrated']++;
                $this->line("  [{$fresh->id}] migrated → student_id={$student->id}, matric={$student->matric_number}");
            } catch (\Throwable $e) {
                $results['failed']++;
                $failures[] = [
                    'applicant_id' => $fresh->id,
                    'reason'       => $e->getMessage(),
                ];
                Log::error('applicants:backfill-migrations: row failed', [
                    'applicant_id' => $fresh->id,
                    'error'        => $e->getMessage(),
                ]);
                $this->error("  [{$fresh->id}] failed: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->table(
            ['result', 'count'],
            [
                ['migrated',          $results['migrated']],
                ['already_migrated',  $results['already_migrated']],
                ['failed',            $results['failed']],
            ]
        );

        if ($failures !== []) {
            $this->newLine();
            $this->warn('Failures:');
            $this->table(['applicant_id', 'reason'], $failures);
        }

        return $results['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}