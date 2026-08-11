<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Per-user additional-roles pivot.
 *
 * `users.role_id` remains the primary role used by RoleMiddleware,
 * LoginController::authenticated, and AuthService::getRedirectUrl —
 * changing the primary role is still a single-column write.
 *
 * The pivot lets `super_admin` attach additional roles to a user
 * (e.g. make `burtest` both `bursar` AND `cashier`) without rewriting
 * the 19 callers that read `$user->role->slug`. Future PRs can opt
 * those callers into reading the pivot; this migration only adds the
 * storage and backfills the existing users so the index page renders
 * consistently from day one.
 *
 * The backfill copies every existing user's `role_id` into the pivot
 * via `insertOrIgnore` so it's safe to re-run the migration if needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $t->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            // We only stamp when the membership was added. No need
            // for an updated_at — the pivot is a set, not a history.
            $t->timestamp('created_at')->useCurrent();

            $t->unique(['user_id', 'role_id']);
            $t->index(['role_id', 'user_id']);
        });

        // Backfill: every existing user's primary role becomes a
        // pivot row so the relationship is populated from day one.
        // `insertOrIgnore` makes the backfill idempotent — if the
        // migration is re-run (e.g. after a partial failure) the
        // unique index will silently skip duplicates.
        DB::table('users')
            ->whereNotNull('role_id')
            ->orderBy('id')
            ->chunkById(200, function ($users) {
                $now = now();
                $rows = [];
                foreach ($users as $u) {
                    $rows[] = [
                        'user_id'    => $u->id,
                        'role_id'    => $u->role_id,
                        'created_at' => $now,
                    ];
                }
                if (!empty($rows)) {
                    DB::table('role_user')->insertOrIgnore($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};