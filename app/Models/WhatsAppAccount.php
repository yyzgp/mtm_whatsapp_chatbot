<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class WhatsAppAccount extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_whatsapp_accounts';

    protected $fillable = [
        'name', 'waba_id', 'access_token', 'verify_token', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = ['access_token'];

    public function getAccessTokenAttribute($value): string
    {
        if (empty($value)) return '';
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    public function setAccessTokenAttribute(string $value): void
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(WhatsAppPhoneNumber::class, 'whatsapp_account_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsAppTemplate::class, 'whatsapp_account_id');
    }
}
