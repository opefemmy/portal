<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Print the production-allowed values for `payment_types.purpose`.
 *
 * The repo migration declares `purpose` as a varchar(30) but the
 * production database has a strict MySQL ENUM that rejects any
 * value outside the allowed set with a 1265 truncation error. This
 * command reads the live ENUM definition and prints it so the
 * operator can see exactly what values will succeed and what will
 * fail at INSERT time.
 *
 * Usage:
 *   php artisan payment-types:list-purposes
 */
class PaymentTypeListPurposes extends Command
{
    protected $signature = 'payment-types:list-purposes';

    protected $description = 'Print the production-allowed values for payment_types.purpose';

    public function handle(): int
    {
        try {
            $rows = DB::select("SHOW COLUMNS FROM payment_types WHERE Field = 'purpose'");
        } catch (\Throwable $e) {
            $this->error('Could not read the payment_types.purpose column: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (!$rows) {
            $this->error('payment_types.purpose column does not exist (unrun migration?).');
            return self::FAILURE;
        }

        $row = $rows[0];
        $type = (string) $row->Type;

        $this->line('  <options=bold>payment_types.purpose</>');
        $this->line('  type = ' . $type);

        // ENUM types come back as e.g. "enum('application','acceptance','other')".
        if (preg_match("/^enum\\((.*)\\)$/i", $type, $m)) {
            $allowed = str_getcsv($m[1], ",", "'");
            $this->newLine();
            $this->line('  <options=bold>Allowed values</> (' . count($allowed) . ')');
            foreach ($allowed as $v) {
                $this->line('    - ' . $v);
            }
        } elseif (preg_match("/^set\\((.*)\\)$/i", $type, $m)) {
            $allowed = str_getcsv($m[1], ",", "'");
            $this->newLine();
            $this->line('  <options=bold>SET values (multi)</> (' . count($allowed) . ')');
            foreach ($allowed as $v) {
                $this->line('    - ' . $v);
            }
        } else {
            // varchar or similar — any string up to length limit is accepted.
            $this->newLine();
            $this->info('  This column is a free-text type (' . $type . '). Any string up to the length limit is allowed.');
        }

        return self::SUCCESS;
    }
}
