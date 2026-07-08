<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_backups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 50); // database, files, storage, config
            $table->string('file_path', 255)->nullable();
            $table->string('file_size', 50)->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('system_health_logs', function (Blueprint $table) {
            $table->id();
            $table->string('check_name', 100);
            $table->string('status', 20); // healthy, warning, critical
            $table->text('message')->nullable();
            $table->text('details')->nullable();
            $table->timestamp('checked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('system_health_logs');
    }
};