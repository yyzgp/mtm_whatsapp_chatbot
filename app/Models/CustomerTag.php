<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerTag extends Model
{
    protected $connection = 'shared';
    protected $table = 'sc_customer_tags';

    protected $fillable = ['name', 'color'];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'sc_customer_tag_pivot', 'tag_id', 'customer_id');
    }
}
