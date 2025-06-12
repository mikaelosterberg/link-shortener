<?php

namespace App\Filament\Widgets;

use App\Models\Click;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrafficTypesWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected function getHeading(): string
    {
        return 'Traffic Types';
    }

    protected function getStats(): array
    {
        $totalClicks = Click::count();

        if ($totalClicks === 0) {
            return [
                Stat::make('Mobile Traffic', '0%')
                    ->description('No traffic data yet')
                    ->descriptionIcon('heroicon-m-device-phone-mobile')
                    ->color('gray'),
            ];
        }

        // Mobile traffic (phones and tablets)
        $mobileClicks = Click::where('user_agent', 'like', '%Mobile%')->count();
        $tabletClicks = Click::where('user_agent', 'like', '%Tablet%')->count();
        $mobilePercentage = round((($mobileClicks + $tabletClicks) / $totalClicks) * 100, 1);

        // Desktop traffic (everything else)
        $desktopClicks = $totalClicks - $mobileClicks - $tabletClicks;
        $desktopPercentage = round(($desktopClicks / $totalClicks) * 100, 1);

        // Bot traffic detection (basic)
        $botClicks = Click::where(function ($query) {
            $query->where('user_agent', 'like', '%bot%')
                ->orWhere('user_agent', 'like', '%crawler%')
                ->orWhere('user_agent', 'like', '%spider%')
                ->orWhere('user_agent', 'like', '%Googlebot%')
                ->orWhere('user_agent', 'like', '%Bingbot%');
        })->count();
        $botPercentage = round(($botClicks / $totalClicks) * 100, 1);

        // Direct vs Referral traffic
        $directClicks = Click::where(function ($query) {
            $query->whereNull('referer')->orWhere('referer', '');
        })->count();
        $referralClicks = $totalClicks - $directClicks;
        $referralPercentage = round(($referralClicks / $totalClicks) * 100, 1);

        return [
            Stat::make('Mobile/Tablet', "{$mobilePercentage}%")
                ->description("{$mobileClicks} mobile + {$tabletClicks} tablet clicks")
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color($mobilePercentage > 50 ? 'success' : 'primary'),

            Stat::make('Desktop', "{$desktopPercentage}%")
                ->description("{$desktopClicks} desktop clicks")
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color($desktopPercentage > 50 ? 'success' : 'info'),

            Stat::make('Referral Traffic', "{$referralPercentage}%")
                ->description("{$referralClicks} clicks from other sites")
                ->descriptionIcon('heroicon-m-link')
                ->color($referralPercentage > 30 ? 'success' : 'warning'),

            Stat::make('Bot Traffic', "{$botPercentage}%")
                ->description("{$botClicks} clicks from bots/crawlers")
                ->descriptionIcon('heroicon-m-bug-ant')
                ->color($botPercentage > 20 ? 'danger' : 'gray'),
        ];
    }
}
