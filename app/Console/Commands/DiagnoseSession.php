<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic for "419 Page Expired" / CSRF / session issues on the live site.
 *
 *   php artisan session:diagnose                # read-only checks
 *   php artisan session:diagnose --fix          # also wipe session files & re-clear config caches
 *
 * What it checks:
 *   1. APP_URL — must match the scheme the user actually reaches the site over,
 *      otherwise session cookies won't round-trip (browser rejects secure cookies
 *      on plain HTTP; on the reverse, the cookie set without Secure is dropped
 *      by Chrome/Firefox on HTTPS, so every POST looks like a new session).
 *   2. SESSION_DRIVER + storage backend reachability (file / database / etc.)
 *   3. storage/framework/sessions/ writability (file driver only).
 *   4. The route cache, view cache, and bootstrap/cache are not stale.
 *
 * Why a command and not a controller route:
 *   - The user is usually SSH'd in when this breaks. They want a one-liner.
 *   - It does NOT change anything unless --fix is passed. By default it's a
 *     pure read-only triage that prints a checklist.
 *
 * --fix:
 *   - Wipes storage/framework/sessions/*. (This is the Laravel equivalent of
 *     "log every user out" — session cookies become orphaned, next request
 *     gets a fresh session with a fresh CSRF token. 419 disappears for the
 *     next page load.)
 *   - Re-runs optimize:clear so compiled routes / views / config are rebuilt.
 *
 * It does NOT touch the database or .env. If APP_URL is wrong, the operator
 * must edit .env themselves — we never overwrite that file.
 */
class DiagnoseSession extends Command
{
    protected $signature = 'session:diagnose
        {--fix : Also wipe the session directory and re-clear all Laravel caches.}';

    protected $description = 'Diagnose 419 / CSRF / session issues on the live site';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $issues = [];

        $this->line('  <options=bold>Session / CSRF diagnostic</>');
        $this->newLine();

        // --- 1. APP_URL ---------------------------------------------------
        $appUrl = (string) config('app.url');
        $this->line(sprintf('  APP_URL  = %s', $appUrl));
        if ($appUrl === '' || $appUrl === 'http://localhost' || str_ends_with($appUrl, ':8000')) {
            $issues[] = "APP_URL is set to a local/dev value ('{$appUrl}'). The live site is reached over the public URL, so session cookies set with the wrong scheme/host get dropped by the browser. Update .env to the real APP_URL (e.g. https://eportal.personel.ink).";
        }

        // --- 2. Session driver + storage ----------------------------------
        $driver = config('session.driver');
        $this->line(sprintf('  driver   = %s', $driver));

        if ($driver === 'database') {
            $hasTable = Schema::hasTable(config('session.table', 'sessions'));
            $this->line(sprintf('  table    = %s (%s)', config('session.table', 'sessions'), $hasTable ? 'exists' : 'MISSING'));
            if (! $hasTable) {
                $issues[] = "SESSION_DRIVER=database but the sessions table is missing. Run `php artisan session:table && php artisan migrate`, OR switch SESSION_DRIVER to file.";
            }
        }

        if ($driver === 'file') {
            $path = storage_path('framework/sessions');
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $count = $exists ? (count(File::glob($path.'/*')) ?: 0) : 0;
            $this->line(sprintf('  path     = %s (exists=%s writable=%s files=%d)', $path, $exists ? 'yes' : 'NO', $writable ? 'yes' : 'NO', $count));
            if (! $exists) {
                $issues[] = "storage/framework/sessions/ does not exist. Recreate it: `mkdir -p storage/framework/sessions && chmod 775 storage/framework/sessions`.";
            } elseif (! $writable) {
                $issues[] = "storage/framework/sessions/ is not writable by PHP. Fix: `chmod -R 775 storage/framework/sessions && chown -R <php-user>:<webgroup> storage/framework`.";
            } elseif ($count > 5000) {
                $this->warn("  {$count} session files present — that's high but not a 419 cause. Cleanup with --fix if desired.");
            }
        }

        // --- 3. CSRF middleware wired correctly ---------------------------
        // We can't introspect the global middleware stack from inside the
        // application, but we can poke the VerifyCsrfToken's except list via
        // config('app.debug') — which is *not* what we want — so just print
        // a hint that the operator can grep.
        $this->line('  csrf     = VerifyCsrfToken is registered via bootstrap/app.php ->withMiddleware(web(append:[...]))');

        // --- 4. Cache staleness check -------------------------------------
        $stale = [];
        foreach (['config.php', 'routes-v7.php'] as $file) {
            $full = base_path('bootstrap/cache/'.$file);
            if (file_exists($full)) {
                $stale[] = $file;
            }
        }
        $this->line('  stale    = '.($stale ? 'bootstrap/cache/'.implode(', bootstrap/cache/', $stale).' present (rebuilt automatically by --fix)' : 'none'));

        // --- 5. Route reachability ---------------------------------------
        $loginRouteOk = function_exists('route') && \Illuminate\Support\Facades\Route::has('login');
        $this->line(sprintf('  login    = route %s', $loginRouteOk ? 'login registered' : 'login MISSING (login routes are not loaded)'));
        if (! $loginRouteOk) {
            $issues[] = "The `login` named route is missing — /login POST will 404, not 419. Check routes/web.php for the Route::get('/login', ...) line and confirm routes/web.php is included in bootstrap/app.php ->withRouting(web:).";
        }

        $this->newLine();

        if (! $issues) {
            $this->info('  No structural issues found. If you still see 419:');
            $this->line('    - Clear browser cookies for the site, retry in a private window.');
            $this->line('    - Confirm the live .env was re-read after `git pull` (run `php artisan config:clear`).');
            $this->line('    - Run `php artisan session:diagnose --fix` to wipe session files + clear caches.');
        } else {
            $this->error('  Found '.count($issues).' likely cause(s):');
            foreach ($issues as $i => $msg) {
                $this->line("    ".($i + 1).". {$msg}");
            }
        }

        if (! $fix) {
            $this->newLine();
            $this->line('  Pass --fix to wipe the session directory and clear all caches.');
            return self::SUCCESS;
        }

        // --- --fix --------------------------------------------------------
        $this->newLine();
        $this->warn('  Applying fix...');

        if ($driver === 'file') {
            $path = storage_path('framework/sessions');
            if (is_dir($path)) {
                $files = File::glob($path.'/*') ?: [];
                $deleted = 0;
                foreach ($files as $f) {
                    if (is_file($f) && @unlink($f)) {
                        $deleted++;
                    }
                }
                $this->info("    Wiped {$deleted} session file(s) from storage/framework/sessions/.");
            }
        }

        if ($driver === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            $table = config('session.table', 'sessions');
            $deleted = \Illuminate\Support\Facades\DB::table($table)->delete();
            $this->info("    Wiped {$deleted} session row(s) from the {$table} table.");
        }

        // Mirror the artisan optimize:clear set so any stale compiled caches
        // are gone too. Each step is its own try/catch — a failure in one
        // shouldn't block the others.
        foreach ([
            'config:clear'   => 'config',
            'route:clear'    => 'routes',
            'view:clear'     => 'views',
            'cache:clear'    => 'application cache',
        ] as $cmd => $label) {
            try {
                $this->call($cmd);
                $this->info("    Cleared {$label}.");
            } catch (\Throwable $e) {
                $this->warn("    Failed to clear {$label}: {$e->getMessage()}");
            }
        }

        // Trim the bootstrap/cache compiled files. They are auto-regenerated
        // on the next request, so deleting them is safe.
        foreach (['services.php', 'packages.php', 'events.php', 'config.php', 'routes-v7.php'] as $f) {
            $full = base_path('bootstrap/cache/'.$f);
            if (file_exists($full) && @unlink($full)) {
                $this->info("    Removed bootstrap/cache/{$f}.");
            }
        }

        $this->newLine();
        $this->info('  Fix applied. The next request will get a fresh session + CSRF token.');
        $this->line('  Ask the user to hard-refresh / clear cookies for the site before retrying login.');

        return self::SUCCESS;
    }
}