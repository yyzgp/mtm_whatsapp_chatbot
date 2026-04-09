<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // Use the shared connection (no table prefix) to reuse mtm_backend's users table
    protected $connection = 'shared';

    // Force Spatie to always use 'web' guard for permissions,
    // even when authenticated via Sanctum (which resolves to 'sanctum' guard)
    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'is_active',
        'last_login_at',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function assignedCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'assigned_to');
    }

    public function createdCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'assigned_to');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'sc_team_user')->withTimestamps();
    }

    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function getTeamMemberIds(): array
    {
        return $this->ledTeams()->with('members')->get()
            ->flatMap(fn($team) => $team->members->pluck('id'))
            ->unique()
            ->toArray();
    }

    public function getAccessiblePhoneNumberIds(): array
    {
        return $this->teams()
            ->whereNotNull('whatsapp_phone_number_id')
            ->pluck('whatsapp_phone_number_id')
            ->unique()
            ->toArray();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAgents($query)
    {
        return $query->whereHas('roles', fn($q) => $q->whereIn('name', ['sales_agent', 'sales_manager', 'admin']));
    }

    /**
     * Override can() to always use Spatie's web guard for permission checks.
     * This prevents guard mismatch when authenticated via Sanctum API.
     */
    public function can($abilities, $arguments = [])
    {
        if (is_string($abilities)) {
            try {
                return $this->hasPermissionTo($abilities, 'web');
            } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist $e) {
                return false;
            }
        }

        return parent::can($abilities, $arguments);
    }
}
