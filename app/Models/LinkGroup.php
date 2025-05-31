<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LinkGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function links(): HasMany
    {
        return $this->hasMany(Link::class, 'group_id');
    }
}
