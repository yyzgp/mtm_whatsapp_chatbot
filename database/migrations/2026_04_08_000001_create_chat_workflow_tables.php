<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type', 50);
            // message_received, conversation_opened, conversation_reopened,
            // no_reply_timeout, keyword_match, ai_handoff
            $table->json('trigger_config')->nullable();
            // e.g. { "keywords": ["price","pricing"], "timeout_minutes": 30 }
            $table->json('conditions')->nullable();
            // e.g. [{"field":"customer_status","operator":"equals","value":"inquiry"}]
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'trigger_type', 'priority']);
        });

        Schema::create('chat_workflow_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('chat_workflows')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('action_type', 50);
            // send_message, send_template, assign_agent, assign_team,
            // change_status, add_tag, enable_ai, disable_ai,
            // set_ai_prompt, wait, notify_agent
            $table->json('action_config')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_workflow_actions');
        Schema::dropIfExists('chat_workflows');
    }
};
