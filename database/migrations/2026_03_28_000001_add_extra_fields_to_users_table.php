<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // These columns are now added via mtm_backend migration.
        // Skip if they already exist on the shared users table.
        $shared = Schema::connection('shared');
        if ($shared->hasColumn('users', 'is_active')) return;

        $shared->table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('phone', 20)->nullable()->after('avatar');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'phone', 'is_active', 'last_login_at']);
        });
    }
};
