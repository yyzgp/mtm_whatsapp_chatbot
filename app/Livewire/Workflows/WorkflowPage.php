<?php

namespace App\Livewire\Workflows;

use App\Models\ChatWorkflow;
use App\Models\ChatWorkflowAction;
use App\Models\CustomerTag;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppTemplate;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WorkflowPage extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    // Workflow form
    public string $name = '';
    public string $description = '';
    public string $trigger_type = 'message_received';
    public array $trigger_config = [];
    public array $conditions = [];
    public bool $is_active = true;
    public int $priority = 0;
    public array $actions = [];

    // Temp fields for adding
    public string $newKeyword = '';

    protected array $rules = [
        'name' => 'required|string|max:255',
        'trigger_type' => 'required|string',
    ];

    #[Computed]
    public function workflows()
    {
        return ChatWorkflow::with('actions')
            ->orderBy('priority', 'asc')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function agents()
    {
        return User::whereIn('role', [1, 2])->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function teams()
    {
        return Team::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function templates()
    {
        return WhatsAppTemplate::where('status', 'approved')->orderBy('name')->get(['id', 'name', 'language']);
    }

    #[Computed]
    public function tags()
    {
        return CustomerTag::orderBy('name')->get(['id', 'name']);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $workflow = ChatWorkflow::with('actions')->findOrFail($id);
        $this->editingId = $id;
        $this->name = $workflow->name;
        $this->description = $workflow->description ?? '';
        $this->trigger_type = $workflow->trigger_type;
        $this->trigger_config = $workflow->trigger_config ?? [];
        $this->conditions = $workflow->conditions ?? [];
        $this->is_active = $workflow->is_active;
        $this->priority = $workflow->priority;
        $this->actions = $workflow->actions->map(fn($a) => [
            'action_type' => $a->action_type,
            'action_config' => $a->action_config ?? [],
        ])->toArray();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'trigger_type' => $this->trigger_type,
            'trigger_config' => !empty($this->trigger_config) ? $this->trigger_config : null,
            'conditions' => !empty($this->conditions) ? $this->conditions : null,
            'is_active' => $this->is_active,
            'priority' => $this->priority,
        ];

        if ($this->editingId) {
            $workflow = ChatWorkflow::findOrFail($this->editingId);
            $workflow->update($data);
            $workflow->actions()->delete();
        } else {
            $workflow = ChatWorkflow::create($data);
        }

        foreach ($this->actions as $i => $action) {
            // Only save config keys relevant to the action type
            $cleanConfig = $this->cleanActionConfig($action['action_type'], $action['action_config'] ?? []);

            ChatWorkflowAction::create([
                'workflow_id' => $workflow->id,
                'sort_order' => $i,
                'action_type' => $action['action_type'],
                'action_config' => !empty($cleanConfig) ? $cleanConfig : null,
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
        unset($this->workflows);
        $this->dispatch('notify', ['message' => 'Workflow saved.', 'type' => 'success']);
    }

    public function toggleActive(int $id): void
    {
        $workflow = ChatWorkflow::findOrFail($id);
        $workflow->update(['is_active' => !$workflow->is_active]);
        unset($this->workflows);
    }

    public function deleteWorkflow(int $id): void
    {
        ChatWorkflow::findOrFail($id)->delete();
        unset($this->workflows);
        $this->dispatch('notify', ['message' => 'Workflow deleted.', 'type' => 'success']);
    }

    // Condition management
    public function addCondition(): void
    {
        $this->conditions[] = ['field' => 'message_content', 'operator' => 'contains', 'value' => ''];
    }

    public function removeCondition(int $index): void
    {
        array_splice($this->conditions, $index, 1);
    }

    // Action management
    public function addAction(): void
    {
        $this->actions[] = ['action_type' => 'send_message', 'action_config' => []];
    }

    public function removeAction(int $index): void
    {
        array_splice($this->actions, $index, 1);
    }

    public function moveActionUp(int $index): void
    {
        if ($index <= 0) return;
        [$this->actions[$index - 1], $this->actions[$index]] = [$this->actions[$index], $this->actions[$index - 1]];
    }

    public function moveActionDown(int $index): void
    {
        if ($index >= count($this->actions) - 1) return;
        [$this->actions[$index + 1], $this->actions[$index]] = [$this->actions[$index], $this->actions[$index + 1]];
    }

    // Keyword management for keyword_match trigger
    public function addKeyword(): void
    {
        if (empty(trim($this->newKeyword))) return;
        $keywords = $this->trigger_config['keywords'] ?? [];
        $keywords[] = trim($this->newKeyword);
        $this->trigger_config['keywords'] = $keywords;
        $this->newKeyword = '';
    }

    public function removeKeyword(int $index): void
    {
        $keywords = $this->trigger_config['keywords'] ?? [];
        array_splice($keywords, $index, 1);
        $this->trigger_config['keywords'] = $keywords;
    }

    private function cleanActionConfig(string $type, array $config): array
    {
        $allowedKeys = match ($type) {
            'send_message' => ['message'],
            'send_template' => ['template_id'],
            'assign_agent' => ['strategy', 'user_id', 'team_id'],
            'assign_team' => ['team_id'],
            'change_status' => ['status'],
            'add_tag' => ['tag_id'],
            'set_ai_prompt' => ['prompt'],
            'wait' => ['minutes'],
            'notify_agent' => ['message'],
            default => [],
        };

        return array_filter(
            array_intersect_key($config, array_flip($allowedKeys)),
            fn($v) => $v !== null && $v !== ''
        );
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = $this->description = '';
        $this->trigger_type = 'message_received';
        $this->trigger_config = [];
        $this->conditions = [];
        $this->actions = [];
        $this->is_active = true;
        $this->priority = 0;
        $this->newKeyword = '';
    }

    public function render()
    {
        return view('livewire.workflows.workflow-page')
            ->layout('layouts.app');
    }
}
