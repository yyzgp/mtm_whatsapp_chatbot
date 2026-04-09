<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // The emoji reacted to this message (null = no reaction / reaction removed)
            $table->string('reaction', 20)->nullable()->after('reply_to_wamid');
            // Who reacted: 'customer' or 'agent'
            $table->string('reaction_by', 20)->nullable()->after('reaction');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['reaction', 'reaction_by']);
        });
    }
};
