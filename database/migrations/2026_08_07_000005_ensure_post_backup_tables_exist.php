<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only safety net for tables that the post-backup CREATE-TABLE
 * migrations were supposed to add but were skipped when the local DB
 * was restored from `database_backup_20260724.sql`.
 *
 * During the restore the 28 batch-2 migrations (2024_06_16 … 2024_07_08)
 * were marked as already-applied so re-creating tables the backup
 * already contained would be skipped. But the backup only covers the
 * core portal tables — it does NOT include any of the hospital,
 * finance, audit, pharmacy, hostel (extra tables), system, or
 * `applications` tables. So those tables never landed locally.
 *
 * Production already has every one of these tables (so this is a no-op
 * there). If the original migrations are ever re-run against local they
 * will crash on `CREATE TABLE` because the tables now exist — that's
 * fine; this migration absorbs them.
 *
 * Every table is wrapped in `Schema::hasTable` so re-running is safe.
 * down() is intentionally a no-op; production keeps these tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // 2024_07_06_000001_create_hospital_tables
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_wards')) {
            Schema::create('hospital_wards', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('type')->comment('general, private, emergency, maternity, etc.');
                $t->integer('total_beds');
                $t->integer('available_beds')->default(0);
                $t->decimal('daily_rate', 12, 2)->default(0);
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_patients')) {
            Schema::create('hospital_patients', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('patient_number')->unique();
                $t->string('first_name');
                $t->string('last_name');
                $t->string('other_name')->nullable();
                $t->string('gender', 10);
                $t->date('date_of_birth');
                $t->string('blood_group')->nullable();
                $t->string('genotype')->nullable();
                $t->string('phone');
                $t->string('email')->nullable();
                $t->text('address');
                $t->string('state')->nullable();
                $t->string('lga')->nullable();
                $t->string('nationality')->default('Nigerian');
                $t->string('next_of_kin_name');
                $t->string('next_of_kin_phone');
                $t->string('next_of_kin_relationship');
                $t->text('next_of_kin_address')->nullable();
                $t->string('patient_type', 20)->default('student');
                $t->foreignId('registered_by')->constrained('users')->cascadeOnDelete();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_staff')) {
            Schema::create('hospital_staff', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('staff_number')->unique();
                $t->string('first_name');
                $t->string('last_name');
                $t->string('staff_type', 30);
                $t->string('specialization')->nullable();
                $t->string('license_number')->nullable();
                $t->date('license_expiry')->nullable();
                $t->string('phone');
                $t->string('email')->nullable();
                $t->text('address')->nullable();
                $t->string('gender', 10)->nullable();
                $t->boolean('is_available')->default(true);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_beds')) {
            Schema::create('hospital_beds', function (Blueprint $t) {
                $t->id();
                $t->foreignId('ward_id')->constrained('hospital_wards')->cascadeOnDelete();
                $t->string('bed_number');
                $t->string('status', 30)->default('available');
                $t->unsignedBigInteger('patient_id')->nullable();
                $t->dateTime('occupied_at')->nullable();
                $t->dateTime('discharged_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_appointments')) {
            Schema::create('hospital_appointments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->foreignId('scheduled_by')->constrained('users')->cascadeOnDelete();
                $t->dateTime('appointment_date');
                $t->time('appointment_time');
                $t->string('status', 60)->default('scheduled');
                $t->text('complaint')->nullable();
                $t->text('notes')->nullable();
                $t->dateTime('checked_in_at')->nullable();
                $t->dateTime('completed_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_vital_signs')) {
            Schema::create('hospital_vital_signs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('recorded_by')->constrained('hospital_staff')->cascadeOnDelete();
                $t->decimal('temperature', 4, 1)->nullable();
                $t->string('blood_pressure_systolic')->nullable();
                $t->string('blood_pressure_diastolic')->nullable();
                $t->decimal('weight', 5, 2)->nullable();
                $t->decimal('height', 5, 2)->nullable();
                $t->integer('pulse')->nullable();
                $t->integer('oxygen_level')->nullable();
                $t->decimal('blood_sugar', 5, 2)->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_medical_records')) {
            Schema::create('hospital_medical_records', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->nullable()->constrained('hospital_staff')->nullOnDelete();
                $t->unsignedBigInteger('appointment_id')->nullable();
                $t->text('chief_complaint')->nullable();
                $t->text('symptoms')->nullable();
                $t->text('examination_findings')->nullable();
                $t->text('doctor_notes')->nullable();
                $t->text('treatment_plan')->nullable();
                $t->dateTime('consultation_date');
                $t->string('visit_type', 20)->default('new');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_diagnoses')) {
            Schema::create('hospital_diagnoses', function (Blueprint $t) {
                $t->id();
                $t->foreignId('medical_record_id')->constrained('hospital_medical_records')->cascadeOnDelete();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->string('icd_code')->nullable();
                $t->string('diagnosis');
                $t->text('description')->nullable();
                $t->string('severity', 20)->nullable();
                $t->string('type', 20)->default('primary');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_prescriptions')) {
            Schema::create('hospital_prescriptions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->unsignedBigInteger('medical_record_id')->nullable();
                $t->text('notes')->nullable();
                $t->string('status', 30)->default('pending');
                $t->unsignedBigInteger('dispensed_by')->nullable();
                $t->dateTime('dispensed_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_prescription_items')) {
            Schema::create('hospital_prescription_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('prescription_id')->constrained('hospital_prescriptions')->cascadeOnDelete();
                $t->unsignedBigInteger('drug_id')->nullable();
                $t->string('drug_name');
                $t->string('dosage');
                $t->string('frequency');
                $t->string('duration');
                $t->string('quantity')->nullable();
                $t->text('instructions')->nullable();
                $t->boolean('is_dispensed')->default(false);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_lab_requests')) {
            Schema::create('hospital_lab_requests', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->unsignedBigInteger('medical_record_id')->nullable();
                $t->string('test_type');
                $t->text('clinical_notes')->nullable();
                $t->string('status', 30)->default('pending');
                $t->dateTime('requested_at');
                $t->dateTime('completed_at')->nullable();
                $t->decimal('amount', 10, 2)->default(0);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_lab_results')) {
            Schema::create('hospital_lab_results', function (Blueprint $t) {
                $t->id();
                $t->foreignId('lab_request_id')->constrained('hospital_lab_requests')->cascadeOnDelete();
                $t->unsignedBigInteger('recorded_by')->nullable();
                $t->string('test_name');
                $t->string('parameter')->nullable();
                $t->string('result')->nullable();
                $t->string('unit')->nullable();
                $t->string('reference_range')->nullable();
                $t->string('status')->comment('normal, abnormal, critical');
                $t->text('notes')->nullable();
                $t->dateTime('recorded_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_admissions')) {
            Schema::create('hospital_admissions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('doctor_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->unsignedBigInteger('bed_id')->nullable();
                $t->string('admission_number')->unique();
                $t->dateTime('admission_date');
                $t->dateTime('discharge_date')->nullable();
                $t->string('status', 20)->default('admitted');
                $t->text('reason')->nullable();
                $t->text('diagnosis')->nullable();
                $t->text('treatment_plan')->nullable();
                $t->text('discharge_notes')->nullable();
                $t->decimal('daily_rate', 12, 2)->default(0);
                $t->decimal('total_charges', 12, 2)->default(0);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_referrals')) {
            Schema::create('hospital_referrals', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('referrer_id')->constrained('hospital_staff')->cascadeOnDelete();
                $t->unsignedBigInteger('referred_to_id')->nullable();
                $t->string('external_facility')->nullable();
                $t->text('reason');
                $t->text('notes')->nullable();
                $t->string('status', 20)->default('pending');
                $t->dateTime('referred_at');
                $t->dateTime('accepted_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_reports')) {
            Schema::create('hospital_reports', function (Blueprint $t) {
                $t->id();
                $t->foreignId('patient_id')->constrained('hospital_patients')->cascadeOnDelete();
                $t->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
                $t->string('report_type')->comment('medical_report, medical_certificate, lab_report, discharge_summary');
                $t->string('title');
                $t->text('content')->nullable();
                $t->string('file_path')->nullable();
                $t->string('status', 20)->default('draft');
                $t->dateTime('released_at')->nullable();
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2024_07_06_000002_create_hospital_pharmacy_tables
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('hospital_suppliers')) {
            Schema::create('hospital_suppliers', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('code')->unique();
                $t->string('contact_person')->nullable();
                $t->string('phone');
                $t->string('email')->nullable();
                $t->text('address')->nullable();
                $t->string('bank_name')->nullable();
                $t->string('account_number')->nullable();
                $t->string('account_name')->nullable();
                $t->text('notes')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_drug_categories')) {
            Schema::create('hospital_drug_categories', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('code')->unique();
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_drugs')) {
            Schema::create('hospital_drugs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('category_id')->nullable()->constrained('hospital_drug_categories')->nullOnDelete();
                $t->string('name');
                $t->string('generic_name')->nullable();
                $t->string('code')->unique();
                $t->string('form');
                $t->string('strength')->nullable();
                $t->string('unit');
                $t->decimal('cost_price', 10, 2)->default(0);
                $t->decimal('selling_price', 10, 2)->default(0);
                $t->integer('reorder_level')->default(10);
                $t->integer('current_stock')->default(0);
                $t->text('storage_location')->nullable();
                $t->text('side_effects')->nullable();
                $t->text('contraindications')->nullable();
                $t->text('instructions')->nullable();
                $t->boolean('requires_prescription')->default(true);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_drug_batches')) {
            Schema::create('hospital_drug_batches', function (Blueprint $t) {
                $t->id();
                $t->foreignId('drug_id')->nullable()->constrained('hospital_drugs')->nullOnDelete();
                $t->string('batch_number')->unique();
                $t->integer('quantity');
                $t->integer('remaining_quantity');
                $t->decimal('unit_cost', 10, 2);
                $t->date('manufacture_date')->nullable();
                $t->date('expiry_date');
                $t->date('received_date');
                $t->foreignId('supplier_id')->nullable()->constrained('hospital_suppliers')->nullOnDelete();
                $t->string('status', 20)->default('active')->comment('active, expired, depleted');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_inventory_movements')) {
            Schema::create('hospital_inventory_movements', function (Blueprint $t) {
                $t->id();
                $t->foreignId('drug_id')->nullable()->constrained('hospital_drugs')->nullOnDelete();
                $t->foreignId('batch_id')->nullable()->constrained('hospital_drug_batches')->nullOnDelete();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('movement_type', 30)->comment('purchase, sale, adjustment, expired, returned, transfer');
                $t->integer('quantity');
                $t->integer('quantity_before');
                $t->integer('quantity_after');
                $t->decimal('unit_cost', 10, 2)->nullable();
                $t->text('reference')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_store_items')) {
            Schema::create('hospital_store_items', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('code')->unique();
                $t->string('category')->nullable();
                $t->string('unit')->nullable();
                $t->decimal('cost_price', 10, 2)->default(0);
                $t->decimal('selling_price', 10, 2)->default(0);
                $t->integer('current_stock')->default(0);
                $t->integer('reorder_level')->default(10);
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_store_batches')) {
            Schema::create('hospital_store_batches', function (Blueprint $t) {
                $t->id();
                $t->foreignId('item_id')->nullable()->constrained('hospital_store_items')->nullOnDelete();
                $t->string('batch_number')->unique();
                $t->integer('quantity');
                $t->integer('remaining_quantity');
                $t->decimal('unit_cost', 10, 2);
                $t->date('manufacture_date')->nullable();
                $t->date('expiry_date')->nullable();
                $t->date('received_date');
                $t->foreignId('supplier_id')->nullable()->constrained('hospital_suppliers')->nullOnDelete();
                $t->string('status', 20)->default('active')->comment('active, expired, depleted');
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_purchases')) {
            Schema::create('hospital_purchases', function (Blueprint $t) {
                $t->id();
                $t->string('purchase_number')->unique();
                $t->foreignId('supplier_id')->nullable()->constrained('hospital_suppliers')->nullOnDelete();
                $t->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $t->date('purchase_date');
                $t->date('expected_delivery')->nullable();
                $t->date('actual_delivery')->nullable();
                $t->decimal('subtotal', 12, 2)->default(0);
                $t->decimal('tax', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);
                $t->string('status', 30)->default('pending')->comment('pending, approved, ordered, received, cancelled');
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('hospital_purchase_items')) {
            Schema::create('hospital_purchase_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('purchase_id')->nullable()->constrained('hospital_purchases')->cascadeOnDelete();
                $t->unsignedBigInteger('item_id')->nullable();
                $t->string('item_type');
                $t->string('item_name');
                $t->integer('quantity');
                $t->decimal('unit_cost', 10, 2);
                $t->decimal('total', 10, 2);
                $t->string('batch_number')->nullable();
                $t->date('expiry_date')->nullable();
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2024_07_06_000003_create_finance_tables
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('finance_ledgers')) {
            Schema::create('finance_ledgers', function (Blueprint $t) {
                $t->id();
                $t->string('code')->unique();
                $t->string('name');
                $t->string('type')->comment('asset, liability, income, expense');
                $t->string('category')->nullable();
                $t->foreignId('parent_id')->nullable()->constrained('finance_ledgers')->nullOnDelete();
                $t->decimal('opening_balance', 12, 2)->default(0);
                $t->decimal('balance', 12, 2)->default(0);
                $t->boolean('is_active')->default(true);
                $t->boolean('allow_manual_entry')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_allowances')) {
            Schema::create('finance_allowances', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('code')->unique();
                $t->decimal('amount', 10, 2);
                $t->string('type', 20)->comment('fixed, percentage');
                $t->boolean('is_taxable')->default(true);
                $t->boolean('is_active')->default(true);
                $t->text('description')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_deductions')) {
            Schema::create('finance_deductions', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('code')->unique();
                $t->decimal('amount', 10, 2);
                $t->string('type', 20)->comment('fixed, percentage');
                $t->string('calculation_base')->nullable();
                $t->boolean('is_active')->default(true);
                $t->text('description')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_vendors')) {
            Schema::create('finance_vendors', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('code')->unique();
                $t->string('contact_person')->nullable();
                $t->string('phone');
                $t->string('email')->nullable();
                $t->text('address')->nullable();
                $t->string('bank_name')->nullable();
                $t->string('account_number')->nullable();
                $t->string('account_name')->nullable();
                $t->string('tax_id')->nullable();
                $t->boolean('is_active')->default(true);
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_invoices')) {
            Schema::create('finance_invoices', function (Blueprint $t) {
                $t->id();
                $t->string('invoice_number')->unique();
                $t->foreignId('student_id')->nullable()->constrained('users')->cascadeOnDelete();
                $t->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
                $t->foreignId('session_id')->nullable()->constrained('sessions')->nullOnDelete();
                $t->string('payment_type')->comment('school_fees, hostel_fees, medical_fees, acceptance_fee, other');
                $t->string('description');
                $t->decimal('amount', 12, 2);
                $t->decimal('amount_paid', 12, 2)->default(0);
                $t->decimal('balance', 12, 2)->default(0);
                $t->decimal('discount', 12, 2)->default(0);
                $t->decimal('penalty', 12, 2)->default(0);
                $t->string('status', 30)->default('pending')->comment('pending, partial, paid, overdue, cancelled');
                $t->date('due_date')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_receipts')) {
            Schema::create('finance_receipts', function (Blueprint $t) {
                $t->id();
                $t->string('receipt_number')->unique();
                $t->foreignId('invoice_id')->nullable()->constrained('finance_invoices')->nullOnDelete();
                $t->foreignId('student_id')->nullable()->constrained('users')->cascadeOnDelete();
                $t->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $t->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
                $t->decimal('amount', 12, 2);
                $t->decimal('amount_received', 12, 2);
                $t->decimal('change_given', 12, 2)->default(0);
                $t->string('payment_method')->comment('cash, bank_transfer, cheque, pos, online');
                $t->string('reference_number')->nullable();
                $t->string('bank_name')->nullable();
                $t->string('cheque_number')->nullable();
                $t->date('payment_date');
                $t->text('notes')->nullable();
                $t->boolean('is_verified')->default(false);
                $t->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $t->dateTime('verified_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_transactions')) {
            Schema::create('finance_transactions', function (Blueprint $t) {
                $t->id();
                $t->string('transaction_number')->unique();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->foreignId('session_id')->nullable()->constrained('sessions')->nullOnDelete();
                $t->string('type')->comment('credit, debit');
                $t->string('category')->comment('income, expense');
                $t->string('ledger_code');
                $t->string('description');
                $t->decimal('amount', 12, 2);
                $t->decimal('balance', 12, 2)->default(0);
                $t->string('reference_type')->nullable();
                $t->unsignedBigInteger('reference_id')->nullable();
                $t->date('transaction_date');
                $t->string('status', 20)->default('pending')->comment('pending, posted, cancelled');
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_refunds')) {
            Schema::create('finance_refunds', function (Blueprint $t) {
                $t->id();
                $t->string('refund_number')->unique();
                $t->foreignId('student_id')->nullable()->constrained('users')->cascadeOnDelete();
                $t->foreignId('receipt_id')->nullable()->constrained('finance_receipts')->nullOnDelete();
                $t->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $t->decimal('amount', 12, 2);
                $t->text('reason');
                $t->string('status', 30)->default('pending')->comment('pending, approved, rejected, processed');
                $t->string('payment_method')->nullable();
                $t->string('reference_number')->nullable();
                $t->dateTime('approved_at')->nullable();
                $t->dateTime('processed_at')->nullable();
                $t->text('rejection_reason')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_cash_book')) {
            Schema::create('finance_cash_book', function (Blueprint $t) {
                $t->id();
                $t->string('entry_number')->unique();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('type')->comment('receipt, payment');
                $t->date('date');
                $t->string('description');
                $t->decimal('cash_in', 12, 2)->default(0);
                $t->decimal('cash_out', 12, 2)->default(0);
                $t->decimal('balance', 12, 2)->default(0);
                $t->string('reference_type')->nullable();
                $t->unsignedBigInteger('reference_id')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_department_ledgers')) {
            Schema::create('finance_department_ledgers', function (Blueprint $t) {
                $t->id();
                $t->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $t->foreignId('ledger_id')->constrained('finance_ledgers')->cascadeOnDelete();
                $t->decimal('allocation', 12, 2)->default(0);
                $t->decimal('spent', 12, 2)->default(0);
                $t->decimal('balance', 12, 2)->default(0);
                $t->unsignedBigInteger('fiscal_year_id')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_budgets')) {
            Schema::create('finance_budgets', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('fiscal_year');
                $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $t->decimal('total_budget', 12, 2);
                $t->decimal('total_spent', 12, 2)->default(0);
                $t->decimal('balance', 12, 2)->default(0);
                $t->date('start_date');
                $t->date('end_date');
                $t->string('status', 20)->default('draft')->comment('draft, approved, active, closed');
                $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $t->dateTime('approved_at')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_budget_allocations')) {
            Schema::create('finance_budget_allocations', function (Blueprint $t) {
                $t->id();
                $t->foreignId('budget_id')->constrained('finance_budgets')->cascadeOnDelete();
                $t->foreignId('ledger_id')->constrained('finance_ledgers')->cascadeOnDelete();
                $t->decimal('allocated_amount', 12, 2);
                $t->decimal('spent_amount', 12, 2)->default(0);
                $t->decimal('balance', 12, 2)->default(0);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_payroll')) {
            Schema::create('finance_payroll', function (Blueprint $t) {
                $t->id();
                $t->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
                $t->string('month');
                $t->string('year');
                $t->decimal('basic_salary', 12, 2);
                $t->decimal('total_allowances', 12, 2)->default(0);
                $t->decimal('total_deductions', 12, 2)->default(0);
                $t->decimal('gross_salary', 12, 2);
                $t->decimal('net_salary', 12, 2);
                $t->decimal('tax_deducted', 12, 2)->default(0);
                $t->decimal('pension_deducted', 12, 2)->default(0);
                $t->string('status', 30)->default('draft')->comment('draft, calculated, approved, paid');
                $t->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
                $t->dateTime('processed_at')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_staff_allowances')) {
            Schema::create('finance_staff_allowances', function (Blueprint $t) {
                $t->id();
                $t->foreignId('payroll_id')->constrained('finance_payroll')->cascadeOnDelete();
                $t->foreignId('allowance_id')->constrained('finance_allowances')->cascadeOnDelete();
                $t->decimal('amount', 10, 2);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_staff_deductions')) {
            Schema::create('finance_staff_deductions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('payroll_id')->constrained('finance_payroll')->cascadeOnDelete();
                $t->foreignId('deduction_id')->constrained('finance_deductions')->cascadeOnDelete();
                $t->decimal('amount', 10, 2);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_purchase_orders')) {
            Schema::create('finance_purchase_orders', function (Blueprint $t) {
                $t->id();
                $t->string('po_number')->unique();
                $t->foreignId('vendor_id')->constrained('finance_vendors')->cascadeOnDelete();
                $t->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $t->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
                $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $t->date('order_date');
                $t->date('expected_delivery')->nullable();
                $t->decimal('subtotal', 12, 2)->default(0);
                $t->decimal('tax', 12, 2)->default(0);
                $t->decimal('total', 12, 2)->default(0);
                $t->string('status', 40)->default('draft')->comment('draft, pending, approved, rejected, received, paid, cancelled');
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('finance_vendor_payments')) {
            Schema::create('finance_vendor_payments', function (Blueprint $t) {
                $t->id();
                $t->string('payment_number')->unique();
                $t->foreignId('vendor_id')->constrained('finance_vendors')->cascadeOnDelete();
                $t->foreignId('po_id')->nullable()->constrained('finance_purchase_orders')->nullOnDelete();
                $t->foreignId('processed_by')->constrained('users')->cascadeOnDelete();
                $t->decimal('amount', 12, 2);
                $t->string('payment_method')->comment('cash, cheque, bank_transfer, pos');
                $t->string('reference_number')->nullable();
                $t->string('cheque_number')->nullable();
                $t->date('payment_date');
                $t->string('status', 30)->default('pending')->comment('pending, approved, released, cancelled');
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2024_07_06_000004_create_audit_tables
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('module')->comment('hospital, finance, student, staff, etc.');
                $t->string('action');
                $t->string('description')->nullable();
                $t->string('entity_type')->nullable();
                $t->unsignedBigInteger('entity_id')->nullable();
                $t->json('old_values')->nullable();
                $t->json('new_values')->nullable();
                $t->string('ip_address')->nullable();
                $t->string('user_agent')->nullable();
                $t->string('computer_name')->nullable();
                $t->string('status', 20)->default('success')->comment('success, failed');
                $t->text('error_message')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('deleted_records')) {
            Schema::create('deleted_records', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('table_name');
                $t->unsignedBigInteger('record_id');
                $t->json('record_data');
                $t->text('deletion_reason')->nullable();
                $t->string('ip_address')->nullable();
                $t->string('user_agent')->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('approval_workflows')) {
            Schema::create('approval_workflows', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('module');
                $t->string('approval_type')->comment('budget, refund, payment, etc.');
                $t->integer('level')->default(1);
                $t->foreignId('approver_role_id')->constrained('roles')->cascadeOnDelete();
                $t->decimal('min_amount', 12, 2)->nullable();
                $t->decimal('max_amount', 12, 2)->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('approval_requests')) {
            Schema::create('approval_requests', function (Blueprint $t) {
                $t->id();
                $t->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
                $t->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
                $t->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('reference_type')->nullable();
                $t->unsignedBigInteger('reference_id')->nullable();
                $t->string('description');
                $t->decimal('amount', 12, 2)->nullable();
                $t->string('status', 20)->default('pending')->comment('pending, approved, rejected, cancelled');
                $t->text('remarks')->nullable();
                $t->text('rejection_reason')->nullable();
                $t->dateTime('requested_at');
                $t->dateTime('responded_at')->nullable();
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2024_07_07_200004_create_missing_tables
        // (already guarded with hasTable in the original; added for safety)
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('portal_notifications')) {
            Schema::create('portal_notifications', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('title');
                $t->text('message');
                $t->string('type')->default('info');
                $t->string('link')->nullable();
                $t->boolean('is_read')->default(false);
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('applications')) {
            Schema::create('applications', function (Blueprint $t) {
                $t->id();
                $t->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
                $t->string('application_number')->unique();
                $t->string('programme Applied')->nullable();
                $t->string('status')->default('pending');
                $t->text('notes')->nullable();
                $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamp('reviewed_at')->nullable();
                $t->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2024_07_08_000001_create_system_versions_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('system_versions')) {
            Schema::create('system_versions', function (Blueprint $t) {
                $t->id();
                $t->string('version', 20);
                $t->string('release_name', 100)->nullable();
                $t->date('release_date')->nullable();
                $t->text('description')->nullable();
                $t->string('migration_status', 20)->default('pending')->comment('pending, running, completed, failed');
                $t->string('installed_by', 100)->nullable();
                $t->timestamp('installed_at')->nullable();
                $t->boolean('is_current')->default(false);
                $t->timestamps();
                $t->unique('version');
            });
        }

        // ─────────────────────────────────────────────────────────────────
        // 2024_07_08_000002_create_system_backups_table
        // ─────────────────────────────────────────────────────────────────
        if (! Schema::hasTable('system_backups')) {
            Schema::create('system_backups', function (Blueprint $t) {
                $t->id();
                $t->string('name', 100);
                $t->string('type', 50); // database, files, storage, config
                $t->string('file_path', 255)->nullable();
                $t->string('file_size', 50)->nullable();
                $t->string('status', 30)->default('pending')->comment('pending, in_progress, completed, failed');
                $t->text('error_message')->nullable();
                $t->string('created_by', 100)->nullable();
                $t->timestamps();
            });
        }
        if (! Schema::hasTable('system_health_logs')) {
            Schema::create('system_health_logs', function (Blueprint $t) {
                $t->id();
                $t->string('check_name', 100);
                $t->string('status', 20); // healthy, warning, critical
                $t->text('message')->nullable();
                $t->text('details')->nullable();
                $t->timestamp('checked_at');
            });
        }
    }

    public function down(): void
    {
        // No-op. Production keeps these tables; rolling them back would
        // break every controller that does INSERT/UPDATE/SELECT against them.
    }
};
