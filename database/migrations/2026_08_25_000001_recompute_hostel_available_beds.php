<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Safety-net: recompute every `hostel_rooms.available_beds` from the
 * live count of `hostel_beds` rows with status='available'.
 *
 * Symptom: the student hostel dashboard rendered every hostel as
 * "Full" even when no student had applied. Root cause was a stale
 * `hostel_rooms.available_beds` column (defaulted to 0 in the
 * original migration and never refreshed on legacy rows where the
 * student self-apply path didn't decrement it).
 *
 * Hostel::getLiveAvailableBedsAttribute() now reads the live count
 * from `hostel_beds.status='available'` at read time, so the student
 * dashboard stops showing "Full" for fresh rooms immediately. This
 * migration brings the cached `available_beds` column in line with
 * reality so anything that bypasses the accessor (raw SQL exports,
 * the admin show page's badge, the Bursar widget if any is added
 * later) sees the right value too.
 *
 * No-op when either table is missing (mirrors the local-DB safety-
 * net pattern from [[local-db-broader-hospital-tables-drift]]).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hostel_rooms') || ! Schema::hasTable('hostel_beds')) {
            return;
        }

        DB::statement(
            'UPDATE `hostel_rooms` r '
            . 'LEFT JOIN ('
            .     'SELECT `hostel_room_id`, COUNT(*) AS available_count '
            .     'FROM `hostel_beds` '
            .     "WHERE `status` = 'available' "
            .     'GROUP BY `hostel_room_id`'
            . ') b ON b.`hostel_room_id` = r.`id` '
            . 'SET r.`available_beds` = COALESCE(b.`available_count`, 0)'
        );
    }

    public function down(): void
    {
        // No-op. We don't know what the prior available_beds value was
        // (it was the bad one we just overwrote). Recomputing again is
        // always safe, so rolling back would just leave the column
        // correct.
    }
};