<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use App\Models\Link;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OverviewStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Use rolling time windows for consistent results across timezones
        $now = now();
        $last24Hours = $now->copy()->subHours(24);
        $last7Days = $now->copy()->subDays(7);

        // Total links
        $totalLinks = Link::count();
        $activeLinks = Link::where('is_active', true)->count();

        // Total clicks with rolling windows
        $totalClicks = Click::count();
        $last24HourClicks = Click::where('clicked_at', '>=', $last24Hours)->count();
        $weekClicks = Click::where('clicked_at', '>=', $last7Days)->count();

        // Average clicks per link
        $avgClicksPerLink = $totalLinks > 0 ? round($totalClicks / $totalLinks, 1) : 0;

        // Most popular redirect type
        $popularRedirectType = Link::select('redirect_type', DB::raw('count(*) as count'))
            ->groupBy('redirect_type')
            ->orderByDesc('count')
            ->first();

        $redirectTypeLabel = $popularRedirectType ?
            "Most used: {$popularRedirectType->redirect_type}" :
            'No links yet';

        return [
            Stat::make('Total Links', $totalLinks)
                ->description("{$activeLinks} active")
                ->descriptionIcon('heroicon-m-link')
                ->color('primary'),

            Stat::make('Total Clicks', number_format($totalClicks))
                ->description("Last 24h: {$last24HourClicks} | Last 7d: {$weekClicks}")
                ->descriptionIcon('heroicon-m-cursor-arrow-rays')
                ->color('success'),

            Stat::make('Avg Clicks/Link', $avgClicksPerLink)
                ->description($redirectTypeLabel)
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Click Rate', $this->getClickRate())
                ->description($this->getClickRateDescription())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($this->getClickRateColor()),
        ];
    }

    private function getClickRate(): string
    {
        // Use rolling 24-hour windows for consistent comparison
        $now = now();
        $last24Hours = Click::whereBetween('clicked_at', [$now->copy()->subHours(24), $now])->count();
        $previous24Hours = Click::whereBetween('clicked_at', [$now->copy()->subHours(48), $now->copy()->subHours(24)])->count();

        if ($previous24Hours == 0) {
            return $last24Hours > 0 ? '+100%' : '0%';
        }

        $change = (($last24Hours - $previous24Hours) / $previous24Hours) * 100;

        return ($change >= 0 ? '+' : '').round($change, 1).'%';
    }

    private function getClickRateDescription(): string
    {
        // Use same rolling windows as getClickRate() for consistency
        $now = now();
        $last24Hours = Click::whereBetween('clicked_at', [$now->copy()->subHours(24), $now])->count();
        $previous24Hours = Click::whereBetween('clicked_at', [$now->copy()->subHours(48), $now->copy()->subHours(24)])->count();

        return "Last 24h: {$last24Hours} vs Previous 24h: {$previous24Hours}";
    }

    private function getClickRateColor(): string
    {
        $rate = $this->getClickRate();
        if (str_starts_with($rate, '+')) {
            return 'success';
        } elseif (str_starts_with($rate, '-')) {
            return 'danger';
        }

        return 'gray';
    }
}
