<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users table is shared — skip if column already exists.
        $shared = Schema::connection('shared');
        if ($shared->hasColumn('users', 'expo_push_token') || $shared->hasColumn('users', 'fcm_token')) return;

        $shared->table('users', function (Blueprint $table) {
            $table->string('expo_push_token')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('expo_push_token');
        });
    }
};
