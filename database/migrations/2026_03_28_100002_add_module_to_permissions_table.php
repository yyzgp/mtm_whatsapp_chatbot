<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permissions table is shared — add columns only if missing.
        $shared = Schema::connection('shared');
        if ($shared->hasColumn('permissions', 'module')) return;

        $shared->table('permissions', function (Blueprint $table) {
            $table->string('module', 100)->nullable()->after('name');
            $table->string('display_name')->nullable()->after('module');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['module', 'display_name']);
        });
    }
};
