<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class SharedRole extends SpatieRole
{
    protected $connection = 'shared';
}
