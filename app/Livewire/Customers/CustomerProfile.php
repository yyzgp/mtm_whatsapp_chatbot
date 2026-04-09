<?php

namespace App\Livewire\Customers;

use App\Enums\ActivityType;
use App\Enums\CustomerPriority;
use App\Enums\CustomerStatus;
use App\Events\CustomerStatusChanged;
use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\CustomerDocument;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class CustomerProfile extends Component
{
    use WithFileUploads;

    public Customer $customer;
    public string $activeTab = 'information';

    // Activity log modal
    public bool $showActivityModal = false;
    public string $activityType = 'note';
    public string $activitySubject = '';
    public string $activityDescription = '';
    public string $activityOutcome = '';

    // Document upload
    public $documentFile = null;
    public string $documentName = '';

    // Edit customer info
    public bool $editingInfo = false;
    public string $editFirstName = '';
    public string $editLastName = '';
    public string $editEmail = '';
    public string $editPhone = '';
    public string $editCompany = '';
    public string $editJobTitle = '';
    public string $editCity = '';
    public string $editCountry = '';
    public string $editNotes = '';
    public string $editExpectedValue = '';
    public string $editPriority = '';

    // Status change confirmation
    public string $pendingStatus = '';
    public string $pendingLabel = '';
    public string $statusNotes = '';
    public bool $showStatusModal = false;

    // Edit status quick
    public string $newStatus = '';

    public function mount(Customer $customer): void
    {
        $this->customer = $customer->load(['assignedAgent', 'source', 'tags', 'activities.user', 'documents.uploadedBy']);
        $this->newStatus = $customer->status->value;
    }

    #[Computed]
    public function agents()
    {
        return User::whereIn('role', [1, 2])->orderBy('name')->get();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return CustomerStatus::cases();
    }

    #[Computed]
    public function activityTypes(): array
    {
        return array_filter(ActivityType::cases(), fn($t) => !in_array($t, [ActivityType::StatusChange, ActivityType::Assignment]));
    }

    public function startEditing(): void
    {
        $this->editFirstName = $this->customer->first_name;
        $this->editLastName = $this->customer->last_name;
        $this->editEmail = $this->customer->email ?? '';
        $this->editPhone = $this->customer->phone;
        $this->editCompany = $this->customer->company_name ?? '';
        $this->editJobTitle = $this->customer->job_title ?? '';
        $this->editCity = $this->customer->city ?? '';
        $this->editCountry = $this->customer->country ?? '';
        $this->editNotes = $this->customer->notes ?? '';
        $this->editExpectedValue = $this->customer->expected_value ? (string) $this->customer->expected_value : '';
        $this->editPriority = $this->customer->priority->value;
        $this->editingInfo = true;
    }

    public function saveInfo(): void
    {
        $this->validate([
            'editFirstName' => 'required|string|max:100',
            'editLastName' => 'required|string|max:100',
            'editEmail' => 'nullable|email|max:255',
            'editPhone' => 'required|string|max:30',
            'editCompany' => 'nullable|string|max:255',
            'editJobTitle' => 'nullable|string|max:100',
            'editCity' => 'nullable|string|max:100',
            'editCountry' => 'nullable|string|max:100',
            'editNotes' => 'nullable|string',
            'editExpectedValue' => 'nullable|numeric|min:0',
            'editPriority' => 'required|string',
        ]);

        $this->customer->update([
            'first_name' => $this->editFirstName,
            'last_name' => $this->editLastName,
            'email' => $this->editEmail ?: null,
            'phone' => $this->editPhone,
            'company_name' => $this->editCompany ?: null,
            'job_title' => $this->editJobTitle ?: null,
            'city' => $this->editCity ?: null,
            'country' => $this->editCountry ?: null,
            'notes' => $this->editNotes ?: null,
            'expected_value' => $this->editExpectedValue ?: null,
            'priority' => $this->editPriority,
        ]);

        $this->editingInfo = false;
        $this->customer->refresh();
        $this->dispatch('notify', ['message' => 'Customer updated.', 'type' => 'success']);
    }

    public function cancelEditing(): void
    {
        $this->editingInfo = false;
    }

    public function confirmStatusChange(string $status, string $label): void
    {
        if ($status === $this->customer->status->value) return;
        $this->pendingStatus = $status;
        $this->pendingLabel = $label;
        $this->statusNotes = '';
        $this->showStatusModal = true;
    }

    public function applyStatusChange(): void
    {
        if (!$this->pendingStatus || $this->pendingStatus === $this->customer->status->value) {
            $this->showStatusModal = false;
            return;
        }
        $this->changeStatus($this->pendingStatus, $this->statusNotes);
        $this->showStatusModal = false;
        $this->pendingStatus = '';
        $this->pendingLabel = '';
        $this->statusNotes = '';
    }

    public function cancelStatusChange(): void
    {
        $this->showStatusModal = false;
        $this->pendingStatus = '';
        $this->pendingLabel = '';
        $this->statusNotes = '';
    }

    public function changeStatus(string $status, string $notes = ''): void
    {
        $oldStatus = $this->customer->status->value;
        if ($oldStatus === $status) return;

        $this->customer->update(['status' => $status]);
        event(new CustomerStatusChanged($this->customer->fresh(), $oldStatus, $status, auth()->user()));

        // Log the status change as an activity with notes
        CustomerActivity::create([
            'customer_id' => $this->customer->id,
            'user_id' => auth()->id(),
            'type' => ActivityType::StatusChange,
            'subject' => 'Status changed from ' . ucfirst(str_replace('_', ' ', $oldStatus)) . ' to ' . ucfirst(str_replace('_', ' ', $status)),
            'description' => $notes ?: null,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $this->customer->refresh();
        $this->newStatus = $status;
        $this->dispatch('notify', ['message' => 'Status updated.', 'type' => 'success']);
    }

    public function assignTo(int $userId): void
    {
        if (!auth()->user()->can('customers.assign')) return;
        $old = $this->customer->assigned_to;
        $this->customer->update(['assigned_to' => $userId ?: null]);

        // Sync to all open chat conversations
        \App\Models\ChatConversation::where('customer_id', $this->customer->id)
            ->whereIn('status', ['open', 'pending'])
            ->update(['assigned_to' => $userId ?: null]);

        CustomerActivity::create([
            'customer_id' => $this->customer->id,
            'user_id' => auth()->id(),
            'type' => ActivityType::Assignment,
            'subject' => 'Customer assigned',
            'description' => 'Assigned to ' . (User::find($userId)?->name ?? 'nobody'),
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $this->customer->refresh();
        $this->dispatch('notify', ['message' => 'Customer assigned.', 'type' => 'success']);
    }

    public function logActivity(): void
    {
        $this->validate([
            'activityType' => 'required',
            'activitySubject' => 'required|string|max:255',
            'activityDescription' => 'nullable|string',
            'activityOutcome' => 'nullable|string|max:255',
        ]);

        CustomerActivity::create([
            'customer_id' => $this->customer->id,
            'user_id' => auth()->id(),
            'type' => $this->activityType,
            'subject' => $this->activitySubject,
            'description' => $this->activityDescription ?: null,
            'outcome' => $this->activityOutcome ?: null,
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        $this->showActivityModal = false;
        $this->activityType = 'note';
        $this->activitySubject = $this->activityDescription = $this->activityOutcome = '';
        $this->customer->refresh();
        $this->dispatch('notify', ['message' => 'Activity logged.', 'type' => 'success']);
    }

    public function uploadDocument(): void
    {
        $this->validate([
            'documentFile' => 'required|file|max:10240', // 10MB
            'documentName' => 'required|string|max:255',
        ]);

        $path = $this->documentFile->store('customer-documents', 'public');

        CustomerDocument::create([
            'customer_id' => $this->customer->id,
            'uploaded_by' => auth()->id(),
            'name' => $this->documentName,
            'file_path' => $path,
            'file_size' => $this->documentFile->getSize(),
            'mime_type' => $this->documentFile->getMimeType(),
        ]);

        $this->documentFile = null;
        $this->documentName = '';
        $this->customer->refresh();
        $this->dispatch('notify', ['message' => 'Document uploaded.', 'type' => 'success']);
    }

    public function deleteDocument(int $id): void
    {
        CustomerDocument::findOrFail($id)->delete();
        $this->customer->refresh();
        $this->dispatch('notify', ['message' => 'Document deleted.', 'type' => 'success']);
    }

    public function render()
    {
        return view('livewire.customers.customer-profile')
            ->layout('layouts.app');
    }
}
