<?php

namespace App\Livewire\Teams;

use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppPhoneNumber;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamPage extends Component
{
    public bool $showModal = false;
    public ?int $editingTeamId = null;

    public string $name = '';
    public string $description = '';
    public string $leaderId = '';
    public string $phoneNumberId = '';
    public bool $isActive = true;

    // Members management
    public bool $showMembersModal = false;
    public ?int $managingTeamId = null;
    public array $selectedMembers = [];

    protected array $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
        'leaderId' => 'nullable',
        'isActive' => 'boolean',
    ];

    #[Computed]
    public function teams()
    {
        return Team::with(['leader', 'members', 'phoneNumber'])->orderBy('name')->get();
    }

    #[Computed]
    public function agents()
    {
        return User::whereIn('role', [1, 2])->orderBy('name')->get();
    }

    #[Computed]
    public function phoneNumbers()
    {
        return WhatsAppPhoneNumber::where('is_active', true)->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'description', 'leaderId', 'phoneNumberId', 'isActive']);
        $this->isActive = true;
        $this->editingTeamId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $team = Team::findOrFail($id);
        $this->editingTeamId = $id;
        $this->name = $team->name;
        $this->description = $team->description ?? '';
        $this->leaderId = (string) ($team->leader_id ?? '');
        $this->phoneNumberId = (string) ($team->whatsapp_phone_number_id ?? '');
        $this->isActive = $team->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'leader_id' => $this->leaderId ?: null,
            'whatsapp_phone_number_id' => $this->phoneNumberId ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingTeamId) {
            Team::findOrFail($this->editingTeamId)->update($data);
        } else {
            $team = Team::create($data);
            if ($team->leader_id) {
                $team->members()->syncWithoutDetaching([$team->leader_id]);
            }
        }

        $this->showModal = false;
        unset($this->teams);
        $this->dispatch('notify', ['message' => 'Team saved.', 'type' => 'success']);
    }

    public function openMembers(int $id): void
    {
        $team = Team::with('members')->findOrFail($id);
        $this->managingTeamId = $id;
        $this->selectedMembers = $team->members->pluck('id')->toArray();
        $this->showMembersModal = true;
    }

    public function toggleMember(int $userId): void
    {
        if (in_array($userId, $this->selectedMembers)) {
            $this->selectedMembers = array_values(array_filter($this->selectedMembers, fn($id) => $id !== $userId));
        } else {
            $this->selectedMembers[] = $userId;
        }
    }

    public function saveMembers(): void
    {
        $team = Team::findOrFail($this->managingTeamId);

        $members = $this->selectedMembers;
        if ($team->leader_id && !in_array($team->leader_id, $members)) {
            $members[] = $team->leader_id;
        }

        $team->members()->sync($members);
        $this->showMembersModal = false;
        unset($this->teams);
        $this->dispatch('notify', ['message' => 'Team members updated.', 'type' => 'success']);
    }

    public function deleteTeam(int $id): void
    {
        Team::findOrFail($id)->delete();
        unset($this->teams);
        $this->dispatch('notify', ['message' => 'Team deleted.', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.teams.team-page')
            ->layout('layouts.app');
    }
}
