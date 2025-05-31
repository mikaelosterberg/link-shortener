<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ClickTrendsChart extends ChartWidget
{
    protected static ?string $heading = 'Click Trends (Last 30 Days)';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $filter = '30';
    
    protected function getData(): array
    {
        $days = (int) $this->filter;
        
        // Get daily click counts for the specified period
        $clickData = Click::select(
                DB::raw('DATE(clicked_at) as date'),
                DB::raw('COUNT(*) as clicks')
            )
            ->where('clicked_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        // Create array of all dates in range to fill gaps
        $dateRange = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $dateRange->push(now()->subDays($i)->format('Y-m-d'));
        }
        
        // Map click data to dates, filling missing dates with 0
        $chartData = $dateRange->map(function ($date) use ($clickData) {
            $dayData = $clickData->firstWhere('date', $date);
            return $dayData ? $dayData->clicks : 0;
        });
        
        $labels = $dateRange->map(function ($date) {
            return \Carbon\Carbon::parse($date)->format('M j');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Daily Clicks',
                    'data' => $chartData->values()->toArray(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 3 months',
        ];
    }
    
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}