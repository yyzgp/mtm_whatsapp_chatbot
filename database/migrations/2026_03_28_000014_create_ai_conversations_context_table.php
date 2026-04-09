<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations_context', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->unique()->constrained('chat_conversations')->cascadeOnDelete();
            $table->longText('messages_json');
            $table->unsignedInteger('token_count')->default(0);
            $table->timestamp('last_updated_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations_context');
    }
};
