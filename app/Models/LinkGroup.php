<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LinkGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'color',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function links(): HasMany
    {
        return $this->hasMany(Link::class, 'group_id');
    }

    public function setAsDefault(): void
    {
        // Unset any existing default
        self::where('is_default', true)->update(['is_default' => false]);

        // Set this group as default
        $this->is_default = true;
        $this->save();
    }

    public static function getDefault(): ?self
    {
        return self::where('is_default', true)->first();
    }
}
