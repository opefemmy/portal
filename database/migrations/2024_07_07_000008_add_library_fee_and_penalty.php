<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add library fee required field to students
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'library_fee_paid')) {
                $table->boolean('library_fee_paid')->default(false)->after('year_of_entry');
            }
            if (!Schema::hasColumn('students', 'library_fee_paid_at')) {
                $table->timestamp('library_fee_paid_at')->nullable()->after('library_fee_paid');
            }
        });

        // Add penalty fields to book_loans
        Schema::table('book_loans', function (Blueprint $table) {
            if (!Schema::hasColumn('book_loans', 'late_fee')) {
                $table->decimal('late_fee', 10, 2)->default(0)->after('remarks');
            }
            if (!Schema::hasColumn('book_loans', 'late_fee_paid')) {
                $table->boolean('late_fee_paid')->default(false)->after('late_fee');
            }
            if (!Schema::hasColumn('book_loans', 'late_fee_paid_at')) {
                $table->timestamp('late_fee_paid_at')->nullable()->after('late_fee_paid');
            }
            if (!Schema::hasColumn('book_loans', 'penalty_days')) {
                $table->integer('penalty_days')->default(0)->after('late_fee_paid_at');
            }
        });

        // Add late fee per day to books table
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'late_fee_per_day')) {
                $table->decimal('late_fee_per_day', 10, 2)->default(100)->after('available');
            }
            if (!Schema::hasColumn('books', 'max_borrow_days')) {
                $table->integer('max_borrow_days')->default(14)->after('late_fee_per_day');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['library_fee_paid', 'library_fee_paid_at']);
        });

        Schema::table('book_loans', function (Blueprint $table) {
            $table->dropColumn(['late_fee', 'late_fee_paid', 'late_fee_paid_at', 'penalty_days']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['late_fee_per_day', 'max_borrow_days']);
        });
    }
};