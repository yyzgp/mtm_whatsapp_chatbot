<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30);
            $table->string('waba_conversation_id')->nullable();
            $table->enum('status', ['open', 'pending', 'resolved', 'spam'])->default('open');
            $table->boolean('ai_active')->default(true);
            $table->timestamp('ai_paused_until')->nullable();
            $table->timestamp('last_agent_reply_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 500)->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['contact_phone', 'whatsapp_account_id']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};
