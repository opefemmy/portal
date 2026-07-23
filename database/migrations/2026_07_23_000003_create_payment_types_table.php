<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Application Fee, Acceptance Fee, School Fee
            $table->string('code')->unique(); // e.g., APP_FEE, ACCEPT_FEE
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_payment')->default(true);
            $table->string('payment_channel')->nullable(); // external, internal
            $table->integer('priority')->default(0);
            $table->timestamps();
        });

        // Add payment_type_id to external_payments
        Schema::table('external_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_type_id')->nullable()->after('payment_channel');
            $table->foreign('payment_type_id')->references('id')->on('payment_types')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('external_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_type_id']);
            $table->dropColumn('payment_type_id');
        });
        Schema::dropIfExists('payment_types');
    }
};
