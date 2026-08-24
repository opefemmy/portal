<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slice N — student course registration fixes.
     *
     * Two problems this migration addresses:
     *
     * 1)  The original unique key on `student_courses` was
     *     UNIQUE (student_id, course_id, session_id)
     *     (see database/migrations/2024_01_01_000012_create_student_courses_table.php:19).
     *
     *     The controller now creates one StudentCourse row per
     *     (student, course, session, semester) tuple. Two
     *     semester='first' rows would collide on the old key —
     *     broaden the key to include `semester` so a fully-paid
     *     student can register the same course in both semesters
     *     of the same session.
     *
     * 2)  The controller used to insert `semester='both'`, which
     *     is not a value the live `student_courses.semester`
     *     ENUM('first','second') allows — every registration
     *     crashed with SQLSTATE[01000] 1265 truncation. The
     *     controller fix is in this commit (per-course semester);
     *     no schema change is needed for the ENUM, but the
     *     comment here documents the why.
     *
     * MySQL foreign-key handling:
     *   `attendances.student_course_id` and `results.student_course_id`
     *   both FK to `student_courses.id`. On a freshly-restored
     *   local database (`database_backup_20260724.sql` restored
     *   into a clean MySQL data dir) the FK index referencing
     *   pattern is preserved even when the FK constraint name no
     *   longer appears in information_schema — MySQL refuses to
     *   drop the unique index with error 1553 ("Cannot drop index
     *   'student_course_unique': needed in a foreign key
     *   constraint") until FKs are dropped, OR FK_CHECKS=0 is
     *   set. We use the FK_CHECKS=0 path because:
     *     - The FKs aren't on the local DB at all (the
     *       information_schema has 0 inbound FKs for
     *       student_courses), so dropping them is a no-op there.
     *     - Production may have the FKs intact — the FK_CHECKS=0
     *       path leaves them untouched if they're not actually
     *       referencing `student_course_unique` (which, after
     *       schema inspection of the local DB, they aren't).
     *
     * The unique key is recreated under the SAME name
     * `student_course_unique` so MySQL re-uses the slot — no
     * left-over index by another name.
     */
    public function up(): void
    {
        if (!Schema::hasTable('student_courses')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $this->dropIndexIfExists('student_courses', 'student_course_unique');

            Schema::table('student_courses', function (Blueprint $table) {
                $table->unique(
                    ['student_id', 'course_id', 'session_id', 'semester'],
                    'student_course_unique'
                );
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('student_courses')) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $this->dropIndexIfExists('student_courses', 'student_course_unique');

            Schema::table('student_courses', function (Blueprint $table) {
                $table->unique(
                    ['student_id', 'course_id', 'session_id'],
                    'student_course_unique'
                );
            });
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Drop an index by name only if it actually exists in
     * information_schema. Drops wrapped in try/catch can hide real
     * bugs (e.g. typos in the index name). A SELECT pre-check is
     * safe, fast, and idempotent.
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $db = DB::connection()->getDatabaseName();
        $exists = DB::selectOne(
            'SELECT COUNT(*) AS c
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?',
            [$db, $table, $indexName]
        );

        if ((int) ($exists->c ?? 0) === 0) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($indexName) {
            $table->dropUnique($indexName);
        });
    }
};
