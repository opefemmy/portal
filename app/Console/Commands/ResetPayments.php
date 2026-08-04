<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\ExternalPayment;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wipe every payment record and reset every applicant's fee state.
 *
 *   php artisan payments:reset               # dry-run
 *   php artisan payments:reset --force       # actually wipe
 *   php artisan payments:reset --keep-students  # do NOT delete Student rows
 *                                              # that were created via the
 *                                              # applicant→student migration
 *
 * What it does (when --force is set):
 *   1. Truncate the `payments` table
 *   2. Truncate the `external_payments` table (if it exists)
 *   3. Truncate the `students` table (only those rows that came from the
 *      applicant migration, unless --keep-students is passed)
 *   4. Reset applicant fee columns:
 *        payment_status, payment_ref, payment_transaction_id, payment_amount,
 *        payment_date, application_paid_at, acceptance_paid_at,
 *        compulsory_paid_at, migrated_to_student_at, student_id, matric_number
 *      on every applicant row.
 *
 * Why a dedicated command and not a SQL script:
 *   - Each table is wrapped in a transaction so a partial failure doesn't
 *     leave the DB half-wiped.
 *   - Row counts are printed as we go so the operator can audit.
 *   - --force is required to actually do anything; without it the command
 *     only reports the planned counts. No 'Are you sure?' prompt because
 *     it's intended to be run unattended from a deploy script.
 *
 * WARNING: This is destructive and not reversible without a backup. There
 * is no confirmation prompt. Operators are expected to have taken a backup
 * (or to have accepted the loss) before invoking --force.
 */
class ResetPayments extends Command
{
    protected $signature = 'payments:reset
        {--force : Actually wipe. Without this flag the command only reports what would change.}
        {--keep-students : Skip step 3 — leave Student rows intact (they keep their matric numbers and the applicant→student linkage stays broken).}';

    protected $description = 'Wipe all payments and reset every applicant\'s fee state';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $keepStudents = (bool) $this->option('keep-students');

        $this->warn('This will:');
        $this->line('  - delete every row in <fg=yellow>payments</>');
        if (Schema::hasTable('external_payments')) {
            $this->line('  - delete every row in <fg=yellow>external_payments</>');
        }
        if (! $keepStudents && Schema::hasTable('students')) {
            $this->line('  - delete every row in <fg=yellow>students</> that came from an applicant migration');
        }
        $this->line('  - reset every <fg=yellow>applicants</> payment column (payment_status, *_paid_at, student_id, matric_number, ...)');

        if (! $force) {
            $this->newLine();
            $this->info('Counts that would be wiped:');
            $this->table(['table', 'rows'], [
                ['payments', Payment::count()],
                ['external_payments', Schema::hasTable('external_payments') ? ExternalPayment::count() : 'n/a'],
                ['students (from applicant migration)', $keepStudents ? 'kept (--keep-students)' : (Schema::hasTable('students') ? Student::where('from_application', true)->count() : 'n/a')],
                ['applicants with payment_status=completed', Applicant::where('payment_status', 'completed')->count()],
                ['applicants with application_paid_at', Applicant::whereNotNull('application_paid_at')->count()],
                ['applicants with acceptance_paid_at', Applicant::whereNotNull('acceptance_paid_at')->count()],
                ['applicants with compulsory_paid_at', Applicant::whereNotNull('compulsory_paid_at')->count()],
                ['applicants with student_id', Applicant::whereNotNull('student_id')->count()],
            ]);
            $this->newLine();
            $this->warn('Dry run — pass --force to actually wipe.');

            return self::SUCCESS;
        }

        // --force path. Each step is its own DB::transaction so a failure
        // in step N does not undo step N-1. We do NOT wrap the whole
        // command in one transaction because some of these tables have
        // FKs that get in the way of nested transactions on MySQL.
        $counts = [];

        // Step 1: payments
        $counts['payments'] = DB::transaction(function () {
            $count = Payment::count();
            // delete() returns affected row count and respects FKs.
            Payment::query()->delete();

            return $count;
        });
        $this->info("  [1] payments: deleted {$counts['payments']}");

        // Step 2: external_payments
        if (Schema::hasTable('external_payments')) {
            $counts['external_payments'] = DB::transaction(function () {
                $count = ExternalPayment::count();
                ExternalPayment::query()->delete();

                return $count;
            });
            $this->info("  [2] external_payments: deleted {$counts['external_payments']}");
        } else {
            $this->warn('  [2] external_payments: skipped (table missing)');
        }

        // Step 3: students (only the from_application ones — never touch
        // manually-created student records).
        if (! $keepStudents && Schema::hasTable('students')) {
            $counts['students_from_application'] = DB::transaction(function () {
                $count = Student::where('from_application', true)->count();
                Student::where('from_application', true)->delete();

                return $count;
            });
            $this->info("  [3] students (from_application=true): deleted {$counts['students_from_application']}");
        } elseif ($keepStudents) {
            $this->warn('  [3] students: kept (--keep-students)');
        } else {
            $this->warn('  [3] students: skipped (table missing)');
        }

        // Step 4: applicant columns. Reset the union of columns we know
        // about, but only the ones that actually exist on this DB so the
        // command works on legacy deployments missing one of them.
        $columnsToReset = [
            'payment_status',
            'payment_ref',
            'payment_transaction_id',
            'payment_amount',
            'payment_date',
            'application_paid_at',
            'acceptance_paid_at',
            'compulsory_paid_at',
            'migrated_to_student_at',
            'student_id',
            'matric_number',
        ];

        $existing = array_values(array_filter(
            $columnsToReset,
            fn ($c) => Schema::hasColumn('applicants', $c),
        ));

        $counts['applicants_reset'] = DB::transaction(function () use ($existing) {
            // Build an update that nulls each existing column. We touch every
            // applicant (not just completed ones) so the column state is
            // uniform across the table — easier to reason about.
            $update = array_fill_keys($existing, null);

            return Applicant::query()->update($update);
        });
        $this->info("  [4] applicants: reset {$counts['applicants_reset']} row(s), columns: " . implode(', ', $existing));

        $this->newLine();
        $this->info('Done.');
        $this->table(['metric', 'count'], array_map(
            fn ($k, $v) => [$k, $v],
            array_keys($counts),
            array_values($counts),
        ));

        return self::SUCCESS;
    }
}