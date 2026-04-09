<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class SharedPermission extends SpatiePermission
{
    protected $connection = 'shared';
}
