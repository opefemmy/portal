<?php

namespace App\Console\Commands;

use App\Models\PaymentType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic for "i am unable to add a payment type" / admin
 * /admin/payment-types store failures on the live site.
 *
 *   php artisan payment-types:diagnose                # read-only
 *   php artisan payment-types:diagnose --try-create   # also test INSERT
 *
 * What it checks:
 *   1. payment_types table existence and column drift vs. the migrations
 *      that should have run (2026_07_23_000003 base,
 *      2026_07_24_000001 +purpose, 2026_08_04_000001 +audience).
 *   2. Whether the deployable controller file on disk matches the
 *      `Schema::hasColumn()` guard pattern. If the on-disk controller
 *      is the OLD version (no guard), every store() POST will 500 the
 *      moment audience is in the payload and the column doesn't exist.
 *   3. The current row count + sample row, so the admin can confirm a
 *      recently-added row landed.
 *   4. Whether all 5 admin.*.payment-types.* routes are registered.
 *   5. With --try-create, attempts a real INSERT through Eloquent and
 *      reports the SQL error verbatim if it fails.
 *
 * Why a command and not a controller route:
 *   - This is run on the live server with `php artisan`. It does not
 *     need an HTTP request.
 *   - It does NOT change anything unless --try-create is passed; even
 *     then it inserts a clearly-named test row (code = DIAG_<timestamp>)
 *     and the admin can delete it after.
 */
class DiagnosePaymentTypes extends Command
{
    protected $signature = 'payment-types:diagnose
        {--try-create : Attempt a real INSERT to capture the SQL error verbatim (inserts a DIAG_<timestamp> row).}';

    protected $description = 'Diagnose "unable to add payment type" failures on the live site';

    public function handle(): int
    {
        $issues = [];
        $this->line('  <options=bold>Payment-types diagnostic</>');
        $this->newLine();

        // --- 1. Table + column drift ------------------------------------
        $tableExists = Schema::hasTable('payment_types');
        $this->line(sprintf('  table    = payment_types (%s)', $tableExists ? 'exists' : 'MISSING'));

        if (! $tableExists) {
            $issues[] = "The payment_types table does not exist at all. Run `php artisan migrate`.";
            $this->error('  Found '.count($issues).' likely cause(s):');
            foreach ($issues as $i => $msg) {
                $this->line("    ".($i + 1).". {$msg}");
            }
            return self::SUCCESS;
        }

        $expectedColumns = [
            'name'             => 'base (2026_07_23_000003)',
            'code'             => 'base (2026_07_23_000003)',
            'description'      => 'base (2026_07_23_000003)',
            'amount'           => 'base (2026_07_23_000003)',
            'is_active'        => 'base (2026_07_23_000003)',
            'requires_payment' => 'base (2026_07_23_000003)',
            'payment_channel'  => 'base (2026_07_23_000003)',
            'priority'         => 'base (2026_07_23_000003)',
            'purpose'          => '2026_07_24_000001',
            'audience'         => '2026_08_04_000001',
        ];

        $missing = [];
        foreach ($expectedColumns as $col => $source) {
            $has = Schema::hasColumn('payment_types', $col);
            $mark = $has ? '✓' : '✗';
            $this->line(sprintf('  column %-18s = %s  (%s)', $col, $mark, $source));
            if (! $has) {
                $missing[] = $col;
            }
        }
        if ($missing) {
            $issues[] = "Missing columns: ".implode(', ', $missing).". Run `php artisan migrate` to add them. Until you do, the controller's Schema::hasColumn() guard will strip them from the INSERT and the row will save, but it won't have the full feature set.";
        }

        // --- 2. Controller code on disk ----------------------------------
        $controllerPath = app_path('Http/Controllers/Admin/PaymentTypeController.php');
        $controllerSource = is_file($controllerPath) ? file_get_contents($controllerPath) : '';
        $hasGuard = str_contains($controllerSource, 'Schema::hasColumn')
            && str_contains($controllerSource, "'audience'");
        $hasTryCatch = str_contains($controllerSource, "Log::error('admin/payment-types: create failed'");
        $this->line(sprintf('  controller = %s (hasGuard=%s hasTryCatch=%s)',
            $controllerPath,
            $hasGuard ? 'yes' : 'NO',
            $hasTryCatch ? 'yes' : 'NO'
        ));
        if (! $hasGuard) {
            $issues[] = "The on-disk PaymentTypeController.php is the OLD version (no Schema::hasColumn() guard for audience). `git pull` did not deploy the fix to this server — PHP-FPM may be holding the old bytecode in opcache. Restart PHP-FPM (e.g. `sudo systemctl restart php8.2-fpm`) and re-deploy the controller file.";
        }
        if (! $hasTryCatch) {
            $issues[] = "The on-disk controller has no try/catch around create(). The fix commit (08fe7252) added one. If the file is old, redeploy.";
        }

        // --- 3. Existing rows --------------------------------------------
        $count = PaymentType::count();
        $this->line(sprintf('  rows      = %d total', $count));
        if ($count > 0) {
            $sample = PaymentType::orderBy('id')->first();
            $this->line(sprintf('  sample    = #%d code=%s name=%s amount=%.2f audience=%s',
                $sample->id,
                $sample->code,
                $sample->name,
                (float) $sample->amount,
                $sample->audience ?? '(NULL — column missing?)'
            ));
        }

        // --- 4. Routes ---------------------------------------------------
        $routes = [
            'admin.payment-types.index',
            'admin.payment-types.create',
            'admin.payment-types.store',
            'admin.payment-types.edit',
            'admin.payment-types.update',
            'admin.payment-types.destroy',
        ];
        $missingRoutes = [];
        foreach ($routes as $name) {
            $present = \Illuminate\Support\Facades\Route::has($name);
            $mark = $present ? '✓' : '✗';
            $this->line(sprintf('  route %-30s = %s', $name, $mark));
            if (! $present) {
                $missingRoutes[] = $name;
            }
        }
        if ($missingRoutes) {
            $issues[] = "Routes not registered: ".implode(', ', $missingRoutes).". `php artisan route:clear` then `php artisan optimize:clear`.";
        }

        // --- 5. Optional real-INSERT test --------------------------------
        if ($this->option('try-create')) {
            $this->newLine();
            $this->warn('  Attempting a real INSERT with the controller-shaped payload...');
            $code = 'DIAG_'.time();
            try {
                $payload = [
                    'name' => 'Diagnostic Row',
                    'code' => $code,
                    'description' => 'Inserted by `php artisan payment-types:diagnose --try-create`. Safe to delete.',
                    'amount' => 1.00,
                    'is_active' => true,
                    'requires_payment' => true,
                    'payment_channel' => 'both',
                    'priority' => 999,
                ];
                // Mirror the controller: only include the optional columns
                // if they exist on the schema. Otherwise the INSERT would
                // throw — exactly the bug we're triaging.
                if (Schema::hasColumn('payment_types', 'purpose')) {
                    $payload['purpose'] = 'other';
                }
                if (Schema::hasColumn('payment_types', 'audience')) {
                    $payload['audience'] = 'both';
                }
                $row = PaymentType::create($payload);
                $this->info("    INSERT OK — new row #{$row->id} code={$row->code}");
                $this->line('    You can now load /admin/payment-types in the browser and confirm the row appears.');
                $this->line("    To remove: `php artisan tinker --execute=\"App\\Models\\PaymentType::where('code','{$code}')->delete();\"`");
            } catch (\Throwable $e) {
                $this->error('    INSERT FAILED: '.$e->getMessage());
                $this->line('    SQL bindings: '.json_encode($payload ?? []));
                $issues[] = "The live INSERT failed with: ".$e->getMessage();
            }
        }

        $this->newLine();

        if (! $issues) {
            $this->info('  No structural issues found. If admin/users still see "fails to add":');
            $this->line('    - Run `php artisan optimize:clear` to wipe config / route / view caches.');
            $this->line('    - Restart PHP-FPM (opcache may serve the old controller bytecode):');
            $this->line('        sudo systemctl restart php8.2-fpm');
            $this->line('    - Confirm the user has role super_admin OR admin (Middleware: role:super_admin,admin).');
            $this->line('    - In the browser devtools Network tab, look at the POST /admin/payment-types response:');
            $this->line('        * 302 to /admin/payment-types with success flash = row saved (refresh list to see it).');
            $this->line('        * 302 back with error flash = validation or schema (read the flash text).');
            $this->line('        * 419 = CSRF / session, run `php artisan session:diagnose --fix`.');
            $this->line('        * 500 = check storage/logs/laravel.log for the stack trace.');
        } else {
            $this->error('  Found '.count($issues).' likely cause(s):');
            foreach ($issues as $i => $msg) {
                $this->line("    ".($i + 1).". {$msg}");
            }
        }

        return self::SUCCESS;
    }
}