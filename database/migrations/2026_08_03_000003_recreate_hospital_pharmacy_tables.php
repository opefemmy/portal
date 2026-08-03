<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotent re-creation of pharmacy tables that the 2024_07_06_000002
     * migration marked as Ran but never actually created (DB drift).
     *
     * Each create() is wrapped in hasTable() so re-running this on a system
     * that already has these tables is a no-op.
     */
    public function up(): void
    {
        if (!Schema::hasTable('hospital_suppliers')) {
            Schema::create('hospital_suppliers', function (Blueprint $table) {
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
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hospital_drug_categories')) {
            Schema::create('hospital_drug_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hospital_drugs')) {
            Schema::create('hospital_drugs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->nullable()->constrained('hospital_drug_categories')->nullOnDelete();
                $table->string('name');
                $table->string('generic_name')->nullable();
                $table->string('code')->unique();
                $table->string('form');
                $table->string('strength')->nullable();
                $table->string('unit');
                $table->decimal('cost_price', 10, 2)->default(0);
                $table->decimal('selling_price', 10, 2)->default(0);
                $table->integer('reorder_level')->default(10);
                $table->integer('current_stock')->default(0);
                $table->text('storage_location')->nullable();
                $table->text('side_effects')->nullable();
                $table->text('contraindications')->nullable();
                $table->text('instructions')->nullable();
                $table->boolean('requires_prescription')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hospital_drug_batches')) {
            Schema::create('hospital_drug_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('drug_id')->nullable()->constrained('hospital_drugs')->nullOnDelete();
                $table->string('batch_number')->unique();
                $table->integer('quantity');
                $table->integer('remaining_quantity');
                $table->decimal('unit_cost', 10, 2);
                $table->date('manufacture_date')->nullable();
                $table->date('expiry_date');
                $table->date('received_date');
                $table->foreignId('supplier_id')->nullable()->constrained('hospital_suppliers')->nullOnDelete();
                $table->string('status', 20)->default('active')->comment('active, expired, depleted');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hospital_inventory_movements')) {
            Schema::create('hospital_inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('drug_id')->nullable()->constrained('hospital_drugs')->nullOnDelete();
                $table->foreignId('batch_id')->nullable()->constrained('hospital_drug_batches')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('movement_type', 30)->comment('purchase, sale, adjustment, expired, returned, transfer');
                $table->integer('quantity');
                $table->integer('quantity_before');
                $table->integer('quantity_after');
                $table->decimal('unit_cost', 10, 2)->nullable();
                $table->text('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hospital_store_items')) {
            Schema::create('hospital_store_items', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->string('category')->nullable();
                $table->string('unit')->nullable();
                $table->decimal('cost_price', 10, 2)->default(0);
                $table->decimal('selling_price', 10, 2)->default(0);
                $table->integer('current_stock')->default(0);
                $table->integer('reorder_level')->default(10);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hospital_store_batches')) {
            Schema::create('hospital_store_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('item_id')->nullable()->constrained('hospital_store_items')->nullOnDelete();
                $table->string('batch_number')->unique();
                $table->integer('quantity');
                $table->integer('remaining_quantity');
                $table->decimal('unit_cost', 10, 2);
                $table->date('manufacture_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->date('received_date');
                $table->foreignId('supplier_id')->nullable()->constrained('hospital_suppliers')->nullOnDelete();
                $table->string('status', 20)->default('active')->comment('active, expired, depleted');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hospital_purchases')) {
            Schema::create('hospital_purchases', function (Blueprint $table) {
                $table->id();
                $table->string('purchase_number')->unique();
                $table->foreignId('supplier_id')->nullable()->constrained('hospital_suppliers')->nullOnDelete();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('purchase_date');
                $table->date('expected_delivery')->nullable();
                $table->date('actual_delivery')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('status', 30)->default('pending')->comment('pending, approved, ordered, received, cancelled');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hospital_purchase_items')) {
            Schema::create('hospital_purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->nullable()->constrained('hospital_purchases')->cascadeOnDelete();
                $table->unsignedBigInteger('item_id')->nullable();
                $table->string('item_type');
                $table->string('item_name');
                $table->integer('quantity');
                $table->decimal('unit_cost', 10, 2);
                $table->decimal('total', 10, 2);
                $table->string('batch_number')->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Idempotent teardown — only drop if present.
        foreach ([
            'hospital_purchase_items',
            'hospital_purchases',
            'hospital_store_batches',
            'hospital_store_items',
            'hospital_inventory_movements',
            'hospital_drug_batches',
            'hospital_drugs',
            'hospital_drug_categories',
            'hospital_suppliers',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
