<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insert notification settings as key-value pairs
        DB::table('settings')->insertOrIgnore([
            ['key' => 'scrolling_message', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'login_notification', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'post_login_message', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'show_post_login_popup', 'value' => 'false', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'scrolling_message',
            'login_notification',
            'post_login_message',
            'show_post_login_popup'
        ])->delete();
    }
};