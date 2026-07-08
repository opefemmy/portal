<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20);
            $table->string('release_name', 100)->nullable();
            $table->date('release_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('migration_status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->string('installed_by', 100)->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique('version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_versions');
    }
};