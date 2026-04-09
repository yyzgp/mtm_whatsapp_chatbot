<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerSource extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_customer_sources';

    protected $fillable = ['name', 'color', 'icon', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'source_id');
    }
}
