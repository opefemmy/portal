<?php
/**
 * Database Fix Script for Production Server
 *
 * Upload this file to your server's public folder and visit:
 * https://eportal.personel.ink/fix.php
 *
 * After running, DELETE this file for security!
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "<pre style='background: #1a1a1a; color: #00ff00; padding: 20px; font-family: monospace;'>";
echo "=========================================\n";
echo "PORTAL DATABASE FIX SCRIPT\n";
echo "=========================================\n\n";

$tables = [
    'hospital_wards',
    'hospital_patients',
    'hospital_staff',
    'hospital_beds',
    'hospital_appointments',
    'hospital_vital_signs',
    'hospital_medical_records',
    'hospital_diagnoses',
    'hospital_prescriptions',
    'hospital_prescription_items',
    'hospital_lab_requests',
    'hospital_lab_results',
    'hospital_admissions',
    'hospital_referrals',
    'hospital_reports',
    'hospital_drugs',
    'hospital_drug_categories',
    'hospital_drug_batches',
    'hospital_suppliers',
    'hospital_store_items',
    'hospital_store_batches',
    'hospital_purchases',
];

echo "1. Adding softDeletes to hospital tables...\n";
$fixed = 0;
foreach ($tables as $table) {
    try {
        if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
            Schema::table($table, function ($t) {
                $t->softDeletes();
            });
            echo "   ✓ Fixed: $table\n";
            $fixed++;
        }
    } catch (Exception $e) {
        echo "   ✗ Error: $table - " . $e->getMessage() . "\n";
    }
}
echo "   Total fixed: $fixed\n\n";

// 2. Create system_settings table
echo "2. Creating system_settings table...\n";
try {
    if (!Schema::hasTable('system_settings')) {
        Schema::create('system_settings', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            ['key' => 'payment_open', 'value' => '0', 'description' => 'Payment status'],
            ['key' => 'library_fee_required', 'value' => 'false', 'description' => 'Require library fee'],
            ['key' => 'library_fee_amount', 'value' => '500', 'description' => 'Library fee amount'],
            ['key' => 'library_late_fee_per_day', 'value' => '100', 'description' => 'Late fee per day'],
            ['key' => 'library_max_borrow_days', 'value' => '14', 'description' => 'Max borrow days'],
        ]);
        echo "   ✓ Created system_settings table\n";
    } else {
        echo "   ✓ system_settings already exists\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 3. Create payment_gateways table
echo "\n3. Creating payment_gateways table...\n";
try {
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
        echo "   ✓ Created payment_gateways table\n";
    } else {
        echo "   ✓ payment_gateways already exists\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 4. Create grade_classifications table
echo "\n4. Creating grade_classifications table...\n";
try {
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
        echo "   ✓ Created grade_classifications table\n";
    } else {
        echo "   ✓ grade_classifications already exists\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 5. Create grading_scales table
echo "\n5. Creating grading_scales table...\n";
try {
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
        echo "   ✓ Created grading_scales table\n";
    } else {
        echo "   ✓ grading_scales already exists\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 6. Add columns to users table
echo "\n6. Adding columns to users table...\n";
try {
    if (Schema::hasTable('users')) {
        $userCols = ['password_changed_at', 'security_question', 'security_answer', 'must_change_password', 'guidance_name', 'guidance_phone', 'guidance_address'];
        foreach ($userCols as $col) {
            if (!Schema::hasColumn('users', $col)) {
                if ($col === 'password_changed_at') {
                    Schema::table('users', function ($t) { $t->timestamp('password_changed_at')->nullable(); });
                } elseif ($col === 'must_change_password') {
                    Schema::table('users', function ($t) { $t->boolean('must_change_password')->default(false); });
                } elseif (in_array($col, ['guidance_name', 'guidance_phone'])) {
                    Schema::table('users', function ($t) use ($col) { $t->string($col)->nullable(); });
                } elseif ($col === 'guidance_address') {
                    Schema::table('users', function ($t) { $t->text('guidance_address')->nullable(); });
                } else {
                    Schema::table('users', function ($t) use ($col) { $t->string($col)->nullable(); });
                }
                echo "   ✓ Added: $col\n";
            }
        }
        echo "   ✓ Users table updated\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 7. Add columns to students table
echo "\n7. Adding columns to students table...\n";
try {
    if (Schema::hasTable('students')) {
        $studentCols = ['year_of_entry', 'library_fee_paid', 'library_fee_paid_at'];
        foreach ($studentCols as $col) {
            if (!Schema::hasColumn('students', $col)) {
                if ($col === 'year_of_entry') {
                    Schema::table('students', function ($t) { $t->integer('year_of_entry')->nullable(); });
                } elseif ($col === 'library_fee_paid') {
                    Schema::table('students', function ($t) { $t->boolean('library_fee_paid')->default(false); });
                } elseif ($col === 'library_fee_paid_at') {
                    Schema::table('students', function ($t) { $t->timestamp('library_fee_paid_at')->nullable(); });
                }
                echo "   ✓ Added: $col\n";
            }
        }
        echo "   ✓ Students table updated\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 8. Enhance regime_payments table
echo "\n8. Enhancing regime_payments table...\n";
try {
    if (Schema::hasTable('regime_payments')) {
        $regimeCols = ['payment_type', 'school_id', 'department_id', 'programme_id', 'session_id', 'semester', 'level', 'level_operator', 'portal_charge', 'include_portal_charge', 'payment_config'];
        foreach ($regimeCols as $col) {
            if (!Schema::hasColumn('regime_payments', $col)) {
                if ($col === 'payment_type') {
                    Schema::table('regime_payments', function ($t) { $t->string('payment_type')->default('school_fee'); });
                } elseif ($col === 'portal_charge') {
                    Schema::table('regime_payments', function ($t) { $t->decimal('portal_charge', 10, 2)->default(0); });
                } elseif ($col === 'include_portal_charge') {
                    Schema::table('regime_payments', function ($t) { $t->boolean('include_portal_charge')->default(false); });
                } elseif ($col === 'payment_config') {
                    Schema::table('regime_payments', function ($t) { $t->string('payment_config')->default('full'); });
                } elseif (in_array($col, ['semester', 'level_operator'])) {
                    Schema::table('regime_payments', function ($t) use ($col) { $t->string($col)->nullable(); });
                } elseif ($col === 'level') {
                    Schema::table('regime_payments', function ($t) { $t->integer('level')->nullable(); });
                } else {
                    Schema::table('regime_payments', function ($t) use ($col) { $t->foreignId($col)->nullable()->constrained()->onDelete('cascade'); });
                }
                echo "   ✓ Added: $col\n";
            }
        }
        echo "   ✓ regime_payments table updated\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=========================================\n";
echo "FIX COMPLETE!\n";
echo "=========================================\n";
echo "\n⚠️  IMPORTANT: Delete this file now for security!\n";
echo "</pre>";