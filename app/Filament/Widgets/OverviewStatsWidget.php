<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use App\Models\Link;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OverviewStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Total links
        $totalLinks = Link::count();
        $activeLinks = Link::where('is_active', true)->count();
        
        // Total clicks
        $totalClicks = Click::count();
        $todayClicks = Click::whereDate('clicked_at', today())->count();
        $weekClicks = Click::where('clicked_at', '>=', now()->subDays(7))->count();
        
        // Average clicks per link
        $avgClicksPerLink = $totalLinks > 0 ? round($totalClicks / $totalLinks, 1) : 0;
        
        // Most popular redirect type
        $popularRedirectType = Link::select('redirect_type', DB::raw('count(*) as count'))
            ->groupBy('redirect_type')
            ->orderByDesc('count')
            ->first();
            
        $redirectTypeLabel = $popularRedirectType ? 
            "Most used: {$popularRedirectType->redirect_type}" : 
            "No links yet";

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
        $yesterdayClicks = Click::whereDate('clicked_at', today()->subDay())->count();
        $todayClicks = Click::whereDate('clicked_at', today())->count();
        
        if ($yesterdayClicks == 0) {
            return $todayClicks > 0 ? '+100%' : '0%';
        }
        
        $change = (($todayClicks - $yesterdayClicks) / $yesterdayClicks) * 100;
        return ($change >= 0 ? '+' : '') . round($change, 1) . '%';
    }
    
    private function getClickRateDescription(): string
    {
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