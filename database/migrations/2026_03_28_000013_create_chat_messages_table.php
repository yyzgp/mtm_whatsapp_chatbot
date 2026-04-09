<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->cascadeOnDelete();
            $table->enum('sender_type', ['customer', 'agent', 'ai', 'system']);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('wamid')->nullable()->unique();
            $table->string('reply_to_wamid')->nullable();
            $table->enum('message_type', ['text', 'image', 'audio', 'video', 'document', 'location', 'template', 'sticker', 'reaction', 'unsupported'])->default('text');
            $table->text('content')->nullable();
            $table->string('media_url', 1000)->nullable();
            $table->string('media_mime_type', 100)->nullable();
            $table->unsignedBigInteger('media_file_size')->nullable();
            $table->text('media_caption')->nullable();
            $table->string('template_name')->nullable();
            $table->json('template_params')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('ai_tokens_used')->nullable();
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
            $table->index('wamid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
