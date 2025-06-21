<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportComponent extends Model
{
    protected $fillable = [
        'report_id',
        'container_id',
        'component_type',
        'title',
        'config',
        'position_x',
        'position_y',
        'width',
        'height',
        'order_index',
        'flex_basis',
        'flex_grow',
        'flex_shrink',
    ];

    protected $casts = [
        'config' => 'array',
        'position_x' => 'integer',
        'position_y' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'order_index' => 'integer',
        'flex_grow' => 'integer',
        'flex_shrink' => 'integer',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(ReportContainer::class, 'container_id');
    }

    /**
     * Get CSS styles for this component's flex properties
     */
    public function getFlexStylesAttribute(): string
    {
        return "flex: {$this->flex_grow} {$this->flex_shrink} {$this->flex_basis};";
    }

    /**
     * Get preset size options
     */
    public static function getSizePresets(): array
    {
        return [
            'small' => ['flex_basis' => '25%', 'flex_grow' => 0, 'flex_shrink' => 1],
            'medium' => ['flex_basis' => '50%', 'flex_grow' => 0, 'flex_shrink' => 1],
            'large' => ['flex_basis' => '75%', 'flex_grow' => 0, 'flex_shrink' => 1],
            'full' => ['flex_basis' => '100%', 'flex_grow' => 0, 'flex_shrink' => 1],
            'auto' => ['flex_basis' => 'auto', 'flex_grow' => 1, 'flex_shrink' => 1],
        ];
    }

    public function getComponentTypeDisplayName(): string
    {
        return match ($this->component_type) {
            'metric_card' => 'Metric Card',
            'line_chart' => 'Line Chart',
            'bar_chart' => 'Bar Chart',
            'pie_chart' => 'Pie Chart',
            'data_table' => 'Data Table',
            'geographic_map' => 'Geographic Map',
            'text_block' => 'Text Block',
            default => ucwords(str_replace('_', ' ', $this->component_type)),
        };
    }

    public function getDefaultConfig(): array
    {
        return match ($this->component_type) {
            'metric_card' => [
                'metric' => 'total_clicks',
                'show_comparison' => true,
                'comparison_period' => 'previous_period',
                'color' => 'blue',
            ],
            'line_chart' => [
                'metric' => 'clicks_over_time',
                'period' => 'daily',
                'show_points' => true,
                'smooth' => false,
            ],
            'bar_chart' => [
                'metric' => 'top_links',
                'limit' => 10,
                'orientation' => 'vertical',
                'show_values' => true,
            ],
            'pie_chart' => [
                'metric' => 'traffic_sources',
                'limit' => 5,
                'show_labels' => true,
                'show_percentages' => true,
            ],
            'data_table' => [
                'metric' => 'link_performance',
                'columns' => ['link', 'clicks', 'unique_clicks', 'ctr'],
                'sortable' => true,
                'limit' => 20,
            ],
            'geographic_map' => [
                'metric' => 'clicks_by_country',
                'map_type' => 'world',
                'color_scheme' => 'blue',
            ],
            'text_block' => [
                'content' => 'Add your custom text here...',
                'alignment' => 'left',
                'font_size' => 'medium',
            ],
            default => [],
        };
    }
}
