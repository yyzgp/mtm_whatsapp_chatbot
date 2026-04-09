<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SharedRole as Role;

class UserPage extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';

    public bool $showModal = false;
    public bool $editMode = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $phone = '';
    public bool $is_active = true;
    public ?int $role_id = null;

    protected function rules(): array
    {
        $passwordRule = $this->editMode ? 'nullable|min:8' : 'required|min:8';
        $emailRule = $this->editMode
            ? 'required|email|unique:shared.users,email,' . $this->editingId
            : 'required|email|unique:shared.users,email';

        return [
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'password' => $passwordRule,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'role_id' => 'nullable|exists:shared.roles,id',
        ];
    }

    #[Computed]
    public function users()
    {
        // Only show Technicians (role=1) and Admins (role=2) from mtm_backend
        $query = User::with('roles')->whereIn('role', [1, 2]);

        // If user can't view all users, scope to team members only
        if (!auth()->user()->can('users.view_all')) {
            $user = auth()->user();
            $teamMemberIds = $user->getTeamMemberIds();

            // Always include self
            $visibleIds = array_unique(array_merge([$user->id], $teamMemberIds));
            $query->whereIn('id', $visibleIds);
        }

        return $query
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->roleFilter, fn($q) => $q->whereHas('roles', fn($q) => $q->where('id', $this->roleFilter)))
            ->orderBy('name')
            ->paginate(20);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->phone = $user->phone ?? '';
        $this->is_active = $user->is_active;
        $this->role_id = $user->roles->first()?->id;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        if ($this->editMode) {
            $user = User::findOrFail($this->editingId);
            $user->update($data);
        } else {
            $user = User::create($data);
        }

        if ($this->role_id) {
            $role = Role::findOrFail($this->role_id);
            $user->syncRoles([$role->name]);
        } else {
            $user->syncRoles([]);
        }

        $this->showModal = false;
        $this->resetForm();
        unset($this->users);
        $this->dispatch('notify', ['message' => $this->editMode ? 'User updated.' : 'User created.', 'type' => 'success']);
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            $this->dispatch('notify', ['message' => 'Cannot deactivate yourself.', 'type' => 'error']);
            return;
        }
        $user->update(['is_active' => !$user->is_active]);
        unset($this->users);
    }

    private function resetForm(): void
    {
        $this->name = $this->email = $this->password = $this->phone = '';
        $this->is_active = true;
        $this->role_id = $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.users.user-page')
            ->layout('layouts.app');
    }
}
