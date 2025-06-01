<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class GeographicStatsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected function getStats(): array
    {
        // Get top countries by click count
        $topCountries = Click::select('country', DB::raw('count(*) as click_count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('click_count')
            ->limit(3)
            ->get();

        // Total clicks with location data
        $totalGeoClicks = Click::whereNotNull('country')->count();
        $totalClicks = Click::count();
        $geoPercentage = $totalClicks > 0 ? round(($totalGeoClicks / $totalClicks) * 100, 1) : 0;

        $stats = [
            Stat::make('Geographic Coverage', "{$geoPercentage}%")
                ->description($totalClicks > 0 ? 
                    "{$totalGeoClicks} of {$totalClicks} clicks have location data" : 
                    "No clicks recorded yet")
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color($geoPercentage > 0 ? 'success' : 'gray'),
        ];

        // Only show country stats if we have geographic data
        if ($topCountries->count() > 0) {
            foreach ($topCountries as $index => $country) {
                $rank = ['1st', '2nd', '3rd'][$index] ?? '';
                $stats[] = Stat::make("{$rank} Country", $country->country)
                    ->description("{$country->click_count} clicks")
                    ->descriptionIcon('heroicon-m-map-pin')
                    ->color('primary');
            }
        } else {
            // Show helpful message when no geo data
            $stats[] = Stat::make('Geographic Data', 'Not Available')
                ->description($totalClicks > 0 ? 
                    'Clicks from localhost/private IPs' : 
                    'Create some links and get clicks!')
                ->descriptionIcon('heroicon-m-information-circle')
                ->color('warning');
        }

        return $stats;
    }
    
    protected function getColumns(): int
    {
        return 4;
    }
}