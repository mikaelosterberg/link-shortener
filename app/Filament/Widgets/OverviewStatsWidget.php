<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use App\Models\Link;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OverviewStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Get user's timezone for display, but use simpler date calculations for reliability
        $userTimezone = TimezoneService::getUserTimezone();

        try {
            $userToday = Carbon::now($userTimezone)->startOfDay();
            $userYesterday = $userToday->copy()->subDay();
            $userWeekAgo = $userToday->copy()->subDays(7);

            // Total links
            $totalLinks = Link::count();
            $activeLinks = Link::where('is_active', true)->count();

            // Total clicks (timezone-aware)
            $totalClicks = Click::count();
            $todayClicks = Click::whereBetween('clicked_at', [
                $userToday->utc(),
                $userToday->copy()->endOfDay()->utc(),
            ])->count();
            $weekClicks = Click::where('clicked_at', '>=', $userWeekAgo->utc())->count();
        } catch (\Exception $e) {
            // Fallback to UTC calculations if timezone conversion fails
            $totalLinks = Link::count();
            $activeLinks = Link::where('is_active', true)->count();
            $totalClicks = Click::count();
            $todayClicks = Click::whereDate('clicked_at', today())->count();
            $weekClicks = Click::where('clicked_at', '>=', now()->subDays(7))->count();
        }

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
                ->description("Today: {$todayClicks} | Week: {$weekClicks}")
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
        // Use simple UTC calculations for reliability
        $todayClicks = Click::whereDate('clicked_at', today())->count();
        $yesterdayClicks = Click::whereDate('clicked_at', today()->subDay())->count();

        if ($yesterdayClicks == 0) {
            return $todayClicks > 0 ? '+100%' : '0%';
        }

        $change = (($todayClicks - $yesterdayClicks) / $yesterdayClicks) * 100;

        return ($change >= 0 ? '+' : '').round($change, 1).'%';
    }

    private function getClickRateDescription(): string
    {
        // For now, let's use simple UTC calculations to ensure it works
        $todayClicks = Click::whereDate('clicked_at', today())->count();
        $yesterdayClicks = Click::whereDate('clicked_at', today()->subDay())->count();

        return "Today: {$todayClicks} vs Yesterday: {$yesterdayClicks}";
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
