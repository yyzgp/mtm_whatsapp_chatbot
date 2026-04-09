<?php

namespace App\Livewire\Customers;

use App\Enums\CustomerPriority;
use App\Enums\CustomerStatus;
use App\Events\CustomerStatusChanged;
use App\Models\Customer;
use App\Models\CustomerSource;
use App\Models\CustomerTag;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerPage extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $priorityFilter = '';

    #[Url]
    public string $agentFilter = '';

    #[Url]
    public string $view = 'table'; // table | kanban

    public string $sortBy = 'created_at';
    public string $sortDir = 'desc';

    // Create/Edit Modal
    public bool $showModal = false;
    public bool $editMode = false;
    public ?int $editingId = null;

    // Form fields
    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $phone_whatsapp = '';
    public string $company_name = '';
    public string $job_title = '';
    public string $status = 'inquiry';
    public string $priority = 'medium';
    public ?int $source_id = null;
    public ?int $assigned_to = null;
    public string $notes = '';
    public string $expected_value = '';
    public string $city = '';
    public string $country = '';

    // Delete confirmation
    public bool $showDeleteConfirm = false;
    public ?int $deletingId = null;

    protected function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:30',
            'phone_whatsapp' => 'nullable|string|max:30',
            'company_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:100',
            'status' => 'required|in:' . implode(',', array_column(CustomerStatus::cases(), 'value')),
            'priority' => 'required|in:' . implode(',', array_column(CustomerPriority::cases(), 'value')),
            'source_id' => 'nullable|exists:customer_sources,id',
            'assigned_to' => 'nullable|exists:shared.users,id',
            'notes' => 'nullable|string',
            'expected_value' => 'nullable|numeric|min:0',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ];
    }

    #[Computed]
    public function customers()
    {
        $query = Customer::with(['assignedAgent', 'source', 'tags'])
            ->withCount('activities')
            ->visibleTo(auth()->user());

        if ($this->search) {
            $query->search($this->search);
        }
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        if ($this->priorityFilter) {
            $query->where('priority', $this->priorityFilter);
        }
        if ($this->agentFilter) {
            $query->where('assigned_to', $this->agentFilter);
        }

        return $query->orderBy($this->sortBy, $this->sortDir)->paginate(20);
    }

    #[Computed]
    public function kanbanColumns(): array
    {
        $statuses = CustomerStatus::pipeline();
        $columns = [];

        $query = Customer::with(['assignedAgent'])
            ->visibleTo(auth()->user());
        if ($this->search) $query->search($this->search);

        $allCustomers = $query->get()->groupBy(fn($c) => $c->status->value);

        foreach ($statuses as $status) {
            $columns[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'color' => $status->badgeClass(),
                'customers' => $allCustomers[$status->value] ?? collect(),
            ];
        }
        return $columns;
    }

    #[Computed]
    public function agents(): Collection
    {
        return User::whereIn('role', [1, 2])->orderBy('name')->get();
    }

    #[Computed]
    public function sources(): Collection
    {
        return CustomerSource::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return CustomerStatus::cases();
    }

    #[Computed]
    public function priorityOptions(): array
    {
        return CustomerPriority::cases();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'asc';
        }
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $this->editingId = $id;
        $this->first_name = $customer->first_name;
        $this->last_name = $customer->last_name;
        $this->email = $customer->email ?? '';
        $this->phone = $customer->phone;
        $this->phone_whatsapp = $customer->phone_whatsapp ?? '';
        $this->company_name = $customer->company_name ?? '';
        $this->job_title = $customer->job_title ?? '';
        $this->status = $customer->status->value;
        $this->priority = $customer->priority->value;
        $this->source_id = $customer->source_id;
        $this->assigned_to = $customer->assigned_to;
        $this->notes = $customer->notes ?? '';
        $this->expected_value = $customer->expected_value ?? '';
        $this->city = $customer->city ?? '';
        $this->country = $customer->country ?? '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email ?: null,
            'phone' => $this->phone,
            'phone_whatsapp' => $this->phone_whatsapp ?: null,
            'company_name' => $this->company_name ?: null,
            'job_title' => $this->job_title ?: null,
            'status' => $this->status,
            'priority' => $this->priority,
            'source_id' => $this->source_id,
            'assigned_to' => $this->assigned_to,
            'notes' => $this->notes ?: null,
            'expected_value' => $this->expected_value ?: null,
            'city' => $this->city ?: null,
            'country' => $this->country ?: null,
        ];

        if ($this->editMode) {
            $customer = Customer::findOrFail($this->editingId);
            $oldStatus = $customer->status->value;
            $customer->update($data);
            if ($oldStatus !== $this->status) {
                event(new CustomerStatusChanged($customer, $oldStatus, $this->status, auth()->user()));
            }
            $this->dispatch('notify', ['message' => 'Customer updated successfully.', 'type' => 'success']);
        } else {
            $data['created_by'] = auth()->id();
            Customer::create($data);
            $this->dispatch('notify', ['message' => 'Customer created successfully.', 'type' => 'success']);
        }

        $this->showModal = false;
        $this->resetForm();
        unset($this->customers);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        Customer::findOrFail($this->deletingId)->delete();
        $this->showDeleteConfirm = false;
        $this->deletingId = null;
        $this->dispatch('notify', ['message' => 'Customer deleted.', 'type' => 'success']);
        unset($this->customers);
    }

    public function moveToStatus(int $customerId, string $newStatus): void
    {
        $customer = Customer::findOrFail($customerId);
        $oldStatus = $customer->status->value;
        if ($oldStatus !== $newStatus) {
            $customer->update(['status' => $newStatus]);
            event(new CustomerStatusChanged($customer, $oldStatus, $newStatus, auth()->user()));
        }
        unset($this->kanbanColumns);
    }

    private function resetForm(): void
    {
        $this->first_name = $this->last_name = $this->email = $this->phone = '';
        $this->phone_whatsapp = $this->company_name = $this->job_title = '';
        $this->status = 'inquiry';
        $this->priority = 'medium';
        $this->source_id = $this->assigned_to = null;
        $this->notes = $this->expected_value = $this->city = $this->country = '';
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.customers.customer-page')
            ->layout('layouts.app');
    }
}
