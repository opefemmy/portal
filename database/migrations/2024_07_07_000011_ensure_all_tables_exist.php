<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure system_settings table exists
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function ($table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('value')->nullable();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Insert default settings
            DB::table('system_settings')->insert([
                ['key' => 'payment_open', 'value' => '0', 'description' => 'Payment status'],
                ['key' => 'library_fee_required', 'value' => 'false', 'description' => 'Require library fee'],
                ['key' => 'library_fee_amount', 'value' => '500', 'description' => 'Library fee amount'],
                ['key' => 'library_late_fee_per_day', 'value' => '100', 'description' => 'Late fee per day'],
                ['key' => 'library_max_borrow_days', 'value' => '14', 'description' => 'Max borrow days'],
            ]);
            echo "Created system_settings table\n";
        }

        // Ensure payment_gateways table exists
        if (!Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function ($table) {
                $table->id();
                $table->string('provider');
                $table->string('test_public_key')->nullable();
                $table->string('test_secret_key')->nullable();
                $table->string('live_public_key')->nullable();
                $table->string('live_secret_key')->nullable();
                $table->boolean('is_test_mode')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
            echo "Created payment_gateways table\n";
        }

        // Ensure grade_classifications table exists
        if (!Schema::hasTable('grade_classifications')) {
            Schema::create('grade_classifications', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->decimal('min_gpa', 3, 2);
                $table->decimal('max_gpa', 3, 2);
                $table->string('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            DB::table('grade_classifications')->insert([
                ['name' => 'First Class', 'slug' => 'first_class', 'min_gpa' => 4.50, 'max_gpa' => 5.00, 'description' => 'Outstanding', 'sort_order' => 1],
                ['name' => 'Second Class Upper', 'slug' => 'second_class_upper', 'min_gpa' => 3.50, 'max_gpa' => 4.49, 'description' => 'Very good', 'sort_order' => 2],
                ['name' => 'Second Class Lower', 'slug' => 'second_class_lower', 'min_gpa' => 2.50, 'max_gpa' => 3.49, 'description' => 'Good', 'sort_order' => 3],
                ['name' => 'Third Class', 'slug' => 'third_class', 'min_gpa' => 1.50, 'max_gpa' => 2.49, 'description' => 'Satisfactory', 'sort_order' => 4],
                ['name' => 'Pass', 'slug' => 'pass', 'min_gpa' => 1.00, 'max_gpa' => 1.49, 'description' => 'Minimum pass', 'sort_order' => 5],
                ['name' => 'Fail', 'slug' => 'fail', 'min_gpa' => 0.00, 'max_gpa' => 0.99, 'description' => 'Did not meet requirements', 'sort_order' => 6],
            ]);
            echo "Created grade_classifications table\n";
        }

        // Ensure grading_scales table exists
        if (!Schema::hasTable('grading_scales')) {
            Schema::create('grading_scales', function ($table) {
                $table->id();
                $table->string('grade');
                $table->integer('min_score');
                $table->integer('max_score');
                $table->decimal('grade_point', 3, 2);
                $table->decimal('gpa_weight', 3, 2)->default(1.0);
                $table->string('remark');
                $table->string('classification')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            DB::table('grading_scales')->insert([
                ['grade' => 'A', 'min_score' => 70, 'max_score' => 100, 'grade_point' => 4.00, 'gpa_weight' => 4.0, 'remark' => 'Excellent', 'classification' => 'first_class', 'sort_order' => 1],
                ['grade' => 'B', 'min_score' => 60, 'max_score' => 69, 'grade_point' => 3.50, 'gpa_weight' => 3.5, 'remark' => 'Very Good', 'classification' => 'second_class_upper', 'sort_order' => 2],
                ['grade' => 'C', 'min_score' => 50, 'max_score' => 59, 'grade_point' => 3.00, 'gpa_weight' => 3.0, 'remark' => 'Good', 'classification' => 'second_class_lower', 'sort_order' => 3],
                ['grade' => 'D', 'min_score' => 45, 'max_score' => 49, 'grade_point' => 2.50, 'gpa_weight' => 2.5, 'remark' => 'Fair', 'classification' => 'third_class', 'sort_order' => 4],
                ['grade' => 'E', 'min_score' => 40, 'max_score' => 44, 'grade_point' => 2.00, 'gpa_weight' => 2.0, 'remark' => 'Pass', 'classification' => 'pass', 'sort_order' => 5],
                ['grade' => 'F', 'min_score' => 0, 'max_score' => 39, 'grade_point' => 0.00, 'gpa_weight' => 0.0, 'remark' => 'Fail', 'classification' => 'fail', 'sort_order' => 6],
            ]);
            echo "Created grading_scales table\n";
        }

        // Add year_of_entry to students if missing
        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'year_of_entry')) {
            Schema::table('students', function ($table) {
                $table->integer('year_of_entry')->nullable();
            });
            echo "Added year_of_entry to students\n";
        }

        // Add library fields to students if missing
        if (Schema::hasTable('students') && !Schema::hasColumn('students', 'library_fee_paid')) {
            Schema::table('students', function ($table) {
                $table->boolean('library_fee_paid')->default(false);
                $table->timestamp('library_fee_paid_at')->nullable();
            });
            echo "Added library_fee_paid to students\n";
        }

        // Add user security fields if missing
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function ($table) {
                $table->timestamp('password_changed_at')->nullable();
                $table->string('security_question')->nullable();
                $table->string('security_answer')->nullable();
                $table->boolean('must_change_password')->default(false);
                $table->string('guidance_name')->nullable();
                $table->string('guidance_phone')->nullable();
                $table->text('guidance_address')->nullable();
            });
            echo "Added security fields to users\n";
        }

        echo "All checks completed!\n";
    }

    public function down(): void
    {
        //
    }
};