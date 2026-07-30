<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataCleanupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Fixes duplicate sessions and ensures proper semester values.
     */
    public function run(): void
    {
        $this->command->info('Starting data cleanup...');

        // 1. Remove duplicate sessions (keep the most recent one)
        $this->fixDuplicateSessions();

        // 2. Ensure proper semester values
        $this->fixSemesters();

        // 3. Ensure no N/A values in critical fields
        $this->fixNaValues();

        $this->command->info('Data cleanup completed!');
    }

    /**
     * Fix duplicate session names - keep only one per name
     */
    protected function fixDuplicateSessions(): void
    {
        $this->command->info('Fixing duplicate sessions...');

        // Get all session names with their IDs
        $sessions = DB::table('sessions')
            ->select('name', DB::raw('MIN(id) as min_id'), DB::raw('MAX(id) as max_id'), DB::raw('COUNT(*) as count'))
            ->groupBy('name')
            ->having('count', '>', 1)
            ->get();

        foreach ($sessions as $session) {
            // Delete duplicates, keeping the one with the highest ID (most recent)
            DB::table('sessions')
                ->where('name', $session->name)
                ->where('id', '!=', $session->max_id)
                ->delete();

            $this->command->info("  Removed duplicate session: {$session->name}");
        }

        // Add unique index to prevent future duplicates
        try {
            DB::statement('ALTER TABLE sessions ADD UNIQUE INDEX unique_session_name (name)');
        } catch (\Exception $e) {
            // Index might already exist
            $this->command->info('  Session name unique index already exists or could not be added');
        }
    }

    /**
     * Ensure proper semester values (First Semester, Second Semester only)
     */
    protected function fixSemesters(): void
    {
        $this->command->info('Fixing semester values...');

        // Get all unique semester values currently in use
        $currentSemesters = DB::table('sessions')
            ->distinct()
            ->pluck('semester')
            ->filter()
            ->values();

        $this->command->info('  Current semester values: ' . $currentSemesters->implode(', '));

        // Check if we have valid semesters
        $validSemesters = ['First Semester', 'Second Semester'];

        // Update any N/A or invalid semester values
        DB::table('sessions')
            ->where(function ($query) {
                $query->whereNull('semester')
                    ->orWhere('semester', 'N/A')
                    ->orWhere('semester', '')
                    ->orWhereRaw("LOWER(semester) IN ('na', 'n/a', 'null', 'none', 'not applicable')");
            })
            ->update(['semester' => 'First Semester']);

        // Update all sessions to have proper semester values
        // Even-numbered years = First Semester, Odd = Second (example logic)
        // Or just alternate based on ID
        $sessions = DB::table('sessions')
            ->orderBy('id')
            ->get();

        $counter = 0;
        foreach ($sessions as $session) {
            $semester = $validSemesters[$counter % 2];
            DB::table('sessions')
                ->where('id', $session->id)
                ->update(['semester' => $semester]);
            $counter++;
        }

        // Ensure semesters table has proper values
        $this->fixSemestersTable();
    }

    /**
     * Fix semesters table to have proper values
     */
    protected function fixSemestersTable(): void
    {
        // Check if semesters table exists
        if (!DB::getSchemaBuilder()->hasTable('semesters')) {
            return;
        }

        // Clear and reseed with proper semesters
        DB::table('semesters')->delete();

        $semesters = [
            ['name' => 'First Semester', 'code' => 'FIRST', 'sort_order' => 1, 'is_active' => true],
            ['name' => 'Second Semester', 'code' => 'SECOND', 'sort_order' => 2, 'is_active' => true],
        ];

        foreach ($semesters as $semester) {
            DB::table('semesters')->insert(array_merge($semester, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info('  Semesters table updated with proper values');
    }

    /**
     * Fix N/A values in various tables
     */
    protected function fixNaValues(): void
    {
        $this->command->info('Fixing N/A values...');

        // Fix student_courses semester
        DB::table('student_courses')
            ->where(function ($query) {
                $query->whereNull('semester')
                    ->orWhere('semester', 'N/A')
                    ->orWhere('semester', '');
            })
            ->update(['semester' => 'First Semester']);

        // Fix results - ensure no null references
        // This is handled by the application logic, not direct fixes

        $this->command->info('  N/A values cleaned up');
    }
}
