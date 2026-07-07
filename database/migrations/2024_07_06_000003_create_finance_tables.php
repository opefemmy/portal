<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Finance Ledgers (must come first - referenced by many)
        Schema::create('finance_ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->comment('asset, liability, income, expense');
            $table->string('category')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('finance_ledgers')->onDelete('set null');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_manual_entry')->default(true);
            $table->timestamps();
        });

        // Finance Allowances (no dependencies)
        Schema::create('finance_allowances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['fixed', 'percentage']);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Finance Deductions (no dependencies)
        Schema::create('finance_deductions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['fixed', 'percentage']);
            $table->string('calculation_base')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Finance Vendors (no dependencies)
        Schema::create('finance_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('contact_person')->nullable();
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Finance Invoices (depends on users, sessions)
        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('student_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('set null');
            $table->string('payment_type')->comment('school_fees, hostel_fees, medical_fees, acceptance_fee, other');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('penalty', 12, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Finance Receipts (depends on finance_invoices, users, payments)
        Schema::create('finance_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('invoice_id')->nullable()->constrained('finance_invoices')->onDelete('set null');
            $table->foreignId('student_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->foreignId('generated_by')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_received', 12, 2);
            $table->decimal('change_given', 12, 2)->default(0);
            $table->string('payment_method')->comment('cash, bank_transfer, cheque, pos, online');
            $table->string('reference_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('verified_at')->nullable();
            $table->timestamps();
        });

        // Finance Transactions (General Ledger - depends on users, sessions)
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('sessions')->onDelete('set null');
            $table->string('type')->comment('credit, debit');
            $table->string('category')->comment('income, expense');
            $table->string('ledger_code');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('transaction_date');
            $table->enum('status', ['pending', 'posted', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Finance Refunds (depends on users, finance_receipts)
        Schema::create('finance_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique();
            $table->foreignId('student_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('receipt_id')->nullable()->constrained('finance_receipts')->onDelete('set null');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('reference_number')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Finance Cash Book (depends on users)
        Schema::create('finance_cash_book', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type')->comment('receipt, payment');
            $table->date('date');
            $table->string('description');
            $table->decimal('cash_in', 12, 2)->default(0);
            $table->decimal('cash_out', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Finance Department Ledgers (depends on departments, finance_ledgers)
        Schema::create('finance_department_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->foreignId('ledger_id')->constrained('finance_ledgers')->onDelete('cascade');
            $table->decimal('allocation', 12, 2)->default(0);
            $table->decimal('spent', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->unsignedBigInteger('fiscal_year_id')->nullable();
            $table->timestamps();
        });

        // Finance Budgets (depends on users, departments)
        Schema::create('finance_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('fiscal_year');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->decimal('total_budget', 12, 2);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'approved', 'active', 'closed'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Finance Budget Allocations (depends on finance_budgets, finance_ledgers)
        Schema::create('finance_budget_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('finance_budgets')->onDelete('cascade');
            $table->foreignId('ledger_id')->constrained('finance_ledgers')->onDelete('cascade');
            $table->decimal('allocated_amount', 12, 2);
            $table->decimal('spent_amount', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        // Finance Payroll (depends on users)
        Schema::create('finance_payroll', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->onDelete('cascade');
            $table->string('month');
            $table->string('year');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('total_allowances', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('net_salary', 12, 2);
            $table->decimal('tax_deducted', 12, 2)->default(0);
            $table->decimal('pension_deducted', 12, 2)->default(0);
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid'])->default('draft');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });

        // Finance Staff Allowances (Payroll Items - depends on finance_payroll, finance_allowances)
        Schema::create('finance_staff_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('finance_payroll')->onDelete('cascade');
            $table->foreignId('allowance_id')->constrained('finance_allowances')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        // Finance Staff Deductions (Payroll Items - depends on finance_payroll, finance_deductions)
        Schema::create('finance_staff_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('finance_payroll')->onDelete('cascade');
            $table->foreignId('deduction_id')->constrained('finance_deductions')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        // Finance Purchase Orders (depends on finance_vendors, departments, users)
        Schema::create('finance_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('vendor_id')->constrained('finance_vendors')->onDelete('cascade');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('order_date');
            $table->date('expected_delivery')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'received', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Finance Vendor Payments (depends on finance_vendors, finance_purchase_orders, users)
        Schema::create('finance_vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('vendor_id')->constrained('finance_vendors')->onDelete('cascade');
            $table->foreignId('po_id')->nullable()->constrained('finance_purchase_orders')->onDelete('set null');
            $table->foreignId('processed_by')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('payment_method')->comment('cash, cheque, bank_transfer, pos');
            $table->string('reference_number')->nullable();
            $table->string('cheque_number')->nullable();
            $table->date('payment_date');
            $table->enum('status', ['pending', 'approved', 'released', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_vendor_payments');
        Schema::dropIfExists('finance_purchase_orders');
        Schema::dropIfExists('finance_vendors');
        Schema::dropIfExists('finance_staff_deductions');
        Schema::dropIfExists('finance_staff_allowances');
        Schema::dropIfExists('finance_deductions');
        Schema::dropIfExists('finance_allowances');
        Schema::dropIfExists('finance_payroll');
        Schema::dropIfExists('finance_budget_allocations');
        Schema::dropIfExists('finance_budgets');
        Schema::dropIfExists('finance_department_ledgers');
        Schema::dropIfExists('finance_cash_book');
        Schema::dropIfExists('finance_ledgers');
        Schema::dropIfExists('finance_refunds');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_receipts');
        Schema::dropIfExists('finance_invoices');
    }
};