<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatWorkflow;
use App\Models\ChatWorkflowAction;
use App\Models\CustomerTag;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use App\Jobs\ExecuteDelayedWorkflowActions;
use App\Services\PushNotificationService;
use App\Services\WhatsApp\WhatsAppApiService;
use Illuminate\Support\Facades\Log;

class WorkflowEngine
{
    public function evaluate(
        ChatConversation $conversation,
        string $trigger,
        ?string $messageContent = null,
        array $context = []
    ): bool {
        // Ensure relationships are loaded
        $conversation->loadMissing('phoneNumber', 'customer');

        $workflows = ChatWorkflow::active()
            ->forTrigger($trigger)
            ->with('actions')
            ->orderBy('priority', 'asc')
            ->get();

        Log::info("Workflow evaluation", [
            'trigger' => $trigger,
            'conversation_id' => $conversation->id,
            'workflows_found' => $workflows->count(),
        ]);

        // For keyword_match trigger, also load keyword workflows
        if ($trigger === 'message_received' && $messageContent) {
            $keywordWorkflows = ChatWorkflow::active()
                ->forTrigger(ChatWorkflow::TRIGGER_KEYWORD_MATCH)
                ->with('actions')
                ->orderBy('priority', 'asc')
                ->get()
                ->filter(fn($w) => $this->matchesKeywords($w, $messageContent));

            $workflows = $workflows->merge($keywordWorkflows)->sortByDesc('priority');
        }

        foreach ($workflows as $workflow) {
            Log::info("Checking workflow '{$workflow->name}'", [
                'workflow_id' => $workflow->id,
                'trigger_type' => $workflow->trigger_type,
                'conditions' => $workflow->conditions,
                'actions_count' => $workflow->actions->count(),
            ]);

            if ($this->evaluateConditions($workflow, $conversation, $messageContent, $context)) {
                Log::info("=== WORKFLOW MATCHED: '{$workflow->name}' ===", [
                    'workflow_id' => $workflow->id,
                    'conversation_id' => $conversation->id,
                    'trigger' => $trigger,
                ]);
                $this->executeActions($workflow, $conversation, $messageContent);
                $matched = true;
                // Refresh conversation state for next workflow (e.g. assigned_to may have changed)
                $conversation->refresh();
            } else {
                Log::info("Workflow '{$workflow->name}' conditions NOT met");
            }
        }

        if (!($matched ?? false)) {
            Log::info("No workflows matched for trigger '{$trigger}'");
        }
        return $matched ?? false;
    }

    private function matchesKeywords(ChatWorkflow $workflow, string $messageContent): bool
    {
        $keywords = $workflow->trigger_config['keywords'] ?? [];
        if (empty($keywords)) return false;

        $lower = strtolower($messageContent);
        $matchMode = $workflow->trigger_config['match_mode'] ?? 'any'; // any or all

        if ($matchMode === 'all') {
            foreach ($keywords as $keyword) {
                if (!str_contains($lower, strtolower(trim($keyword)))) return false;
            }
            return true;
        }

        // Default: any
        foreach ($keywords as $keyword) {
            if (str_contains($lower, strtolower(trim($keyword)))) return true;
        }
        return false;
    }

    private function evaluateConditions(
        ChatWorkflow $workflow,
        ChatConversation $conversation,
        ?string $messageContent,
        array $context = []
    ): bool {
        $conditions = $workflow->conditions ?? [];
        if (empty($conditions)) return true; // No conditions = always match

        $conversation->loadMissing('customer');

        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $conversation, $messageContent, $context)) {
                return false; // All conditions must pass (AND logic)
            }
        }

        return true;
    }

    private function evaluateCondition(
        array $condition,
        ChatConversation $conversation,
        ?string $messageContent,
        array $context = []
    ): bool {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? '';

        Log::info('Evaluating condition', [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'conversation_id' => $conversation->id,
        ]);

        // Boolean fields — return directly as true/false comparison
        $boolFields = ['is_new_conversation', 'ai_active', 'has_customer'];
        if (in_array($field, $boolFields)) {
            $actual = match ($field) {
                'is_new_conversation' => $context['is_new_conversation'] ?? false,
                'ai_active' => $conversation->isAiActive(),
                'has_customer' => $conversation->customer_id !== null,
            };
            // value "1" or operator "1" = yes/true, "" = no/false
            // (handle case where UI puts value in operator field)
            $expected = !empty($value) || $operator === '1';
            Log::info('Bool condition result', [
                'field' => $field, 'actual' => $actual, 'expected' => $expected, 'result' => $actual === $expected,
            ]);
            return $actual === $expected;
        }

        // Assigned agent — check customer's assigned_to, not conversation's
        if ($field === 'assigned_to') {
            $customer = $conversation->customer;
            $assignedTo = $customer?->assigned_to;

            $effectiveOp = in_array($operator, ['is_null', 'is_not_null', 'equals']) ? $operator : 'is_null';

            $result = match ($effectiveOp) {
                'is_null' => $assignedTo === null,
                'is_not_null' => $assignedTo !== null,
                'equals' => (string) $assignedTo === (string) $value,
                default => false,
            };
            Log::info('Assigned condition result', [
                'field' => $field, 'operator' => $effectiveOp,
                'customer_assigned_to' => $assignedTo, 'result' => $result,
            ]);
            return $result;
        }

        $actual = match ($field) {
            'message_content' => $messageContent ?? '',
            'customer_status' => $conversation->customer?->status?->value ?? '',
            'customer_priority' => $conversation->customer?->priority?->value ?? '',
            'conversation_status' => is_string($conversation->status) ? $conversation->status : ($conversation->status?->value ?? ''),
            'assigned_to' => $conversation->assigned_to,
            'contact_phone' => $conversation->contact_phone,
            default => null,
        };

        $result = match ($operator) {
            'equals' => (string) $actual === (string) $value,
            'not_equals' => (string) $actual !== (string) $value,
            'contains' => is_string($actual) && str_contains(strtolower($actual), strtolower((string) $value)),
            'not_contains' => is_string($actual) && !str_contains(strtolower($actual), strtolower((string) $value)),
            'is_null' => $actual === null || $actual === '' || $actual === 0,
            'is_not_null' => $actual !== null && $actual !== '' && $actual !== 0,
            'greater_than' => (float) $actual > (float) $value,
            'less_than' => (float) $actual < (float) $value,
            default => false,
        };

        Log::info('Condition result', [
            'field' => $field, 'operator' => $operator,
            'actual' => $actual, 'actual_type' => gettype($actual),
            'expected' => $value, 'result' => $result,
        ]);

        return $result;
    }

    public function executeActions(
        ChatWorkflow $workflow,
        ChatConversation $conversation,
        ?string $messageContent = null
    ): void {
        $actions = $workflow->actions;
        $delayedActions = [];
        $delay = 0;

        foreach ($actions as $action) {
            if ($action->action_type === ChatWorkflowAction::TYPE_WAIT) {
                $delay += ($action->action_config['minutes'] ?? 1) * 60;
                continue;
            }

            if ($delay > 0) {
                $delayedActions[] = ['action_id' => $action->id, 'delay' => $delay];
            } else {
                $this->executeAction($action, $conversation, $messageContent);
            }
        }

        // Dispatch delayed actions
        foreach ($delayedActions as $delayed) {
            ExecuteDelayedWorkflowActions::dispatch(
                $delayed['action_id'],
                $conversation->id,
                $messageContent
            )->delay(now()->addSeconds($delayed['delay']));
        }
    }

    public function executeAction(
        ChatWorkflowAction $action,
        ChatConversation $conversation,
        ?string $messageContent = null
    ): void {
        $config = $action->action_config ?? [];

        Log::info('Executing workflow action', [
            'action_id' => $action->id,
            'type' => $action->action_type,
            'config' => $config,
            'conversation_id' => $conversation->id,
        ]);

        try {
            match ($action->action_type) {
                ChatWorkflowAction::TYPE_SEND_MESSAGE => $this->actionSendMessage($conversation, $config),
                ChatWorkflowAction::TYPE_SEND_TEMPLATE => $this->actionSendTemplate($conversation, $config),
                ChatWorkflowAction::TYPE_ASSIGN_AGENT => $this->actionAssignAgent($conversation, $config),
                ChatWorkflowAction::TYPE_ASSIGN_TEAM => $this->actionAssignTeam($conversation, $config),
                ChatWorkflowAction::TYPE_CHANGE_STATUS => $this->actionChangeStatus($conversation, $config),
                ChatWorkflowAction::TYPE_ADD_TAG => $this->actionAddTag($conversation, $config),
                ChatWorkflowAction::TYPE_ENABLE_AI => $conversation->update(['ai_active' => true, 'ai_paused_until' => null]),
                ChatWorkflowAction::TYPE_DISABLE_AI => $conversation->update(['ai_active' => false]),
                ChatWorkflowAction::TYPE_SET_AI_PROMPT => $this->actionSetAiPrompt($conversation, $config),
                ChatWorkflowAction::TYPE_NOTIFY_AGENT => $this->actionNotifyAgent($conversation, $config),
                default => Log::warning("Unknown workflow action: {$action->action_type}"),
            };
        } catch (\Exception $e) {
            Log::error("Workflow action failed", [
                'action_id' => $action->id,
                'type' => $action->action_type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function actionSendMessage(ChatConversation $conversation, array $config): void
    {
        $message = $config['message'] ?? '';
        if (empty($message)) {
            Log::warning('Workflow send_message: empty message config');
            return;
        }

        $phoneNumber = $conversation->phoneNumber;
        if (!$phoneNumber) {
            Log::warning('Workflow send_message: no phone number on conversation', ['conversation_id' => $conversation->id]);
            return;
        }

        Log::info('Workflow sending message', ['conversation_id' => $conversation->id, 'message' => substr($message, 0, 50)]);

        $api = app(WhatsAppApiService::class);
        $apiResponse = $api->sendTextMessage($conversation->contact_phone, $message, $phoneNumber);
        $wamid = $apiResponse['messages'][0]['id'] ?? null;

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'system',
            'wamid' => $wamid,
            'message_type' => 'text',
            'content' => $message,
            'status' => $wamid ? 'sent' : 'failed',
            'sent_at' => $wamid ? now() : null,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => '⚡ ' . substr($message, 0, 97),
        ]);
    }

    private function actionSendTemplate(ChatConversation $conversation, array $config): void
    {
        $templateId = $config['template_id'] ?? null;
        if (!$templateId) return;

        $template = WhatsAppTemplate::find($templateId);
        if (!$template) return;

        $phoneNumber = $conversation->phoneNumber;
        if (!$phoneNumber) return;

        $api = app(WhatsAppApiService::class);
        $apiResponse = $api->sendTemplateMessage(
            $conversation->contact_phone,
            $template->name,
            $template->language ?? 'en',
            [],
            $phoneNumber
        );

        $wamid = $apiResponse['messages'][0]['id'] ?? null;

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'system',
            'wamid' => $wamid,
            'message_type' => 'template',
            'content' => "Workflow: Template {$template->name}",
            'template_name' => $template->name,
            'status' => $wamid ? 'sent' : 'failed',
            'sent_at' => $wamid ? now() : null,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'last_message_preview' => "⚡ Template: {$template->name}",
        ]);
    }

    private function actionAssignAgent(ChatConversation $conversation, array $config): void
    {
        $strategy = $config['strategy'] ?? 'specific';
        Log::info('actionAssignAgent', ['strategy' => $strategy, 'config' => $config, 'conversation_id' => $conversation->id]);

        if ($strategy === 'round_robin') {
            $teamId = $config['team_id'] ?? null;
            if (!$teamId) return;

            $team = Team::with('members')->find($teamId);
            if (!$team || $team->members->isEmpty()) return;

            // Simple round-robin: pick the member with fewest active conversations
            $memberIds = $team->members->pluck('id')->toArray();
            $agentId = ChatConversation::whereIn('assigned_to', $memberIds)
                ->whereIn('status', ['open', 'pending'])
                ->selectRaw('assigned_to, COUNT(*) as cnt')
                ->groupBy('assigned_to')
                ->orderBy('cnt')
                ->value('assigned_to');

            // If no conversations at all, pick first member
            if (!$agentId) {
                $agentId = $memberIds[0];
            }

            // Or pick a member not in the result (has 0 conversations)
            $busyIds = ChatConversation::whereIn('assigned_to', $memberIds)
                ->whereIn('status', ['open', 'pending'])
                ->pluck('assigned_to')
                ->unique()
                ->toArray();

            $freeAgents = array_diff($memberIds, $busyIds);
            if (!empty($freeAgents)) {
                $agentId = reset($freeAgents);
            }

            $conversation->update(['assigned_to' => $agentId]);
            if ($conversation->customer_id) {
                $conversation->customer?->update(['assigned_to' => $agentId]);
            }
        } else {
            $userId = $config['user_id'] ?? null;
            Log::info('Assigning specific agent', ['user_id' => $userId, 'conversation_id' => $conversation->id]);
            if ($userId) {
                $conversation->update(['assigned_to' => (int) $userId]);

                // Also assign the customer to this agent
                if ($conversation->customer_id) {
                    $conversation->customer?->update(['assigned_to' => (int) $userId]);
                }

                Log::info('Agent assigned', ['user_id' => $userId, 'customer_id' => $conversation->customer_id]);
            } else {
                Log::warning('No user_id in assign_agent config');
            }
        }
    }

    private function actionAssignTeam(ChatConversation $conversation, array $config): void
    {
        $teamId = $config['team_id'] ?? null;
        if (!$teamId) return;

        $team = Team::find($teamId);
        if (!$team) return;

        // Assign to team leader
        if ($team->leader_id) {
            $conversation->update(['assigned_to' => $team->leader_id]);
            if ($conversation->customer_id) {
                $conversation->customer?->update(['assigned_to' => $team->leader_id]);
            }
        }
    }

    private function actionChangeStatus(ChatConversation $conversation, array $config): void
    {
        $status = $config['status'] ?? null;
        if ($status && in_array($status, ['open', 'pending', 'resolved', 'spam'])) {
            $conversation->update(['status' => $status]);
        }
    }

    private function actionAddTag(ChatConversation $conversation, array $config): void
    {
        $tagId = $config['tag_id'] ?? null;
        if (!$tagId || !$conversation->customer_id) return;

        $tag = CustomerTag::find($tagId);
        if (!$tag) return;

        $conversation->customer->tags()->syncWithoutDetaching([$tagId]);
    }

    private function actionSetAiPrompt(ChatConversation $conversation, array $config): void
    {
        $prompt = $config['prompt'] ?? '';
        if (empty($prompt)) return;

        // Store override prompt in conversation metadata
        $metadata = $conversation->metadata ?? [];
        $metadata['ai_prompt_override'] = $prompt;
        $conversation->update(['metadata' => $metadata]);
    }

    private function actionNotifyAgent(ChatConversation $conversation, array $config): void
    {
        $message = $config['message'] ?? 'Workflow notification';
        $agentId = $conversation->assigned_to;
        if (!$agentId) return;

        $agent = User::find($agentId);
        if (!$agent) return;

        try {
            $pushService = app(PushNotificationService::class);
            $title = $conversation->contact_name ?? $conversation->contact_phone;
            $pushService->sendToUser($agent, $title, $message, [
                'conversation_id' => (string) $conversation->id,
                'type' => 'workflow_notification',
            ]);
        } catch (\Exception $e) {
            Log::warning("Workflow push notification failed", ['error' => $e->getMessage()]);
        }
    }
}
