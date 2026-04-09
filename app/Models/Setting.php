<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_settings';

    protected $fillable = ['group', 'key', 'value', 'is_encrypted'];

    protected $casts = ['is_encrypted' => 'boolean'];

    public static function get(string $group, string $key, mixed $default = null): mixed
    {
        $setting = static::where('group', $group)->where('key', $key)->first();
        if (!$setting) return $default;
        if ($setting->is_encrypted && $setting->value) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Exception) {
                return $setting->value;
            }
        }
        return $setting->value;
    }

    public static function set(string $group, string $key, mixed $value, bool $encrypted = false): void
    {
        $storeValue = $encrypted && $value ? Crypt::encryptString((string) $value) : $value;
        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $storeValue, 'is_encrypted' => $encrypted]
        );
    }
}
