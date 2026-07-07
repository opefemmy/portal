<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hospital Suppliers (create first)
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

        // Hospital Drug Categories
        Schema::create('hospital_drug_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Hospital Drugs/Medications
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

        // Hospital Drug Batches
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
            $table->enum('status', ['active', 'expired', 'depleted'])->default('active');
            $table->timestamps();
        });

        // Hospital Inventory Movements
        Schema::create('hospital_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drug_id')->nullable()->constrained('hospital_drugs')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('hospital_drug_batches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('movement_type', ['purchase', 'sale', 'adjustment', 'expired', 'returned', 'transfer']);
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->text('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Hospital Store Items
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

        // Hospital Store Batches
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
            $table->enum('status', ['active', 'expired', 'depleted'])->default('active');
            $table->timestamps();
        });

        // Hospital Purchases
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
            $table->enum('status', ['pending', 'approved', 'ordered', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Hospital Purchase Items
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

    public function down(): void
    {
        Schema::dropIfExists('hospital_purchase_items');
        Schema::dropIfExists('hospital_purchases');
        Schema::dropIfExists('hospital_store_batches');
        Schema::dropIfExists('hospital_store_items');
        Schema::dropIfExists('hospital_inventory_movements');
        Schema::dropIfExists('hospital_suppliers');
        Schema::dropIfExists('hospital_drug_batches');
        Schema::dropIfExists('hospital_drugs');
        Schema::dropIfExists('hospital_drug_categories');
    }
};