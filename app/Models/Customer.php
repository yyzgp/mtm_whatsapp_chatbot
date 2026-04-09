<?php

namespace App\Models;

use App\Enums\CustomerPriority;
use App\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'shared';
    protected $table = 'sc_customers';

    protected $fillable = [
        'ulid', 'assigned_to', 'created_by', 'source_id',
        'first_name', 'last_name', 'email', 'phone', 'phone_whatsapp',
        'company_name', 'job_title', 'address', 'city', 'country',
        'notes', 'status', 'priority', 'expected_value', 'actual_value',
        'expected_close_date', 'closed_at', 'lost_reason', 'custom_fields', 'avatar',
    ];

    protected $casts = [
        'status' => CustomerStatus::class,
        'priority' => CustomerPriority::class,
        'expected_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            $customer->ulid = Str::ulid()->toString();
        });

        static::deleting(function (Customer $customer) {
            $customer->conversations->each(function ($conversation) {
                $conversation->messages()->delete();
                $conversation->delete();
            });
        });
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getWhatsAppNumberAttribute(): string
    {
        return $this->phone_whatsapp ?? $this->phone;
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CustomerSource::class, 'source_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class)->latest();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CustomerStatusHistory::class)->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class)->latest();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class, 'sc_customer_tag_pivot', 'customer_id', 'tag_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function primaryConversation()
    {
        return $this->hasOne(ChatConversation::class)->latestOfMany();
    }

    /**
     * Scope: filter customers based on user's role and team position.
     *
     * - customers.view_all → no filter
     * - team leader → own assigned + assigned to team members
     * - otherwise → only own assigned
     */
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->can('customers.view_all')) {
            return $query;
        }

        // Check if user is a leader of any active team
        $ledTeams = Team::where('leader_id', $user->id)->active()->with('members')->get();

        if ($ledTeams->isNotEmpty()) {
            // Get all member IDs across teams this user leads
            $teamMemberIds = $ledTeams->flatMap(fn ($team) => $team->members->pluck('id'))
                ->push($user->id)
                ->unique()
                ->values()
                ->toArray();

            return $query->whereIn('assigned_to', $teamMemberIds);
        }

        // Regular agent: only their own
        return $query->where('assigned_to', $user->id);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('company_name', 'like', "%{$search}%");
        });
    }
}
