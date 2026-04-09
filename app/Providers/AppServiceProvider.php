<?php

namespace App\Providers;

use App\Events\AgentRepliedToChat;
use App\Events\CustomerStatusChanged;
use App\Listeners\LogCustomerStatusChange;
use App\Listeners\PauseAiOnAgentReply;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\SharedPersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Use shared connection for Sanctum tokens (no sc_ prefix)
        Sanctum::usePersonalAccessTokenModel(SharedPersonalAccessToken::class);

        // Register events
        Event::listen(AgentRepliedToChat::class, PauseAiOnAgentReply::class);
        Event::listen(CustomerStatusChanged::class, LogCustomerStatusChange::class);

        // Schedule commands
        Schedule::command('crm:resume-ai-reply')->everyFiveMinutes();
        Schedule::command('crm:check-no-reply-timeout')->everyFiveMinutes();
    }
}
