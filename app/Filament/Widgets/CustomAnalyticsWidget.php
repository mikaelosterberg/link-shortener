<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use App\Models\Link;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Example Custom Analytics Widget
 *
 * This is an example of how users can create custom Filament widgets
 * to display additional analytics or metrics on their dashboard.
 *
 * To use this widget:
 * 1. Uncomment the registration in CustomizationServiceProvider
 * 2. Customize the stats to show your specific metrics
 */
class CustomAnalyticsWidget extends BaseWidget
{
    protected static ?int $sort = 10; // Show after built-in widgets

    protected function getHeading(): string
    {
        return 'Custom Analytics';
    }

    protected function getStats(): array
    {
        // Example custom metrics
        $topReferrer = Click::selectRaw('referer, COUNT(*) as count')
            ->whereNotNull('referer')
            ->where('referer', '!=', '')
            ->groupBy('referer')
            ->orderByDesc('count')
            ->first();

        $mobileClicks = Click::where('user_agent', 'like', '%Mobile%')->count();
        $totalClicks = Click::count();
        $mobilePercentage = $totalClicks > 0 ? round(($mobileClicks / $totalClicks) * 100, 1) : 0;

        $averageClicksPerDay = $totalClicks > 0
            ? round($totalClicks / max(1, now()->diffInDays(Link::oldest()->first()?->created_at ?? now())), 1)
            : 0;

        return [
            Stat::make('Top Referrer', $topReferrer?->referer ? parse_url($topReferrer->referer, PHP_URL_HOST) : 'None')
                ->description($topReferrer ? "{$topReferrer->count} clicks" : 'No referrer data')
                ->descriptionIcon('heroicon-m-link')
                ->color('primary'),

            Stat::make('Mobile Traffic', "{$mobilePercentage}%")
                ->description("{$mobileClicks} of {$totalClicks} clicks from mobile")
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color($mobilePercentage > 50 ? 'success' : 'warning'),

            Stat::make('Daily Average', $averageClicksPerDay)
                ->description('Clicks per day since launch')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
