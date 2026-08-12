<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the cross-domain permission tables.
     *
     * `permissions` is the canonical catalogue of every named action
     * the system recognises. `role_permissions` is a many-to-many
     * pivot between roles and permissions. Both are additive — the
     * existing `roles.permissions` JSON column is left intact and
     * continues to be written by ERPRolesSeeder; the pivot is the
     * new read path used by App\Services\Permissions\PermissionService.
     *
     * The migration is wrapped in `Schema::hasTable` guards so a
     * partial run (e.g. a previous attempt that created one table but
     * not the other) can re-run cleanly.
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('slug')->unique();              // e.g. 'patients.create'
                $t->string('group', 50)->nullable();       // e.g. 'hospital', 'bursar'
                $t->string('description')->nullable();
                $t->timestamps();
                $t->index('group');
            });
        }

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $t->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $t->timestamps();
                $t->unique(['role_id', 'permission_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
