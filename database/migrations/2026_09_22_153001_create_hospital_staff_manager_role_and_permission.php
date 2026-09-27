<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the Hospital Staff Manager role if it doesn't exist
        $role = DB::table('roles')->where('slug', 'hospital_staff_manager')->first();
        if (!$role) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'Hospital Staff Manager',
                'slug' => 'hospital_staff_manager',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $roleId = $role->id;
        }

        // 2. Create the permission if it doesn't exist
        $permission = DB::table('permissions')->where('slug', 'admin.hospital-staff.manage')->first();
        if (!$permission) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Manage Hospital Staff',
                'slug' => 'admin.hospital-staff.manage',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $permissionId = $permission->id;
        }

        // 3. Assign permission to the new role
        DB::table('role_permissions')->updateOrInsert(
            ['permission_id' => $permissionId, 'role_id' => $roleId],
            ['updated_at' => now()]
        );

        // 4. Assign permission to super_admin (if exists)
        $superAdminRole = DB::table('roles')->where('slug', 'super_admin')->first();
        if ($superAdminRole) {
            DB::table('role_permissions')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $superAdminRole->id],
                ['updated_at' => now()]
            );
        }

        // 5. Assign permission to admin (if exists)
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        if ($adminRole) {
            DB::table('role_permissions')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $adminRole->id],
                ['updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permission-role links
        DB::table('role_permissions')->where('permission_id', function($query) {
            $query->select('id')->from('permissions')->where('slug', 'admin.hospital-staff.manage');
        })->delete();

        // Remove permission
        DB::table('permissions')->where('slug', 'admin.hospital-staff.manage')->delete();

        // Remove role
        DB::table('roles')->where('slug', 'hospital_staff_manager')->delete();
    }
};
