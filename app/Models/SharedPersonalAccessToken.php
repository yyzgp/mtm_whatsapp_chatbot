<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class SharedPersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'shared';
    protected $table = 'personal_access_tokens';
}
