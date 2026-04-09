<?php

namespace App\Console\Commands;

use App\Models\ChatConversation;
use Illuminate\Console\Command;

class ResumeAiAutoReply extends Command
{
    protected $signature = 'crm:resume-ai-reply';
    protected $description = 'Resume AI auto-reply for conversations where the pause period has expired';

    public function handle(): void
    {
        $count = ChatConversation::where('ai_active', true)
            ->whereNotNull('ai_paused_until')
            ->where('ai_paused_until', '<=', now())
            ->update(['ai_paused_until' => null]);

        if ($count > 0) {
            $this->info("Resumed AI for {$count} conversation(s).");
        }
    }
}
