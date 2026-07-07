<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure system_settings table exists
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('value');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Insert default settings if they don't exist
        $settings = [
            ['key' => 'payment_open', 'value' => '0', 'description' => 'Payment status: 0 = closed, 1 = open'],
            ['key' => 'registration_open', 'value' => '0', 'description' => 'Course registration status'],
            ['key' => 'portal_name', 'value' => 'University Portal', 'description' => 'Portal name'],
            // Library settings
            ['key' => 'library_fee_required', 'value' => 'false', 'description' => 'Require library fee before borrowing'],
            ['key' => 'library_fee_amount', 'value' => '500', 'description' => 'Library fee amount'],
            ['key' => 'library_late_fee_per_day', 'value' => '100', 'description' => 'Late fee per day for book return'],
            ['key' => 'library_max_borrow_days', 'value' => '14', 'description' => 'Maximum days to borrow a book'],
        ];

        foreach ($settings as $setting) {
            DB::table('system_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    public function down(): void
    {
        // Keep the table data
    }
};