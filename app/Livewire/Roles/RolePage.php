<?php

namespace App\Livewire\Roles;

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\SharedPermission as Permission;
use App\Models\SharedRole as Role;

class RolePage extends Component
{
    public ?int $editingRoleId = null;
    public bool $showModal = false;
    public bool $showPermissionMatrix = false;

    public string $name = '';
    public string $display_name = '';
    public string $description = '';

    public array $selectedPermissions = [];

    protected array $rules = [
        'name' => 'required|string|max:255|alpha_dash',
        'display_name' => 'required|string|max:255',
        'description' => 'nullable|string',
    ];

    #[Computed]
    public function roles()
    {
        return Role::with('permissions')->withCount('users')->orderBy('name')->get();
    }

    #[Computed]
    public function permissionsByModule(): array
    {
        return Permission::orderBy('module')->orderBy('name')
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'display_name', 'description']);
        $this->editingRoleId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $role = Role::findOrFail($id);
        $this->editingRoleId = $id;
        $this->name = $role->name;
        $this->display_name = $role->display_name ?? '';
        $this->description = $role->description ?? '';
        $this->showModal = true;
    }

    public function openPermissions(int $id): void
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->editingRoleId = $id;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showPermissionMatrix = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
            $role->update([
                'name' => $this->name,
                'display_name' => $this->display_name,
                'description' => $this->description,
            ]);
        } else {
            Role::create([
                'name' => $this->name,
                'guard_name' => 'web',
                'display_name' => $this->display_name,
                'description' => $this->description,
            ]);
        }

        $this->showModal = false;
        unset($this->roles);
        $this->dispatch('notify', ['message' => 'Role saved.', 'type' => 'success']);
    }

    public function savePermissions(): void
    {
        $role = Role::findOrFail($this->editingRoleId);
        $role->syncPermissions($this->selectedPermissions);
        $this->showPermissionMatrix = false;
        unset($this->roles);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->dispatch('notify', ['message' => 'Permissions updated.', 'type' => 'success']);
    }

    public function togglePermission(string $permissionName): void
    {
        if (in_array($permissionName, $this->selectedPermissions)) {
            $this->selectedPermissions = array_values(array_filter($this->selectedPermissions, fn($p) => $p !== $permissionName));
        } else {
            $this->selectedPermissions[] = $permissionName;
        }
    }

    public function toggleModule(string $module): void
    {
        $modulePermissions = Permission::where('module', $module)->pluck('name')->toArray();
        $allSelected = empty(array_diff($modulePermissions, $this->selectedPermissions));

        if ($allSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $modulePermissions));
        } else {
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $modulePermissions)));
        }
    }

    public function render()
    {
        return view('livewire.roles.role-page')
            ->layout('layouts.app');
    }
}
