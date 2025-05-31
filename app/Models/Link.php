<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'short_code',
        'original_url',
        'group_id',
        'redirect_type',
        'is_active',
        'expires_at',
        'created_by',
        'click_count',
        'custom_slug',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'click_count' => 'integer',
        'redirect_type' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LinkGroup::class, 'group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getFullUrlAttribute(): string
    {
        return url($this->short_code);
    }
}
