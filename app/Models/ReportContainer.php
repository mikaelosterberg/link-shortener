<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportContainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'name',
        'order_index',
        'flex_direction',
        'justify_content',
        'align_items',
        'gap',
        'min_height',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(ReportComponent::class, 'container_id')->orderBy('order_index');
    }

    /**
     * Get CSS styles for this container
     */
    public function getCssStylesAttribute(): string
    {
        return 'display: flex; '.
               "flex-direction: {$this->flex_direction}; ".
               "justify-content: {$this->justify_content}; ".
               "align-items: {$this->align_items}; ".
               "gap: {$this->gap}; ".
               "min-height: {$this->min_height}; ".
               'flex-wrap: wrap;';
    }

    /**
     * Get preset layout options
     */
    public static function getLayoutPresets(): array
    {
        return [
            'default' => [
                'name' => 'Default Row',
                'flex_direction' => 'row',
                'justify_content' => 'flex-start',
                'align_items' => 'stretch',
                'gap' => '16px',
                'min_height' => 'auto',
            ],
            'centered' => [
                'name' => 'Centered Row',
                'flex_direction' => 'row',
                'justify_content' => 'center',
                'align_items' => 'center',
                'gap' => '24px',
                'min_height' => '200px',
            ],
            'spaced' => [
                'name' => 'Spaced Apart',
                'flex_direction' => 'row',
                'justify_content' => 'space-between',
                'align_items' => 'stretch',
                'gap' => '16px',
                'min_height' => 'auto',
            ],
            'column' => [
                'name' => 'Vertical Stack',
                'flex_direction' => 'column',
                'justify_content' => 'flex-start',
                'align_items' => 'stretch',
                'gap' => '16px',
                'min_height' => 'auto',
            ],
        ];
    }
}
