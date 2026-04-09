<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users table is shared — skip if already renamed or column doesn't exist.
        $shared = Schema::connection('shared');
        if ($shared->hasColumn('users', 'fcm_token') || !$shared->hasColumn('users', 'expo_push_token')) return;

        $shared->table('users', function (Blueprint $table) {
            $table->renameColumn('expo_push_token', 'fcm_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('fcm_token', 'expo_push_token');
        });
    }
};
