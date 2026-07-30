<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicates extends Command
{
    protected $signature = 'db:cleanup-duplicates';
    protected $description = 'Remove duplicate entries from all tables';

    public function handle()
    {
        $this->info('Starting duplicate cleanup...');

        // Clean hospital_service_types
        $this->cleanupDuplicates('hospital_service_types', ['name', 'category']);

        // Clean hospital_wards
        $this->cleanupDuplicates('hospital_wards', ['name']);

        // Clean hospital_staff (by name + type)
        $this->cleanupDuplicates('hospital_staff', ['first_name', 'last_name', 'staff_type']);

        // Clean hospital_drugs
        $this->cleanupDuplicates('hospital_drugs', ['code']);

        // Clean hospital_drug_categories
        $this->cleanupDuplicates('hospital_drug_categories', ['code']);

        // Clean hospital_suppliers
        $this->cleanupDuplicates('hospital_suppliers', ['code']);

        // Clean sessions
        $this->cleanupSessions();

        // Clean payment_gateways
        $this->cleanupDuplicates('payment_gateways', ['provider']);

        $this->info('Duplicate cleanup completed!');
        return 0;
    }

    protected function cleanupDuplicates(string $table, array $uniqueFields)
    {
        $this->info("Cleaning $table...");

        // Get duplicate entries
        $duplicates = DB::table($table)
            ->select($uniqueFields)
            ->selectRaw('COUNT(*) as count')
            ->groupBy($uniqueFields)
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->line("  No duplicates found in $table");
            return;
        }

        foreach ($duplicates as $dup) {
            $conditions = [];
            foreach ($uniqueFields as $field) {
                $conditions[] = [$field, '=', $dup->$field];
            }

            // Keep the first entry, delete the rest
            $firstEntry = DB::table($table)->where($conditions)->first();
            if ($firstEntry) {
                DB::table($table)->where($conditions)
                    ->where('id', '!=', $firstEntry->id)
                    ->delete();
                $this->line("  Removed duplicates from $table: " . json_array_get($dup, $uniqueFields));
            }
        }
    }

    protected function cleanupSessions()
    {
        $this->info("Cleaning sessions...");

        // Get all sessions ordered by name, keep only one per name
        $sessions = DB::table('sessions')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->groupBy('name');

        foreach ($sessions as $name => $sessionGroup) {
            if ($sessionGroup->count() > 1) {
                // Keep the first one, delete the rest
                $keep = $sessionGroup->first()->id;
                $deleteIds = $sessionGroup->skip(1)->pluck('id');

                DB::table('sessions')->whereIn('id', $deleteIds)->delete();
                $this->line("  Removed " . ($sessionGroup->count() - 1) . " duplicate sessions: $name");
            }
        }
    }
}

function json_array_get($obj, array $fields): string
{
    $result = [];
    foreach ($fields as $field) {
        $result[] = $obj->$field ?? '';
    }
    return implode(', ', $result);
}
